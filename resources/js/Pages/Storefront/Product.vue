<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { cart, formatCurrency } from '@/Stores/cart';
import { favorites } from '@/Stores/favorites';
import { Head, Link } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Heart,
    ShoppingBag,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const props = defineProps({
    locale: { type: String, default: 'en' },
    isRtl: { type: Boolean, default: false },
    settings: { type: Object, default: () => ({}) },
    navPages: { type: Array, default: () => [] },
    footer: { type: Object, default: null },
    footerPages: { type: Array, default: () => [] },
    product: { type: Object, required: true },
    related: { type: Array, default: () => [] },
    seo: { type: Object, default: () => ({}) },
});

const activeImage = ref(0);
const selectedVariantId = ref(props.product.variants?.[0]?.id || null);
const thumbScroller = ref(null);
const mainImageScroller = ref(null);
const relatedScroller = ref(null);

const isExpanded = ref(false);
const isFavorite = ref(false);
const canScrollRelatedLeft = ref(false);
const canScrollRelatedRight = ref(false);

const isDraggingMobileCarousel = ref(false);
let startCarouselX = 0;
let startCarouselY = 0;
let carouselDragResetTimer = null;

const fallbackGallery = [
    props.product.image || '/storefront/soleil/shop-product-4.jpg',
    '/storefront/soleil/shop-product-7.jpg',
    '/storefront/soleil/shop-product-6.jpg',
    '/storefront/soleil/shop-product-3.jpg',
    '/storefront/soleil/shop-product-1.jpg',
];

const fallbackRelatedProducts = [
    { id: 101, name: 'The Siena Pump', category: 'Pumps', price: 295, image: '/storefront/soleil/shop-product-3.jpg', slug: 'siena-pump' },
    { id: 102, name: 'Luna Slide', category: 'Sandals', price: 185, image: '/storefront/soleil/shop-product-4.jpg', slug: 'luna-slide' },
    { id: 103, name: 'Chloe Block Heel', category: 'Heels', price: 310, image: '/storefront/soleil/shop-product-6.jpg', slug: 'chloe-heel' },
    { id: 104, name: 'Capri Loafer', category: 'Flats', price: 245, image: '/storefront/soleil/shop-product-7.jpg', slug: 'capri-loafer' },
    { id: 105, name: 'Pin Mule', category: 'Mules', price: 335, image: '/storefront/soleil/shop-product-1.jpg', slug: 'pin-mule' },
    { id: 106, name: 'Meurisier', category: 'Heels', price: 345, image: '/storefront/soleil/shop-product-3.jpg', slug: 'meurisier' },
    { id: 107, name: 'Rosée Flat', category: 'Flats', price: 295, image: '/storefront/soleil/shop-product-4.jpg', slug: 'rosee-flat' },
    { id: 108, name: 'Noisetier Wedge', category: 'Platforms', price: 370, image: '/storefront/soleil/shop-product-6.jpg', slug: 'noisetier' },
];

const galleryImages = computed(() => {
    const productImages = (props.product.images || [])
        .map((image) => ({
            url: image.url,
            alt: image.alt || props.product.name,
        }))
        .filter((image) => image.url);

    const baseImages = productImages.length
        ? productImages
        : (props.product.image ? [{ url: props.product.image, alt: props.product.name }] : []);

    const seen = new Set(baseImages.map((image) => image.url));

    return [
        ...baseImages,
        ...fallbackGallery
            .filter((url) => !seen.has(url))
            .map((url) => ({ url, alt: props.product.name })),
    ].slice(0, 5);
});

const relatedProducts = computed(() => {
    const seen = new Set();
    const mergedProducts = [...(props.related || []), ...fallbackRelatedProducts];

    return mergedProducts
        .filter((product) => {
            const key = product.id || product.slug || product.name;

            if (!key || seen.has(key)) {
                return false;
            }

            seen.add(key);
            return true;
        })
        .slice(0, 12);
});

const selectedVariant = computed(() => {
    return props.product.variants?.find((variant) => variant.id === selectedVariantId.value) || null;
});

const displayPrice = computed(() => selectedVariant.value?.price ?? props.product.current_price);

function pathFor(path = '') {
    const [basePath, query = ''] = String(path).replace(/^\/+/, '').split('?');
    const prefix = props.locale === 'en' ? '' : `/${props.locale}`;
    const url = `${prefix}/${basePath}`.replace(/\/$/, '') || '/';

    return query ? `${url}?${query}` : url;
}

