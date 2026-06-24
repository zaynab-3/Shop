<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    mode: String,
    section: Object,
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    section_key: props.section?.section_key || '',
    title: props.section?.title || '',
    subtitle: props.section?.subtitle || '',
    content: props.section?.content || '',
    button_text: props.section?.button_text || '',
    button_url: props.section?.button_url || '',
    image_url: '',
    image_file: null,
    image_alt_text: props.section?.image_alt_text || '',
    remove_image: false,
    is_active: props.section?.is_active ?? true,
    sort_order: props.section?.sort_order || 0,
});

const currentUploadedImage = computed(() => props.section?.image_is_uploaded ? props.section.image_url : null);
const selectedImageName = computed(() => form.image_file?.name || '');

function handleImageFile(event) {
    form.image_file = event.target.files?.[0] || null;
    form.remove_image = false;
}

function submit() {
    if (isEdit.value) {
        form
            .transform((data) => ({ ...data, _method: 'patch' }))
            .post(route('admin.homepage.update', props.section.id), { forceFormData: true });
        return;
    }

    form.post(route('admin.homepage.store'), { forceFormData: true });
}
</script>

<template>
    <Head :title="`${isEdit ? 'Edit' : 'Add'} Home Section - Admin`" />

    <AdminLayout>
        <template #header>
            <div class="admin-page-heading">
                <div>
                    <Link :href="route('admin.homepage.index')" class="admin-back-link">
                        <ArrowLeft class="h-4 w-4" />
                        Home Page
                    </Link>
                    <h1>{{ isEdit ? 'Edit Home Section' : 'Add Home Section' }}</h1>
                </div>
                <button class="admin-primary-button" type="button" :disabled="form.processing" @click="submit">
                    <Save class="h-4 w-4" />
                    {{ form.processing ? 'Saving...' : 'Save Section' }}
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
                        <h3>Section Content</h3>
                        <p>Keys like hero, collections_intro, collection_women, collection_men, collection_kids, editorial, new_arrivals, promo_banner, instagram_social, and footer are used by the home page.</p>
                    </div>
                    <div class="admin-form-grid two">
                        <label class="admin-field">
                            <span>Section key</span>
                            <input v-model="form.section_key" class="form-input" placeholder="hero" required />
                        </label>
                        <label class="admin-field">
                            <span>Sort order</span>
                            <input v-model="form.sort_order" class="form-input" min="0" type="number" />
                        </label>
                        <label class="admin-field span-2">
                            <span>Title</span>
                            <input v-model="form.title" class="form-input" />
                        </label>
                        <label class="admin-field span-2">
                            <span>Subtitle / small note</span>
                            <input v-model="form.subtitle" class="form-input" />
                        </label>
                        <label class="admin-field span-2">
                            <span>Content</span>
                            <textarea v-model="form.content" class="form-input min-h-44" />
                        </label>
                        <label class="admin-field">
                            <span>Button text</span>
                            <input v-model="form.button_text" class="form-input" />
                        </label>
                        <label class="admin-field">
                            <span>Button URL</span>
                            <input v-model="form.button_url" class="form-input" placeholder="/shop" />
                        </label>
                    </div>
                </section>

                <section class="admin-form-card">
                    <div class="admin-form-card-header">
                        <h3>Image</h3>
                        <p>Upload a local image for this home section. If none is uploaded, the storefront uses the built-in splash image.</p>
                    </div>
                    <div v-if="currentUploadedImage && !form.remove_image" class="admin-current-image">
                        <img :src="currentUploadedImage" :alt="form.image_alt_text || form.title" />
                        <button class="admin-secondary-button" type="button" @click="form.remove_image = true">
                            Remove uploaded image
                        </button>
                    </div>
                    <div v-else class="admin-splash-note">
                        No uploaded image is active for this section. The storefront will show its splash fallback.
                    </div>

                    <label class="admin-upload-field">
                        <input accept="image/png,image/jpeg,image/webp" type="file" @change="handleImageFile" />
                        <span>Choose section image</span>
                        <small>Stored in storage/app/public/homepage/{{ form.section_key || 'section-key' }}. JPG, PNG, or WebP up to 10MB.</small>
                    </label>

                    <div v-if="selectedImageName" class="admin-selected-files">
                        <span>{{ selectedImageName }}</span>
                    </div>

                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>Alt text</span>
                            <input v-model="form.image_alt_text" class="form-input" />
                        </label>
                    </div>
                </section>
            </div>

            <aside class="admin-form-side">
                <section class="admin-form-card">
                    <h3>Visibility</h3>
                    <div class="admin-toggle-list">
                        <label class="admin-toggle"><input v-model="form.is_active" type="checkbox" /><span>Active on home page</span></label>
                    </div>
                </section>

                <section class="admin-form-card sticky-actions">
                    <button class="admin-primary-button w-full justify-center" type="submit" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Section' }}
                    </button>
                    <Link :href="route('admin.homepage.index')" class="admin-secondary-button w-full justify-center">
                        Cancel
                    </Link>
                </section>
            </aside>
        </form>
    </AdminLayout>
</template>
