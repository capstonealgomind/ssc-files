<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import VoterHoverCard from '@/Components/VoterHoverCard.vue';
import { useToast } from '@/composables/useToast';
import { useClientPagination } from '@/composables/useClientPagination';

const props = defineProps({
    voters: { type: Array, default: () => [] },
});

const page      = usePage();
const { error: toastError } = useToast();
const canManageVoters = computed(() => ['admin', 'committee'].includes(page.props.auth?.user?.role));
const activeTab = ref('all');
const search    = ref('');
const riskFilter = ref('');
const departmentFilter = ref('');
const courseFilter = ref('');
const yearFilter = ref('');

const showDeleteDialog = ref(false);
const deletingVoter = ref(null);
const pendingDeleteIds = ref([]);
const deleteConfirmText = ref('');
const deleteForm = useForm({ confirmation: '', ids: [] });
const canConfirmDelete = computed(() => deleteConfirmText.value === 'DELETE');
const isBulkDelete = computed(() => !deletingVoter.value && pendingDeleteIds.value.length > 0);
const deleteTargetCount = computed(() =>
    deletingVoter.value ? 1 : pendingDeleteIds.value.length,
);

function riskLevel(score) {
    if (score >= 80) return { label: 'LOW',      dot: 'hsl(142 71% 45%)', bg: 'hsl(142 76% 94%)', text: 'hsl(142 71% 29%)' };
    if (score >= 50) return { label: 'MODERATE', dot: 'hsl(38 92% 50%)',  bg: 'hsl(38 92% 94%)',  text: 'hsl(38 62% 30%)' };
    if (score >= 20) return { label: 'HIGH',     dot: 'hsl(25 95% 53%)',  bg: 'hsl(25 95% 94%)',  text: 'hsl(25 75% 30%)' };
    return              { label: 'CRITICAL',     dot: 'hsl(0 84% 60%)',   bg: 'hsl(0 84% 94%)',   text: 'hsl(0 62% 35%)' };
}

function schoolYearBadge(voter) {
    if (typeof voter?.school_year_updated !== 'boolean') {
        return null;
    }

    const yearLabel = voter.school_year_label ? ` for ${voter.school_year_label}` : '';

    if (voter.school_year_updated) {
        return {
            label: 'Updated',
            bg: 'hsl(142 76% 94%)',
            text: 'hsl(142 71% 29%)',
            title: `Year level confirmed${yearLabel}`,
        };
    }

    if (!voter.is_verified) {
        return null;
    }

    return {
        label: 'Outdated',
        bg: 'hsl(38 92% 94%)',
        text: 'hsl(38 62% 30%)',
        title: `Year level not updated${yearLabel}`,
    };
}

const counts = computed(() => ({
    all:      props.voters.length,
    pending:  props.voters.filter(v => !v.is_verified && v.email_verified).length,
    verified: props.voters.filter(v => v.is_verified).length,
    flagged:  props.voters.filter(v => v.fraud_score < 20).length,
}));

function uniqueNamedOptions(voters, idKey, nameKey, sortKey = null) {
    const seen = new Map();

    for (const voter of voters) {
        const id = voter[idKey];
        if (id == null || id === '') {
            continue;
        }

        const value = String(id);
        if (!seen.has(value)) {
            seen.set(value, {
                value,
                label: voter[nameKey] || `ID ${value}`,
                sort: sortKey != null ? (voter[sortKey] ?? 999) : 0,
            });
        }
    }

    return [...seen.values()].sort((a, b) => {
        if (sortKey != null && a.sort !== b.sort) {
            return a.sort - b.sort;
        }

        return a.label.localeCompare(b.label);
    });
}

function optionExists(options, value) {
    return options.some((option) => option.value === value);
}

const departmentOptions = computed(() =>
    uniqueNamedOptions(props.voters, 'department_id', 'department'),
);

