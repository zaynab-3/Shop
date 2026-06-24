<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Heart } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { formatCurrency } from '@/Stores/cart';
import { favorites } from '@/Stores/favorites';
import gsap from 'gsap';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    locale: {
        type: String,
        default: 'en',
    },
});

const rail = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

function pathFor(path = '') {
    const [basePath, queryString = ''] = String(path).replace(/^\/+/, '').split('?');
    const prefix = props.locale === 'en' ? '' : `/${props.locale}`;
    const url = `${prefix}/${basePath}`.replace(/\/$/, '') || '/';

    return queryString ? `${url}?${queryString}` : url;
}

function productUrl(product) {
    return product.href || pathFor(`products/${product.slug}`);
}

function productImage(product) {
    return product.image || product.thumbnail || product.main_image || '/storefront/soleil/shop-product-7.jpg';
}

function productPrice(product) {
    return formatCurrency(product.current_price || product.price || 0, product.currency || 'USD');
}

function updateArrowState() {
    if (!rail.value) return;

    const { scrollLeft, scrollWidth, clientWidth } = rail.value;
    const maxScrollLeft = scrollWidth - clientWidth;

    canScrollLeft.value = scrollLeft > 4;
    canScrollRight.value = scrollLeft < maxScrollLeft - 4;
}

function scrollRail(direction) {
    if (!rail.value) return;

    const card = rail.value.querySelector('.related-rail-card');
    const cardWidth = card ? card.getBoundingClientRect().width : 220;
    const gap = 20;
    const amount = (cardWidth + gap) * 2;

    rail.value.scrollBy({
        left: direction === 'next' ? amount : -amount,
        behavior: 'smooth',
    });
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

function toggleFavoriteWithAnim(event, product) {
    const button = event.currentTarget;
    animateHeart(button);
    favorites.toggleItem(product);
}

onMounted(async () => {
    await nextTick();
    updateArrowState();

    rail.value?.addEventListener('scroll', updateArrowState, { passive: true });
    window.addEventListener('resize', updateArrowState);
});

onBeforeUnmount(() => {
    rail.value?.removeEventListener('scroll', updateArrowState);
    window.removeEventListener('resize', updateArrowState);
});
</script>

<template>
    <section v-if="products.length" class="related-rail-section">
        <div class="related-rail-shell">
            <div class="related-rail-heading-row">
                <h2 class="related-rail-title">
                    Browse More <em>Products</em>
                </h2>

                <div class="related-rail-arrows">
                    <button type="button" class="related-rail-arrow" :disabled="!canScrollLeft"
                        aria-label="Previous products" @click="scrollRail('prev')">
                        <ChevronLeft class="h-5 w-5 stroke-[1.7]" />
                    </button>

                    <button type="button" class="related-rail-arrow" :disabled="!canScrollRight"
                        aria-label="Next products" @click="scrollRail('next')">
                        <ChevronRight class="h-5 w-5 stroke-[1.7]" />
                    </button>
                </div>
            </div>

            <div ref="rail" class="related-rail-track">
                <Link v-for="product in products" :key="product.id" :href="productUrl(product)"
                    class="related-rail-card">
                    <div class="related-rail-media">
                        <img :src="productImage(product)" :alt="product.name" loading="lazy" />

                        <button type="button" class="related-rail-favorite" aria-label="Add to favorites"
                            @click.prevent.stop="toggleFavoriteWithAnim($event, product)">
                            <Heart class="h-4 w-4 stroke-[1.5]"
                                :class="{ 'fill-[#cf7467] text-[#cf7467]': favorites.isFavorite(product.id) }" />
                        </button>
                    </div>

                    <div class="related-rail-info">
                        <h3>
                            {{ product.name }}
                        </h3>

                        <p v-if="product.category">
                            {{ product.category }}
                        </p>

                        <span>
                            {{ productPrice(product) }}
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </section>
</template>

<style scoped>
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
    scrollbar-width: none;
}

.related-rail-track::-webkit-scrollbar {
    display: none;
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
