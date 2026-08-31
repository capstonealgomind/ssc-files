<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

const props = defineProps({
    images: {
        type: Array,
        default: () => [],
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const UNIT_SIZE = 10;
const COLUMN_ORDER = ["left", "center", "right", "portraits"];

const viewportRef = ref(null);
const hovering = ref(false);
const inView = ref(true);
const openedImage = ref(null);
const unitWidth = ref(0);

let observer = null;
let resizeObserver = null;

const galleryImages = computed(() =>
    (props.images ?? []).filter((image) => image?.image_url),
);

const units = computed(() => {
    const list = galleryImages.value;

    if (!list.length) {
        return [];
    }

    const pool = [...list];

    while (pool.length < UNIT_SIZE) {
        pool.push(...list);
    }

    const count = Math.max(1, Math.ceil(list.length / UNIT_SIZE));
    const chunks = [];

    for (let unitIndex = 0; unitIndex < count; unitIndex++) {
        const start = unitIndex * UNIT_SIZE;
        const chunk = [];

        for (let offset = 0; offset < UNIT_SIZE; offset++) {
            chunk.push(pool[(start + offset) % pool.length]);
        }

        chunks.push(chunk);
    }

    return chunks;
});

const copyCount = computed(() => {
    if (!units.value.length) {
        return 2;
    }

    const width = unitWidth.value;
    const viewport = viewportRef.value?.clientWidth ?? width;

    if (!width || !viewport) {
        return 2;
    }

    const setWidth = units.value.length * width;
    const minTotal = Math.max(viewport * 2, setWidth * 2);
    let copies = Math.ceil(minTotal / setWidth);

    if (copies % 2) {
        copies += 1;
    }

    return Math.max(2, copies);
});

const trackUnits = computed(() => {
    if (!units.value.length) {
        return [];
    }

    return Array.from({ length: copyCount.value }, () => units.value).flat();
});

const animationDuration = computed(() => {
    const count = Math.max(units.value.length, 1);
    return `${Math.max(count * 22, 28)}s`;
});

const canAnimate = computed(
    () =>
        trackUnits.value.length > 1 &&
        inView.value &&
        !hovering.value &&
        !openedImage.value &&
        !prefersReducedMotion(),
);

function prefersReducedMotion() {
    return (
        typeof window !== "undefined" &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches
    );
}

function openImage(image) {
    if (!image?.image_url) {
        return;
    }

    openedImage.value = image;
}

function closeImage() {
    openedImage.value = null;
}

function onKeydown(event) {
    if (openedImage.value && event.key === "Escape") {
        closeImage();
    }
}

function setupObserver() {
    if (!viewportRef.value || typeof IntersectionObserver === "undefined") {
        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                inView.value = entry.isIntersecting;
            });
        },
        { threshold: 0.12 },
    );

    observer.observe(viewportRef.value);
}

function measureUnitWidth() {
    const viewport = viewportRef.value?.clientWidth ?? 0;

    if (!viewport) {
        unitWidth.value = 0;
        return;
    }

    if (props.compact) {
        unitWidth.value = viewport;
        return;
    }

    if (typeof window !== "undefined" && window.matchMedia("(min-width: 1280px)").matches) {
        unitWidth.value = Math.min(720, Math.round(viewport * 0.38));
        return;
    }

    if (typeof window !== "undefined" && window.matchMedia("(min-width: 1024px)").matches) {
        unitWidth.value = Math.min(640, Math.round(viewport * 0.48));
        return;
    }

    if (typeof window !== "undefined" && window.matchMedia("(min-width: 768px)").matches) {
        unitWidth.value = Math.min(560, Math.round(viewport * 0.62));
        return;
    }

    unitWidth.value = Math.min(640, Math.max(520, Math.round(viewport * 1.72)));
}

function setupResizeObserver() {
    if (!viewportRef.value) {
        return;
    }

    measureUnitWidth();

    if (typeof ResizeObserver === "undefined") {
        window.addEventListener("resize", measureUnitWidth);
        return;
    }

    resizeObserver = new ResizeObserver(() => {
        measureUnitWidth();
    });

    resizeObserver.observe(viewportRef.value);
}

function columnImages(unit, side) {
    if (side === "left") {
        return [unit[0], unit[1], unit[2]];
    }

    if (side === "center") {
        return [unit[3], unit[4]];
    }

    if (side === "right") {
        return [unit[5], unit[6], unit[7]];
    }

    return [unit[8], unit[9]];
}

function isPortraitColumn(side) {
    return side === "center" || side === "portraits";
}

onMounted(() => {
    setupObserver();
    setupResizeObserver();
    window.addEventListener("keydown", onKeydown);
});

onUnmounted(() => {
    observer?.disconnect();
    observer = null;
    resizeObserver?.disconnect();
    resizeObserver = null;
    window.removeEventListener("resize", measureUnitWidth);
    window.removeEventListener("keydown", onKeydown);
});

watch(
    () => props.images,
    () => {
        openedImage.value = null;
    },
);
</script>

