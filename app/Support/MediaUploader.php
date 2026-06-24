<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MediaUploader
{
    public static function store(UploadedFile $file, string $directory, string $namePrefix, ?string $altText = null): MediaAsset
    {
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBase = Str::slug($baseName) ?: Str::slug($namePrefix) ?: 'image';
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = $safeBase.'-'.Str::random(10).'.'.$extension;
        $path = $file->storeAs(trim($directory, '/'), $filename, 'public');

        return MediaAsset::create([
            'name' => Str::headline($baseName ?: $namePrefix),
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt_text' => $altText ?: Str::headline($namePrefix),
            'is_active' => true,
        ]);
    }
}
