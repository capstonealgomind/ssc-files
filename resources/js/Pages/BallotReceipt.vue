<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    receipt: { type: Object, required: true },
});
</script>

<template>
    <AppLayout>
        <Head title="Ballot Receipt" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">Ballot Receipt</h1>
        </template>

        <div class="max-w-2xl mx-auto space-y-4">
            <!-- Success banner -->
            <div class="rounded-lg border px-4 py-3 text-sm flex items-center gap-2"
                style="border-color:hsl(142 76% 80%); background:hsl(142 76% 94%); color:hsl(142 71% 25%);">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Your ballot has been submitted successfully. Save your receipt below.
            </div>

            <!-- Receipt card -->
            <div id="ballot-receipt" class="rounded-xl border overflow-hidden shadow-sm"
                style="border-color:hsl(240 5.9% 90%); background:#fff;">
                <!-- Header -->
                <div class="px-6 py-5 text-center border-b" style="border-color:hsl(240 5.9% 90%); background:hsl(221 83% 98%);">
                    <p class="text-xs font-semibold uppercase tracking-widest" style="color:hsl(221 83% 45%);">SSCEVS</p>
                    <h2 class="text-lg font-bold mt-0.5" style="color:hsl(240 10% 3.9%);">Ballot Receipt</h2>
                    <p class="text-xs mt-0.5" style="color:hsl(240 3.8% 46.1%);">Smart Student Council Electronic Voting System</p>
                    <div class="inline-flex items-center gap-1.5 mt-3 px-3 py-1 rounded-full text-xs font-bold"
                        style="background:hsl(221 83% 94%); color:hsl(221 83% 35%); border:1px solid hsl(221 83% 80%);">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ receipt.receipt_number }}
                    </div>
                </div>

                <!-- Status -->
                <div class="mx-6 my-4 rounded-lg px-4 py-3 text-center"
                    style="background:hsl(142 76% 94%); border:1px solid hsl(142 76% 80%);">
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color:hsl(142 71% 35%);">Ballot Status</p>
                    <p class="text-sm font-bold mt-0.5 flex items-center justify-center gap-1.5" style="color:hsl(142 71% 25%);">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Successfully Recorded
                    </p>
                </div>

                <!-- Details -->
                <div class="px-6 pb-2 space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color:hsl(240 3.8% 46.1%);">Receipt Details</p>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between gap-4">
                                <span style="color:hsl(240 3.8% 46.1%);">Date Submitted</span>
                                <span class="font-medium text-right" style="color:hsl(240 10% 3.9%);">{{ receipt.submitted_at }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span style="color:hsl(240 3.8% 46.1%);">Election</span>
                                <span class="font-medium text-right" style="color:hsl(240 10% 3.9%);">{{ receipt.election.title }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span style="color:hsl(240 3.8% 46.1%);">Voting Period</span>
                                <span class="font-medium text-right text-xs" style="color:hsl(240 10% 3.9%);">{{ receipt.election.voting_period }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-4" style="border-color:hsl(240 5.9% 90%);">
                        <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color:hsl(240 3.8% 46.1%);">Voter Information</p>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between gap-4">
                                <span style="color:hsl(240 3.8% 46.1%);">Name</span>
                                <span class="font-medium" style="color:hsl(240 10% 3.9%);">{{ receipt.voter.name }}</span>
                            </div>
                            <div v-if="receipt.voter.voter_id_number" class="flex justify-between gap-4">
                                <span style="color:hsl(240 3.8% 46.1%);">Voter ID</span>
                                <span class="font-medium" style="color:hsl(240 10% 3.9%);">{{ receipt.voter.voter_id_number }}</span>
                            </div>
                            <div v-if="receipt.voter.department" class="flex justify-between gap-4">
                                <span style="color:hsl(240 3.8% 46.1%);">Department</span>
                                <span class="font-medium" style="color:hsl(240 10% 3.9%);">{{ receipt.voter.department }}</span>
                            </div>
                            <div v-if="receipt.voter.course" class="flex justify-between gap-4">
                                <span style="color:hsl(240 3.8% 46.1%);">Course</span>
                                <span class="font-medium" style="color:hsl(240 10% 3.9%);">{{ receipt.voter.course }}</span>
                            </div>
                            <div v-if="receipt.voter.year_level" class="flex justify-between gap-4">
                                <span style="color:hsl(240 3.8% 46.1%);">Year Level</span>
                                <span class="font-medium" style="color:hsl(240 10% 3.9%);">{{ receipt.voter.year_level }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-4" style="border-color:hsl(240 5.9% 90%);">
                        <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color:hsl(240 3.8% 46.1%);">Your Selections</p>
                        <div class="rounded-lg border overflow-hidden" style="border-color:hsl(240 5.9% 90%);">
                            <div v-for="(selection, i) in receipt.selections" :key="i"
                                class="flex items-center justify-between px-4 py-2.5 text-sm border-b last:border-0"
                                :style="i % 2 === 0 ? { background: '#fff' } : { background: 'hsl(240 4.8% 98%)' }"
                                style="border-color:hsl(240 5.9% 90%);">
                                <span style="color:hsl(240 3.8% 46.1%);">{{ selection.position }}</span>
                                <span class="font-semibold" style="color:hsl(240 10% 3.9%);">{{ selection.candidate }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer note -->
                <div class="px-6 py-4 mt-2 border-t text-xs text-center" style="border-color:hsl(240 5.9% 90%); color:hsl(240 3.8% 46.1%);">
                    Keep this receipt for your records. Your vote is confidential and cannot be changed after submission.
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a :href="receipt.pdf_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition-opacity w-full sm:w-auto justify-center"
                    style="background:hsl(221 83% 53%);">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download PDF Receipt
                </a>
                <Link href="/my-votes"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium border w-full sm:w-auto justify-center"
                    style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%);">
                    View My Votes
                </Link>
                <Link href="/elections"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium border w-full sm:w-auto justify-center"
                    style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%);">
                    Back to Elections
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
