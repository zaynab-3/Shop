<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const props = defineProps({
    locale: { type: String, default: 'en' },
    isRtl: { type: Boolean, default: false },
    settings: { type: Object, default: () => ({}) },
    navPages: { type: Array, default: () => [] },
    footer: { type: Object, default: null },
    footerPages: { type: Array, default: () => [] },
});

onMounted(() => {
    if (typeof window === 'undefined') return;
    gsap.registerPlugin(ScrollTrigger);

    // ── Sticky sidebar header: sweeps in from left with gentle ease ───
    gsap.fromTo('.pp-header',
        { x: -48, autoAlpha: 0 },
        { x: 0, autoAlpha: 1, duration: 1.1, ease: 'power3.out', delay: 0.1 }
    );

    // ── Each content section: clips up from below one by one ─────────
    gsap.utils.toArray('.pp-section').forEach((el, i) => {
        gsap.fromTo(el,
            { y: 36, autoAlpha: 0 },
            {
                y: 0, autoAlpha: 1, duration: 0.85, ease: 'power2.out',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 90%',
                    end: 'bottom 8%',
                    toggleActions: 'play reverse play reverse',
                },
                delay: i * 0.05,
            }
        );
    });

    // ── CTA / contact block: scale-in with bounce ──────────────────
    gsap.fromTo('.pp-cta',
        { scale: 0.93, autoAlpha: 0 },
        {
            scale: 1, autoAlpha: 1, duration: 0.9, ease: 'back.out(1.6)',
            scrollTrigger: {
                trigger: '.pp-cta',
                start: 'top 88%',
                end: 'bottom 5%',
                toggleActions: 'play reverse play reverse',
            }
        }
    );
});

onBeforeUnmount(() => {
    ScrollTrigger.getAll().forEach(t => t.kill());
});
</script>

