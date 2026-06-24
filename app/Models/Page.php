<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasLocalizedContent;

    protected $fillable = [
        'page_key',
        'slug',
        'title',
        'content',
        'is_active',
        'show_in_nav',
        'noindex',
        'sort_order',
        'meta_title',
        'meta_description',
        'og_image_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_nav' => 'boolean',
        'noindex' => 'boolean',
    ];

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_image_id');
    }
}
