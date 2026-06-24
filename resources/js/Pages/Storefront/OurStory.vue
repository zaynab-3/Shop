<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
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

function pathFor(path = '') {
    const [basePath, query = ''] = String(path).replace(/^\/+/, '').split('?');
    const prefix = props.locale === 'en' ? '' : `/${props.locale}`;
    const url = `${prefix}/${basePath}`.replace(/\/$/, '') || '/';
    return query ? `${url}?${query}` : url;
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);

        // 1. Hero Section Animation (Fade & Swipe Up on Entry/Exit)
        gsap.fromTo(
            '.gsap-hero',
            { y: 50, autoAlpha: 0 },
            {
                y: 0,
                autoAlpha: 1,
                duration: 1.2,
                stagger: 0.15,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: '.gsap-hero-section',
                    start: 'top 85%',
                    end: 'bottom 15%',
                    // play on enter, reverse on leave, play on re-enter back, reverse on leave back
                    toggleActions: 'play reverse play reverse'
                }
            }
        );

        // 2. Story Blocks Animation (Swipe Left/Right & Fade on Entry/Exit)
        const storyBlocks = document.querySelectorAll('.gsap-story-block');

        storyBlocks.forEach((block) => {
            const slideLeftEl = block.querySelector('.slide-from-left');
            const slideRightEl = block.querySelector('.slide-from-right');

            // Set initial states so they are pushed out to the sides
            gsap.set(slideLeftEl, { x: -80, autoAlpha: 0 });
            gsap.set(slideRightEl, { x: 80, autoAlpha: 0 });

            const tl = gsap.timeline({
                scrollTrigger: {
                    trigger: block,
                    start: 'top 80%',
                    end: 'bottom 20%',
                    // This triggers the fade show and fade exit based on viewport position
                    toggleActions: 'play reverse play reverse'
                }
            });

            // Animate both elements pulling inward to their natural positions
            tl.to(slideLeftEl, { x: 0, autoAlpha: 1, duration: 1.4, ease: 'power3.out' }, 0)
                .to(slideRightEl, { x: 0, autoAlpha: 1, duration: 1.4, ease: 'power3.out' }, 0.15); // 0.15s stagger
        });
    }
});
</script>

