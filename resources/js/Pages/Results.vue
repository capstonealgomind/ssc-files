<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Select from '@/Components/ui/Select.vue';
import Label from '@/Components/ui/Label.vue';
import LottieAnimation from '@/Components/LottieAnimation.vue';

const props = defineProps({
    electionOptions: { type: Array, default: () => [] },
    selectedElectionId: { type: Number, default: null },
    selectedElection: { type: Object, default: null },
    results: { type: Object, default: null },
});

const clockAnimationSrc = '/animation/clock%20time.json';
const expandedPositions = ref({});

const selectedElectionValue = computed({
    get: () => (props.selectedElectionId ? String(props.selectedElectionId) : ''),
    set: (value) => {
        if (!value) return;
        router.get('/results', { election_id: value }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
});

const summaryCards = computed(() => {
    const summary = props.results?.summary;
    if (!summary) return [];

    return [
        { label: 'Ballots cast', value: summary.ballots_cast },
        { label: 'Turnout', value: `${summary.turnout_rate}%` },
        { label: 'Positions', value: summary.positions },
        { label: 'Candidates', value: summary.candidates },
    ];
});

const winners = computed(() => props.results?.winners ?? []);
const positions = computed(() => props.results?.positions ?? []);
const isPublished = computed(() => Boolean(props.selectedElection?.is_published));

function initials(name) {
    if (!name) return '?';
    return name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function isExpanded(positionId) {
    return expandedPositions.value[positionId] !== false;
}

function togglePosition(positionId) {
    expandedPositions.value = {
        ...expandedPositions.value,
        [positionId]: !isExpanded(positionId),
    };
}

function phaseLabel(phase) {
    if (phase === 'open') return 'Voting in progress';
    if (phase === 'upcoming') return 'Voting not yet open';
    return 'Official results published';
}

function phaseStyle(phase) {
    if (phase === 'open') {
        return { bg: 'hsl(38 92% 94%)', color: 'hsl(38 62% 30%)' };
    }
    if (phase === 'upcoming') {
        return { bg: 'hsl(221 83% 94%)', color: 'hsl(221 83% 35%)' };
    }
    return { bg: 'hsl(142 76% 94%)', color: 'hsl(142 71% 29%)' };
}
</script>

<template>
    <AppLayout>
        <Head title="Results" />

        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Results</h1>
        </template>

        <div class="w-full space-y-5">
            <!-- Hero -->
            <section
                class="rounded-xl border overflow-hidden sscevs-panel"
                style="border-color: hsl(240 5.9% 90%); background: linear-gradient(135deg, hsl(221 83% 98%) 0%, #fff 55%, hsl(43 60% 97%) 100%);"
            >
                <div class="px-5 py-6 sm:px-7 sm:py-7">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div class="min-w-0 max-w-2xl">
                            <p class="text-xs font-semibold uppercase tracking-wide" style="color: hsl(221 83% 40%);">
                                Official election results
                            </p>
                            <h2 class="text-xl sm:text-2xl font-semibold mt-1.5 tracking-tight" style="color: hsl(240 10% 3.9%);">
                                {{ selectedElection?.title || 'Election Results' }}
                            </h2>
                            <p class="text-sm mt-2 leading-relaxed" style="color: hsl(240 3.8% 46.1%);">
                                {{ selectedElection?.description
                                    || 'View published winners and full tallies after the voting period ends.' }}
                            </p>

                            <div v-if="selectedElection" class="mt-4 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :style="{
                                        background: phaseStyle(selectedElection.voting_phase).bg,
                                        color: phaseStyle(selectedElection.voting_phase).color,
                                    }"
                                >
                                    {{ phaseLabel(selectedElection.voting_phase) }}
                                </span>
                                <span class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                                    {{ selectedElection.voting_period }}
                                </span>
                            </div>
                        </div>

                        <div class="w-full lg:w-80 space-y-1.5">
                            <Label html-for="results-election">Select election</Label>
                            <Select
                                id="results-election"
                                v-model="selectedElectionValue"
                                :options="electionOptions"
                                placeholder="Select election"
                                :disabled="electionOptions.length === 0"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Empty elections -->
            <div
                v-if="electionOptions.length === 0"
                class="rounded-xl border px-6 py-16 text-center sscevs-panel"
                style="border-color: hsl(240 5.9% 90%); background: #fff;"
            >
                <div
                    class="mx-auto h-14 w-14 rounded-full flex items-center justify-center mb-4"
                    style="background: hsl(240 4.8% 95.9%);"
                >
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: hsl(240 3.8% 46.1%);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold" style="color: hsl(240 10% 3.9%);">No elections available</p>
                <p class="text-sm mt-1 max-w-md mx-auto" style="color: hsl(240 3.8% 46.1%);">
                    Results will appear here once elections are scheduled and voting has concluded.
                </p>
            </div>

            <!-- Not published yet -->
            <div
                v-else-if="selectedElection && !isPublished"
                class="rounded-xl border overflow-hidden sscevs-panel"
                style="border-color: hsl(240 5.9% 90%); background: #fff;"
            >
                <div class="px-6 py-12 sm:px-10 text-center">
                    <div class="mx-auto mb-4 h-28 w-28 sm:h-32 sm:w-32">
                        <LottieAnimation :src="clockAnimationSrc" />
                    </div>
                    <h3 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">
                        {{ selectedElection.voting_phase === 'open' ? 'Voting is still open' : 'Results not available yet' }}
                    </h3>
                    <p class="text-sm mt-2 max-w-lg mx-auto leading-relaxed" style="color: hsl(240 3.8% 46.1%);">
                        Official winners and tallies are published only after the voting period ends.
                        {{ selectedElection.voting_phase === 'open'
                            ? 'You can still cast or review your ballot on the Elections page.'
                            : 'Please check back once voting opens and concludes.' }}
                    </p>
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                        <Link
                            href="/elections"
                            class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium text-white"
                            style="background: hsl(221 83% 53%);"
                        >
                            Go to Elections
                        </Link>
                        <Link
                            href="/my-votes"
                            class="inline-flex items-center justify-center rounded-md border px-4 py-2 text-sm font-medium"
                            style="border-color: hsl(240 5.9% 90%); color: hsl(240 10% 3.9%);"
                        >
                            View My Votes
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Published results -->
            <template v-else-if="results">
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
                    <div
                        v-for="card in summaryCards"
                        :key="card.label"
                        class="rounded-xl border px-4 py-3.5 sscevs-panel"
                        style="border-color: hsl(240 5.9% 90%); background: #fff;"
                    >
                        <p class="text-[11px] font-medium uppercase tracking-wide" style="color: hsl(240 3.8% 46.1%);">
                            {{ card.label }}
                        </p>
                        <p class="text-2xl font-semibold mt-1 tabular-nums tracking-tight" style="color: hsl(240 10% 3.9%);">
                            {{ typeof card.value === 'number' ? card.value.toLocaleString() : card.value }}
                        </p>
                    </div>
                </div>

                <!-- Winners -->
                <section class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-8 w-8 rounded-lg flex items-center justify-center"
                            style="background: hsl(43 96% 92%); color: hsl(43 74% 35%);"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M6 4h12v2.5c0 2.5-1.5 4.5-3.5 5.5L12 18l-2.5-6C7.5 11 6 9 6 6.5V4zm2 0v2.5c0 1.6.9 3 2.2 3.7L12 13.5l1.8-3.3C15.1 9.5 16 8.1 16 6.5V4H8z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Winners by Position</h3>
                            <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">Official declared winners for this election</p>
                        </div>
                    </div>

                    <div
                        v-if="winners.length === 0"
                        class="rounded-xl border px-5 py-10 text-center text-sm"
                        style="border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%); background: #fff;"
                    >
                        No winners to display for this election.
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4"
                    >
                        <article
                            v-for="winner in winners"
                            :key="winner.position_id"
                            class="rounded-xl border overflow-hidden sscevs-panel flex flex-col"
                            style="border-color: hsl(240 5.9% 90%); background: #fff;"
                        >
                            <div
                                class="px-4 py-2.5 text-center border-b"
                                style="background: hsl(240 4.8% 98%); border-color: hsl(240 5.9% 90%);"
                            >
                                <h4 class="text-sm font-bold" style="color: hsl(240 10% 3.9%);">
                                    {{ winner.position_name }}
                                </h4>
                            </div>

                            <div class="flex-1 flex flex-col items-center text-center px-4 py-6">
                                <div class="relative mb-4">
                                    <img
                                        v-if="winner.photo_url"
                                        :src="winner.photo_url"
                                        :alt="winner.candidate_name"
                                        class="h-20 w-20 rounded-full object-cover border-2"
                                        :style="{ borderColor: winner.department_color_hex || 'hsl(221 83% 53%)' }"
                                    />
                                    <div
                                        v-else
                                        class="h-20 w-20 rounded-full flex items-center justify-center text-lg font-semibold border-2"
                                        :style="{
                                            backgroundColor: `${winner.department_color_hex || '#2563eb'}18`,
                                            color: winner.department_color_hex || '#2563eb',
                                            borderColor: `${winner.department_color_hex || '#2563eb'}55`,
                                        }"
                                    >
                                        {{ initials(winner.candidate_name) }}
                                    </div>
                                    <span
                                        class="absolute -bottom-1 -right-1 h-7 w-7 rounded-full flex items-center justify-center border-2 border-white"
                                        :style="{ backgroundColor: `${winner.department_color_hex || '#2563eb'}22` }"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                            :style="{ color: winner.department_color_hex || '#2563eb' }"
                                            aria-hidden="true"
                                        >
                                            <path d="M6 4h12v2.5c0 2.5-1.5 4.5-3.5 5.5L12 18l-2.5-6C7.5 11 6 9 6 6.5V4zm2 0v2.5c0 1.6.9 3 2.2 3.7L12 13.5l1.8-3.3C15.1 9.5 16 8.1 16 6.5V4H8z" />
                                        </svg>
                                    </span>
                                </div>

                                <p class="text-base font-bold leading-snug" style="color: hsl(240 10% 3.9%);">
                                    {{ winner.candidate_name }}
                                </p>
                                <p class="text-sm mt-1" style="color: hsl(240 3.8% 46.1%);">
                                    {{ winner.partylist_label || winner.partylist_name }}
                                </p>
                            </div>

                            <div
                                class="border-t px-4 py-3 flex items-center justify-between"
                                style="border-color: hsl(240 5.9% 90%); background: hsl(240 4.8% 98%);"
                            >
                                <span class="text-xs font-medium" style="color: hsl(240 3.8% 46.1%);">Winning votes</span>
                                <span class="text-sm font-bold tabular-nums" style="color: hsl(240 10% 3.9%);">
                                    {{ winner.votes.toLocaleString() }}
                                    <span class="font-medium" style="color: hsl(240 3.8% 46.1%);">
                                        ({{ winner.percentage }}%)
                                    </span>
                                </span>
                            </div>
                        </article>
                    </div>
                </section>

                <!-- Full tally -->
                <section class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-8 w-8 rounded-lg flex items-center justify-center"
                            style="background: hsl(221 83% 94%); color: hsl(221 83% 40%);"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Full Vote Tally</h3>
                            <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">Complete results for every position</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <article
                            v-for="position in positions"
                            :key="position.id"
                            class="rounded-xl border overflow-hidden sscevs-panel"
                            style="border-color: hsl(240 5.9% 90%); background: #fff;"
                        >
                            <button
                                type="button"
                                class="w-full flex items-center justify-between gap-3 px-4 py-3.5 text-left transition-colors hover:bg-[hsl(240_4.8%_98%)]"
                                :aria-expanded="isExpanded(position.id)"
                                @click="togglePosition(position.id)"
                            >
                                <div class="min-w-0">
                                    <h4 class="text-sm font-semibold" style="color: hsl(240 10% 3.9%);">
                                        {{ position.name }}
                                    </h4>
                                    <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                        {{ position.total_votes.toLocaleString() }}
                                        {{ position.total_votes === 1 ? 'vote' : 'votes' }}
                                        · {{ position.candidates.length }}
                                        {{ position.candidates.length === 1 ? 'candidate' : 'candidates' }}
                                    </p>
                                </div>
                                <svg
                                    class="h-4 w-4 shrink-0 transition-transform duration-200"
                                    :class="{ 'rotate-180': isExpanded(position.id) }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    style="color: hsl(240 3.8% 46.1%);"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                v-show="isExpanded(position.id)"
                                class="border-t divide-y"
                                style="border-color: hsl(240 5.9% 90%);"
                            >
                                <div
                                    v-for="candidate in position.candidates"
                                    :key="candidate.id"
                                    class="px-4 py-3"
                                    style="border-color: hsl(240 5.9% 94%);"
                                >
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <img
                                                v-if="candidate.photo_url"
                                                :src="candidate.photo_url"
                                                :alt="candidate.name"
                                                class="h-9 w-9 rounded-full object-cover shrink-0 border"
                                                style="border-color: hsl(240 5.9% 90%);"
                                            />
                                            <div
                                                v-else
                                                class="h-9 w-9 rounded-full flex items-center justify-center text-[11px] font-semibold shrink-0"
                                                style="background: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 30%);"
                                            >
                                                {{ initials(candidate.name) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold leading-snug" style="color: hsl(240 10% 3.9%);">
                                                    <svg
                                                        v-if="candidate.show_trophy"
                                                        class="inline-block h-3.5 w-3.5 mr-1 -mt-0.5"
                                                        viewBox="0 0 24 24"
                                                        fill="currentColor"
                                                        style="color: hsl(43 96% 50%);"
                                                        aria-hidden="true"
                                                    >
                                                        <path d="M6 4h12v2.5c0 2.5-1.5 4.5-3.5 5.5L12 18l-2.5-6C7.5 11 6 9 6 6.5V4zm2 0v2.5c0 1.6.9 3 2.2 3.7L12 13.5l1.8-3.3C15.1 9.5 16 8.1 16 6.5V4H8z" />
                                                    </svg>
                                                    {{ candidate.name }}
                                                </p>
                                                <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                                    {{ candidate.partylist_label }}
                                                </p>
                                            </div>
                                        </div>
                                        <p class="text-sm font-semibold shrink-0 tabular-nums" style="color: hsl(240 10% 3.9%);">
                                            {{ candidate.votes.toLocaleString() }}
                                            <span class="font-medium text-xs" style="color: hsl(240 3.8% 46.1%);">
                                                ({{ candidate.percentage }}%)
                                            </span>
                                        </p>
                                    </div>

                                    <div
                                        class="h-2 rounded-full overflow-hidden"
                                        style="background: hsl(240 4.8% 95.9%);"
                                    >
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :style="{
                                                width: `${Math.max(candidate.percentage, candidate.votes > 0 ? 2 : 0)}%`,
                                                background: candidate.is_leader
                                                    ? 'hsl(221 83% 53%)'
                                                    : 'hsl(240 5.9% 78%)',
                                            }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
