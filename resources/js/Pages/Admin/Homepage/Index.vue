<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Home, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    sections: Array,
});

const activeCount = computed(() => props.sections.filter((section) => section.is_active).length);

function destroySection(section) {
    if (!window.confirm(`Delete "${section.section_key}"? You can also make it inactive instead.`)) {
        return;
    }

    router.delete(route('admin.homepage.destroy', section.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Homepage - Admin" />

    <AdminLayout>
        <template #header>
            <div class="admin-page-heading">
                <div>
                    <p class="admin-eyebrow">Storefront</p>
                    <h1>Home Page</h1>
                </div>
                <Link :href="route('admin.homepage.create')" class="admin-primary-button">
                    <Plus class="h-4 w-4" />
                    Add Section
                </Link>
            </div>
        </template>

        <div class="admin-stack">
            <div v-if="$page.props.flash?.success" class="admin-success">
                {{ $page.props.flash.success }}
            </div>

            <div class="admin-metrics-grid compact">
                <div class="admin-stat">
                    <p>Home sections</p>
                    <strong>{{ sections.length }}</strong>
                </div>
                <div class="admin-stat">
                    <p>Active sections</p>
                    <strong>{{ activeCount }}</strong>
                </div>
            </div>

            <section class="admin-panel admin-table-panel">
                <div class="admin-panel-header">
                    <div>
                        <h3>Editable Home Blocks</h3>
                        <p>Hero, collections, editorial, product headings, promo, social, and footer content.</p>
                    </div>
                    <Home class="h-5 w-5 text-gray-400" />
                </div>

                <div class="overflow-x-auto">
                    <table class="admin-table w-full">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Title</th>
                                <th>Button</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="section in sections" :key="section.id">
                                <td>
                                    <div class="font-semibold text-gray-950">{{ section.section_key }}</div>
                                    <div class="text-xs text-gray-500">Sort {{ section.sort_order }}</div>
                                </td>
                                <td>
                                    <div>{{ section.title || '-' }}</div>
                                    <div v-if="section.subtitle" class="max-w-md truncate text-xs text-gray-500">{{ section.subtitle }}</div>
                                </td>
                                <td>{{ section.button_text || '-' }}</td>
                                <td>
                                    <img v-if="section.image_is_uploaded && section.image_url" :src="section.image_url" :alt="section.image_alt_text || section.title" class="admin-thumb" />
                                    <span v-else class="text-xs text-gray-500">Splash fallback</span>
                                </td>
                                <td>
                                    <span :class="['admin-status-pill', section.is_active ? 'is-live' : 'is-muted']">
                                        {{ section.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-row-actions">
                                        <Link :href="route('admin.homepage.edit', section.id)" class="admin-icon-action" aria-label="Edit section">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button class="admin-icon-action danger" type="button" aria-label="Delete section" @click="destroySection(section)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="sections.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No home sections found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
