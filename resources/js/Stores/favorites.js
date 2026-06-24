import { computed, reactive } from 'vue';

const FAVORITES_KEY = 'scarbina-favorites-v2';

const state = reactive({
    items: [],
});

function hydrate() {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        state.items = JSON.parse(window.localStorage.getItem(FAVORITES_KEY) || '[]');
    } catch {
        state.items = [];
    }
}

function persist() {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(FAVORITES_KEY, JSON.stringify(state.items));
    window.dispatchEvent(new CustomEvent('scarbina-favorites-updated'));
}

function toggleItem(product) {
    const existingIndex = state.items.findIndex((item) => item.id === product.id);
    if (existingIndex > -1) {
        state.items.splice(existingIndex, 1);
    } else {
        state.items.push({
            id: product.id,
            name: product.name,
            slug: product.slug,
            href: product.href,
            image: product.image || product.images?.[0] || '/storefront/soleil/shop-product-4.jpg',
            currency: product.currency || 'USD',
            price: product.current_price || product.price,
            category: product.category,
        });
    }
    persist();
}

function removeItem(id) {
    state.items = state.items.filter((item) => item.id !== id);
    persist();
}

function isFavorite(id) {
    return state.items.some((item) => item.id === id);
}

hydrate();

export const favorites = {
    items: computed(() => state.items),
    count: computed(() => state.items.length),
    toggleItem,
    removeItem,
    isFavorite,
    hydrate,
};