<template>

    <Head title="Privacy Policy | SCARBINA" />

    <StorefrontLayout :locale="locale" :is-rtl="isRtl" :settings="settings" :nav-pages="navPages" :footer="footer"
        :footer-pages="footerPages">
        <div class="bg-[#fef8f6] text-[#1d1b1a] min-h-screen w-full selection:bg-[#1d1b1a] selection:text-[#fef8f6]">

            <main class="flex-grow pt-10 md:pt-12 pb-[80px] md:pb-[140px] px-5 md:px-8 max-w-[88rem] mx-auto w-full">

                <article class="grid grid-cols-1 md:grid-cols-12 gap-y-12 md:gap-x-8 lg:gap-x-12 items-start">

                    <!-- ── Sticky sidebar header ──────────────────────────────── -->
                    <header class="pp-header md:col-span-4 lg:col-span-3 md:sticky md:top-[120px] border-b md:border-b-0 border-[#1d1b1a]/10 pb-8 md:pb-0">

                        <!-- Salmon eyebrow tag -->
                        <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.22em] text-[#CF7467] mb-3">
                            Legal Information
                        </p>

                        <!-- H1 in black -->
                        <h1 class="font-serif text-[2.5rem] md:text-[3rem] lg:text-[3.5rem] leading-[1.05] text-[#1d1b1a] font-medium tracking-tight mb-5">
                            Privacy Policy
                        </h1>

                        <p class="font-sans text-[0.85rem] text-[#1d1b1a]/50 italic mb-8">
                            Last Updated: October 24, 2024
                        </p>

                        <!-- Brand tagline — desktop only, below a soft salmon rule -->
                        <div class="hidden md:block border-t border-[#CF7467]/20 pt-6">
                            <p class="font-serif text-[1.05rem] leading-[1.55] text-[#1d1b1a]/75">
                                Crafted in Tuscany,<br />
                                <span class="text-[#CF7467] italic font-light">Worn Everywhere.</span>
                            </p>
                        </div>
                    </header>

                    <!-- ── Content column ─────────────────────────────────────── -->
                    <div class="md:col-span-8 lg:col-span-7 lg:col-start-5 space-y-12 md:space-y-16">

                        <!-- Intro -->
                        <section class="pp-section">
                            <p class="font-sans text-[0.95rem] md:text-[1rem] leading-[1.8] text-[#1d1b1a]/65">
                                At Scarbina, we curate experiences as carefully as we craft our footwear. This Privacy
                                Policy details our commitment to safeguarding the personal information you entrust to us
                                while interacting with our digital boutique. We believe transparency is the foundation
                                of luxury service.
                            </p>
                        </section>

                        <!-- Information Collection -->
                        <section class="pp-section">
                            <h2 class="font-serif text-[1.5rem] md:text-[1.85rem] text-[#1d1b1a] font-medium mb-5">
                                Information Collection
                            </h2>
                            <p class="font-sans text-[0.95rem] md:text-[1rem] leading-[1.8] text-[#1d1b1a]/65 mb-6">
                                We collect personal information necessary to elevate your shopping experience. This
                                includes information you directly provide when creating an account, subscribing to our
                                editorial newsletter, or completing a transaction.
                            </p>
                            <ul class="list-none space-y-4 pl-0 text-[#1d1b1a]/65 font-sans text-[0.95rem] leading-[1.8]">
                                <li class="flex items-start">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#CF7467]/60 mt-2.5 mr-4 flex-shrink-0"></span>
                                    <span><strong class="text-[#1d1b1a] font-bold">Contact Information:</strong> Your
                                        name, email address, shipping and billing details.</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#CF7467]/60 mt-2.5 mr-4 flex-shrink-0"></span>
                                    <span><strong class="text-[#1d1b1a] font-bold">Transaction History:</strong> Records
                                        of the collections and styles you acquire.</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#CF7467]/60 mt-2.5 mr-4 flex-shrink-0"></span>
                                    <span><strong class="text-[#1d1b1a] font-bold">Digital Footprint:</strong>
                                        Navigational data gathered to refine our website's editorial flow.</span>
                                </li>
                            </ul>
                        </section>

                        <!-- Use of Data -->
                        <section class="pp-section">
                            <h2 class="font-serif text-[1.5rem] md:text-[1.85rem] text-[#1d1b1a] font-medium mb-5">
                                Use of Data
                            </h2>
                            <p class="font-sans text-[0.95rem] md:text-[1rem] leading-[1.8] text-[#1d1b1a]/65">
                                The information we gather is utilized exclusively to provide a bespoke and seamless
                                service. Your data empowers us to process orders efficiently, arrange prompt deliveries,
                                and anticipate your preferences for future collections. We may also use this information
                                to communicate invitations to exclusive previews or to share editorial content that
                                aligns with your aesthetic sensibilities, provided you have opted in.
                            </p>
                        </section>

                        <!-- Security & Discretion -->
                        <section class="pp-section">
                            <h2 class="font-serif text-[1.5rem] md:text-[1.85rem] text-[#1d1b1a] font-medium mb-5">
                                Security &amp; Discretion
                            </h2>
                            <p class="font-sans text-[0.95rem] md:text-[1rem] leading-[1.8] text-[#1d1b1a]/65">
                                We employ rigorous, industry-standard security protocols to protect your personal
                                information against unauthorized access, alteration, or disclosure. Your data is
                                processed within secure environments, mirroring the care we take in crafting our
                                products. We categorically do not sell or rent your personal information to third-party
                                entities. It is shared only with trusted partners necessary to fulfill our service
                                commitments to you (such as premier logistics providers).
                            </p>
                        </section>

                        <!-- Inquiries card — salmon-tinted background -->
                        <section class="pp-section p-8 md:p-10 bg-[#CF7467]/[0.05] border border-[#CF7467]/15 rounded-sm">
                            <h3 class="font-sans text-[0.7rem] font-extrabold uppercase tracking-[0.15em] text-[#CF7467] mb-4">
                                Inquiries
                            </h3>
                            <p class="font-sans text-[0.95rem] leading-[1.8] text-[#1d1b1a]/65">
                                Should you require clarification regarding our privacy practices or wish to update your
                                preferences, our concierge team is at your disposal. Please direct correspondence to <a
                                    class="text-[#CF7467] hover:text-[#B85C50] border-b border-[#CF7467]/40 hover:border-[#B85C50] transition-colors font-bold pb-0.5"
                                    href="mailto:concierge@scarbina.com">concierge@scarbina.com</a>.
                            </p>
                        </section>

                        <!-- CTA -->
                        <div class="pp-cta mt-16 md:mt-20 pt-10 border-t border-[#1d1b1a]/10">
                            <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.22em] text-[#CF7467] mb-6">
                                Have Questions?
                            </p>
                            <a :href="`https://wa.me/${settings.whatsapp_number?.replace(/[^0-9]/g, '') || '96100000000'}?text=Hi!%20I%20have%20questions%20about%20your%20privacy%20policy.`"
                                target="_blank" rel="noreferrer"
                                class="inline-flex items-center gap-3 text-[0.75rem] font-extrabold uppercase tracking-[0.15em] text-[#1d1b1a] border-b border-[#1d1b1a] pb-1.5 hover:text-[#CF7467] hover:border-[#CF7467] transition-colors">
                                Contact Support
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </a>
                        </div>

                    </div>
                </article>

            </main>
        </div>
    </StorefrontLayout>
</template>