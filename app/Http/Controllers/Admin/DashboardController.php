<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'products' => Product::count(),
                'activeProducts' => Product::where('is_active', true)->count(),
                'categories' => Category::count(),
                'pendingOrders' => Order::where('order_status', 'pending')->count(),
                'inactivePages' => Page::where('is_active', false)->count(),
            ],
            'recentOrders' => Order::query()
                ->latest()
                ->limit(6)
                ->get(['order_number', 'customer_name', 'total', 'currency', 'order_status', 'payment_status', 'created_at']),
            'inventory' => Product::query()
                ->with('category')
                ->orderBy('stock_quantity')
                ->limit(8)
                ->get(['id', 'title', 'sku', 'stock_quantity', 'stock_status', 'is_active', 'category_id']),
            'pages' => Page::query()
                ->orderBy('sort_order')
                ->get(['page_key', 'title', 'is_active', 'show_in_nav', 'noindex']),
            'settings' => Setting::publicMap(),
        ]);
    }
}
