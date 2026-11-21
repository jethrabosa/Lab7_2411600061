<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\PurchaseOrderStatusNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InventoryReplenishmentService
{
    private const LOOKBACK_DAYS = 90;

    /**
     * @return array{average_daily_usage: float, reorder_point: int, suggested_quantity: int}
     */
    public function recommendation(Product $product): array
    {
        $usage = (int) $product->transactions()
            ->where('type', 'stock_out')
            ->where('created_at', '>=', now()->subDays(self::LOOKBACK_DAYS))
            ->sum('quantity');
        $averageDailyUsage = $usage / self::LOOKBACK_DAYS;
        $leadTime = (int) ($product->lead_time_days ?? $product->supplier?->lead_time_days ?? 7);
        $reorderPoint = max(
            (int) $product->reorder_level,
            (int) ceil($averageDailyUsage * $leadTime)
        );
        $targetStock = max(
            $reorderPoint + max(1, (int) $product->reorder_level),
            (int) ceil($averageDailyUsage * ($leadTime + 30))
        );
        $outstanding = $product->purchaseOrderItems()
            ->whereHas('purchaseOrder', fn ($query) => $query->whereIn('status', PurchaseOrder::ACTIVE_STATUSES))
            ->selectRaw('COALESCE(SUM(quantity - received_quantity), 0) as outstanding_quantity')
            ->value('outstanding_quantity');

        return [
            'average_daily_usage' => round($averageDailyUsage, 2),
            'reorder_point' => $reorderPoint,
            'suggested_quantity' => max(0, $targetStock - (int) $product->quantity - (int) $outstanding),
        ];
    }

    /**
     * Create at most one outstanding replenishment order for each supplier.
     *
     * @return Collection<int, PurchaseOrder>
     */
    public function generatePurchaseOrders(): Collection
    {
        $products = Product::query()->with('supplier')
            ->whereNotNull('supplier_id')
            ->orderBy('supplier_id')
            ->orderBy('id')
            ->get();
        $recommendations = [];

        foreach ($products as $product) {
            $recommendation = $this->recommendation($product);
            if ($product->quantity <= $recommendation['reorder_point']
                && $recommendation['suggested_quantity'] > 0
                && $product->supplier) {
                $recommendations[$product->supplier_id][] = [$product, $recommendation['suggested_quantity']];
            }
        }

        $orders = collect();

        foreach ($recommendations as $supplierId => $items) {
            $order = DB::transaction(function () use ($supplierId, $items): ?PurchaseOrder {
                $supplier = Supplier::query()->lockForUpdate()->find($supplierId);
                if (! $supplier) {
                    return null;
                }
                $activeOrderExists = PurchaseOrder::query()
                    ->where('supplier_id', $supplier->id)
                    ->whereIn('status', PurchaseOrder::ACTIVE_STATUSES)
                    ->exists();
                if ($activeOrderExists) {
                    return null;
                }

                $order = PurchaseOrder::create([
                    'order_number' => 'PO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                    'supplier_id' => $supplier->id,
                    'created_by' => User::query()->where('is_admin', true)->value('id'),
                    'status' => 'pending_approval',
                    'lead_time_days' => $supplier->lead_time_days,
                ]);

                foreach ($items as [$product, $quantity]) {
                    $lockedProduct = Product::query()->lockForUpdate()->find($product->id);
                    if (! $lockedProduct || $lockedProduct->supplier_id !== $supplier->id) {
                        continue;
                    }

                    $recommendation = $this->recommendation($lockedProduct);
                    if ($lockedProduct->quantity > $recommendation['reorder_point']
                        || $recommendation['suggested_quantity'] < 1) {
                        continue;
                    }

                    $order->items()->create([
                        'product_id' => $lockedProduct->id,
                        'quantity' => min($quantity, $recommendation['suggested_quantity']),
                        'unit_cost' => $lockedProduct->supplier_unit_cost ?? $lockedProduct->unit_cost,
                    ]);
                }

                if (! $order->items()->exists()) {
                    $order->delete();

                    return null;
                }

                return $order->load(['supplier', 'items.product']);
            });

            if ($order) {
                $orders->push($order);
                $admins = User::query()->where('is_admin', true)->whereNotNull('email_verified_at')->get();
                Notification::send($admins, new PurchaseOrderStatusNotification($order, 'created'));
            }
        }

        return $orders;
    }
}