const courseOptions = computed(() => {
    const source = departmentFilter.value
        ? props.voters.filter((voter) => String(voter.department_id) === departmentFilter.value)
        : props.voters;

    return uniqueNamedOptions(source, 'course_id', 'course');
});

const yearOptions = computed(() => {
    let source = props.voters;

    if (departmentFilter.value) {
        source = source.filter((voter) => String(voter.department_id) === departmentFilter.value);
    }

    if (courseFilter.value) {
        source = source.filter((voter) => String(voter.course_id) === courseFilter.value);
    }

    return uniqueNamedOptions(source, 'year_level_id', 'year_level', 'year_level_sort');
});

const hasActiveFilters = computed(() =>
    Boolean(search.value.trim() || riskFilter.value || departmentFilter.value || courseFilter.value || yearFilter.value)
    || activeTab.value !== 'all',
);

const filtered = computed(() => {
    let list = props.voters;

    if (activeTab.value === 'pending')  list = list.filter(v => !v.is_verified && v.email_verified);
    if (activeTab.value === 'verified') list = list.filter(v => v.is_verified);
    if (activeTab.value === 'flagged')  list = list.filter(v => v.fraud_score < 20);

    if (riskFilter.value) {
        list = list.filter(v => riskLevel(v.fraud_score).label === riskFilter.value);
    }

    if (departmentFilter.value) {
        list = list.filter(v => String(v.department_id) === departmentFilter.value);
    }

    if (courseFilter.value) {
        list = list.filter(v => String(v.course_id) === courseFilter.value);
    }

    if (yearFilter.value) {
        list = list.filter(v => String(v.year_level_id) === yearFilter.value);
    }

    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(v =>
            v.name?.toLowerCase().includes(q) ||
            v.email?.toLowerCase().includes(q) ||
            v.student_id_number?.toLowerCase().includes(q) ||
            v.voter_id_number?.toLowerCase().includes(q) ||
            v.department?.toLowerCase().includes(q) ||
            v.course?.toLowerCase().includes(q) ||
            v.year_level?.toLowerCase().includes(q),
        );
    }

    return list;
});

const { items: pagedVoters, meta: voterPage, setPage: setVoterPage } = useClientPagination(filtered);

const selectedIds = ref([]);
const selectedSet = computed(() => new Set(selectedIds.value));
const pageIds = computed(() => pagedVoters.value.map((voter) => voter.id));
const filteredIds = computed(() => filtered.value.map((voter) => voter.id));

const selectedCount = computed(() => selectedIds.value.length);
const selectedOnPageCount = computed(() =>
    pageIds.value.filter((id) => selectedSet.value.has(id)).length,
);
const selectedFilteredCount = computed(() =>
    filteredIds.value.filter((id) => selectedSet.value.has(id)).length,
);
const allPageSelected = computed(() =>
    pageIds.value.length > 0 && selectedOnPageCount.value === pageIds.value.length,
);
const allFilteredSelected = computed(() =>
    filteredIds.value.length > 0 && selectedFilteredCount.value === filteredIds.value.length,
);
const headerIndeterminate = computed(() =>
    selectedOnPageCount.value > 0 && !allPageSelected.value,
);

function isSelected(id) {
    return selectedSet.value.has(id);
}

function toggleSelected(id) {
    if (isSelected(id)) {
        selectedIds.value = selectedIds.value.filter((item) => item !== id);
        return;
    }

    selectedIds.value = [...selectedIds.value, id];
}

function togglePageSelection() {
    if (allPageSelected.value) {
        const page = new Set(pageIds.value);
        selectedIds.value = selectedIds.value.filter((id) => !page.has(id));
        return;
    }

    const next = new Set(selectedIds.value);
    for (const id of pageIds.value) {
        next.add(id);
    }
    selectedIds.value = [...next];
}

function selectAllFiltered() {
    selectedIds.value = [...filteredIds.value];
}

