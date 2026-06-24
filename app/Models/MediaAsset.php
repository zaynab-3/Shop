<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $fillable = [
        'name',
        'disk',
        'path',
        'remote_url',
        'webp_path',
        'mime_type',
        'size',
        'alt_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getUrlAttribute(): ?string
    {
        if ($this->remote_url) {
            return $this->remote_url;
        }

        if ($this->webp_path && $this->disk === 'public') {
            return '/storage/'.ltrim($this->webp_path, '/');
        }

        if ($this->path && $this->disk === 'public') {
            return '/storage/'.ltrim($this->path, '/');
        }

        if ($this->webp_path && $this->disk) {
            return Storage::disk($this->disk)->url($this->webp_path);
        }

        return $this->path && $this->disk ? Storage::disk($this->disk)->url($this->path) : null;
    }
}
