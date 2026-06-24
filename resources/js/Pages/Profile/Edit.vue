<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    CheckCircle2,
    ChevronRight,
    LogOut,
    MapPin,
    Package,
    Pencil,
    Plus,
    Trash2,
    UserRound,
} from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    hasPassword: { type: Boolean, default: true },
    googleAccount: { type: Boolean, default: false },
    status: { type: String, default: null },

    locale: { type: String, default: 'en' },
    isRtl: { type: Boolean, default: false },
    settings: { type: Object, default: () => ({}) },
    navPages: { type: Array, default: () => [] },
    footer: { type: Object, default: null },
    footerPages: { type: Array, default: () => [] },
});

const page = usePage();

const activeTab = ref('account');
const addressEditingId = ref(null);
const showAddressForm = ref(false);
const addresses = ref([]);

const user = computed(() => page.props.auth?.user || {});
const brandName = computed(() => props.settings?.brand_name || 'SCARBINA');

const storageUserKey = computed(() => user.value?.id || user.value?.email || 'guest');
const addressesStorageKey = computed(() => `scarbina_profile_addresses_${storageUserKey.value}`);

const emptyAddressForm = () => ({
    label: '',
    full_name: '',
    phone: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    area: '',
    country: '',
    postal_code: '',
    is_default: false,
});

const addressForm = reactive(emptyAddressForm());

function resetAddressForm() {
    Object.assign(addressForm, emptyAddressForm());
    addressEditingId.value = null;
    showAddressForm.value = false;
}

function pathFor(path = '') {
    const [basePath, queryString = ''] = String(path).replace(/^\/+/, '').split('?');
    const prefix = props.locale === 'en' ? '' : `/${props.locale}`;
    const url = `${prefix}/${basePath}`.replace(/\/$/, '') || '/';

    return queryString ? `${url}?${queryString}` : url;
}

function setActiveTab(tab) {
    activeTab.value = tab;
}

function makeId(prefix) {
    return `${prefix}_${Date.now()}_${Math.random().toString(16).slice(2)}`;
}

function saveAddressesToStorage() {
    localStorage.setItem(addressesStorageKey.value, JSON.stringify(addresses.value));
}

function loadStoredProfileData() {
    try {
        const storedAddresses = JSON.parse(localStorage.getItem(addressesStorageKey.value) || '[]');
        addresses.value = Array.isArray(storedAddresses) ? storedAddresses : [];
    } catch (error) {
        addresses.value = [];
    }
}

function openNewAddressForm() {
    resetAddressForm();
    showAddressForm.value = true;
}

function editAddress(address) {
    Object.assign(addressForm, {
        label: address.label || '',
        full_name: address.full_name || '',
        phone: address.phone || '',
        address_line_1: address.address_line_1 || '',
        address_line_2: address.address_line_2 || '',
        city: address.city || '',
        area: address.area || '',
        country: address.country || '',
        postal_code: address.postal_code || '',
        is_default: Boolean(address.is_default),
    });

    addressEditingId.value = address.id;
    showAddressForm.value = true;
}

function submitAddress() {
    if (
        !addressForm.full_name ||
        !addressForm.phone ||
        !addressForm.address_line_1 ||
        !addressForm.city ||
        !addressForm.country
    ) {
        return;
    }

    const payload = {
        id: addressEditingId.value || makeId('address'),
        label: addressForm.label || 'Address',
        full_name: addressForm.full_name,
        phone: addressForm.phone,
        address_line_1: addressForm.address_line_1,
        address_line_2: addressForm.address_line_2,
        city: addressForm.city,
        area: addressForm.area,
        country: addressForm.country,
        postal_code: addressForm.postal_code,
        is_default: Boolean(addressForm.is_default),
    };

    let nextAddresses = [...addresses.value];

    if (payload.is_default || nextAddresses.length === 0) {
        nextAddresses = nextAddresses.map((address) => ({
            ...address,
            is_default: false,
        }));

        payload.is_default = true;
    }

    if (addressEditingId.value) {
        nextAddresses = nextAddresses.map((address) => (
            address.id === addressEditingId.value ? payload : address
        ));
    } else {
        nextAddresses.unshift(payload);
    }

    addresses.value = nextAddresses;
    saveAddressesToStorage();
    resetAddressForm();
}

