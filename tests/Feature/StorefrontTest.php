<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_seeded_storefront(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Storefront/Home')
                ->where('locale', 'en')
                ->has('categories', 3)
                ->has('bestSellers'));
    }

    public function test_shop_supports_arabic_locale(): void
    {
        $this->seed();

        $this->get('/ar/shop')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Storefront/Shop')
                ->where('locale', 'ar')
                ->where('isRtl', true)
                ->has('products.data'));
    }

    public function test_whatsapp_order_is_saved_before_redirect_url_is_returned(): void
    {
        $this->seed();
        Setting::updateOrCreate(
            ['key' => 'whatsapp_number'],
            ['value' => '+961 71 234 567', 'type' => 'string', 'is_public' => true],
        );

        $product = Product::query()->with('variants')->where('stock_quantity', '>', 0)->firstOrFail();
        $variant = $product->variants->firstWhere('stock_quantity', '>', 0);

        $response = $this->postJson('/orders/whatsapp', [
            'locale' => 'en',
            'website' => '',
            'payment_method' => 'cash_on_delivery',
            'customer' => [
                'name' => 'Zainab Client',
                'phone' => '+961 70 000 000',
                'area' => 'Beirut',
                'address' => 'Pickup from store',
                'notes' => 'Please confirm availability.',
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure(['order_number', 'whatsapp_url']);

        $this->assertStringStartsWith('https://wa.me/96171234567?text=', $response->json('whatsapp_url'));

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Zainab Client',
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'unpaid',
        ]);

        $this->assertNotEmpty(Order::first()?->whatsapp_message);
        $this->assertStringContainsString('Payment: Cash on delivery', Order::first()?->whatsapp_message);
    }
}
