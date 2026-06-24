<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    mode: String,
    product: Object,
    categories: Array,
});

const isEdit = computed(() => props.mode === 'edit');

const blankVariant = () => ({
    id: null,
    size: '',
    color: '',
    sku: '',
    price: '',
    sale_price: '',
    stock_quantity: 0,
    is_active: true,
});

const blankProduct = () => ({
    category_id: '',
    audience_type: 'women',
    title: '',
    slug: '',
    sku: '',
    price: '',
    sale_price: '',
    currency: 'USD',
    short_description: '',
    description: '',
    stock_quantity: 0,
    is_featured: false,
    is_active: true,
    is_new: false,
    is_best_seller: false,
    sort_order: 0,
    meta_title: '',
    meta_description: '',
    tags_text: '',
    product_images: [],
    remove_image_ids: [],
    variants: [],
});

const productData = {
    ...blankProduct(),
    ...(props.product || {}),
    category_id: props.product?.category_id || '',
    sale_price: props.product?.sale_price ?? '',
    variants: props.product?.variants?.map((variant) => ({
        ...blankVariant(),
        ...variant,
        price: variant.price ?? '',
        sale_price: variant.sale_price ?? '',
    })) || [],
};

const form = useForm(productData);

const variantStockTotal = computed(() =>
    form.variants.reduce((total, variant) => total + (variant.is_active ? Number(variant.stock_quantity || 0) : 0), 0),
);

function addVariant() {
    form.variants.push(blankVariant());
}

function removeVariant(index) {
    form.variants.splice(index, 1);
}

function handleProductImages(event) {
    form.product_images = Array.from(event.target.files || []);
}

function toggleRemoveImage(imageId) {
    if (form.remove_image_ids.includes(imageId)) {
        form.remove_image_ids = form.remove_image_ids.filter((id) => id !== imageId);
        return;
    }

    form.remove_image_ids.push(imageId);
}

function submit() {
    form.is_best_seller = false;

    if (isEdit.value) {
        form
            .transform((data) => ({ ...data, is_best_seller: false, _method: 'patch' }))
            .post(route('admin.products.update', props.product.id), { forceFormData: true });
        return;
    }

    form
        .transform((data) => ({ ...data, is_best_seller: false }))
        .post(route('admin.products.store'), { forceFormData: true });
}
</script>