function deleteAddress(addressId) {
    const deletedWasDefault = addresses.value.find((address) => address.id === addressId)?.is_default;

    let nextAddresses = addresses.value.filter((address) => address.id !== addressId);

    if (deletedWasDefault && nextAddresses.length) {
        nextAddresses = nextAddresses.map((address, index) => ({
            ...address,
            is_default: index === 0,
        }));
    }

    addresses.value = nextAddresses;
    saveAddressesToStorage();
}

function setDefaultAddress(addressId) {
    addresses.value = addresses.value.map((address) => ({
        ...address,
        is_default: address.id === addressId,
    }));

    saveAddressesToStorage();
}

watch(storageUserKey, () => {
    loadStoredProfileData();
});

onMounted(() => {
    loadStoredProfileData();
});
</script>

<template>

    <Head :title="`Profile | ${brandName}`" />

    <StorefrontLayout :locale="locale" :is-rtl="isRtl" :settings="settings" :nav-pages="navPages" :footer="footer"
        :footer-pages="footerPages">
        <section class="bg-[#fef8f6] text-[#1d1b1a] antialiased">
            <div class="mx-auto w-full max-w-[76rem] px-4 py-7 sm:px-5 md:px-6 md:py-8 lg:px-8 lg:py-10">
                <div class="mb-7 text-center md:text-left">
                    <h1
                        class="mb-2 font-serif text-3xl font-medium leading-tight tracking-tight text-[#B85C50] md:text-[2.35rem]">
                        My Account
                    </h1>

                    <p class="text-[0.95rem] text-[#6d5651]">
                        Manage your details, saved addresses, and order history.
                    </p>
                </div>

                <div
                    class="grid min-h-[32rem] gap-8 md:grid-cols-[14rem_minmax(0,1fr)] md:items-start md:gap-8 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-10">
                    <aside
                        class="w-full md:sticky md:top-[6.25rem] md:self-start md:max-h-[calc(100vh-7rem)] md:overflow-y-auto md:pr-1">
                        <div
                            class="mb-8 flex flex-col items-center border-b border-[#d8c2bc]/40 pb-8 text-center md:hidden">
                            <h2 class="font-serif text-xl font-medium text-[#111111]">
                                {{ user.name || 'Guest User' }}
                            </h2>

                            <p class="mt-1 text-[0.85rem] text-[#6d5651]">
                                {{ user.email || 'guest@example.com' }}
                            </p>
                        </div>

                        <nav class="flex flex-col gap-2 rounded-xl bg-[#fef8f6] md:bg-transparent">
                            <button type="button"
                                class="group flex items-center justify-between rounded-lg border p-3.5 text-left transition-all"
                                :class="activeTab === 'account'
                                    ? 'border-[#d8c2bc]/50 bg-[#f7ebe8] shadow-sm'
                                    : 'border-transparent hover:bg-[#f7ebe8]/50'" @click="setActiveTab('account')">
                                <div class="flex min-w-0 items-center gap-3">
                                    <UserRound class="h-5 w-5 shrink-0 stroke-[1.5]" :class="activeTab === 'account'
                                        ? 'text-[#B85C50]'
                                        : 'text-[#6d5651] group-hover:text-[#B85C50]'" />

                                    <span class="truncate text-[0.9rem] font-bold"
                                        :class="activeTab === 'account' ? 'text-[#111111]' : 'text-[#53433f]'">
                                        Account Details
                                    </span>
                                </div>

                                <ChevronRight v-if="activeTab === 'account'" class="h-4 w-4 shrink-0 text-[#B85C50]" />
                            </button>

                            <button type="button"
                                class="group flex items-center justify-between rounded-lg border p-3.5 text-left transition-all"
                                :class="activeTab === 'orders'
                                    ? 'border-[#d8c2bc]/50 bg-[#f7ebe8] shadow-sm'
                                    : 'border-transparent hover:bg-[#f7ebe8]/50'" @click="setActiveTab('orders')">
                                <div class="flex min-w-0 items-center gap-3">
                                    <Package class="h-5 w-5 shrink-0 stroke-[1.5]" :class="activeTab === 'orders'
                                        ? 'text-[#B85C50]'
                                        : 'text-[#6d5651] group-hover:text-[#B85C50]'" />

                                    <span class="truncate text-[0.9rem] font-bold"
                                        :class="activeTab === 'orders' ? 'text-[#111111]' : 'text-[#53433f]'">
                                        Orders
                                    </span>
                                </div>

                                <ChevronRight v-if="activeTab === 'orders'" class="h-4 w-4 shrink-0 text-[#B85C50]" />
                            </button>

                            <button type="button"
                                class="group flex items-center justify-between rounded-lg border p-3.5 text-left transition-all"
                                :class="activeTab === 'addresses'
                                    ? 'border-[#d8c2bc]/50 bg-[#f7ebe8] shadow-sm'
                                    : 'border-transparent hover:bg-[#f7ebe8]/50'" @click="setActiveTab('addresses')">
                                <div class="flex min-w-0 items-center gap-3">
                                    <MapPin class="h-5 w-5 shrink-0 stroke-[1.5]" :class="activeTab === 'addresses'
                                        ? 'text-[#B85C50]'
                                        : 'text-[#6d5651] group-hover:text-[#B85C50]'" />

                                    <span class="truncate text-[0.9rem] font-bold"
                                        :class="activeTab === 'addresses' ? 'text-[#111111]' : 'text-[#53433f]'">
                                        Addresses
                                    </span>
                                </div>

                                <ChevronRight v-if="activeTab === 'addresses'"
                                    class="h-4 w-4 shrink-0 text-[#B85C50]" />
                            </button>

                            <div class="mt-4 border-t border-[#d8c2bc]/40 pt-4">
                                <Link href="/logout" method="post" as="button"
                                    class="group flex w-full items-center gap-3 p-3.5 text-[#53433f] transition-colors hover:text-[#dc2626]">
                                    <LogOut class="h-5 w-5 shrink-0 stroke-[1.5] group-hover:text-[#dc2626]" />

                                    <span class="truncate text-[0.9rem] font-bold">
                                        Sign Out
                                    </span>
                                </Link>
                            </div>
                        </nav>
                    </aside>

                    <main class="min-w-0">
                        <div v-if="activeTab === 'account'" class="space-y-6">
                            <section
                                class="custom-breeze-form rounded-xl border border-[#d8c2bc]/40 bg-white p-6 shadow-sm md:p-10">
                                <h2
                                    class="mb-6 border-b border-[#d8c2bc]/30 pb-4 font-serif text-[1.5rem] leading-tight text-[#B85C50]">
                                    Profile Information
                                </h2>

                                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status"
                                    class="max-w-xl" />
                            </section>

                            <section
                                class="custom-breeze-form rounded-xl border border-[#d8c2bc]/40 bg-white p-6 shadow-sm md:p-10">
                                <h2
                                    class="mb-6 border-b border-[#d8c2bc]/30 pb-4 font-serif text-[1.5rem] leading-tight text-[#B85C50]">
                                    Update Password
                                </h2>

                                <UpdatePasswordForm :has-password="hasPassword" :google-account="googleAccount"
                                    class="max-w-xl" />
                            </section>

                            <section
                                class="custom-breeze-form rounded-xl border border-[#d8c2bc]/40 bg-white p-6 shadow-sm md:p-10">
                                <h2
                                    class="mb-6 border-b border-[#d8c2bc]/30 pb-4 font-serif text-[1.5rem] leading-tight text-[#B85C50]">
                                    Delete Account
                                </h2>

                                <DeleteUserForm class="max-w-xl" />
                            </section>
                        </div>

                        <section v-if="activeTab === 'orders'"
                            class="rounded-xl border border-[#d8c2bc]/40 bg-white p-6 shadow-sm md:p-10">
                            <div class="mb-6 flex items-end justify-between border-b border-[#d8c2bc]/30 pb-4">
                                <h2 class="font-serif text-[1.5rem] leading-tight text-[#B85C50]">
                                    Recent Orders
                                </h2>

                                <Link :href="pathFor('shop')"
                                    class="border-b border-[#cf7467]/30 pb-0.5 text-[0.65rem] font-bold uppercase tracking-[0.15em] text-[#cf7467] transition-colors hover:border-[#B85C50] hover:text-[#B85C50]">
                                    Shop New
                                </Link>
                            </div>

                            <div class="space-y-4">
                                <article
                                    class="group flex flex-col items-start justify-between gap-4 rounded-lg border border-[#d8c2bc]/30 p-4 transition-all duration-300 hover:border-[#B85C50]/30 hover:bg-[#f7ebe8]/30 sm:flex-row sm:items-center">
                                    <div class="flex items-center gap-4">
                                        <div class="h-16 w-14 shrink-0 overflow-hidden rounded bg-[#f7ebe8]">
                                            <img src="/storefront/soleil/shop-product-3.jpg" alt="Order product"
                                                class="h-full w-full object-cover" />
                                        </div>

                                        <div>
                                            <p
                                                class="mb-1 text-[0.65rem] font-bold uppercase tracking-widest text-[#6d5651]">
                                                Order #SL-0942
                                            </p>

                                            <p class="text-[0.95rem] font-medium text-[#111111]">
                                                The Siena Pump - Terracotta
                                            </p>

                                            <p class="mt-1 text-[0.7rem] text-[#6d5651]">
                                                Oct 12, 2025
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex w-full flex-row items-center justify-between gap-2 sm:w-auto sm:flex-col sm:items-end">
                                        <span
                                            class="rounded-sm bg-[#e5f4dc] px-2.5 py-1 text-[0.6rem] font-extrabold uppercase tracking-widest text-[#2f6b23]">
                                            Delivered
                                        </span>

                                        <span class="text-[0.95rem] font-extrabold text-[#111111]">
                                            $295.00
                                        </span>
                                    </div>
                                </article>

                                <article
                                    class="group flex flex-col items-start justify-between gap-4 rounded-lg border border-[#d8c2bc]/30 p-4 transition-all duration-300 hover:border-[#B85C50]/30 hover:bg-[#f7ebe8]/30 sm:flex-row sm:items-center">
                                    <div class="flex items-center gap-4">
                                        <div class="h-16 w-14 shrink-0 overflow-hidden rounded bg-[#f7ebe8]">
                                            <img src="/storefront/soleil/shop-product-6.jpg" alt="Order product"
                                                class="h-full w-full object-cover opacity-80 grayscale transition-all duration-500 group-hover:opacity-100 group-hover:grayscale-0" />
                                        </div>

                                        <div>
                                            <p
                                                class="mb-1 text-[0.65rem] font-bold uppercase tracking-widest text-[#6d5651]">
                                                Order #SL-0811
                                            </p>

                                            <p class="text-[0.95rem] font-medium text-[#111111]">
                                                Chloe Block Heel - Blush
                                            </p>

                                            <p class="mt-1 text-[0.7rem] text-[#6d5651]">
                                                Aug 04, 2025
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex w-full flex-row items-center justify-between gap-2 sm:w-auto sm:flex-col sm:items-end">
                                        <span
                                            class="rounded-sm bg-[#f0b4ad]/30 px-2.5 py-1 text-[0.6rem] font-extrabold uppercase tracking-widest text-[#b42318]">
                                            Returned
                                        </span>

                                        <span class="text-[0.95rem] font-extrabold text-[#6d5651] line-through">
                                            $310.00
                                        </span>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section v-if="activeTab === 'addresses'"
                            class="rounded-xl border border-[#d8c2bc]/40 bg-white p-6 shadow-sm md:p-10">
                            <div
                                class="mb-6 flex flex-col gap-4 border-b border-[#d8c2bc]/30 pb-4 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h2 class="font-serif text-[1.5rem] leading-tight text-[#B85C50]">
                                        Saved Addresses
                                    </h2>

                                    <p class="mt-2 text-[0.85rem] leading-relaxed text-[#6d5651]">
                                        Add the delivery addresses you use most often.
                                    </p>
                                </div>

                                <button type="button"
                                    class="inline-flex items-center justify-center gap-2 rounded-sm bg-[#B85C50] px-4 py-3 text-[0.68rem] font-bold uppercase tracking-[0.15em] text-white transition-colors hover:bg-[#6f3727]"
                                    @click="openNewAddressForm">
                                    <Plus class="h-4 w-4 stroke-[1.7]" />
                                    Add Address
                                </button>
                            </div>

                            <form v-if="showAddressForm"
                                class="mb-6 grid gap-4 rounded-lg border border-[#d8c2bc]/40 bg-[#fef8f6] p-4 md:grid-cols-2"
                                @submit.prevent="submitAddress">
                                <label class="profile-field">
                                    <span>Label</span>
                                    <input v-model="addressForm.label" type="text" placeholder="Home, Work..." />
                                </label>

                                <label class="profile-field">
                                    <span>Full Name *</span>
                                    <input v-model="addressForm.full_name" type="text" required
                                        placeholder="Recipient name" />
                                </label>

                                <label class="profile-field">
                                    <span>Phone *</span>
                                    <input v-model="addressForm.phone" type="text" required placeholder="+961..." />
                                </label>

                                <label class="profile-field">
                                    <span>Country *</span>
                                    <input v-model="addressForm.country" type="text" required placeholder="Lebanon" />
                                </label>

                                <label class="profile-field">
                                    <span>City *</span>
                                    <input v-model="addressForm.city" type="text" required placeholder="Beirut" />
                                </label>

                                <label class="profile-field">
                                    <span>Area</span>
                                    <input v-model="addressForm.area" type="text" placeholder="Hamra, Achrafieh..." />
                                </label>

                                <label class="profile-field md:col-span-2">
                                    <span>Address Line 1 *</span>
                                    <input v-model="addressForm.address_line_1" type="text" required
                                        placeholder="Street, building, floor" />
                                </label>

                                <label class="profile-field">
                                    <span>Address Line 2</span>
                                    <input v-model="addressForm.address_line_2" type="text"
                                        placeholder="Apartment, landmark..." />
                                </label>

                                <label class="profile-field">
                                    <span>Postal Code</span>
                                    <input v-model="addressForm.postal_code" type="text" placeholder="Optional" />
                                </label>

                                <label
                                    class="flex items-center gap-2 text-[0.8rem] font-bold text-[#53433f] md:col-span-2">
                                    <input v-model="addressForm.is_default" type="checkbox"
                                        class="rounded border-[#d8c2bc] text-[#B85C50] focus:ring-[#B85C50]" />
                                    Set as default address
                                </label>

                                <div class="flex flex-col gap-2 sm:flex-row md:col-span-2">
                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-sm bg-[#B85C50] px-5 py-3 text-[0.68rem] font-bold uppercase tracking-[0.15em] text-white transition-colors hover:bg-[#6f3727]">
                                        {{ addressEditingId ? 'Update Address' : 'Save Address' }}
                                    </button>

                                    <button type="button"
                                        class="inline-flex items-center justify-center rounded-sm border border-[#d8c2bc] px-5 py-3 text-[0.68rem] font-bold uppercase tracking-[0.15em] text-[#B85C50] transition-colors hover:bg-[#f7ebe8]"
                                        @click="resetAddressForm">
                                        Cancel
                                    </button>
                                </div>
                            </form>

                            <div v-if="addresses.length" class="grid gap-4">
                                <article v-for="address in addresses" :key="address.id"
                                    class="rounded-lg border border-[#d8c2bc]/35 bg-[#fef8f6] p-4">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                                <h3 class="font-serif text-lg font-medium text-[#111111]">
                                                    {{ address.label || 'Address' }}
                                                </h3>

                                                <span v-if="address.is_default"
                                                    class="inline-flex items-center gap-1 rounded-full bg-[#e5f4dc] px-2.5 py-1 text-[0.58rem] font-extrabold uppercase tracking-widest text-[#2f6b23]">
                                                    <CheckCircle2 class="h-3 w-3" />
                                                    Default
                                                </span>
                                            </div>

                                            <p class="text-[0.95rem] font-bold text-[#111111]">
                                                {{ address.full_name }}
                                            </p>

                                            <p class="mt-1 text-[0.85rem] leading-relaxed text-[#6d5651]">
                                                {{ address.address_line_1 }}
                                                <template v-if="address.address_line_2">
                                                    , {{ address.address_line_2 }}
                                                </template>
                                                <br />
                                                <template v-if="address.area">
                                                    {{ address.area }},
                                                </template>
                                                {{ address.city }}, {{ address.country }}
                                                <template v-if="address.postal_code">
                                                    {{ address.postal_code }}
                                                </template>
                                            </p>

                                            <p class="mt-2 text-[0.82rem] font-bold text-[#B85C50]">
                                                {{ address.phone }}
                                            </p>
                                        </div>

                                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                                            <button v-if="!address.is_default" type="button"
                                                class="rounded-sm border border-[#d8c2bc] px-3 py-2 text-[0.62rem] font-bold uppercase tracking-[0.12em] text-[#B85C50] hover:bg-white"
                                                @click="setDefaultAddress(address.id)">
                                                Default
                                            </button>

                                            <button type="button"
                                                class="grid h-9 w-9 place-items-center rounded-full bg-white text-[#B85C50] shadow-sm transition-transform hover:-translate-y-0.5"
                                                aria-label="Edit address" @click="editAddress(address)">
                                                <Pencil class="h-4 w-4 stroke-[1.6]" />
                                            </button>

                                            <button type="button"
                                                class="grid h-9 w-9 place-items-center rounded-full bg-white text-[#dc2626] shadow-sm transition-transform hover:-translate-y-0.5"
                                                aria-label="Delete address" @click="deleteAddress(address.id)">
                                                <Trash2 class="h-4 w-4 stroke-[1.6]" />
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else
                                class="rounded-lg border border-dashed border-[#d8c2bc] bg-[#fef8f6] p-8 text-center">
                                <MapPin class="mx-auto h-8 w-8 text-[#B85C50]" />

                                <h3 class="mt-3 font-serif text-xl text-[#111111]">
                                    No saved addresses yet
                                </h3>

                                <p class="mx-auto mt-2 max-w-md text-[0.9rem] leading-relaxed text-[#6d5651]">
                                    Add your first address so checkout feels faster next time.
                                </p>
                            </div>
                        </section>
                    </main>
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>

<style scoped>
:deep(label) {
    font-size: 0.65rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.15em !important;
    color: #904b3d !important;
}

:deep(input[type="text"]),
:deep(input[type="email"]),
:deep(input[type="password"]),
:deep(select) {
    width: 100% !important;
    background: transparent !important;
    border: 0 !important;
    border-bottom: 1px solid #d8c2bc !important;
    padding: 0.5rem 0 !important;
    font-size: 0.95rem !important;
    color: #111111 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
}

:deep(input[type="text"]:focus),
:deep(input[type="email"]:focus),
:deep(input[type="password"]:focus),
:deep(select:focus) {
    border-bottom-color: #B85C50 !important;
    box-shadow: none !important;
    outline: none !important;
}

:deep(.inline-flex.items-center.px-4.py-2.bg-gray-800),
:deep(button[type="submit"]) {
    background-color: #B85C50 !important;
    color: white !important;
    border-radius: 2px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.15em !important;
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    padding: 0.75rem 1.5rem !important;
    transition: background-color 0.3s !important;
}

:deep(.inline-flex.items-center.px-4.py-2.bg-gray-800:hover),
:deep(button[type="submit"]:hover) {
    background-color: #6f3727 !important;
}

:deep(.text-gray-600) {
    color: #6d5651 !important;
}

:deep(.text-gray-900) {
    color: #1d1b1a !important;
}

:deep(.text-sm.text-gray-600) {
    color: #6d5651 !important;
}

:deep(.mt-2.text-sm.text-green-600) {
    color: #2f6b23 !important;
}

.profile-field {
    display: grid;
    gap: 0.4rem;
}

.profile-field span {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: #904b3d;
}

.profile-field input,
.profile-field select {
    width: 100%;
    border: 0;
    border-bottom: 1px solid #d8c2bc;
    background: transparent;
    padding: 0.5rem 0;
    color: #111111;
    font-size: 0.95rem;
    outline: none;
}

.profile-field input:focus,
.profile-field select:focus {
    border-bottom-color: #B85C50;
}
</style>
