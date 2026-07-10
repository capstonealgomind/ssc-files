<script setup>
import { reactive } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePdfDownload } from '@/composables/usePdfDownload';

const props = defineProps({
    ballots: { type: Array, default: () => [] },
});

const { downloadPdf, downloading } = usePdfDownload();

const expanded = reactive(
    Object.fromEntries(props.ballots.map((ballot) => [ballot.election_id, true])),
);

function isExpanded(electionId) {
    return expanded[electionId] !== false;
}

function toggleVotes(electionId) {
    expanded[electionId] = !isExpanded(electionId);
}
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
            <div
                v-for="ballot in ballots"
                :key="ballot.election_id"
                class="rounded-lg border overflow-hidden"
                style="border-color:hsl(240 5.9% 90%); background:#fff;"
            >
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
                        <button
                            type="button"
                            :disabled="downloading"
                            @click="downloadPdf(ballot.pdf_url, ballot.pdf_filename)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-semibold text-white disabled:opacity-60"
                            style="background:hsl(221 83% 53%);">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ downloading ? '...' : 'PDF' }}
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    class="w-full flex items-center justify-between gap-3 px-5 py-3 text-left transition-colors hover:bg-[hsl(240_4.8%_98%)]"
                    :aria-expanded="isExpanded(ballot.election_id)"
                    @click="toggleVotes(ballot.election_id)"
                >
                    <span class="text-sm font-medium" style="color:hsl(240 10% 3.9%);">
                        My votes
                        <span class="font-normal" style="color:hsl(240 3.8% 46.1%);">
                            ({{ ballot.votes?.length || 0 }} {{ (ballot.votes?.length || 0) === 1 ? 'position' : 'positions' }})
                        </span>
                    </span>
                    <svg
                        class="h-4 w-4 shrink-0 transition-transform duration-200"
                        :class="{ 'rotate-180': isExpanded(ballot.election_id) }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        style="color:hsl(240 3.8% 46.1%);"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    v-show="isExpanded(ballot.election_id)"
                    class="px-4 pb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 border-t"
                    style="border-color:hsl(240 5.9% 90%);"
                >
                    <div
                        v-for="vote in ballot.votes"
                        :key="vote.position_id"
                        class="rounded-lg border px-4 py-3 mt-3"
                        style="border-color:hsl(240 5.9% 90%);"
                    >
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
