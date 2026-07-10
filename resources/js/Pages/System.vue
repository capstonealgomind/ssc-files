<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Select from '@/Components/ui/Select.vue';
import OnlineVotersDeviceChart from '@/Components/system/OnlineVotersDeviceChart.vue';
import VotingQueueTrafficChart from '@/Components/system/VotingQueueTrafficChart.vue';

const FEED_LIMIT = 10;
const POLL_INTERVAL_MS = 10000;
const QUEUE_POLL_INTERVAL_MS = 5000;

const props = defineProps({
    tab: { type: String, default: 'presence' },
    metrics: { type: Object, default: () => ({}) },
    chart: { type: Array, default: () => [] },
    range: { type: String, default: '24h' },
    queue: { type: Object, default: () => ({}) },
    updatedAt: { type: String, default: '' },
});

let pollTimer = null;

const activeTab = ref(props.tab === 'queue' ? 'queue' : 'presence');
const selectedRange = ref(props.range);
const knownFeedIds = ref(new Set());
const enteringFeedIds = ref(new Set());
const feedReady = ref(false);

const tabs = [
    { id: 'presence', label: 'Presence' },
    { id: 'queue', label: 'Voting Queue' },
];

const rangeOptions = [
    { value: '1h', label: 'Last hour' },
    { value: '24h', label: 'Last 24 hours' },
    { value: '7d', label: 'Last 7 days' },
];

const metricCards = computed(() => [
    {
        key: 'active',
        label: 'Active voters',
        value: props.metrics.active_voters ?? 0,
        hint: 'Verified accounts ready to participate',
        color: 'hsl(221 83% 53%)',
        bg: 'hsl(221 83% 96%)',
    },
    {
        key: 'online',
        label: 'Voters online',
        value: props.metrics.voters_online ?? 0,
        hint: `Mobile ${props.metrics.online_mobile ?? 0} · Desktop ${props.metrics.online_desktop ?? 0}`,
        color: 'hsl(142 71% 35%)',
        bg: 'hsl(142 76% 94%)',
        live: true,
    },
    {
        key: 'eligible',
        label: 'Eligible voters',
        value: props.metrics.eligible_voters ?? 0,
        hint: 'Verified voters in the system',
        color: 'hsl(38 92% 40%)',
        bg: 'hsl(38 92% 94%)',
    },
    {
        key: 'expired',
        label: 'Expired accounts',
        value: props.metrics.expired_accounts ?? 0,
        hint: 'Voters past course duration / expiry date',
        color: 'hsl(0 72% 45%)',
        bg: 'hsl(0 84% 95%)',
    },
]);

const queueCards = computed(() => [
    {
        key: 'in_flight',
        label: 'In queue',
        value: props.queue.in_flight ?? 0,
        hint: `Pending ${props.queue.pending ?? 0} · Processing ${props.queue.processing ?? 0}`,
        color: 'hsl(221 83% 45%)',
        bg: 'hsl(221 83% 96%)',
        live: true,
    },
    {
        key: 'jobs',
        label: 'Worker jobs waiting',
        value: props.queue.queued_jobs ?? 0,
        hint: `Failed jobs table: ${props.queue.failed_jobs ?? 0}`,
        color: 'hsl(38 92% 40%)',
        bg: 'hsl(38 92% 94%)',
    },
    {
        key: 'completed',
        label: 'Completed ballots',
        value: props.queue.completed ?? 0,
        hint: 'Successfully processed submissions',
        color: 'hsl(142 71% 35%)',
        bg: 'hsl(142 76% 94%)',
    },
    {
        key: 'failed',
        label: 'Failed ballots',
        value: props.queue.failed ?? 0,
        hint: 'Need retry or support review',
        color: 'hsl(0 72% 45%)',
        bg: 'hsl(0 84% 95%)',
    },
]);

const recentQueue = computed(() => {
    const rows = [...(props.queue.recent ?? [])].slice(0, FEED_LIMIT);
    // Oldest of the latest 10 at top, newest at bottom so new jobs flow upward.
    return rows.reverse();
});

function syncFeedAnimation(rows) {
    const nextIds = rows.map((row) => row.id);
    const known = knownFeedIds.value;

    if (!feedReady.value) {
        knownFeedIds.value = new Set(nextIds);
        feedReady.value = true;
        return;
    }

    const incoming = nextIds.filter((id) => !known.has(id));
    if (incoming.length === 0) {
        knownFeedIds.value = new Set(nextIds);
        return;
    }

    enteringFeedIds.value = new Set([
        ...enteringFeedIds.value,
        ...incoming,
    ]);

    knownFeedIds.value = new Set(nextIds);

    window.setTimeout(() => {
        const current = new Set(enteringFeedIds.value);
        incoming.forEach((id) => current.delete(id));
        enteringFeedIds.value = current;
    }, 900);
}

