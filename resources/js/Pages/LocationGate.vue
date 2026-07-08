<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import LottieAnimation from '@/Components/LottieAnimation.vue';
import Button from '@/Components/ui/Button.vue';

const props = defineProps({
    rangeMeters: {
        type: Number,
        default: null,
    },
});

const locationAnimationSrc = '/animation/Location%20Search.json';

const page = usePage();
const detecting = ref(false);
const clientError = ref('');

const form = useForm({
    latitude: null,
    longitude: null,
});

const locationError = computed(() => page.props.errors?.location ?? null);

const rangeLabel = computed(() => {
    const meters = props.rangeMeters;
    if (!meters) {
        return '';
    }

    if (meters >= 1000) {
        const km = meters / 1000;
        return km % 1 === 0 ? `${km} km` : `${km.toFixed(1)} km`;
    }

    return `${meters} m`;
});

function detectLocation() {
    clientError.value = '';

    if (!navigator.geolocation) {
        clientError.value =
            'Location detection is not supported on this device.';
        return;
    }

    detecting.value = true;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude;
            form.longitude = position.coords.longitude;

            form.post('/location/verify', {
                preserveScroll: true,
                onFinish: () => {
                    detecting.value = false;
                },
                onError: () => {
                    detecting.value = false;
                },
            });
        },
        (error) => {
            detecting.value = false;

            if (error.code === error.PERMISSION_DENIED) {
                clientError.value =
                    'Allow location access in your browser, then try again.';
                return;
            }

            if (error.code === error.TIMEOUT) {
                clientError.value = 'Location detection timed out. Try again.';
                return;
            }

            clientError.value = 'Unable to detect your location. Try again.';
        },
        {
            enableHighAccuracy: true,
            timeout: 20000,
            maximumAge: 0,
        },
    );
}

onMounted(() => {
    if (!page.props.errors?.location) {
        detectLocation();
    }
});
</script>

<template>
    <GuestLayout>
        <Head title="Location Verification" />

        <div class="location-gate w-full max-w-lg mx-auto space-y-5 sm:space-y-6 text-center">
            <div class="location-gate__animation mx-auto w-full max-w-[280px] sm:max-w-sm">
                <LottieAnimation :src="locationAnimationSrc" />
            </div>

            <div class="space-y-2 px-2 sm:px-4">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight guest-title">
                    Location check
                </h1>
                <p class="text-sm sm:text-base leading-relaxed guest-muted">
                    This site is available inside the campus only
                    <span v-if="rangeLabel"> ({{ rangeLabel }} range limit)</span>.
                </p>
            </div>

            <div class="space-y-3 sm:space-y-4 text-left px-1 sm:px-0">
                <div
                    v-if="detecting"
                    class="rounded-lg border px-3 py-3 sm:px-4 text-sm sm:text-base flex items-start gap-3"
                    style="
                        border-color: hsl(214 60% 88%);
                        background-color: hsl(214 100% 97%);
                        color: hsl(215 40% 35%);
                    "
                >
                    <svg
                        class="h-5 w-5 shrink-0 animate-spin mt-0.5"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                    <p>Detecting your location...</p>
                </div>

                <div
                    v-if="locationError"
                    class="rounded-lg border px-3 py-3 sm:px-4 text-sm sm:text-base space-y-2"
                    style="
                        border-color: hsl(0 84% 60% / 0.25);
                        background-color: hsl(0 84% 60% / 0.08);
                        color: hsl(0 72% 40%);
                    "
                >
                    <p class="font-medium">Outside campus</p>
                    <p>Please go to the campus to access this site.</p>
                </div>

                <div
                    v-if="clientError"
                    class="rounded-lg border px-3 py-3 sm:px-4 text-sm sm:text-base"
                    style="
                        border-color: hsl(38 92% 50% / 0.25);
                        background-color: hsl(48 96% 89%);
                        color: hsl(32 81% 29%);
                    "
                >
                    {{ clientError }}
                </div>

                <Button
                    type="button"
                    variant="navy"
                    class="w-full min-h-11 sm:min-h-12 text-sm sm:text-base"
                    :disabled="detecting || form.processing"
                    @click="detectLocation"
                >
                    {{
                        detecting || form.processing
                            ? 'Detecting...'
                            : 'Try again'
                    }}
                </Button>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.location-gate__animation {
    height: 9rem;
}

@media (min-width: 400px) {
    .location-gate__animation {
        height: 10.5rem;
    }
}

@media (min-width: 640px) {
    .location-gate__animation {
        height: 11.5rem;
    }
}

@media (min-width: 768px) {
    .location-gate__animation {
        height: 13rem;
    }
}
</style>