<template>

    <Head title="Our Story | SCARBINA" />

    <StorefrontLayout :locale="locale" :is-rtl="isRtl" :settings="settings" :nav-pages="navPages" :footer="footer"
        :footer-pages="footerPages">
        <div
            class="bg-[#F8F7F4] text-[#111111] min-h-screen selection:bg-[#111111] selection:text-[#F8F7F4] overflow-x-hidden">

            <main
                class="flex-grow pt-[100px] md:pt-[120px] pb-[80px] md:pb-[140px] px-4 md:px-8 max-w-[88rem] mx-auto w-full">

                <section
                    class="gsap-hero-section mb-[80px] md:mb-[140px] text-center mx-auto flex flex-col items-center max-w-3xl">
                    <span
                        class="gsap-hero text-[0.65rem] font-extrabold uppercase tracking-[0.2em] text-[#111111]/50 mb-5">
                        Our Heritage
                    </span>
                    <h1
                        class="gsap-hero font-serif text-[2.5rem] md:text-[3.75rem] lg:text-[4.5rem] leading-[1.05] text-[#111111] mb-6 tracking-tight font-medium">
                        Crafted in Tuscany, <br class="hidden md:block" />
                        <span class="italic font-light text-[#111111]/70">Worn Everywhere.</span>
                    </h1>
                    <p
                        class="gsap-hero font-sans text-[0.9rem] md:text-[1rem] leading-[1.8] text-[#111111]/70 max-w-2xl">
                        Founded on the belief that luxury should be felt as much as it is seen. Scarbina marries
                        generations of Italian artisanry with modern, breathable design. We source the finest,
                        sun-drenched leathers to create silhouettes that anchor the contemporary wardrobe.
                    </p>
                </section>

                <section
                    class="gsap-story-block grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-0 mb-[80px] md:mb-[160px] items-center">

                    <div
                        class="slide-from-left lg:col-span-6 lg:col-start-1 relative w-full h-[400px] md:h-[450px] lg:h-[550px] bg-[#E8A39A]/20 rounded-sm overflow-hidden group order-1 lg:order-1">
                        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBO9DvpJdp2US2TdS_H6GiWx0wRmyQGzR6_USDhIni4UEQTPz0CNgIXPPh8-FMLgHk7YvuBIMXJJJNyXCvX5hEY856Hr-ucn9YYkOTX2jpWTahmy___wfZEcIiMm5iYXX-wbuff56RzXMxP3igk4tVmc-8S4DV6ABICWs8HwN2dv98R-AntHcdhMbdoMvWpaHWH3Gmz2Q3xmWgJ9K_Nji3pHDwU0eFB4ZlsM0Hssq3UuZoOGF-3cZiJi9bw-m_1xkMBUGyCG6Acl9Q-"
                            alt="Artisan hands working on leather"
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-[2s] ease-out group-hover:scale-105"
                            loading="lazy" />
                        <div
                            class="absolute inset-0 bg-[#111111]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 mix-blend-overlay">
                        </div>
                    </div>

                    <div
                        class="slide-from-right lg:col-span-5 lg:col-start-8 flex flex-col justify-center order-2 lg:order-2 px-2 md:px-0">
                        <h2
                            class="font-serif text-[1.85rem] lg:text-[2.5rem] leading-[1.15] text-[#111111] mb-5 font-medium">
                            The Artisan Touch
                        </h2>
                        <p class="font-sans text-[0.95rem] leading-[1.8] text-[#111111]/70 mb-8">
                            Every pair of Scarbina shoes passes through the hands of master shoemakers in our Florence
                            atelier. We eschew mass production in favor of mindful creation, ensuring each stitch and
                            curve meets our exacting standards of fresh, out-of-the-box luxury.
                        </p>

                        <div class="w-12 h-px bg-[#111111]/20 mb-5"></div>
                        <p class="text-[0.65rem] font-extrabold uppercase tracking-[0.15em] text-[#111111]/50">
                            Over 50 meticulous steps per shoe.
                        </p>
                    </div>

                </section>

                <section class="gsap-story-block grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-0 items-center">

                    <div
                        class="slide-from-right lg:col-span-6 lg:col-start-7 relative w-full h-[400px] md:h-[450px] lg:h-[550px] bg-[#D8CEC4]/30 rounded-sm overflow-hidden group order-1 lg:order-2">
                        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuD997DU8BoakRImuPUsvg2Ue81TIzULdbc4BLcyIw190K1gV_nmsRXK-S4hsrDi-mCxnwbjX6abYfZinoS1xuFt6BMzqpKUPTLkGmQkpUBXMxlLCXs-AAjE3R3qxm4f0fTbsX22ZS6A24dWrPhjACms7VS0BENVoCLYxIVG1NpX2YCnSQlzejCQWAaMJesURPU8XuTaTbJwgmQewZxj-GdtO-cfNnjId5maaHNohSyqE_vqy05H99-i4bnY_JUBzg3G-QkB_xzkg4sT"
                            alt="Premium materials in warm tones"
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-[2s] ease-out group-hover:scale-105"
                            loading="lazy" />
                        <div
                            class="absolute inset-0 bg-[#E8A39A]/10 mix-blend-multiply opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <div
                        class="slide-from-left lg:col-span-5 lg:col-start-1 flex flex-col justify-center order-2 lg:order-1 px-2 md:px-0">
                        <h2
                            class="font-serif text-[1.85rem] lg:text-[2.5rem] leading-[1.15] text-[#111111] mb-5 font-medium">
                            Sun-Drenched Tones
                        </h2>
                        <p class="font-sans text-[0.95rem] leading-[1.8] text-[#111111]/70 mb-8">
                            Our color palette is a love letter to the Mediterranean sun. We dye our materials using
                            techniques that yield warm, luminous shades—from terracotta to rich cream—designed to
                            complement the skin and elevate minimal dressing.
                        </p>

                        <Link :href="pathFor('shop')"
                            class="inline-flex items-center gap-3 text-[0.75rem] font-extrabold uppercase tracking-[0.15em] text-[#111111] border-b border-[#111111] pb-1.5 hover:text-[#111111]/50 hover:border-[#111111]/50 transition-colors self-start">
                            Explore the Collection
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </Link>
                    </div>

                </section>

            </main>
        </div>
    </StorefrontLayout>
</template>