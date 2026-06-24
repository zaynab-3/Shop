<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasLocalizedContent;
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'audience_type',
        'title',
        'slug',
        'sku',
        'price',
        'sale_price',
        'currency',
        'description',
        'short_description',
        'stock_quantity',
        'stock_status',
        'is_featured',
        'is_active',
        'is_new',
        'is_best_seller',
        'sort_order',
        'meta_title',
        'meta_description',
        'og_image_id',
        'tags',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_new' => 'boolean',
        'is_best_seller' => 'boolean',
        'tags' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_active', true)->orderByDesc('is_main')->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_image_id');
    }

    public function currentPrice(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public function mainImage(): ?MediaAsset
    {
        return $this->images->firstWhere('is_main', true)?->media
            ?? $this->images->first()?->media;
    }

    public function toCardArray(string $locale): array
    {
        $slug = $this->localized('slug', $locale);
        $image = $this->mainImage();

        return [
            'id' => $this->id,
            'name' => $this->localized('title', $locale),
            'slug' => $slug,
            'href' => storefront_url($locale, 'products/'.$slug),
            'short_description' => $this->localized('short_description', $locale),
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            'current_price' => $this->currentPrice(),
            'currency' => $this->currency,
            'image' => $image?->url,
            'alt' => $image?->alt_text ?? $this->title,
            'category' => $this->category?->localized('name', $locale),
            'audience_type' => $this->audience_type,
            'stock_status' => $this->stock_status,
            'stock_quantity' => $this->stock_quantity,
            'is_new' => $this->is_new,
            'is_featured' => $this->is_featured,
            'variants' => $this->variants
                ->where('is_active', true)
                ->values()
                ->map(fn (ProductVariant $variant) => $variant->toStorefrontArray($this))
                ->all(),
        ];
    }
}
