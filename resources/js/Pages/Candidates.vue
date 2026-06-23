<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PagePlaceholder from '@/Components/PagePlaceholder.vue';

const icon = `<svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>`;
</script>

<template>
    <AppLayout>
        <Head title="Candidates" />
        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Candidates</h1>
        </template>
        <PagePlaceholder
            title="Candidates"
            description="Add, edit, and manage candidates running in each election."
            :icon="icon"
        />
    </AppLayout>
</template>
