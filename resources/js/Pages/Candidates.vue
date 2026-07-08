<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    candidates: {
        type: Array,
        default: () => [],
    },
    elections: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
    positionOptions: {
        type: Array,
        default: () => [],
    },
    departmentColors: {
        type: Object,
        default: () => ({}),
    },
});

const { error: toastError } = useToast();

const searchQuery = ref('');
const electionFilter = ref('all');
const positionFilter = ref('all');
const departmentFilter = ref('all');

const showDeleteDialog = ref(false);
const deletingCandidate = ref(null);
const showPlatformDialog = ref(false);
const platformCandidate = ref(null);

const electionFilterOptions = computed(() => [
    { value: 'all', label: 'All elections' },
    ...props.elections.map((item) => ({ value: String(item.id), label: item.title })),
]);

const positionFilterOptions = computed(() => [
    { value: 'all', label: 'All positions' },
    ...props.positionOptions,
]);

const departmentFilterOptions = computed(() => [
    { value: 'all', label: 'All departments' },
    { value: 'none', label: 'No department' },
    ...props.departments.map((item) => ({ value: String(item.id), label: item.name })),
]);

const hasActiveFilters = computed(() =>
    searchQuery.value.trim() !== ''
    || electionFilter.value !== 'all'
    || positionFilter.value !== 'all'
    || departmentFilter.value !== 'all',
);

const filteredCandidates = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.candidates.filter((candidate) => {
        if (electionFilter.value !== 'all' && String(candidate.election_id) !== electionFilter.value) {
            return false;
        }

        if (positionFilter.value !== 'all' && String(candidate.position_id) !== positionFilter.value) {
            return false;
        }

        if (departmentFilter.value === 'none' && candidate.department_id) {
            return false;
        }

        if (departmentFilter.value !== 'all' && departmentFilter.value !== 'none'
            && String(candidate.department_id) !== departmentFilter.value) {
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
            candidate.partylist_label,
            candidate.partylist_name,
            candidate.platform,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(query);
    });
});

const emptyStateMessage = computed(() => {
    if (props.elections.length === 0) {
        return 'No elections available yet.';
    }

    if (props.candidates.length === 0) {
        return 'No candidates have been added yet.';
    }

    if (hasActiveFilters.value) {
        return 'No candidates match your search or filters.';
    }

    return 'No candidates found.';
});

function clearFilters() {
    searchQuery.value = '';
    electionFilter.value = 'all';
    positionFilter.value = 'all';
    departmentFilter.value = 'all';
}

function departmentColorHex(colorName) {
    return props.departmentColors[colorName] || '#2563eb';
}

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
}

function truncateText(text, maxLength = 55) {
    if (!text || text.length <= maxLength) {
        return text;
    }

    const trimmed = text.slice(0, maxLength).trimEnd();
    const lastSpace = trimmed.lastIndexOf(' ');

    if (lastSpace > maxLength * 0.6) {
        return `${trimmed.slice(0, lastSpace)}…`;
    }

    return `${trimmed}…`;
}

function isPlatformTruncated(text, maxLength = 55) {
    return Boolean(text && text.length > maxLength);
}

function openPlatformDialog(candidate) {
    platformCandidate.value = candidate;
    showPlatformDialog.value = true;
}

function closePlatformDialog() {
    showPlatformDialog.value = false;
    platformCandidate.value = null;
}

function goToCreatePage() {
    router.visit('/candidates/create');
}

function openDeleteDialog(candidate) {
    deletingCandidate.value = candidate;
    showDeleteDialog.value = true;
}

function closeDeleteDialog() {
    showDeleteDialog.value = false;
    deletingCandidate.value = null;
}

