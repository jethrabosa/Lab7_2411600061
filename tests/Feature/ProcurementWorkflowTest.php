<?php

namespace Tests\Feature;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProcurementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_procurement_pages_require_an_admin_user(): void
    {
        $this->get(route('suppliers.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())
            ->get(route('suppliers.index'))
            ->assertForbidden();
    }

    public function test_admin_dashboard_links_to_manual_purchase_order_creation(): void
    {
        $supplier = Supplier::create(['name' => 'Dashboard Supplier']);
        $order = PurchaseOrder::create([
            'order_number' => 'PO-DASHBOARD-001',
            'supplier_id' => $supplier->id,
            'status' => 'pending_approval',
        ]);

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('suppliers.index'))
            ->assertSee('Supplier Directory')
            ->assertSee('Manage suppliers')
            ->assertSee(route('purchase-orders.index'))
            ->assertSee('Purchase Orders')
            ->assertSee('Manage purchase orders')
            ->assertSee('1 awaiting approval')
            ->assertSee(route('purchase-orders.create'))
            ->assertSee('Create Purchase Order');
    }

    public function test_admin_can_create_a_purchase_order_for_products_from_one_supplier(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Manual Order Supplier', 'lead_time_days' => 6]);
        $product = Product::create([
            'name' => 'Roofing Sheets',
            'sku' => 'ROOF-001',
            'category' => 'Roofing',
            'quantity' => 12,
            'reorder_level' => 5,
            'unit_cost' => 25,
            'supplier_id' => $supplier->id,
            'supplier_unit_cost' => 22.50,
        ]);

        $this->actingAs($admin)
            ->get(route('purchase-orders.create'))
            ->assertOk()
            ->assertSee('Create Purchase Order')
            ->assertSee('Unit cost (PHP)')
            ->assertSee('₱22.50')
            ->assertSee('Roofing Sheets');

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'items' => [$product->id => 8],
        ]);

        $order = PurchaseOrder::query()->firstOrFail();
        $this->assertSame('pending_approval', $order->status);
        $this->assertSame($admin->id, $order->created_by);
        $this->assertSame(6, $order->lead_time_days);
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 8,
            'unit_cost' => '22.50',
        ]);
        $response->assertRedirect(route('purchase-orders.show', $order));
        $this->get(route('purchase-orders.show', $order))
            ->assertOk()
            ->assertSee('₱180.00');
    }

    public function test_manual_purchase_order_rejects_products_from_another_supplier(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Selected Supplier']);
        $otherSupplier = Supplier::create(['name' => 'Different Supplier']);
        $product = Product::create([
            'name' => 'Other Supplier Product',
            'sku' => 'OTHER-001',
            'category' => 'Other',
            'quantity' => 10,
            'reorder_level' => 2,
            'unit_cost' => 5,
            'supplier_id' => $otherSupplier->id,
        ]);

        $this->actingAs($admin)
            ->from(route('purchase-orders.create'))
            ->post(route('purchase-orders.store'), [
                'supplier_id' => $supplier->id,
                'items' => [$product->id => 3],
            ])
            ->assertRedirect(route('purchase-orders.create'))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_scheduled_replenishment_generates_a_supplier_order_and_does_not_duplicate_it(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create([
            'name' => 'Reliable Building Supply',
            'lead_time_days' => 5,
        ]);
        $product = Product::create([
            'name' => 'Cement Bag',
            'sku' => 'CEM-100',
            'category' => 'Building Materials',
            'quantity' => 5,
            'reorder_level' => 10,
            'unit_cost' => 12,
            'supplier_id' => $supplier->id,
            'supplier_unit_cost' => 9.5,
        ]);
        InventoryTransaction::withoutEvents(fn () => InventoryTransaction::create([
            'product_id' => $product->id,
            'type' => 'stock_out',
            'quantity' => 90,
            'created_at' => now()->subDays(30),
        ]));

        $this->actingAs($admin);
        Artisan::call('inventory:generate-purchase-orders');
        Artisan::call('inventory:generate-purchase-orders');

        $this->assertDatabaseCount('purchase_orders', 1);
        $order = PurchaseOrder::with('items')->firstOrFail();
        $this->assertSame('pending_approval', $order->status);
        $this->assertSame(30, $order->items->first()->quantity);
        $this->assertSame('9.50', $order->items->first()->unit_cost);
    }

    public function test_admin_approves_orders_and_receipts_update_stock_and_supplier_performance(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Concrete Co', 'lead_time_days' => 3]);
        $product = Product::create([
            'name' => 'Concrete Mix',
            'sku' => 'MIX-001',
            'category' => 'Building Materials',
            'quantity' => 2,
            'reorder_level' => 5,
            'unit_cost' => 10,
            'supplier_id' => $supplier->id,
        ]);
        $order = PurchaseOrder::create([
            'order_number' => 'PO-TEST-001',
            'supplier_id' => $supplier->id,
            'created_by' => $admin->id,
            'status' => 'pending_approval',
            'lead_time_days' => 3,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 4,
            'unit_cost' => 10,
        ]);
        $this->actingAs($admin);

        $this->post(route('purchase-orders.approve', $order))->assertRedirect();
        $this->post(route('purchase-orders.mark-ordered', $order))->assertRedirect();
        $this->assertSame('ordered', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->expected_delivery_at);

        $this->post(route('purchase-orders.receive', $order), [
            'received' => [$item->id => 2],
        ])->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 4]);
        $this->assertSame('partially_received', $order->fresh()->status);

        $this->post(route('purchase-orders.receive', $order), [
            'received' => [$item->id => 2],
        ])->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 6]);
        $this->assertSame('received', $order->fresh()->status);
        $this->assertDatabaseCount('inventory_transactions', 2);

        $this->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Concrete Co')
            ->assertSee('100%');
    }

    public function test_receipt_cannot_exceed_the_remaining_order_quantity(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Supplier']);
        $product = Product::create([
            'name' => 'Timber',
            'sku' => 'TIM-001',
            'category' => 'Lumber',
            'quantity' => 1,
            'reorder_level' => 1,
            'unit_cost' => 3,
        ]);
        $order = PurchaseOrder::create([
            'order_number' => 'PO-TEST-002',
            'supplier_id' => $supplier->id,
            'status' => 'ordered',
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_cost' => 3,
        ]);

        $this->actingAs($admin)
            ->from(route('purchase-orders.show', $order))
            ->post(route('purchase-orders.receive', $order), [
                'received' => [$item->id => 3],
            ])
            ->assertRedirect(route('purchase-orders.show', $order))
            ->assertSessionHasErrors('received.'.$item->id);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 1]);
        $this->assertSame(0, $item->fresh()->received_quantity);
    }

    public function test_archiving_supplier_keeps_historical_purchase_order_supplier_reference(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Old supplier']);
        $order = PurchaseOrder::create([
            'order_number' => 'PO-TEST-003',
            'supplier_id' => $supplier->id,
            'status' => 'received',
        ]);

        $this->actingAs($admin)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'));

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
        $this->assertSame('Old supplier', $order->fresh()->supplier->name);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }
}
