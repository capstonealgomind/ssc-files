<script setup>
import { onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Toaster } from 'vue-sonner';
import { showFlashToast } from '@/composables/useToast';

const page = usePage();

/** Latest Inertia visit — success events in v3 no longer include `visit`. */
let currentVisit = null;

function shouldHandleFlash(visit) {
    if (!visit?.only?.length) {
        return true;
    }

    return visit.only.includes('flash');
}

function clearDisplayedFlash(flash) {
    if (!flash || typeof flash !== 'object') {
        return;
    }

    flash.success = null;
    flash.error = null;
}

function handleFlash(flash) {
    showFlashToast(flash);
    clearDisplayedFlash(flash);
}

onMounted(() => {
    handleFlash(page.props.flash);
});

router.on('start', (event) => {
    currentVisit = event.detail.visit ?? null;
});

router.on('success', (event) => {
    if (!shouldHandleFlash(currentVisit)) {
        return;
    }

    handleFlash(event.detail.page.props.flash);
});
</script>

<template>
    <Toaster
        position="top-right"
        theme="light"
        :rich-colors="false"
        :close-button="false"
        :duration="4500"
        class="sscevs-toaster !z-[9999]"
        :toast-options="{
            classes: {
                toast: 'sscevs-toast',
                success: 'sscevs-toast-success',
                error: 'sscevs-toast-error',
                warning: 'sscevs-toast-warning',
                info: 'sscevs-toast-info',
                title: 'sscevs-toast-title',
                description: 'sscevs-toast-description',
            },
        }"
    >
        <template #success-icon>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="h-4 w-4"
                aria-hidden="true"
            >
                <path d="M20 6L9 17l-5-5" />
            </svg>
        </template>

        <template #error-icon>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="h-4 w-4"
                aria-hidden="true"
            >
                <circle cx="12" cy="12" r="10" />
                <path d="M12 8v4" />
                <path d="M12 16h.01" />
            </svg>
        </template>

        <template #warning-icon>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="h-4 w-4"
                aria-hidden="true"
            >
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
            </svg>
        </template>

        <template #info-icon>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="h-4 w-4"
                aria-hidden="true"
            >
                <circle cx="12" cy="12" r="10" />
                <path d="M12 16v-4" />
                <path d="M12 8h.01" />
            </svg>
        </template>
    </Toaster>
</template>
