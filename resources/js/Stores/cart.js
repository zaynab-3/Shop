import { computed, reactive } from 'vue';

const CART_KEY = 'scarbina-cart-v1';

const state = reactive({
    items: [],
});

function hydrate() {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        state.items = JSON.parse(window.localStorage.getItem(CART_KEY) || '[]');
    } catch {
        state.items = [];
    }
}

function persist() {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(CART_KEY, JSON.stringify(state.items));
    window.dispatchEvent(new CustomEvent('scarbina-cart-updated'));
}

function keyFor(product, variant = null) {
    return `${product.id}:${variant?.id || 'base'}`;
}

function addItem(product, variant = null, quantity = 1) {
    const key = keyFor(product, variant);
    const existing = state.items.find((item) => item.key === key);
    const maxStock = variant?.stock_quantity ?? product.stock_quantity ?? 1;

    if (existing) {
        existing.name = product.name;
        existing.slug = product.slug;
        existing.href = product.href;
        existing.image = variant?.image || product.image;
        existing.currency = product.currency;
        existing.price = variant?.price ?? product.current_price ?? product.price;
        existing.stock_quantity = maxStock;
        existing.quantity = Math.min(existing.quantity + quantity, maxStock);
    } else {
        state.items.push({
            key,
            product_id: product.id,
            variant_id: variant?.id || null,
            name: product.name,
            slug: product.slug,
            href: product.href,
            image: variant?.image || product.image,
            currency: product.currency,
            price: variant?.price ?? product.current_price ?? product.price,
            quantity: Math.min(quantity, maxStock),
            stock_quantity: maxStock,
            variant: variant
                ? {
                      id: variant.id,
                      size: variant.size,
                      color: variant.color,
                      sku: variant.sku,
                  }
                : null,
        });
    }

    persist();
}

function updateQuantity(key, quantity) {
    const item = state.items.find((cartItem) => cartItem.key === key);

    if (!item) {
        return;
    }

    item.quantity = Math.max(1, Math.min(Number(quantity), item.stock_quantity || 1));
    persist();
}

function removeItem(key) {
    state.items = state.items.filter((item) => item.key !== key);
    persist();
}

function clearCart() {
    state.items = [];
    persist();
}

hydrate();

export const cart = {
    items: computed(() => state.items),
    count: computed(() => state.items.reduce((total, item) => total + item.quantity, 0)),
    subtotal: computed(() => state.items.reduce((total, item) => total + item.quantity * item.price, 0)),
    addItem,
    updateQuantity,
    removeItem,
    clearCart,
    hydrate,
};

export function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(Number(amount || 0));
}
