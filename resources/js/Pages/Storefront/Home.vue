<script>
let initialLoadComplete = false;
</script>

<script setup>
import Loader from '@/Components/Loader.vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { formatCurrency } from '@/Stores/cart';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, MessageCircle, PlayCircle } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const props = defineProps({
    locale: { type: String, default: 'en' },
    isRtl: { type: Boolean, default: false },
    locales: { type: Array, default: () => ['en', 'fr', 'ar'] },
    settings: { type: Object, default: () => ({}) },
    navPages: { type: Array, default: () => [] },
    footer: { type: Object, default: null },
    footerPages: { type: Array, default: () => [] },
    hero: { type: Object, default: null },
    sections: { type: Array, default: () => [] },
    newArrivals: { type: Array, default: () => [] },
    seo: { type: Object, default: () => ({}) },
});

const page = usePage();

const showLoader = ref(!initialLoadComplete);
const hideHeaderLogoForIntro = ref(showLoader.value);
const heroAnimationPlayed = ref(false);

function canUseMotion() {
    return typeof window !== 'undefined'
        && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function onLoaderLanded() {
    hideHeaderLogoForIntro.value = false;
}

function setHeroInitialState() {
    if (!canUseMotion()) {
        return;
    }

    gsap.set('.hero-curtain-wrap', { clipPath: 'inset(0% 0% 100% 0%)' });
    gsap.set('.hero-curtain-img', { scale: 1.18 });
    gsap.set('.hero-product-badge', { y: 24, autoAlpha: 0 });
    gsap.set('.hero-tag', { autoAlpha: 0, letterSpacing: '0.55em', y: -6 });
    gsap.set('.hero-line-inner', { yPercent: 108 });
    gsap.set('.hero-copy', { autoAlpha: 0, y: 14 });
    gsap.set('.hero-cta-btn', { autoAlpha: 0, y: 20, scale: 0.95 });
}

function playHeroAnimation() {
    if (!canUseMotion() || heroAnimationPlayed.value) {
        return;
    }

    heroAnimationPlayed.value = true;

    const heroTl = gsap.timeline({ defaults: { ease: 'power3.out' } });

    heroTl
        .to(
            '.hero-curtain-wrap',
            {
                clipPath: 'inset(0% 0% 0% 0%)',
                duration: 1.15,
                ease: 'expo.inOut',
            },
            0,
        )
        .to(
            '.hero-curtain-img',
            {
                scale: 1.05,
                duration: 1.45,
                ease: 'power3.out',
            },
            0,
        )
        .to(
            '.hero-product-badge',
            {
                y: 0,
                autoAlpha: 1,
                duration: 0.6,
                ease: 'back.out(1.5)',
            },
            0.78,
        )
        .to(
            '.hero-tag',
            {
                autoAlpha: 1,
                letterSpacing: '0.2em',
                y: 0,
                duration: 0.65,
                ease: 'power4.out',
            },
            0.34,
        )
        .to(
            '.hero-line-inner',
            {
                yPercent: 0,
                duration: 0.95,
                ease: 'expo.out',
                stagger: 0.12,
            },
            0.48,
        )
        .to(
            '.hero-copy',
            {
                autoAlpha: 1,
                y: 0,
                duration: 0.65,
                ease: 'power2.out',
            },
            0.92,
        )
        .to(
            '.hero-cta-btn',
            {
                autoAlpha: 1,
                y: 0,
                scale: 1,
                duration: 0.55,
                ease: 'back.out(1.6)',
                stagger: 0.09,
            },
            1.08,
        );
}

async function onLoaderDone() {
    initialLoadComplete = true;
    showLoader.value = false;
    hideHeaderLogoForIntro.value = false;

    await nextTick();

    window.requestAnimationFrame(() => {
        playHeroAnimation();
    });
}

function setupScrollAnimations() {
    if (!canUseMotion()) {
        return;
    }

    gsap.fromTo(
        '.home-arrivals-heading',
        { y: 30, autoAlpha: 0 },
        {
            y: 0,
            autoAlpha: 1,
            duration: 0.9,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.home-arrivals-heading',
                start: 'top 88%',
                end: 'bottom 15%',
                toggleActions: 'play reverse play reverse',
            },
        },
    );

    gsap.utils.toArray('.home-arrival-card').forEach((card, i) => {
        gsap.fromTo(
            card,
            { y: 60, autoAlpha: 0, scale: 0.97 },
            {
                y: 0,
                autoAlpha: 1,
                scale: 1,
                duration: 0.95,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: card,
                    start: 'top 90%',
                    end: 'bottom 10%',
                    toggleActions: 'play reverse play reverse',
                },
                delay: i * 0.07,
            },
        );
    });

    gsap.fromTo(
        '.home-editorial-image',
        { x: -70, autoAlpha: 0, scale: 1.04 },
        {
            x: 0,
            autoAlpha: 1,
            scale: 1,
            duration: 1.25,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.home-editorial-section',
                start: 'top 82%',
                end: 'bottom 15%',
                toggleActions: 'play reverse play reverse',
            },
        },
    );

    gsap.fromTo(
        '.home-editorial-text',
        { x: 70, autoAlpha: 0 },
        {
            x: 0,
            autoAlpha: 1,
            duration: 1.25,
            ease: 'power3.out',
            delay: 0.12,
            scrollTrigger: {
                trigger: '.home-editorial-section',
                start: 'top 82%',
                end: 'bottom 15%',
                toggleActions: 'play reverse play reverse',
            },
        },
    );

    gsap.fromTo(
        '.home-promo-section',
        { y: 44, autoAlpha: 0 },
        {
            y: 0,
            autoAlpha: 1,
            duration: 1.1,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: '.home-promo-section',
                start: 'top 85%',
                end: 'bottom 15%',
                toggleActions: 'play reverse play reverse',
            },
        },
    );
}