function toggleFavorite() {
    isFavorite.value = !isFavorite.value;
}

function setActiveImage(index) {
    activeImage.value = (index + galleryImages.value.length) % galleryImages.value.length;

    requestAnimationFrame(() => {
        const activeButton = thumbScroller.value?.querySelector(`[data-thumb-index="${activeImage.value}"]`);
        activeButton?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

        const mainImage = mainImageScroller.value?.querySelector(`[data-main-index="${activeImage.value}"]`);
        mainImage?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    });
}

function nextImage() {
    setActiveImage(activeImage.value + 1);
}

function previousImage() {
    setActiveImage(activeImage.value - 1);
}

function handleMainScroll(event) {
    const container = event.target;
    const itemWidth = container.clientWidth;
    const newIndex = Math.round(container.scrollLeft / itemWidth);

    if (newIndex !== activeImage.value && newIndex >= 0 && newIndex < galleryImages.value.length) {
        activeImage.value = newIndex;

        const activeButton = thumbScroller.value?.querySelector(`[data-thumb-index="${newIndex}"]`);
        activeButton?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
}

function handleCarouselTouchStart(event) {
    if (carouselDragResetTimer) {
        clearTimeout(carouselDragResetTimer);
        carouselDragResetTimer = null;
    }

    startCarouselX = event.touches[0].clientX;
    startCarouselY = event.touches[0].clientY;
    isDraggingMobileCarousel.value = false;
}

function handleCarouselTouchMove(event) {
    if (!startCarouselX || !startCarouselY) {
        return;
    }

    const diffX = Math.abs(event.touches[0].clientX - startCarouselX);
    const diffY = Math.abs(event.touches[0].clientY - startCarouselY);

    if (diffX > 10 || diffY > 10) {
        isDraggingMobileCarousel.value = true;
    }
}

function handleCarouselTouchEnd() {
    carouselDragResetTimer = window.setTimeout(() => {
        isDraggingMobileCarousel.value = false;
        startCarouselX = 0;
        startCarouselY = 0;
    }, 140);
}

function handleCarouselClick() {
    if (isDraggingMobileCarousel.value) {
        return;
    }

    isExpanded.value = true;
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

function toggleFavoriteProductWithAnim(event) {
    const button = event.currentTarget;
    animateHeart(button);
    favorites.toggleItem(props.product);
}

function toggleRelatedFavoriteWithAnim(event, product) {
    const button = event.currentTarget;
    animateHeart(button);
    favorites.toggleItem(product);
}

function addToBagWithAnim(event) {
    const button = event.currentTarget;
    animateCart(button);
    cart.addItem(props.product, selectedVariant.value, 1);
}

function relatedProductHref(product) {
    return product.href || pathFor(`products/${product.slug}`);
}

function relatedProductImage(product) {
    return product.image
        || product.thumbnail
        || product.main_image
        || '/storefront/soleil/shop-product-7.jpg';
}

function relatedProductPrice(product) {
    return formatCurrency(product.current_price || product.price || 0, product.currency || 'USD');
}

function updateRelatedArrowState() {
    if (!relatedScroller.value) {
        canScrollRelatedLeft.value = false;
        canScrollRelatedRight.value = false;
        return;
    }

    const { scrollLeft, scrollWidth, clientWidth } = relatedScroller.value;
    const maxScrollLeft = Math.max(scrollWidth - clientWidth, 0);

    canScrollRelatedLeft.value = scrollLeft > 6;
    canScrollRelatedRight.value = scrollLeft < maxScrollLeft - 6;
}

function scrollRelated(direction) {
    if (!relatedScroller.value) {
        return;
    }

    const firstCard = relatedScroller.value.querySelector('.related-rail-card');
    const cardWidth = firstCard?.getBoundingClientRect().width || 220;
    const amount = (cardWidth + 20) * 2;

    relatedScroller.value.scrollBy({
        left: direction === 'next' ? amount : -amount,
        behavior: 'smooth',
    });
}

function closeLightbox() {
    isExpanded.value = false;
}

watch(isExpanded, (value) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = value ? 'hidden' : '';
    }
});

