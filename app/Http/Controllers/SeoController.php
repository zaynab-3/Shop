<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml')."\n", 200)
            ->header('Content-Type', 'text/plain');
    }

    public function sitemap(): Response
    {
        $urls = collect([
            url('/'),
            url('/shop'),
        ]);

        Page::query()
            ->where('is_active', true)
            ->where('noindex', false)
            ->get()
            ->each(fn (Page $page) => $urls->push(url('/'.$page->slug)));

        Product::query()
            ->where('is_active', true)
            ->with('translations')
            ->get()
            ->each(function (Product $product) use ($urls) {
                $urls->push(url('/products/'.$product->localized('slug', 'en')));
            });

        $xml = view('sitemap', ['urls' => $urls->unique()->values()])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
