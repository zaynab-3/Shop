<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { PackagePlus, Pencil, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    products: Array,
    categories: Array,
});

const activeCount = computed(() => props.products.filter((product) => product.is_active).length);
const variantCount = computed(() => props.products.reduce((total, product) => total + (product.variants?.length || 0), 0));

function destroyProduct(product) {
    if (!window.confirm(`Delete "${product.title}"? This hides it from the shop and removes its variants.`)) {
        return;
    }

    router.delete(route('admin.products.destroy', product.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Products - Admin" />

    <AdminLayout>
        <template #header>
            <div class="admin-page-heading">
                <div>
                    <p class="admin-eyebrow">Catalog</p>
                    <h1>Products</h1>
                </div>
                <Link :href="route('admin.products.create')" class="admin-primary-button">
                    <PackagePlus class="h-4 w-4" />
                    Add Product
                </Link>
            </div>
        </template>

        <div class="admin-stack">
            <div v-if="$page.props.flash?.success" class="admin-success">
                {{ $page.props.flash.success }}
            </div>

            <div class="admin-metrics-grid">
                <div class="admin-stat">
                    <p>Total products</p>
                    <strong>{{ products.length }}</strong>
                </div>
                <div class="admin-stat">
                    <p>Active products</p>
                    <strong>{{ activeCount }}</strong>
                </div>
                <div class="admin-stat">
                    <p>Variant rows</p>
                    <strong>{{ variantCount }}</strong>
                </div>
            </div>

            <section class="admin-panel admin-table-panel">
                <div class="admin-panel-header">
                    <div>
                        <h3>Product Library</h3>
                        <p>Products, stock, badges, SEO, and variants.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="admin-table w-full">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in products" :key="product.id">
                                <td>
                                    <div class="font-semibold text-gray-950">{{ product.title }}</div>
                                    <div class="text-xs text-gray-500">{{ product.slug }}</div>
                                </td>
                                <td>{{ product.category_name || '-' }}</td>
                                <td>{{ product.sku || '-' }}</td>
                                <td>
                                    <span>{{ product.price }} {{ product.currency }}</span>
                                    <span v-if="product.sale_price" class="ml-1 text-xs text-red-600">{{ product.sale_price }} sale</span>
                                </td>
                                <td>
                                    <span>{{ product.stock_quantity }}</span>
                                    <span v-if="product.variants?.length" class="ml-1 text-xs text-gray-500">from variants</span>
                                </td>
                                <td>
                                    <span :class="['admin-status-pill', product.is_active ? 'is-live' : 'is-muted']">
                                        {{ product.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-row-actions">
                                        <Link :href="route('admin.products.edit', product.id)" class="admin-icon-action" aria-label="Edit product">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button class="admin-icon-action danger" type="button" aria-label="Delete product" @click="destroyProduct(product)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="products.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No products found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