onMounted(async () => {
    await nextTick();

    if (typeof window !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);

        // ── Hero split: gallery slides in from LEFT, info from RIGHT ────
        const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
        tl.fromTo('.product-gallery',
            { x: -50, autoAlpha: 0 },
            { x: 0, autoAlpha: 1, duration: 1.1 }
        ).fromTo('.product-info-panel',
            { x: 50, autoAlpha: 0 },
            { x: 0, autoAlpha: 1, duration: 1.1 },
            '<0.12'
        );

        // ── Breadcrumb: quick clip-in from left ─────────────────────────
        gsap.fromTo('.product-breadcrumb',
            { autoAlpha: 0, x: -20 },
            { autoAlpha: 1, x: 0, duration: 0.7, ease: 'power2.out', delay: 0.05 }
        );

        // ── Specs / accordion rows: stagger up from below ───────────────
        gsap.utils.toArray('.product-spec-row').forEach((row, i) => {
            gsap.fromTo(row,
                { y: 24, autoAlpha: 0 },
                {
                    y: 0, autoAlpha: 1,
                    duration: 0.55, ease: 'power2.out',
                    scrollTrigger: {
                        trigger: row,
                        start: 'top 92%',
                        end: 'bottom 5%',
                        toggleActions: 'play reverse play reverse',
                    },
                    delay: i * 0.05,
                }
            );
        });

        // ── Related products rail: cascade with scale + stagger ──────────
        gsap.utils.toArray('.related-product-card').forEach((card, i) => {
            gsap.fromTo(card,
                { x: 40, autoAlpha: 0, scale: 0.96 },
                {
                    x: 0, autoAlpha: 1, scale: 1,
                    duration: 0.7, ease: 'back.out(1.3)',
                    scrollTrigger: {
                        trigger: '.product-related-section',
                        start: 'top 88%',
                        end: 'bottom 5%',
                        toggleActions: 'play reverse play reverse',
                    },
                    delay: Math.min(i * 0.06, 0.48),
                }
            );
        });
    }

    updateRelatedArrowState();
    relatedScroller.value?.addEventListener('scroll', updateRelatedArrowState, { passive: true });
    window.addEventListener('resize', updateRelatedArrowState);
});

onBeforeUnmount(() => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }

    if (carouselDragResetTimer) {
        clearTimeout(carouselDragResetTimer);
    }

    ScrollTrigger.getAll().forEach(t => t.kill());
    relatedScroller.value?.removeEventListener('scroll', updateRelatedArrowState);
    window.removeEventListener('resize', updateRelatedArrowState);
});
</script>

