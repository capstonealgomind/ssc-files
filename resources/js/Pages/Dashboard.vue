<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MetricCard from '@/Components/charts/MetricCard.vue';
import TotalVotesSummaryCard from '@/Components/charts/TotalVotesSummaryCard.vue';
import DepartmentVotesChart from '@/Components/charts/DepartmentVotesChart.vue';
import VotersByPhaseChart from '@/Components/charts/VotersByPhaseChart.vue';
import LiveVoteEntryTable from '@/Components/dashboard/LiveVoteEntryTable.vue';
import VotingStatusPieChart from '@/Components/dashboard/VotingStatusPieChart.vue';

const props = defineProps({
    stats:                { type: Object, default: () => ({}) },
    voter_stats:          { type: Object, default: () => ({}) },
    elections:            { type: Array,  default: () => [] },
    active_election_list: { type: Array,  default: () => [] },
    voting_status:        { type: Array,  default: () => [] },
    announcements:        { type: Array,  default: () => [] },
    recent_votes:         { type: Array,  default: () => [] },
    is_admin:             { type: Boolean, default: false },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// ── Admin: original metric cards ─────────────────────────────────────────
const voterIcon   = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`;
const votesIcon   = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
const turnoutIcon = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>`;
const electionIcon= `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>`;

const adminMetricCards = [
    { title: 'Total Voters',  value: '4,682', subtitle: 'Since last week', change: '15.54%', trend: 'up',   sparkline: [3200,3600,3900,4100,4300,4682], sparklineColor: 'hsl(221 83% 53%)', icon: voterIcon },
    { title: 'Votes Cast',    value: '2,350', subtitle: 'Since last week', change: '180.1%', trend: 'up',   sparkline: [420,680,920,1180,1650,2350],    sparklineColor: 'hsl(230 65% 45%)', icon: votesIcon },
    { title: 'Turnout Rate',  value: '78.2%', subtitle: 'Since last week', change: '40.2%',  trend: 'down', sparkline: [92,88,84,81,79,78],             sparklineColor: 'hsl(221 70% 60%)', icon: turnoutIcon },
];

// ── Voter: live vote feed ────────────────────────────────────────────────

const voterStatCards = computed(() => [
    {
        label: 'Active Elections',
        value: props.voter_stats.active_elections ?? 0,
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    },
    {
        label: 'Votes Cast',
        value: props.voter_stats.votes_cast ?? 0,
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>`,
    },
    {
        label: 'Upcoming Elections',
        value: props.voter_stats.upcoming_elections ?? 0,
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    },
    {
        label: 'Completed Elections',
        value: props.voter_stats.completed_elections ?? 0,
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>`,
    },
]);

const quickLinks = [
    {
        title: 'Elections',
        description: 'View elections and submit your ballot',
        href: '/elections',
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    },
    {
        title: 'Dashboard',
        description: 'View live vote updates',
        href: '/dashboard',
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM13 7a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2h-4a2 2 0 01-2-2V7zM3 15a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2zM13 15a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2z"/></svg>`,
    },
    {
        title: 'My Account',
        description: 'Check your verification status',
        href: '/dashboard',
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>`,
    },
];

function electionStatusStyle(status, votingPhase) {
    if (votingPhase === 'open') return { bg: 'hsl(142 76% 94%)', color: 'hsl(142 71% 29%)' };
    if (status === 'active')    return { bg: 'hsl(142 76% 94%)', color: 'hsl(142 71% 29%)' };
    if (status === 'scheduled') return { bg: 'hsl(221 83% 94%)', color: 'hsl(221 83% 35%)' };
    return { bg: 'hsl(240 4.8% 95.9%)', color: 'hsl(240 3.8% 46.1%)' };
}
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">Dashboard</h1>
        </template>

        <!-- ══════════════════════════════════════════════════════════
             ADMIN VIEW
        ══════════════════════════════════════════════════════════ -->
        <div v-if="is_admin" class="space-y-4 w-full min-h-full">

            <!-- Top metric row -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <MetricCard v-for="card in adminMetricCards" :key="card.title" v-bind="card" />
                <TotalVotesSummaryCard />
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
            <LiveVoteEntryTable :entries="recent_votes" />
        </div>

        <!-- ══════════════════════════════════════════════════════════
             VOTER VIEW
        ══════════════════════════════════════════════════════════ -->
        <div v-else class="space-y-6">

            <!-- ── Stat cards ───────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
                <div v-for="card in voterStatCards" :key="card.label"
                    class="sscevs-panel rounded-xl border px-4 py-4 lg:px-5 lg:py-5 flex items-center gap-3 lg:gap-4">
                    <div class="h-10 w-10 lg:h-11 lg:w-11 shrink-0 rounded-lg flex items-center justify-center"
                        style="background:hsl(240 4.8% 95.9%);">
                        <span class="h-5 w-5 lg:h-6 lg:w-6" style="color:hsl(240 10% 3.9%);" v-html="card.icon"></span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl lg:text-3xl font-black leading-none" style="color:hsl(240 10% 3.9%);">{{ card.value }}</p>
                        <p class="text-xs lg:text-sm font-semibold mt-1 truncate" style="color:hsl(240 3.8% 46.1%);">{{ card.label }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Active elections + Voting status pie ─────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">

                <!-- Active elections list -->
                <div class="sscevs-panel lg:col-span-2 rounded-xl border overflow-hidden">
                    <div class="px-5 py-4 border-b flex items-center justify-between"
                        style="border-color:hsl(240 5.9% 90%);">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                style="color:hsl(240 10% 3.9%);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h2 class="text-base lg:text-lg font-bold" style="color:hsl(240 10% 3.9%);">Active Elections</h2>
                        </div>
                        <Link href="/elections"
                            class="text-xs lg:text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors hover:bg-gray-100"
                            style="background:hsl(240 4.8% 95.9%); color:hsl(240 10% 3.9%);">
                            Go to Elections
                        </Link>
                    </div>

                    <div v-if="active_election_list.length === 0"
                        class="flex flex-col items-center justify-center py-14 px-4 text-center">
                        <div class="h-12 w-12 rounded-full flex items-center justify-center mb-3"
                            style="background:hsl(240 4.8% 95.9%);">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                style="color:hsl(240 3.8% 46.1%);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <p class="text-sm font-bold" style="color:hsl(240 10% 3.9%);">No active elections right now</p>
                        <p class="text-xs mt-1" style="color:hsl(240 3.8% 46.1%);">Check back when voting opens.</p>
                    </div>

                    <div v-else class="divide-y" style="border-color:hsl(240 5.9% 90%);">
                        <div v-for="election in active_election_list" :key="election.id"
                            class="px-5 py-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <h3 class="text-sm lg:text-base font-bold leading-snug" style="color:hsl(240 10% 3.9%);">
                                    {{ election.title }}
                                </h3>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0"
                                    :style="{ background: electionStatusStyle(election.status, election.voting_phase).bg, color: electionStatusStyle(election.status, election.voting_phase).color }">
                                    {{ election.status_label }}
                                </span>
                            </div>
                            <p v-if="election.description" class="text-xs lg:text-sm mb-2 line-clamp-2"
                                style="color:hsl(240 3.8% 46.1%);">{{ election.description }}</p>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs lg:text-sm font-semibold"
                                style="color:hsl(240 3.8% 46.1%);">
                                <span>{{ election.candidate_count }} candidate{{ election.candidate_count !== 1 ? 's' : '' }}</span>
                                <span>Ends {{ election.voting_ends_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voting status pie chart -->
                <div class="sscevs-panel rounded-xl border overflow-hidden">
                    <div class="px-5 py-4 border-b" style="border-color:hsl(240 5.9% 90%);">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                style="color:hsl(240 10% 3.9%);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                            </svg>
                            <h2 class="text-base lg:text-lg font-bold" style="color:hsl(240 10% 3.9%);">My Voting Status</h2>
                        </div>
                        <p class="text-xs lg:text-sm mt-1 font-medium" style="color:hsl(240 3.8% 46.1%);">
                            Your participation across all elections
                        </p>
                    </div>
                    <div class="p-5">
                        <VotingStatusPieChart :data="voting_status" />
                    </div>
                </div>
            </div>

            <!-- ── Live vote entry table ────────────────────────────── -->
            <LiveVoteEntryTable :entries="recent_votes" />

            <!-- ── Announcements + Quick links ──────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">

                <!-- Recent announcements -->
                <div class="sscevs-panel lg:col-span-2 rounded-xl border overflow-hidden">
                    <div class="px-5 py-4 border-b" style="border-color:hsl(240 5.9% 90%);">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                style="color:hsl(240 10% 3.9%);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.34 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <h2 class="text-base lg:text-lg font-bold" style="color:hsl(240 10% 3.9%);">Recent Announcements</h2>
                        </div>
                    </div>

                    <div v-if="announcements.length === 0"
                        class="flex flex-col items-center justify-center py-14 px-4 text-center">
                        <p class="text-sm font-bold" style="color:hsl(240 10% 3.9%);">No announcements yet</p>
                    </div>

                    <div v-else class="divide-y" style="border-color:hsl(240 5.9% 90%);">
                        <div v-for="item in announcements" :key="item.id" class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3 mb-1">
                                <h3 class="text-sm lg:text-base font-bold leading-snug" style="color:hsl(240 10% 3.9%);">
                                    {{ item.title }}
                                </h3>
                                <span class="text-xs font-semibold shrink-0 whitespace-nowrap"
                                    style="color:hsl(240 3.8% 46.1%);">{{ item.date }}</span>
                            </div>
                            <p class="text-xs lg:text-sm leading-relaxed" style="color:hsl(240 3.8% 46.1%);">
                                {{ item.excerpt }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="sscevs-panel rounded-xl border overflow-hidden">
                    <div class="px-5 py-4 border-b" style="border-color:hsl(240 5.9% 90%);">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                style="color:hsl(240 10% 3.9%);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <h2 class="text-base lg:text-lg font-bold" style="color:hsl(240 10% 3.9%);">Quick Links</h2>
                        </div>
                    </div>

                    <div class="p-3 space-y-2">
                        <Link v-for="link in quickLinks" :key="link.title" :href="link.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-3 transition-colors hover:bg-gray-50">
                            <div class="h-10 w-10 shrink-0 rounded-lg flex items-center justify-center"
                                style="background:hsl(240 4.8% 95.9%);">
                                <span class="h-5 w-5" style="color:hsl(240 10% 3.9%);" v-html="link.icon"></span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold" style="color:hsl(240 10% 3.9%);">{{ link.title }}</p>
                                <p class="text-xs font-medium mt-0.5" style="color:hsl(240 3.8% 46.1%);">{{ link.description }}</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                style="color:hsl(240 3.8% 60%);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>

        </div>

    </AppLayout>
</template>
