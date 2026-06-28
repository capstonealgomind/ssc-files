<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PagePlaceholder from '@/Components/PagePlaceholder.vue';

const icon = `<svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`;
</script>

<template>
    <AppLayout>
        <Head title="Results" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">Results</h1>
        </template>
        <PagePlaceholder
            title="Election Results"
            description="Official election results will appear here once voting has ended and results are published."
            :icon="icon"
        />
    </AppLayout>
</template>
