<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $lowStockProducts = Product::lowStock()->count();
        $totalQuantity = Product::sum('quantity');
        $recentTransactions = InventoryTransaction::with(['product', 'user'])->latest()->take(5)->get();
        $pendingPurchaseOrders = Auth::user()?->is_admin
            ? PurchaseOrder::where('status', 'pending_approval')->count()
            : 0;
        $openPurchaseOrders = Auth::user()?->is_admin
            ? PurchaseOrder::whereIn('status', ['approved', 'ordered', 'partially_received'])->count()
            : 0;
        $totalPurchaseOrders = Auth::user()?->is_admin ? PurchaseOrder::count() : 0;
        $totalSuppliers = Auth::user()?->is_admin ? Supplier::count() : 0;

        return view('dashboard', compact(
            'totalProducts',
            'lowStockProducts',
            'totalQuantity',
            'recentTransactions',
            'pendingPurchaseOrders',
            'openPurchaseOrders',
            'totalPurchaseOrders',
            'totalSuppliers'
        ));
    }

    public function stats()
    {
        return response()->json([
            'total_products' => Product::count(),
            'low_stock_products' => Product::lowStock()->count(),
            'total_quantity' => Product::sum('quantity'),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function lowStock()
    {
        return response()->json(Product::lowStock()->select('id', 'name', 'quantity', 'reorder_level')->get());
    }

    public function lowStockPage()
    {
        $products = Product::lowStock()->orderBy('name')->paginate(20);

        return view('notifications.index', compact('products'));
    }

    public function recentTransactions()
    {
        return response()->json(
            InventoryTransaction::with(['product:id,name', 'user:id,name'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (InventoryTransaction $transaction) => [
                    'id' => $transaction->id,
                    'product_id' => $transaction->product_id,
                    'product_name' => $transaction->product?->name ?? 'Unknown product',
                    'type' => $transaction->type,
                    'quantity' => $transaction->quantity,
                    'signed_quantity' => $transaction->signed_quantity,
                    'user_name' => $transaction->user?->name ?? 'System',
                    'created_at' => $transaction->created_at->format('M d, Y H:i'),
                ])
        );
    }
}
