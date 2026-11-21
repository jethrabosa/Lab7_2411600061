<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::query()->withCount(['purchaseOrders', 'receivedPurchaseOrders'])
            ->with(['purchaseOrders' => fn ($query) => $query->where('status', 'received')->with('items')])
            ->orderBy('name')
            ->paginate(15);

        foreach ($suppliers as $supplier) {
            $receivedOrders = $supplier->purchaseOrders;
            $onTimeOrders = $receivedOrders->filter(
                fn ($order) => $order->expected_delivery_at
                    && $order->received_at
                    && $order->received_at->lessThanOrEqualTo($order->expected_delivery_at)
            );
            $items = $receivedOrders->flatMap->items;
            $accurateItems = $items->filter(
                fn ($item) => $item->received_quantity === $item->quantity
            );

            $supplier->setAttribute(
                'on_time_rate',
                $receivedOrders->isEmpty() ? null : round($onTimeOrders->count() / $receivedOrders->count() * 100)
            );
            $supplier->setAttribute(
                'order_accuracy',
                $items->isEmpty() ? null : round($accurateItems->count() / $items->count() * 100)
            );
            $supplier->setAttribute(
                'average_delivery_days',
                $receivedOrders->isEmpty()
                    ? null
                    : round($receivedOrders->avg(fn ($order) => $order->ordered_at?->diffInDays($order->received_at)), 1)
            );
        }

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier archived. Historical purchase orders remain available.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'lead_time_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
