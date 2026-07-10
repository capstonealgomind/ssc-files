<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";
import Select from "@/Components/ui/Select.vue";
import MetricCard from "@/Components/charts/MetricCard.vue";
import TotalVotesSummaryCard from "@/Components/charts/TotalVotesSummaryCard.vue";
import DepartmentVotesChart from "@/Components/charts/DepartmentVotesChart.vue";
import VotersByPhaseChart from "@/Components/charts/VotersByPhaseChart.vue";
import LiveVoteEntryTable from "@/Components/dashboard/LiveVoteEntryTable.vue";
import VotingStatusPieChart from "@/Components/dashboard/VotingStatusPieChart.vue";

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    admin_metrics: { type: Object, default: () => ({}) },
    votes_summary: { type: Object, default: () => ({}) },
    votes_by_department: { type: Array, default: () => [] },
    ballots_over_time: { type: Object, default: () => ({}) },
    voter_stats: { type: Object, default: () => ({}) },
    elections: { type: Array, default: () => [] },
    active_election_list: { type: Array, default: () => [] },
    voting_status: { type: Array, default: () => [] },
    announcements: { type: Array, default: () => [] },
    recent_votes: { type: Array, default: () => [] },
    is_admin: { type: Boolean, default: false },
    is_committee: { type: Boolean, default: false },
    candidate_count: { type: Number, default: 0 },
    candidates: { type: Array, default: () => [] },
    position_options: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const searchQuery = ref("");
const electionFilter = ref("all");
const positionFilter = ref("all");
const departmentFilter = ref("all");

let adminPollTimer = null;

function refreshAdminDashboard() {
    router.reload({
        only: [
            "stats",
            "admin_metrics",
            "votes_summary",
            "votes_by_department",
            "ballots_over_time",
            "recent_votes",
        ],
        preserveScroll: true,
        showProgress: false,
    });
}

onMounted(() => {
    if (props.is_admin) {
        adminPollTimer = window.setInterval(refreshAdminDashboard, 10000);
    }
});

onUnmounted(() => {
    if (adminPollTimer) {
        window.clearInterval(adminPollTimer);
        adminPollTimer = null;
    }
});

const electionFilterOptions = computed(() => [
    { value: "all", label: "All elections" },
    ...props.elections.map((item) => ({
        value: String(item.id),
        label: item.title,
    })),
]);

const positionFilterOptions = computed(() => [
    { value: "all", label: "All positions" },
    ...props.position_options,
]);

const departmentFilterOptions = computed(() => [
    { value: "all", label: "All departments" },
    { value: "none", label: "No department" },
    ...props.departments.map((item) => ({
        value: String(item.id),
        label: item.name,
    })),
]);

const hasActiveFilters = computed(
    () =>
        searchQuery.value.trim() !== "" ||
        electionFilter.value !== "all" ||
        positionFilter.value !== "all" ||
        departmentFilter.value !== "all",
);

const filteredCandidates = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.candidates.filter((candidate) => {
        if (
            electionFilter.value !== "all" &&
            String(candidate.election_id) !== electionFilter.value
        ) {
            return false;
        }

        if (
            positionFilter.value !== "all" &&
            String(candidate.position_id) !== positionFilter.value
        ) {
            return false;
        }

        if (departmentFilter.value === "none" && candidate.department_id) {
            return false;
        }

        if (
            departmentFilter.value !== "all" &&
            departmentFilter.value !== "none" &&
            String(candidate.department_id) !== departmentFilter.value
        ) {
            return false;
        }

        if (!query) {
            return true;
        }

        const haystack = [
            candidate.name,
            candidate.position,
            candidate.election_title,
            candidate.department_name,
            candidate.course_name,
            candidate.partylist_label,
            candidate.platform,
        ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();

        return haystack.includes(query);
    });
});

function clearFilters() {
    searchQuery.value = "";
    electionFilter.value = "all";
    positionFilter.value = "all";
    departmentFilter.value = "all";
}

function getInitials(name) {
    if (!name) return "?";
    return name
        .split(" ")
        .map((n) => n[0])
        .slice(0, 2)
        .join("")
        .toUpperCase();
}

function truncateText(text, max = 100) {
    if (!text) return "";
    return text.length > max ? `${text.slice(0, max).trim()}…` : text;
}

