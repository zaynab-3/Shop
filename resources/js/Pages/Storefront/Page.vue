<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    locale: { type: String, default: 'en' },
    isRtl: { type: Boolean, default: false },
    settings: { type: Object, default: () => ({}) },
    navPages: { type: Array, default: () => [] },
    footer: { type: Object, default: null },
    footerPages: { type: Array, default: () => [] },
    page: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head :title="page.title ? `${page.title} | SCARBINA` : 'SCARBINA'" />

    <StorefrontLayout :locale="locale" :is-rtl="isRtl" :settings="settings" :nav-pages="navPages" :footer="footer" :footer-pages="footerPages">
        <div class="bg-[#F8F7F4] text-[#111111] min-h-screen w-full">
            <main class="flex-grow pt-[100px] md:pt-[120px] pb-[80px] md:pb-[140px] px-4 md:px-8 max-w-[800px] mx-auto w-full">
                <article class="bg-white p-8 md:p-12 rounded-[1rem] shadow-[0_8px_32px_rgba(207,116,103,0.03)] border border-[#cf7467]/10">
                    <header class="border-b border-[#cf7467]/10 pb-8 mb-10 text-center">
                        <h1 class="font-serif text-[2.2rem] md:text-[3rem] leading-tight text-[#CF7467] font-medium">{{ page.title }}</h1>
                    </header>

                    <div class="font-sans text-[0.95rem] leading-[1.8] text-[#6d5651] space-y-6" v-html="page.content">
                    </div>
                </article>
            </main>
        </div>
    </StorefrontLayout>
</template>
