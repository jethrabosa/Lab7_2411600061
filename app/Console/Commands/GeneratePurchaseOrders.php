<?php

namespace App\Console\Commands;

use App\Services\InventoryReplenishmentService;
use Illuminate\Console\Command;

class GeneratePurchaseOrders extends Command
{
    protected $signature = 'inventory:generate-purchase-orders';

    protected $description = 'Generate approval-ready purchase orders for products that need replenishment';

    public function handle(InventoryReplenishmentService $replenishment): int
    {
        $orders = $replenishment->generatePurchaseOrders();
        $this->info("Generated {$orders->count()} purchase order(s) awaiting approval.");

        return self::SUCCESS;
    }
}