<template>
    <Head :title="`${isEdit ? 'Edit' : 'Add'} Product - Admin`" />

    <AdminLayout>
        <template #header>
            <div class="admin-page-heading">
                <div>
                    <Link :href="route('admin.products.index')" class="admin-back-link">
                        <ArrowLeft class="h-4 w-4" />
                        Products
                    </Link>
                    <h1>{{ isEdit ? 'Edit Product' : 'Add Product' }}</h1>
                </div>
                <button class="admin-primary-button" type="button" :disabled="form.processing" @click="submit">
                    <Save class="h-4 w-4" />
                    {{ form.processing ? 'Saving...' : 'Save Product' }}
                </button>
            </div>
        </template>

        <form class="admin-form-layout" @submit.prevent="submit">
            <div class="admin-form-main">
                <div v-if="Object.keys(form.errors).length" class="admin-error">
                    <p v-for="(message, key) in form.errors" :key="key">{{ message }}</p>
                </div>

                <section class="admin-form-card">
                    <div class="admin-form-card-header">
                        <h3>Product Details</h3>
                        <p>Main catalog information shown on shop and product pages.</p>
                    </div>
                    <div class="admin-form-grid two">
                        <label class="admin-field">
                            <span>Title</span>
                            <input v-model="form.title" class="form-input" required />
                        </label>
                        <label class="admin-field">
                            <span>Slug</span>
                            <input v-model="form.slug" class="form-input" placeholder="auto-from-title-if-empty" />
                        </label>
                        <label class="admin-field">
                            <span>Category</span>
                            <select v-model="form.category_id" class="form-input">
                                <option value="">No category</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                        </label>
                        <label class="admin-field">
                            <span>Section</span>
                            <select v-model="form.audience_type" class="form-input">
                                <option value="women">Women</option>
                                <option value="men">Men</option>
                                <option value="kids">Kids</option>
                            </select>
                        </label>
                        <label class="admin-field">
                            <span>SKU</span>
                            <input v-model="form.sku" class="form-input" />
                        </label>
                        <label class="admin-field">
                            <span>Currency</span>
                            <input v-model="form.currency" class="form-input uppercase" maxlength="3" required />
                        </label>
                        <label class="admin-field">
                            <span>Price</span>
                            <input v-model="form.price" class="form-input" min="0" step="0.01" type="number" required />
                        </label>
                        <label class="admin-field">
                            <span>Sale price</span>
                            <input v-model="form.sale_price" class="form-input" min="0" step="0.01" type="number" />
                        </label>
                        <label class="admin-field">
                            <span>Base stock</span>
                            <input v-model="form.stock_quantity" class="form-input" min="0" type="number" :disabled="form.variants.length > 0" />
                            <small v-if="form.variants.length">Using {{ variantStockTotal }} from active variants.</small>
                        </label>
                        <label class="admin-field">
                            <span>Sort order</span>
                            <input v-model="form.sort_order" class="form-input" min="0" type="number" />
                        </label>
                        <label class="admin-field span-2">
                            <span>Short description</span>
                            <textarea v-model="form.short_description" class="form-input min-h-20" />
                        </label>
                        <label class="admin-field span-2">
                            <span>Description</span>
                            <textarea v-model="form.description" class="form-input min-h-32" />
                        </label>
                    </div>
                </section>

                <section class="admin-form-card">
                    <div class="admin-form-card-header inline">
                        <div>
                            <h3>Variants</h3>
                            <p>Use size, color, SKU, and stock rows when a product has options.</p>
                        </div>
                        <button class="admin-secondary-button" type="button" @click="addVariant">
                            <Plus class="h-4 w-4" />
                            Add Variant
                        </button>
                    </div>

                    <div v-if="form.variants.length" class="admin-variant-list">
                        <div v-for="(variant, index) in form.variants" :key="variant.id || index" class="admin-variant-row">
                            <input v-model="variant.color" class="form-input" placeholder="Color" />
                            <input v-model="variant.size" class="form-input" placeholder="Size" />
                            <input v-model="variant.sku" class="form-input wide" placeholder="Variant SKU" />
                            <input v-model="variant.stock_quantity" class="form-input" min="0" type="number" placeholder="Stock" />
                            <input v-model="variant.price" class="form-input" min="0" step="0.01" type="number" placeholder="Price" />
                            <label class="admin-inline-check">
                                <input v-model="variant.is_active" type="checkbox" />
                                Active
                            </label>
                            <button class="admin-icon-action danger" type="button" @click="removeVariant(index)" aria-label="Remove variant">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <p v-else class="admin-empty-copy">No variants yet. Base stock will be used until variants are added.</p>
                </section>

                <section class="admin-form-card">
                    <div class="admin-form-card-header">
                        <h3>Product Images</h3>
                        <p>Upload one or more product photos. They are stored under storage/app/public/products/{product id}.</p>
                    </div>

                    <div v-if="product?.images?.length" class="admin-image-grid">
                        <div
                            v-for="image in product.images"
                            :key="image.id"
                            :class="['admin-image-tile', form.remove_image_ids.includes(image.id) ? 'is-marked' : '']"
                        >
                            <img :src="image.url" :alt="image.alt_text || product.title" />
                            <div class="admin-image-tile-footer">
                                <span>{{ image.is_main ? 'Main image' : 'Gallery image' }}</span>
                                <button type="button" @click="toggleRemoveImage(image.id)">
                                    {{ form.remove_image_ids.includes(image.id) ? 'Keep' : 'Remove' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <label class="admin-upload-field">
                        <input multiple accept="image/png,image/jpeg,image/webp" type="file" @change="handleProductImages" />
                        <span>Choose product images</span>
                        <small>JPG, PNG, or WebP. Max 10MB each.</small>
                    </label>

                    <div v-if="form.product_images.length" class="admin-selected-files">
                        <span v-for="file in form.product_images" :key="file.name">{{ file.name }}</span>
                    </div>
                </section>

                <section class="admin-form-card">
                    <div class="admin-form-card-header">
                        <h3>SEO</h3>
                        <p>Search result and social sharing copy.</p>
                    </div>
                    <div class="admin-form-grid two">
                        <label class="admin-field">
                            <span>Meta title</span>
                            <input v-model="form.meta_title" class="form-input" />
                        </label>
                        <label class="admin-field">
                            <span>Tags</span>
                            <input v-model="form.tags_text" class="form-input" placeholder="blazer, women, tailored" />
                        </label>
                        <label class="admin-field span-2">
                            <span>Meta description</span>
                            <textarea v-model="form.meta_description" class="form-input min-h-24" />
                        </label>
                    </div>
                </section>
            </div>

            <aside class="admin-form-side">
                <section class="admin-form-card">
                    <h3>Visibility</h3>
                    <div class="admin-toggle-list">
                        <label class="admin-toggle"><input v-model="form.is_active" type="checkbox" /><span>Active</span></label>
                        <label class="admin-toggle"><input v-model="form.is_featured" type="checkbox" /><span>Featured</span></label>
                        <label class="admin-toggle"><input v-model="form.is_new" type="checkbox" /><span>New arrival</span></label>
                    </div>
                </section>

                <section class="admin-form-card sticky-actions">
                    <button class="admin-primary-button w-full justify-center" type="submit" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Product' }}
                    </button>
                    <Link :href="route('admin.products.index')" class="admin-secondary-button w-full justify-center">
                        Cancel
                    </Link>
                </section>
            </aside>
        </form>
    </AdminLayout>
</template>
