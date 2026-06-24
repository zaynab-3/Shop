import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import gsap from 'gsap';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'SCARBINA';
const routeMotionMinMs = 360;
let routeMotionActive = false;
let routeMotionStartedAt = 0;
let routeMotionHideTimer = null;
let routeMotionTimeline = null;
let pagePrimedForEntrance = false;

const canAnimate = () =>
    typeof window !== 'undefined'
    && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const getPageSurface = () => (
    document.querySelector('.storefront-main')
    || document.querySelector('.admin-content')
    || document.querySelector('#app main')
    || document.querySelector('#app')
);

const getPageTargets = () => {
    const surface = getPageSurface();
    if (!surface) return [];

    const children = [...surface.children].filter((child) => !child.classList.contains('page-transition-layer'));
    return children.length ? children : [surface];
};

const shouldAnimateVisit = (event) => {
    const visit = event?.detail?.visit;
    if (!visit) return true;

    const method = String(visit.method || 'get').toLowerCase();
    if (method !== 'get') return false;

    // Filter/search/pagination visits already preserve the current surface, so keep them snappy.
    if (visit.preserveScroll || visit.preserveState) return false;
    if (visit.only?.length || visit.except?.length) return false;

    return true;
};

const ensureTransitionLayer = () => {
    let layer = document.querySelector('.page-transition-layer');

    if (layer) return layer;

    layer = document.createElement('div');
    layer.className = 'page-transition-layer';
    layer.setAttribute('aria-hidden', 'true');
    layer.innerHTML = `
        <div class="page-transition-panel">
            <span class="page-transition-brand">SCARBINA</span>
            <span class="page-transition-line"></span>
        </div>
    `;
    document.body.appendChild(layer);

    return layer;
};

const animatePageOut = () => {
    if (!canAnimate()) return;

    const targets = getPageTargets();
    if (!targets.length) return;

    gsap.killTweensOf(targets);
    gsap.to(targets, {
        autoAlpha: 0.18,
        y: -12,
        scale: 0.992,
        filter: 'blur(7px)',
        duration: 0.24,
        ease: 'power2.out',
        stagger: { each: 0.018, from: 'start' },
        overwrite: true,
    });
};

const entranceState = { autoAlpha: 0, y: 24, scale: 0.992, filter: 'blur(10px)' };

const primePageForEntrance = () => {
    if (!canAnimate() || !routeMotionActive) return;

    const targets = getPageTargets();
    if (!targets.length) return;

    gsap.killTweensOf(targets);
    gsap.set(targets, entranceState);
    pagePrimedForEntrance = true;
};

const animatePageIn = () => {
    if (!canAnimate()) return;

    const targets = getPageTargets();
    if (!targets.length) return;

    gsap.killTweensOf(targets);

    if (!pagePrimedForEntrance) {
        gsap.set(targets, entranceState);
    }

    gsap.to(targets, {
        autoAlpha: 1,
        y: 0,
        scale: 1,
        filter: 'blur(0px)',
        duration: 0.64,
        ease: 'power3.out',
        stagger: { each: 0.035, from: 'start' },
        clearProps: 'opacity,visibility,transform,filter',
        onComplete: () => {
            pagePrimedForEntrance = false;
        },
    });
};

const showPageTransition = (event) => {
    if (!canAnimate() || !shouldAnimateVisit(event)) return;

    const layer = ensureTransitionLayer();
    const panel = layer.querySelector('.page-transition-panel');
    const line = layer.querySelector('.page-transition-line');

    clearTimeout(routeMotionHideTimer);
    routeMotionActive = true;
    pagePrimedForEntrance = false;
    routeMotionStartedAt = performance.now();
    document.documentElement.classList.add('is-routing');

    animatePageOut();
    gsap.killTweensOf([layer, panel, line]);
    routeMotionTimeline?.kill();

    gsap.set(layer, { display: 'grid', pointerEvents: 'auto' });
    gsap.set(line, { scaleX: 0, transformOrigin: 'left center' });

    routeMotionTimeline = gsap.timeline()
        .to(layer, { autoAlpha: 1, duration: 0.18, ease: 'power2.out' }, 0)
        .fromTo(
            panel,
            { autoAlpha: 0, y: -10, scale: 0.98 },
            { autoAlpha: 1, y: 0, scale: 1, duration: 0.28, ease: 'power3.out' },
            0.02,
        )
        .to(line, { scaleX: 0.72, duration: 0.42, ease: 'power3.out' }, 0.08)
        .to(line, { scaleX: 0.9, duration: 0.9, ease: 'sine.inOut', repeat: -1, yoyo: true }, 0.5);
};

const hidePageTransition = () => {
    if (!canAnimate() || !routeMotionActive) return;

    const elapsed = performance.now() - routeMotionStartedAt;
    const wait = Math.max(0, routeMotionMinMs - elapsed);

    clearTimeout(routeMotionHideTimer);
    routeMotionHideTimer = window.setTimeout(() => {
        const layer = ensureTransitionLayer();
        const panel = layer.querySelector('.page-transition-panel');
        const line = layer.querySelector('.page-transition-line');

        routeMotionTimeline?.kill();
        gsap.killTweensOf([layer, panel, line]);
        primePageForEntrance();

        gsap.timeline({
            onStart: animatePageIn,
            onComplete: () => {
                gsap.set(layer, { display: 'none', pointerEvents: 'none' });
                document.documentElement.classList.remove('is-routing');
                routeMotionActive = false;
            },
        })
            .to(line, { scaleX: 1, duration: 0.16, ease: 'power2.out' }, 0)
            .to(panel, { autoAlpha: 0, y: -8, scale: 0.985, duration: 0.2, ease: 'power2.in' }, 0.06)
            .to(layer, { autoAlpha: 0, duration: 0.34, ease: 'power2.out' }, 0.1);
    }, wait);
};

router.on('start', showPageTransition);
router.on('navigate', () => window.requestAnimationFrame(primePageForEntrance));
router.on('finish', () => {
    primePageForEntrance();
    window.requestAnimationFrame(hidePageTransition);
});

const initInertia = () => {
    const el = document.getElementById('app');
    if (!el || !el.dataset.page) return;
    const page = JSON.parse(el.dataset.page);

    createInertiaApp({
        page,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob('./Pages/**/*.vue'),
            ),
        setup({ el, App, props, plugin }) {
            const vueApp = createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue)
                .mount(el);

            window.requestAnimationFrame(animatePageIn);

            return vueApp;
        },
        progress: false,
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initInertia);
} else {
    initInertia();
}