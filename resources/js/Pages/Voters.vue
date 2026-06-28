<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';

const props = defineProps({
    voters: { type: Array, default: () => [] },
});

const page      = usePage();
const isAdmin   = computed(() => page.props.auth?.user?.role === 'admin');
const activeTab = ref('all');
const search    = ref('');
const riskFilter = ref('');

function riskLevel(score) {
    if (score >= 80) return { label: 'LOW',      dot: 'hsl(142 71% 45%)', bg: 'hsl(142 76% 94%)', text: 'hsl(142 71% 29%)' };
    if (score >= 50) return { label: 'MODERATE', dot: 'hsl(38 92% 50%)',  bg: 'hsl(38 92% 94%)',  text: 'hsl(38 62% 30%)' };
    if (score >= 20) return { label: 'HIGH',     dot: 'hsl(25 95% 53%)',  bg: 'hsl(25 95% 94%)',  text: 'hsl(25 75% 30%)' };
    return              { label: 'CRITICAL',     dot: 'hsl(0 84% 60%)',   bg: 'hsl(0 84% 94%)',   text: 'hsl(0 62% 35%)' };
}

const counts = computed(() => ({
    all:      props.voters.length,
    pending:  props.voters.filter(v => !v.is_verified && v.email_verified).length,
    verified: props.voters.filter(v => v.is_verified).length,
    flagged:  props.voters.filter(v => v.fraud_score < 20).length,
}));

const filtered = computed(() => {
    let list = props.voters;

    if (activeTab.value === 'pending')  list = list.filter(v => !v.is_verified && v.email_verified);
    if (activeTab.value === 'verified') list = list.filter(v => v.is_verified);
    if (activeTab.value === 'flagged')  list = list.filter(v => v.fraud_score < 20);

    if (riskFilter.value) {
        list = list.filter(v => riskLevel(v.fraud_score).label === riskFilter.value);
    }

    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(v =>
            v.name?.toLowerCase().includes(q) ||
            v.email?.toLowerCase().includes(q) ||
            v.student_id_number?.toLowerCase().includes(q) ||
            v.voter_id_number?.toLowerCase().includes(q),
        );
    }

    return list;
});

