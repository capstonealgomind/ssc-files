<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    faqs: { type: Array, default: () => [] },
});

const openIndex = ref(null);

function toggle(index) {
    openIndex.value = openIndex.value === index ? null : index;
}
</script>

<template>
    <AppLayout>
        <Head title="FAQ" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">FAQ</h1>
        </template>

        <div class="space-y-3">
            <div v-for="(faq, index) in faqs" :key="index"
                class="rounded-xl border bg-white overflow-hidden"
                style="border-color:hsl(240 5.9% 90%);">
                <button type="button"
                    class="w-full flex items-center justify-between gap-3 px-5 py-4 text-left"
                    @click="toggle(index)">
                    <span class="text-sm font-bold" style="color:hsl(240 10% 3.9%);">{{ faq.question }}</span>
                    <svg class="h-4 w-4 shrink-0 transition-transform"
                        :class="openIndex === index ? 'rotate-180' : ''"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        style="color:hsl(240 3.8% 46.1%);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div v-if="openIndex === index" class="px-5 pb-4">
                    <p class="text-sm leading-relaxed" style="color:hsl(240 3.8% 46.1%);">{{ faq.answer }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
