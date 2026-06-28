<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    announcements: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout>
        <Head title="Announcements" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">Announcements</h1>
        </template>

        <div v-if="announcements.length === 0"
            class="rounded-xl border bg-white p-8 text-center"
            style="border-color:hsl(240 5.9% 90%);">
            <p class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">No announcements yet</p>
            <p class="text-xs mt-1" style="color:hsl(240 3.8% 46.1%);">Check back later for election updates.</p>
        </div>

        <div v-else class="space-y-3">
            <div v-for="item in announcements" :key="item.id"
                class="rounded-xl border bg-white px-5 py-4"
                style="border-color:hsl(240 5.9% 90%);">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <h2 class="text-base font-bold leading-snug" style="color:hsl(240 10% 3.9%);">{{ item.title }}</h2>
                    <span class="text-xs font-semibold shrink-0 whitespace-nowrap"
                        style="color:hsl(240 3.8% 46.1%);">{{ item.date }}</span>
                </div>
                <p class="text-sm leading-relaxed" style="color:hsl(240 3.8% 46.1%);">{{ item.excerpt }}</p>
            </div>
        </div>
    </AppLayout>
</template>