// ── Admin: metric cards from live stats ──────────────────────────────────
const voterIcon = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`;
const votesIcon = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
const turnoutIcon = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>`;

function formatMetricValue(value, suffix = '') {
    const number = Number(value) || 0;
    return `${number.toLocaleString()}${suffix}`;
}

const adminMetricCards = computed(() => {
    const metrics = props.admin_metrics || {};
    const voters = metrics.total_voters || {};
    const ballots = metrics.votes_cast || {};
    const turnout = metrics.turnout_rate || {};

    return [
        {
            title: "Total Voters",
            value: formatMetricValue(voters.value ?? props.stats.total_voters ?? 0),
            subtitle: voters.subtitle || "Registered voter accounts",
            change: voters.change || "0%",
            trend: voters.trend || "up",
            sparkline: voters.sparkline?.length ? voters.sparkline : [Number(voters.value) || 0],
            sparklineColor: "hsl(221 83% 53%)",
            icon: voterIcon,
        },
        {
            title: "Votes Cast",
            value: formatMetricValue(ballots.value ?? props.stats.votes_cast ?? 0),
            subtitle: ballots.subtitle || "Ballots submitted",
            change: ballots.change || "0%",
            trend: ballots.trend || "up",
            sparkline: ballots.sparkline?.length ? ballots.sparkline : [Number(ballots.value) || 0],
            sparklineColor: "hsl(230 65% 45%)",
            icon: votesIcon,
        },
        {
            title: "Turnout Rate",
            value: formatMetricValue(turnout.value ?? 0, "%"),
            subtitle: turnout.subtitle || "Of verified voters",
            change: turnout.change || "0%",
            trend: turnout.trend || "up",
            sparkline: turnout.sparkline?.length ? turnout.sparkline : [Number(turnout.value) || 0],
            sparklineColor: "hsl(221 70% 60%)",
            icon: turnoutIcon,
        },
    ];
});

// ── Voter: live vote feed ────────────────────────────────────────────────

const voterStatCards = computed(() => [
    {
        label: "Active Elections",
        value: props.voter_stats.active_elections ?? 0,
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    },
    {
        label: "Votes Cast",
        value: props.voter_stats.votes_cast ?? 0,
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>`,
    },
    {
        label: "Upcoming Elections",
        value: props.voter_stats.upcoming_elections ?? 0,
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    },
    {
        label: "Completed Elections",
        value: props.voter_stats.completed_elections ?? 0,
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>`,
    },
]);