function isEntering(id) {
    return enteringFeedIds.value.has(id);
}

watch(
    recentQueue,
    (rows) => {
        syncFeedAnimation(rows);
    },
    { immediate: true, deep: true },
);

function statusStyle(status) {
    if (status === 'completed') {
        return { bg: 'hsl(142 76% 94%)', color: 'hsl(142 71% 29%)' };
    }
    if (status === 'failed') {
        return { bg: 'hsl(0 84% 94%)', color: 'hsl(0 62% 35%)' };
    }
    if (status === 'processing') {
        return { bg: 'hsl(221 83% 94%)', color: 'hsl(221 83% 35%)' };
    }
    return { bg: 'hsl(38 92% 94%)', color: 'hsl(38 62% 30%)' };
}

function visitSystem(overrides = {}) {
    router.get('/system', {
        tab: activeTab.value,
        range: selectedRange.value,
        ...overrides,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        showProgress: false,
        only: ['tab', 'metrics', 'chart', 'range', 'queue', 'updatedAt'],
    });
}

function switchTab(tabId) {
    if (activeTab.value === tabId) return;
    activeTab.value = tabId;
    visitSystem({ tab: tabId });
    startPolling();
}

function refresh() {
    visitSystem();
}

function startPolling() {
    stopPolling();
    const interval = activeTab.value === 'queue'
        ? QUEUE_POLL_INTERVAL_MS
        : POLL_INTERVAL_MS;
    pollTimer = window.setInterval(refresh, interval);
}

function stopPolling() {
    if (pollTimer) {
        window.clearInterval(pollTimer);
        pollTimer = null;
    }
}

watch(selectedRange, (value) => {
    visitSystem({ range: value });
});

watch(
    () => props.range,
    (value) => {
        if (value !== selectedRange.value) {
            selectedRange.value = value;
        }
    },
);

watch(
    () => props.tab,
    (value) => {
        const next = value === 'queue' ? 'queue' : 'presence';
        if (next !== activeTab.value) {
            activeTab.value = next;
        }
    },
);

