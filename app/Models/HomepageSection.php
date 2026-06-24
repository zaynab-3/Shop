<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomepageSection extends Model
{
    use HasLocalizedContent;

    protected $fillable = [
        'section_key',
        'title',
        'subtitle',
        'content',
        'button_text',
        'button_url',
        'image_id',
        'video_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(HomepageSectionTranslation::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function toStorefrontArray(string $locale): array
    {
        return [
            'key' => $this->section_key,
            'title' => $this->localized('title', $locale),
            'subtitle' => $this->localized('subtitle', $locale),
            'content' => $this->localized('content', $locale),
            'button_text' => $this->localized('button_text', $locale),
            'button_url' => $this->button_url,
            'image' => $this->image?->url,
            'image_alt' => $this->image?->alt_text,
            'image_is_uploaded' => (bool) ($this->image?->disk && $this->image?->path),
        ];
    }
}
