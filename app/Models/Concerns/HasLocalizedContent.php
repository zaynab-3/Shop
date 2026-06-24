<?php

namespace App\Models\Concerns;

trait HasLocalizedContent
{
    public function translationFor(?string $locale): mixed
    {
        $locale = $locale ?: config('app.locale', 'en');
        $translations = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();

        return $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', 'en')
            ?? $translations->first();
    }

    public function localized(string $field, ?string $locale, ?string $fallbackField = null): ?string
    {
        $translation = $this->translationFor($locale);
        $value = $translation?->{$field};

        if (filled($value)) {
            return $value;
        }

        return $this->{$fallbackField ?: $field} ?? null;
    }
}
