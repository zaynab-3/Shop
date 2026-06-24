<script setup>
import { Link } from '@inertiajs/vue3';
import { ShoppingBag, Check } from 'lucide-vue-next';
import { ref } from 'vue';
import { cart, formatCurrency } from '@/Stores/cart';

const props = defineProps({
    product: { type: Object, required: true },
});

const productSplashImage = 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=1200&q=80';
const added = ref(false);

function quickAdd() {
    if (added.value) return;
    const variant = props.product.variants?.find((item) => item.stock_quantity > 0) || null;
    cart.addItem(props.product, variant, 1);
    added.value = true;
    setTimeout(() => { added.value = false; }, 1800);
}
</script>

<template>
    <article class="product-card group">
        <Link :href="product.href" class="block overflow-hidden bg-[#D8CEC4]">
            <img
                :src="product.image || productSplashImage"
                :alt="product.alt || product.name"
                class="aspect-[4/5] w-full object-cover transition duration-700 group-hover:scale-105"
                loading="lazy"
            />
        </Link>
        <div class="mt-4 flex items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap gap-2">
                    <span v-if="product.is_new" class="badge">New</span>
                    <span v-if="product.sale_price" class="badge">Sale</span>
                    <span v-if="product.stock_quantity <= 0" class="badge">Out of Stock</span>
                </div>
                <Link :href="product.href" class="mt-2 block font-display text-lg leading-tight">
                    {{ product.name }}
                </Link>
                <p v-if="product.category" class="mt-1 text-xs uppercase tracking-[0.18em] text-black/45">
                    {{ product.category }}
                </p>
            </div>
            <button
                class="quick-add-btn shrink-0"
                :class="{ 'quick-add-btn--added': added }"
                type="button"
                :disabled="product.stock_quantity <= 0"
                @click="quickAdd"
                :aria-label="added ? 'Added to cart' : 'Add to cart'"
            >
                <Transition name="icon-swap" mode="out-in">
                    <Check v-if="added" class="h-4 w-4" key="check" />
                    <ShoppingBag v-else class="h-4 w-4" key="bag" />
                </Transition>
            </button>
        </div>
        <div class="mt-2 flex items-center gap-2">
            <span class="font-semibold">{{ formatCurrency(product.current_price, product.currency) }}</span>
            <span v-if="product.sale_price" class="text-sm text-black/40 line-through">
                {{ formatCurrency(product.price, product.currency) }}
            </span>
        </div>
    </article>
</template>
