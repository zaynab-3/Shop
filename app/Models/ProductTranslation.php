<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTranslation extends Model
{
    protected $fillable = [
        'product_id',
        'locale',
        'title',
        'slug',
        'short_description',
        'description',
        'meta_title',
        'meta_description',
    ];
}
