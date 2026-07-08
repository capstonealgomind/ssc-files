<script setup>
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    images: {
        type: Array,
        default: () => [],
    },
});

const imagesReady = ref(false);

const hasImages = computed(() => props.images.length > 0);

const trackImages = computed(() => {
    if (!hasImages.value) {
        return [];
    }

    return [...props.images, ...props.images];
});

const animationDuration = computed(() => {
    const count = props.images.length;
    return `${Math.max(count * 5, 30)}s`;
});

async function preloadImages(images) {
    const urls = [...new Set(images.map((image) => image.image_url).filter(Boolean))];

    if (!urls.length) {
        imagesReady.value = false;
        return;
    }

    imagesReady.value = false;

    await Promise.all(
        urls.map(
            (url) =>
                new Promise((resolve) => {
                    const img = new Image();
                    img.decoding = 'async';
                    img.onload = () => resolve();
                    img.onerror = () => resolve();
                    img.src = url;
                }),
        ),
    );

    imagesReady.value = true;
}

onMounted(() => {
    if (hasImages.value) {
        preloadImages(props.images);
    }
});

watch(
    () => props.images,
    (images) => {
        if (images.length) {
            preloadImages(images);
            return;
        }

        imagesReady.value = false;
    },
    { deep: true },
);
</script>

<template>
    <section
        v-if="hasImages"
        class="ssc-members-carousel-section py-10 sm:py-14"
        aria-label="SSC members"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6 sm:mb-8 text-center">
            <p class="text-xs sm:text-sm font-semibold uppercase tracking-[0.14em] guest-muted">
                Supreme Student Council
            </p>
            <h2 class="mt-2 text-xl sm:text-2xl md:text-3xl font-bold guest-title">
                Meet our SSC members
            </h2>
        </div>

        <div class="ssc-members-carousel">
            <div
                class="ssc-members-carousel-track"
                :class="{ 'ssc-members-carousel-track--ready': imagesReady }"
                :style="{ animationDuration }"
            >
                <article
                    v-for="(image, index) in trackImages"
                    :key="`${image.id}-${index}`"
                    class="ssc-members-carousel-card"
                >
                    <img
                        :src="image.image_url"
                        alt="SSC member"
                        class="ssc-members-carousel-image"
                        loading="eager"
                        decoding="async"
                        :fetchpriority="index < 4 ? 'high' : 'auto'"
                    />
                </article>
            </div>
        </div>
    </section>
</template>

<style scoped>
.ssc-members-carousel-section {
    background: linear-gradient(
        180deg,
        hsl(214 100% 98%) 0%,
        hsl(0 0% 100%) 100%
    );
}

.ssc-members-carousel {
    overflow: hidden;
    width: 100%;
    mask-image: linear-gradient(
        to right,
        transparent 0%,
        #000 8%,
        #000 92%,
        transparent 100%
    );
}

.ssc-members-carousel-track {
    display: flex;
    width: max-content;
    gap: 1rem;
    padding-bottom: 0.25rem;
    animation-name: ssc-members-carousel-scroll;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
    animation-play-state: paused;
}

.ssc-members-carousel-track--ready {
    animation-play-state: running;
}

.ssc-members-carousel-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    opacity: 1;
}

.ssc-members-carousel-card {
    flex: 0 0 auto;
    width: 9rem;
    height: 12rem;
    border-radius: 0.75rem;
    border: 1px solid var(--sscevs-border);
    background-color: hsl(240 4.8% 98%);
    box-shadow: 0 8px 24px hsl(215 85% 42% / 0.08);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
}

@keyframes ssc-members-carousel-scroll {
    from {
        transform: translateX(0);
    }

    to {
        transform: translateX(-50%);
    }
}

@media (min-width: 640px) {
    .ssc-members-carousel-track {
        gap: 1.25rem;
    }

    .ssc-members-carousel-card {
        width: 10.5rem;
        height: 14rem;
    }
}

@media (min-width: 1024px) {
    .ssc-members-carousel-card {
        width: 11.5rem;
        height: 15rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .ssc-members-carousel-track {
        animation: none;
        flex-wrap: wrap;
        justify-content: center;
        width: 100%;
        max-width: 72rem;
        margin-inline: auto;
        padding-inline: 1rem;
    }
}
</style>
