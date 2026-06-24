<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private array $orderStatuses = ['pending', 'contacted', 'confirmed', 'preparing', 'delivered', 'cancelled'];

    private array $paymentStatuses = ['unpaid', 'pending', 'paid', 'failed', 'refunded'];

    public function index(): Response
    {
        return Inertia::render('Admin/Orders/Index', [
            'orders' => Order::query()
                ->with('items')
                ->latest()
                ->get()
                ->map(fn (Order $order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'customer_area' => $order->customer_area,
                    'customer_address' => $order->customer_address,
                    'customer_notes' => $order->customer_notes,
                    'subtotal' => (float) $order->subtotal,
                    'total' => (float) $order->total,
                    'currency' => $order->currency,
                    'order_status' => $order->order_status,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'created_at' => $order->created_at?->format('Y-m-d H:i'),
                ]),
            'orderStatuses' => $this->orderStatuses,
            'paymentStatuses' => $this->paymentStatuses,
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load('items');

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'customer_area' => $order->customer_area,
                'customer_address' => $order->customer_address,
                'customer_notes' => $order->customer_notes,
                'subtotal' => (float) $order->subtotal,
                'discount_total' => (float) $order->discount_total,
                'delivery_fee' => (float) $order->delivery_fee,
                'total' => (float) $order->total,
                'currency' => $order->currency,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'whatsapp_message' => $order->whatsapp_message,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name_snapshot' => $item->product_name_snapshot,
                    'selected_size' => $item->selected_size,
                    'selected_color' => $item->selected_color,
                    'sku_snapshot' => $item->sku_snapshot,
                    'unit_price' => (float) $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => (float) $item->subtotal,
                ]),
            ],
            'orderStatuses' => $this->orderStatuses,
            'paymentStatuses' => $this->paymentStatuses,
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'order_status' => ['required', Rule::in($this->orderStatuses)],
            'payment_status' => ['required', Rule::in($this->paymentStatuses)],
        ]);

        $order->update($data);

        return back()->with('success', 'Order updated.');
    }
}
