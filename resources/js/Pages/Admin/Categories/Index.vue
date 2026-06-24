<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderPlus, Pencil, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    categories: Array,
});

const activeCount = computed(() => props.categories.filter((category) => category.is_active).length);

function destroyCategory(category) {
    if (!window.confirm(`Delete "${category.name}"? Products in this category will keep existing but lose the category link.`)) {
        return;
    }

    router.delete(route('admin.categories.destroy', category.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Categories - Admin" />

    <AdminLayout>
        <template #header>
            <div class="admin-page-heading">
                <div>
                    <p class="admin-eyebrow">Catalog</p>
                    <h1>Categories</h1>
                </div>
                <Link :href="route('admin.categories.create')" class="admin-primary-button">
                    <FolderPlus class="h-4 w-4" />
                    Add Category
                </Link>
            </div>
        </template>

        <div class="admin-stack">
            <div v-if="$page.props.flash?.success" class="admin-success">
                {{ $page.props.flash.success }}
            </div>

            <div class="admin-metrics-grid compact">
                <div class="admin-stat">
                    <p>Total categories</p>
                    <strong>{{ categories.length }}</strong>
                </div>
                <div class="admin-stat">
                    <p>Active categories</p>
                    <strong>{{ activeCount }}</strong>
                </div>
            </div>

            <section class="admin-panel admin-table-panel">
                <div class="admin-panel-header">
                    <div>
                        <h3>Category List</h3>
                        <p>Storefront collection names, SEO copy, and filter ordering.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="admin-table w-full">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="category in categories" :key="category.id">
                                <td>
                                    <div class="font-semibold text-gray-950">{{ category.name }}</div>
                                    <div v-if="category.description" class="max-w-md truncate text-xs text-gray-500">{{ category.description }}</div>
                                </td>
                                <td>{{ category.slug }}</td>
                                <td>{{ category.products_count }}</td>
                                <td>
                                    <span :class="['admin-status-pill', category.is_active ? 'is-live' : 'is-muted']">
                                        {{ category.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-row-actions">
                                        <Link :href="route('admin.categories.edit', category.id)" class="admin-icon-action" aria-label="Edit category">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button class="admin-icon-action danger" type="button" aria-label="Delete category" @click="destroyCategory(category)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categories.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    No categories found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
