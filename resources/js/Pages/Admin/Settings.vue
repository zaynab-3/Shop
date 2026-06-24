<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PhoneNumberField from '@/Components/PhoneNumberField.vue';
import { isValidInternationalPhone } from '@/Data/countryDialCodes';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();

const settingsForm = page.props.settingsForm || {};
const langEnabled = ref(Boolean(settingsForm.language_switcher_enabled ?? page.props.settings?.language_switcher_enabled === '1'));
const whatsappForm = useForm({
    whatsapp_number: settingsForm.whatsapp_number || page.props.settings?.whatsapp_number || '',
});

function toggleLanguage() {
    router.post(route('admin.settings.language.toggle'), {
        enabled: langEnabled.value,
    }, { preserveScroll: true });
}

function saveWhatsapp() {
    whatsappForm.clearErrors('whatsapp_number');

    if (!isValidInternationalPhone(whatsappForm.whatsapp_number)) {
        whatsappForm.setError('whatsapp_number', 'Enter a valid WhatsApp number for the selected country code.');
        return;
    }

    whatsappForm.post(route('admin.settings.whatsapp.update'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Settings - Admin" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">Settings</h2>
        </template>

        <div class="mx-auto max-w-3xl space-y-6">

            <section class="admin-panel">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Order &amp; WhatsApp</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            This number is fetched from the database when a customer sends their cart order.
                        </p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Active
                    </span>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="saveWhatsapp">
                    <PhoneNumberField
                        id="whatsapp_number"
                        v-model="whatsappForm.whatsapp_number"
                        label="WhatsApp number"
                        helper="Choose the business country code. Type digits only; use the local leading 0 when that country needs it. This is what order redirects use."
                        :error="whatsappForm.errors.whatsapp_number"
                        required
                    />

                    <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-4">
                        <p class="text-xs text-gray-500">
                            Stored in the <span class="font-semibold text-gray-700">settings</span> table as <span class="font-semibold text-gray-700">whatsapp_number</span>.
                        </p>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md bg-black px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:opacity-50"
                            :disabled="whatsappForm.processing"
                        >
                            {{ whatsappForm.processing ? 'Saving...' : 'Save number' }}
                        </button>
                    </div>
                </form>

                <p v-if="$page.props.flash?.success" class="mt-3 text-sm font-medium text-green-600">
                    {{ $page.props.flash.success }}
                </p>
            </section>

            <!-- Language Section -->
            <section class="admin-panel">
                <h3 class="text-base font-semibold text-gray-900">Language &amp; Localisation</h3>
                <p class="mt-1 text-sm text-gray-500">Control whether visitors can switch the storefront language.</p>

                <div class="mt-6 flex items-center justify-between rounded-lg border border-gray-200 p-4">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Language Switcher</p>
                        <p class="mt-0.5 text-xs text-gray-500">When disabled the EN / FR / AR buttons are hidden from every visitor.</p>
                    </div>
                    <!-- Toggle switch -->
                    <button
                        type="button"
                        @click="langEnabled = !langEnabled; toggleLanguage()"
                        :class="[
                            langEnabled ? 'bg-black' : 'bg-gray-200',
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none'
                        ]"
                        :aria-checked="langEnabled"
                        role="switch"
                    >
                        <span
                            :class="[
                                langEnabled ? 'translate-x-5' : 'translate-x-0',
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                            ]"
                        />
                    </button>
                </div>

                <p v-if="$page.props.flash?.success" class="mt-3 text-sm font-medium text-green-600">
                    {{ $page.props.flash.success }}
                </p>
            </section>

        </div>
    </AdminLayout>
</template>
