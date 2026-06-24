<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function home(Request $request): Response
    {
        $locale = storefront_locale($request->route('locale'));
        $categories = $this->categories($locale);
        $sections = HomepageSection::query()
            ->active()
            ->with(['translations', 'image'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (HomepageSection $section) => $section->toStorefrontArray($locale));

        return Inertia::render('Storefront/Home', [
            ...$this->shared($locale),
            'hero' => $sections->firstWhere('key', 'hero'),
            'sections' => $sections->values(),
            'categories' => $categories,
            'newArrivals' => $this->productCards($locale, fn (Builder $query) => $query->where('is_new', true), 4),
            'seo' => [
                'title' => 'Scarbina | Exclusive Feminine Footwear',
                'description' => 'Shop Scarbina collections for elegant and comfortable shoes. Build your cart and send your order by WhatsApp.',
                'image' => $sections->firstWhere('key', 'hero')['image'] ?? null,
                'noindex' => false,
            ],
        ]);
    }

    public function shop(Request $request): Response
    {
        $locale = storefront_locale($request->route('locale'));
        $products = Product::query()
            ->active()
            ->with(['translations', 'category.translations', 'images.media', 'variants.image'])
            ->when($request->string('category')->toString(), function (Builder $query, string $slug) use ($locale) {
                $query->whereHas('category', function (Builder $categoryQuery) use ($slug, $locale) {
                    $categoryQuery->where('slug', $slug)
                        ->orWhereHas('translations', fn (Builder $translationQuery) => $translationQuery
                            ->where('locale', $locale)
                            ->where('slug', $slug));
                });
            })
            ->when($request->string('audience')->toString(), fn (Builder $query, string $audience) => $query->where('audience_type', $audience))
            ->when($request->string('stock')->toString() === 'in_stock', fn (Builder $query) => $query->where('stock_status', 'in_stock')->where('stock_quantity', '>', 0))
            ->when($request->input('max_price'), fn (Builder $query, $max) => $query->whereRaw('coalesce(sale_price, price) <= ?', [(float)$max]))
            ->when($request->string('q')->toString(), function (Builder $query, string $term) use ($locale) {
                $query->where(function (Builder $search) use ($term, $locale) {
                    $search->where('title', 'like', '%'.$term.'%')
                        ->orWhere('sku', 'like', '%'.$term.'%')
                        ->orWhereHas('translations', fn (Builder $translationQuery) => $translationQuery
                            ->where('locale', $locale)
                            ->where('title', 'like', '%'.$term.'%'));
                });
            });

        match ($request->string('sort')->toString()) {
            'price_asc' => $products->orderByRaw('coalesce(sale_price, price) asc'),
            'price_desc' => $products->orderByRaw('coalesce(sale_price, price) desc'),
            'featured' => $products->orderByDesc('is_featured')->orderBy('sort_order'),
            default => $products->latest(),
        };

        return Inertia::render('Storefront/Shop', [
            ...$this->shared($locale),
            'categories' => $this->categories($locale),
            'products' => $products->paginate(12)->withQueryString()->through(fn (Product $product) => $product->toCardArray($locale)),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'category' => $request->string('category')->toString(),
                'audience' => $request->string('audience')->toString(),
                'sort' => in_array($request->string('sort')->toString(), ['featured', 'price_asc', 'price_desc'], true)
                    ? $request->string('sort')->toString()
                    : 'newest',
                'stock' => $request->string('stock')->toString(),
                'max_price' => $request->input('max_price'),
            ],
            'seo' => [
                'title' => 'Shop Scarbina',
                'description' => 'Browse Scarbina shoes by Heels, Flats, Boots, price, stock status, and collection.',
                'noindex' => false,
            ],
        ]);
    }

    public function product(Request $request): Response
    {
        $locale = storefront_locale($request->route('locale'));
        $slug = $request->route('slug');
        $product = Product::query()
            ->active()
            ->with(['translations', 'category.translations', 'images.media', 'variants.image', 'ogImage'])
            ->where(function (Builder $query) use ($slug, $locale) {
                $query->where('slug', $slug)
                    ->orWhereHas('translations', fn (Builder $translationQuery) => $translationQuery
                        ->where('locale', $locale)
                        ->where('slug', $slug));
            })
            ->firstOrFail();

        $card = $product->toCardArray($locale);
        $related = $this->productCards(
            $locale,
            fn (Builder $query) => $query->where('category_id', $product->category_id)->whereKeyNot($product->id),
            4
        );

        return Inertia::render('Storefront/Product', [
            ...$this->shared($locale),
            'product' => [
                ...$card,
                'description' => $product->localized('description', $locale),
                'sku' => $product->sku,
                'tags' => $product->tags ?? [],
                'images' => $product->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => $image->media?->url,
                    'alt' => $image->alt_text ?: $image->media?->alt_text ?: $card['name'],
                ])->values(),
            ],
            'related' => $related,
            'seo' => [
                'title' => $product->localized('meta_title', $locale) ?: $card['name'].' | Scarbina',
                'description' => $product->localized('meta_description', $locale) ?: $card['short_description'],
                'image' => $product->ogImage?->url ?? $card['image'],
                'noindex' => false,
                'structuredData' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => $card['name'],
                    'description' => $card['short_description'],
                    'image' => array_filter($product->images->map(fn ($image) => $image->media?->url)->all()),
                    'sku' => $product->sku,
                    'offers' => [
                        '@type' => 'Offer',
                        'priceCurrency' => $product->currency,
                        'price' => $card['current_price'],
                        'availability' => $product->stock_quantity > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    ],
                ],
            ],
        ]);
    }

    public function cart(Request $request): Response
    {
        return $this->checkout($request);
    }

    public function checkout(Request $request): Response
    {
        $locale = storefront_locale($request->route('locale'));

        return Inertia::render('Storefront/Checkout', [
            ...$this->shared($locale),
            'seo' => [
                'title' => 'Checkout | Scarbina',
                'description' => 'Review your Scarbina cart, choose cash on delivery, and send your order through WhatsApp.',
                'noindex' => true,
            ],
        ]);
    }

    public function ourStory(Request $request): Response
    {
        $locale = storefront_locale($request->route('locale'));

        return Inertia::render('Storefront/OurStory', [
            ...$this->shared($locale),
            'seo' => [
                'title' => 'Our Story | Soleil Shoes',
                'description' => 'Born in the sun-drenched ateliers of Italy, Soleil Shoes is a testament to the enduring power of meticulous craftsmanship.',
                'noindex' => false,
            ],
        ]);
    }

    public function page(Request $request): Response
    {
        $locale = storefront_locale($request->route('locale'));
        $slug = $request->route('slug');

        if ($slug === 'privacy-policy') {
            return Inertia::render('Storefront/PrivacyPolicy', [
                ...$this->shared($locale),
                'seo' => [
                    'title' => 'Privacy Policy | Scarbina',
                    'description' => 'At Scarbina, we curate experiences as carefully as we craft our footwear. This Privacy Policy details our commitment to safeguarding the personal information you entrust to us.',
                    'noindex' => false,
                ],
            ]);
        }

        $page = Page::query()
            ->with(['translations', 'ogImage'])
            ->where(function (Builder $query) use ($slug, $locale) {
                $query->where('slug', $slug)
                    ->orWhereHas('translations', fn (Builder $translationQuery) => $translationQuery
                        ->where('locale', $locale)
                        ->where('slug', $slug));
            })
            ->firstOrFail();

        return Inertia::render('Storefront/Page', [
            ...$this->shared($locale),
            'page' => [
                'title' => $page->localized('title', $locale),
                'content' => $page->is_active ? $page->localized('content', $locale) : null,
                'is_active' => $page->is_active,
            ],
            'seo' => [
                'title' => $page->localized('meta_title', $locale) ?: $page->localized('title', $locale).' | Scarbina',
                'description' => $page->localized('meta_description', $locale),
                'image' => $page->ogImage?->url,
                'noindex' => $page->noindex || ! $page->is_active,
            ],
        ]);
    }

    private function shared(string $locale): array
    {
        return [
            'locale' => $locale,
            'isRtl' => $locale === 'ar',
            'locales' => ['en', 'fr', 'ar'],
            'settings' => Setting::publicMap(),
            'navPages' => Page::query()
                ->where('show_in_nav', true)
                ->where('is_active', true)
                ->with('translations')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Page $page) => [
                    'title' => $page->localized('title', $locale),
                    'href' => storefront_url($locale, 'pages/'.$page->localized('slug', $locale)),
                ]),
            'footerPages' => Page::query()
                ->where('is_active', true)
                ->whereIn('page_key', ['privacy', 'terms'])
                ->with('translations')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Page $page) => [
                    'title' => $page->localized('title', $locale),
                    'href' => storefront_url($locale, 'pages/'.$page->localized('slug', $locale)),
                ]),
            'footer' => HomepageSection::query()
                ->active()
                ->with(['translations', 'image'])
                ->where('section_key', 'footer')
                ->first()
                ?->toStorefrontArray($locale),
        ];
    }

    private function categories(string $locale)
    {
        return Category::query()
            ->active()
            ->with(['translations', 'image'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Category $category) => $category->toStorefrontArray($locale));
    }

    private function productCards(string $locale, callable $callback, int $limit)
    {
        $query = Product::query()
            ->active()
            ->with(['translations', 'category.translations', 'images.media', 'variants.image'])
            ->orderBy('sort_order');

        $callback($query);

        return $query->limit($limit)->get()->map(fn (Product $product) => $product->toCardArray($locale));
    }
}
