<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { FilePlus2, Pencil, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    pages: Array,
});

const activeCount = computed(() => props.pages.filter((page) => page.is_active).length);
const navCount = computed(() => props.pages.filter((page) => page.show_in_nav).length);

function destroyPage(page) {
    if (!window.confirm(`Delete "${page.title}"? This removes the page from the admin and public site.`)) {
        return;
    }

    router.delete(route('admin.pages.destroy', page.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Pages - Admin" />

    <AdminLayout>
        <template #header>
            <div class="admin-page-heading">
                <div>
                    <p class="admin-eyebrow">Content</p>
                    <h1>Pages</h1>
                </div>
                <Link :href="route('admin.pages.create')" class="admin-primary-button">
                    <FilePlus2 class="h-4 w-4" />
                    Add Page
                </Link>
            </div>
        </template>

        <div class="admin-stack">
            <div v-if="$page.props.flash?.success" class="admin-success">
                {{ $page.props.flash.success }}
            </div>

            <div class="admin-metrics-grid">
                <div class="admin-stat">
                    <p>Total pages</p>
                    <strong>{{ pages.length }}</strong>
                </div>
                <div class="admin-stat">
                    <p>Active pages</p>
                    <strong>{{ activeCount }}</strong>
                </div>
                <div class="admin-stat">
                    <p>Shown in nav</p>
                    <strong>{{ navCount }}</strong>
                </div>
            </div>

            <section class="admin-panel admin-table-panel">
                <div class="admin-panel-header">
                    <div>
                        <h3>Page Library</h3>
                        <p>Inactive pages remain hidden and noindexed.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="admin-table w-full">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Key</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Nav</th>
                                <th>Robots</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="page in pages" :key="page.id">
                                <td>
                                    <div class="font-semibold text-gray-950">{{ page.title }}</div>
                                    <div v-if="page.meta_title" class="max-w-md truncate text-xs text-gray-500">{{ page.meta_title }}</div>
                                </td>
                                <td>{{ page.page_key }}</td>
                                <td>{{ page.slug }}</td>
                                <td>
                                    <span :class="['admin-status-pill', page.is_active ? 'is-live' : 'is-muted']">
                                        {{ page.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ page.show_in_nav ? 'Shown' : 'Hidden' }}</td>
                                <td>{{ page.noindex ? 'Noindex' : 'Index' }}</td>
                                <td>
                                    <div class="admin-row-actions">
                                        <Link :href="route('admin.pages.edit', page.id)" class="admin-icon-action" aria-label="Edit page">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button class="admin-icon-action danger" type="button" aria-label="Delete page" @click="destroyPage(page)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="pages.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No pages found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
