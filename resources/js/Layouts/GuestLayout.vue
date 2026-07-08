<script setup>
import { Link } from '@inertiajs/vue3';
import Button from '@/Components/ui/Button.vue';
import GuestHeaderBrand from '@/Components/GuestHeaderBrand.vue';

defineProps({
    showBackHome: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="guest-shell">
        <header class="guest-header">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="min-h-16 flex items-center justify-between gap-3 py-1.5">
                    <GuestHeaderBrand />

                    <Link v-if="showBackHome" href="/">
                        <Button variant="ghost" size="sm" class="gap-1.5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span class="hidden sm:inline">Back to home</span>
                            <span class="sm:hidden">Home</span>
                        </Button>
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center px-4 py-6 sm:px-6 sm:py-12 bg-white">
            <div class="w-full">
                <slot />
            </div>
        </main>
    </div>
</template>