onMounted(async () => {
    if (typeof window === 'undefined') {
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    await nextTick();

    setHeroInitialState();
    setupScrollAnimations();

    if (!showLoader.value) {
        window.requestAnimationFrame(() => {
            playHeroAnimation();
        });
    }
});

onBeforeUnmount(() => {
    ScrollTrigger.getAll().forEach((trigger) => {
        if (trigger.trigger?.closest?.('.home-page-root')) {
            trigger.kill();
        }
    });
});

const sectionMap = computed(() => props.sections.reduce((map, section) => {
    if (section?.key) {
        map[section.key] = section;
    }

    return map;
}, {}));

const heroSection = computed(() => props.hero || sectionMap.value.hero || {});
const editorial = computed(() => sectionMap.value.editorial || {});
const promo = computed(() => sectionMap.value.promo_banner || {});
const brandName = computed(() => props.settings.brand_name || 'SCARBINA');
const logoSrc = computed(() => props.settings?.logo_url || '/logo.svg');

const assetPath = '/storefront/soleil/';

const heroCopy = computed(() => {
    const copy = heroSection.value.content || '';

    if (copy && !copy.toLowerCase().includes("women's shoes")) {
        return copy;
    }

    return 'High heels with sculptural lines, luminous finishes, and a balanced fit for polished evenings and elevated everyday dressing.';
});

const designImages = computed(() => ({
    heroDesktop: heroSection.value.image || `${assetPath}shop-product-7.jpg`,
    artisan: editorial.value.image || `${assetPath}artisan-story.jpg`,
    arrivals: [
        `${assetPath}shop-product-3.jpg`,
        `${assetPath}shop-product-4.jpg`,
        `${assetPath}shop-product-6.jpg`,
        `${assetPath}shop-product-7.jpg`,
    ],
}));

const fallbackProducts = [
    { title: 'Classic Black Stilettos', material: 'Heels', price: 120 },
    { title: 'Leather Ankle Boots', material: 'Boots', price: 135 },
    { title: 'Chloe Block Heel', material: 'Blush Pink', price: 310 },
    { title: 'The Siena Pump', material: 'Terracotta Leather', price: 295 },
];

const productPool = computed(() => {
    const seen = new Set();

    return props.newArrivals.filter((product) => {
        if (!product?.id || seen.has(product.id)) {
            return false;
        }

        seen.add(product.id);

        return true;
    });
});

const arrivalCards = computed(() => fallbackProducts.map((fallback, index) => {
    const product = productPool.value[index];

    return {
        id: product?.id || `fallback-${index}`,
        title: product?.name || fallback.title,
        material: product?.category || fallback.material,
        price: product ? formatCurrency(product.current_price, product.currency) : formatCurrency(fallback.price, 'USD'),
        href: product?.href || (product?.slug ? pathFor(`products/${product.slug}`) : pathFor('shop')),
        image: product?.image || designImages.value.arrivals[index],
    };
}));

function pathFor(path = '') {
    const [basePath, queryString = ''] = String(path).replace(/^\/+/, '').split('?');
    const prefix = props.locale === 'en' ? '' : `/${props.locale}`;
    const url = `${prefix}/${basePath}`.replace(/\/$/, '') || '/';

    return queryString ? `${url}?${queryString}` : url;
}

function isExternalUrl(url) {
    return /^https?:\/\//i.test(String(url || ''));
}

function normalizeUrl(url = '') {
    if (!url) {
        return pathFor('shop');
    }

    if (isExternalUrl(url)) {
        return url;
    }

    return pathFor(String(url).replace(/^\/+/, ''));
}
</script>

<template>
    <Loader v-if="showLoader" :logo-src="logoSrc" target-selector="#site-header-logo" @landed="onLoaderLanded"
        @done="onLoaderDone" />

    <Head :title="seo.title || `${brandName} | Home`">
        <meta v-if="seo.description" head-key="description" name="description" :content="seo.description" />
    </Head>

    <StorefrontLayout :locale="locale" :is-rtl="isRtl" :settings="settings" :nav-pages="navPages" :footer="footer"
        :footer-pages="footerPages" :intro-logo-hidden="hideHeaderLogoForIntro">
        <div class="home-page-root w-full overflow-hidden bg-[#fef8f6] text-[#1d1b1a] antialiased"
            style="perspective: 1000px;">
            <section
                class="relative mx-auto mb-12 w-full max-w-[75rem] px-0 pb-10 pt-[1rem] md:mb-20 md:px-12 md:pb-16 md:pt-12">
                <div class="flex flex-col items-center gap-8 md:grid md:grid-cols-12 lg:gap-14">
                    <div class="home-hero-image order-1 w-full md:order-2 md:col-span-7">
                        <div
                            class="hero-curtain-wrap relative aspect-[4/3] overflow-hidden bg-[#f7ebe8] md:aspect-[5/4] md:rounded-sm">
                            <img :src="designImages.heroDesktop" :alt="heroSection.image_alt || 'Elegant high-end shoe'"
                                class="hero-curtain-img h-full w-full object-cover transition-transform duration-[2s] ease-out hover:scale-100" />

                            <div
                                class="hero-product-badge absolute bottom-0 right-0 min-w-[150px] border-l border-t border-[#1d1b1a]/5 bg-[#fef8f6]/95 p-3 backdrop-blur-md md:min-w-[190px] md:p-5">
                                <span
                                    class="mb-1 block text-[0.55rem] font-bold uppercase tracking-[0.2em] text-[#cf7467] md:text-[0.6rem]">
                                    Featured
                                </span>

                                <h2
                                    class="font-serif text-[0.95rem] font-medium leading-tight text-[#CF7467] md:text-[1.1rem]">
                                    The Gilded Heel
                                </h2>

                                <p class="mt-1 text-[0.65rem] font-medium text-[#6d5651] md:text-[0.7rem]">
                                    Metallic Leather
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="home-hero-text order-2 z-10 flex flex-col items-center px-6 text-center md:order-1 md:col-span-5 md:items-start md:px-0 md:text-left">
                        <span
                            class="hero-tag mb-3 block text-[0.6rem] font-bold uppercase tracking-[0.2em] text-[#cf7467] md:mb-4 md:text-[0.65rem]">
                            The New Collection
                        </span>

                        <h1
                            class="mb-4 font-serif text-[clamp(2.8rem,5vw,4.2rem)] font-medium leading-[1.05] tracking-tight text-[#1d1b1a] md:mb-5">
                            <span class="hero-line-mask block overflow-hidden">
                                <span class="hero-line-inner block">Elegance</span>
                            </span>

                            <span class="hero-line-mask block overflow-hidden">
                                <span class="hero-line-inner block">in</span>
                            </span>

                            <span class="hero-line-mask block overflow-hidden">
                                <span class="hero-line-inner block font-light italic text-[#CF7467] md:ml-12">
                                    Every Step
                                </span>
                            </span>
                        </h1>

                        <p
                            class="hero-copy mb-6 max-w-[20rem] text-[0.9rem] leading-[1.7] text-[#6d5651] md:mb-8 md:max-w-sm md:text-[0.95rem] md:leading-[1.8]">
                            {{ heroCopy }}
                        </p>

                        <div class="flex flex-wrap items-center justify-center gap-4 md:justify-start md:gap-8">
                            <Link :href="normalizeUrl(heroSection.button_url || 'shop')"
                                class="hero-cta-btn rounded-sm bg-[#cf7467] px-6 py-3.5 text-[0.65rem] font-bold uppercase tracking-[0.2em] text-white shadow-sm transition-colors duration-300 hover:bg-[#CF7467] md:px-8 md:text-[0.7rem]">
                                {{ heroSection.button_text || 'Shop Collection' }}
                            </Link>

                            <Link :href="pathFor('shop')"
                                class="hero-cta-btn group flex items-center gap-2 text-[0.65rem] font-bold uppercase tracking-[0.15em] text-[#cf7467] transition-opacity hover:opacity-70 md:text-[0.7rem]">
                                <span
                                    class="border-b-[1.5px] border-[#cf7467]/30 pb-1 transition-colors group-hover:border-[#cf7467]">
                                    Watch Film
                                </span>

                                <PlayCircle
                                    class="h-4 w-4 stroke-[1.5] transition-transform group-hover:scale-110 md:h-5 md:w-5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto mb-20 max-w-[75rem] px-4 md:mb-28 md:px-12">
                <div
                    class="home-arrivals-heading mb-8 flex flex-col items-start gap-4 md:mb-10 md:flex-row md:items-end md:justify-between">
                    <div class="max-w-xl">
                        <h2
                            class="mb-3 font-serif text-[clamp(2.2rem,4vw,2.75rem)] font-medium leading-tight text-[#CF7467]">
                            Curated <span class="font-light italic text-[#cf7467]">Arrivals</span>
                        </h2>

                        <p class="text-[0.9rem] leading-[1.75] text-[#6d5651] md:text-[0.95rem] md:leading-[1.8]">
                            Discover the latest silhouettes crafted for the season ahead, blending timeless elegance
                            with modern comfort.
                        </p>
                    </div>

                    <Link :href="pathFor('shop?sort=newest')"
                        class="hidden border-b-[1.5px] border-[#cf7467]/30 pb-1 text-[0.7rem] font-bold uppercase tracking-[0.15em] text-[#cf7467] transition-colors hover:border-[#cf7467] hover:text-[#CF7467] md:inline-flex">
                        View All
                    </Link>
                </div>

                <div class="grid grid-cols-12 gap-3 md:gap-6 lg:gap-10">
                    <div class="home-arrival-card col-span-7">
                        <Link :href="arrivalCards[0].href" class="group block">
                            <div class="relative mb-2 aspect-[4/3] overflow-hidden rounded-sm bg-[#f7ebe8] md:mb-4">
                                <img :src="arrivalCards[0].image" :alt="arrivalCards[0].title"
                                    class="h-full w-full object-cover transition-transform duration-1000 ease-out group-hover:scale-[1.03]" />
                            </div>

                            <div
                                class="mt-2 flex flex-col gap-1 px-0 md:mt-3 md:flex-row md:items-baseline md:justify-between">
                                <div class="min-w-0">
                                    <h3
                                        class="truncate font-serif text-[0.82rem] font-bold leading-tight text-[#1d1b1a] md:text-[1.15rem]">
                                        {{ arrivalCards[0].title }}
                                    </h3>

                                    <p
                                        class="mt-1 truncate text-[0.55rem] font-bold uppercase tracking-widest text-[#6d5651] md:text-[0.65rem]">
                                        {{ arrivalCards[0].material }}
                                    </p>
                                </div>

                                <p class="text-[0.72rem] font-extrabold text-[#1d1b1a] md:text-[0.9rem]">
                                    {{ arrivalCards[0].price }}
                                </p>
                            </div>
                        </Link>
                    </div>

                    <div class="home-arrival-card col-span-5 mt-7 md:mt-12">
                        <Link :href="arrivalCards[1].href" class="group block">
                            <div class="relative mb-2 aspect-[4/3] overflow-hidden rounded-sm bg-[#f7ebe8] md:mb-4">
                                <img :src="arrivalCards[1].image" :alt="arrivalCards[1].title"
                                    class="h-full w-full object-cover transition-transform duration-1000 ease-out group-hover:scale-[1.03]" />

                                <span
                                    class="absolute left-2 top-2 bg-white/90 px-2 py-1 text-[0.45rem] font-bold uppercase tracking-[0.16em] text-[#CF7467] shadow-sm backdrop-blur-sm md:left-4 md:top-4 md:px-3 md:py-1.5 md:text-[0.6rem] md:tracking-[0.2em]">
                                    Bestseller
                                </span>
                            </div>

                            <h3
                                class="truncate font-serif text-[0.78rem] font-bold leading-tight text-[#1d1b1a] md:text-[1.05rem]">
                                {{ arrivalCards[1].title }}
                            </h3>

                            <p class="mt-1 text-[0.7rem] font-bold text-[#6d5651] md:text-[0.85rem]">
                                {{ arrivalCards[1].price }}
                            </p>
                        </Link>
                    </div>

                    <div class="home-arrival-card col-span-5 mt-4 md:mt-8">
                        <Link :href="arrivalCards[2].href" class="group block">
                            <div class="relative mb-2 aspect-[4/3] overflow-hidden rounded-sm bg-[#f7ebe8] md:mb-4">
                                <img :src="arrivalCards[2].image" :alt="arrivalCards[2].title"
                                    class="h-full w-full object-cover transition-transform duration-1000 ease-out group-hover:scale-[1.03]" />
                            </div>

                            <h3
                                class="truncate font-serif text-[0.78rem] font-bold leading-tight text-[#1d1b1a] md:text-[1.05rem]">
                                {{ arrivalCards[2].title }}
                            </h3>

                            <p class="mt-1 text-[0.7rem] font-bold text-[#6d5651] md:text-[0.85rem]">
                                {{ arrivalCards[2].price }}
                            </p>
                        </Link>
                    </div>

                    <div class="home-arrival-card col-span-7">
                        <Link :href="arrivalCards[3].href" class="group block">
                            <div class="relative mb-2 aspect-[4/3] overflow-hidden rounded-sm bg-[#f7ebe8] md:mb-4">
                                <img :src="arrivalCards[3].image" :alt="arrivalCards[3].title"
                                    class="h-full w-full object-cover transition-transform duration-1000 ease-out group-hover:scale-[1.03]" />
                            </div>

                            <div
                                class="mt-2 flex flex-col gap-1 px-0 md:mt-3 md:flex-row md:items-baseline md:justify-between">
                                <div class="min-w-0">
                                    <h3
                                        class="truncate font-serif text-[0.82rem] font-bold leading-tight text-[#1d1b1a] md:text-[1.15rem]">
                                        {{ arrivalCards[3].title }}
                                    </h3>

                                    <p
                                        class="mt-1 truncate text-[0.55rem] font-bold uppercase tracking-widest text-[#6d5651] md:text-[0.65rem]">
                                        {{ arrivalCards[3].material }}
                                    </p>
                                </div>

                                <p class="text-[0.72rem] font-extrabold text-[#1d1b1a] md:text-[0.9rem]">
                                    {{ arrivalCards[3].price }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>

                <div class="mt-10 text-center md:hidden">
                    <Link :href="pathFor('shop?sort=newest')"
                        class="inline-flex border-b-[1.5px] border-[#cf7467]/30 pb-1 text-[0.7rem] font-bold uppercase tracking-[0.15em] text-[#cf7467] transition-colors hover:border-[#CF7467]">
                        View All Arrivals
                    </Link>
                </div>
            </section>

            <section class="home-editorial-section mx-auto mb-20 max-w-[75rem] px-6 md:mb-28 md:px-12">
                <div
                    class="flex flex-col overflow-hidden rounded-[1rem] border border-[#1d1b1a]/5 bg-white shadow-sm md:flex-row">
                    <div class="home-editorial-image aspect-[4/3] w-full md:h-[400px] md:w-1/2 md:aspect-auto">
                        <img :src="designImages.artisan" :alt="editorial.image_alt || 'Artisan touch'"
                            class="h-full w-full object-cover" />
                    </div>

                    <div
                        class="home-editorial-text flex w-full flex-col items-start justify-center p-8 md:w-1/2 md:p-12 lg:p-16">
                        <span class="mb-3 text-[0.65rem] font-bold uppercase tracking-[0.2em] text-[#cf7467]">
                            Heritage
                        </span>

                        <h2
                            class="mb-4 font-serif text-[clamp(2rem,3vw,2.5rem)] font-medium leading-tight text-[#CF7467]">
                            {{ editorial.title || "The Artisan's Touch" }}
                        </h2>

                        <p class="mb-8 text-[0.95rem] leading-[1.8] text-[#6d5651]">
                            {{ editorial.content || '' }}
                        </p>

                        <Link :href="normalizeUrl(editorial.button_url || 'our-story')"
                            class="border-b-[1.5px] border-[#CF7467]/30 pb-1 text-[0.7rem] font-bold uppercase tracking-[0.15em] text-[#CF7467] transition-colors hover:border-[#CF7467]">
                            {{ editorial.button_text || 'Discover More' }}
                        </Link>
                    </div>
                </div>
            </section>

            <section class="home-promo-section mx-auto mb-20 max-w-[75rem] px-6 md:mb-28 md:px-12">
                <div
                    class="rounded-xl border border-[#d8c2bc]/30 bg-[#fbf0ee] px-6 py-16 text-center shadow-sm md:py-20">
                    <div class="mx-auto flex max-w-2xl flex-col items-center">
                        <MessageCircle class="mb-5 h-8 w-8 stroke-[1.5] text-[#cf7467]" />

                        <h2
                            class="mb-4 font-serif text-[clamp(2rem,4vw,2.5rem)] font-medium leading-tight text-[#CF7467]">
                            {{ promo.title || 'Custom Creations' }}
                        </h2>

                        <p class="mb-8 max-w-xl text-[0.95rem] leading-[1.8] text-[#6d5651]">
                            {{ promo.content || '' }}
                        </p>

                        <a v-if="page.props.auth?.user"
                            :href="`https://wa.me/${settings.whatsapp_number?.replace(/[^0-9]/g, '') || '96100000000'}?text=Hi!%20I%20have%20a%20custom%20shoe%20design%20request.`"
                            target="_blank" rel="noreferrer"
                            class="inline-flex items-center gap-2 rounded-sm bg-[#B85C50] px-8 py-3.5 text-[0.75rem] font-bold uppercase tracking-[0.15em] text-white shadow-sm transition-colors hover:bg-[#6f3727]">
                            Message on WhatsApp
                            <ArrowRight class="h-4 w-4" />
                        </a>

                        <Link v-else href="/login"
                            class="inline-flex items-center gap-2 rounded-sm bg-[#B85C50] px-8 py-3.5 text-[0.75rem] font-bold uppercase tracking-[0.15em] text-white shadow-sm transition-colors hover:bg-[#6f3727]">
                            Login to Request
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </StorefrontLayout>
</template>