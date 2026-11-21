<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->forceFill(['is_admin' => true])->save();

        $cementSupplier = Supplier::create([
            'name' => 'Central Building Supplies',
            'contact_name' => 'Maria Santos',
            'email' => 'maria@central-supplies.test',
            'phone' => '555-0101',
            'lead_time_days' => 5,
        ]);
        $steelSupplier = Supplier::create([
            'name' => 'Prime Steel Trading',
            'contact_name' => 'Alex Reyes',
            'email' => 'alex@prime-steel.test',
            'phone' => '555-0102',
            'lead_time_days' => 10,
        ]);
        $paintSupplier = Supplier::create([
            'name' => 'Color House Depot',
            'contact_name' => 'Jamie Cruz',
            'email' => 'jamie@color-house.test',
            'phone' => '555-0103',
            'lead_time_days' => 3,
        ]);

        Product::create([
            'name' => 'Cement Bags',
            'sku' => 'CEM-001',
            'category' => 'Building Materials',
            'description' => 'Standard cement bags for construction projects.',
            'quantity' => 0,
            'reorder_level' => 25,
            'unit_cost' => 8.50,
            'supplier_id' => $cementSupplier->id,
            'supplier_unit_cost' => 8.00,
        ]);

        Product::create([
            'name' => 'Steel Rods',
            'sku' => 'STEEL-010',
            'category' => 'Metalworks',
            'description' => 'High-strength steel rods for reinforcement.',
            'quantity' => 0,
            'reorder_level' => 30,
            'unit_cost' => 15.75,
            'supplier_id' => $steelSupplier->id,
            'supplier_unit_cost' => 14.50,
        ]);

        Product::create([
            'name' => 'Paint Buckets',
            'sku' => 'PAINT-404',
            'category' => 'Finishing',
            'description' => 'Paint buckets for interior and exterior use.',
            'quantity' => 0,
            'reorder_level' => 20,
            'unit_cost' => 22.00,
            'supplier_id' => $paintSupplier->id,
            'supplier_unit_cost' => 20.00,
        ]);

        foreach (Product::all() as $product) {
            $openingQuantity = match ($product->sku) {
                'CEM-001' => 120,
                'STEEL-010' => 24,
                'PAINT-404' => 46,
            };

            $product->transactions()->create([
                'user_id' => $user->id,
                'type' => 'stock_in',
                'quantity' => $openingQuantity,
                'reference_number' => 'OPENING-'.$product->sku,
                'notes' => 'Seeded opening stock',
            ]);
        }
    }
}
