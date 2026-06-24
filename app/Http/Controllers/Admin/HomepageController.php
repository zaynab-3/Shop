<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Models\MediaAsset;
use App\Support\MediaUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class HomepageController extends Controller
{
    public function index(): Response
    {
        $this->ensureCoreSections();

        return Inertia::render('Admin/Homepage/Index', [
            'sections' => HomepageSection::query()
                ->with('image')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (HomepageSection $section) => $this->sectionResource($section)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Homepage/Form', [
            'mode' => 'create',
            'section' => null,
        ]);
    }

    public function edit(HomepageSection $section): Response
    {
        $section->load(['translations', 'image']);

        return Inertia::render('Admin/Homepage/Form', [
            'mode' => 'edit',
            'section' => $this->sectionResource($section),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $section = HomepageSection::create($this->payload($data, $request));
        $this->syncTranslations($section);

        return redirect()->route('admin.homepage.index')->with('success', 'Home section created.');
    }

    public function update(Request $request, HomepageSection $section): RedirectResponse
    {
        $data = $this->validated($request, $section);
        $section->update($this->payload($data, $request, $section));
        $this->syncTranslations($section);

        return redirect()->route('admin.homepage.index')->with('success', 'Home section updated.');
    }

    public function destroy(HomepageSection $section): RedirectResponse
    {
        $section->delete();

        return back()->with('success', 'Home section deleted.');
    }

    private function validated(Request $request, ?HomepageSection $section = null): array
    {
        $data = $request->validate([
            'section_key' => ['required', 'string', 'max:120', Rule::unique('homepage_sections', 'section_key')->ignore($section?->id)],
            'title' => ['nullable', 'string', 'max:220'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:120'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'string', 'max:2000'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $data['section_key'] = Str::slug($data['section_key'], '_');

        $exists = HomepageSection::query()
            ->where('section_key', $data['section_key'])
            ->when($section, fn ($query) => $query->whereKeyNot($section->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['section_key' => 'This section key is already used.']);
        }

        return $data;
    }

    private function payload(array $data, Request $request, ?HomepageSection $section = null): array
    {
        return [
            'section_key' => $data['section_key'],
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'content' => $data['content'] ?? null,
            'button_text' => $data['button_text'] ?? null,
            'button_url' => $data['button_url'] ?? null,
            'image_id' => $this->imageId($data, $request, $section),
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ];
    }

    private function imageId(array $data, Request $request, ?HomepageSection $section = null): ?int
    {
        if ($request->boolean('remove_image')) {
            return null;
        }

        if ($request->hasFile('image_file')) {
            return MediaUploader::store(
                $request->file('image_file'),
                'homepage/'.$data['section_key'],
                $data['section_key'].' home section image',
                $data['image_alt_text'] ?: ($data['title'] ?? Str::headline($data['section_key']))
            )->id;
        }

        $url = trim($data['image_url'] ?? '');

        if ($url === '') {
            return $section?->image_id;
        }

        $asset = MediaAsset::updateOrCreate(
            ['remote_url' => $url],
            [
                'name' => Str::headline($data['section_key']).' image',
                'alt_text' => $data['image_alt_text'] ?: $data['title'] ?: Str::headline($data['section_key']),
                'is_active' => true,
            ]
        );

        return $asset->id;
    }

    private function sectionResource(HomepageSection $section): array
    {
        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'content' => $section->content,
            'button_text' => $section->button_text,
            'button_url' => $section->button_url,
            'image_url' => $section->image?->url,
            'image_alt_text' => $section->image?->alt_text,
            'image_is_uploaded' => (bool) ($section->image?->disk && $section->image?->path),
            'is_active' => $section->is_active,
            'sort_order' => $section->sort_order,
        ];
    }

    private function syncTranslations(HomepageSection $section): void
    {
        foreach (['en', 'fr', 'ar'] as $locale) {
            $section->translations()->updateOrCreate(['locale' => $locale], [
                'title' => $section->title,
                'subtitle' => $section->subtitle,
                'content' => $section->content,
                'button_text' => $section->button_text,
            ]);
        }
    }

    private function ensureCoreSections(): void
    {
        $sections = [
            [
                'section_key' => 'hero',
                'title' => 'SCARBINA',
                'subtitle' => 'Feminine elegance in every step.',
                'content' => 'Browse our exclusive collection of women\'s shoes. Add your favorites to cart and confirm via WhatsApp.',
                'button_text' => 'Shop Collection',
                'button_url' => '/shop',
                'image_url' => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=1800&q=82',
                'image_alt_text' => 'Elegant feminine shoes on display',
                'sort_order' => 1,
            ],
            [
                'section_key' => 'collections_intro',
                'title' => 'Find Your Perfect Pair.',
                'subtitle' => 'Heels, flats, boots, and more.',
                'content' => 'Explore the Scarbina collection designed exclusively for women.',
                'button_text' => 'Shop All',
                'button_url' => '/shop',
                'sort_order' => 2,
            ],
            [
                'section_key' => 'collection_women',
                'title' => 'Heels',
                'content' => 'Elegant heels for special moments.',
                'button_text' => 'Shop Heels',
                'button_url' => '/shop?category=heels',
                'image_url' => 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=1200&q=80',
                'image_alt_text' => 'High heels portrait',
                'sort_order' => 3,
            ],
            [
                'section_key' => 'collection_men',
                'title' => 'Flats',
                'content' => 'Comfortable and stylish flats.',
                'button_text' => 'Shop Flats',
                'button_url' => '/shop?category=flats',
                'image_url' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=1200&q=80',
                'image_alt_text' => 'Comfortable flat shoes',
                'sort_order' => 4,
            ],
            [
                'section_key' => 'collection_kids',
                'title' => 'Boots',
                'content' => 'Versatile boots for all seasons.',
                'button_text' => 'Shop Boots',
                'button_url' => '/shop?category=boots',
                'image_url' => 'https://images.unsplash.com/photo-1520639888713-7851133b1ed0?auto=format&fit=crop&w=1200&q=80',
                'image_alt_text' => 'Stylish boots for women',
                'sort_order' => 5,
            ],
            [
                'section_key' => 'editorial',
                'title' => 'Luxury for your feet',
                'subtitle' => 'Editorial',
                'content' => 'Scarbina focuses on high-quality feminine footwear and a smooth WhatsApp ordering experience.',
                'button_text' => 'Discover More',
                'button_url' => '/shop',
                'image_url' => 'https://images.unsplash.com/photo-1595341888016-a392ef81b7de?auto=format&fit=crop&w=1400&q=80',
                'image_alt_text' => 'Shoe display rack',
                'sort_order' => 7,
            ],
            [
                'section_key' => 'new_arrivals',
                'title' => 'Just Arrived',
                'subtitle' => 'New Arrivals',
                'button_url' => '/shop?sort=newest',
                'sort_order' => 8,
            ],
            [
                'section_key' => 'promo_banner',
                'title' => 'Order effortlessly via WhatsApp.',
                'subtitle' => 'Order Flow',
                'content' => 'Simply build your cart and send us the request directly.',
                'button_text' => 'Start Shopping',
                'button_url' => '/shop',
                'sort_order' => 9,
            ],
            [
                'section_key' => 'instagram_social',
                'title' => 'Follow our latest drops on Instagram.',
                'subtitle' => 'Instagram',
                'content' => 'Stay updated with Scarbina\'s newest arrivals.',
                'button_text' => '@scarbina_shoes',
                'button_url' => 'https://instagram.com/scarbina_shoes',
                'sort_order' => 10,
            ],
            [
                'section_key' => 'footer',
                'title' => 'SCARBINA',
                'subtitle' => 'Shop',
                'content' => 'Exclusive feminine footwear. Build your cart and confirm through WhatsApp.',
                'sort_order' => 11,
            ],
        ];

        foreach ($sections as $section) {
            if (HomepageSection::where('section_key', $section['section_key'])->exists()) {
                continue;
            }

            $imageId = null;

            if (! empty($section['image_url'])) {
                $imageId = MediaAsset::updateOrCreate(
                    ['remote_url' => $section['image_url']],
                    [
                        'name' => Str::headline($section['section_key']).' image',
                        'alt_text' => $section['image_alt_text'] ?? $section['title'],
                        'is_active' => true,
                    ]
                )->id;
            }

            $model = HomepageSection::create([
                'section_key' => $section['section_key'],
                'title' => $section['title'] ?? null,
                'subtitle' => $section['subtitle'] ?? null,
                'content' => $section['content'] ?? null,
                'button_text' => $section['button_text'] ?? null,
                'button_url' => $section['button_url'] ?? null,
                'image_id' => $imageId,
                'is_active' => true,
                'sort_order' => $section['sort_order'],
            ]);

            $this->syncTranslations($model);
        }
    }
}