<template>

    <Head :title="seo.title || `${product.name} | SCARBINA`">
        <meta v-if="seo.description" head-key="description" name="description" :content="seo.description" />
    </Head>

    <StorefrontLayout :locale="locale" :is-rtl="isRtl" :settings="settings" :nav-pages="navPages" :footer="footer"
        :footer-pages="footerPages">
        <div class="product-detail-page bg-[#fef8f6] text-[#1d1b1a] antialiased w-full min-h-screen relative">
            <article class="max-w-[68rem] mx-auto px-5 md:px-8 py-4 md:py-6">
                <nav class="product-breadcrumb flex items-center gap-2 text-[0.65rem] font-bold uppercase tracking-widest text-[#cf7467] mb-6"
                    aria-label="Breadcrumb">
                    <Link :href="pathFor('shop')" class="hover:text-[#B85C50] transition-colors">
                        Shop
                    </Link>
                    <span class="text-[#d8c2bc]">/</span>
                    <span class="text-[#53433f]">{{ product.name }}</span>
                </nav>

                <div class="flex flex-col lg:grid lg:grid-cols-2 gap-10 lg:gap-12 items-start">
                    <section class="product-gallery w-full min-w-0" aria-label="Product images">
                        <div class="relative w-full bg-[#f7ebe8] overflow-hidden rounded-sm group">
                            <div ref="mainImageScroller"
                                class="flex md:hidden overflow-x-auto snap-x snap-mandatory no-scrollbar w-full aspect-square"
                                @scroll.passive="handleMainScroll" @touchstart="handleCarouselTouchStart"
                                @touchmove="handleCarouselTouchMove" @touchend="handleCarouselTouchEnd"
                                @touchcancel="handleCarouselTouchEnd">
                                <div v-for="(image, index) in galleryImages" :key="`main-${index}`"
                                    :data-main-index="index" class="w-full h-full shrink-0 snap-center cursor-zoom-in"
                                    @click="handleCarouselClick">
                                    <img :src="image.url" :alt="image.alt"
                                        class="w-full h-full object-cover object-center" />
                                </div>
                            </div>

                            <div class="hidden md:block aspect-[4/5] max-h-[420px] lg:max-h-[460px] relative w-full cursor-zoom-in"
                                @click="isExpanded = true">
                                <img :src="galleryImages[activeImage]?.url" :alt="galleryImages[activeImage]?.alt"
                                    class="absolute inset-0 w-full h-full object-cover object-center transition-opacity duration-300" />

                                <button type="button"
                                    class="absolute left-3 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center bg-white/80 hover:bg-white text-[#B85C50] rounded-full shadow-sm opacity-0 group-hover:opacity-100 transition-all duration-300 transform -translate-x-2 group-hover:translate-x-0"
                                    aria-label="Previous image" @click.stop="previousImage">
                                    <ChevronLeft class="h-5 w-5 stroke-[1.5]" />
                                </button>

                                <button type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center bg-white/80 hover:bg-white text-[#B85C50] rounded-full shadow-sm opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0"
                                    aria-label="Next image" @click.stop="nextImage">
                                    <ChevronRight class="h-5 w-5 stroke-[1.5]" />
                                </button>
                            </div>
                        </div>

                        <div ref="thumbScroller" class="flex gap-2.5 overflow-x-auto no-scrollbar snap-x mt-3 pb-1"
                            aria-label="Product thumbnails">
                            <button v-for="(image, index) in galleryImages" :key="image.url" type="button"
                                :data-thumb-index="index"
                                class="relative w-[3.5rem] md:w-[4.2rem] shrink-0 aspect-square bg-[#f7ebe8] snap-start rounded-sm overflow-hidden border-[1.5px] transition-colors duration-200"
                                :class="activeImage === index ? 'border-[#B85C50] opacity-100' : 'border-transparent opacity-60 hover:opacity-100'"
                                @click="setActiveImage(index)">
                                <img :src="image.url" :alt="image.alt" class="w-full h-full object-cover" />
                            </button>
                        </div>
                    </section>

                    <section class="product-info-panel w-full lg:sticky lg:top-28 lg:max-w-md mx-auto lg:ml-auto lg:mr-0">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-[#cf7467] text-[0.6rem] font-bold uppercase tracking-[0.2em]">
                                {{ product.category || 'Heels' }}
                            </span>

                            <button type="button"
                                class="p-1 -mt-1 -mr-1 text-[#B85C50] hover:scale-110 transition-transform"
                                :aria-label="favorites.isFavorite(product.id) ? 'Remove from favorites' : 'Add to favorites'"
                                @click.prevent="toggleFavoriteProductWithAnim">
                                <Heart class="h-[1.2rem] w-[1.2rem] stroke-[1.5]"
                                    :class="{ 'fill-[#cf7467] text-[#cf7467]': favorites.isFavorite(product.id) }" />
                            </button>
                        </div>

                        <h1
                            class="font-serif text-[#B85C50] text-3xl lg:text-[2.2rem] leading-tight font-medium tracking-tight mb-2">
                            {{ product.name }}
                        </h1>

                        <p class="text-[#B85C50] text-[1.05rem] font-extrabold tracking-wide mb-5">
                            {{ formatCurrency(displayPrice, product.currency || 'USD') }}
                        </p>

                        <p class="text-[#53433f] text-[0.9rem] leading-[1.65] mb-6">
                            {{ product.description || product.short_description || '' }}
                        </p>

                        <div v-if="product.variants?.length" class="mb-6">
                            <div class="flex justify-between items-center mb-2.5">
                                <span class="text-[#111111] text-[0.6rem] font-extrabold uppercase tracking-[0.15em]">
                                    Select Size
                                </span>

                                <button type="button"
                                    class="text-[#cf7467] text-[0.6rem] font-extrabold uppercase tracking-[0.15em] hover:text-[#B85C50] transition-colors">
                                    Size Guide
                                </button>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button v-for="variant in product.variants" :key="variant.id" type="button"
                                    class="w-[3rem] h-[2.5rem] flex items-center justify-center text-[0.8rem] font-bold transition-all rounded-sm border"
                                    :class="selectedVariantId === variant.id ? 'bg-[#cf7467] text-white border-[#cf7467]' : 'bg-[#f7ebe8] text-[#1d1b1a] border-[#d8c2bc]/30 hover:border-[#cf7467]'"
                                    @click="selectedVariantId = variant.id">
                                    {{ variant.size || variant.color || 'One' }}
                                </button>
                            </div>
                        </div>

                        <button type="button"
                            class="w-full bg-[#cf7467] hover:bg-[#B85C50] text-white h-[3.25rem] flex items-center justify-center gap-2 text-[0.7rem] font-bold uppercase tracking-[0.2em] rounded-sm transition-colors mb-4"
                            @click="addToBagWithAnim">
                            <ShoppingBag class="h-[1.1rem] w-[1.1rem] stroke-[2]" />
                            Add to Bag
                        </button>

                        <div class="bg-[#fbf0ee] border border-[#d8c2bc]/30 rounded-sm py-3 text-center mb-6">
                            <span
                                class="block text-[#cf7467] text-[0.65rem] font-extrabold uppercase tracking-[0.2em] mb-0.5">
                                Free Returns
                            </span>
                            <span class="block text-[#53433f] text-[0.7rem]">
                                Ships in 2-3 business days
                            </span>
                        </div>

                        <div class="border-t border-[#111111]/10">
                            <details class="product-spec-row group border-b border-[#111111]/10" open>
                                <summary
                                    class="flex justify-between items-center cursor-pointer list-none py-3.5 text-[#111111] text-[0.65rem] font-bold uppercase tracking-[0.15em]">
                                    Product Details
                                    <ChevronDown
                                        class="h-3.5 w-3.5 text-[#B85C50] transition-transform duration-300 group-open:-rotate-180" />
                                </summary>

                                <div class="pb-4 text-[#53433f] text-[0.85rem] leading-[1.6]">
                                    <p>{{ product.short_description || '' }}</p>
                                </div>
                            </details>

                            <details class="product-spec-row group border-b border-[#111111]/10">
                                <summary
                                    class="flex justify-between items-center cursor-pointer list-none py-3.5 text-[#111111] text-[0.65rem] font-bold uppercase tracking-[0.15em]">
                                    Materials & Care
                                    <ChevronDown
                                        class="h-3.5 w-3.5 text-[#B85C50] transition-transform duration-300 group-open:-rotate-180" />
                                </summary>

                                <div class="pb-4 text-[#53433f] text-[0.85rem] leading-[1.6]">
                                    <ul class="list-disc pl-4 space-y-1">
                                        <li>100% Premium Nappa Leather upper</li>
                                        <li>Leather lining and sole</li>
                                        <li>Wipe clean with a dry, soft cloth</li>
                                        <li>Store in provided dust bag away from direct sunlight</li>
                                    </ul>
                                </div>
                            </details>

                            <details class="product-spec-row group border-b border-[#111111]/10">
                                <summary
                                    class="flex justify-between items-center cursor-pointer list-none py-3.5 text-[#111111] text-[0.65rem] font-bold uppercase tracking-[0.15em]">
                                    Shipping & Returns
                                    <ChevronDown
                                        class="h-3.5 w-3.5 text-[#B85C50] transition-transform duration-300 group-open:-rotate-180" />
                                </summary>

                                <div class="pb-4 text-[#53433f] text-[0.85rem] leading-[1.6]">
                                    <p>
                                        Enjoy free standard shipping on all orders. You have 14 days from delivery to
                                        request a return or exchange. Orders are seamlessly confirmed via WhatsApp.
                                    </p>
                                </div>
                            </details>
                        </div>
                    </section>
                </div>
            </article>

            <section v-if="relatedProducts.length" class="product-related-section related-rail-section">
                <div class="related-rail-shell">
                    <div class="related-rail-heading-row">
                        <h2 class="related-rail-title">
                            Browse More <em>Products</em>
                        </h2>

                        <div class="related-rail-arrows">
                            <button type="button" class="related-rail-arrow" :disabled="!canScrollRelatedLeft"
                                aria-label="Previous products" @click="scrollRelated('prev')">
                                <ChevronLeft class="h-5 w-5 stroke-[1.7]" />
                            </button>

                            <button type="button" class="related-rail-arrow" :disabled="!canScrollRelatedRight"
                                aria-label="Next products" @click="scrollRelated('next')">
                                <ChevronRight class="h-5 w-5 stroke-[1.7]" />
                            </button>
                        </div>
                    </div>

                    <div ref="relatedScroller" class="related-rail-track no-scrollbar"
                        @scroll.passive="updateRelatedArrowState">
                        <Link v-for="product in relatedProducts" :key="product.id || product.slug"
                            :href="relatedProductHref(product)" class="related-product-card related-rail-card">
                            <div class="related-rail-media">
                                <img :src="relatedProductImage(product)" :alt="product.name" loading="lazy" />

                                <button type="button" class="related-rail-favorite" aria-label="Add to favorites"
                                    @click.prevent.stop="toggleRelatedFavoriteWithAnim($event, product)">
                                    <Heart class="h-4 w-4 stroke-[1.5]"
                                        :class="{ 'fill-[#cf7467] text-[#cf7467]': favorites.isFavorite(product.id) }" />
                                </button>
                            </div>

                            <div class="related-rail-info">
                                <h3>{{ product.name }}</h3>

                                <p v-if="product.category">
                                    {{ product.category }}
                                </p>

                                <span>
                                    {{ relatedProductPrice(product) }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>
        </div>

        <Teleport to="body">
            <Transition name="lightbox">
                <div v-if="isExpanded"
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-[#111111]/90 backdrop-blur-md lightbox-shell"
                    @click.self="closeLightbox">
                    <button type="button"
                        class="absolute top-4 right-4 md:top-8 md:right-8 w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors z-[101]"
                        aria-label="Close lightbox" @click.stop="closeLightbox">
                        <X class="w-5 h-5 md:w-6 md:h-6 stroke-[1.5]" />
                    </button>

                    <button type="button"
                        class="hidden md:flex absolute left-8 top-1/2 -translate-y-1/2 w-12 h-12 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors z-[101]"
                        aria-label="Previous image" @click.stop="previousImage">
                        <ChevronLeft class="w-8 h-8 stroke-[1.5]" />
                    </button>

                    <button type="button"
                        class="hidden md:flex absolute right-8 top-1/2 -translate-y-1/2 w-12 h-12 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors z-[101]"
                        aria-label="Next image" @click.stop="nextImage">
                        <ChevronRight class="w-8 h-8 stroke-[1.5]" />
                    </button>

                    <img :src="galleryImages[activeImage]?.url" :alt="galleryImages[activeImage]?.alt"
                        class="max-w-[95vw] max-h-[90vh] object-contain cursor-zoom-out select-none shadow-2xl lightbox-image"
                        @click.stop="closeLightbox" />

                    <p
                        class="absolute bottom-5 left-1/2 -translate-x-1/2 text-white/60 text-[0.7rem] font-bold uppercase tracking-[0.18em] md:hidden">
                        Tap image to close
                    </p>
                </div>
            </Transition>
        </Teleport>
    </StorefrontLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

details>summary::-webkit-details-marker {
    display: none;
}

.lightbox-shell {
    touch-action: none;
    overscroll-behavior: contain;
}

.lightbox-image {
    -webkit-user-drag: none;
    user-drag: none;
}

.lightbox-enter-active,
.lightbox-leave-active {
    transition: all 0.5s cubic-bezier(0.25, 1, 0.5, 1);
}

.lightbox-enter-from,
.lightbox-leave-to {
    opacity: 0;
    backdrop-filter: blur(0px);
}

.lightbox-enter-from img,
.lightbox-leave-to img {
    transform: scale(0.95);
}

/* Related products rail: isolated from app.css */
.related-rail-section {
    width: 100%;
    margin-top: 3rem;
    overflow: hidden;
    background: var(--soleil-surface, #fff8f6);
}

.related-rail-shell {
    width: min(100%, var(--soleil-container-max, 70rem));
    margin-inline: auto;
}

.related-rail-heading-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-inline: 2rem 1rem;
}

.related-rail-title {
    color: var(--soleil-primary-strong, #CF7467);
    font-family: 'Playfair Display', Georgia, serif;
    font-size: clamp(1.45rem, 4vw, 2rem);
    font-weight: 500;
    line-height: 1.1;
}

.related-rail-title em {
    color: var(--soleil-primary, #cf7467);
    font-style: italic;
    font-weight: 400;
}

.related-rail-arrows {
    display: none;
    align-items: center;
    gap: 0.55rem;
}

.related-rail-arrow {
    display: grid;
    width: 2.45rem;
    height: 2.45rem;
    place-items: center;
    border: 1px solid rgb(207 116 103 / 0.24);
    border-radius: 999px;
    background: rgb(255 248 246 / 0.9);
    color: var(--soleil-primary, #cf7467);
    box-shadow: 0 10px 26px rgb(109 86 81 / 0.08);
    transition:
        background 160ms ease,
        color 160ms ease,
        border-color 160ms ease,
        opacity 160ms ease,
        transform 160ms ease;
}

.related-rail-arrow:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: var(--soleil-primary, #cf7467);
    background: var(--soleil-primary, #cf7467);
    color: #ffffff;
}

.related-rail-arrow:disabled {
    cursor: not-allowed;
    opacity: 0.32;
}

.related-rail-track {
    display: flex;
    gap: 1rem;
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    overscroll-behavior-inline: contain;
    scroll-snap-type: x mandatory;
    scroll-padding-left: 2rem;
    -webkit-overflow-scrolling: touch;
    padding: 1.25rem 1rem 1.5rem 2rem;
}

.related-rail-card {
    display: block;
    box-sizing: border-box;
    flex: 0 0 9.75rem;
    width: 9.75rem;
    min-width: 9.75rem;
    max-width: 9.75rem;
    scroll-snap-align: start;
    color: var(--soleil-text, #241c1a);
}

.related-rail-media {
    position: relative;
    width: 100%;
    aspect-ratio: 3 / 4;
    overflow: hidden;
    border-radius: 0.45rem;
    background: var(--soleil-surface-container, #f7ebe8);
}

.related-rail-media img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: var(--soleil-surface-container, #f7ebe8);
    transition: transform 500ms ease;
}

.related-rail-card:hover .related-rail-media img {
    transform: scale(1.035);
}

.related-rail-favorite {
    position: absolute;
    top: 0.55rem;
    right: 0.55rem;
    z-index: 2;
    display: grid;
    width: 1.9rem;
    height: 1.9rem;
    place-items: center;
    border-radius: 999px;
    background: rgb(255 248 246 / 0.86);
    color: var(--soleil-primary, #cf7467);
    box-shadow: 0 8px 18px rgb(109 86 81 / 0.08);
    backdrop-filter: blur(10px);
}

.related-rail-info {
    display: block;
    padding-top: 0.55rem;
}

.related-rail-info h3 {
    max-width: 100%;
    overflow: hidden;
    color: var(--soleil-primary-strong, #CF7467);
    font-family: 'Hanken Grotesk', 'Segoe UI', sans-serif;
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1.15;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.related-rail-info p {
    margin-top: 0.18rem;
    overflow: hidden;
    color: rgb(109 86 81 / 0.72);
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    line-height: 1.1;
    text-overflow: ellipsis;
    text-transform: uppercase;
    white-space: nowrap;
}

.related-rail-info span {
    display: block;
    margin-top: 0.28rem;
    color: var(--soleil-primary, #cf7467);
    font-size: 0.78rem;
    font-weight: 800;
    line-height: 1;
}

@media (min-width: 640px) {
    .related-rail-heading-row {
        padding-inline: 2rem 1.5rem;
    }

    .related-rail-track {
        gap: 1.15rem;
        padding: 1.35rem 1.5rem 1.75rem 2rem;
    }

    .related-rail-card {
        flex-basis: 11rem;
        width: 11rem;
        min-width: 11rem;
        max-width: 11rem;
    }
}

@media (min-width: 768px) {
    .related-rail-section {
        margin-top: 3.25rem;
    }

    .related-rail-heading-row {
        padding-inline: 2rem 2.75rem;
    }

    .related-rail-arrows {
        display: flex;
    }

    .related-rail-track {
        gap: 1.25rem;
        padding: 1.5rem 2.75rem 1.85rem 2rem;
    }

    .related-rail-card {
        flex-basis: 12.25rem;
        width: 12.25rem;
        min-width: 12.25rem;
        max-width: 12.25rem;
    }
}

@media (min-width: 1024px) {
    .related-rail-track {
        gap: 1.35rem;
        padding-left: 2rem;
        padding-right: 2.75rem;
    }

    .related-rail-card {
        flex: 0 0 12.75rem;
        width: 12.75rem;
        min-width: 12.75rem;
        max-width: 12.75rem;
    }
}
</style>
