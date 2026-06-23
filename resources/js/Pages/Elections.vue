<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PagePlaceholder from '@/Components/PagePlaceholder.vue';

const icon = `<svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
</script>

<template>
    <AppLayout>
        <Head title="Elections" />
        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Elections</h1>
        </template>
        <PagePlaceholder
            title="Elections"
            description="Create and manage election events, schedules, and voting periods."
            :icon="icon"
        />
    </AppLayout>
</template>
