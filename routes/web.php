<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/shop', [StorefrontController::class, 'shop'])->middleware('throttle:search')->name('shop');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout');
Route::get('/our-story', [StorefrontController::class, 'ourStory'])->name('our-story');
Route::get('/products/{slug}', [StorefrontController::class, 'product'])->name('products.show');
Route::get('/pages/{slug}', [StorefrontController::class, 'page'])->name('pages.show');

Route::prefix('{locale}')
    ->where(['locale' => 'en|fr|ar'])
    ->group(function () {
        Route::get('/', [StorefrontController::class, 'home'])->name('localized.home');
        Route::get('/shop', [StorefrontController::class, 'shop'])->middleware('throttle:search')->name('localized.shop');
        Route::get('/cart', [StorefrontController::class, 'cart'])->name('localized.cart');
        Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('localized.checkout');
        Route::get('/our-story', [StorefrontController::class, 'ourStory'])->name('localized.our-story');
        Route::get('/products/{slug}', [StorefrontController::class, 'product'])->name('localized.products.show');
        Route::get('/pages/{slug}', [StorefrontController::class, 'page'])->name('localized.pages.show');
    });

Route::post('/orders/whatsapp', [OrderController::class, 'store'])
    ->middleware('throttle:orders')
    ->name('orders.whatsapp');

// Auth controllers redirect to route('dashboard'); point it at the admin panel.
Route::redirect('/dashboard', '/admin', 301)->name('dashboard')->middleware(['auth', 'verified', 'admin']);

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/homepage', [App\Http\Controllers\Admin\HomepageController::class, 'index'])->name('homepage.index');
    Route::get('/homepage/create', [App\Http\Controllers\Admin\HomepageController::class, 'create'])->name('homepage.create');
    Route::post('/homepage', [App\Http\Controllers\Admin\HomepageController::class, 'store'])->name('homepage.store');
    Route::get('/homepage/{section}/edit', [App\Http\Controllers\Admin\HomepageController::class, 'edit'])->name('homepage.edit');
    Route::patch('/homepage/{section}', [App\Http\Controllers\Admin\HomepageController::class, 'update'])->name('homepage.update');
    Route::delete('/homepage/{section}', [App\Http\Controllers\Admin\HomepageController::class, 'destroy'])->name('homepage.destroy');
    Route::get('/products', [App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [App\Http\Controllers\Admin\ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [App\Http\Controllers\Admin\ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('products.edit');
    Route::patch('/products/{product}', [App\Http\Controllers\Admin\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/categories', [App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('categories.edit');
    Route::patch('/categories/{category}', [App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/orders', [App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}', [App\Http\Controllers\Admin\OrderController::class, 'update'])->name('orders.update');
    Route::get('/pages', [App\Http\Controllers\Admin\PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/create', [App\Http\Controllers\Admin\PageController::class, 'create'])->name('pages.create');
    Route::post('/pages', [App\Http\Controllers\Admin\PageController::class, 'store'])->name('pages.store');
    Route::get('/pages/{page}/edit', [App\Http\Controllers\Admin\PageController::class, 'edit'])->name('pages.edit');
    Route::patch('/pages/{page}', [App\Http\Controllers\Admin\PageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [App\Http\Controllers\Admin\PageController::class, 'destroy'])->name('pages.destroy');
    Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/language', [App\Http\Controllers\Admin\SettingController::class, 'toggleLanguage'])->name('settings.language.toggle');
    Route::post('/settings/whatsapp', [App\Http\Controllers\Admin\SettingController::class, 'updateWhatsapp'])->name('settings.whatsapp.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
