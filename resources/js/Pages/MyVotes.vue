<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    ballots: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout>
        <Head title="My Votes" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">My Votes</h1>
        </template>

        <div v-if="ballots.length === 0"
            class="flex flex-col items-center justify-center py-20 text-center">
            <div class="h-14 w-14 rounded-full flex items-center justify-center mb-4"
                style="background:hsl(240 4.8% 95.9%);">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    style="color:hsl(240 3.8% 46.1%);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <p class="text-sm font-medium" style="color:hsl(240 10% 3.9%);">No ballots submitted yet</p>
            <p class="text-xs mt-1 mb-4" style="color:hsl(240 3.8% 46.1%);">
                Once you cast your vote during an open election, your ballot will appear here.
            </p>
            <Link href="/elections"
                class="px-4 py-2 rounded text-xs font-semibold text-white"
                style="background:hsl(221 83% 53%);">
                Go to Elections
            </Link>
        </div>

        <div v-else class="space-y-4">
            <div v-for="ballot in ballots" :key="ballot.election_id"
                class="rounded-lg border overflow-hidden" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                <div class="px-5 py-4 border-b flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3"
                    style="border-color:hsl(240 5.9% 90%);">
                    <div>
                        <h2 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">{{ ballot.election_title }}</h2>
                        <p class="text-xs mt-0.5" style="color:hsl(240 3.8% 46.1%);">
                            Submitted on {{ ballot.submitted_at }}
                        </p>
                        <p v-if="ballot.receipt_number" class="text-xs mt-0.5 font-medium" style="color:hsl(221 83% 45%);">
                            Receipt: {{ ballot.receipt_number }}
                        </p>
                    </div>
                    <div v-if="ballot.receipt_id" class="flex gap-2 shrink-0">
                        <Link :href="`/ballot-receipt/${ballot.receipt_id}`"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded border text-xs font-medium"
                            style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%);">
                            View Receipt
                        </Link>
                        <a :href="`/ballot-receipt/${ballot.receipt_id}/pdf`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-semibold text-white"
                            style="background:hsl(221 83% 53%);">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            PDF
                        </a>
                    </div>
                </div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="vote in ballot.votes" :key="vote.position_id"
                        class="rounded-lg border px-4 py-3" style="border-color:hsl(240 5.9% 90%);">
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">
                            {{ vote.position }}
                        </p>
                        <p class="text-sm font-medium mt-1" style="color:hsl(240 10% 3.9%);">{{ vote.candidate }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