const quickLinks = [
    {
        title: "Elections",
        description: "View elections and submit your ballot",
        href: "/elections",
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    },
    {
        title: "Dashboard",
        description: "View live vote updates",
        href: "/dashboard",
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM13 7a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2h-4a2 2 0 01-2-2V7zM3 15a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2zM13 15a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2z"/></svg>`,
    },
    {
        title: "My Account",
        description: "Check your verification status",
        href: "/dashboard",
        icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>`,
    },
];

function electionStatusStyle(status, votingPhase) {
    if (votingPhase === "open")
        return { bg: "hsl(142 76% 94%)", color: "hsl(142 71% 29%)" };
    if (status === "active")
        return { bg: "hsl(142 76% 94%)", color: "hsl(142 71% 29%)" };
    if (status === "scheduled")
        return { bg: "hsl(221 83% 94%)", color: "hsl(221 83% 35%)" };
    return { bg: "hsl(240 4.8% 95.9%)", color: "hsl(240 3.8% 46.1%)" };
}
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />
        <template #header>
            <h1
                class="text-base font-semibold"
                style="color: hsl(240 10% 3.9%)"
            >
                Dashboard
            </h1>
        </template>

        <!-- ══════════════════════════════════════════════════════════
             ADMIN VIEW
        ══════════════════════════════════════════════════════════ -->
        <div v-if="is_admin" class="space-y-4 w-full min-h-full">
            <!-- Top metric row -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <MetricCard
                    v-for="card in adminMetricCards"
                    :key="card.title"
                    v-bind="card"
                />
                <TotalVotesSummaryCard
                    :value="votes_summary.value || 0"
                    :subtitle="votes_summary.subtitle || ''"
                    :sparkline="votes_summary.sparkline || []"
                />
            </div>

            <!-- Charts row -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-2">
                    <DepartmentVotesChart :data="votes_by_department" />
                </div>
                <div>
                    <VotersByPhaseChart
                        :points="ballots_over_time.points || []"
                        :latest="ballots_over_time.latest || 0"
                        :subtitle="ballots_over_time.subtitle || ''"
                    />
                </div>
            </div>

            <!-- Live vote entry table -->
            <LiveVoteEntryTable :entries="recent_votes" />
        </div>

        <!-- ══════════════════════════════════════════════════════════
             COMMITTEE VIEW
        ══════════════════════════════════════════════════════════ -->
        <div v-else-if="is_committee" class="space-y-5 w-full">
            <!-- Header + stats -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <h2
                        class="text-lg font-semibold"
                        style="color: hsl(240 10% 3.9%)"
                    >
                        Welcome, {{ user?.name }}
                    </h2>
                    <p class="text-sm mt-1" style="color: hsl(240 3.8% 46.1%)">
                        Review candidates you’ve added, or create a new one.
                    </p>
                </div>
                <Link
                    href="/committee"
                    class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors shrink-0"
                    style="
                        background-color: hsl(240 5.9% 10%);
                        color: hsl(0 0% 98%);
                    "
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4.5v15m7.5-7.5h-15"
                        />
                    </svg>
                    Add candidate
                </Link>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <div
                    class="rounded-xl border px-4 py-4 sm:px-5"
                    style="
                        background-color: hsl(0 0% 100%);
                        border-color: hsl(240 5.9% 90%);
                    "
                >
                    <p
                        class="text-xs font-medium uppercase tracking-wide"
                        style="color: hsl(240 3.8% 46.1%)"
                    >
                        Candidates
                    </p>
                    <p
                        class="text-2xl sm:text-3xl font-bold mt-1"
                        style="color: hsl(240 10% 3.9%)"
                    >
                        {{ candidate_count }}
                    </p>
                </div>
                <div
                    class="rounded-xl border px-4 py-4 sm:px-5"
                    style="
                        background-color: hsl(0 0% 100%);
                        border-color: hsl(240 5.9% 90%);
                    "
                >
                    <p
                        class="text-xs font-medium uppercase tracking-wide"
                        style="color: hsl(240 3.8% 46.1%)"
                    >
                        Elections
                    </p>
                    <p
                        class="text-2xl sm:text-3xl font-bold mt-1"
                        style="color: hsl(240 10% 3.9%)"
                    >
                        {{ elections.length }}
                    </p>
                </div>
            </div>

            <!-- Search & filters -->
            <div
                v-if="candidates.length > 0"
                class="rounded-xl border p-4 space-y-4"
                style="
                    background-color: hsl(0 0% 100%);
                    border-color: hsl(240 5.9% 90%);
                "
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                >
                    <div>
                        <h3
                            class="text-sm font-semibold"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Search &amp; filters
                        </h3>
                        <p
                            class="text-xs mt-0.5"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            Find candidates by name, position, election, or
                            department.
                        </p>
                    </div>
                    <Button
                        v-if="hasActiveFilters"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="clearFilters"
                    >
                        Clear filters
                    </Button>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4"
                >
                    <div class="space-y-1.5 sm:col-span-2 xl:col-span-1">
                        <Label html-for="committee-candidate-search"
                            >Search</Label
                        >
                        <div class="relative">
                            <svg
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
                                />
                            </svg>
                            <Input
                                id="committee-candidate-search"
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search by name, platform..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label html-for="committee-election-filter"
                            >Election</Label
                        >
                        <Select
                            id="committee-election-filter"
                            v-model="electionFilter"
                            :options="electionFilterOptions"
                            placeholder="All elections"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label html-for="committee-position-filter"
                            >Position</Label
                        >
                        <Select
                            id="committee-position-filter"
                            v-model="positionFilter"
                            :options="positionFilterOptions"
                            placeholder="All positions"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label html-for="committee-department-filter"
                            >Department</Label
                        >
                        <Select
                            id="committee-department-filter"
                            v-model="departmentFilter"
                            :options="departmentFilterOptions"
                            placeholder="All departments"
                        />
                    </div>
                </div>
            </div>

            <!-- Candidates section -->
            <div class="space-y-4">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <h3
                            class="text-base font-semibold"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Created candidates
                        </h3>
                        <p
                            class="text-xs mt-0.5"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            <template v-if="hasActiveFilters">
                                {{ filteredCandidates.length }} of
                                {{ candidates.length }}
                                {{
                                    candidates.length === 1
                                        ? "candidate"
                                        : "candidates"
                                }}
                            </template>
                            <template v-else>
                                Newest first · {{ candidates.length }}
                                {{
                                    candidates.length === 1
                                        ? "candidate"
                                        : "candidates"
                                }}
                            </template>
                        </p>
                    </div>
                </div>

                <!-- Empty state: no candidates at all -->
                <div
                    v-if="candidates.length === 0"
                    class="rounded-xl border flex flex-col items-center justify-center py-16 px-4 text-center"
                    style="
                        background-color: hsl(0 0% 100%);
                        border-color: hsl(240 5.9% 90%);
                    "
                >
                    <div
                        class="h-14 w-14 rounded-full flex items-center justify-center mb-4"
                        style="background-color: hsl(240 4.8% 95.9%)"
                    >
                        <svg
                            class="h-7 w-7"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                            />
                        </svg>
                    </div>
                    <p
                        class="text-sm font-semibold"
                        style="color: hsl(240 10% 3.9%)"
                    >
                        No candidates yet
                    </p>
                    <p
                        class="text-xs mt-1 max-w-xs"
                        style="color: hsl(240 3.8% 46.1%)"
                    >
                        Add your first candidate to get them on the ballot.
                    </p>
                    <Link
                        href="/committee"
                        class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium hover:underline"
                        style="color: hsl(240 5.9% 10%)"
                    >
                        Create a candidate
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </Link>
                </div>

                <!-- Empty state: filters matched nothing -->
                <div
                    v-else-if="filteredCandidates.length === 0"
                    class="rounded-xl border flex flex-col items-center justify-center py-14 px-4 text-center"
                    style="
                        background-color: hsl(0 0% 100%);
                        border-color: hsl(240 5.9% 90%);
                    "
                >
                    <p
                        class="text-sm font-semibold"
                        style="color: hsl(240 10% 3.9%)"
                    >
                        No matches found
                    </p>
                    <p class="text-xs mt-1" style="color: hsl(240 3.8% 46.1%)">
                        No candidates match your search or filters.
                    </p>
                    <button
                        type="button"
                        class="mt-3 text-sm font-medium hover:underline"
                        style="color: hsl(240 5.9% 10%)"
                        @click="clearFilters"
                    >
                        Clear search and filters
                    </button>
                </div>

                <!-- Candidate card grid -->
                <div
                    v-else
                    class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4"
                >
                    <div
                        v-for="candidate in filteredCandidates"
                        :key="candidate.id"
                        class="rounded-xl border overflow-hidden flex flex-col transition-shadow hover:shadow-md"
                        style="
                            background-color: hsl(0 0% 100%);
                            border-color: hsl(240 5.9% 90%);
                        "
                    >
                        <div class="relative bg-[hsl(240_4.8%_95.9%)]">
                            <div
                                class="absolute top-2.5 left-2.5 z-10 flex flex-col items-start gap-1"
                            >
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wide"
                                    :style="
                                        candidate.partylist_id
                                            ? 'background-color: hsl(262 83% 94%); color: hsl(262 83% 35%);'
                                            : 'background-color: hsl(240 5.9% 10%); color: #fff;'
                                    "
                                >
                                    {{ candidate.partylist_label }}
                                </span>
                            </div>
                            <div class="absolute top-2.5 right-2.5 z-10">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold"
                                    style="
                                        background-color: hsl(221 83% 94%);
                                        color: hsl(221 83% 35%);
                                    "
                                >
                                    {{ candidate.position || "No position" }}
                                </span>
                            </div>

                            <img
                                v-if="candidate.photo_url"
                                :src="candidate.photo_url"
                                :alt="candidate.name"
                                class="block w-full aspect-[4/5] object-cover"
                            />
                            <div
                                v-else
                                class="aspect-[4/5] flex flex-col items-center justify-center gap-2"
                            >
                                <div
                                    class="h-16 w-16 rounded-full flex items-center justify-center text-lg font-semibold"
                                    style="
                                        background-color: hsl(240 5.9% 10%);
                                        color: hsl(0 0% 98%);
                                    "
                                >
                                    {{ getInitials(candidate.name) }}
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col flex-1 p-3.5 space-y-2 border-t"
                            style="border-color: hsl(240 5.9% 90%)"
                        >
                            <div>
                                <p
                                    class="font-semibold text-sm leading-tight"
                                    style="color: hsl(240 10% 3.9%)"
                                >
                                    {{ candidate.name }}
                                </p>
                                <p
                                    class="text-xs mt-1 truncate"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    {{
                                        candidate.election_title ||
                                        "No election"
                                    }}
                                </p>
                            </div>

                            <div
                                v-if="
                                    candidate.department_name ||
                                    candidate.course_name
                                "
                                class="space-y-0.5"
                            >
                                <p
                                    v-if="candidate.department_name"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium"
                                    :style="{
                                        color:
                                            candidate.department_color_hex ||
                                            'hsl(240 3.8% 46.1%)',
                                    }"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full shrink-0"
                                        :style="{
                                            backgroundColor:
                                                candidate.department_color_hex ||
                                                '#64748b',
                                        }"
                                    />
                                    {{ candidate.department_name }}
                                </p>
                                <p
                                    v-if="candidate.course_name"
                                    class="text-xs leading-snug line-clamp-2"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    {{ candidate.course_name }}
                                </p>
                            </div>

                            <p
                                v-if="candidate.platform"
                                class="text-xs leading-relaxed line-clamp-2"
                                style="color: hsl(240 5.9% 35%)"
                            >
                                {{ truncateText(candidate.platform) }}
                            </p>

                            <p
                                v-if="candidate.created_at"
                                class="text-[11px] mt-auto pt-1"
                                style="color: hsl(240 3.8% 60%)"
                            >
                                Added {{ candidate.created_at }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
             VOTER VIEW
        ══════════════════════════════════════════════════════════ -->
        <div v-else class="space-y-6">
            <!-- ── Stat cards ───────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
                <div
                    v-for="card in voterStatCards"
                    :key="card.label"
                    class="sscevs-panel rounded-xl border px-4 py-4 lg:px-5 lg:py-5 flex items-center gap-3 lg:gap-4"
                >
                    <div
                        class="h-10 w-10 lg:h-11 lg:w-11 shrink-0 rounded-lg flex items-center justify-center"
                        style="background: hsl(240 4.8% 95.9%)"
                    >
                        <span
                            class="h-5 w-5 lg:h-6 lg:w-6"
                            style="color: hsl(240 10% 3.9%)"
                            v-html="card.icon"
                        ></span>
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-2xl lg:text-3xl font-black leading-none"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            {{ card.value }}
                        </p>
                        <p
                            class="text-xs lg:text-sm font-semibold mt-1 truncate"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            {{ card.label }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ── Active elections + Voting status pie ─────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
                <!-- Active elections list -->
                <div
                    class="sscevs-panel lg:col-span-2 rounded-xl border overflow-hidden"
                >
                    <div
                        class="px-5 py-4 border-b flex items-center justify-between"
                        style="border-color: hsl(240 5.9% 90%)"
                    >
                        <div class="flex items-center gap-2">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <h2
                                class="text-base lg:text-lg font-bold"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                Active Elections
                            </h2>
                        </div>
                        <Link
                            href="/elections"
                            class="text-xs lg:text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors hover:bg-gray-100"
                            style="
                                background: hsl(240 4.8% 95.9%);
                                color: hsl(240 10% 3.9%);
                            "
                        >
                            Go to Elections
                        </Link>
                    </div>

                    <div
                        v-if="active_election_list.length === 0"
                        class="flex flex-col items-center justify-center py-14 px-4 text-center"
                    >
                        <div
                            class="h-12 w-12 rounded-full flex items-center justify-center mb-3"
                            style="background: hsl(240 4.8% 95.9%)"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                        </div>
                        <p
                            class="text-sm font-bold"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            No active elections right now
                        </p>
                        <p
                            class="text-xs mt-1"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            Check back when voting opens.
                        </p>
                    </div>

                    <div
                        v-else
                        class="divide-y"
                        style="border-color: hsl(240 5.9% 90%)"
                    >
                        <div
                            v-for="election in active_election_list"
                            :key="election.id"
                            class="px-5 py-4 hover:bg-gray-50 transition-colors"
                        >
                            <div
                                class="flex items-start justify-between gap-3 mb-2"
                            >
                                <h3
                                    class="text-sm lg:text-base font-bold leading-snug"
                                    style="color: hsl(240 10% 3.9%)"
                                >
                                    {{ election.title }}
                                </h3>
                                <span
                                    class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0"
                                    :style="{
                                        background: electionStatusStyle(
                                            election.status,
                                            election.voting_phase,
                                        ).bg,
                                        color: electionStatusStyle(
                                            election.status,
                                            election.voting_phase,
                                        ).color,
                                    }"
                                >
                                    {{ election.status_label }}
                                </span>
                            </div>
                            <p
                                v-if="election.description"
                                class="text-xs lg:text-sm mb-2 line-clamp-2"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                {{ election.description }}
                            </p>
                            <div
                                class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs lg:text-sm font-semibold"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                <span
                                    >{{ election.candidate_count }} candidate{{
                                        election.candidate_count !== 1
                                            ? "s"
                                            : ""
                                    }}</span
                                >
                                <span>Ends {{ election.voting_ends_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voting status pie chart -->
                <div class="sscevs-panel rounded-xl border overflow-hidden">
                    <div
                        class="px-5 py-4 border-b"
                        style="border-color: hsl(240 5.9% 90%)"
                    >
                        <div class="flex items-center gap-2">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"
                                />
                            </svg>
                            <h2
                                class="text-base lg:text-lg font-bold"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                My Voting Status
                            </h2>
                        </div>
                        <p
                            class="text-xs lg:text-sm mt-1 font-medium"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
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
                <div
                    class="sscevs-panel lg:col-span-2 rounded-xl border overflow-hidden"
                >
                    <div
                        class="px-5 py-4 border-b"
                        style="border-color: hsl(240 5.9% 90%)"
                    >
                        <div class="flex items-center gap-2">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"
                                />
                            </svg>
                            <h2
                                class="text-base lg:text-lg font-bold"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                Recent Announcements
                            </h2>
                        </div>
                    </div>

                    <div
                        v-if="announcements.length === 0"
                        class="flex flex-col items-center justify-center py-14 px-4 text-center"
                    >
                        <p
                            class="text-sm font-bold"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            No announcements yet
                        </p>
                    </div>

                    <div
                        v-else
                        class="divide-y"
                        style="border-color: hsl(240 5.9% 90%)"
                    >
                        <div
                            v-for="item in announcements"
                            :key="item.id"
                            class="px-5 py-4"
                        >
                            <div
                                class="flex items-start justify-between gap-3 mb-1"
                            >
                                <h3
                                    class="text-sm lg:text-base font-bold leading-snug"
                                    style="color: hsl(240 10% 3.9%)"
                                >
                                    {{ item.title }}
                                </h3>
                                <span
                                    class="text-xs font-semibold shrink-0 whitespace-nowrap"
                                    style="color: hsl(240 3.8% 46.1%)"
                                    >{{ item.date }}</span
                                >
                            </div>
                            <p
                                class="text-xs lg:text-sm leading-relaxed"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                {{ item.excerpt }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="sscevs-panel rounded-xl border overflow-hidden">
                    <div
                        class="px-5 py-4 border-b"
                        style="border-color: hsl(240 5.9% 90%)"
                    >
                        <div class="flex items-center gap-2">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                                />
                            </svg>
                            <h2
                                class="text-base lg:text-lg font-bold"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                Quick Links
                            </h2>
                        </div>
                    </div>

                    <div class="p-3 space-y-2">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.title"
                            :href="link.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-3 transition-colors hover:bg-gray-50"
                        >
                            <div
                                class="h-10 w-10 shrink-0 rounded-lg flex items-center justify-center"
                                style="background: hsl(240 4.8% 95.9%)"
                            >
                                <span
                                    class="h-5 w-5"
                                    style="color: hsl(240 10% 3.9%)"
                                    v-html="link.icon"
                                ></span>
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="text-sm font-bold"
                                    style="color: hsl(240 10% 3.9%)"
                                >
                                    {{ link.title }}
                                </p>
                                <p
                                    class="text-xs font-medium mt-0.5"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    {{ link.description }}
                                </p>
                            </div>
                            <svg
                                class="h-4 w-4 shrink-0 ml-auto"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                style="color: hsl(240 3.8% 60%)"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