function confirmDelete() {
    if (!deletingCandidate.value) return;

    router.delete(`/candidates/${deletingCandidate.value.id}`, {
        preserveScroll: true,
        preserveState: true,
        only: ['candidates'],
        onSuccess: () => closeDeleteDialog(),
        onError: () => {
            toastError('Delete failed', 'Unable to remove this candidate. Please try again.');
            closeDeleteDialog();
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Candidates" />

        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Candidates</h1>
        </template>

        <div class="w-full space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold" style="color: hsl(240 10% 3.9%);">Election candidates</h2>
                    <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                        Add, edit, and manage candidates running in each election.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                        style="background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);"
                    >
                        <template v-if="hasActiveFilters">
                            {{ filteredCandidates.length }} of {{ candidates.length }}
                            {{ candidates.length === 1 ? 'candidate' : 'candidates' }}
                        </template>
                        <template v-else>
                            {{ filteredCandidates.length }}
                            {{ filteredCandidates.length === 1 ? 'candidate' : 'candidates' }}
                        </template>
                    </span>
                    <Button
                        type="button"
                        :disabled="elections.length === 0"
                        @click.prevent="goToCreatePage"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add candidate
                    </Button>
                </div>
            </div>

            <div
                class="rounded-xl border p-4 space-y-4"
                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold" style="color: hsl(240 10% 3.9%);">Search &amp; filters</h3>
                        <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                            Find candidates by name, position, election, or department.
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

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="space-y-1.5 sm:col-span-2 xl:col-span-1">
                        <Label html-for="candidate-search">Search</Label>
                        <div class="relative">
                            <svg
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                style="color: hsl(240 3.8% 46.1%);"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                            </svg>
                            <Input
                                id="candidate-search"
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search by name, platform..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label html-for="election-filter">Election</Label>
                        <Select
                            id="election-filter"
                            v-model="electionFilter"
                            :options="electionFilterOptions"
                            placeholder="All elections"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label html-for="position-filter">Position</Label>
                        <Select
                            id="position-filter"
                            v-model="positionFilter"
                            :options="positionFilterOptions"
                            placeholder="All positions"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label html-for="department-filter">Department</Label>
                        <Select
                            id="department-filter"
                            v-model="departmentFilter"
                            :options="departmentFilterOptions"
                            placeholder="All departments"
                        />
                    </div>
                </div>

                <p v-if="elections.length === 0" class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                    Create an election first before adding candidates.
                </p>
            </div>

            <div
                class="rounded-xl border overflow-hidden"
                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm table-fixed">
                        <thead>
                            <tr class="border-b" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);">
                                <th class="h-10 px-4 text-left align-middle font-medium w-[26%]" style="color: hsl(240 3.8% 46.1%);">Candidate</th>
                                <th class="h-10 px-4 text-left align-middle font-medium w-[11%]" style="color: hsl(240 3.8% 46.1%);">Position</th>
                                <th class="h-10 px-4 text-left align-middle font-medium w-[18%]" style="color: hsl(240 3.8% 46.1%);">Election</th>
                                <th class="h-10 px-4 text-left align-middle font-medium w-[18%]" style="color: hsl(240 3.8% 46.1%);">Department</th>
                                <th class="h-10 px-4 text-left align-middle font-medium w-[12%]" style="color: hsl(240 3.8% 46.1%);">Partylist</th>
                                <th class="h-10 px-4 text-right align-middle font-medium w-[15%]" style="color: hsl(240 3.8% 46.1%);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="candidate in filteredCandidates"
                                :key="candidate.id"
                                class="border-b transition-colors hover:bg-gray-50"
                                style="border-color: hsl(240 5.9% 90%);"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <img
                                            v-if="candidate.photo_url"
                                            :src="candidate.photo_url"
                                            :alt="candidate.name"
                                            class="h-9 w-9 rounded-full object-cover shrink-0 border"
                                            style="border-color: hsl(240 5.9% 90%);"
                                        />
                                        <div
                                            v-else
                                            class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-semibold shrink-0"
                                            style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);"
                                        >
                                            {{ getInitials(candidate.name) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium truncate" style="color: hsl(240 10% 3.9%);">{{ candidate.name }}</p>
                                            <div v-if="candidate.platform" class="flex items-center gap-1 mt-0.5 min-w-0">
                                                <p
                                                    class="text-xs truncate min-w-0"
                                                    style="color: hsl(240 3.8% 46.1%);"
                                                >
                                                    {{ truncateText(candidate.platform) }}
                                                </p>
                                                <button
                                                    v-if="isPlatformTruncated(candidate.platform)"
                                                    type="button"
                                                    class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full transition-colors hover:bg-gray-100"
                                                    style="color: hsl(221 83% 53%);"
                                                    aria-label="View full advocacies and platform"
                                                    @click.stop="openPlatformDialog(candidate)"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        style="background-color: hsl(221 83% 94%); color: hsl(221 83% 35%);"
                                    >
                                        {{ candidate.position }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="block truncate" style="color: hsl(240 3.8% 46.1%);" :title="candidate.election_title">
                                        {{ candidate.election_title }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div v-if="candidate.department_name" class="flex items-center gap-2 min-w-0">
                                        <span
                                            class="h-2.5 w-2.5 rounded-full shrink-0"
                                            :style="{ backgroundColor: departmentColorHex(candidate.department_color) }"
                                        />
                                        <span class="truncate" style="color: hsl(240 3.8% 46.1%);" :title="candidate.department_name">
                                            {{ candidate.department_name }}
                                        </span>
                                    </div>
                                    <span v-else style="color: hsl(240 3.8% 46.1%);">—</span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :style="candidate.partylist_id
                                            ? 'background-color: hsl(262 83% 94%); color: hsl(262 83% 35%);'
                                            : 'background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);'"
                                    >
                                        {{ candidate.partylist_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button variant="outline" size="sm" @click="router.visit(`/candidates/${candidate.id}/edit`)">Edit</Button>
                                        <Button variant="destructive" size="sm" @click="openDeleteDialog(candidate)">Delete</Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredCandidates.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center" style="color: hsl(240 3.8% 46.1%);">
                                    <p>{{ emptyStateMessage }}</p>
                                    <button
                                        v-if="hasActiveFilters"
                                        type="button"
                                        class="mt-2 text-sm font-medium transition-colors hover:underline"
                                        style="color: hsl(240 5.9% 10%);"
                                        @click="clearFilters"
                                    >
                                        Clear search and filters
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Dialog
            :show="showDeleteDialog"
            title="Remove candidate"
            description="This action cannot be undone."
            @close="closeDeleteDialog"
        >
            <p class="text-sm mb-6" style="color: hsl(240 3.8% 46.1%);">
                Are you sure you want to remove
                <span class="font-medium" style="color: hsl(240 10% 3.9%);">{{ deletingCandidate?.name }}</span>
                from {{ deletingCandidate?.election_title }}?
            </p>

            <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="closeDeleteDialog">Cancel</Button>
                <Button type="button" variant="destructive" @click="confirmDelete">Remove candidate</Button>
            </div>
        </Dialog>

        <Dialog
            :show="showPlatformDialog"
            title="Advocacies & Platform"
            :description="platformCandidate?.name"
            wide
            @close="closePlatformDialog"
        >
            <p class="text-sm leading-relaxed whitespace-pre-wrap" style="color: hsl(240 5.9% 20%);">
                {{ platformCandidate?.platform }}
            </p>

            <div class="mt-6 flex justify-end">
                <Button type="button" variant="outline" @click="closePlatformDialog">Close</Button>
            </div>
        </Dialog>
    </AppLayout>
</template>
