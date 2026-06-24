<template>
    <div v-if="mounted" ref="overlayEl" class="loader-overlay">
        <div class="loader-glow"></div>

        <div ref="logoWrapEl" class="loader-logo-wrap" v-html="svgMarkup"></div>

        <button type="button" class="skip-btn" @click="skip">
            Skip
        </button>
    </div>
</template>

<script setup>
import gsap from 'gsap';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    logoSrc: { type: String, default: '/logo.svg' },
    targetSelector: { type: String, default: '#site-header-logo' },
    drawDuration: { type: Number, default: 2.05 },
    fillDuration: { type: Number, default: 0.42 },
    holdDuration: { type: Number, default: 0.18 },
    morphDuration: { type: Number, default: 0.82 },
});

const emit = defineEmits(['landed', 'done']);

const mounted = ref(true);
const overlayEl = ref(null);
const logoWrapEl = ref(null);
const svgMarkup = ref('');

let timeline = null;
let landedEmitted = false;
let doneEmitted = false;

function emitLanded() {
    if (landedEmitted) return;

    landedEmitted = true;
    emit('landed');
}

function emitDone() {
    if (doneEmitted) return;

    doneEmitted = true;
    mounted.value = false;
    emit('done');
}

async function loadSvg() {
    try {
        const response = await fetch(props.logoSrc, { cache: 'force-cache' });

        if (!response.ok) {
            throw new Error('Could not load logo SVG.');
        }

        svgMarkup.value = await response.text();
    } catch (error) {
        svgMarkup.value = `
            <img
                src="${props.logoSrc}"
                alt="Logo"
                class="loader-logo-img"
            />
        `;
    }
}

function prepareSvgForDrawing() {
    const wrapper = logoWrapEl.value;

    if (!wrapper) return [];

    const svg = wrapper.querySelector('svg');

    if (!svg) return [];

    svg.classList.add('loader-logo-svg');
    svg.removeAttribute('width');
    svg.removeAttribute('height');
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

    const drawableNodes = Array.from(
        svg.querySelectorAll('path, circle, ellipse, line, polyline, polygon, rect'),
    ).filter((node) => typeof node.getTotalLength === 'function');

    drawableNodes.forEach((node) => {
        const length = Math.ceil(node.getTotalLength());

        gsap.set(node, {
            fill: 'transparent',
            stroke: '#111111',
            strokeWidth: node.getAttribute('stroke-width') || 3,
            strokeLinecap: 'round',
            strokeLinejoin: 'round',
            strokeDasharray: length,
            strokeDashoffset: length,
            opacity: 1,
        });
    });

    return drawableNodes;
}

function getMorphValues() {
    const logo = logoWrapEl.value;
    const target = document.querySelector(props.targetSelector);

    if (!logo || !target) return null;

    const logoRect = logo.getBoundingClientRect();
    const targetRect = target.getBoundingClientRect();

    if (!logoRect.width || !logoRect.height || !targetRect.width || !targetRect.height) {
        return null;
    }

    const logoCenterX = logoRect.left + logoRect.width / 2;
    const logoCenterY = logoRect.top + logoRect.height / 2;
    const targetCenterX = targetRect.left + targetRect.width / 2;
    const targetCenterY = targetRect.top + targetRect.height / 2;

    return {
        x: targetCenterX - logoCenterX,
        y: targetCenterY - logoCenterY,
        scale: Math.min(targetRect.width / logoRect.width, targetRect.height / logoRect.height),
    };
}

function startFallbackAnimation() {
    timeline = gsap.timeline({
        onComplete: emitDone,
    });

    timeline
        .set(overlayEl.value, { autoAlpha: 1 })
        .fromTo(
            logoWrapEl.value,
            { autoAlpha: 0, scale: 0.9, filter: 'blur(8px)' },
            { autoAlpha: 1, scale: 1, filter: 'blur(0px)', duration: 0.35, ease: 'power3.out' },
            0,
        )
        .to({}, { duration: 0.35 })
        .add(() => emitLanded())
        .to(overlayEl.value, {
            autoAlpha: 0,
            duration: 0.3,
            ease: 'power2.inOut',
        });
}

