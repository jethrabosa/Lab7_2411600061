<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\PurchaseOrderStatusNotification;
use App\Services\InventoryReplenishmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(): View
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier', 'creator'])
            ->withCount('items')
            ->latest()
            ->paginate(20);

        return view('purchase-orders.index', compact('orders'));
    }

    public function create(): View
    {
        $suppliers = Supplier::query()->orderBy('name')->get();
        $products = Product::query()
            ->whereHas('supplier')
            ->with('supplier')
            ->orderBy('name')
            ->get();

        return view('purchase-orders.create', compact('suppliers', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->whereNull('deleted_at'),
            ],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0'],
        ]);
        $quantities = collect($validated['items'])
            ->map(fn ($quantity) => (int) $quantity)
            ->filter(fn (int $quantity) => $quantity > 0);

        if ($quantities->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Enter a quantity for at least one product.',
            ]);
        }

        $products = Product::query()
            ->where('supplier_id', $validated['supplier_id'])
            ->whereIn('id', $quantities->keys())
            ->get()
            ->keyBy('id');

        if ($products->count() !== $quantities->count()) {
            throw ValidationException::withMessages([
                'items' => 'Choose products assigned to the selected supplier.',
            ]);
        }

        $purchaseOrder = DB::transaction(function () use ($validated, $quantities, $products): PurchaseOrder {
            $supplier = Supplier::query()->findOrFail($validated['supplier_id']);
            $order = PurchaseOrder::create([
                'order_number' => 'PO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'supplier_id' => $supplier->id,
                'created_by' => Auth::id(),
                'status' => 'pending_approval',
                'lead_time_days' => $supplier->lead_time_days,
            ]);

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $product->supplier_unit_cost ?? $product->unit_cost,
                ]);
            }

            return $order->load('supplier');
        });

        $admins = User::query()
            ->where('is_admin', true)
            ->whereNotNull('email_verified_at')
            ->get();
        Notification::send($admins, new PurchaseOrderStatusNotification($purchaseOrder, 'created'));

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order created and sent for approval.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'creator', 'approver', 'items.product']);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function generate(InventoryReplenishmentService $replenishment): RedirectResponse
    {
        $orders = $replenishment->generatePurchaseOrders();

        return redirect()->route('purchase-orders.index')
            ->with('success', "Generated {$orders->count()} purchase order(s) for approval.");
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->status === 'pending_approval', 409, 'Only pending purchase orders can be approved.');

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        $this->notifyOrderParticipants($purchaseOrder->fresh(['supplier']), 'approved');

        return back()->with('success', 'Purchase order approved.');
    }

    public function reject(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->status === 'pending_approval', 409, 'Only pending purchase orders can be rejected.');
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);
        $purchaseOrder->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);
        $this->notifyOrderParticipants($purchaseOrder->fresh(['supplier']), 'rejected');

        return back()->with('success', 'Purchase order rejected.');
    }

    public function markOrdered(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->status === 'approved', 409, 'Only approved purchase orders can be marked as ordered.');
        $purchaseOrder->update([
            'status' => 'ordered',
            'ordered_at' => now(),
            'expected_delivery_at' => now()->addDays($purchaseOrder->lead_time_days),
        ]);
        $this->notifyOrderParticipants($purchaseOrder->fresh(['supplier']), 'ordered');

        return back()->with('success', 'Purchase order marked as placed with the supplier.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless(in_array($purchaseOrder->status, ['ordered', 'partially_received'], true), 409, 'Only placed purchase orders can receive stock.');
        $validated = $request->validate([
            'received' => ['required', 'array'],
            'received.*' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($purchaseOrder, $validated): void {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);
            $items = $order->items()->lockForUpdate()->get();
            $receivedAny = false;
            $fullyReceived = true;

            foreach ($items as $item) {
                $additionalQuantity = (int) ($validated['received'][$item->id] ?? 0);
                $remaining = $item->quantity - $item->received_quantity;

                if ($additionalQuantity > $remaining) {
                    throw ValidationException::withMessages([
                        'received.'.$item->id => 'The received quantity cannot exceed the outstanding quantity.',
                    ]);
                }

                $projectedReceived = $item->received_quantity + $additionalQuantity;
                $fullyReceived = $fullyReceived
                    && $projectedReceived >= $item->quantity;

                if ($additionalQuantity === 0) {
                    continue;
                }

                $receivedAny = true;
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $product->transactions()->create([
                    'user_id' => Auth::id(),
                    'type' => 'stock_in',
                    'quantity' => $additionalQuantity,
                    'reference_number' => $order->order_number,
                    'notes' => 'Purchase order delivery received.',
                ]);
                $item->increment('received_quantity', $additionalQuantity);
            }

            if (! $receivedAny) {
                throw ValidationException::withMessages([
                    'received' => 'Enter at least one quantity received.',
                ]);
            }
            $order->update([
                'status' => $fullyReceived ? 'received' : 'partially_received',
                'received_at' => $fullyReceived ? now() : null,
            ]);
        });

        $updatedOrder = $purchaseOrder->fresh(['supplier']);
        $this->notifyOrderParticipants(
            $updatedOrder,
            $updatedOrder->status === 'received' ? 'received' : 'partially_received'
        );

        return back()->with('success', 'Delivery quantities recorded and inventory updated.');
    }

    private function notifyOrderParticipants(PurchaseOrder $purchaseOrder, string $event): void
    {
        $recipients = collect([$purchaseOrder->creator])
            ->filter()
            ->merge(User::query()->where('is_admin', true)->whereNotNull('email_verified_at')->get())
            ->unique('id')
            ->values();

        Notification::send($recipients, new PurchaseOrderStatusNotification($purchaseOrder, $event));
    }
}
