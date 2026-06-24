<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    mode: String,
    category: Object,
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    name: props.category?.name || '',
    slug: props.category?.slug || '',
    description: props.category?.description || '',
    is_active: props.category?.is_active ?? true,
    sort_order: props.category?.sort_order || 0,
    meta_title: props.category?.meta_title || '',
    meta_description: props.category?.meta_description || '',
});

function submit() {
    if (isEdit.value) {
        form.patch(route('admin.categories.update', props.category.id));
        return;
    }

    form.post(route('admin.categories.store'));
}
</script>

<template>
    <Head :title="`${isEdit ? 'Edit' : 'Add'} Category - Admin`" />

    <AdminLayout>
        <template #header>
            <div class="admin-page-heading">
                <div>
                    <Link :href="route('admin.categories.index')" class="admin-back-link">
                        <ArrowLeft class="h-4 w-4" />
                        Categories
                    </Link>
                    <h1>{{ isEdit ? 'Edit Category' : 'Add Category' }}</h1>
                </div>
                <button class="admin-primary-button" type="button" :disabled="form.processing" @click="submit">
                    <Save class="h-4 w-4" />
                    {{ form.processing ? 'Saving...' : 'Save Category' }}
                </button>
            </div>
        </template>

        <form class="admin-form-layout narrow" @submit.prevent="submit">
            <div class="admin-form-main">
                <div v-if="Object.keys(form.errors).length" class="admin-error">
                    <p v-for="(message, key) in form.errors" :key="key">{{ message }}</p>
                </div>

                <section class="admin-form-card">
                    <div class="admin-form-card-header">
                        <h3>Category Details</h3>
                        <p>Controls storefront filters and collection links.</p>
                    </div>
                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>Name</span>
                            <input v-model="form.name" class="form-input" required />
                        </label>
                        <label class="admin-field">
                            <span>Slug</span>
                            <input v-model="form.slug" class="form-input" placeholder="auto-from-name-if-empty" />
                        </label>
                        <label class="admin-field">
                            <span>Description</span>
                            <textarea v-model="form.description" class="form-input min-h-28" />
                        </label>
                        <label class="admin-field">
                            <span>Sort order</span>
                            <input v-model="form.sort_order" class="form-input" min="0" type="number" />
                        </label>
                        <label class="admin-toggle"><input v-model="form.is_active" type="checkbox" /><span>Active</span></label>
                    </div>
                </section>

                <section class="admin-form-card">
                    <div class="admin-form-card-header">
                        <h3>SEO</h3>
                        <p>Search result copy for this category.</p>
                    </div>
                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>Meta title</span>
                            <input v-model="form.meta_title" class="form-input" />
                        </label>
                        <label class="admin-field">
                            <span>Meta description</span>
                            <textarea v-model="form.meta_description" class="form-input min-h-24" />
                        </label>
                    </div>
                </section>
            </div>
        </form>
    </AdminLayout>
</template>
