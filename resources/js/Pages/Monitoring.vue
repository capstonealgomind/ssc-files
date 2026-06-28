<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Select from '@/Components/ui/Select.vue';
import MonitoringPositionCard from '@/Components/monitoring/MonitoringPositionCard.vue';
import FinalResultsWinnerCard from '@/Components/monitoring/FinalResultsWinnerCard.vue';
import FinalResultsPartyCard from '@/Components/monitoring/FinalResultsPartyCard.vue';
import TurnoutByDepartmentChart from '@/Components/monitoring/TurnoutByDepartmentChart.vue';
import TurnoutByYearLevelChart from '@/Components/monitoring/TurnoutByYearLevelChart.vue';
import LeadingByPartylistChart from '@/Components/monitoring/LeadingByPartylistChart.vue';

const props = defineProps({
    electionOptions: {
        type: Array,
        default: () => [],
    },
    selectedElectionId: {
        type: Number,
        default: null,
    },
    selectedElection: {
        type: Object,
        default: null,
    },
    liveCountingActive: {
        type: Boolean,
        default: false,
    },
    statusMessage: {
        type: String,
        default: 'NO ELECTION SELECTED',
    },
    positions: {
        type: Array,
        default: () => [],
    },
    participation: {
        type: Object,
        default: null,
    },
    analytics: {
        type: Object,
        default: null,
    },
    finalResults: {
        type: Object,
        default: null,
    },
    turnoutByDepartment: {
        type: Array,
        default: () => [],
    },
    turnoutByYearLevel: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('live');
const positionFilter = ref('all');
let refreshTimer = null;

const tabs = [
    {
        id: 'live',
        label: 'Live Monitoring',
        icon: 'chart',
    },
    {
        id: 'analytics',
        label: 'Analytics',
        icon: 'bars',
    },
    {
        id: 'results',
        label: 'Final Results',
        icon: 'medal',
    },
    {
        id: 'participation',
        label: 'Participation',
        icon: 'clock',
    },
];

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

    return props.positions.filter(
        (position) => String(position.id) === positionFilter.value,
    );
});