<template>
    <div
        v-if="trackUnits.length"
        class="gb-carousel"
        :class="{ 'gb-carousel--compact': compact }"
        role="region"
        aria-label="Campus gallery"
        @mouseenter="hovering = true"
        @mouseleave="hovering = false"
    >
        <div ref="viewportRef" class="gb-viewport">
            <div
                class="gb-track"
                :class="{ 'gb-track--running': canAnimate }"
                :style="{ animationDuration }"
            >
                <article
                    v-for="(unit, unitIndex) in trackUnits"
                    :key="`unit-${unitIndex}`"
                    class="gb-unit"
                    :style="
                        unitWidth
                            ? { width: `${unitWidth}px`, flexBasis: `${unitWidth}px` }
                            : null
                    "
                >
                    <div
                        v-for="side in COLUMN_ORDER"
                        :key="`${unitIndex}-${side}`"
                        class="gb-col"
                        :class="
                            isPortraitColumn(side)
                                ? 'gb-col--center'
                                : 'gb-col--side'
                        "
                    >
                        <button
                            v-for="(image, cellIndex) in columnImages(unit, side)"
                            :key="`${image.id ?? image.image_url}-${unitIndex}-${side}-${cellIndex}`"
                            type="button"
                            class="gb-cell"
                            :class="{
                                'gb-cell--landscape':
                                    !isPortraitColumn(side) && cellIndex === 1,
                            }"
                            aria-label="Open gallery photo"
                            @click="openImage(image)"
                        >
                            <img
                                :src="image.image_url"
                                alt="SSCEVS gallery"
                                class="gb-image"
                                :loading="unitIndex < 2 ? 'eager' : 'lazy'"
                                decoding="async"
                            />
                        </button>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="openedImage"
            class="gb-lightbox"
            role="dialog"
            aria-modal="true"
            aria-label="Gallery photo"
            @click.self="closeImage"
        >
            <button
                type="button"
                class="gb-lightbox-close"
                aria-label="Close photo"
                @click="closeImage"
            >
                Close
            </button>
            <img
                :src="openedImage.image_url"
                alt="SSCEVS gallery"
                class="gb-lightbox-image"
            />
        </div>
    </Teleport>
</template>

<style scoped>
.gb-carousel {
    position: relative;
    width: 100%;
    margin: 0;
    padding: 0;
}

.gb-carousel::before,
.gb-carousel::after {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    width: min(14vw, 9rem);
    z-index: 2;
    pointer-events: none;
}

.gb-carousel::before {
    left: 0;
    background: linear-gradient(
        to right,
        #ffffff 0%,
        rgba(255, 255, 255, 0.92) 28%,
        rgba(255, 255, 255, 0.45) 62%,
        rgba(255, 255, 255, 0) 100%
    );
}

.gb-carousel::after {
    right: 0;
    background: linear-gradient(
        to left,
        #ffffff 0%,
        rgba(255, 255, 255, 0.92) 28%,
        rgba(255, 255, 255, 0.45) 62%,
        rgba(255, 255, 255, 0) 100%
    );
}

.gb-carousel--compact::before,
.gb-carousel--compact::after {
    width: 3.5rem;
}

.gb-viewport {
    width: 100%;
    overflow: hidden;
    background: #ffffff;
}

.gb-track {
    display: flex;
    width: max-content;
    transform: translate3d(0, 0, 0);
    backface-visibility: hidden;
    animation-name: gb-scroll;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
    animation-play-state: paused;
    will-change: transform;
}

.gb-track--running {
    animation-play-state: running;
}

.gb-unit {
    box-sizing: border-box;
    flex: 0 0 auto;
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1.32fr 1fr 1.32fr;
    gap: 0.4rem;
    height: 28rem;
    padding-right: 0.4rem;
}

.gb-carousel--compact .gb-unit {
    height: 12.5rem;
    gap: 0.3rem;
    padding-right: 0.3rem;
}

.gb-col {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    min-width: 0;
    min-height: 0;
}

.gb-carousel--compact .gb-col {
    gap: 0.3rem;
}

.gb-col--side .gb-cell {
    flex: 1.05 1 0;
}

.gb-col--side .gb-cell--landscape {
    flex: 0.78 1 0;
}

.gb-col--center .gb-cell {
    flex: 1 1 0;
}

.gb-cell {
    position: relative;
    display: block;
    width: 100%;
    min-height: 0;
    overflow: hidden;
    border: 0;
    padding: 0;
    border-radius: 0;
    background: #ececec;
    cursor: zoom-in;
}

.gb-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 18%;
    pointer-events: none;
}

.gb-lightbox {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background: rgba(8, 10, 16, 0.88);
}

.gb-lightbox-image {
    max-width: min(92vw, 1100px);
    max-height: 86vh;
    object-fit: contain;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
}

.gb-lightbox-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    border: 0;
    border-radius: 999px;
    padding: 0.45rem 0.85rem;
    background: rgba(255, 255, 255, 0.92);
    color: #111111;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

@keyframes gb-scroll {
    from {
        transform: translate3d(0, 0, 0);
    }

    to {
        transform: translate3d(-50%, 0, 0);
    }
}

@media (min-width: 768px) {
    .gb-unit {
        height: 32rem;
        gap: 0.45rem;
        padding-right: 0.45rem;
    }

    .gb-col {
        gap: 0.45rem;
    }
}

@media (min-width: 1024px) {
    .gb-unit {
        height: 36rem;
        gap: 0.5rem;
        padding-right: 0.5rem;
    }

    .gb-col {
        gap: 0.5rem;
    }
}

@media (max-width: 640px) {
    .gb-unit {
        height: 26rem;
        gap: 0.32rem;
        padding-right: 0.32rem;
    }

    .gb-col {
        gap: 0.32rem;
    }

    .gb-carousel::before,
    .gb-carousel::after {
        width: 3.25rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .gb-track {
        animation: none;
    }
}
</style>
