<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasLocalizedContent;

    protected $fillable = [
        'parent_id',
        'image_id',
        'slug',
        'name',
        'description',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function toStorefrontArray(string $locale): array
    {
        $slug = $this->localized('slug', $locale);

        return [
            'id' => $this->id,
            'name' => $this->localized('name', $locale),
            'slug' => $slug,
            'description' => $this->localized('description', $locale),
            'image' => $this->image?->url,
            'href' => storefront_url($locale, 'shop', ['category' => $slug]),
        ];
    }
}
