<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (filled($request->input('website'))) {
            throw ValidationException::withMessages(['website' => 'Invalid order request.']);
        }

        $validated = $request->validate([
            'locale' => ['nullable', 'in:en,fr,ar'],
            'customer.name' => ['required', 'string', 'max:120'],
            'customer.phone' => ['required', 'string', 'max:16', 'regex:/^\\+[1-9]\\d{6,14}$/'],
            'customer.area' => ['required', 'string', 'max:120'],
            'customer.address' => ['required', 'string', 'max:500'],
            'customer.notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'in:cash_on_delivery,whatsapp_manual'],
            'items' => ['required', 'array', 'min:1', 'max:25'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $locale = storefront_locale($validated['locale'] ?? 'en');
        $currency = Setting::getValue('default_currency', 'USD');
        $order = DB::transaction(function () use ($validated, $request, $locale, $currency) {
            $subtotal = 0;
            $items = collect($validated['items'])->map(function (array $item) use (&$subtotal, $locale) {
                $product = Product::query()
                    ->active()
                    ->with('translations')
                    ->findOrFail($item['product_id']);
                $variant = isset($item['variant_id'])
                    ? ProductVariant::query()->where('product_id', $product->id)->where('is_active', true)->findOrFail($item['variant_id'])
                    : null;

                $available = $variant?->stock_quantity ?? $product->stock_quantity;
                if ($available < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => $product->localized('title', $locale).' does not have enough stock.',
                    ]);
                }

                $unitPrice = (float) ($variant?->sale_price ?? $variant?->price ?? $product->sale_price ?? $product->price);
                $lineSubtotal = $unitPrice * $item['quantity'];
                $subtotal += $lineSubtotal;

                return [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ];
            });

            $order = Order::create([
                'order_number' => 'SCB-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'customer_name' => $validated['customer']['name'],
                'customer_phone' => $validated['customer']['phone'],
                'customer_area' => $validated['customer']['area'],
                'customer_address' => $validated['customer']['address'],
                'customer_notes' => $validated['customer']['notes'] ?? null,
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'delivery_fee' => 0,
                'total' => $subtotal,
                'currency' => $currency,
                'order_status' => 'pending',
                'payment_method' => $validated['payment_method'] ?? 'cash_on_delivery',
                'payment_status' => 'unpaid',
                'source' => 'website',
                'safe_ip_hash_or_masked_ip' => $this->safeIp($request),
                'user_agent_hash_optional' => $request->userAgent() ? hash('sha256', $request->userAgent()) : null,
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $variant = $item['variant'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name_snapshot' => $product->localized('title', $locale),
                    'selected_size' => $variant?->size,
                    'selected_color' => $variant?->color,
                    'sku_snapshot' => $variant?->sku ?? $product->sku,
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $order->load('items');
            $order->update(['whatsapp_message' => $this->messageFor($order)]);

            return $order;
        });

        $number = preg_replace('/\D+/', '', Setting::getValue('whatsapp_number', '96100000000'));

        return response()->json([
            'order_number' => $order->order_number,
            'whatsapp_url' => 'https://wa.me/'.$number.'?text='.rawurlencode($order->whatsapp_message),
        ]);
    }

    private function messageFor(Order $order): string
    {
        $lines = [
            'Scarbina Order Receipt',
            'Order: '.$order->order_number,
            'Time: '.$order->created_at?->format('Y-m-d H:i'),
            '',
            'Customer: '.$order->customer_name,
            'Phone: '.$order->customer_phone,
            'Area: '.$order->customer_area,
            'Address/Pickup: '.$order->customer_address,
        ];

        if ($order->customer_notes) {
            $lines[] = 'Notes: '.$order->customer_notes;
        }

        $lines[] = '';
        $lines[] = 'Items:';

        foreach ($order->items as $item) {
            $variant = collect([$item->selected_color, $item->selected_size])->filter()->join(' / ');
            $lines[] = '- '.$item->product_name_snapshot.($variant ? ' ('.$variant.')' : '').' x'.$item->quantity.' @ '.number_format((float) $item->unit_price, 2).' '.$order->currency.' = '.number_format((float) $item->subtotal, 2).' '.$order->currency;
        }

        $lines[] = '';
        $lines[] = 'Total: '.number_format((float) $order->total, 2).' '.$order->currency;
        $lines[] = 'Payment: '.$this->paymentMethodLabel($order->payment_method);

        return implode("\n", $lines);
    }

    private function paymentMethodLabel(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'cash_on_delivery' => 'Cash on delivery',
            default => 'WhatsApp/manual confirmation',
        };
    }

    private function safeIp(Request $request): ?string
    {
        $ip = $request->ip();

        if (! $ip) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $masked = implode('.', [$parts[0], $parts[1], $parts[2], '0']);
        } else {
            $masked = Str::of($ip)->beforeLast(':')->beforeLast(':')->append('::')->toString();
        }

        return hash('sha256', $masked.config('app.key'));
    }
}