const selectedElectionValue = computed({
    get: () => (props.selectedElectionId ? String(props.selectedElectionId) : ''),
    set: (value) => {
        if (!value) return;
        router.get('/monitoring', { election_id: value }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
});

const statusDotColor = computed(() => {
    if (props.liveCountingActive) return 'hsl(142 71% 45%)';
    if (props.statusMessage === 'VOTING NOT YET OPEN') return 'hsl(38 92% 50%)';
    return 'hsl(240 3.8% 65%)';
});

const statusTextColor = computed(() =>
    props.liveCountingActive ? 'hsl(262 83% 35%)' : 'hsl(240 5.9% 10%)',
);

const partyBreakdown = computed(() => {
    if (!props.analytics?.leading_by_party) return [];
    return Object.entries(props.analytics.leading_by_party).map(([label, count]) => ({
        label,
        count,
    }));
});

const winners = computed(() => props.finalResults?.winners ?? []);
const partylistPerformance = computed(() => props.finalResults?.partylist_performance ?? []);

function switchTab(tabId) {
    activeTab.value = tabId;
}

function refreshData() {
    router.reload({
        only: [
            'selectedElectionId',
            'selectedElection',
            'liveCountingActive',
            'statusMessage',
            'positions',
            'participation',
            'analytics',
            'finalResults',
            'turnoutByDepartment',
            'turnoutByYearLevel',
        ],
        preserveScroll: true,
    });
}

onMounted(() => {
    if (props.liveCountingActive) {
        refreshTimer = window.setInterval(refreshData, 15000);
    }
});

onUnmounted(() => {
    if (refreshTimer) {
        window.clearInterval(refreshTimer);
    }
});
</script>

<template>
    <AppLayout>
        <Head title="Monitoring" />

        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Monitoring</h1>
        </template>

        <div class="w-full space-y-5">
            <div
                v-if="electionOptions.length > 1"
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            >
                <div>
                    <p class="text-sm font-medium" style="color: hsl(240 10% 3.9%);">Election</p>
                    <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                        Choose which election to monitor.
                    </p>
                </div>
                <div class="w-full sm:w-80">
                    <Select
                        id="monitoring-election"
                        v-model="selectedElectionValue"
                        :options="electionOptions"
                        placeholder="Select election"
                    />
                </div>
            </div>

            <div
                class="rounded-xl border overflow-hidden"
                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
            >
                <div
                    class="flex flex-wrap gap-1 border-b px-2 pt-2"
                    style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 98%);"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px"
                        :style="activeTab === tab.id
                            ? 'color: hsl(262 83% 45%); border-color: hsl(262 83% 58%);'
                            : 'color: hsl(240 3.8% 46.1%); border-color: transparent;'"
                        @click="switchTab(tab.id)"
                    >
                        <svg
                            v-if="tab.icon === 'chart'"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        <svg
                            v-else-if="tab.icon === 'bars'"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        <svg
                            v-else-if="tab.icon === 'medal'"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.875C5.25 3.839 6.089 3 7.125 3h9.75c1.036 0 1.875.839 1.875 1.875v4.125c0 1.036-.839 1.875-1.875 1.875h-9.75A1.875 1.875 0 015.25 9V4.875z" />
                        </svg>
                        <svg
                            v-else
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ tab.label }}
                    </button>
                </div>

                <div
                    class="px-4 py-3 border-b flex items-center gap-2.5"
                    style="border-color: hsl(221 83% 88%); background-color: hsl(221 83% 96%);"
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
                        v-if="selectedElection"
                        class="ml-auto text-xs hidden sm:block"
                        style="color: hsl(240 3.8% 46.1%);"
                    >
                        {{ selectedElection.title }}
                    </p>
                </div>

                <div class="p-4 sm:p-5">
                    <template v-if="!selectedElection">
                        <div class="py-16 text-center">
                            <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                                No elections available to monitor yet.
                            </p>
                        </div>
                    </template>

                    <template v-else-if="activeTab === 'live'">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                            <div class="flex items-center gap-2">
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    style="color: hsl(43 96% 56%);"
                                    aria-hidden="true"
                                >
                                    <path d="M6 4h12v2.5c0 2.5-1.5 4.5-3.5 5.5L12 18l-2.5-6C7.5 11 6 9 6 6.5V4zm2 0v2.5c0 1.6.9 3 2.2 3.7L12 13.5l1.8-3.3C15.1 9.5 16 8.1 16 6.5V4H8z" />
                                </svg>
                                <h2 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">
                                    Leading Candidates
                                </h2>
                            </div>
                            <div class="w-full sm:w-52">
                                <Select
                                    id="position-filter"
                                    v-model="positionFilter"
                                    :options="positionFilterOptions"
                                    placeholder="All Positions"
                                />
                            </div>
                        </div>

                        <div
                            v-if="filteredPositions.length === 0"
                            class="py-16 text-center rounded-xl border"
                            style="border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
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
                    </template>

                    <template v-else-if="activeTab === 'results'">
                        <div class="space-y-8">
                            <section class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        style="color: hsl(262 83% 45%);"
                                        aria-hidden="true"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.875C5.25 3.839 6.089 3 7.125 3h9.75c1.036 0 1.875.839 1.875 1.875v4.125c0 1.036-.839 1.875-1.875 1.875h-9.75A1.875 1.875 0 015.25 9V4.875z" />
                                    </svg>
                                    <h2 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">
                                        Winners per Position
                                    </h2>
                                </div>

                                <div
                                    v-if="winners.length === 0"
                                    class="py-16 text-center rounded-xl border"
                                    style="border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
                                >
                                    No winners to display yet.
                                </div>

                                <div
                                    v-else
                                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                                >
                                    <FinalResultsWinnerCard
                                        v-for="winner in winners"
                                        :key="winner.position_id"
                                        :winner="winner"
                                    />
                                </div>
                            </section>

                            <section class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        style="color: hsl(262 83% 45%);"
                                        aria-hidden="true"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                    </svg>
                                    <h2 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">
                                        Party List Performance
                                    </h2>
                                </div>

                                <div
                                    v-if="partylistPerformance.length === 0"
                                    class="py-16 text-center rounded-xl border"
                                    style="border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
                                >
                                    No party list data available yet.
                                </div>

                                <div
                                    v-else
                                    class="grid grid-cols-1 xl:grid-cols-2 gap-4"
                                >
                                    <FinalResultsPartyCard
                                        v-for="party in partylistPerformance"
                                        :key="party.label"
                                        :party="party"
                                    />
                                </div>
                            </section>
                        </div>
                    </template>

                    <template v-else-if="activeTab === 'analytics'">
                        <div class="space-y-4">
                            <div>
                                <h2 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Election analytics</h2>
                                <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                    Turnout breakdown for {{ selectedElection.title }}.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
                                <div
                                    class="rounded-lg border px-3 py-2.5 sscevs-panel"
                                    style="border-color: hsl(240 5.9% 90%);"
                                >
                                    <p class="text-[11px] font-medium" style="color: hsl(240 3.8% 46.1%);">Total votes</p>
                                    <p class="text-xl font-semibold mt-0.5 tabular-nums tracking-tight" style="color: hsl(240 10% 3.9%);">
                                        {{ analytics?.total_votes ?? 0 }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-lg border px-3 py-2.5 sscevs-panel"
                                    style="border-color: hsl(240 5.9% 90%);"
                                >
                                    <p class="text-[11px] font-medium" style="color: hsl(240 3.8% 46.1%);">Positions</p>
                                    <p class="text-xl font-semibold mt-0.5 tabular-nums tracking-tight" style="color: hsl(240 10% 3.9%);">
                                        {{ analytics?.positions_tracked ?? 0 }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-lg border px-3 py-2.5 sscevs-panel"
                                    style="border-color: hsl(240 5.9% 90%);"
                                >
                                    <p class="text-[11px] font-medium" style="color: hsl(240 3.8% 46.1%);">Candidates</p>
                                    <p class="text-xl font-semibold mt-0.5 tabular-nums tracking-tight" style="color: hsl(240 10% 3.9%);">
                                        {{ analytics?.candidates_running ?? 0 }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-lg border px-3 py-2.5 sscevs-panel"
                                    style="border-color: hsl(240 5.9% 90%);"
                                >
                                    <p class="text-[11px] font-medium" style="color: hsl(240 3.8% 46.1%);">Avg votes / position</p>
                                    <p class="text-xl font-semibold mt-0.5 tabular-nums tracking-tight" style="color: hsl(240 10% 3.9%);">
                                        {{ analytics?.avg_votes_per_position ?? 0 }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">
                                <TurnoutByDepartmentChart
                                    :departments="turnoutByDepartment"
                                    :subtitle="selectedElection ? `Turnout breakdown for ${selectedElection.title}` : 'Voted vs registered voters by department'"
                                />
                                <TurnoutByYearLevelChart :year-levels="turnoutByYearLevel" />
                            </div>

                            <LeadingByPartylistChart :items="partyBreakdown" />
                        </div>
                    </template>

                    <template v-else-if="activeTab === 'participation'">
                        <div class="space-y-5">
                            <div>
                                <h2 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Voter participation</h2>
                                <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                    Turnout among verified voters for this election.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                                <div class="rounded-xl border p-4 sscevs-panel" style="border-color: hsl(240 5.9% 90%);">
                                    <p class="text-xs font-medium uppercase tracking-wide" style="color: hsl(240 3.8% 46.1%);">Eligible voters</p>
                                    <p class="text-2xl font-bold mt-1 tabular-nums" style="color: hsl(262 83% 45%);">
                                        {{ participation?.eligible_voters ?? 0 }}
                                    </p>
                                </div>
                                <div class="rounded-xl border p-4 sscevs-panel" style="border-color: hsl(240 5.9% 90%);">
                                    <p class="text-xs font-medium uppercase tracking-wide" style="color: hsl(240 3.8% 46.1%);">Ballots cast</p>
                                    <p class="text-2xl font-bold mt-1 tabular-nums" style="color: hsl(262 83% 45%);">
                                        {{ participation?.ballots_cast ?? 0 }}
                                    </p>
                                </div>
                                <div class="rounded-xl border p-4 sscevs-panel" style="border-color: hsl(240 5.9% 90%);">
                                    <p class="text-xs font-medium uppercase tracking-wide" style="color: hsl(240 3.8% 46.1%);">Not yet voted</p>
                                    <p class="text-2xl font-bold mt-1 tabular-nums" style="color: hsl(262 83% 45%);">
                                        {{ participation?.pending_voters ?? 0 }}
                                    </p>
                                </div>
                                <div class="rounded-xl border p-4 sscevs-panel" style="border-color: hsl(240 5.9% 90%);">
                                    <p class="text-xs font-medium uppercase tracking-wide" style="color: hsl(240 3.8% 46.1%);">Turnout rate</p>
                                    <p class="text-2xl font-bold mt-1 tabular-nums" style="color: hsl(262 83% 45%);">
                                        {{ participation?.turnout_rate ?? 0 }}%
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-xl border p-5 sscevs-panel" style="border-color: hsl(240 5.9% 90%);">
                                <div class="flex items-center justify-between text-sm mb-2">
                                    <span style="color: hsl(240 10% 3.9%);">Overall turnout</span>
                                    <span class="font-medium tabular-nums" style="color: hsl(262 83% 45%);">
                                        {{ participation?.turnout_rate ?? 0 }}%
                                    </span>
                                </div>
                                <div class="h-3 rounded-full overflow-hidden" style="background-color: hsl(240 4.8% 95.9%);">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="{
                                            width: `${participation?.turnout_rate ?? 0}%`,
                                            backgroundColor: 'hsl(262 83% 58%)',
                                        }"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
