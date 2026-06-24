<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { cart, formatCurrency } from '@/Stores/cart';
import { favorites } from '@/Stores/favorites';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, ShoppingBag, SlidersHorizontal, Heart, LayoutGrid, List as ListIcon, ChevronDown, X } from 'lucide-vue-next';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const props = defineProps({
    locale: { type: String, default: 'en' },
    isRtl: { type: Boolean, default: false },
    settings: { type: Object, default: () => ({}) },
    navPages: { type: Array, default: () => [] },
    footer: { type: Object, default: null },
    footerPages: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    products: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
    seo: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q || '');
const selectedSort = ref(props.filters.sort || 'newest');
const selectedCategory = ref(props.filters.category || '');
const maxPrice = ref(props.filters.max_price ? parseInt(props.filters.max_price) : 500);
const showMobileFilters = ref(false);

const shopImages = [
    '/storefront/soleil/shop-product-3.jpg',
    '/storefront/soleil/shop-product-4.jpg',
    '/storefront/soleil/shop-product-6.jpg',
    '/storefront/soleil/shop-product-7.jpg',
    '/storefront/soleil/shop-product-1.jpg',
];

// Expanded mock data to match the visual reference
const collectionFilters = [
    { name: "Summer '26", slug: 'summer-26' },
    { name: "Bridal '26", slug: 'bridal-26' },
    { name: "Autumn Winter '26", slug: 'aw-26' },
    { name: "Bridal '25", slug: 'bridal-25' },
    { name: "Spring / Summer 25", slug: 'ss-25' },
    { name: "Autumn / Winter 24", slug: 'aw-24' },
    { name: "Bridal", slug: 'bridal' },
    { name: "Boots", slug: 'boots' },
    { name: "Collections", slug: 'collections' },
    { name: "Flats", slug: 'flats' },
    { name: "Mocassins", slug: 'mocassins' },
    { name: "Mule", slug: 'mule' },
    { name: "Platforms", slug: 'platforms' },
    { name: "Pumps", slug: 'pumps' },
];

const editorialProducts = [
    { name: 'Pin', price: 335.00, category: 'Mule', tags: ['Best Seller'] },
    { name: 'Meurisier', price: 345.00, category: 'Pumps', tags: [] },
    { name: 'Rosée', price: 295.00, category: 'Sandals', tags: ['New'] },
    { name: 'Noisetier', price: 370.00, category: 'Boots', tags: [] },
    { name: 'Scarlet Point', price: 295.00, category: 'Pumps', tags: [] },
    { name: 'Satin Nude', price: 320.00, category: 'Heels', tags: ['Sale'] },
    { name: 'Noir Stiletto', price: 360.00, category: 'Pumps', tags: [] },
    { name: 'Gilded Strap', price: 345.00, category: 'Sandals', tags: [] },
];



const productRows = computed(() => props.products.data || []);
const productCards = computed(() => {
    return (productRows.value.length ? productRows.value : editorialProducts).map((product, index) => {
        const editorial = editorialProducts[index % editorialProducts.length];

        // Generate an array of 3 images for the swipe/hover carousel
        const images = [
            shopImages[(index) % shopImages.length],
            shopImages[(index + 1) % shopImages.length],
            shopImages[(index + 2) % shopImages.length],
        ];

        return {
            ...product,
            id: product.id || `fallback-${index}`,
            name: product.name || editorial.name,
            category: product.category || editorial.category || 'Footwear',
            tags: product.tags || editorial.tags || [],
            href: product.href || pathFor('shop'),
            images: images, // Use array instead of single string
            alt: product.alt || editorial.name || 'Footwear product',
            current_price: product.current_price ?? editorial.price,
            currency: product.currency || 'USD',
            variants: product.variants || [],
            stock_quantity: product.stock_quantity ?? 8,
        };
    });
});

const totalProducts = computed(() => props.products.total || 67); // Mocked to match 67 results from screenshot

function pathFor(path = '') {
    const [basePath, query = ''] = String(path).replace(/^\/+/, '').split('?');
    const prefix = props.locale === 'en' ? '' : `/${props.locale}`;
    const url = `${prefix}/${basePath}`.replace(/\/$/, '') || '/';
    return query ? `${url}?${query}` : url;
}

function applyFilters(overrides = {}) {
    const query = {
        q: search.value || undefined,
        category: selectedCategory.value || undefined,
        sort: selectedSort.value === 'newest' ? undefined : selectedSort.value,
        max_price: maxPrice.value < 500 ? maxPrice.value : undefined,
        ...overrides,
    };

    router.get(pathFor('shop'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function selectCategory(slug = '') {
    // Toggle logic for checkboxes
    selectedCategory.value = selectedCategory.value === slug ? '' : slug;
    applyFilters({ category: selectedCategory.value });
}

const animateHeart = (el) => {
    if (!el) return;
    const target = el.querySelector('svg') || el;
    gsap.timeline()
        .to(target, { scale: 1.4, duration: 0.1, ease: 'power1.out' })
        .to(target, { scale: 1.1, duration: 0.1, ease: 'power1.inOut' })
        .to(target, { scale: 1.35, duration: 0.1, ease: 'power1.out' })
        .to(target, { scale: 1, duration: 0.18, ease: 'power2.inOut' });
};

const animateCart = (el) => {
    if (!el) return;
    const target = el.querySelector('svg') || el;
    gsap.timeline()
        .to(target, { scale: 1.4, duration: 0.12, ease: 'back.out(1.7)' })
        .to(target, { rotate: 15, duration: 0.08 })
        .to(target, { rotate: -15, duration: 0.08 })
        .to(target, { rotate: 0, scale: 1, duration: 0.15, ease: 'power2.out' });
};

function toggleFavoriteWithAnim(event, product) {
    const button = event.currentTarget;
    animateHeart(button);
    favorites.toggleItem(product);
}

function addProductWithAnim(event, product) {
    const button = event.currentTarget;
    animateCart(button);
    cart.addItem(product, product.variants?.[0] || null, 1);
}

// Mobile Scroll Tracking for Image Carousel Indicators
const activeImageIndices = ref({});
const hoveredImageIndices = ref({});

const handleScroll = (e, productId) => {
    const container = e.target;
    const scrollPosition = container.scrollLeft;
    const itemWidth = container.clientWidth;
    activeImageIndices.value[productId] = Math.round(scrollPosition / itemWidth);
};

const handleMouseMove = (e, product) => {
    const rect = e.currentTarget.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const width = rect.width;
    const numImages = product.images.length;
    if (numImages > 0) {
        const index = Math.min(Math.floor((x / width) * numImages), numImages - 1);
        hoveredImageIndices.value[product.id] = index;
    }
};

const handleMouseLeave = (productId) => {
    hoveredImageIndices.value[productId] = 0;
};

const getActiveImageIndex = (product) => {
    if (hoveredImageIndices.value[product.id] !== undefined) {
        return hoveredImageIndices.value[product.id];
    }
    return activeImageIndices.value[product.id] || 0;
};

onMounted(() => {
    if (typeof window === 'undefined') return;
    gsap.registerPlugin(ScrollTrigger);

    // ── Sidebar: slides in from the left, comfy ease ─────────────────
    gsap.fromTo('.shop-sidebar',
        { x: -40, autoAlpha: 0 },
        { x: 0, autoAlpha: 1, duration: 0.9, ease: 'power2.out', delay: 0.15 }
    );

    // ── Sort bar: fades down from top ────────────────────────────────
    gsap.fromTo('.shop-sort-bar',
        { y: -18, autoAlpha: 0 },
        { y: 0, autoAlpha: 1, duration: 0.7, ease: 'power2.out', delay: 0.2 }
    );

    // ── Product cards: stagger with a scale-up lift ───────────────────
    gsap.utils.toArray('.shop-product-card').forEach((card, i) => {
        gsap.fromTo(card,
            { y: 48, autoAlpha: 0, scale: 0.95 },
            {
                y: 0, autoAlpha: 1, scale: 1,
                duration: 0.75, ease: 'power2.out',
                scrollTrigger: {
                    trigger: card,
                    start: 'top 92%',
                    end: 'bottom 8%',
                    toggleActions: 'play reverse play reverse',
                },
                delay: (i % 5) * 0.07,
            }
        );
    });
});

onBeforeUnmount(() => {
    ScrollTrigger.getAll().forEach(t => t.kill());
});
</script>

<template>

    <Head :title="seo.title || 'Shop | SCARBINA'">
        <meta v-if="seo.description" head-key="description" name="description" :content="seo.description" />
    </Head>

    <StorefrontLayout :locale="locale" :is-rtl="isRtl" :settings="settings" :nav-pages="navPages" :footer="footer"
        :footer-pages="footerPages">
        <div class="bg-white text-[#111111] antialiased w-full min-h-screen">

            <section class="max-w-[88rem] mx-auto px-4 md:px-8 py-8 md:py-12">

                <div class="md:hidden flex items-center justify-between mb-6">
                    <h1 class="font-serif text-2xl text-[#111111]">Shop</h1>
                    <button @click="showMobileFilters = !showMobileFilters"
                        class="flex items-center gap-2 text-sm border border-[#e5e5e5] px-4 py-2 rounded-full">
                        <SlidersHorizontal class="w-4 h-4" />
                        Filters
                    </button>
                </div>

                <div class="flex flex-col md:flex-row gap-10 lg:gap-16 items-start">

                    <aside class="shop-sidebar hidden md:block w-[12rem] shrink-0">
                        <div class="mb-10">
                            <h3 class="text-[0.75rem] uppercase tracking-widest text-[#111111] font-semibold mb-6">Max
                                Price</h3>
                            <div class="space-y-4">
                                <input type="range" min="50" max="500" step="10" v-model="maxPrice"
                                    @change="applyFilters()"
                                    class="w-full accent-[#111111] bg-[#e5e5e5] h-1 rounded-lg appearance-none cursor-pointer" />
                                <div class="flex items-center justify-between text-[0.85rem] text-[#555555]">
                                    <span>$50</span>
                                    <span class="font-bold text-[#111111]">${{ maxPrice === 500 ? '500+' : maxPrice
                                    }}</span>
                                    <span>$500+</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-12">
                            <h3 class="text-[0.75rem] uppercase tracking-widest text-[#111111] font-semibold mb-6">
                                Categories</h3>

                            <div class="relative">
                                <ul class="space-y-4 max-h-[280px] overflow-y-auto pr-2 custom-scrollbar"
                                    :class="{ 'pb-8': collectionFilters.length + 1 > 8 }">
                                    <li>
                                        <button @click="selectCategory('')"
                                            class="text-[0.8rem] uppercase tracking-wider transition-colors duration-200"
                                            :class="!selectedCategory ? 'text-[#111111] font-bold border-b border-[#111111]' : 'text-[#888888] hover:text-[#111111]'">
                                            All Products
                                        </button>
                                    </li>
                                    <li v-for="cat in collectionFilters" :key="cat.slug">
                                        <button @click="selectCategory(cat.slug)"
                                            class="text-[0.8rem] uppercase tracking-wider transition-colors duration-200"
                                            :class="selectedCategory === cat.slug ? 'text-[#111111] font-bold border-b border-[#111111]' : 'text-[#888888] hover:text-[#111111]'">
                                            {{ cat.name }}
                                        </button>
                                    </li>
                                </ul>
                                <div v-if="collectionFilters.length + 1 > 8"
                                    class="absolute bottom-0 left-0 right-2 h-8 bg-gradient-to-t from-white to-transparent pointer-events-none z-10" />
                            </div>
                        </div>
                    </aside>

                    <main class="flex-1 w-full min-w-0">

                        <div class="shop-sort-bar hidden md:flex items-center justify-between pb-6 mb-8 border-b border-[#e5e5e5]">
                            <span class="text-[0.75rem] text-[#111111] uppercase tracking-widest font-semibold">{{
                                totalProducts }} Products</span>

                            <div class="flex items-center gap-6">
                                <select v-model="selectedSort" @change="applyFilters()"
                                    class="bg-[#f8f8f8] hover:bg-[#f0f0f0] transition-colors border-none text-[0.72rem] uppercase tracking-widest text-[#111111] font-bold focus:ring-0 cursor-pointer pl-4 pr-10 py-2.5 rounded-sm appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20stroke%3D%22%23111111%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22m6%208%204%204%204-4%22%2F%3E%3C%2Fsvg%3E')] bg-[size:1.25em_1.25em] bg-[position:right_0.6rem_center] bg-no-repeat">
                                    <option value="newest">Sort by latest</option>
                                    <option value="featured">Sort by popularity</option>
                                    <option value="price_asc">Price: low to high</option>
                                    <option value="price_desc">Price: high to low</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-x-4 gap-y-10 w-full">
                            <article v-for="product in productCards" :key="product.id"
                                class="shop-product-card flex flex-col group relative">

                                <div class="relative w-full aspect-[4/5] bg-[#f8f8f8] mb-3 overflow-hidden">

                                    <div class="md:hidden flex w-full h-full overflow-x-auto snap-x snap-mandatory no-scrollbar"
                                        @scroll="handleScroll($event, product.id)">
                                        <Link v-for="(img, idx) in product.images" :key="idx" :href="product.href"
                                            class="w-full h-full flex-shrink-0 snap-center">
                                            <img :src="img" :alt="`${product.alt} view ${idx + 1}`"
                                                class="w-full h-full object-cover object-center" />
                                        </Link>
                                    </div>

                                    <div class="hidden md:block w-full h-full relative cursor-pointer"
                                        @mousemove="handleMouseMove($event, product)"
                                        @mouseleave="handleMouseLeave(product.id)">
                                        <Link :href="product.href" class="absolute inset-0">
                                            <img v-for="(img, idx) in product.images" :key="idx" :src="img"
                                                :alt="`${product.alt} view ${idx + 1}`"
                                                class="absolute inset-0 w-full h-full object-cover object-center transition-opacity duration-300 ease-in-out"
                                                :class="getActiveImageIndex(product) === idx ? 'opacity-100 z-10' : 'opacity-0 z-0'" />
                                        </Link>
                                    </div>

                                    <div class="absolute bottom-2 left-3 right-3 flex gap-1 z-20 pointer-events-none">
                                        <div v-for="(img, idx) in product.images" :key="`ind-${idx}`"
                                            class="h-[3px] flex-1 rounded-full transition-colors duration-300"
                                            :class="getActiveImageIndex(product) === idx ? 'bg-[#111111]' : 'bg-[#111111]/20'">
                                        </div>
                                    </div>

                                    <!-- Actions container (Add to Bag & Favorite) -->
                                    <div
                                        class="absolute top-2.5 right-2.5 z-20 flex items-center gap-1.5 bg-white/95 border border-[#111111]/10 rounded-full px-2 py-1 shadow-md md:opacity-0 md:group-hover:opacity-100 transition-all duration-300">
                                        <button @click.prevent="addProductWithAnim($event, product)"
                                            class="p-1.5 text-[#111111] hover:bg-[#111111]/5 rounded-full transition-colors active:scale-90 flex items-center justify-center"
                                            :aria-label="`Add ${product.name} to bag`">
                                            <ShoppingBag class="h-4 w-4 stroke-[1.5]" />
                                        </button>
                                        <button @click.prevent="toggleFavoriteWithAnim($event, product)"
                                            class="p-1.5 text-[#111111] hover:bg-[#111111]/5 rounded-full transition-colors active:scale-90 flex items-center justify-center"
                                            :aria-label="favorites.isFavorite(product.id) ? 'Remove from favorites' : 'Add to favorites'">
                                            <Heart class="h-4 w-4 stroke-[1.5]"
                                                :class="{ 'fill-[#cf7467] text-[#cf7467]': favorites.isFavorite(product.id) }" />
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-col relative px-1">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span
                                            class="text-[0.65rem] tracking-widest uppercase text-[#777777] font-medium">{{
                                                product.category }}</span>
                                        <div v-if="product.tags?.length" class="flex gap-1.5">
                                            <span v-for="tag in product.tags" :key="tag"
                                                class="text-[0.6rem] px-1.5 py-0.5 bg-[#f5f5f5] text-[#111111] uppercase tracking-widest rounded-sm">{{
                                                    tag }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-end justify-between gap-4">
                                        <Link :href="product.href" class="block flex-1 min-w-0">
                                            <h2
                                                class="text-[0.85rem] text-[#111111] font-medium leading-tight hover:opacity-70 transition-opacity truncate">
                                                {{ product.name }}
                                            </h2>
                                        </Link>
                                        <span class="text-[0.85rem] font-medium text-[#111111] whitespace-nowrap">{{
                                            formatCurrency(product.current_price, product.currency) }}</span>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <nav v-if="products.links?.length"
                            class="flex justify-center gap-2 mt-20 border-t border-[#e5e5e5] pt-10"
                            aria-label="Pagination">
                            <Link v-for="link in products.links" :key="link.label" :href="link.url || '#'"
                                class="flex items-center justify-center min-w-[2.5rem] h-10 text-[0.9rem] transition-colors rounded-sm"
                                :class="link.active ? 'font-bold text-[#111111]' : (link.url ? 'text-[#777777] hover:text-[#111111]' : 'opacity-40 cursor-not-allowed')"
                                v-html="link.label" />
                        </nav>

                    </main>
                </div>
            </section>

            <!-- Mobile Filters Drawer Backdrop -->
            <Transition name="fade">
                <div v-if="showMobileFilters" @click="showMobileFilters = false"
                    class="fixed inset-0 z-[80] bg-black/40 backdrop-blur-sm md:hidden"></div>
            </Transition>

            <!-- Mobile Filters Drawer Content -->
            <Transition name="slide">
                <div v-if="showMobileFilters"
                    class="fixed top-3 right-3 bottom-[5.5rem] z-[81] w-[18rem] max-w-[85vw] bg-[#fff8f6] text-[#111111] p-6 shadow-[0_24px_70px_rgba(0,0,0,0.22)] flex flex-col md:hidden rounded-2xl border border-black/5">
                    <div class="flex items-center justify-between pb-4 border-b border-[#e5e5e5] mb-6">
                        <h2 class="text-[0.75rem] uppercase tracking-widest font-bold text-[#111111]">Filters</h2>
                        <button @click="showMobileFilters = false" class="p-1 hover:opacity-75">
                            <X class="w-5 h-5 text-[#111111]" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto space-y-8 pr-2">
                        <!-- Price -->
                        <div>
                            <h3 class="text-[0.75rem] uppercase tracking-widest text-[#777777] font-semibold mb-4">Max
                                Price</h3>
                            <div class="space-y-4">
                                <input type="range" min="50" max="500" step="10" v-model="maxPrice"
                                    @change="applyFilters()"
                                    class="w-full accent-[#111111] bg-[#e5e5e5] h-1 rounded-lg appearance-none cursor-pointer" />
                                <div class="flex items-center justify-between text-[0.85rem] text-[#555555]">
                                    <span>$50</span>
                                    <span class="font-bold text-[#111111]">${{ maxPrice === 500 ? '500+' : maxPrice
                                    }}</span>
                                    <span>$500+</span>
                                </div>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div>
                            <h3 class="text-[0.75rem] uppercase tracking-widest text-[#777777] font-semibold mb-4">
                                Categories</h3>
                            <ul class="space-y-4">
                                <li>
                                    <button @click="selectCategory(''); showMobileFilters = false"
                                        class="text-[0.85rem] transition-colors duration-200 block w-full text-left"
                                        :class="!selectedCategory ? 'text-[#111111] font-bold' : 'text-[#777777] hover:text-[#111111]'">
                                        All Products
                                    </button>
                                </li>
                                <li v-for="cat in collectionFilters" :key="cat.slug">
                                    <button @click="selectCategory(cat.slug); showMobileFilters = false"
                                        class="text-[0.85rem] transition-colors duration-200 block w-full text-left"
                                        :class="selectedCategory === cat.slug ? 'text-[#111111] font-bold' : 'text-[#777777] hover:text-[#111111]'">
                                        {{ cat.name }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </StorefrontLayout>
</template>

<style>
/* Hide scrollbar for mobile image swipe carousel */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    /* IE and Edge */
    scrollbar-width: none;
    /* Firefox */
}

/* Custom Thin Scrollbar for categories list */
.custom-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.custom-scrollbar::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
}

/* Fade Transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Slide Transition */
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}
</style>