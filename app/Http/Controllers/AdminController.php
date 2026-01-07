<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        // 1. Stats
        $stats = [
            'users'           => User::count(),
            'products'        => Product::count(),
            'categories'      => Category::count(),
            'total_orders'    => Order::count(),
            'total_revenue'   => Order::sum('total_amount'),
            'pending_orders'  => Order::where('status', 'pending')->count(),
            'low_stock'       => Product::where('stock', '<', 5)->count(),
        ];

        // 2. Recent Orders (5 terbaru)
        $recentOrders = Order::with('user')->latest()->take(5)->get();

        // 3. Top Selling Products (6 produk terlaris)
        $topProducts = Product::join('order_items', 'products.id', '=', 'order_items.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.category_id',
                'products.price',
                'products.stock',
                DB::raw('SUM(order_items.quantity) as sold')
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.category_id',
                'products.price',
                'products.stock'
            )
            ->orderByDesc('sold')
            ->take(6)
            ->get();

        // 4. Revenue chart (7 hari terakhir)
        $revenueChart = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Kirim semua data ke view
        return view('admin.dashboard', compact('stats', 'recentOrders', 'topProducts', 'revenueChart'));
    }
}