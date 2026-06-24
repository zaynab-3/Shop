<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_pages_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@wheik.test')->firstOrFail();

        foreach ([
            route('admin.dashboard'),
            route('admin.products.index'),
            route('admin.categories.index'),
            route('admin.orders.index'),
            route('admin.pages.index'),
            route('admin.settings.index'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_can_create_update_and_delete_product_with_variants(): void
    {
        $admin = $this->adminUser();
        $category = Category::create([
            'name' => 'Women',
            'slug' => 'women',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'audience_type' => 'women',
            'title' => 'Test Blazer',
            'slug' => 'test-blazer',
            'sku' => 'TEST-BLAZER',
            'price' => 50,
            'sale_price' => null,
            'currency' => 'USD',
            'short_description' => 'Short',
            'description' => 'Long',
            'stock_quantity' => 0,
            'is_featured' => true,
            'is_active' => true,
            'is_new' => true,
            'is_best_seller' => false,
            'sort_order' => 1,
            'meta_title' => 'Meta',
            'meta_description' => 'Meta description',
            'tags_text' => 'test, blazer',
            'variants' => [
                ['size' => 'S', 'color' => 'Black', 'sku' => 'TEST-BLAZER-S', 'price' => null, 'sale_price' => null, 'stock_quantity' => 4, 'is_active' => true],
                ['size' => 'M', 'color' => 'Black', 'sku' => 'TEST-BLAZER-M', 'price' => null, 'sale_price' => null, 'stock_quantity' => 8, 'is_active' => true],
            ],
        ])->assertRedirect();

        $product = Product::where('slug', 'test-blazer')->firstOrFail();
        $this->assertSame(12, $product->stock_quantity);
        $this->assertSame(2, $product->variants()->count());

        $this->actingAs($admin)->patch(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'audience_type' => 'men',
            'title' => 'Updated Blazer',
            'slug' => 'updated-blazer',
            'sku' => 'TEST-BLAZER',
            'price' => 55,
            'sale_price' => null,
            'currency' => 'USD',
            'short_description' => 'Short',
            'description' => 'Long',
            'stock_quantity' => 9,
            'is_featured' => false,
            'is_active' => true,
            'is_new' => false,
            'is_best_seller' => true,
            'sort_order' => 2,
            'meta_title' => null,
            'meta_description' => null,
            'tags_text' => '',
            'variants' => [],
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame('updated-blazer', $product->slug);
        $this->assertSame(9, $product->stock_quantity);
        $this->assertSame(0, $product->variants()->count());

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product))->assertRedirect();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_manage_categories_pages_and_order_statuses(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Accessories',
            'slug' => 'accessories',
            'description' => 'Small pieces',
            'is_active' => true,
            'sort_order' => 4,
            'meta_title' => null,
            'meta_description' => null,
        ])->assertRedirect();

        $category = Category::where('slug', 'accessories')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.categories.update', $category), [
            'name' => 'Accessories Edit',
            'slug' => 'accessories-edit',
            'description' => 'Updated',
            'is_active' => false,
            'sort_order' => 5,
            'meta_title' => null,
            'meta_description' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'slug' => 'accessories-edit', 'is_active' => false]);

        $this->actingAs($admin)->post(route('admin.pages.store'), [
            'page_key' => 'fit_guide',
            'title' => 'Fit Guide',
            'slug' => 'fit-guide',
            'content' => 'Sizing help',
            'is_active' => false,
            'show_in_nav' => false,
            'noindex' => true,
            'sort_order' => 9,
            'meta_title' => null,
            'meta_description' => null,
        ])->assertRedirect();

        $page = Page::where('slug', 'fit-guide')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.pages.update', $page), [
            'page_key' => 'fit_guide',
            'title' => 'Fit Guide Live',
            'slug' => 'fit-guide-live',
            'content' => 'Sizing help',
            'is_active' => true,
            'show_in_nav' => true,
            'noindex' => false,
            'sort_order' => 9,
            'meta_title' => null,
            'meta_description' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'slug' => 'fit-guide-live', 'is_active' => true]);

        $order = Order::create([
            'order_number' => 'WHEIK-TEST',
            'customer_name' => 'Customer',
            'customer_phone' => '+96170000000',
            'customer_area' => 'Beirut',
            'customer_address' => 'Pickup',
            'subtotal' => 10,
            'discount_total' => 0,
            'delivery_fee' => 0,
            'total' => 10,
            'currency' => 'USD',
            'order_status' => 'pending',
            'payment_method' => 'whatsapp_manual',
            'payment_status' => 'unpaid',
            'source' => 'website',
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), [
            'order_status' => 'confirmed',
            'payment_status' => 'pending',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_status' => 'confirmed', 'payment_status' => 'pending']);

        $this->actingAs($admin)->delete(route('admin.pages.destroy', $page))->assertRedirect();
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_can_upload_product_and_homepage_images(): void
    {
        Storage::fake('public');

        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => null,
            'audience_type' => 'women',
            'title' => 'Image Product',
            'slug' => 'image-product',
            'sku' => null,
            'price' => 25,
            'sale_price' => null,
            'currency' => 'USD',
            'short_description' => null,
            'description' => null,
            'stock_quantity' => 3,
            'is_featured' => false,
            'is_active' => true,
            'is_new' => false,
            'is_best_seller' => false,
            'sort_order' => 0,
            'meta_title' => null,
            'meta_description' => null,
            'tags_text' => '',
            'variants' => [],
            'product_images' => [
                UploadedFile::fake()->image('front.jpg', 900, 1200),
            ],
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('slug', 'image-product')->firstOrFail();
        $image = $product->images()->with('media')->firstOrFail();
        $this->assertStringStartsWith('products/'.$product->id.'/', $image->media->path);
        Storage::disk('public')->assertExists($image->media->path);

        $this->actingAs($admin)->post(route('admin.homepage.store'), [
            'section_key' => 'test_upload_section',
            'title' => 'Upload Section',
            'subtitle' => null,
            'content' => null,
            'button_text' => null,
            'button_url' => null,
            'image_file' => UploadedFile::fake()->image('hero.jpg', 1200, 700),
            'image_alt_text' => 'Uploaded hero',
            'is_active' => true,
            'sort_order' => 99,
        ])->assertRedirect(route('admin.homepage.index'));

        $section = \App\Models\HomepageSection::where('section_key', 'test_upload_section')->firstOrFail();
        $this->assertStringStartsWith('homepage/test_upload_section/', $section->image->path);
        Storage::disk('public')->assertExists($section->image->path);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Main Admin', 'slug' => 'main_admin']);

        $user->roles()->attach($role);

        return $user;
    }
}
