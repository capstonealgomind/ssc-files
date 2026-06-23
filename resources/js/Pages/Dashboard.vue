<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MetricCard from '@/Components/charts/MetricCard.vue';
import TotalVotesSummaryCard from '@/Components/charts/TotalVotesSummaryCard.vue';
import DepartmentVotesChart from '@/Components/charts/DepartmentVotesChart.vue';
import VotersByPhaseChart from '@/Components/charts/VotersByPhaseChart.vue';
import LiveVoteEntryTable from '@/Components/dashboard/LiveVoteEntryTable.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.role === 'admin');

const voterIcon = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`;
const votesIcon = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
const turnoutIcon = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>`;
const electionIcon = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>`;

const metricCards = computed(() => {
    if (isAdmin.value) {
        return [
            {
                title: 'Total Voters',
                value: '4,682',
                subtitle: 'Since last week',
                change: '15.54%',
                trend: 'up',
                sparkline: [3200, 3600, 3900, 4100, 4300, 4682],
                sparklineColor: 'hsl(221 83% 53%)',
                icon: voterIcon,
            },
            {
                title: 'Votes Cast',
                value: '2,350',
                subtitle: 'Since last week',
                change: '180.1%',
                trend: 'up',
                sparkline: [420, 680, 920, 1180, 1650, 2350],
                sparklineColor: 'hsl(230 65% 45%)',
                icon: votesIcon,
            },
            {
                title: 'Turnout Rate',
                value: '78.2%',
                subtitle: 'Since last week',
                change: '40.2%',
                trend: 'down',
                sparkline: [92, 88, 84, 81, 79, 78],
                sparklineColor: 'hsl(221 70% 60%)',
                icon: turnoutIcon,
            },
        ];
    }

    return [
        {
            title: 'Elections Available',
            value: '3',
            subtitle: 'Open for voting',
            change: '12.5%',
            trend: 'up',
            sparkline: [1, 1, 2, 2, 3, 3],
            sparklineColor: 'hsl(221 83% 53%)',
            icon: electionIcon,
        },
        {
            title: 'Votes Cast',
            value: '2',
            subtitle: 'By you so far',
            change: '100%',
            trend: 'up',
            sparkline: [0, 0, 1, 1, 2, 2],
            sparklineColor: 'hsl(230 65% 45%)',
            icon: votesIcon,
        },
        {
            title: 'Results Published',
            value: '1',
            subtitle: 'Completed elections',
            change: '8.3%',
            trend: 'up',
            sparkline: [0, 0, 0, 1, 1, 1],
            sparklineColor: 'hsl(221 70% 60%)',
            icon: turnoutIcon,
        },
    ];
});
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Dashboard</h1>
        </template>

        <div
            class="space-y-4 w-full min-h-full -m-4 p-4"
            style="background-color: hsl(240 4.8% 95.9%);"
        >
            <!-- Top metric row -->
            <div
                class="grid grid-cols-1 md:grid-cols-2 gap-4"
                :class="isAdmin ? 'xl:grid-cols-4' : 'xl:grid-cols-3'"
            >
                <MetricCard
                    v-for="card in metricCards"
                    :key="card.title"
                    v-bind="card"
                />
                <TotalVotesSummaryCard v-if="isAdmin" />
            </div>

            <!-- Charts row -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-2">
                    <DepartmentVotesChart />
                </div>
                <div>
                    <VotersByPhaseChart />
                </div>
            </div>

            <!-- Live vote entry table -->
            <LiveVoteEntryTable />
        </div>
    </AppLayout>
</template>
