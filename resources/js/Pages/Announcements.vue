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

        <div v-else class="space-y-4">
            <article
                v-for="item in announcements"
                :key="item.id"
                class="rounded-xl border bg-white overflow-hidden"
                style="border-color:hsl(240 5.9% 90%);"
            >
                <div class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <h2 class="text-base font-bold leading-snug" style="color:hsl(240 10% 3.9%);">{{ item.title }}</h2>
                        <span class="text-xs font-semibold shrink-0 whitespace-nowrap"
                            style="color:hsl(240 3.8% 46.1%);">{{ item.date }}</span>
                    </div>

                    <p class="text-sm leading-relaxed whitespace-pre-wrap" style="color:hsl(240 3.8% 46.1%);">{{ item.body }}</p>

                    <div v-if="item.links?.length" class="flex flex-wrap gap-2 mt-4">
                        <a
                            v-for="(link, index) in item.links"
                            :key="`${item.id}-link-${index}`"
                            :href="link.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-lg transition-colors hover:opacity-90"
                            style="background:hsl(221 83% 53%); color:#fff;"
                        >
                            {{ link.label }}
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5M21 3l-9 9m0 0h4.5M12 12V3" />
                            </svg>
                        </a>
                    </div>

                    <div v-if="item.images?.length" class="grid grid-cols-1 gap-3 mt-4">
                        <div
                            v-for="(image, index) in item.images"
                            :key="`${item.id}-image-${index}`"
                            class="rounded-lg border bg-gray-50 p-3 flex items-center justify-center"
                            style="border-color:hsl(240 5.9% 90%);"
                        >
                            <img
                                :src="image"
                                :alt="`${item.title} image ${index + 1}`"
                                class="w-full max-h-[28rem] object-contain"
                            />
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </AppLayout>
</template>
