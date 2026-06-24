<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'size',
        'color',
        'material',
        'style',
        'sku',
        'price',
        'sale_price',
        'stock_quantity',
        'image_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function toStorefrontArray(Product $product): array
    {
        $price = (float) ($this->sale_price ?? $this->price ?? $product->sale_price ?? $product->price);

        return [
            'id' => $this->id,
            'size' => $this->size,
            'color' => $this->color,
            'material' => $this->material,
            'style' => $this->style,
            'sku' => $this->sku,
            'price' => $price,
            'stock_quantity' => $this->stock_quantity,
            'image' => $this->image?->url,
            'is_active' => $this->is_active,
        ];
    }
}
