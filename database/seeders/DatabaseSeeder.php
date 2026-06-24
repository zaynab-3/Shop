<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            ['name' => 'Main Admin', 'slug' => 'main_admin', 'description' => 'Full access to website, orders, products, pages, settings, and users.'],
            ['name' => 'Product Admin', 'slug' => 'product_admin', 'description' => 'Manage products, categories, media, and stock.'],
            ['name' => 'Editor', 'slug' => 'editor', 'description' => 'Manage pages, SEO, and homepage editorial content.'],
            ['name' => 'Order Manager', 'slug' => 'order_manager', 'description' => 'Manage customer orders and WhatsApp follow-up status.'],
        ])->map(fn (array $role) => Role::updateOrCreate(['slug' => $role['slug']], $role));

        $admin = User::updateOrCreate(
            ['email' => 'admin@scarbina.test'],
            [
                'name' => 'Scarbina Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->roles()->sync([$roles->firstWhere('slug', 'main_admin')->id]);

        collect([
            ['key' => 'brand_name', 'value' => 'SCARBINA', 'type' => 'string', 'is_public' => true],
            ['key' => 'instagram_handle', 'value' => 'scarbina_shoes', 'type' => 'string', 'is_public' => true],
            ['key' => 'whatsapp_number', 'value' => '96100000000', 'type' => 'string', 'is_public' => true],
            ['key' => 'default_currency', 'value' => 'USD', 'type' => 'string', 'is_public' => true],
            ['key' => 'cookie_banner_enabled', 'value' => '1', 'type' => 'boolean', 'is_public' => true],
        ])->each(fn (array $setting) => Setting::updateOrCreate(['key' => $setting['key']], $setting));

        $media = collect([
            'hero' => ['name' => 'Scarbina hero', 'remote_url' => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=1800&q=82', 'alt_text' => 'Elegant feminine shoes on display'],
            'heels' => ['name' => 'Heels collection', 'remote_url' => 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=1200&q=80', 'alt_text' => 'High heels portrait'],
            'flats' => ['name' => 'Flats collection', 'remote_url' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=1200&q=80', 'alt_text' => 'Comfortable flat shoes'],
            'boots' => ['name' => 'Boots collection', 'remote_url' => 'https://images.unsplash.com/photo-1520639888713-7851133b1ed0?auto=format&fit=crop&w=1200&q=80', 'alt_text' => 'Stylish boots for women'],
            'rack' => ['name' => 'Shoe rack', 'remote_url' => 'https://images.unsplash.com/photo-1595341888016-a392ef81b7de?auto=format&fit=crop&w=1400&q=80', 'alt_text' => 'Shoe display rack'],
            'black-heels' => ['name' => 'Black stiletto heels', 'remote_url' => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=1200&q=80', 'alt_text' => 'Black stiletto heels'],
            'leather-boots' => ['name' => 'Leather ankle boots', 'remote_url' => 'https://images.unsplash.com/photo-1520639888713-7851133b1ed0?auto=format&fit=crop&w=1200&q=80', 'alt_text' => 'Leather ankle boots detail'],
            'nude-flats' => ['name' => 'Nude ballet flats', 'remote_url' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=1200&q=80', 'alt_text' => 'Nude ballet flats'],
            'sneakers' => ['name' => 'Casual sneakers', 'remote_url' => 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?auto=format&fit=crop&w=1200&q=80', 'alt_text' => 'White casual sneakers'],
        ])->mapWithKeys(fn (array $asset, string $key) => [
            $key => MediaAsset::updateOrCreate(['name' => $asset['name']], [...$asset, 'is_active' => true]),
        ]);

        $categories = collect([
            'heels' => [
                'image_id' => $media['heels']->id,
                'name' => 'Heels',
                'description' => 'Elegant and bold heels for special occasions.',
                'translations' => [
                    'en' => ['name' => 'Heels', 'slug' => 'heels', 'description' => 'Elegant and bold heels for special occasions.'],
                    'fr' => ['name' => 'Talons', 'slug' => 'talons', 'description' => 'Talons élégants et audacieux.'],
                    'ar' => ['name' => 'كعب عالي', 'slug' => 'heels-ar', 'description' => 'أحذية كعب عالي أنيقة للمناسبات.'],
                ],
            ],
            'flats' => [
                'image_id' => $media['flats']->id,
                'name' => 'Flats',
                'description' => 'Comfortable and stylish flats for everyday wear.',
                'translations' => [
                    'en' => ['name' => 'Flats', 'slug' => 'flats', 'description' => 'Comfortable and stylish flats for everyday wear.'],
                    'fr' => ['name' => 'Plates', 'slug' => 'plates', 'description' => 'Chaussures plates confortables.'],
                    'ar' => ['name' => 'أحذية مسطحة', 'slug' => 'flats-ar', 'description' => 'أحذية مسطحة مريحة وأنيقة يومية.'],
                ],
            ],
            'boots' => [
                'image_id' => $media['boots']->id,
                'name' => 'Boots',
                'description' => 'Sleek and versatile boots for any season.',
                'translations' => [
                    'en' => ['name' => 'Boots', 'slug' => 'boots', 'description' => 'Sleek and versatile boots for any season.'],
                    'fr' => ['name' => 'Bottes', 'slug' => 'bottes', 'description' => 'Bottes élégantes pour toute saison.'],
                    'ar' => ['name' => 'جزم', 'slug' => 'boots-ar', 'description' => 'جزم أنيقة لكل الفصول.'],
                ],
            ],
        ])->mapWithKeys(function (array $category, string $slug) {
            $model = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'image_id' => $category['image_id'],
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                    'sort_order' => ['heels' => 1, 'flats' => 2, 'boots' => 3][$slug],
                    'meta_title' => 'Scarbina '.$category['name'],
                    'meta_description' => $category['description'],
                ]
            );

            foreach ($category['translations'] as $locale => $translation) {
                $model->translations()->updateOrCreate(['locale' => $locale], [
                    ...$translation,
                    'meta_title' => 'Scarbina '.$translation['name'],
                    'meta_description' => $translation['description'],
                ]);
            }

            return [$slug => $model];
        });

        $this->seedProducts($categories, $media);
        $this->seedHomepage($media);
        $this->seedPages();
    }

    private function seedProducts($categories, $media): void
    {
        $products = [
            [
                'category' => 'heels',
                'audience_type' => 'women',
                'title' => 'Classic Black Stilettos',
                'slug' => 'classic-black-stilettos',
                'sku' => 'SCB-H-BLK-01',
                'price' => 120,
                'sale_price' => null,
                'stock_quantity' => 15,
                'is_featured' => true,
                'is_new' => true,
                'is_best_seller' => false,
                'image' => 'black-heels',
                'tags' => ['heels', 'black', 'classic'],
                'translations' => [
                    'en' => ['title' => 'Classic Black Stilettos', 'slug' => 'classic-black-stilettos', 'short_description' => 'Timeless black stilettos with a sleek pointed toe.', 'description' => 'Designed for elegance, these stilettos elevate any outfit from day to night.'],
                    'fr' => ['title' => 'Talons Aiguilles Noirs', 'slug' => 'talons-aiguilles-noirs', 'short_description' => 'Talons aiguilles noirs intemporels.', 'description' => 'Conçus pour l\'élégance.'],
                    'ar' => ['title' => 'كعب عالي أسود كلاسيكي', 'slug' => 'classic-black-stilettos-ar', 'short_description' => 'كعب عالي أسود كلاسيكي.', 'description' => 'مصمم للأناقة.'],
                ],
                'variants' => [
                    ['size' => '36', 'color' => 'Black', 'sku' => 'SCB-H-BLK-36', 'stock_quantity' => 5],
                    ['size' => '37', 'color' => 'Black', 'sku' => 'SCB-H-BLK-37', 'stock_quantity' => 5],
                    ['size' => '38', 'color' => 'Black', 'sku' => 'SCB-H-BLK-38', 'stock_quantity' => 5],
                ],
            ],
            [
                'category' => 'boots',
                'audience_type' => 'women',
                'title' => 'Leather Ankle Boots',
                'slug' => 'leather-ankle-boots',
                'sku' => 'SCB-B-LTH-01',
                'price' => 150,
                'sale_price' => 135,
                'stock_quantity' => 10,
                'is_featured' => true,
                'is_new' => true,
                'is_best_seller' => false,
                'image' => 'leather-boots',
                'tags' => ['boots', 'leather', 'ankle'],
                'translations' => [
                    'en' => ['title' => 'Leather Ankle Boots', 'slug' => 'leather-ankle-boots', 'short_description' => 'Premium leather ankle boots with a comfortable block heel.', 'description' => 'Versatile boots that pair perfectly with jeans or dresses.'],
                    'fr' => ['title' => 'Bottines en Cuir', 'slug' => 'bottines-en-cuir', 'short_description' => 'Bottines en cuir premium.', 'description' => 'Bottes polyvalentes.'],
                    'ar' => ['title' => 'جزمة كاحل جلدية', 'slug' => 'leather-ankle-boots-ar', 'short_description' => 'جزمة جلدية مريحة.', 'description' => 'جزمة متعددة الاستخدامات.'],
                ],
                'variants' => [
                    ['size' => '37', 'color' => 'Brown', 'sku' => 'SCB-B-BRN-37', 'stock_quantity' => 5],
                    ['size' => '39', 'color' => 'Brown', 'sku' => 'SCB-B-BRN-39', 'stock_quantity' => 5],
                ],
            ],
            [
                'category' => 'flats',
                'audience_type' => 'women',
                'title' => 'Nude Ballet Flats',
                'slug' => 'nude-ballet-flats',
                'sku' => 'SCB-F-NUD-01',
                'price' => 85,
                'sale_price' => null,
                'stock_quantity' => 20,
                'is_featured' => false,
                'is_new' => true,
                'is_best_seller' => false,
                'image' => 'nude-flats',
                'tags' => ['flats', 'nude', 'ballet'],
                'translations' => [
                    'en' => ['title' => 'Nude Ballet Flats', 'slug' => 'nude-ballet-flats', 'short_description' => 'Soft nude ballet flats for all-day comfort.', 'description' => 'Minimal and easy to wear everywhere.'],
                    'fr' => ['title' => 'Ballerines Nude', 'slug' => 'ballerines-nude', 'short_description' => 'Ballerines douces et confortables.', 'description' => 'Minimaliste et facile à porter.'],
                    'ar' => ['title' => 'باليرينا بيج', 'slug' => 'nude-ballet-flats-ar', 'short_description' => 'باليرينا ناعمة ومريحة.', 'description' => 'بسيطة وسهلة الارتداء.'],
                ],
                'variants' => [
                    ['size' => '38', 'color' => 'Nude', 'sku' => 'SCB-F-NUD-38', 'stock_quantity' => 10],
                    ['size' => '40', 'color' => 'Nude', 'sku' => 'SCB-F-NUD-40', 'stock_quantity' => 10],
                ],
            ],
        ];

        foreach ($products as $index => $data) {
            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $categories[$data['category']]->id,
                    'audience_type' => $data['audience_type'],
                    'title' => $data['title'],
                    'sku' => $data['sku'],
                    'price' => $data['price'],
                    'sale_price' => $data['sale_price'],
                    'currency' => 'USD',
                    'stock_quantity' => $data['stock_quantity'],
                    'stock_status' => $data['stock_status'] ?? 'in_stock',
                    'is_featured' => $data['is_featured'],
                    'is_active' => true,
                    'is_new' => $data['is_new'],
                    'is_best_seller' => $data['is_best_seller'],
                    'sort_order' => $index + 1,
                    'tags' => $data['tags'],
                    'short_description' => $data['translations']['en']['short_description'],
                    'description' => $data['translations']['en']['description'],
                    'meta_title' => $data['title'].' | Scarbina',
                    'meta_description' => $data['translations']['en']['short_description'],
                    'og_image_id' => $media[$data['image']]->id,
                ]
            );

            foreach ($data['translations'] as $locale => $translation) {
                $product->translations()->updateOrCreate(['locale' => $locale], [
                    ...$translation,
                    'meta_title' => $translation['title'].' | Scarbina',
                    'meta_description' => $translation['short_description'],
                ]);
            }

            $product->images()->updateOrCreate(
                ['media_id' => $media[$data['image']]->id],
                ['alt_text' => $media[$data['image']]->alt_text, 'sort_order' => 1, 'is_main' => true, 'is_active' => true]
            );

            foreach ($data['variants'] as $variantIndex => $variant) {
                $product->variants()->updateOrCreate(['sku' => $variant['sku']], [
                    ...$variant,
                    'is_active' => true,
                    'sort_order' => $variantIndex + 1,
                ]);
            }
        }
    }

    private function seedHomepage($media): void
    {
        $sections = [
            [
                'section_key' => 'hero',
                'title' => 'SCARBINA',
                'subtitle' => 'Feminine elegance in every step.',
                'content' => 'Browse our exclusive collection of women\'s shoes. Add your favorites to cart and confirm via WhatsApp.',
                'button_text' => 'Shop Collection',
                'button_url' => '/shop',
                'image_id' => $media['hero']->id,
                'sort_order' => 1,
            ],
            [
                'section_key' => 'collections_intro',
                'title' => 'Find Your Perfect Pair.',
                'subtitle' => 'Heels, flats, boots, and more.',
                'content' => 'Explore the Scarbina collection designed exclusively for women.',
                'button_text' => 'Shop All',
                'button_url' => '/shop',
                'image_id' => null,
                'sort_order' => 2,
            ],
            [
                'section_key' => 'collection_women',
                'title' => 'Heels',
                'subtitle' => null,
                'content' => 'Elegant heels for special moments.',
                'button_text' => 'Shop Heels',
                'button_url' => '/shop?category=heels',
                'image_id' => $media['heels']->id,
                'sort_order' => 3,
            ],
            [
                'section_key' => 'collection_men',
                'title' => 'Flats',
                'subtitle' => null,
                'content' => 'Comfortable and stylish flats.',
                'button_text' => 'Shop Flats',
                'button_url' => '/shop?category=flats',
                'image_id' => $media['flats']->id,
                'sort_order' => 4,
            ],
            [
                'section_key' => 'collection_kids',
                'title' => 'Boots',
                'subtitle' => null,
                'content' => 'Versatile boots for all seasons.',
                'button_text' => 'Shop Boots',
                'button_url' => '/shop?category=boots',
                'image_id' => $media['boots']->id,
                'sort_order' => 5,
            ],
            [
                'section_key' => 'editorial',
                'title' => 'Luxury for your feet',
                'subtitle' => 'Crafted for elegance and comfort.',
                'content' => 'Scarbina focuses on high-quality feminine footwear and a smooth WhatsApp ordering experience.',
                'button_text' => 'Discover More',
                'button_url' => '/shop',
                'image_id' => $media['rack']->id,
                'sort_order' => 7,
            ],
            [
                'section_key' => 'new_arrivals',
                'title' => 'Just Arrived',
                'subtitle' => 'New Arrivals',
                'content' => null,
                'button_text' => null,
                'button_url' => '/shop?sort=newest',
                'image_id' => null,
                'sort_order' => 8,
            ],
            [
                'section_key' => 'promo_banner',
                'title' => 'Order effortlessly via WhatsApp.',
                'subtitle' => 'Order Flow',
                'content' => 'Simply build your cart and send us the request directly.',
                'button_text' => 'Start Shopping',
                'button_url' => '/shop',
                'image_id' => null,
                'sort_order' => 9,
            ],
            [
                'section_key' => 'instagram_social',
                'title' => 'Follow our latest drops on Instagram.',
                'subtitle' => 'Instagram',
                'content' => 'Stay updated with Scarbina\'s newest arrivals.',
                'button_text' => '@scarbina_shoes',
                'button_url' => 'https://instagram.com/scarbina_shoes',
                'image_id' => null,
                'sort_order' => 10,
            ],
            [
                'section_key' => 'footer',
                'title' => 'SCARBINA',
                'subtitle' => 'Shop',
                'content' => 'Exclusive feminine footwear. Build your cart and confirm through WhatsApp.',
                'button_text' => null,
                'button_url' => null,
                'image_id' => null,
                'sort_order' => 11,
            ],
        ];

        foreach ($sections as $section) {
            $model = HomepageSection::updateOrCreate(['section_key' => $section['section_key']], [
                ...$section,
                'is_active' => true,
            ]);

            foreach (['en', 'fr', 'ar'] as $locale) {
                $model->translations()->updateOrCreate(['locale' => $locale], [
                    'title' => $section['title'],
                    'subtitle' => $section['subtitle'],
                    'content' => $section['content'],
                    'button_text' => $section['button_text'],
                ]);
            }
        }
    }

    private function seedPages(): void
    {
        $pages = [
            ['page_key' => 'our_story', 'slug' => 'our-story', 'title' => 'Our Story', 'is_active' => false, 'show_in_nav' => false, 'noindex' => true],
            ['page_key' => 'privacy', 'slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'is_active' => false, 'show_in_nav' => false, 'noindex' => true],
            ['page_key' => 'terms', 'slug' => 'terms-and-conditions', 'title' => 'Terms and Conditions', 'is_active' => false, 'show_in_nav' => false, 'noindex' => true],
            ['page_key' => 'contact', 'slug' => 'contact', 'title' => 'Contact', 'is_active' => false, 'show_in_nav' => false, 'noindex' => true],
        ];

        foreach ($pages as $index => $page) {
            $model = Page::updateOrCreate(['page_key' => $page['page_key']], [
                ...$page,
                'content' => 'This page is prepared in the admin panel and will be activated before launch.',
                'sort_order' => $index + 1,
                'meta_title' => $page['title'].' | Scarbina',
                'meta_description' => 'Scarbina '.$page['title'],
            ]);

            foreach (['en', 'fr', 'ar'] as $locale) {
                $model->translations()->updateOrCreate(['locale' => $locale], [
                    'title' => $page['title'],
                    'slug' => $page['slug'],
                    'content' => $model->content,
                    'meta_title' => $model->meta_title,
                    'meta_description' => $model->meta_description,
                ]);
            }
        }
    }
}
