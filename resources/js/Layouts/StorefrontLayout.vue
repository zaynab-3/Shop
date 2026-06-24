<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import gsap from 'gsap';
import axios from 'axios';
import {
    ArrowLeft,
    ArrowRight,
    BookOpen,
    CheckCircle2,
    Facebook,
    Grid2X2,
    Heart,
    House,
    Instagram,
    Languages,
    Loader2,
    Mail,
    MessageCircle,
    Minus,
    Plus,
    Search,
    ShoppingBag,
    Trash2,
    Truck,
    UserRound,
    X,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { cart, formatCurrency } from '@/Stores/cart';
import { favorites } from '@/Stores/favorites';
import PhoneNumberField from '@/Components/PhoneNumberField.vue';

const props = defineProps({
    locale: { type: String, default: 'en' },
    isRtl: { type: Boolean, default: false },
    settings: { type: Object, default: () => ({}) },
    navPages: { type: Array, default: () => [] },
    footer: { type: Object, default: null },
    footerPages: { type: Array, default: null },
    introLogoHidden: { type: Boolean, default: false },
});

const page = usePage();

const menuOpen = ref(false);
const cartOpen = ref(false);
const favoritesOpen = ref(false);
const query = ref('');
const cookieVisible = ref(false);
const pendingNavKey = ref('');
const showSwipeTip = ref(false);

const drawerStep = ref('cart');

const form = reactive({
    name: '',
    phone: '',
    area: '',
    address: '',
    notes: '',
    website: '',
});

const errors = ref({});
const submitting = ref(false);
const submittedOrder = ref(null);

const checkoutItems = computed(() => cart.items.value);
const hasItems = computed(() => checkoutItems.value.length > 0);
const currency = computed(() => checkoutItems.value[0]?.currency || props.settings.default_currency || 'USD');
const subtotal = computed(() => cart.subtotal.value);
const deliveryFee = computed(() => 0);
const total = computed(() => subtotal.value + deliveryFee.value);
const defaultPhoneCode = computed(() => props.settings.default_country_code || '+961');

const effectiveFooterPages = computed(() => props.footerPages || page.props.footerPages || []);
const brandName = computed(() => props.settings.brand_name || 'SCARBINA');
const logoSrc = computed(() => props.settings?.logo_url || '/logo.svg');
const instagramHandle = computed(() => props.settings.instagram_handle || 'scarbina_shoes');

const swipeOffsets = ref({});
const activeSwipeKey = ref(null);
let startX = 0;
let startY = 0;
let isScrolling = null;

const SWIPE_THRESHOLD = -100;

function fieldError(key) {
    const value = errors.value[key];

    return Array.isArray(value) ? value[0] : value || '';
}

function orderItemsPayload() {
    return checkoutItems.value.map((item) => ({
        product_id: item.product_id,
        variant_id: item.variant_id,
        quantity: item.quantity,
    }));
}

async function submitOrder() {
    if (!hasItems.value || submitting.value) {
        return;
    }

    submitting.value = true;
    errors.value = {};
    submittedOrder.value = null;

    try {
        const { data } = await axios.post('/orders/whatsapp', {
            locale: props.locale,
            website: form.website,
            payment_method: 'cash_on_delivery',
            customer: {
                name: form.name,
                phone: form.phone,
                area: form.area,
                address: form.address,
                notes: form.notes,
            },
            items: orderItemsPayload(),
        });

        submittedOrder.value = data;
        cart.clearCart();
        resetSwipeRows();
        drawerStep.value = 'success';

        if (data.whatsapp_url) {
            window.setTimeout(() => {
                window.location.assign(data.whatsapp_url);
            }, 800);
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            errors.value = {
                general: 'We could not prepare the WhatsApp order. Please try again.',
            };
        }
    } finally {
        submitting.value = false;
    }
}

function resetSwipeRows() {
    swipeOffsets.value = {};
    activeSwipeKey.value = null;
    startX = 0;
    startY = 0;
    isScrolling = null;
}

function onTouchStart(event, key) {
    Object.keys(swipeOffsets.value).forEach((existingKey) => {
        if (existingKey !== key) {
            swipeOffsets.value[existingKey] = 0;
        }
    });

    activeSwipeKey.value = key;
    startX = event.touches[0].clientX;
    startY = event.touches[0].clientY;
    isScrolling = null;
}

function onTouchMove(event, key) {
    if (activeSwipeKey.value !== key) {
        return;
    }

    const currentX = event.touches[0].clientX;
    const currentY = event.touches[0].clientY;
    const diffX = currentX - startX;
    const diffY = currentY - startY;

    if (isScrolling === null) {
        if (Math.abs(diffX) < 8 && Math.abs(diffY) < 8) {
            return;
        }

        isScrolling = Math.abs(diffY) > Math.abs(diffX);
    }

    if (isScrolling) {
        return;
    }

    if (event.cancelable) {
        event.preventDefault();
    }

    if (diffX < 0) {
        swipeOffsets.value[key] = Math.max(diffX, -110);
    } else if (diffX > 0 && swipeOffsets.value[key] < 0) {
        swipeOffsets.value[key] = Math.min(0, diffX);
    }
}

function onTouchEnd(key) {
    if (activeSwipeKey.value !== key || isScrolling) {
        activeSwipeKey.value = null;
        isScrolling = null;
        return;
    }

    if (swipeOffsets.value[key] <= SWIPE_THRESHOLD) {
        cart.removeItem(key);
        delete swipeOffsets.value[key];

        if (showSwipeTip.value) {
            dismissSwipeTip();
        }
    } else {
        swipeOffsets.value[key] = 0;
    }

    activeSwipeKey.value = null;
    isScrolling = null;
}

function dismissSwipeTip() {
    showSwipeTip.value = false;
    window.localStorage.setItem('scarbina-cart-swipe-tip', '1');
}

function animateHeart(el) {
    if (!el) {
        return;
    }

    const target = el.querySelector('svg') || el;

    gsap.timeline()
        .to(target, { scale: 1.4, duration: 0.1, ease: 'power1.out' })
        .to(target, { scale: 1.1, duration: 0.1, ease: 'power1.inOut' })
        .to(target, { scale: 1.35, duration: 0.1, ease: 'power1.out' })
        .to(target, { scale: 1, duration: 0.18, ease: 'power2.inOut' });
}

function animateButton(el, type = 'cart') {
    if (!el) {
        return;
    }

    const target = el.querySelector('svg') || el;

    gsap.timeline()
        .to(target, { scale: 1.4, duration: 0.12, ease: 'back.out(1.7)' })
        .to(target, { rotate: type === 'cart' ? 15 : 0, duration: 0.08 })
        .to(target, { rotate: type === 'cart' ? -15 : 0, duration: 0.08 })
        .to(target, { rotate: 0, scale: 1, duration: 0.15, ease: 'power2.out' });
}

function unfavoriteWithAnim(event, id) {
    const button = event.currentTarget;
    const card = button.closest('.fav-drawer-item');

    animateHeart(button);

    if (card) {
        gsap.to(card, {
            x: 150,
            opacity: 0,
            height: 0,
            marginTop: 0,
            marginBottom: 0,
            paddingTop: 0,
            paddingBottom: 0,
            duration: 0.35,
            ease: 'power3.inOut',
            onComplete: () => {
                favorites.removeItem(id);
            },
        });
    } else {
        favorites.removeItem(id);
    }
}

function addToCartWithAnim(event, item) {
    const button = event.currentTarget;

    animateButton(button, 'cart');
    cart.addItem(item, null, 1);
}

function removeCartItemWithAnim(event, key) {
    const button = event.currentTarget;
    const card = button.closest('.cart-drawer-item');

    if (card) {
        gsap.to(card, {
            x: 150,
            opacity: 0,
            height: 0,
            marginTop: 0,
            marginBottom: 0,
            paddingTop: 0,
            paddingBottom: 0,
            duration: 0.35,
            ease: 'power3.inOut',
            onComplete: () => {
                cart.removeItem(key);
                delete swipeOffsets.value[key];
            },
        });
    } else {
        cart.removeItem(key);
        delete swipeOffsets.value[key];
    }
}

const removeRouteStartListener = router.on('start', () => {
    menuOpen.value = false;
    cartOpen.value = false;
    favoritesOpen.value = false;
    resetSwipeRows();
});

const removeRouteFinishListener = router.on('finish', () => {
    pendingNavKey.value = '';
});

const removeRouteErrorListener = router.on('error', () => {
    pendingNavKey.value = '';
});

const labels = computed(() => {
    const dictionary = {
        en: {
            home: 'Home',
            shop: 'Shop',
            cart: 'Cart',
            profile: 'Profile',
            favorites: 'Favorites',
            search: 'Search products',
            instagram: 'Instagram',
            accept: 'Accept',
            reject: 'Reject',
            cookie: 'Scarbina uses essential cookies for cart and order flow. Optional analytics stay off until accepted.',
            legal: 'Legal',
            whatsapp: 'WhatsApp',
            yourCart: 'Your Cart',
            emptyCart: 'Your cart is empty.',
            checkout: 'Checkout',
            subtotal: 'Subtotal',
            items: 'items',
        },
        fr: {
            home: 'Accueil',
            shop: 'Boutique',
            cart: 'Panier',
            profile: 'Profil',
            favorites: 'Favoris',
            search: 'Rechercher',
            instagram: 'Instagram',
            accept: 'Accepter',
            reject: 'Refuser',
            cookie: 'Scarbina utilise des cookies essentiels pour le panier et la commande. Les analyses optionnelles restent desactivees avant accord.',
            legal: 'Legal',
            whatsapp: 'WhatsApp',
            yourCart: 'Votre Panier',
            emptyCart: 'Votre panier est vide.',
            checkout: 'Paiement',
            subtotal: 'Sous-total',
            items: 'articles',
        },
        ar: {
            home: 'الرئيسية',
            shop: 'المتجر',
            cart: 'السلة',
            profile: 'الملف',
            favorites: 'المفضلة',
            search: 'ابحث',
            instagram: 'انستغرام',
            accept: 'موافق',
            reject: 'رفض',
            cookie: 'تستخدم Scarbina ملفات تعريف اساسية للسلة والطلب. التحليلات الاختيارية لا تعمل قبل الموافقة.',
            legal: 'قانوني',
            whatsapp: 'واتساب',
            yourCart: 'سلتك',
            emptyCart: 'سلتك فارغة.',
            checkout: 'إتمام الطلب',
            subtotal: 'المجموع',
            items: 'عناصر',
        },
    };

    return dictionary[props.locale] || dictionary.en;
});

function pathFor(path = '') {
    const trimmed = path.replace(/^\/+/, '');
    const prefix = props.locale === 'en' ? '' : `/${props.locale}`;

    return `${prefix}/${trimmed}`.replace(/\/$/, '') || '/';
}

function submitSearch() {
    router.get(pathFor('shop'), { q: query.value }, { preserveState: true });
    menuOpen.value = false;
}

function matchesRoute(...names) {
    return names.some((name) => route().current(name));
}

function isRouteActiveFor(key) {
    if (key === 'home') return matchesRoute('home', 'localized.home');
    if (key === 'shop') return matchesRoute('shop', 'localized.shop', 'products.show', 'localized.products.show');
    if (key === 'our-story') return matchesRoute('our-story', 'localized.our-story');
    if (key === 'cart') return matchesRoute('cart', 'localized.cart', 'checkout', 'localized.checkout');
    if (key === 'profile') return matchesRoute('profile.edit');

    return false;
}

function isNavActive(key) {
    return pendingNavKey.value ? pendingNavKey.value === key : isRouteActiveFor(key);
}

function markNavPending(key) {
    pendingNavKey.value = key;
}

function setCookieChoice(choice) {
    window.localStorage.setItem('scarbina-cookie-consent', choice);
    cookieVisible.value = false;
}

function canUseMotion() {
    return typeof window !== 'undefined'
        && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function immediateEnter(el, done) {
    gsap.set(el, { clearProps: 'all', autoAlpha: 1 });
    done();
}

function beforeBackdrop(el) {
    if (canUseMotion()) {
        gsap.set(el, { autoAlpha: 0 });
    }
}

function beforeMenu(el) {
    if (!canUseMotion()) {
        return;
    }

    const parts = el.querySelectorAll(':scope > *');

    gsap.set(el, {
        autoAlpha: 0,
        y: 24,
        scale: 0.972,
        filter: 'blur(10px)',
        transformOrigin: 'bottom center',
    });

    gsap.set(parts, { autoAlpha: 0, y: 14 });
}

function beforeCart(el) {
    if (!canUseMotion()) {
        return;
    }

    const header = el.querySelector('.cart-drawer-header');
    const empty = el.querySelector('.cart-drawer-empty');
    const rows = el.querySelectorAll('.cart-drawer-item, .cart-drawer-tip');
    const footer = el.querySelector('.cart-drawer-footer');
    const parts = [header, empty, ...rows, footer].filter(Boolean);

    gsap.set(el, {
        xPercent: 104,
        autoAlpha: 0,
        filter: 'blur(8px)',
        transformOrigin: 'right center',
    });

    gsap.set(parts, {
        autoAlpha: 0,
        x: 20,
    });
}

function enterBackdrop(el, done) {
    if (!canUseMotion()) {
        return immediateEnter(el, done);
    }

    gsap.killTweensOf(el);
    gsap.fromTo(
        el,
        { autoAlpha: 0 },
        {
            autoAlpha: 1,
            duration: 0.28,
            ease: 'power2.out',
            clearProps: 'opacity,visibility',
            onComplete: done,
        },
    );
}

function leaveBackdrop(el, done) {
    if (!canUseMotion()) {
        gsap.set(el, { autoAlpha: 0 });
        done();
        return;
    }

    gsap.killTweensOf(el);
    gsap.to(el, {
        autoAlpha: 0,
        duration: 0.22,
        ease: 'power2.inOut',
        onComplete: done,
    });
}

function menuEnter(el, done) {
    if (!canUseMotion()) {
        return immediateEnter(el, done);
    }

    const parts = el.querySelectorAll(':scope > *');

    gsap.killTweensOf([el, ...parts]);
    gsap.set(el, { transformOrigin: 'bottom center' });

    gsap.timeline({ onComplete: done })
        .fromTo(
            el,
            { autoAlpha: 0, y: 24, scale: 0.972, filter: 'blur(10px)' },
            { autoAlpha: 1, y: 0, scale: 1, filter: 'blur(0px)', duration: 0.44, ease: 'power4.out' },
            0,
        )
        .fromTo(
            parts,
            { autoAlpha: 0, y: 14 },
            { autoAlpha: 1, y: 0, duration: 0.38, ease: 'power3.out', stagger: 0.055 },
            0.12,
        );
}

function menuLeave(el, done) {
    if (!canUseMotion()) {
        gsap.set(el, { autoAlpha: 0 });
        done();
        return;
    }

    const parts = el.querySelectorAll(':scope > *');

    gsap.killTweensOf([el, ...parts]);

    gsap.timeline({ onComplete: done })
        .to(
            parts,
            {
                autoAlpha: 0,
                y: 8,
                duration: 0.14,
                ease: 'power2.in',
                stagger: { each: 0.025, from: 'end' },
            },
            0,
        )
        .to(
            el,
            {
                autoAlpha: 0,
                y: 18,
                scale: 0.982,
                filter: 'blur(8px)',
                duration: 0.24,
                ease: 'power3.in',
            },
            0.04,
        );
}

function cartEnter(el, done) {
    if (!canUseMotion()) {
        return immediateEnter(el, done);
    }

    const header = el.querySelector('.cart-drawer-header');
    const empty = el.querySelector('.cart-drawer-empty');
    const rows = el.querySelectorAll('.cart-drawer-item, .cart-drawer-tip');
    const footer = el.querySelector('.cart-drawer-footer');
    const parts = [header, empty, ...rows, footer].filter(Boolean);

    gsap.killTweensOf([el, ...parts]);
    gsap.set(el, { transformOrigin: 'right center' });

    gsap.timeline({ onComplete: done })
        .fromTo(
            el,
            { xPercent: 104, autoAlpha: 0, filter: 'blur(8px)' },
            { xPercent: 0, autoAlpha: 1, filter: 'blur(0px)', duration: 0.5, ease: 'power4.out' },
            0,
        )
        .fromTo(
            parts,
            { autoAlpha: 0, x: 20 },
            { autoAlpha: 1, x: 0, duration: 0.36, ease: 'power3.out', stagger: 0.045 },
            0.15,
        );
}

function cartLeave(el, done) {
    if (!canUseMotion()) {
        gsap.set(el, { autoAlpha: 0 });
        done();
        return;
    }

    const parts = el.querySelectorAll('.cart-drawer-header, .cart-drawer-empty, .cart-drawer-item, .cart-drawer-tip, .cart-drawer-footer');

    gsap.killTweensOf([el, ...parts]);

    gsap.timeline({ onComplete: done })
        .to(
            parts,
            {
                autoAlpha: 0,
                x: 10,
                duration: 0.16,
                ease: 'power2.in',
                stagger: { each: 0.02, from: 'end' },
            },
            0,
        )
        .to(
            el,
            {
                xPercent: 104,
                autoAlpha: 0.7,
                filter: 'blur(8px)',
                duration: 0.3,
                ease: 'power3.inOut',
            },
            0.02,
        );
}

function beforeFav(el) {
    if (!canUseMotion()) {
        return;
    }

    const header = el.querySelector('.fav-drawer-header');
    const empty = el.querySelector('.fav-drawer-empty');
    const rows = el.querySelectorAll('.fav-drawer-item');
    const parts = [header, empty, ...rows].filter(Boolean);

    gsap.set(el, {
        xPercent: 104,
        autoAlpha: 0.7,
        filter: 'blur(8px)',
        transformOrigin: 'right center',
    });

    gsap.set(parts, {
        autoAlpha: 0,
        x: 20,
    });
}

function favEnter(el, done) {
    if (!canUseMotion()) {
        return immediateEnter(el, done);
    }

    const header = el.querySelector('.fav-drawer-header');
    const empty = el.querySelector('.fav-drawer-empty');
    const rows = el.querySelectorAll('.fav-drawer-item');
    const parts = [header, empty, ...rows].filter(Boolean);

    gsap.killTweensOf([el, ...parts]);
    gsap.set(el, { transformOrigin: 'right center' });

    gsap.timeline({ onComplete: done })
        .fromTo(
            el,
            { xPercent: 104, autoAlpha: 0.7, filter: 'blur(8px)' },
            { xPercent: 0, autoAlpha: 1, filter: 'blur(0px)', duration: 0.5, ease: 'power4.out' },
            0,
        )
        .fromTo(
            parts,
            { autoAlpha: 0, x: 20 },
            { autoAlpha: 1, x: 0, duration: 0.36, ease: 'power3.out', stagger: 0.045 },
            0.15,
        );
}

function favLeave(el, done) {
    if (!canUseMotion()) {
        gsap.set(el, { autoAlpha: 0 });
        done();
        return;
    }

    const parts = el.querySelectorAll('.fav-drawer-header, .fav-drawer-empty, .fav-drawer-item');

    gsap.killTweensOf([el, ...parts]);

    gsap.timeline({ onComplete: done })
        .to(
            parts,
            {
                autoAlpha: 0,
                x: 10,
                duration: 0.16,
                ease: 'power2.in',
                stagger: { each: 0.02, from: 'end' },
            },
            0,
        )
        .to(
            el,
            {
                xPercent: 104,
                autoAlpha: 0.7,
                filter: 'blur(8px)',
                duration: 0.3,
                ease: 'power3.inOut',
            },
            0.02,
        );
}

watch([cartOpen, menuOpen, favoritesOpen], ([cartIsOpen, menuIsOpen, favoritesAreOpen]) => {
    const isFloatingUiOpen = cartIsOpen || menuIsOpen || favoritesAreOpen;

    document.body.style.overflow = isFloatingUiOpen ? 'hidden' : '';
    document.documentElement.classList.toggle('floating-ui-open', isFloatingUiOpen);

    if (cartIsOpen) {
        resetSwipeRows();

        if (
            cart.count.value > 0
            && typeof window !== 'undefined'
            && !window.localStorage.getItem('scarbina-cart-swipe-tip')
        ) {
            showSwipeTip.value = true;
        }

        if (cart.count.value === 0) {
            drawerStep.value = 'cart';
        }
    } else {
        resetSwipeRows();

        window.setTimeout(() => {
            drawerStep.value = 'cart';
        }, 300);
    }
});

onMounted(() => {
    cookieVisible.value = props.settings.cookie_banner_enabled === '1'
        && !window.localStorage.getItem('scarbina-cookie-consent');
});

onBeforeUnmount(() => {
    removeRouteStartListener();
    removeRouteFinishListener();
    removeRouteErrorListener();

    document.body.style.overflow = '';
    document.documentElement.classList.remove('floating-ui-open');

    resetSwipeRows();
});
</script>

<template>
    <div :dir="isRtl ? 'rtl' : 'ltr'"
        class="min-h-screen bg-[#F8F7F4] text-[#111111] font-sans antialiased selection:bg-[#111111] selection:text-[#F8F7F4] pb-[80px] md:pb-0 flex flex-col">
        <header
            class="fixed top-0 left-0 right-0 z-[60] bg-[#F8F7F4]/90 backdrop-blur-md border-b border-[#111111]/5 transition-all duration-200">
            <div
                class="max-w-[88rem] mx-auto flex md:grid md:grid-cols-[minmax(12rem,1fr)_auto_minmax(18rem,1fr)] items-center justify-between h-[4.8rem] px-4 md:px-8">
                <div class="w-10 md:hidden"></div>

                <Link :href="pathFor()"
                    class="absolute left-1/2 flex -translate-x-1/2 items-center transition-opacity hover:opacity-70 md:relative md:left-0 md:translate-x-0"
                    @click="markNavPending('home')">
                    <img id="site-header-logo" :src="logoSrc" :alt="brandName"
                        class="site-header-logo h-[3.15rem] w-auto max-w-[4.25rem] bg-transparent object-contain transition-opacity duration-150 md:h-[3.35rem] md:max-w-[4.5rem]"
                        :class="introLogoHidden ? 'opacity-0' : 'opacity-100'" />
                </Link>

                <nav class="hidden md:flex items-center justify-center gap-8 lg:gap-10">
                    <Link :href="pathFor()"
                        class="text-[0.75rem] font-extrabold uppercase tracking-[0.15em] transition-colors pb-1"
                        :class="isNavActive('home') ? 'text-[#111111] border-b-2 border-[#111111]' : 'text-[#111111]/70 hover:text-[#111111] border-b-2 border-transparent hover:border-[#111111]/30'"
                        @click="markNavPending('home')">
                        {{ labels.home }}
                    </Link>

                    <Link :href="pathFor('shop')"
                        class="text-[0.75rem] font-extrabold uppercase tracking-[0.15em] transition-colors pb-1"
                        :class="isNavActive('shop') ? 'text-[#111111] border-b-2 border-[#111111]' : 'text-[#111111]/70 hover:text-[#111111] border-b-2 border-transparent hover:border-[#111111]/30'"
                        @click="markNavPending('shop')">
                        {{ labels.shop }}
                    </Link>

                    <Link :href="pathFor('our-story')"
                        class="text-[0.75rem] font-extrabold uppercase tracking-[0.15em] transition-colors pb-1"
                        :class="isNavActive('our-story') ? 'text-[#111111] border-b-2 border-[#111111]' : 'text-[#111111]/70 hover:text-[#111111] border-b-2 border-transparent hover:border-[#111111]/30'"
                        @click="markNavPending('our-story')">
                        Our Story
                    </Link>

                    <Link v-for="pageItem in navPages" :key="pageItem.href" :href="pageItem.href"
                        class="text-[0.75rem] font-extrabold uppercase tracking-[0.15em] transition-colors pb-1 border-b-2 border-transparent text-[#111111]/70 hover:text-[#111111] hover:border-[#111111]/30">
                        {{ pageItem.title }}
                    </Link>
                </nav>

                <div class="flex items-center justify-end gap-2.5">
                    <button type="button"
                        class="md:hidden relative flex items-center justify-center w-10 h-10 border border-[#111111]/10 rounded-full bg-white hover:bg-[#111111] hover:text-[#F8F7F4] hover:border-[#111111] transition-all duration-200 text-[#111111]"
                        @click="favoritesOpen = true">
                        <Heart class="h-5 w-5 stroke-[1.5]" />

                        <span v-if="favorites.count.value"
                            class="absolute -top-1 -right-1 w-[1.15rem] h-[1.15rem] flex items-center justify-center bg-[#111111] text-[#F8F7F4] text-[0.65rem] font-bold rounded-full border-[1.5px] border-[#F8F7F4] leading-none transition-colors">
                            {{ favorites.count.value }}
                        </span>
                    </button>

                    <button type="button"
                        class="md:hidden relative flex items-center justify-center w-10 h-10 border border-[#111111]/10 rounded-full bg-white hover:bg-[#111111] hover:text-[#F8F7F4] hover:border-[#111111] transition-all duration-200 text-[#111111]"
                        @click="cartOpen = true">
                        <ShoppingBag class="h-5 w-5 stroke-[1.5]" />

                        <span v-if="cart.count.value"
                            class="absolute -top-1 -right-1 w-[1.15rem] h-[1.15rem] flex items-center justify-center bg-[#111111] text-[#F8F7F4] text-[0.65rem] font-bold rounded-full border-[1.5px] border-[#F8F7F4] leading-none transition-colors">
                            {{ cart.count.value }}
                        </span>
                    </button>

                    <div class="hidden md:flex items-center gap-3">
                        <form
                            class="flex items-center gap-2 border border-[#111111]/10 rounded-full bg-white px-4 h-10 w-48 lg:w-56 focus-within:border-[#111111]/35 focus-within:shadow-[0_0_0_2px_rgba(17,17,17,0.05)] transition-all duration-200"
                            @submit.prevent="submitSearch">
                            <Search class="h-4 w-4 text-[#111111]/60" />

                            <input v-model="query" :placeholder="labels.search"
                                class="w-full bg-transparent border-none outline-none text-[0.82rem] text-[#111111] placeholder:text-[#111111]/40" />
                        </form>

                        <Link href="/profile"
                            class="relative flex items-center justify-center w-10 h-10 border border-[#111111]/10 rounded-full bg-white hover:bg-[#111111] hover:text-[#F8F7F4] hover:border-[#111111] transition-all duration-200 text-[#111111]">
                            <UserRound class="h-5 w-5 stroke-[1.5]" />
                        </Link>

                        <button type="button"
                            class="relative flex items-center justify-center w-10 h-10 border border-[#111111]/10 rounded-full bg-white hover:bg-[#111111] hover:text-[#F8F7F4] hover:border-[#111111] transition-all duration-200 text-[#111111]"
                            @click="favoritesOpen = true">
                            <Heart class="h-5 w-5 stroke-[1.5]" />

                            <span v-if="favorites.count.value"
                                class="absolute -top-1 -right-1 w-[1.15rem] h-[1.15rem] flex items-center justify-center bg-[#111111] text-[#F8F7F4] text-[0.65rem] font-bold rounded-full border-[1.5px] border-[#F8F7F4] leading-none transition-colors">
                                {{ favorites.count.value }}
                            </span>
                        </button>

                        <button type="button"
                            class="relative flex items-center justify-center w-10 h-10 border border-[#111111]/10 rounded-full bg-white hover:bg-[#111111] hover:text-[#F8F7F4] hover:border-[#111111] transition-all duration-200 text-[#111111]"
                            @click="cartOpen = true">
                            <ShoppingBag class="h-5 w-5 stroke-[1.5]" />

                            <span v-if="cart.count.value"
                                class="absolute -top-1 -right-1 w-[1.15rem] h-[1.15rem] flex items-center justify-center bg-[#111111] text-[#F8F7F4] text-[0.65rem] font-bold rounded-full border-[1.5px] border-[#F8F7F4] leading-none transition-colors">
                                {{ cart.count.value }}
                            </span>
                        </button>

                        <div v-if="$page.props.settings?.language_switcher_enabled === '1'"
                            class="flex items-center border border-[#111111]/10 rounded-full bg-white/70 shadow-[0_10px_24px_rgba(17,17,17,0.04)] overflow-hidden h-[2.45rem]">
                            <Link href="/"
                                class="flex items-center px-3 h-full text-[0.7rem] font-bold transition-all duration-150"
                                :class="locale === 'en' ? 'bg-[#111111] text-[#F8F7F4]' : 'text-[#111111] hover:bg-[#111111]/10'">
                                EN
                            </Link>

                            <Link href="/fr"
                                class="flex items-center px-3 h-full text-[0.7rem] font-bold transition-all duration-150"
                                :class="locale === 'fr' ? 'bg-[#111111] text-[#F8F7F4]' : 'text-[#111111] hover:bg-[#111111]/10'">
                                FR
                            </Link>

                            <Link href="/ar"
                                class="flex items-center px-3 h-full text-[0.7rem] font-bold transition-all duration-150"
                                :class="locale === 'ar' ? 'bg-[#111111] text-[#F8F7F4]' : 'text-[#111111] hover:bg-[#111111]/10'">
                                AR
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 pt-[4.8rem]">
            <slot />
        </main>

        <footer class="bg-[#F8F7F4] pt-16 mt-auto">
            <div class="max-w-[88rem] mx-auto px-4 md:px-8 pt-10 pb-14 md:pb-10 border-t border-[#111111]/10">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 lg:gap-16">
                    <div class="flex flex-col gap-4">
                        <img :src="logoSrc" :alt="brandName"
                            class="h-20 w-auto max-w-[4rem] object-contain bg-transparent" />

                        <p class="text-[#111111]/70 font-serif text-[0.95rem] leading-relaxed max-w-[18rem]">
                            Exclusive feminine footwear. Build your cart and confirm through WhatsApp.
                        </p>

                        <div class="flex items-center gap-4 mt-2">
                            <a :href="`https://instagram.com/${instagramHandle}`" target="_blank" rel="noreferrer"
                                class="text-[#111111]/60 hover:text-[#111111] transition-colors" aria-label="Instagram">
                                <Instagram class="h-[1.2rem] w-[1.2rem] stroke-[1.5]" />
                            </a>

                            <a href="mailto:hello@scarbina.com"
                                class="text-[#111111]/60 hover:text-[#111111] transition-colors" aria-label="Email">
                                <Mail class="h-[1.2rem] w-[1.2rem] stroke-[1.5]" />
                            </a>

                            <a href="https://facebook.com" target="_blank" rel="noreferrer"
                                class="text-[#111111]/60 hover:text-[#111111] transition-colors" aria-label="Facebook">
                                <Facebook class="h-[1.2rem] w-[1.2rem] stroke-[1.5]" />
                            </a>

                            <a href="https://tiktok.com" target="_blank" rel="noreferrer"
                                class="text-[#111111]/60 hover:text-[#111111] transition-colors" aria-label="TikTok">
                                <svg class="h-[1.1rem] w-[1.1rem] fill-current" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M12.525.02c1.31-.02 2.61-.01 3.91-.04.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[0.7rem] font-extrabold tracking-[0.15em] uppercase text-[#111111] mb-6">
                            Quick Links
                        </h4>

                        <div class="flex flex-col gap-3">
                            <Link :href="pathFor()"
                                class="text-[#111111]/70 hover:text-[#111111] text-[0.85rem] transition-colors w-fit">
                                {{ labels.home }}
                            </Link>

                            <Link :href="pathFor('shop')"
                                class="text-[#111111]/70 hover:text-[#111111] text-[0.85rem] transition-colors w-fit">
                                {{ labels.shop }}
                            </Link>

                            <Link :href="pathFor('our-story')"
                                class="text-[#111111]/70 hover:text-[#111111] text-[0.85rem] transition-colors w-fit">
                                Our Story
                            </Link>

                            <Link href="/profile"
                                class="text-[#111111]/70 hover:text-[#111111] text-[0.85rem] transition-colors w-fit">
                                {{ labels.profile }}
                            </Link>

                            <Link :href="pathFor('pages/privacy-policy')"
                                class="text-[#111111]/70 hover:text-[#111111] text-[0.85rem] transition-colors w-fit">
                                Privacy Policy
                            </Link>

                            <Link :href="pathFor('pages/exchange-policy')"
                                class="text-[#111111]/70 hover:text-[#111111] text-[0.85rem] transition-colors w-fit">
                                Exchange Policy
                            </Link>

                            <Link :href="pathFor('pages/terms-and-conditions')"
                                class="text-[#111111]/70 hover:text-[#111111] text-[0.85rem] transition-colors w-fit">
                                Terms & Conditions
                            </Link>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[0.7rem] font-extrabold tracking-[0.15em] uppercase text-[#111111] mb-6">
                            Request a Design
                        </h4>

                        <p class="text-[0.85rem] text-[#111111]/70 mb-5 leading-[1.65]">
                            Have a dream pair of shoes? Send us an image of the design you want to recreate, and we will
                            bring it to life.
                        </p>

                        <a :href="`https://wa.me/${settings.whatsapp_number?.replace(/[^0-9]/g, '') || '1234567890'}?text=Hi!%20I%20have%20a%20custom%20shoe%20design%20request.%20Attached%20is%20the%20image%20of%20the%20shoe%20I%20want%20to%20recreate.`"
                            target="_blank" rel="noreferrer"
                            class="inline-flex items-center gap-2 text-[0.75rem] font-extrabold uppercase tracking-widest text-[#111111] border-b-[2px] border-[#111111] pb-1 hover:text-[#111111]/60 hover:border-[#111111]/60 transition-colors mt-2 w-fit">
                            Message on WhatsApp
                            <ArrowRight class="h-4 w-4" />
                        </a>
                    </div>
                </div>

                <div
                    class="flex flex-col md:flex-row items-center justify-between gap-6 border-t border-[#111111]/10 mt-16 pt-6">
                    <p class="text-[0.75rem] text-[#111111]/60">
                        © 2024 {{ brandName }}. All rights reserved.
                    </p>

                    <div class="flex flex-wrap justify-center gap-6">
                        <Link :href="pathFor('pages/sustainability')"
                            class="text-[0.75rem] text-[#111111]/60 hover:text-[#111111] transition-colors">
                            Sustainability
                        </Link>

                        <Link v-for="pageLink in effectiveFooterPages" :key="pageLink.href" :href="pageLink.href"
                            class="text-[0.75rem] text-[#111111]/60 hover:text-[#111111] transition-colors">
                            {{ pageLink.title }}
                        </Link>
                    </div>
                </div>
            </div>
        </footer>

        <div v-if="cookieVisible"
            class="fixed bottom-28 md:bottom-6 left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] max-w-2xl bg-[#F8F7F4] border border-[#111111]/10 rounded-2xl p-4 shadow-[0_18px_60px_rgba(0,0,0,0.18)] z-[44] flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-[0.85rem] text-[#111111]/70 leading-relaxed text-center md:text-left">
                {{ labels.cookie }}
            </p>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <button
                    class="flex-1 md:flex-none bg-[#111111] text-[#F8F7F4] px-5 py-3 rounded-lg text-[0.72rem] font-bold uppercase tracking-widest hover:-translate-y-[1px] transition-transform"
                    type="button" @click="setCookieChoice('accepted')">
                    {{ labels.accept }}
                </button>

                <button
                    class="flex-1 md:flex-none border border-[#111111]/20 text-[#111111] px-5 py-3 rounded-lg text-[0.72rem] font-bold uppercase tracking-widest hover:-translate-y-[1px] transition-transform"
                    type="button" @click="setCookieChoice('rejected')">
                    {{ labels.reject }}
                </button>
            </div>
        </div>

        <nav
            class="fixed bottom-4 md:hidden left-1/2 -translate-x-1/2 z-[76] flex items-center justify-between w-[calc(100%-1.5rem)] max-w-sm bg-[#F8F7F4]/95 backdrop-blur-md border border-[#111111]/10 rounded-[1rem] p-1.5 shadow-[0_18px_45px_rgba(0,0,0,0.18)]">
            <Link :href="pathFor()"
                class="flex flex-col items-center justify-center h-[3.25rem] flex-1 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all duration-200"
                :class="isNavActive('home') ? 'bg-[#111111] text-[#F8F7F4] shadow-md -translate-y-[1px]' : 'text-[#111111]/60'"
                @click="markNavPending('home')">
                <House class="h-5 w-5 mb-0.5" :class="{ 'fill-current': isNavActive('home') }" />
                <span>{{ labels.home }}</span>
            </Link>

            <Link :href="pathFor('shop')"
                class="flex flex-col items-center justify-center h-[3.25rem] flex-1 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all duration-200"
                :class="isNavActive('shop') ? 'bg-[#111111] text-[#F8F7F4] shadow-md -translate-y-[1px]' : 'text-[#111111]/60'"
                @click="markNavPending('shop')">
                <Grid2X2 class="h-5 w-5 mb-0.5" :class="{ 'fill-current': isNavActive('shop') }" />
                <span>{{ labels.shop }}</span>
            </Link>

            <Link :href="pathFor('our-story')"
                class="flex flex-col items-center justify-center h-[3.25rem] flex-1 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all duration-200"
                :class="isNavActive('our-story') ? 'bg-[#111111] text-[#F8F7F4] shadow-md -translate-y-[1px]' : 'text-[#111111]/60'"
                @click="markNavPending('our-story')">
                <BookOpen class="h-5 w-5 mb-0.5" :class="{ 'fill-current': isNavActive('our-story') }" />
                <span>Story</span>
            </Link>

            <Link href="/profile"
                class="flex flex-col items-center justify-center h-[3.25rem] flex-1 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all duration-200"
                :class="isNavActive('profile') ? 'bg-[#111111] text-[#F8F7F4] shadow-md -translate-y-[1px]' : 'text-[#111111]/60'"
                @click="markNavPending('profile')">
                <UserRound class="h-5 w-5 mb-0.5" :class="{ 'fill-current': isNavActive('profile') }" />
                <span>{{ labels.profile }}</span>
            </Link>
        </nav>

        <Transition :css="false" @before-enter="beforeBackdrop" @enter="enterBackdrop" @leave="leaveBackdrop">
            <div v-if="menuOpen" class="fixed inset-0 z-[49] bg-[#111111]/40 backdrop-blur-sm md:hidden"
                @click="menuOpen = false" />
        </Transition>

        <Transition :css="false" @before-enter="beforeMenu" @enter="menuEnter" @leave="menuLeave">
            <section v-if="menuOpen"
                class="fixed left-3 right-3 bottom-[5.5rem] z-[72] max-h-[70vh] overflow-y-auto bg-[#F8F7F4] border border-[#111111]/10 rounded-xl p-4 shadow-[0_24px_70px_rgba(0,0,0,0.22)] md:hidden"
                aria-label="Mobile menu">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <p class="text-[0.66rem] font-bold uppercase tracking-widest text-[#111111]/40">
                            {{ brandName }}
                        </p>

                        <h2 class="font-serif text-[1.8rem] leading-tight text-[#111111] mt-1">
                            Navigate
                        </h2>
                    </div>

                    <button
                        class="flex items-center justify-center w-10 h-10 border border-[#111111]/10 rounded-full bg-white/70 text-[#111111]"
                        type="button" aria-label="Close menu" @click="menuOpen = false">
                        <X class="h-5 w-5 stroke-[1.5]" />
                    </button>
                </div>

                <form class="flex items-center gap-3 border border-[#111111]/10 rounded-lg bg-white p-3 mb-6"
                    @submit.prevent="submitSearch">
                    <Search class="h-4 w-4 text-[#111111]/60" />

                    <input v-model="query" :placeholder="labels.search"
                        class="w-full bg-transparent border-none outline-none text-[0.82rem] text-[#111111] placeholder:text-[#111111]/40" />
                </form>

                <div class="grid gap-2 mb-6">
                    <Link :href="pathFor('our-story')"
                        class="flex items-center border border-[#111111]/10 rounded-lg bg-white/60 p-3 text-[0.82rem] font-extrabold uppercase tracking-widest text-[#111111]"
                        @click="menuOpen = false">
                        Our Story
                    </Link>

                    <Link v-for="pageItem in navPages" :key="pageItem.href" :href="pageItem.href"
                        class="flex items-center border border-[#111111]/10 rounded-lg bg-white/60 p-3 text-[0.82rem] font-extrabold uppercase tracking-widest text-[#111111]"
                        @click="menuOpen = false">
                        {{ pageItem.title }}
                    </Link>

                    <a :href="`https://instagram.com/${instagramHandle}`" target="_blank" rel="noreferrer"
                        class="flex items-center gap-2 border border-[#111111]/10 rounded-lg bg-white/60 p-3 text-[0.82rem] font-extrabold uppercase tracking-widest text-[#111111]">
                        <Instagram class="h-4 w-4" />
                        @{{ instagramHandle }}
                    </a>
                </div>

                <div v-if="$page.props.settings?.language_switcher_enabled === '1'"
                    class="flex items-center justify-between border-t border-[#111111]/10 pt-4 mt-2">
                    <Languages class="h-5 w-5 text-[#111111]/40" />

                    <div class="flex items-center gap-4 text-[0.7rem] font-bold tracking-widest">
                        <Link href="/" :class="locale === 'en' ? 'text-[#111111]' : 'text-[#111111]/50'">
                            EN
                        </Link>

                        <Link href="/fr" :class="locale === 'fr' ? 'text-[#111111]' : 'text-[#111111]/50'">
                            FR
                        </Link>

                        <Link href="/ar" :class="locale === 'ar' ? 'text-[#111111]' : 'text-[#111111]/50'">
                            AR
                        </Link>
                    </div>
                </div>
            </section>
        </Transition>

        <Transition :css="false" @before-enter="beforeBackdrop" @enter="enterBackdrop" @leave="leaveBackdrop">
            <div v-if="cartOpen" class="fixed inset-0 z-[65] bg-[#111111]/40 backdrop-blur-sm"
                @click="cartOpen = false" />
        </Transition>

        <Transition :css="false" @before-enter="beforeCart" @enter="cartEnter" @leave="cartLeave">
            <div v-if="cartOpen"
                class="cart-drawer-panel fixed top-2 right-2 bottom-[5.4rem] md:top-0 md:right-0 md:bottom-0 w-[calc(100vw-1rem)] md:w-full max-w-[29rem] bg-[#F8F7F4] md:rounded-none rounded-[0.85rem] shadow-[-18px_0_70px_rgba(0,0,0,0.24)] z-[70] flex flex-col border md:border-y-0 md:border-r-0 border-[#111111]/10 overflow-hidden"
                :dir="isRtl ? 'rtl' : 'ltr'">
                <div
                    class="drawer-animate-part cart-drawer-header flex items-center justify-between p-5 border-b border-[#111111]/10">
                    <div class="flex items-center gap-3">
                        <button v-if="drawerStep === 'checkout'"
                            class="p-1 -ml-1 hover:bg-[#111111]/5 rounded-full transition-colors text-[#111111]"
                            aria-label="Back to cart" @click="drawerStep = 'cart'">
                            <ArrowLeft class="h-5 w-5" />
                        </button>

                        <div>
                            <h2 class="text-[0.8rem] font-bold uppercase tracking-[0.14em] text-[#111111]">
                                {{ drawerStep === 'checkout' ? 'Checkout' : (drawerStep === 'success' ? 'Order Sent' :
                                    labels.yourCart) }}
                            </h2>

                            <p v-if="cart.count.value && drawerStep === 'cart'"
                                class="mt-1 text-[0.7rem] text-[#111111]/60 font-bold tracking-widest uppercase">
                                {{ cart.count.value }} {{ labels.items }}
                            </p>
                        </div>
                    </div>

                    <button
                        class="flex items-center justify-center w-10 h-10 border border-[#111111]/10 rounded-full bg-white/70 hover:bg-white text-[#111111] transition-all"
                        type="button" aria-label="Close" @click="cartOpen = false">
                        <X class="h-5 w-5 stroke-[1.5]" />
                    </button>
                </div>

                <div v-if="!cart.count.value && drawerStep !== 'success'"
                    class="drawer-animate-part cart-drawer-empty flex-1 flex flex-col items-center justify-center p-8 text-center">
                    <ShoppingBag class="h-12 w-12 text-[#111111]/20 mb-4 stroke-[1.5]" />

                    <p class="text-[0.88rem] text-[#111111]/60">
                        {{ labels.emptyCart }}
                    </p>

                    <button
                        class="mt-6 bg-[#111111] text-[#F8F7F4] px-6 py-3 rounded-lg text-[0.72rem] font-bold uppercase tracking-widest hover:-translate-y-[1px] transition-transform flex items-center gap-2"
                        type="button" @click="cartOpen = false; markNavPending('shop'); router.visit(pathFor('shop'))">
                        {{ labels.shop }}
                        <ArrowRight class="h-4 w-4" />
                    </button>
                </div>

                <div v-else class="cart-drawer-stage flex-1 flex flex-col overflow-hidden relative">
                    <Transition name="slide-step" mode="out-in">
                        <div v-if="drawerStep === 'cart'" key="cart"
                            class="flex-1 flex flex-col h-full overflow-hidden absolute inset-0">
                            <div
                                class="cart-drawer-scroll flex-1 overflow-y-auto overflow-x-hidden p-4 flex flex-col gap-3">
                                <div v-if="showSwipeTip"
                                    class="drawer-animate-part cart-drawer-tip bg-[#111111] text-[#F8F7F4] p-3 rounded-lg flex items-center justify-between shadow-sm mb-1 md:hidden">
                                    <span class="text-[0.75rem] font-medium opacity-90 tracking-wide">
                                        💡 Swipe items left to remove them from your cart
                                    </span>

                                    <button class="p-1 hover:bg-[#F8F7F4]/20 rounded-full transition-colors shrink-0"
                                        @click="dismissSwipeTip">
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>

                                <div v-for="item in cart.items.value" :key="item.key"
                                    class="cart-drawer-item relative overflow-hidden rounded-[0.85rem] w-full max-w-full">
                                    <div
                                        class="absolute right-[4px] top-[4px] bottom-[4px] z-0 flex w-[90px] items-center justify-center rounded-[0.72rem] bg-[#E8A39A] text-white">
                                        <Trash2 class="h-6 w-6 stroke-[1.8]" />
                                    </div>

                                    <div class="cart-drawer-card relative z-10 flex w-full min-w-0 items-start gap-3 rounded-[0.85rem] border border-[#111111]/10 bg-white p-3 min-h-[8.25rem] shadow-sm ease-out"
                                        style="touch-action: pan-y;" :style="{
                                            transform: `translate3d(${swipeOffsets[item.key] || 0}px, 0, 0)`,
                                            transitionProperty: 'transform',
                                            transitionDuration: activeSwipeKey === item.key ? '0ms' : '200ms',
                                        }" @touchstart="onTouchStart($event, item.key)"
                                        @touchmove="onTouchMove($event, item.key)" @touchend="onTouchEnd(item.key)"
                                        @touchcancel="onTouchEnd(item.key)">
                                        <Link :href="item.href || pathFor(`products/${item.slug}`)"
                                            class="h-[5.75rem] w-[4.75rem] shrink-0 overflow-hidden rounded-[0.55rem] bg-[#D8CEC4] pointer-events-none md:pointer-events-auto"
                                            @click="cartOpen = false; markNavPending('shop')">
                                            <img v-if="item.image" :src="item.image" :alt="item.name"
                                                class="h-full w-full object-cover" />

                                            <div v-else class="h-full w-full bg-[#D8CEC4]" />
                                        </Link>

                                        <div class="min-w-0 flex-1 pt-1">
                                            <div class="flex items-start justify-between gap-3">
                                                <Link :href="item.href || pathFor(`products/${item.slug}`)"
                                                    class="block min-w-0 truncate font-serif text-[0.95rem] font-bold leading-tight text-[#111111] transition-opacity hover:opacity-70"
                                                    @click="cartOpen = false; markNavPending('shop')">
                                                    {{ item.name }}
                                                </Link>

                                                <button
                                                    class="hidden shrink-0 p-1 text-[#111111]/40 transition-colors hover:text-[#111111] md:block"
                                                    type="button" aria-label="Remove item"
                                                    @click="removeCartItemWithAnim($event, item.key)">
                                                    <Trash2 class="h-4 w-4 stroke-[1.5]" />
                                                </button>
                                            </div>

                                            <p v-if="item.variant?.size || item.variant?.color"
                                                class="mt-1 text-[0.65rem] font-bold uppercase tracking-widest text-[#111111]/50">
                                                {{ [item.variant.size, item.variant.color].filter(Boolean).join(' / ')
                                                }}
                                            </p>

                                            <p class="mt-1.5 text-[0.85rem] font-extrabold text-[#111111]">
                                                {{ formatCurrency(item.price, item.currency) }}
                                            </p>

                                            <div class="mt-4 flex items-center">
                                                <div class="flex items-center gap-2">
                                                    <button
                                                        class="flex h-7 w-7 items-center justify-center rounded border border-[#111111]/15 bg-[#F8F7F4] transition-colors hover:bg-[#111111] hover:text-white disabled:opacity-40 disabled:hover:bg-[#F8F7F4] disabled:hover:text-black"
                                                        type="button" :disabled="item.quantity <= 1"
                                                        @click="cart.updateQuantity(item.key, item.quantity - 1)">
                                                        <Minus class="h-3 w-3 stroke-[2]" />
                                                    </button>

                                                    <span
                                                        class="w-5 text-center text-[0.8rem] font-bold text-[#111111]">
                                                        {{ item.quantity }}
                                                    </span>

                                                    <button
                                                        class="flex h-7 w-7 items-center justify-center rounded border border-[#111111]/15 bg-[#F8F7F4] transition-colors hover:bg-[#111111] hover:text-white disabled:opacity-40 disabled:hover:bg-[#F8F7F4] disabled:hover:text-black"
                                                        type="button" :disabled="item.quantity >= item.stock_quantity"
                                                        @click="cart.updateQuantity(item.key, item.quantity + 1)">
                                                        <Plus class="h-3 w-3 stroke-[2]" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="cart-drawer-footer p-5 border-t border-[#111111]/10 bg-[#F8F7F4]/90 backdrop-blur-md">
                                <div class="flex justify-between text-[0.9rem] font-bold text-[#111111] mb-4">
                                    <span class="text-[#111111]/70 uppercase tracking-widest text-[0.7rem]">
                                        {{ labels.subtotal }}
                                    </span>

                                    <span>
                                        {{ formatCurrency(cart.subtotal.value, cart.items.value[0]?.currency) }}
                                    </span>
                                </div>

                                <button type="button"
                                    class="w-full flex items-center justify-center gap-2 bg-[#111111] text-[#F8F7F4] py-3.5 rounded-lg text-[0.72rem] font-extrabold uppercase tracking-widest hover:-translate-y-[1px] shadow-md transition-all"
                                    @click="drawerStep = 'checkout'">
                                    {{ labels.checkout }}
                                    <ArrowRight class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div v-else-if="drawerStep === 'checkout'" key="checkout"
                            class="flex-1 flex flex-col h-full overflow-hidden absolute inset-0 bg-[#F8F7F4]">
                            <form class="flex-1 flex flex-col h-full overflow-hidden" @submit.prevent="submitOrder">
                                <div class="flex-1 overflow-y-auto overflow-x-hidden p-5 space-y-6">
                                    <div v-if="fieldError('general')"
                                        class="border border-red-200 bg-red-50 px-4 py-3 text-[0.8rem] font-bold text-red-700 rounded-md"
                                        role="alert">
                                        {{ fieldError('general') }}
                                    </div>

                                    <div class="space-y-4">
                                        <h3 class="font-serif text-[1.4rem] font-medium text-[#111111]">
                                            Delivery Details
                                        </h3>

                                        <div class="relative">
                                            <input id="chk_name" v-model="form.name" type="text" placeholder=" "
                                                class="peer w-full bg-white border border-[#111111]/10 focus:border-[#111111] rounded-lg px-4 pt-5 pb-2 text-[0.9rem] text-[#111111] outline-none transition-colors"
                                                :class="{ 'border-red-400': fieldError('customer.name') }" />

                                            <label for="chk_name"
                                                class="absolute left-4 top-1.5 text-[0.65rem] font-bold uppercase tracking-widest text-[#111111]/50 pointer-events-none transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-[0.8rem] peer-focus:top-1.5 peer-focus:text-[0.65rem] peer-focus:text-[#111111]">
                                                Full Name
                                            </label>

                                            <span v-if="fieldError('customer.name')"
                                                class="mt-1 block text-[0.7rem] font-bold text-red-600">
                                                {{ fieldError('customer.name') }}
                                            </span>
                                        </div>

                                        <PhoneNumberField id="chk_phone" v-model="form.phone" label="Phone Number"
                                            :default-country-code="defaultPhoneCode"
                                            :error="fieldError('customer.phone')" required
                                            class="bg-white rounded-lg border-[#111111]/10 focus-within:border-[#111111]" />

                                        <div class="relative">
                                            <input id="chk_area" v-model="form.area" type="text" placeholder=" "
                                                class="peer w-full bg-white border border-[#111111]/10 focus:border-[#111111] rounded-lg px-4 pt-5 pb-2 text-[0.9rem] text-[#111111] outline-none transition-colors"
                                                :class="{ 'border-red-400': fieldError('customer.area') }" />

                                            <label for="chk_area"
                                                class="absolute left-4 top-1.5 text-[0.65rem] font-bold uppercase tracking-widest text-[#111111]/50 pointer-events-none transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-[0.8rem] peer-focus:top-1.5 peer-focus:text-[0.65rem] peer-focus:text-[#111111]">
                                                Area / City
                                            </label>

                                            <span v-if="fieldError('customer.area')"
                                                class="mt-1 block text-[0.7rem] font-bold text-red-600">
                                                {{ fieldError('customer.area') }}
                                            </span>
                                        </div>

                                        <div class="relative">
                                            <textarea id="chk_addr" v-model="form.address" rows="2" placeholder=" "
                                                class="peer w-full resize-none bg-white border border-[#111111]/10 focus:border-[#111111] rounded-lg px-4 pt-6 pb-2 text-[0.9rem] text-[#111111] outline-none transition-colors"
                                                :class="{ 'border-red-400': fieldError('customer.address') }"></textarea>

                                            <label for="chk_addr"
                                                class="absolute left-4 top-2 text-[0.65rem] font-bold uppercase tracking-widest text-[#111111]/50 pointer-events-none transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-[0.8rem] peer-focus:top-2 peer-focus:text-[0.65rem] peer-focus:text-[#111111]">
                                                Address / Pickup Note
                                            </label>

                                            <span v-if="fieldError('customer.address')"
                                                class="mt-1 block text-[0.7rem] font-bold text-red-600">
                                                {{ fieldError('customer.address') }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        class="bg-white p-4 rounded-lg flex items-start gap-3 border border-[#111111]/5">
                                        <Truck class="h-5 w-5 shrink-0 text-[#111111] mt-0.5" />

                                        <div>
                                            <p class="text-[0.85rem] font-extrabold text-[#111111]">
                                                Cash on Delivery
                                            </p>

                                            <p class="text-[0.75rem] text-[#111111]/60 mt-0.5 leading-snug">
                                                Pay securely when your order arrives. Standard delivery rates apply.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-5 border-t border-[#111111]/10 bg-white">
                                    <div class="flex justify-between items-end mb-4">
                                        <div>
                                            <span
                                                class="block text-[0.65rem] font-extrabold uppercase tracking-widest text-[#111111]/60 mb-1">
                                                Total (COD)
                                            </span>

                                            <span
                                                class="block text-[1.1rem] font-extrabold text-[#111111] leading-none">
                                                {{ formatCurrency(total, currency) }}
                                            </span>
                                        </div>

                                        <span class="text-[0.7rem] font-bold text-[#111111]/50">
                                            {{ cart.count.value }} Items
                                        </span>
                                    </div>

                                    <button type="submit"
                                        class="w-full flex items-center justify-center gap-2 bg-[#111111] text-[#F8F7F4] py-3.5 rounded-lg text-[0.72rem] font-extrabold uppercase tracking-widest hover:-translate-y-[1px] shadow-md transition-all disabled:opacity-70 disabled:hover:translate-y-0"
                                        :disabled="submitting">
                                        <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />

                                        <MessageCircle v-else class="h-4 w-4" />

                                        {{ submitting ? 'Preparing...' : 'Confirm via WhatsApp' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div v-else-if="drawerStep === 'success'" key="success"
                            class="flex-1 flex flex-col items-center justify-center p-8 text-center absolute inset-0 bg-[#F8F7F4]">
                            <CheckCircle2 class="h-16 w-16 text-green-500 mb-4 stroke-[1.5]" />

                            <h3 class="font-serif text-[1.8rem] font-medium text-[#111111] mb-2">
                                Order Ready
                            </h3>

                            <p class="text-[0.9rem] text-[#111111]/60 leading-relaxed mb-8">
                                Your order
                                <strong class="text-[#111111]">
                                    #{{ submittedOrder?.order_number }}
                                </strong>
                                has been prepared. Please click below to send it to our team via WhatsApp.
                            </p>

                            <a :href="submittedOrder?.whatsapp_url" target="_blank" rel="noopener noreferrer"
                                class="w-full flex items-center justify-center gap-2 bg-[#25D366] text-white py-3.5 rounded-lg text-[0.72rem] font-extrabold uppercase tracking-widest shadow-md transition-all hover:bg-[#1DA851] hover:-translate-y-[1px]">
                                <MessageCircle class="h-4 w-4" />
                                Open WhatsApp
                            </a>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>

        <Transition :css="false" @before-enter="beforeBackdrop" @enter="enterBackdrop" @leave="leaveBackdrop">
            <div v-if="favoritesOpen" class="fixed inset-0 z-[65] bg-[#111111]/40 backdrop-blur-sm"
                @click="favoritesOpen = false" />
        </Transition>

        <Transition :css="false" @before-enter="beforeFav" @enter="favEnter" @leave="favLeave">
            <div v-if="favoritesOpen"
                class="fixed top-2 right-2 bottom-[5.4rem] md:top-0 md:right-0 md:bottom-0 w-[calc(100vw-1rem)] md:w-full max-w-[29rem] bg-[#F8F7F4] md:rounded-none rounded-[0.85rem] shadow-[-18px_0_70px_rgba(0,0,0,0.24)] z-[70] flex flex-col border md:border-y-0 md:border-r-0 border-[#111111]/10 overflow-hidden"
                :dir="isRtl ? 'rtl' : 'ltr'">
                <div class="fav-drawer-header flex items-center justify-between p-5 border-b border-[#111111]/10">
                    <div>
                        <h2 class="text-[0.8rem] font-bold uppercase tracking-[0.14em] text-[#111111]">
                            Your Favorites
                        </h2>

                        <p v-if="favorites.count.value"
                            class="mt-1 text-[0.7rem] text-[#111111]/60 font-bold tracking-widest uppercase">
                            {{ favorites.count.value }} {{ labels.items }}
                        </p>
                    </div>

                    <button
                        class="flex items-center justify-center w-10 h-10 border border-[#111111]/10 rounded-full bg-white/70 hover:bg-white text-[#111111] transition-all"
                        type="button" aria-label="Close favorites" @click="favoritesOpen = false">
                        <X class="h-5 w-5 stroke-[1.5]" />
                    </button>
                </div>

                <div v-if="!favorites.count.value"
                    class="fav-drawer-empty flex-1 flex flex-col items-center justify-center p-8 text-center">
                    <Heart class="h-12 w-12 text-[#111111]/20 mb-4 stroke-[1.5]" />

                    <p class="text-[0.88rem] text-[#111111]/60">
                        Your favorites list is empty.
                    </p>

                    <button
                        class="mt-6 bg-[#111111] text-[#F8F7F4] px-6 py-3 rounded-lg text-[0.72rem] font-bold uppercase tracking-widest hover:-translate-y-[1px] transition-transform flex items-center gap-2"
                        type="button"
                        @click="favoritesOpen = false; markNavPending('shop'); router.visit(pathFor('shop'))">
                        {{ labels.shop }}
                        <ArrowRight class="h-4 w-4" />
                    </button>
                </div>

                <div v-else class="flex-1 flex flex-col overflow-hidden">
                    <div class="flex-1 overflow-y-auto overflow-x-hidden p-4 flex flex-col gap-3">
                        <div v-for="item in favorites.items.value" :key="item.id"
                            class="fav-drawer-item relative overflow-hidden rounded-[0.85rem] w-full">
                            <div
                                class="relative flex w-full min-w-0 items-start gap-3 rounded-[0.85rem] border border-[#111111]/10 bg-white p-3 min-h-[8.25rem] shadow-sm">
                                <Link :href="item.href || pathFor(`products/${item.slug}`)"
                                    class="h-[5.75rem] w-[4.75rem] shrink-0 overflow-hidden rounded-[0.55rem] bg-[#D8CEC4]"
                                    @click="favoritesOpen = false; markNavPending('shop')">
                                    <img v-if="item.image" :src="item.image" :alt="item.name"
                                        class="h-full w-full object-cover" />

                                    <div v-else class="h-full w-full bg-[#D8CEC4]" />
                                </Link>

                                <div class="min-w-0 flex-1 pt-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <Link :href="item.href || pathFor(`products/${item.slug}`)"
                                            class="block min-w-0 truncate font-serif text-[0.95rem] font-bold leading-tight text-[#111111] transition-opacity hover:opacity-70"
                                            @click="favoritesOpen = false; markNavPending('shop')">
                                            {{ item.name }}
                                        </Link>

                                        <button class="shrink-0 p-1 text-[#cf7467] hover:scale-110 transition-transform"
                                            type="button" aria-label="Remove favorite"
                                            @click="unfavoriteWithAnim($event, item.id)">
                                            <Heart class="h-4 w-4 stroke-[1.5] fill-[#cf7467]" />
                                        </button>
                                    </div>

                                    <p v-if="item.category"
                                        class="mt-1 text-[0.65rem] font-bold uppercase tracking-widest text-[#111111]/50">
                                        {{ item.category }}
                                    </p>

                                    <div class="mt-3 flex items-center justify-between pr-1">
                                        <p class="text-[0.85rem] font-extrabold text-[#111111]">
                                            {{ formatCurrency(item.price, item.currency) }}
                                        </p>

                                        <button type="button"
                                            class="p-1 text-[#111111] hover:text-[#cf7467] transition-all hover:scale-110 active:scale-90"
                                            aria-label="Add to bag" @click="addToCartWithAnim($event, item)">
                                            <ShoppingBag class="h-[1.15rem] w-[1.15rem] stroke-[1.8]" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.cart-drawer-panel,
.cart-drawer-stage,
.cart-drawer-scroll {
    overflow-x: hidden !important;
}

.cart-drawer-scroll {
    overscroll-behavior: contain;
}

.cart-drawer-scroll::-webkit-scrollbar:horizontal {
    display: none;
    height: 0;
}

.cart-drawer-item {
    max-width: 100%;
    contain: paint;
}

.cart-drawer-card {
    max-width: 100%;
    will-change: transform;
}

.slide-step-enter-active,
.slide-step-leave-active {
    transition: all 0.35s cubic-bezier(0.25, 1, 0.5, 1);
}

.slide-step-enter-from {
    opacity: 0;
    transform: translateX(10%);
}

.slide-step-leave-to {
    opacity: 0;
    transform: translateX(-10%);
}
</style>