onMounted(() => {
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <AppLayout>
        <Head title="System" />

        <template #header>
            <div class="flex items-center gap-3 min-w-0">
                <h1 class="text-base font-semibold truncate" style="color: hsl(240 10% 3.9%);">System</h1>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide"
                    style="background: hsl(142 76% 94%); color: hsl(142 71% 29%);"
                >
                    <span class="h-1.5 w-1.5 rounded-full animate-pulse" style="background: hsl(142 71% 45%);" />
                    Live
                </span>
            </div>
        </template>

        <div class="w-full space-y-5">
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
                            ? 'color: hsl(221 83% 40%); border-color: hsl(221 83% 53%);'
                            : 'color: hsl(240 3.8% 46.1%); border-color: transparent;'"
                        @click="switchTab(tab.id)"
                    >
                        <svg
                            v-if="tab.id === 'presence'"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z" />
                        </svg>
                        <svg
                            v-else
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                        </svg>
                        {{ tab.label }}
                    </button>
                </div>

                <div class="px-4 py-3 border-b flex flex-wrap items-center gap-2" style="border-color: hsl(240 5.9% 90%);">
                    <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                        <template v-if="activeTab === 'presence'">
                            Real-time voter activity and device presence across the voting system.
                        </template>
                        <template v-else>
                            Live ballot queue traffic for incoming votes waiting to be processed.
                        </template>
                    </p>
                    <p class="text-xs ml-auto" style="color: hsl(240 3.8% 46.1%);">
                        Updated {{ updatedAt }} · refreshes every {{ activeTab === 'queue' ? '5' : '10' }} seconds
                    </p>
                </div>

                <div class="p-4 sm:p-5 space-y-5">
                    <template v-if="activeTab === 'presence'">
                        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium" style="color: hsl(240 10% 3.9%);">Presence overview</p>
                                <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                    Online status is based on voter heartbeats from the last 2 minutes.
                                </p>
                            </div>
                            <div class="w-full sm:w-48">
                                <Select
                                    id="system-range"
                                    v-model="selectedRange"
                                    :options="rangeOptions"
                                    placeholder="Select range"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                            <div
                                v-for="card in metricCards"
                                :key="card.key"
                                class="rounded-xl border px-4 py-4"
                                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-medium" style="color: hsl(240 3.8% 46.1%);">{{ card.label }}</p>
                                        <p class="text-3xl font-semibold mt-1 tabular-nums tracking-tight" style="color: hsl(240 10% 3.9%);">
                                            {{ card.value }}
                                        </p>
                                        <p class="text-xs mt-2" style="color: hsl(240 3.8% 46.1%);">{{ card.hint }}</p>
                                    </div>
                                    <div
                                        class="h-9 w-9 rounded-lg flex items-center justify-center shrink-0"
                                        :style="{ background: card.bg, color: card.color }"
                                    >
                                        <span
                                            v-if="card.live"
                                            class="h-2.5 w-2.5 rounded-full animate-pulse"
                                            :style="{ background: card.color }"
                                        />
                                        <svg
                                            v-else
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <OnlineVotersDeviceChart :data="chart" :range="range" />
                    </template>

                    <template v-else>
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                            <div
                                v-for="card in queueCards"
                                :key="card.key"
                                class="rounded-xl border px-4 py-4"
                                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-medium" style="color: hsl(240 3.8% 46.1%);">{{ card.label }}</p>
                                        <p class="text-3xl font-semibold mt-1 tabular-nums tracking-tight" style="color: hsl(240 10% 3.9%);">
                                            {{ card.value }}
                                        </p>
                                        <p class="text-xs mt-2" style="color: hsl(240 3.8% 46.1%);">{{ card.hint }}</p>
                                    </div>
                                    <div
                                        class="h-9 w-9 rounded-lg flex items-center justify-center shrink-0"
                                        :style="{ background: card.bg, color: card.color }"
                                    >
                                        <span
                                            v-if="card.live"
                                            class="h-2.5 w-2.5 rounded-full animate-pulse"
                                            :style="{ background: card.color }"
                                        />
                                        <svg
                                            v-else
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <VotingQueueTrafficChart :data="queue.traffic || []" />

                        <div
                            class="rounded-xl border overflow-hidden"
                            style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
                        >
                            <div class="px-4 py-3 border-b" style="border-color: hsl(240 5.9% 90%);">
                                <p class="text-sm font-semibold" style="color: hsl(240 10% 3.9%);">Recent ballot jobs</p>
                                <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                    Showing the latest 10 submissions. New jobs enter at the bottom and flow upward.
                                </p>
                            </div>
                            <div class="overflow-x-auto overflow-y-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);">
                                            <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Voter</th>
                                            <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Election</th>
                                            <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Status</th>
                                            <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Queued</th>
                                            <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Wait</th>
                                            <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Receipt</th>
                                        </tr>
                                    </thead>
                                    <tbody v-if="recentQueue.length === 0">
                                        <tr>
                                            <td colspan="6" class="px-4 py-10 text-center text-sm" style="color: hsl(240 3.8% 46.1%);">
                                                No ballot queue jobs yet. Incoming votes will appear here as voters submit.
                                            </td>
                                        </tr>
                                    </tbody>
                                    <TransitionGroup
                                        v-else
                                        name="ballot-feed"
                                        tag="tbody"
                                        class="ballot-feed-body"
                                    >
                                        <tr
                                            v-for="row in recentQueue"
                                            :key="row.id"
                                            class="border-b hover:bg-gray-50 ballot-feed-row"
                                            :class="{ 'ballot-feed-row-new': isEntering(row.id) }"
                                            style="border-color: hsl(240 5.9% 90%);"
                                        >
                                            <td class="px-4 py-3 align-middle">
                                                <p class="font-medium" style="color: hsl(240 10% 3.9%);">{{ row.voter_name || '—' }}</p>
                                                <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                                    {{ row.voter_id_number || '—' }}
                                                </p>
                                            </td>
                                            <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                                {{ row.election || '—' }}
                                            </td>
                                            <td class="px-4 py-3 align-middle">
                                                <span
                                                    class="inline-flex px-2 py-0.5 rounded text-xs font-semibold capitalize"
                                                    :style="{ background: statusStyle(row.status).bg, color: statusStyle(row.status).color }"
                                                >
                                                    {{ row.status }}
                                                </span>
                                                <p
                                                    v-if="row.error_message"
                                                    class="text-xs mt-1 max-w-[14rem]"
                                                    style="color: hsl(0 62% 35%);"
                                                >
                                                    {{ row.error_message }}
                                                </p>
                                            </td>
                                            <td class="px-4 py-3 align-middle whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                                                {{ row.queued_at || '—' }}
                                            </td>
                                            <td class="px-4 py-3 align-middle tabular-nums" style="color: hsl(240 3.8% 46.1%);">
                                                {{ row.wait_seconds == null ? '—' : `${row.wait_seconds}s` }}
                                            </td>
                                            <td class="px-4 py-3 align-middle font-mono text-xs" style="color: hsl(240 10% 3.9%);">
                                                {{ row.receipt_number || '—' }}
                                            </td>
                                        </tr>
                                    </TransitionGroup>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