function clearSelection() {
    selectedIds.value = [];
}

watch(departmentFilter, () => {
    if (courseFilter.value && !optionExists(courseOptions.value, courseFilter.value)) {
        courseFilter.value = '';
    }

    if (yearFilter.value && !optionExists(yearOptions.value, yearFilter.value)) {
        yearFilter.value = '';
    }
});

watch(courseFilter, () => {
    if (yearFilter.value && !optionExists(yearOptions.value, yearFilter.value)) {
        yearFilter.value = '';
    }
});

watch([search, riskFilter, activeTab, departmentFilter, courseFilter, yearFilter], () => {
    setVoterPage(1);
});

watch(() => props.voters, (voters) => {
    const existing = new Set(voters.map((voter) => voter.id));
    selectedIds.value = selectedIds.value.filter((id) => existing.has(id));

    if (deletingVoter.value && !existing.has(deletingVoter.value.id)) {
        toastError('Voter unavailable', 'This student was deleted by another admin.');
        closeDeleteDialog();
        return;
    }

    if (pendingDeleteIds.value.length > 0) {
        const remaining = pendingDeleteIds.value.filter((id) => existing.has(id));
        if (remaining.length !== pendingDeleteIds.value.length) {
            pendingDeleteIds.value = remaining;
            deleteForm.ids = [...remaining];
            if (remaining.length === 0) {
                toastError('Students unavailable', 'The selected students were already deleted.');
                closeDeleteDialog();
            }
        }
    }
});

