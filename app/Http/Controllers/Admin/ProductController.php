<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\MediaUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Products/Index', [
            'products' => Product::query()
                ->with(['category:id,name', 'translations', 'variants'])
                ->latest()
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'category_id' => $product->category_id,
                    'category_name' => $product->category?->name,
                    'audience_type' => $product->audience_type,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'price' => (float) $product->price,
                    'sale_price' => $product->sale_price ? (float) $product->sale_price : null,
                    'currency' => $product->currency,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'stock_quantity' => $product->stock_quantity,
                    'stock_status' => $product->stock_status,
                    'is_featured' => $product->is_featured,
                    'is_active' => $product->is_active,
                    'is_new' => $product->is_new,
                    'sort_order' => $product->sort_order,
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,
                    'tags_text' => collect($product->tags ?? [])->join(', '),
                    'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                        'id' => $variant->id,
                        'size' => $variant->size,
                        'color' => $variant->color,
                        'sku' => $variant->sku,
                        'price' => $variant->price ? (float) $variant->price : null,
                        'sale_price' => $variant->sale_price ? (float) $variant->sale_price : null,
                        'stock_quantity' => $variant->stock_quantity,
                        'is_active' => $variant->is_active,
                        'sort_order' => $variant->sort_order,
                    ])->values(),
                ]),
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'mode' => 'create',
            'product' => null,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function edit(Product $product): Response
    {
        $product->load(['category:id,name', 'translations', 'variants', 'images.media']);

        return Inertia::render('Admin/Products/Form', [
            'mode' => 'edit',
            'product' => $this->productResource($product),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $variants = $this->variantRows($data['variants'] ?? []);
        $this->validateVariantSkus($variants);

        $product = Product::create($this->productPayload($data, $variants));
        $this->syncTranslations($product);
        $this->syncVariants($product, $variants);
        $this->syncImages($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $variants = $this->variantRows($data['variants'] ?? []);
        $this->validateVariantSkus($variants, $product);

        $product->update($this->productPayload($data, $variants));
        $this->syncTranslations($product);
        $this->syncVariants($product, $variants);
        $this->syncImages($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'audience_type' => ['required', Rule::in(['women', 'men', 'kids'])],
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('products', 'slug')->ignore($product?->id)],
            'sku' => ['nullable', 'string', 'max:120', Rule::unique('products', 'sku')->ignore($product?->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_featured' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'is_new' => ['required', 'boolean'],
            'is_best_seller' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags_text' => ['nullable', 'string', 'max:500'],
            'product_images' => ['array'],
            'product_images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_image_ids' => ['array'],
            'remove_image_ids.*' => ['integer'],
            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.size' => ['nullable', 'string', 'max:80'],
            'variants.*.color' => ['nullable', 'string', 'max:80'],
            'variants.*.sku' => ['nullable', 'string', 'max:120'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['required', 'boolean'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['is_best_seller'] = false;

        $exists = Product::query()
            ->where('slug', $data['slug'])
            ->when($product, fn ($query) => $query->whereKeyNot($product->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['slug' => 'This product slug is already used.']);
        }

        return $data;
    }

    private function categoryOptions()
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    private function productResource(Product $product): array
    {
        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'audience_type' => $product->audience_type,
            'title' => $product->title,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => (float) $product->price,
            'sale_price' => $product->sale_price ? (float) $product->sale_price : null,
            'currency' => $product->currency,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'stock_quantity' => $product->stock_quantity,
            'stock_status' => $product->stock_status,
            'is_featured' => $product->is_featured,
            'is_active' => $product->is_active,
            'is_new' => $product->is_new,
            'sort_order' => $product->sort_order,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'tags_text' => collect($product->tags ?? [])->join(', '),
            'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'size' => $variant->size,
                'color' => $variant->color,
                'sku' => $variant->sku,
                'price' => $variant->price ? (float) $variant->price : null,
                'sale_price' => $variant->sale_price ? (float) $variant->sale_price : null,
                'stock_quantity' => $variant->stock_quantity,
                'is_active' => $variant->is_active,
                'sort_order' => $variant->sort_order,
            ])->values(),
            'images' => $product->images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'url' => $image->media?->url,
                'alt_text' => $image->alt_text ?: $image->media?->alt_text,
                'is_main' => $image->is_main,
                'sort_order' => $image->sort_order,
            ])->values(),
        ];
    }

    private function productPayload(array $data, array $variants): array
    {
        $stockQuantity = count($variants)
            ? collect($variants)->where('is_active', true)->sum('stock_quantity')
            : (int) $data['stock_quantity'];

        return [
            'category_id' => $data['category_id'] ?? null,
            'audience_type' => $data['audience_type'],
            'title' => $data['title'],
            'slug' => $data['slug'],
            'sku' => $data['sku'] ?? null,
            'price' => $data['price'],
            'sale_price' => $data['sale_price'] ?? null,
            'currency' => strtoupper($data['currency']),
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'stock_quantity' => $stockQuantity,
            'stock_status' => $stockQuantity > 0 ? 'in_stock' : 'out_of_stock',
            'is_featured' => $data['is_featured'],
            'is_active' => $data['is_active'],
            'is_new' => $data['is_new'],
            'is_best_seller' => false,
            'sort_order' => $data['sort_order'],
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'tags' => collect(explode(',', $data['tags_text'] ?? ''))
                ->map(fn (string $tag) => trim($tag))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function variantRows(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row) => [
                'id' => $row['id'] ?? null,
                'size' => $row['size'] ?? null,
                'color' => $row['color'] ?? null,
                'sku' => $row['sku'] ?? null,
                'price' => $row['price'] ?? null,
                'sale_price' => $row['sale_price'] ?? null,
                'stock_quantity' => (int) ($row['stock_quantity'] ?? 0),
                'is_active' => (bool) ($row['is_active'] ?? true),
            ])
            ->filter(fn (array $row) => $row['size'] || $row['color'] || $row['sku'] || $row['stock_quantity'] > 0)
            ->values()
            ->all();
    }

    private function validateVariantSkus(array $variants, ?Product $product = null): void
    {
        $skus = collect($variants)->pluck('sku')->filter()->values();

        if ($skus->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['variants' => 'Variant SKUs must be unique.']);
        }

        $exists = ProductVariant::query()
            ->whereIn('sku', $skus)
            ->when($product, fn ($query) => $query->where('product_id', '!=', $product->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['variants' => 'One of the variant SKUs is already used by another product.']);
        }
    }

    private function syncTranslations(Product $product): void
    {
        foreach (['en', 'fr', 'ar'] as $locale) {
            $product->translations()->updateOrCreate(['locale' => $locale], [
                'title' => $product->title,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
            ]);
        }
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $kept = [];

        foreach ($variants as $index => $variant) {
            $model = $product->variants()->updateOrCreate(
                ['id' => $variant['id']],
                [
                    ...$variant,
                    'sort_order' => $index + 1,
                ]
            );

            $kept[] = $model->id;
        }

        $product->variants()->when(count($kept), fn ($query) => $query->whereNotIn('id', $kept))->delete();
    }

    private function syncImages(Request $request, Product $product): void
    {
        $removeIds = collect($request->input('remove_image_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($removeIds->isNotEmpty()) {
            $product->images()->whereIn('id', $removeIds)->delete();
        }

        $files = $request->file('product_images', []);

        if (! is_array($files) || $files === []) {
            $this->ensureSingleMainImage($product);

            return;
        }

        $nextSort = (int) $product->images()->max('sort_order') + 1;
        $hasMain = $product->images()->where('is_main', true)->exists();

        foreach ($files as $index => $file) {
            if (! $file) {
                continue;
            }

            $media = MediaUploader::store(
                $file,
                'products/'.$product->id,
                $product->slug.' product image',
                $product->title
            );

            $product->images()->create([
                'media_id' => $media->id,
                'alt_text' => $product->title,
                'sort_order' => $nextSort + $index,
                'is_main' => ! $hasMain && $index === 0,
                'is_active' => true,
            ]);
        }

        $this->ensureSingleMainImage($product);
    }

    private function ensureSingleMainImage(Product $product): void
    {
        $images = $product->images()->orderByDesc('is_main')->orderBy('sort_order')->get();

        if ($images->isEmpty()) {
            return;
        }

        $main = $images->first();

        $product->images()->whereKeyNot($main->id)->update(['is_main' => false]);
        $main->update(['is_main' => true]);
    }
}