const statCards = computed(() => [
    {
        id: 'all', label: 'Total Voters', count: counts.value.all,
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8z"/>`,
    },
    {
        id: 'pending', label: 'Pending Approval', count: counts.value.pending,
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>`,
    },
    {
        id: 'verified', label: 'Approved Voters', count: counts.value.verified,
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>`,
    },
    {
        id: 'flagged', label: 'Flagged', count: counts.value.flagged,
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>`,
    },
]);

function openDetail(voter) {
    router.visit(`/voters/${voter.id}`);
}

function clearFilters() {
    search.value = '';
    riskFilter.value = '';
    activeTab.value = 'all';
}
</script>

<template>
    <AppLayout>
        <Head title="Voters" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">Voters</h1>
        </template>

        <div class="space-y-4">

            <!-- ── Stat cards ─────────────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div v-for="card in statCards" :key="card.id"
                    class="rounded-lg border p-4 cursor-pointer transition-all flex items-center gap-4"
                    :style="activeTab === card.id
                        ? 'border-color:hsl(240 5.9% 70%); background:#fff;'
                        : 'border-color:hsl(240 5.9% 90%); background:#fff;'"
                    @click="activeTab = card.id">

                    <!-- Icon -->
                    <div class="h-10 w-10 rounded-lg flex items-center justify-center shrink-0"
                        style="background:hsl(240 4.8% 95.9%);">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            style="color:hsl(240 10% 3.9%);" v-html="card.icon" />
                    </div>

                    <!-- Text -->
                    <div>
                        <p class="text-xs" style="color:hsl(240 3.8% 46.1%);">{{ card.label }}</p>
                        <p class="text-2xl font-bold leading-tight" style="color:hsl(240 10% 3.9%);">{{ card.count }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Table card ─────────────────────────────────────────────── -->
            <div class="rounded-lg border" style="border-color:hsl(240 5.9% 90%); background:#fff;">

                <!-- Toolbar -->
                <div class="px-4 py-3 border-b flex flex-wrap items-center gap-3" style="border-color:hsl(240 5.9% 90%);">

                    <!-- Search -->
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:hsl(240 3.8% 46.1%)">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <Input v-model="search" placeholder="Search name, ID, email…" class="pl-8 h-8 text-sm w-56" />
                    </div>

                    <!-- Risk filter -->
                    <select v-model="riskFilter"
                        class="h-8 rounded-md border px-2 text-xs outline-none focus:ring-1"
                        style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%); background:#fff;">
                        <option value="">All risk levels</option>
                        <option value="LOW">Low risk</option>
                        <option value="MODERATE">Moderate risk</option>
                        <option value="HIGH">High risk</option>
                        <option value="CRITICAL">Critical risk</option>
                    </select>

                    <!-- Status filter tabs (pill style) -->
                    <div class="flex items-center gap-1 rounded-md border p-0.5" style="border-color:hsl(240 5.9% 90%);">
                        <button v-for="tab in ['all','pending','verified','flagged']" :key="tab"
                            class="px-3 h-6 rounded text-xs font-medium capitalize transition-colors"
                            :style="activeTab === tab
                                ? 'background:hsl(240 5.9% 10%); color:#fff;'
                                : 'color:hsl(240 3.8% 46.1%);'"
                            @click="activeTab = tab">
                            {{ tab }}
                        </button>
                    </div>

                    <!-- Clear filters -->
                    <button v-if="search || riskFilter || activeTab !== 'all'"
                        class="text-xs underline transition-colors ml-1"
                        style="color:hsl(240 3.8% 46.1%);"
                        @mouseenter="$event.target.style.color='hsl(240 10% 3.9%)'"
                        @mouseleave="$event.target.style.color='hsl(240 3.8% 46.1%)'"
                        @click="clearFilters">
                        Clear filters
                    </button>

                    <!-- Count -->
                    <span class="text-xs ml-auto" style="color:hsl(240 3.8% 46.1%);">
                        {{ filtered.length }} of {{ voters.length }} voter{{ voters.length !== 1 ? 's' : '' }}
                    </span>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="border-bottom:1px solid hsl(240 5.9% 90%);">
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Voter</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Voter ID</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Score</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Risk</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Email</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Status</th>
                                <th v-if="isAdmin" class="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filtered.length === 0">
                                <td :colspan="isAdmin ? 7 : 6" class="text-center py-14">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:hsl(240 5.9% 82%)">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm" style="color:hsl(240 3.8% 46.1%);">No voters match your filters.</p>
                                        <button class="text-xs underline" style="color:hsl(240 3.8% 46.1%);" @click="clearFilters">Clear filters</button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-for="voter in filtered" :key="voter.id"
                                class="cursor-pointer transition-colors"
                                style="border-bottom:1px solid hsl(240 5.9% 95%);"
                                @mouseenter="$event.currentTarget.style.background='hsl(240 4.8% 98.5%)'"
                                @mouseleave="$event.currentTarget.style.background=''"
                                @click="openDetail(voter)">

                                <!-- Voter -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                            style="background:hsl(240 5.9% 10%); color:#fff;">
                                            {{ voter.name?.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium truncate" style="color:hsl(240 10% 3.9%);">{{ voter.name }}</p>
                                            <p class="text-xs font-mono" style="color:hsl(240 3.8% 46.1%);">{{ voter.student_id_number }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Voter ID -->
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs px-1.5 py-0.5 rounded"
                                        style="background:hsl(240 4.8% 95.9%); color:hsl(240 10% 3.9%);">
                                        {{ voter.voter_id_number ?? '—' }}
                                    </span>
                                </td>

                                <!-- Score -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-14 rounded-full overflow-hidden" style="background:hsl(240 4.8% 95.9%);">
                                            <div class="h-1.5 rounded-full transition-all"
                                                :style="{ width: Math.max(0, voter.fraud_score) + '%', backgroundColor: riskLevel(voter.fraud_score).dot }" />
                                        </div>
                                        <span class="text-sm font-semibold tabular-nums" style="color:hsl(240 10% 3.9%);">{{ voter.fraud_score }}</span>
                                    </div>
                                </td>

                                <!-- Risk -->
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                                        :style="{ backgroundColor: riskLevel(voter.fraud_score).bg, color: riskLevel(voter.fraud_score).text }">
                                        <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: riskLevel(voter.fraud_score).dot }"></span>
                                        {{ riskLevel(voter.fraud_score).label }}
                                    </span>
                                </td>

                                <!-- Email -->
                                <td class="px-4 py-3">
                                    <span v-if="voter.email_verified" class="inline-flex items-center gap-1 text-xs font-medium px-1.5 py-0.5 rounded"
                                        style="background:hsl(142 76% 94%); color:hsl(142 71% 29%);">
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        Verified
                                    </span>
                                    <span v-else class="text-xs" style="color:hsl(240 3.8% 46.1%);">—</span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3">
                                    <span v-if="voter.is_verified"
                                        class="text-xs font-medium px-1.5 py-0.5 rounded"
                                        style="background:hsl(221 83% 94%); color:hsl(221 83% 35%);">
                                        Approved
                                    </span>
                                    <span v-else
                                        class="text-xs font-medium px-1.5 py-0.5 rounded"
                                        style="background:hsl(38 92% 94%); color:hsl(38 62% 30%);">
                                        Pending
                                    </span>
                                </td>

                                <!-- Action -->
                                <td v-if="isAdmin" class="px-4 py-3 text-right" @click.stop>
                                    <Button size="sm" variant="outline" @click="openDetail(voter)">Review</Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