watch(filteredIds, (ids) => {
    const allowed = new Set(ids);
    const next = selectedIds.value.filter((id) => allowed.has(id));
    if (next.length !== selectedIds.value.length) {
        selectedIds.value = next;
    }
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
    if (!props.voters.some((item) => item.id === voter.id)) {
        toastError('Voter unavailable', 'This student is no longer in the list.');
        return;
    }

    router.visit(`/voters/${voter.id}`);
}

function clearFilters() {
    search.value = '';
    riskFilter.value = '';
    departmentFilter.value = '';
    courseFilter.value = '';
    yearFilter.value = '';
    activeTab.value = 'all';
}

function openDeleteDialog(voter) {
    deletingVoter.value = voter;
    pendingDeleteIds.value = [];
    deleteConfirmText.value = '';
    deleteForm.clearErrors();
    deleteForm.confirmation = '';
    deleteForm.ids = [];
    showDeleteDialog.value = true;
}

function openBulkDeleteDialog() {
    if (!canManageVoters.value || selectedIds.value.length === 0) {
        return;
    }

    deletingVoter.value = null;
    pendingDeleteIds.value = [...selectedIds.value];
    deleteConfirmText.value = '';
    deleteForm.clearErrors();
    deleteForm.confirmation = '';
    deleteForm.ids = [...pendingDeleteIds.value];
    showDeleteDialog.value = true;
}

function closeDeleteDialog() {
    showDeleteDialog.value = false;
    deletingVoter.value = null;
    pendingDeleteIds.value = [];
    deleteConfirmText.value = '';
    deleteForm.reset();
    deleteForm.clearErrors();
}

function confirmDelete() {
    if (!canConfirmDelete.value || deleteForm.processing) {
        return;
    }

    deleteForm.confirmation = 'DELETE';

    if (deletingVoter.value) {
        deleteForm.delete(`/voters/${deletingVoter.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeDeleteDialog(),
            onError: () => {
                toastError(
                    'Delete failed',
                    deleteForm.errors.confirmation
                        || Object.values(deleteForm.errors)[0]
                        || 'Unable to delete this voter. Please try again.',
                );
            },
        });
        return;
    }

    if (pendingDeleteIds.value.length === 0) {
        return;
    }

    deleteForm.ids = [...pendingDeleteIds.value];
    deleteForm.delete('/voters/bulk', {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            closeDeleteDialog();
        },
        onError: () => {
            toastError(
                'Delete failed',
                deleteForm.errors.confirmation
                    || deleteForm.errors.ids
                    || Object.values(deleteForm.errors)[0]
                    || 'Unable to delete the selected students. Please try again.',
            );
        },
    });
}

const POLL_INTERVAL_MS = 5000;
let pollTimer = null;
let pollInFlight = false;

function isPollingPaused() {
    return document.hidden || deleteForm.processing;
}

function refreshVoters() {
    if (pollInFlight || isPollingPaused()) {
        return;
    }

    pollInFlight = true;
    router.reload({
        only: ['voters'],
        preserveScroll: true,
        preserveState: true,
        showProgress: false,
        onFinish: () => {
            pollInFlight = false;
        },
    });
}

function onVisibilityChange() {
    if (!document.hidden) {
        refreshVoters();
    }
}

onMounted(() => {
    pollTimer = window.setInterval(refreshVoters, POLL_INTERVAL_MS);
    document.addEventListener('visibilitychange', onVisibilityChange);
});

onUnmounted(() => {
    if (pollTimer) {
        window.clearInterval(pollTimer);
        pollTimer = null;
    }
    document.removeEventListener('visibilitychange', onVisibilityChange);
});
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
                        <Input v-model="search" placeholder="Search name, ID, email…" class="pl-8 h-8 text-sm w-80 sm:w-[28rem]" />
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

                    <!-- Department -->
                    <select v-model="departmentFilter"
                        aria-label="Filter by department"
                        class="h-8 max-w-[12rem] rounded-md border px-2 text-xs outline-none focus:ring-1"
                        style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%); background:#fff;">
                        <option value="">All departments</option>
                        <option v-for="option in departmentOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <!-- Course -->
                    <select v-model="courseFilter"
                        aria-label="Filter by course"
                        class="h-8 max-w-[14rem] rounded-md border px-2 text-xs outline-none focus:ring-1"
                        style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%); background:#fff;">
                        <option value="">All courses</option>
                        <option v-for="option in courseOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <!-- Year -->
                    <select v-model="yearFilter"
                        aria-label="Filter by year"
                        class="h-8 rounded-md border px-2 text-xs outline-none focus:ring-1"
                        style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%); background:#fff;">
                        <option value="">All years</option>
                        <option v-for="option in yearOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <!-- Clear filters -->
                    <button v-if="hasActiveFilters"
                        class="text-xs underline transition-colors ml-1"
                        style="color:hsl(240 3.8% 46.1%);"
                        @mouseenter="$event.target.style.color='hsl(240 10% 3.9%)'"
                        @mouseleave="$event.target.style.color='hsl(240 3.8% 46.1%)'"
                        @click="clearFilters">
                        Clear filters
                    </button>

                    <!-- Selection -->
                    <div class="flex items-center gap-2 ml-auto">
                        <Button
                            v-if="canManageVoters && selectedCount > 0"
                            size="sm"
                            variant="destructive"
                            @click="openBulkDeleteDialog"
                        >
                            Delete ({{ selectedCount }})
                        </Button>
                        <button
                            v-if="filtered.length > 0 && !allFilteredSelected"
                            type="button"
                            class="text-xs font-medium underline-offset-2 hover:underline"
                            style="color:hsl(221 83% 46%);"
                            @click="selectAllFiltered"
                        >
                            Select all {{ filtered.length }}
                        </button>
                        <button
                            v-if="selectedCount > 0"
                            type="button"
                            class="text-xs underline transition-colors"
                            style="color:hsl(240 3.8% 46.1%);"
                            @click="clearSelection"
                        >
                            Clear selection
                        </button>
                        <span class="text-xs" style="color:hsl(240 3.8% 46.1%);">
                            <template v-if="selectedCount > 0">
                                {{ selectedCount }} selected
                            </template>
                            <template v-else>
                                {{ filtered.length }} of {{ voters.length }} voter{{ voters.length !== 1 ? 's' : '' }}
                            </template>
                        </span>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="border-bottom:1px solid hsl(240 5.9% 90%);">
                                <th class="w-10 px-4 py-2.5">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300"
                                        style="accent-color: hsl(221 83% 53%);"
                                        aria-label="Select all on this page"
                                        title="Select all on this page"
                                        :checked="allPageSelected"
                                        :indeterminate="headerIndeterminate"
                                        :disabled="pageIds.length === 0"
                                        @change="togglePageSelection"
                                    >
                                </th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Voter</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Voter ID</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Department</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Course</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Year</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Score</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Risk</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Email</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Status</th>
                                <th v-if="canManageVoters" class="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filtered.length === 0">
                                <td :colspan="canManageVoters ? 11 : 10" class="text-center py-14">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:hsl(240 5.9% 82%)">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm" style="color:hsl(240 3.8% 46.1%);">No voters match your filters.</p>
                                        <button class="text-xs underline" style="color:hsl(240 3.8% 46.1%);" @click="clearFilters">Clear filters</button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-for="voter in pagedVoters" :key="voter.id"
                                class="transition-colors"
                                :class="isSelected(voter.id)
                                    ? 'bg-[hsl(221_83%_97%)] hover:bg-[hsl(221_83%_95%)]'
                                    : 'hover:bg-[hsl(240_4.8%_98.5%)]'"
                                style="border-bottom:1px solid hsl(240 5.9% 95%);">

                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300"
                                        style="accent-color: hsl(221 83% 53%);"
                                        :aria-label="`Select ${voter.name}`"
                                        :checked="isSelected(voter.id)"
                                        @change="toggleSelected(voter.id)"
                                    >
                                </td>

                                <!-- Voter -->
                                <td class="px-4 py-3">
                                    <VoterHoverCard :voter="voter">
                                        <div class="flex items-center gap-3 cursor-default">
                                            <div class="h-8 w-8 rounded-full overflow-hidden flex items-center justify-center text-xs font-bold shrink-0 ring-offset-1 hover:ring-2 hover:ring-[hsl(221_83%_70%)]"
                                                style="background:hsl(240 5.9% 10%); color:#fff;">
                                                <img
                                                    v-if="voter.profile_photo_url"
                                                    :src="voter.profile_photo_url"
                                                    :alt="voter.name"
                                                    class="h-full w-full object-cover"
                                                />
                                                <template v-else>
                                                    {{ voter.name?.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() }}
                                                </template>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium truncate hover:underline" style="color:hsl(240 10% 3.9%);">{{ voter.name }}</p>
                                                <p class="text-xs font-mono" style="color:hsl(240 3.8% 46.1%);">{{ voter.student_id_number }}</p>
                                            </div>
                                        </div>
                                    </VoterHoverCard>
                                </td>

                                <!-- Voter ID -->
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs px-1.5 py-0.5 rounded"
                                        style="background:hsl(240 4.8% 95.9%); color:hsl(240 10% 3.9%);">
                                        {{ voter.voter_id_number ?? '—' }}
                                    </span>
                                </td>

                                <!-- Department -->
                                <td class="px-4 py-3 max-w-[11rem]">
                                    <span class="block truncate text-xs" :title="voter.department || ''" style="color:hsl(240 10% 3.9%);">{{ voter.department || '—' }}</span>
                                </td>

                                <!-- Course -->
                                <td class="px-4 py-3 max-w-[14rem]">
                                    <span class="block truncate text-xs" :title="voter.course || ''" style="color:hsl(240 10% 3.9%);">{{ voter.course || '—' }}</span>
                                </td>

                                <!-- Year -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="text-xs" style="color:hsl(240 10% 3.9%);">{{ voter.year_level || '—' }}</span>
                                        <span
                                            v-if="schoolYearBadge(voter)"
                                            class="text-[10px] font-semibold px-1.5 py-0.5 rounded"
                                            :style="{ backgroundColor: schoolYearBadge(voter).bg, color: schoolYearBadge(voter).text }"
                                            :title="schoolYearBadge(voter).title"
                                        >
                                            {{ schoolYearBadge(voter).label }}
                                        </span>
                                    </div>
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
                                    <span v-if="voter.is_disabled"
                                        class="text-xs font-medium px-1.5 py-0.5 rounded"
                                        style="background:hsl(0 84% 94%); color:hsl(0 72% 35%);">
                                        Disabled
                                    </span>
                                    <span v-else-if="voter.is_expired"
                                        class="text-xs font-medium px-1.5 py-0.5 rounded"
                                        style="background:hsl(0 84% 94%); color:hsl(0 72% 35%);">
                                        Expired
                                    </span>
                                    <span v-else-if="voter.is_verified"
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
                                <td v-if="canManageVoters" class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <Button size="sm" variant="outline" @click="openDetail(voter)">Review</Button>
                                        <Button size="sm" variant="destructive" @click="openDeleteDialog(voter)">Delete</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    :current-page="voterPage.current_page"
                    :last-page="voterPage.last_page"
                    :total="voterPage.total"
                    :from="voterPage.from"
                    :to="voterPage.to"
                    @change="setVoterPage"
                />
            </div>
        </div>

        <Dialog
            :show="showDeleteDialog"
            :title="isBulkDelete ? `Delete ${deleteTargetCount} student${deleteTargetCount === 1 ? '' : 's'}` : 'Delete voter'"
            description="This permanently removes the voter account and cannot be undone."
            :persistent="deleteForm.processing"
            @close="closeDeleteDialog"
        >
            <div class="space-y-4">
                <p v-if="deletingVoter" class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                    You are about to permanently delete
                    <span class="font-semibold" style="color: hsl(240 10% 3.9%);">{{ deletingVoter?.name }}</span>
                    <span v-if="deletingVoter?.voter_id_number" class="font-mono text-xs"> ({{ deletingVoter.voter_id_number }})</span>.
                    Their votes, ballot receipts, and related records will also be removed.
                </p>
                <p v-else class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                    You are about to permanently delete
                    <span class="font-semibold" style="color: hsl(240 10% 3.9%);">
                        {{ deleteTargetCount }} selected student{{ deleteTargetCount === 1 ? '' : 's' }}
                    </span>.
                    Their votes, ballot receipts, and related records will also be removed.
                </p>

                <div class="space-y-2">
                    <label class="block text-sm font-medium" style="color: hsl(240 10% 3.9%);" for="voter-delete-confirm">
                        Type <span class="font-mono font-bold">DELETE</span> to confirm
                    </label>
                    <Input
                        id="voter-delete-confirm"
                        v-model="deleteConfirmText"
                        type="text"
                        autocomplete="off"
                        placeholder="DELETE"
                        :disabled="deleteForm.processing"
                        @keydown.enter.prevent="confirmDelete"
                    />
                    <p v-if="deleteForm.errors.confirmation" class="text-xs" style="color: hsl(0 72% 40%);">
                        {{ deleteForm.errors.confirmation }}
                    </p>
                    <p v-else-if="deleteForm.errors.ids" class="text-xs" style="color: hsl(0 72% 40%);">
                        {{ deleteForm.errors.ids }}
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button type="button" variant="outline" :disabled="deleteForm.processing" @click="closeDeleteDialog">
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="!canConfirmDelete || deleteForm.processing"
                        @click="confirmDelete"
                    >
                        <template v-if="deleteForm.processing">Deleting…</template>
                        <template v-else-if="isBulkDelete">
                            Delete {{ deleteTargetCount }} student{{ deleteTargetCount === 1 ? '' : 's' }}
                        </template>
                        <template v-else>Delete voter</template>
                    </Button>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
