<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::query()
                ->withCount('products')
                ->latest()
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'meta_title' => $category->meta_title,
                    'meta_description' => $category->meta_description,
                    'products_count' => $category->products_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'mode' => 'create',
            'category' => null,
        ]);
    }

    public function edit(Category $category): Response
    {
        $category->load('translations');

        return Inertia::render('Admin/Categories/Form', [
            'mode' => 'edit',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
                'meta_title' => $category->meta_title,
                'meta_description' => $category->meta_description,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = Category::create($this->payload($this->validated($request)));
        $this->syncTranslations($category);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->payload($this->validated($request, $category)));
        $this->syncTranslations($category);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        $exists = Category::query()
            ->where('slug', $data['slug'])
            ->when($category, fn ($query) => $query->whereKeyNot($category->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['slug' => 'This category slug is already used.']);
        }

        return $data;
    }

    private function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ];
    }

    private function syncTranslations(Category $category): void
    {
        foreach (['en', 'fr', 'ar'] as $locale) {
            $category->translations()->updateOrCreate(['locale' => $locale], [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'meta_title' => $category->meta_title,
                'meta_description' => $category->meta_description,
            ]);
        }
    }
}
