<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSectionTranslation extends Model
{
    protected $fillable = [
        'homepage_section_id',
        'locale',
        'title',
        'subtitle',
        'content',
        'button_text',
    ];
}
