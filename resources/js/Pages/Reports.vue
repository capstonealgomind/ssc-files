<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Label from '@/Components/ui/Label.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    electionOptions: { type: Array, default: () => [] },
    selectedElectionId: { type: Number, default: null },
    preview: { type: Object, default: null },
    reportTypes: { type: Array, default: () => [] },
    filterOptions: {
        type: Object,
        default: () => ({ departments: [], yearLevels: [], courses: [] }),
    },
});

const { error: toastError, success: toastSuccess } = useToast();
const exporting = ref(false);
const showExportDialog = ref(false);
const exportType = ref(null);
const exportFormat = ref('pdf');
const modalElectionId = ref('');
const modalDateFrom = ref('');
const modalDateTo = ref('');
const modalDepartmentId = ref('');
const modalYearLevelId = ref('');
const modalCourseId = ref('');
const filterHasData = ref(true);
const checkingFilters = ref(false);
let availabilityTimer = null;
let availabilityRequestId = 0;

const selectedElectionValue = computed({
    get: () => (props.selectedElectionId ? String(props.selectedElectionId) : ''),
    set: (value) => {
        if (!value) return;
        router.get('/reports', { election_id: value }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
});

const summaryCards = computed(() => {
    const summary = props.preview?.summary;
    if (!summary) return [];

    return [
        { label: 'Eligible voters', value: summary.eligible_voters },
        { label: 'Ballots cast', value: summary.ballots_cast },
        { label: 'Turnout', value: `${summary.turnout_rate}%` },
        { label: 'Total votes', value: summary.total_votes },
        { label: 'Candidates', value: summary.candidates },
        { label: 'Non-voters', value: summary.non_voters },
    ];
});

const selectedReport = computed(() =>
    props.reportTypes.find((item) => item.key === exportType.value) || null,
);

const showStudentFilters = computed(() => exportType.value === 'students_who_voted');

const canDownload = computed(() =>
    Boolean(modalElectionId.value)
    && filterHasData.value
    && !checkingFilters.value
    && !exporting.value,
);

const modalElectionOption = computed(() =>
    props.electionOptions.find((item) => item.value === modalElectionId.value) || null,
);

const departmentFilterOptions = computed(() => props.filterOptions?.departments || []);

const yearLevelFilterOptions = computed(() => props.filterOptions?.yearLevels || []);

const courseFilterOptions = computed(() => {
    const courses = props.filterOptions?.courses || [];
    if (!modalDepartmentId.value) {
        return courses;
    }

    return courses.filter((course) => course.department_id === modalDepartmentId.value);
});

function hasData(type) {
    return Boolean(props.preview?.availability?.[type]);
}

function openExportDialog(type, format) {
    if (!hasData(type) || !props.selectedElectionId || exporting.value) return;

    exportType.value = type;
    exportFormat.value = format;
    modalElectionId.value = String(props.selectedElectionId);
    modalDateFrom.value = props.preview?.election?.voting_starts_at || '';
    modalDateTo.value = props.preview?.election?.voting_ends_at || '';
    modalDepartmentId.value = '';
    modalYearLevelId.value = '';
    modalCourseId.value = '';
    filterHasData.value = true;
    showExportDialog.value = true;
    scheduleAvailabilityCheck();
}

function closeExportDialog() {
    if (exporting.value) return;
    showExportDialog.value = false;
    exportType.value = null;
    if (availabilityTimer) {
        clearTimeout(availabilityTimer);
        availabilityTimer = null;
    }
}

function onModalElectionChange(value) {
    modalElectionId.value = value;
    if (value === String(props.selectedElectionId)) {
        modalDateFrom.value = props.preview?.election?.voting_starts_at || modalDateFrom.value;
        modalDateTo.value = props.preview?.election?.voting_ends_at || modalDateTo.value;
    }
}

function onDepartmentFilterChange(value) {
    modalDepartmentId.value = value;
    const selectedCourse = (props.filterOptions?.courses || []).find(
        (course) => course.value === modalCourseId.value,
    );
    if (selectedCourse && value && selectedCourse.department_id !== value) {
        modalCourseId.value = '';
    }
}

function exportQueryParams(type, dateFrom, dateTo) {
    const params = new URLSearchParams();
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    if (type === 'students_who_voted') {
        if (modalDepartmentId.value) params.set('department_id', modalDepartmentId.value);
        if (modalYearLevelId.value) params.set('year_level_id', modalYearLevelId.value);
        if (modalCourseId.value) params.set('course_id', modalCourseId.value);
    }
    return params;
}

function exportUrl(electionId, type, format, dateFrom, dateTo) {
    const query = exportQueryParams(type, dateFrom, dateTo).toString();
    return `/reports/${electionId}/export/${type}/${format}${query ? `?${query}` : ''}`;
}

function scheduleAvailabilityCheck() {
    if (!showExportDialog.value || !exportType.value || !modalElectionId.value) {
        return;
    }

    if (availabilityTimer) {
        clearTimeout(availabilityTimer);
    }

    checkingFilters.value = true;
    availabilityTimer = setTimeout(() => {
        checkFilterAvailability();
    }, 250);
}

async function checkFilterAvailability() {
    if (!showExportDialog.value || !exportType.value || !modalElectionId.value) {
        checkingFilters.value = false;
        return;
    }

    const requestId = ++availabilityRequestId;
    const type = exportType.value;
    const electionId = modalElectionId.value;
    const query = exportQueryParams(type, modalDateFrom.value, modalDateTo.value).toString();
    const url = `/reports/${electionId}/export/${type}/availability${query ? `?${query}` : ''}`;

    try {
        const { data } = await window.axios.get(url);
        if (requestId !== availabilityRequestId) {
            return;
        }
        filterHasData.value = Boolean(data?.has_data);
    } catch {
        if (requestId !== availabilityRequestId) {
            return;
        }
        filterHasData.value = false;
    } finally {
        if (requestId === availabilityRequestId) {
            checkingFilters.value = false;
        }
    }
}

watch(
    [
        showExportDialog,
        exportType,
        modalElectionId,
        modalDateFrom,
        modalDateTo,
        modalDepartmentId,
        modalYearLevelId,
        modalCourseId,
    ],
    () => {
        if (!showExportDialog.value) {
            return;
        }
        scheduleAvailabilityCheck();
    },
);

function filenameFor(type, format, electionLabel) {
    const label = props.reportTypes.find((item) => item.key === type)?.label || type;
    const election = electionLabel || 'election';
    const slug = `${label}_${election}`
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_|_$/g, '')
        .slice(0, 60);

    return `sscevs_${slug}.${format === 'pdf' ? 'pdf' : 'xlsx'}`;
}

async function confirmExport() {
    if (!exportType.value || !modalElectionId.value || exporting.value || !canDownload.value) return;

    if (modalDateFrom.value && modalDateTo.value && modalDateFrom.value > modalDateTo.value) {
        toastError('Invalid date range', 'Start date must be on or before the end date.');
        return;
    }

    const type = exportType.value;
    const format = exportFormat.value;
    const electionId = modalElectionId.value;
    const electionLabel = modalElectionOption.value?.label || props.preview?.election?.title || 'election';
    const url = exportUrl(electionId, type, format, modalDateFrom.value, modalDateTo.value);

    exporting.value = true;

    try {
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: format === 'pdf'
                    ? 'application/pdf'
                    : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            },
        });

        if (!response.ok) {
            throw new Error(`Export failed (${response.status})`);
        }

        const blob = await response.blob();
        const blobUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = filenameFor(type, format, electionLabel);
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);

        toastSuccess(
            format === 'pdf' ? 'PDF ready' : 'Excel ready',
            `${selectedReport.value?.label || 'Report'} downloaded successfully.`,
        );
        showExportDialog.value = false;
        exportType.value = null;
    } catch {
        toastError('Export failed', 'Unable to download this report. Please try again.');
    } finally {
        exporting.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Reports" />

        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Reports</h1>
        </template>

        <div class="w-full space-y-5">
            <div class="rounded-xl border sscevs-panel p-4 sm:p-5" style="border-color: hsl(240 5.9% 90%); background: #fff;">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">
                            Election reports
                        </h2>
                        <p class="text-sm mt-1" style="color: hsl(240 3.8% 46.1%);">
                            Generate official results, turnout, non-voters, ballot logs, and more. Export as PDF or Excel.
                        </p>
                    </div>

                    <div class="w-full lg:w-80 space-y-1.5">
                        <Label html-for="report-election">Election</Label>
                        <Select
                            id="report-election"
                            v-model="selectedElectionValue"
                            :options="electionOptions"
                            placeholder="Select election"
                            :disabled="electionOptions.length === 0"
                        />
                    </div>
                </div>

                <div
                    v-if="preview"
                    class="mt-5 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3"
                >
                    <div
                        v-for="card in summaryCards"
                        :key="card.label"
                        class="rounded-lg border px-3 py-2.5"
                        style="border-color: hsl(240 5.9% 90%); background: hsl(240 4.8% 98%);"
                    >
                        <p class="text-[11px] font-medium" style="color: hsl(240 3.8% 46.1%);">{{ card.label }}</p>
                        <p class="text-lg font-semibold mt-0.5 tabular-nums" style="color: hsl(240 10% 3.9%);">
                            {{ Number.isFinite(Number(card.value)) ? Number(card.value).toLocaleString() : card.value }}
                        </p>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-5 rounded-lg border px-4 py-8 text-center text-sm"
                    style="border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
                >
                    No elections available for reporting yet.
                </div>
            </div>

            <div
                v-if="preview"
                class="grid grid-cols-1 xl:grid-cols-2 gap-4"
            >
                <section
                    v-for="report in reportTypes"
                    :key="report.key"
                    class="rounded-xl border sscevs-panel p-4 sm:p-5 flex flex-col"
                    style="border-color: hsl(240 5.9% 90%); background: #fff;"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="h-9 w-9 rounded-lg flex items-center justify-center shrink-0"
                            style="background: hsl(221 83% 94%); color: hsl(221 83% 35%);"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold" style="color: hsl(240 10% 3.9%);">
                                {{ report.label }}
                            </h3>
                            <p class="text-sm mt-1" style="color: hsl(240 3.8% 46.1%);">
                                {{ report.description }}
                            </p>
                            <p
                                v-if="!hasData(report.key)"
                                class="text-xs mt-2 font-medium"
                                style="color: hsl(38 92% 40%);"
                            >
                                No data available for this report yet.
                            </p>
                        </div>
                    </div>

                    <div class="mt-auto pt-4 flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="destructive"
                            :disabled="!hasData(report.key) || exporting"
                            :title="!hasData(report.key) ? 'No data available' : 'Export PDF'"
                            @click="openExportDialog(report.key, 'pdf')"
                        >
                            Export PDF
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="success"
                            :disabled="!hasData(report.key) || exporting"
                            :title="!hasData(report.key) ? 'No data available' : 'Export Excel'"
                            @click="openExportDialog(report.key, 'excel')"
                        >
                            Export Excel
                        </Button>
                    </div>
                </section>
            </div>
        </div>

        <Dialog
            :show="showExportDialog"
            :title="exportFormat === 'pdf' ? 'Export PDF' : 'Export Excel'"
            :description="selectedReport ? `Filter and download: ${selectedReport.label}` : 'Filter and download report'"
            :size="showStudentFilters ? 'lg' : 'md'"
            @close="closeExportDialog"
        >
            <form class="space-y-4" @submit.prevent="confirmExport">
                <div class="space-y-1.5">
                    <Label html-for="export-election">Election</Label>
                    <Select
                        id="export-election"
                        :model-value="modalElectionId"
                        :options="electionOptions"
                        placeholder="Select election"
                        @update:model-value="onModalElectionChange"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <Label html-for="export-date-from">From date</Label>
                        <Input
                            id="export-date-from"
                            v-model="modalDateFrom"
                            type="date"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="export-date-to">To date</Label>
                        <Input
                            id="export-date-to"
                            v-model="modalDateTo"
                            type="date"
                        />
                    </div>
                </div>

                <div v-if="showStudentFilters" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="space-y-1.5 min-w-0">
                        <Label html-for="export-department">Department</Label>
                        <Select
                            id="export-department"
                            :model-value="modalDepartmentId"
                            :options="departmentFilterOptions"
                            placeholder="All"
                            allow-empty
                            @update:model-value="onDepartmentFilterChange"
                        />
                    </div>
                    <div class="space-y-1.5 min-w-0">
                        <Label html-for="export-year-level">Year level</Label>
                        <Select
                            id="export-year-level"
                            v-model="modalYearLevelId"
                            :options="yearLevelFilterOptions"
                            placeholder="All"
                            allow-empty
                        />
                    </div>
                    <div class="space-y-1.5 min-w-0">
                        <Label html-for="export-course">Course / Section</Label>
                        <Select
                            id="export-course"
                            v-model="modalCourseId"
                            :options="courseFilterOptions"
                            placeholder="All"
                            allow-empty
                        />
                    </div>
                </div>

                <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                    Leave dates empty to include all records for the selected election.
                    <template v-if="showStudentFilters">
                        Use department, year level, and course/section to narrow students who voted.
                    </template>
                    <template v-else>
                        Date filters apply to votes, ballots, and registration timestamps where available.
                    </template>
                </p>

                <p
                    v-if="!checkingFilters && !filterHasData"
                    class="text-xs font-medium"
                    style="color: hsl(0 72% 45%);"
                >
                    No data matches the selected filters. Adjust filters to enable download.
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" :disabled="exporting" @click="closeExportDialog">
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :variant="exportFormat === 'pdf' ? 'destructive' : 'success'"
                        :disabled="!canDownload"
                        :title="!filterHasData ? 'No data for the selected filters' : undefined"
                    >
                        {{ exporting
                            ? (exportFormat === 'pdf' ? 'Preparing PDF...' : 'Preparing Excel...')
                            : checkingFilters
                                ? 'Checking…'
                                : (exportFormat === 'pdf' ? 'Download PDF' : 'Download Excel') }}
                    </Button>
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
