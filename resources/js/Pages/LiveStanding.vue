<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GuestHeaderBrand from '@/Components/GuestHeaderBrand.vue';
import Button from '@/Components/ui/Button.vue';
import Select from '@/Components/ui/Select.vue';
import MonitoringPositionCard from '@/Components/monitoring/MonitoringPositionCard.vue';

const props = defineProps({
    electionOptions: { type: Array, default: () => [] },
    hasActiveElections: { type: Boolean, default: false },
    selectedElectionId: { type: Number, default: null },
    selectedElection: { type: Object, default: null },
    liveCountingActive: { type: Boolean, default: false },
    statusMessage: { type: String, default: '' },
    positions: { type: Array, default: () => [] },
    participation: { type: Object, default: null },
    updatedAt: { type: String, default: '' },
});

const POLL_INTERVAL_MS = 10000;
let pollTimer = null;

const positionFilter = ref('all');

const selectedElectionValue = computed({
    get: () => (props.selectedElectionId ? String(props.selectedElectionId) : ''),
    set: (value) => {
        if (!value) return;

        const option = props.electionOptions.find((item) => item.value === value);
        if (option?.disabled) return;

        router.get('/live-standing', { election_id: value }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
});

const positionFilterOptions = computed(() => [
    { value: 'all', label: 'All Positions' },
    ...props.positions.map((position) => ({
        value: String(position.id),
        label: position.name,
    })),
]);

const filteredPositions = computed(() => {
    if (positionFilter.value === 'all') {
        return props.positions;
    }

    return props.positions.filter((position) => String(position.id) === positionFilter.value);
});

const statusDotColor = computed(() => {
    if (!props.hasActiveElections) return 'hsl(240 3.8% 46.1%)';
    if (props.liveCountingActive) return 'hsl(142 71% 45%)';
    if (props.selectedElection?.voting_phase === 'upcoming') return 'hsl(221 83% 53%)';
    return 'hsl(240 3.8% 46.1%)';
});

const statusTextColor = computed(() => {
    if (!props.hasActiveElections) return 'hsl(240 5.9% 10%)';
    if (props.liveCountingActive) return 'hsl(142 71% 29%)';
    if (props.selectedElection?.voting_phase === 'upcoming') return 'hsl(221 83% 35%)';
    return 'hsl(240 5.9% 10%)';
});

const summaryCards = computed(() => {
    const p = props.participation;
    if (!p) return [];

    return [
        { label: 'Ballots cast', value: p.ballots_cast },
        { label: 'Eligible voters', value: p.eligible_voters },
        { label: 'Turnout', value: `${p.turnout_rate}%` },
        { label: 'Pending', value: p.pending_voters },
    ];
});

function refreshStandings() {
    router.reload({
        only: [
            'selectedElectionId',
            'selectedElection',
            'liveCountingActive',
            'statusMessage',
            'positions',
            'participation',
            'updatedAt',
            'electionOptions',
            'hasActiveElections',
        ],
        preserveScroll: true,
        showProgress: false,
    });
}

function stopPolling() {
    if (pollTimer) {
        window.clearInterval(pollTimer);
        pollTimer = null;
    }
}

function startPolling() {
    stopPolling();
    if (!props.hasActiveElections || !props.selectedElectionId) return;
    pollTimer = window.setInterval(refreshStandings, POLL_INTERVAL_MS);
}

onMounted(() => {
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});

watch(
    () => [props.selectedElectionId, props.hasActiveElections],
    () => {
        positionFilter.value = 'all';
        startPolling();
    },
);
</script>

<template>
    <Head title="Live Standing" />

    <div class="guest-shell min-h-screen" style="background: linear-gradient(180deg, hsl(221 83% 98%) 0%, hsl(0 0% 100%) 28%, hsl(43 40% 98%) 100%);">
        <header class="guest-header">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="min-h-16 flex items-center justify-between gap-3 py-1.5">
                    <GuestHeaderBrand />

                    <div class="flex items-center gap-2">
                        <Link href="/">
                            <Button variant="ghost" size="sm" class="gap-1.5">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <span class="hidden sm:inline">Back to home</span>
                                <span class="sm:hidden">Home</span>
                            </Button>
                        </Link>
                        <Link href="/login">
                            <Button variant="navy" size="sm">Log in</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-5">
            <section
                class="rounded-xl border overflow-hidden"
                style="border-color: hsl(240 5.9% 90%); background: linear-gradient(135deg, hsl(221 83% 98%) 0%, #fff 55%, hsl(43 60% 97%) 100%);"
            >
                <div class="px-5 py-6 sm:px-7 sm:py-7">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em]" style="color: hsl(221 83% 40%);">
                                Public live board
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight" style="color: hsl(240 10% 3.9%);">
                                Live Standing
                            </h1>
                            <p class="text-sm max-w-xl" style="color: hsl(240 3.8% 46.1%);">
                                Follow candidate standings as votes come in. This page refreshes automatically every 10 seconds.
                            </p>
                        </div>

                        <div
                            v-if="electionOptions.length > 0"
                            class="w-full lg:w-80"
                        >
                            <p class="text-xs font-medium mb-1.5" style="color: hsl(240 3.8% 46.1%);">Election</p>
                            <Select
                                id="live-standing-election"
                                v-model="selectedElectionValue"
                                :options="electionOptions"
                                placeholder="Select election"
                            />
                            <p
                                v-if="!hasActiveElections"
                                class="text-xs mt-2"
                                style="color: hsl(240 3.8% 46.1%);"
                            >
                                Ended elections are listed for reference and cannot be selected.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <div
                class="rounded-xl border px-4 py-3 flex flex-wrap items-center gap-2.5"
                :style="hasActiveElections
                    ? 'border-color: hsl(221 83% 88%); background-color: hsl(221 83% 96%);'
                    : 'border-color: hsl(38 92% 80%); background-color: hsl(38 92% 95%);'"
            >
                <span
                    class="h-2.5 w-2.5 rounded-full shrink-0"
                    :class="liveCountingActive ? 'animate-pulse' : ''"
                    :style="{ backgroundColor: statusDotColor }"
                />
                <p
                    class="text-sm font-bold tracking-wide uppercase"
                    :style="{ color: statusTextColor }"
                >
                    {{ statusMessage }}
                </p>
                <p
                    v-if="hasActiveElections && updatedAt"
                    class="text-xs ml-auto"
                    style="color: hsl(240 3.8% 46.1%);"
                >
                    Updated {{ updatedAt }}
                </p>
            </div>

            <template v-if="!hasActiveElections">
                <div
                    class="rounded-xl border px-5 py-16 text-center"
                    style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
                >
                    <p class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">
                        No active elections
                    </p>
                    <p class="text-sm mt-2 max-w-md mx-auto" style="color: hsl(240 3.8% 46.1%);">
                        There is no election currently open for live standing. Ended elections appear in the dropdown as disabled and marked with (ended).
                    </p>
                </div>
            </template>

            <template v-else-if="selectedElection">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div
                        v-for="card in summaryCards"
                        :key="card.label"
                        class="rounded-xl border px-4 py-3"
                        style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
                    >
                        <p class="text-[11px] font-medium" style="color: hsl(240 3.8% 46.1%);">{{ card.label }}</p>
                        <p class="text-xl font-semibold mt-0.5 tabular-nums tracking-tight" style="color: hsl(240 10% 3.9%);">
                            {{ card.value }}
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-xl border overflow-hidden"
                    style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
                >
                    <div class="px-4 sm:px-5 py-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: hsl(240 5.9% 90%);">
                        <div>
                            <h2 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">
                                {{ selectedElection.title }}
                            </h2>
                            <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                {{ selectedElection.voting_period }}
                            </p>
                        </div>
                        <div class="w-full sm:w-52">
                            <Select
                                id="live-standing-position"
                                v-model="positionFilter"
                                :options="positionFilterOptions"
                                placeholder="All Positions"
                            />
                        </div>
                    </div>

                    <div class="p-4 sm:p-5">
                        <div
                            v-if="filteredPositions.length === 0"
                            class="py-16 text-center"
                            style="color: hsl(240 3.8% 46.1%);"
                        >
                            No candidates have been added to this election yet.
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
                        >
                            <MonitoringPositionCard
                                v-for="position in filteredPositions"
                                :key="position.id"
                                :position="position"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>
</template>
