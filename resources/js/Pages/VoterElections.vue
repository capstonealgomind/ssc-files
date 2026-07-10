<script setup>
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import LottieAnimation from '@/Components/LottieAnimation.vue';
import CandidatePositionRow from '@/Components/elections/CandidatePositionRow.vue';
import { usePdfDownload } from '@/composables/usePdfDownload';

const props = defineProps({
    verified:  { type: Boolean, required: true },
    elections: { type: Array,   default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const { downloadPdf, downloading } = usePdfDownload();

const expanded = ref({});
const ballotMode = ref({});
const selectionsExpanded = ref({});
const confirmElectionId = ref(null);
const selections = reactive({});
const showVoteSuccessAnimation = ref(false);
const pendingReceiptId = ref(null);
const pendingSubmissionId = ref(null);
const voteProcessingMessage = ref('Preparing your receipt…');
let fallbackRedirectTimer = null;
let submissionPollTimer = null;
let animationFinished = false;

const voteSuccessAnimationSrc = '/animation/Election%20concept%20Lottie%20JSON%20animation.json';

function toggle(id) {
    expanded.value[id] = !expanded.value[id];
}

function isSelectionsExpanded(electionId) {
    return selectionsExpanded.value[electionId] !== false;
}

function toggleSelections(electionId) {
    selectionsExpanded.value[electionId] = !isSelectionsExpanded(electionId);
}

function startBallot(election) {
    expanded.value[election.id] = false;
    ballotMode.value[election.id] = true;
    if (!selections[election.id]) {
        selections[election.id] = {};
    }
}

function cancelBallot(electionId) {
    ballotMode.value[electionId] = false;
    selections[electionId] = {};
}

function selectCandidate(electionId, positionId, candidateId) {
    if (!selections[electionId]) selections[electionId] = {};
    selections[electionId][positionId] = candidateId;
}

function groupByPosition(candidates) {
    return candidates.reduce((acc, c) => {
        const key = c.position_id ?? 'unassigned';
        if (!acc[key]) {
            acc[key] = { position_id: c.position_id, position: c.position ?? 'Unassigned', candidates: [] };
        }
        acc[key].candidates.push(c);
        return acc;
    }, {});
}

function positionGroups(election) {
    return Object.values(groupByPosition(election.candidates));
}

function isSelected(electionId, positionId, candidateId) {
    return selections[electionId]?.[positionId] === candidateId;
}

function ballotComplete(election) {
    const groups = positionGroups(election);
    if (groups.length === 0) return false;
    return groups.every(g => selections[election.id]?.[g.position_id]);
}

const submitForm = useForm({ selections: [] });

function openConfirm(election) {
    if (!ballotComplete(election)) return;
    confirmElectionId.value = election.id;
}

function closeConfirm() {
    confirmElectionId.value = null;
}

function clearFallbackRedirect() {
    if (fallbackRedirectTimer) {
        window.clearTimeout(fallbackRedirectTimer);
        fallbackRedirectTimer = null;
    }
}

function clearSubmissionPoll() {
    if (submissionPollTimer) {
        window.clearInterval(submissionPollTimer);
        submissionPollTimer = null;
    }
}

function tryFinishVoteFlow() {
    if (!animationFinished || !pendingReceiptId.value) {
        return;
    }

    goToReceipt();
}

function goToReceipt() {
    if (!pendingReceiptId.value) return;

    const receiptId = pendingReceiptId.value;
    clearFallbackRedirect();
    clearSubmissionPoll();
    showVoteSuccessAnimation.value = false;
    pendingReceiptId.value = null;
    pendingSubmissionId.value = null;
    animationFinished = false;

    router.visit(`/ballot-receipt/${receiptId}`);
}

function onVoteAnimationComplete() {
    animationFinished = true;
    voteProcessingMessage.value = pendingReceiptId.value
        ? 'Opening your receipt…'
        : 'Still processing your ballot…';
    tryFinishVoteFlow();
}

async function pollSubmissionStatus(submissionId) {
    try {
        const response = await fetch(`/ballot-submissions/${submissionId}/status`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Status check failed (${response.status})`);
        }

        const data = await response.json();

        if (data.is_completed && data.ballot_receipt_id) {
            pendingReceiptId.value = data.ballot_receipt_id;
            voteProcessingMessage.value = animationFinished
                ? 'Opening your receipt…'
                : 'Ballot recorded. Finishing animation…';
            clearSubmissionPoll();
            tryFinishVoteFlow();
            return;
        }

        if (data.is_failed) {
            clearSubmissionPoll();
            clearFallbackRedirect();
            showVoteSuccessAnimation.value = false;
            pendingSubmissionId.value = null;
            animationFinished = false;
            window.alert(data.error_message || 'Unable to process your ballot. Please try again.');
            router.reload({ only: ['elections'], preserveScroll: true });
        }
    } catch {
        // Keep polling; transient network issues should not stop the flow.
    }
}

function startSubmissionPolling(submissionId) {
    clearSubmissionPoll();
    pendingSubmissionId.value = submissionId;
    pollSubmissionStatus(submissionId);
    submissionPollTimer = window.setInterval(() => {
        pollSubmissionStatus(submissionId);
    }, 1500);
}

function submitBallot(election) {
    const groups = positionGroups(election);
    submitForm.selections = groups.map(g => ({
        position_id: g.position_id,
        candidate_id: selections[election.id][g.position_id],
    }));
    submitForm.post(`/elections/${election.id}/cast-vote`, {
        preserveScroll: true,
        onSuccess: (pageResult) => {
            closeConfirm();
            ballotMode.value[election.id] = false;
            selections[election.id] = {};

            const flash = pageResult?.props?.flash ?? page.props.flash ?? {};
            const receiptId = flash.ballot_receipt_id ?? null;
            const submissionId = flash.ballot_submission_id ?? null;

            animationFinished = false;
            showVoteSuccessAnimation.value = true;
            voteProcessingMessage.value = 'Queueing and recording your ballot…';
            clearFallbackRedirect();
            clearSubmissionPoll();

            if (receiptId) {
                pendingReceiptId.value = receiptId;
                voteProcessingMessage.value = 'Preparing your receipt…';
                fallbackRedirectTimer = window.setTimeout(goToReceipt, 8000);
                return;
            }

            if (submissionId) {
                startSubmissionPolling(submissionId);
                fallbackRedirectTimer = window.setTimeout(() => {
                    if (!pendingReceiptId.value) {
                        voteProcessingMessage.value = 'Still processing your ballot…';
                    }
                }, 8000);
                return;
            }
        },
    });
}

function phaseStyle(phase) {
    if (phase === 'open')     return { bg: 'hsl(142 76% 94%)', color: 'hsl(142 71% 29%)', dot: 'hsl(142 71% 45%)', label: 'Voting Open' };
    if (phase === 'upcoming') return { bg: 'hsl(221 83% 94%)', color: 'hsl(221 83% 35%)', dot: 'hsl(221 83% 53%)', label: 'Not Started' };
    return                             { bg: 'hsl(240 4.8% 95.9%)', color: 'hsl(240 3.8% 46.1%)', dot: 'hsl(240 3.8% 65%)', label: 'Voting Closed' };
}

function statusStyle(status) {
    if (status === 'active')    return { bg: 'hsl(142 76% 94%)', color: 'hsl(142 71% 29%)', dot: 'hsl(142 71% 45%)' };
    if (status === 'scheduled') return { bg: 'hsl(221 83% 94%)', color: 'hsl(221 83% 35%)', dot: 'hsl(221 83% 53%)' };
    return                             { bg: 'hsl(240 4.8% 95.9%)', color: 'hsl(240 3.8% 46.1%)', dot: 'hsl(240 3.8% 65%)' };
}

const confirmElection = computed(() =>
    props.elections.find(e => e.id === confirmElectionId.value) ?? null
);

onMounted(() => {
    const pending = props.elections.find(e => e.ballot_processing && e.ballot_submission_id);
    if (!pending) return;

    showVoteSuccessAnimation.value = true;
    voteProcessingMessage.value = 'Still processing your ballot…';
    startSubmissionPolling(pending.ballot_submission_id);
});

onUnmounted(() => {
    clearFallbackRedirect();
    clearSubmissionPoll();
});
</script>

<template>
    <AppLayout>
        <Head title="Elections" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">Elections</h1>
        </template>

        <div v-if="!verified" class="flex flex-col items-center justify-center py-20 text-center">
            <div class="h-16 w-16 rounded-full flex items-center justify-center mb-4"
                style="background:hsl(38 92% 94%);">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    style="color:hsl(38 62% 30%);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold mb-1" style="color:hsl(240 10% 3.9%);">Account Pending Verification</h2>
            <p class="text-sm max-w-sm" style="color:hsl(240 3.8% 46.1%);">
                Your voter registration is under review. An administrator needs to verify your account before you can access elections and cast your vote.
            </p>
            <div class="mt-4 rounded-lg border px-5 py-3 text-sm" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full" style="background:hsl(38 92% 50%);"></span>
                    <span style="color:hsl(240 3.8% 46.1%);">Logged in as</span>
                    <span class="font-medium" style="color:hsl(240 10% 3.9%);">{{ user.name }}</span>
                </div>
            </div>
        </div>

        <div v-else class="space-y-4">
            <div v-if="elections.length === 0"
                class="flex flex-col items-center justify-center py-20 text-center">
                <div class="h-14 w-14 rounded-full flex items-center justify-center mb-4"
                    style="background:hsl(240 4.8% 95.9%);">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        style="color:hsl(240 3.8% 46.1%);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-sm font-medium" style="color:hsl(240 10% 3.9%);">No active elections</p>
                <p class="text-xs mt-1" style="color:hsl(240 3.8% 46.1%);">Check back later for upcoming elections.</p>
            </div>

            <div v-for="election in elections" :key="election.id"
                class="rounded-lg border overflow-x-clip" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                <div class="px-5 py-4 border-b flex flex-col sm:flex-row sm:items-center gap-3"
                    style="border-color:hsl(240 5.9% 90%);">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">{{ election.title }}</h2>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                                :style="{ background: statusStyle(election.status).bg, color: statusStyle(election.status).color }">
                                <span class="h-1.5 w-1.5 rounded-full"
                                    :style="{ background: statusStyle(election.status).dot }"></span>
                                {{ election.status_label }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                                :style="{ background: phaseStyle(election.voting_phase).bg, color: phaseStyle(election.voting_phase).color }">
                                <span class="h-1.5 w-1.5 rounded-full"
                                    :style="{ background: phaseStyle(election.voting_phase).dot }"></span>
                                {{ phaseStyle(election.voting_phase).label }}
                            </span>
                            <span v-if="election.has_voted"
                                class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                                style="background:hsl(262 83% 94%); color:hsl(262 60% 35%);">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                Ballot Submitted
                            </span>
                        </div>
                        <p v-if="election.description" class="text-xs mt-0.5 truncate" style="color:hsl(240 3.8% 46.1%);">
                            {{ election.description }}
                        </p>
                        <p class="text-xs mt-0.5" style="color:hsl(240 3.8% 46.1%);">
                            <span class="font-medium" style="color:hsl(240 10% 3.9%);">Voting period:</span>
                            {{ election.voting_period }}
                        </p>
                        <p v-if="election.voting_phase === 'upcoming'" class="text-xs mt-1" style="color:hsl(221 83% 40%);">
                            Voting opens on {{ election.voting_starts_at_display }}
                        </p>
                        <p v-else-if="election.voting_phase === 'closed' && !election.has_voted" class="text-xs mt-1" style="color:hsl(240 3.8% 46.1%);">
                            The voting period for this election has ended.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 flex-wrap">
                        <button v-if="election.can_vote && !ballotMode[election.id]"
                            class="px-3 py-1.5 rounded text-xs font-semibold text-white transition-opacity"
                            style="background:hsl(221 83% 53%);"
                            @mouseenter="$event.currentTarget.style.opacity='0.9'"
                            @mouseleave="$event.currentTarget.style.opacity='1'"
                            @click="startBallot(election)">
                            Cast Your Vote
                        </button>
                        <span
                            v-else-if="election.ballot_processing"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-semibold"
                            style="background:hsl(38 92% 94%); color:hsl(38 62% 30%);"
                        >
                            Processing ballot…
                        </span>
                        <button class="flex items-center gap-1 px-2.5 py-1.5 rounded border transition-colors text-xs font-medium"
                            style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%);"
                            @mouseenter="$event.currentTarget.style.background='hsl(240 4.8% 95.9%)'"
                            @mouseleave="$event.currentTarget.style.background=''"
                            @click="toggle(election.id)">
                            {{ expanded[election.id] ? 'Hide' : 'View' }} Candidates
                            <svg class="h-3.5 w-3.5 transition-transform" :class="expanded[election.id] ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submitted ballot summary -->
                <div
                    v-if="election.has_voted && election.user_votes.length"
                    class="border-b text-sm"
                    style="border-color:hsl(240 5.9% 90%); background:hsl(262 83% 98%);"
                >
                    <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <button
                            type="button"
                            class="flex items-center gap-2 text-left min-w-0"
                            :aria-expanded="isSelectionsExpanded(election.id)"
                            @click="toggleSelections(election.id)"
                        >
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide" style="color:hsl(262 60% 35%);">
                                    Your selections
                                    <span class="font-normal normal-case tracking-normal" style="color:hsl(262 40% 45%);">
                                        ({{ election.user_votes.length }}
                                        {{ election.user_votes.length === 1 ? 'position' : 'positions' }})
                                    </span>
                                </p>
                                <p v-if="election.receipt_number" class="text-xs mt-0.5" style="color:hsl(262 60% 35%);">
                                    Receipt: {{ election.receipt_number }}
                                </p>
                            </div>
                            <svg
                                class="h-3.5 w-3.5 shrink-0 transition-transform duration-200"
                                :class="{ 'rotate-180': isSelectionsExpanded(election.id) }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                style="color:hsl(262 60% 35%);"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div v-if="election.receipt_id" class="flex items-center gap-2 shrink-0">
                            <a :href="`/ballot-receipt/${election.receipt_id}`"
                                class="text-xs font-medium px-2.5 py-1 rounded border"
                                style="border-color:hsl(262 40% 88%); color:hsl(262 60% 35%); background:#fff;">
                                View Receipt
                            </a>
                            <button
                                type="button"
                                :disabled="downloading"
                                @click="downloadPdf(election.pdf_url, election.pdf_filename)"
                                class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded text-white disabled:opacity-60"
                                style="background:hsl(221 83% 53%);">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ downloading ? '...' : 'PDF' }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-show="isSelectionsExpanded(election.id)"
                        class="px-5 pb-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2"
                    >
                        <div
                            v-for="vote in election.user_votes"
                            :key="vote.position_id"
                            class="rounded border px-3 py-2"
                            style="border-color:hsl(262 40% 88%); background:#fff;"
                        >
                            <p class="text-xs" style="color:hsl(240 3.8% 46.1%);">{{ vote.position }}</p>
                            <p class="text-sm font-medium" style="color:hsl(240 10% 3.9%);">{{ vote.candidate }}</p>
                        </div>
                    </div>
                </div>

                <!-- Ballot form -->
                <div v-if="ballotMode[election.id]" class="p-4 pt-5 border-b" style="border-color:hsl(240 5.9% 90%); background:hsl(221 83% 98%);">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">Cast Your Ballot</p>
                            <p class="text-xs mt-0.5" style="color:hsl(240 3.8% 46.1%);">Select one candidate per position. Review carefully — votes cannot be changed.</p>
                        </div>
                        <button class="text-xs font-medium px-2 py-1 rounded" style="color:hsl(240 3.8% 46.1%);"
                            @click="cancelBallot(election.id)">Cancel</button>
                    </div>

                    <p v-if="submitForm.errors.ballot" class="mb-3 text-xs rounded px-3 py-2"
                        style="background:hsl(0 84% 95%); color:hsl(0 72% 40%);">
                        {{ submitForm.errors.ballot }}
                    </p>

                    <div class="space-y-5">
                        <CandidatePositionRow
                            v-for="group in positionGroups(election)"
                            :key="group.position_id"
                            :group="group"
                            selectable
                            :label-style="{ color: 'hsl(221 83% 35%)' }"
                            :is-selected="(candidateId) => isSelected(election.id, group.position_id, candidateId)"
                            @select="selectCandidate(election.id, group.position_id, $event)"
                        />
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button class="px-3 py-1.5 rounded border text-xs font-medium"
                            style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%);"
                            @click="cancelBallot(election.id)">Cancel</button>
                        <button class="px-4 py-1.5 rounded text-xs font-semibold text-white transition-opacity"
                            :style="{ background: ballotComplete(election) ? 'hsl(221 83% 53%)' : 'hsl(240 3.8% 65%)', cursor: ballotComplete(election) ? 'pointer' : 'not-allowed' }"
                            :disabled="!ballotComplete(election)"
                            @click="openConfirm(election)">
                            Submit Ballot
                        </button>
                    </div>
                </div>

                <div v-if="expanded[election.id]" class="p-4">
                    <div v-if="election.candidates.length === 0"
                        class="text-center py-8 text-sm" style="color:hsl(240 3.8% 46.1%);">
                        No candidates added yet.
                    </div>
                    <div v-else class="space-y-4">
                        <CandidatePositionRow
                            v-for="group in positionGroups(election)"
                            :key="group.position_id"
                            :group="group"
                            show-platform
                            @select="() => {}"
                        />
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            :show="!!confirmElection"
            title="Confirm Your Ballot"
            description="Are you sure you want to submit your vote? This action cannot be undone."
            @close="closeConfirm">
            <template v-if="confirmElection">
                <div
                    class="space-y-0 max-h-[min(40vh,16rem)] overflow-y-auto overscroll-contain rounded-md border -mx-1 px-3 py-1"
                    style="border-color:hsl(240 5.9% 90%);"
                >
                    <div
                        v-for="group in positionGroups(confirmElection)"
                        :key="group.position_id"
                        class="flex items-start justify-between gap-3 text-sm py-2.5 border-b last:border-0"
                        style="border-color:hsl(240 5.9% 90%);"
                    >
                        <span class="shrink-0" style="color:hsl(240 3.8% 46.1%);">{{ group.position }}</span>
                        <span class="font-medium text-right break-words" style="color:hsl(240 10% 3.9%);">
                            {{ confirmElection.candidates.find(c => c.id === selections[confirmElection.id][group.position_id])?.name }}
                        </span>
                    </div>
                </div>
            </template>
            <template v-if="confirmElection" #footer>
                <div class="flex justify-end gap-2">
                    <button class="px-3 py-1.5 rounded border text-xs font-medium"
                        style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%);"
                        :disabled="submitForm.processing"
                        @click="closeConfirm">Go Back</button>
                    <button class="px-4 py-1.5 rounded text-xs font-semibold text-white"
                        style="background:hsl(221 83% 53%);"
                        :disabled="submitForm.processing"
                        @click="submitBallot(confirmElection)">
                        {{ submitForm.processing ? 'Submitting…' : 'Confirm & Submit' }}
                    </button>
                </div>
            </template>
        </Dialog>

        <Teleport to="body">
            <div
                v-if="showVoteSuccessAnimation"
                class="fixed inset-0 z-[100] flex flex-col items-center justify-center px-6"
                style="background: rgba(255, 255, 255, 0.96);"
            >
                <div class="w-full max-w-sm sm:max-w-md">
                    <LottieAnimation
                        :src="voteSuccessAnimationSrc"
                        :loop="false"
                        @complete="onVoteAnimationComplete"
                    />
                </div>
                <div class="mt-4 text-center space-y-1">
                    <p class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">
                        Ballot submitted
                    </p>
                    <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                        {{ voteProcessingMessage }}
                    </p>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