function startSequence() {
    const overlay = overlayEl.value;
    const logo = logoWrapEl.value;

    if (!overlay || !logo) return;

    const paths = prepareSvgForDrawing();

    gsap.set(overlay, { autoAlpha: 0 });
    gsap.set(logo, { autoAlpha: 0, scale: 0.92, filter: 'blur(8px)' });

    if (!paths.length) {
        startFallbackAnimation();
        return;
    }

    const morphValues = getMorphValues();

    timeline = gsap.timeline({
        defaults: { ease: 'power3.out' },
        onComplete: emitDone,
    });

    timeline
        .to(
            overlay,
            {
                autoAlpha: 1,
                duration: 0.16,
                ease: 'power1.out',
            },
            0,
        )
        .to(
            logo,
            {
                autoAlpha: 1,
                scale: 1,
                filter: 'blur(0px)',
                duration: 0.32,
                ease: 'power3.out',
            },
            0,
        )
        .to(
            paths,
            {
                strokeDashoffset: 0,
                duration: props.drawDuration,
                ease: 'power2.inOut',
                stagger: {
                    each: 0.008,
                    from: 'start',
                },
            },
            0.04,
        )
        .to(
            paths,
            {
                fill: '#111111',
                stroke: '#111111',
                duration: props.fillDuration,
                ease: 'power2.out',
            },
            `>-=0.08`,
        )
        .to({}, { duration: props.holdDuration });

    if (morphValues) {
        timeline
            .to(
                logo,
                {
                    x: morphValues.x,
                    y: morphValues.y,
                    scale: morphValues.scale,
                    duration: props.morphDuration,
                    ease: 'expo.inOut',
                    transformOrigin: 'center center',
                },
                'handoff',
            )
            .to(
                overlay,
                {
                    backgroundColor: 'rgba(248, 247, 244, 0)',
                    duration: props.morphDuration,
                    ease: 'expo.inOut',
                },
                'handoff',
            )
            .to(
                logo,
                {
                    autoAlpha: 0,
                    duration: 0.1,
                    ease: 'power1.out',
                },
                'handoff+=0.78',
            )
            .add(() => emitLanded(), 'handoff+=0.8')
            .to(
                overlay,
                {
                    autoAlpha: 0,
                    duration: 0.2,
                    ease: 'power1.out',
                },
                'handoff+=0.82',
            );
    } else {
        timeline
            .add(() => emitLanded())
            .to(overlay, {
                autoAlpha: 0,
                duration: 0.35,
                ease: 'power2.inOut',
            });
    }
}

function skip() {
    if (timeline) {
        timeline.kill();
    }

    emitLanded();

    gsap.to(overlayEl.value, {
        autoAlpha: 0,
        duration: 0.18,
        ease: 'power2.out',
        onComplete: emitDone,
    });
}

onMounted(async () => {
    await loadSvg();
    await nextTick();

    requestAnimationFrame(() => {
        startSequence();
    });
});

onBeforeUnmount(() => {
    if (timeline) {
        timeline.kill();
    }
});
</script>

<style scoped>
.loader-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: grid;
    place-items: center;
    overflow: hidden;
    background: #f8f7f4;
    opacity: 0;
    visibility: hidden;
}

.loader-glow {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(circle at 50% 48%, rgb(207 116 103 / 0.08), transparent 26%),
        radial-gradient(circle at 28% 18%, rgb(159 79 71 / 0.06), transparent 30%),
        radial-gradient(circle at 76% 78%, rgb(159 79 71 / 0.06), transparent 34%);
}

.loader-logo-wrap {
    position: relative;
    z-index: 2;
    width: min(13rem, 45vw);
    aspect-ratio: 1 / 1.1;
    transform-origin: center center;
    will-change: transform, opacity, filter;
}

:deep(.loader-logo-svg),
:deep(svg) {
    display: block;
    width: 100%;
    height: 100%;
    overflow: visible;
    background: transparent !important;
}

:deep(path),
:deep(circle),
:deep(ellipse),
:deep(line),
:deep(polyline),
:deep(polygon),
:deep(rect) {
    vector-effect: non-scaling-stroke;
}

:deep(.loader-logo-img) {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: transparent !important;
}

.skip-btn {
    position: absolute;
    right: 1.5rem;
    bottom: 1.5rem;
    z-index: 3;
    border: 1px solid rgb(17 17 17 / 0.16);
    border-radius: 999px;
    background: rgb(255 255 255 / 0.58);
    padding: 0.45rem 1rem;
    color: rgb(17 17 17 / 0.48);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    backdrop-filter: blur(12px);
    transition:
        color 180ms ease,
        border-color 180ms ease,
        background 180ms ease;
}

.skip-btn:hover {
    border-color: rgb(17 17 17 / 0.4);
    background: #ffffff;
    color: #111111;
}

@media (max-width: 767px) {
    .loader-logo-wrap {
        width: min(10rem, 42vw);
    }

    .skip-btn {
        right: 1rem;
        bottom: 1rem;
    }
}
</style>