<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PagePlaceholder from '@/Components/PagePlaceholder.vue';

const icon = `<svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>`;
</script>

<template>
    <AppLayout>
        <Head title="Help & Support" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">Help & Support</h1>
        </template>
        <PagePlaceholder
            title="Help & Support"
            description="Need assistance with voting or your account? Contact the election committee or visit the FAQ page for common questions."
            :icon="icon"
        />
    </AppLayout>
</template>
