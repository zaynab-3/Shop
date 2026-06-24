<?php

use Illuminate\Support\Arr;

if (! function_exists('storefront_locale')) {
    function storefront_locale(?string $locale = null): string
    {
        return in_array($locale, ['en', 'fr', 'ar'], true) ? $locale : 'en';
    }
}

if (! function_exists('storefront_url')) {
    function storefront_url(?string $locale, string $path = '', array $query = []): string
    {
        $locale = storefront_locale($locale);
        $prefix = $locale === 'en' ? '' : '/'.$locale;
        $path = trim($path, '/');
        $url = $prefix.($path ? '/'.$path : '/');

        if ($query !== []) {
            $query = Arr::where($query, fn ($value) => filled($value));
            $url .= $query ? '?'.http_build_query($query) : '';
        }

        return $url === '' ? '/' : $url;
    }
}
