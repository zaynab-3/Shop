<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Pages/Index', [
            'pages' => Page::query()
                ->latest()
                ->get()
                ->map(fn (Page $page) => [
                    'id' => $page->id,
                    'page_key' => $page->page_key,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'content' => $page->content,
                    'is_active' => $page->is_active,
                    'show_in_nav' => $page->show_in_nav,
                    'noindex' => $page->noindex,
                    'sort_order' => $page->sort_order,
                    'meta_title' => $page->meta_title,
                    'meta_description' => $page->meta_description,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Form', [
            'mode' => 'create',
            'page' => null,
        ]);
    }

    public function edit(Page $page): Response
    {
        $page->load('translations');

        return Inertia::render('Admin/Pages/Form', [
            'mode' => 'edit',
            'page' => [
                'id' => $page->id,
                'page_key' => $page->page_key,
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'is_active' => $page->is_active,
                'show_in_nav' => $page->show_in_nav,
                'noindex' => $page->noindex,
                'sort_order' => $page->sort_order,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = Page::create($this->payload($this->validated($request)));
        $this->syncTranslations($page);

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->payload($this->validated($request, $page)));
        $this->syncTranslations($page);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return back()->with('success', 'Page deleted.');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'page_key' => ['required', 'string', 'max:120', Rule::unique('pages', 'page_key')->ignore($page?->id)],
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'content' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'show_in_nav' => ['required', 'boolean'],
            'noindex' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['page_key'] = Str::slug($data['page_key'], '_');
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        $duplicate = Page::query()
            ->where(function ($query) use ($data) {
                $query->where('slug', $data['slug'])
                    ->orWhere('page_key', $data['page_key']);
            })
            ->when($page, fn ($query) => $query->whereKeyNot($page->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['slug' => 'This page key or slug is already used.']);
        }

        return $data;
    }

    private function payload(array $data): array
    {
        return [
            'page_key' => $data['page_key'],
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => $data['content'] ?? null,
            'is_active' => $data['is_active'],
            'show_in_nav' => $data['show_in_nav'],
            'noindex' => $data['noindex'],
            'sort_order' => $data['sort_order'],
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ];
    }

    private function syncTranslations(Page $page): void
    {
        foreach (['en', 'fr', 'ar'] as $locale) {
            $page->translations()->updateOrCreate(['locale' => $locale], [
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ]);
        }
    }
}
