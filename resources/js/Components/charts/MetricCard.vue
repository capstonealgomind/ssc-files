<script setup>
import Card from '@/Components/ui/Card.vue';
import Sparkline from '@/Components/charts/Sparkline.vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: 'Since last week',
    },
    change: {
        type: String,
        required: true,
    },
    trend: {
        type: String,
        default: 'up',
        validator: (value) => ['up', 'down'].includes(value),
    },
    sparkline: {
        type: Array,
        required: true,
    },
    sparklineColor: {
        type: String,
        default: 'hsl(221 83% 53%)',
    },
    icon: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <Card class="flex flex-col">
        <div class="flex items-start justify-between gap-2 px-4 pt-3">
            <div class="flex items-center gap-2 min-w-0">
                <span
                    v-if="icon"
                    class="h-7 w-7 shrink-0 rounded-md flex items-center justify-center"
                    style="background-color: hsl(240 4.8% 95.9%);"
                >
                    <span class="h-3.5 w-3.5" style="color: hsl(240 5.9% 10%);" v-html="icon" />
                </span>
                <p class="text-sm font-medium truncate" style="color: hsl(240 10% 3.9%);">
                    {{ title }}
                </p>
            </div>
            <button
                type="button"
                class="shrink-0 rounded-full p-0.5 transition-colors hover:bg-[hsl(240_4.8%_95.9%)]"
                aria-label="More information"
            >
                <svg class="h-3.5 w-3.5" style="color: hsl(240 3.8% 46.1%);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <path stroke-linecap="round" d="M12 16v-4M12 8h.01" />
                </svg>
            </button>
        </div>

        <div class="flex items-end justify-between gap-3 px-4 pt-2 pb-1">
            <div>
                <p class="text-xl font-bold tracking-tight leading-none" style="color: hsl(240 10% 3.9%);">
                    {{ value }}
                </p>
                <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                    {{ subtitle }}
                </p>
            </div>
            <Sparkline :data="sparkline" :color="sparklineColor" :height="28" />
        </div>

        <div
            class="mt-2 flex items-center justify-between border-t px-4 py-2 text-xs"
            style="border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
        >
            <span>Details</span>
            <span
                class="inline-flex items-center gap-1 font-medium"
                :style="{ color: trend === 'up' ? 'hsl(142 71% 29%)' : 'hsl(0 72% 45%)' }"
            >
                {{ change }}
                <svg v-if="trend === 'up'" class="h-3 w-3" viewBox="0 0 12 12" fill="currentColor">
                    <path d="M6 2l4 5H2l4-5z" />
                </svg>
                <svg v-else class="h-3 w-3" viewBox="0 0 12 12" fill="currentColor">
                    <path d="M6 10L2 5h8L6 10z" />
                </svg>
            </span>
        </div>
    </Card>
</template>
