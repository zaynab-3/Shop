<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    mode: String,
    page: Object,
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    page_key: props.page?.page_key || '',
    title: props.page?.title || '',
    slug: props.page?.slug || '',
    content: props.page?.content || '',
    is_active: props.page?.is_active ?? false,
    show_in_nav: props.page?.show_in_nav ?? false,
    noindex: props.page?.noindex ?? true,
    sort_order: props.page?.sort_order || 0,
    meta_title: props.page?.meta_title || '',
    meta_description: props.page?.meta_description || '',
});

function submit() {
    if (isEdit.value) {
        form.patch(route('admin.pages.update', props.page.id));
        return;
    }

    form.post(route('admin.pages.store'));
}
</script>

<template>
    <Head :title="`${isEdit ? 'Edit' : 'Add'} Page - Admin`" />

    <AdminLayout>
        <template #header>
            <div class="admin-page-heading">
                <div>
                    <Link :href="route('admin.pages.index')" class="admin-back-link">
                        <ArrowLeft class="h-4 w-4" />
                        Pages
                    </Link>
                    <h1>{{ isEdit ? 'Edit Page' : 'Add Page' }}</h1>
                </div>
                <button class="admin-primary-button" type="button" :disabled="form.processing" @click="submit">
                    <Save class="h-4 w-4" />
                    {{ form.processing ? 'Saving...' : 'Save Page' }}
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
                        <h3>Page Content</h3>
                        <p>Controls public pages, inactive coming-soon states, and SEO.</p>
                    </div>
                    <div class="admin-form-grid two">
                        <label class="admin-field">
                            <span>Page key</span>
                            <input v-model="form.page_key" class="form-input" placeholder="privacy_policy" required />
                        </label>
                        <label class="admin-field">
                            <span>Title</span>
                            <input v-model="form.title" class="form-input" required />
                        </label>
                        <label class="admin-field span-2">
                            <span>Slug</span>
                            <input v-model="form.slug" class="form-input" placeholder="auto-from-title-if-empty" />
                        </label>
                        <label class="admin-field span-2">
                            <span>Content</span>
                            <textarea v-model="form.content" class="form-input min-h-56" />
                        </label>
                        <label class="admin-field">
                            <span>Sort order</span>
                            <input v-model="form.sort_order" class="form-input" min="0" type="number" />
                        </label>
                        <label class="admin-field">
                            <span>Meta title</span>
                            <input v-model="form.meta_title" class="form-input" />
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
                    <h3>Publishing</h3>
                    <div class="admin-toggle-list">
                        <label class="admin-toggle"><input v-model="form.is_active" type="checkbox" /><span>Active</span></label>
                        <label class="admin-toggle"><input v-model="form.show_in_nav" type="checkbox" /><span>Show in nav</span></label>
                        <label class="admin-toggle"><input v-model="form.noindex" type="checkbox" /><span>Noindex</span></label>
                    </div>
                </section>

                <section class="admin-form-card sticky-actions">
                    <button class="admin-primary-button w-full justify-center" type="submit" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Page' }}
                    </button>
                    <Link :href="route('admin.pages.index')" class="admin-secondary-button w-full justify-center">
                        Cancel
                    </Link>
                </section>
            </aside>
        </form>
    </AdminLayout>
</template>
