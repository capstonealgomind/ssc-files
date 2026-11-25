<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import OfficialImportSummaryDialog from '@/Components/OfficialImportSummaryDialog.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Input from '@/Components/ui/Input.vue';

const props = defineProps(['records', 'headers', 'filters', 'options', 'imports', 'summary', 'preview', 'removed', 'sort', 'latestFullBatch', 'batch', 'importMetadata', 'unappliedTotal']);
const activeUrl = '/official-student-records';
const removedUrl = '/removed-student-records';
const url = props.removed ? removedUrl : activeUrl;
const title = props.removed ? 'Removed Student Records' : 'Official Student Records';
const previewOpen = ref(true);
const selectedCategory = ref(null);
const detail = ref(null);
const restoring = ref(null);
const emailEdit = ref(null);
const deleting = ref(null);
const deleteConfirmText = ref('');
const selectedIds = ref([]);
const pendingBulkIds = ref([]);
const selectedSet = computed(() => new Set(selectedIds.value));
const pageIds = computed(() => props.records.data.map((record) => record.id));
const selectableIds = computed(() => (props.selectionIds ?? []).map(Number));
const selectedCount = computed(() => selectedIds.value.length);
const allPageSelected = computed(() => pageIds.value.length > 0 && pageIds.value.every((id) => selectedSet.value.has(id)));
const allFilteredSelected = computed(() => selectableIds.value.length > 0 && selectableIds.value.every((id) => selectedSet.value.has(id)));
watch(selectableIds, (ids) => {
    const allowed = new Set(ids);
    selectedIds.value = selectedIds.value.filter((id) => allowed.has(id));
});
const absent = computed(() => restoring.value && props.latestFullBatch != null
    && Number(restoring.value.last_full_batch_id) !== Number(props.latestFullBatch));
const restore = useForm({ confirmed: true, acknowledged: false });
const emailForm = useForm({ email: '' });
const deleteForm = useForm({ confirmation: '', ids: [] });
const confirm = useForm({ token: '', confirmed: true });
const counts = { added: 'Added', updated: 'Updated', retained: 'Retained', removed: 'Removed' };

function countFor(result, key) {
    return (result?.[key] ?? 0) + (key === 'updated' ? (result?.reactivated ?? 0) : 0);
}

function confirmSync() {
    confirm.token = props.preview.token;
    confirm.post(activeUrl + '/confirm');
}

function startRestore(record) {
    restore.reset();
    restore.clearErrors();
    restoring.value = record;
}

function restoreStudent() {
    restore.post(removedUrl + '/' + restoring.value.id + '/restore', {
        preserveScroll: true,
        onSuccess: () => { restoring.value = null; },
    });
}
const filters = reactive({ ...props.filters, sort: props.sort });
const exportHref = computed(() => {
    const params = new URLSearchParams(Object.entries(filters)
        .filter(([key, value]) => key !== 'sort' && value !== undefined && value !== null && String(value).trim() !== '')
        .map(([key, value]) => [key, String(value)]));
    return `${url}/export${params.size ? `?${params.toString()}` : ''}`;
});
const upload = useForm({ file: null, mode: 'add_update' });
const fileInput = ref(null);
const summaryLabels = {
    processed: 'Processed', added: 'Added', duplicates: 'Duplicates Skipped',
    ...counts, invalid: 'Invalid',
};

function search() {
    router.get(url, Object.fromEntries(Object.entries(filters).filter(([, value]) => value)), {
        preserveScroll: true,
        preserveState: true,
    });
}

function changeFilter(field) {
    if (field === 'department') delete filters.course;
    if (field === 'department' || field === 'course') delete filters.year_level;
    if (field !== 'section') delete filters.section;
    search();
}

function importRecords() {
    previewOpen.value = true;
    confirm.clearErrors();
    upload.post(url, {
        onSuccess: () => {
            upload.reset();
            if (fileInput.value) fileInput.value.value = '';
            Object.keys(filters).forEach((key) => delete filters[key]);
        },
    });
}

function startEmailEdit(record) {
    emailForm.clearErrors();
    emailForm.email = record.email ?? '';
    emailEdit.value = record;
}

function saveEmail() {
    if (!emailEdit.value) return;
    emailForm.patch(`${activeUrl}/${emailEdit.value.id}/email`, {
        preserveScroll: true,
        onSuccess: () => { emailEdit.value = null; },
    });
}

function startDelete(record) {
    deleteForm.reset();
    deleteForm.clearErrors();
    deleteConfirmText.value = '';
    pendingBulkIds.value = [];
    deleting.value = record;
}

function toggleSelected(id) {
    selectedIds.value = selectedSet.value.has(id)
        ? selectedIds.value.filter((selected) => selected !== id)
        : [...selectedIds.value, id];
}

function togglePageSelection() {
    selectedIds.value = allPageSelected.value
        ? selectedIds.value.filter((id) => !pageIds.value.includes(id))
        : [...new Set([...selectedIds.value, ...pageIds.value])];
}

function selectAllFiltered() {
    selectedIds.value = [...selectableIds.value];
}

function clearSelection() {
    selectedIds.value = [];
}

function startBulkDelete() {
    if (props.removed || selectedIds.value.length === 0) return;
    deleteForm.reset();
    deleteForm.clearErrors();
    deleteConfirmText.value = '';
    deleting.value = null;
    pendingBulkIds.value = [...selectedIds.value];
}

function deleteRecord() {
    if ((!deleting.value && !pendingBulkIds.value.length) || deleteConfirmText.value !== 'DELETE') return;
    deleteForm.confirmation = deleteConfirmText.value;
    if (pendingBulkIds.value.length) {
        deleteForm.ids = [...pendingBulkIds.value];
        deleteForm.delete(`${activeUrl}/bulk`, {
            preserveScroll: true,
            onSuccess: () => { clearSelection(); pendingBulkIds.value = []; },
        });
        return;
    }
    deleteForm.delete(`${activeUrl}/${deleting.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { deleting.value = null; },
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="title" />
        <template #header>
            <h1 class="text-base font-semibold">{{ title }}</h1>
        </template>

        <div class="space-y-4">
            <p v-if="$page.props.flash?.success" role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ $page.props.flash.success }}</p>
            <Link v-if="removed" :href="activeUrl" class="text-sm font-medium underline">Back to Official Student Records</Link>
            <section v-else class="rounded-xl border bg-white p-4">
                <form class="flex flex-wrap items-center gap-3" @submit.prevent="importRecords">
                    <label for="official-file" class="sr-only">Official student records file</label>
                    <input id="official-file" ref="fileInput" type="file" accept=".xlsx,.xls,.csv" class="max-w-full text-sm"
                        @change="upload.file = $event.target.files[0] ?? null" />
                    <select v-model="upload.mode" aria-label="Import mode" class="rounded-md border px-2 py-2 text-sm">
                        <option value="add_update">Add/Update Records</option>
                        <option value="full">Full Synchronization</option>
                    </select>
                    <Button type="submit" :disabled="!upload.file || upload.processing">
                        {{ upload.processing ? 'Importing…' : 'Import Excel/CSV' }}
                    </Button>
                </form>
                <div class="mt-3 space-y-1 text-xs leading-relaxed text-muted-foreground">
                    <p><strong>Add/Update Records</strong> adds new students and updates existing records. Students missing from the file stay active.</p>
                    <p><strong>Full Synchronization</strong> uses the file as the complete list. After confirmation, students missing from the file move to Removed Student Records.</p>
                    <p>Include an <strong>Email</strong> column in the Excel/CSV file. During registration, the student must enter this same email address; a mismatch will be rejected.</p>
                </div>
                <p v-if="upload.errors.file" role="alert" class="mt-2 text-sm text-destructive">{{ upload.errors.file }}</p>
            </section>

            <p v-if="confirm.errors.file || $page.props.errors?.file" role="alert" class="text-sm text-destructive">
                {{ confirm.errors.file || $page.props.errors.file }}
            </p>
            <Dialog :show="!!preview && previewOpen" title="Confirm Full Synchronization" @close="previewOpen = false">
                <p class="mb-3 text-sm">Confirm that {{ preview?.filename }} is the complete authoritative student list. Every active student missing from this file will move to Removed Student Records.</p>
                <dl class="mb-4 space-y-1 text-sm">
                    <div v-for="(label, key) in counts" :key="key" class="flex justify-between">
                        <dt>{{ label }}</dt><dd>{{ countFor(preview?.counts, key) }}</dd>
                    </div>
                </dl>
                <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="previewOpen = false">Cancel</Button>
                    <Button variant="destructive" :disabled="confirm.processing" @click="confirmSync">Confirm Full Synchronization</Button>
                </div>
            </Dialog>

            <section v-if="summary" class="space-y-4 rounded-xl border bg-white p-4 shadow-sm sm:p-5" aria-live="polite">
                <div class="flex flex-col gap-4 border-b pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold tracking-tight">Import summary</h2>
                        <div v-if="importMetadata" class="mt-1 text-sm text-muted-foreground">
                            <p class="break-all">{{ importMetadata.filename }}</p>
                            <p class="mt-0.5 tabular-nums">{{ importMetadata.date }}</p>
                        </div>
                    </div>
                    <div class="shrink-0 rounded-lg border bg-muted px-4 py-2">
                        <p class="text-xs font-medium text-muted-foreground">Total Students in Table</p>
                        <p class="text-2xl font-semibold tabular-nums">{{ records.total.toLocaleString() }}</p>
                    </div>
                </div>
                <p v-if="summary.import_status === 'rejected'" role="status" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-900">
                    Import rejected. No student records were changed.
                    <span v-if="unappliedTotal">{{ unappliedTotal.toLocaleString() }} rows were left unapplied.</span>
                    <span v-if="summary.invalid"> Open Invalid to see which rows failed validation.</span>
                </p>
                <div class="grid grid-cols-2 gap-2 text-sm lg:grid-cols-4 xl:grid-cols-7">
                    <button v-for="(label, key) in summaryLabels" :key="key" type="button"
                        class="flex min-w-0 flex-col justify-between gap-2 rounded-lg border px-3 py-2.5 text-left transition-colors hover:bg-muted focus-visible:outline-2 focus-visible:outline-offset-2"
                        :class="key === 'invalid' && summary.invalid ? 'border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100' : ''"
                        @click="selectedCategory = key">
                        <span class="text-xs font-medium">{{ label }}</span>
                        <span class="text-xl font-semibold tabular-nums">{{ countFor(summary, key) }}</span>
                    </button>
                </div>
                <details v-if="summary.issues?.length" class="rounded-lg border">
                    <summary class="cursor-pointer rounded-lg bg-muted px-3 py-2.5 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2">Review import issues</summary>
                    <ul class="max-h-80 divide-y overflow-auto px-3 text-sm">
                        <li v-for="issue in summary.issues" :key="issue.row" class="break-words py-2.5">
                            <strong>Row {{ issue.row }}</strong> · {{ issue.message }}
                            <div v-for="(difference, field) in issue.differences" :key="field">
                                {{ field }} · Existing: {{ difference.existing }} · Uploaded: {{ difference.uploaded }}
                            </div>
                        </li>
                    </ul>
                </details>
            </section>

            <p v-if="!removed && !summary" class="text-sm">No student imports yet.</p>
            <OfficialImportSummaryDialog :batch="batch" :metadata="importMetadata" :category="selectedCategory"
                :title="summaryLabels[selectedCategory]" :total="countFor(summary, selectedCategory)"
                @close="selectedCategory = null" />

            <form class="flex flex-wrap items-center gap-2" @submit.prevent="search">
                <Input v-model="filters.search" type="search" placeholder="Search student records" aria-label="Search student records" @change="search" />
                <select v-for="(values, field) in options" :key="field" v-model="filters[field]"
                    @change="changeFilter(field)" :aria-label="headers[field]" class="rounded-md border bg-white px-2 py-2 text-sm">
                    <option :value="undefined">{{ headers[field] }}</option>
                    <option v-for="value in values" :key="value" :value="value">{{ field === 'section' ? 'Section ' + value : value }}</option>
                </select>
                <select v-if="removed" v-model="filters.sort" aria-label="Sort removed records" class="rounded-md border px-2 py-2 text-sm" @change="search">
                    <option value="date">Most recently removed</option>
                    <option value="name">Name A–Z</option>
                </select>
                <a :href="exportHref" class="inline-flex min-h-9 items-center rounded-md border bg-white px-3 text-sm font-medium shadow-sm transition-colors hover:bg-muted">
                    Export Excel
                </a>
            </form>

            <section v-if="!removed" class="flex flex-wrap items-center gap-2 rounded-lg border bg-white px-3 py-2">
                <Button v-if="selectedCount" size="sm" variant="destructive" @click="startBulkDelete">Delete ({{ selectedCount }})</Button>
                <button v-if="selectableIds.length && !allFilteredSelected" type="button" class="text-xs font-medium text-blue-700 underline" @click="selectAllFiltered">
                    Select all {{ selectableIds.length }}
                </button>
                <button v-if="selectedCount" type="button" class="text-xs text-muted-foreground underline" @click="clearSelection">Clear selection</button>
                <span class="ml-auto text-xs text-muted-foreground">{{ selectedCount ? `${selectedCount} selected` : `${records.total} records` }}</span>
            </section>

            <section class="overflow-hidden rounded-xl border bg-white">
                <div class="admin-table-scroll">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-b bg-slate-200 text-slate-900">
                            <tr>
                                <th v-if="!removed" scope="col" class="w-10 px-3 py-3">
                                    <input type="checkbox" aria-label="Select all on this page" title="Select all on this page"
                                        :checked="allPageSelected" :disabled="!pageIds.length" class="h-4 w-4 accent-blue-600" @change="togglePageSelection" />
                                </th>
                                <th v-for="(label, field) in headers" :key="field" scope="col"
                                    :class="{ 'text-center': field === 'section' }"
                                    class="whitespace-nowrap px-4 py-3 text-xs font-semibold">{{ label }}</th>
                                <th scope="col" class="px-4 py-3 text-xs font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="record in records.data" :key="record.id"
                                class="border-b border-slate-200 even:bg-slate-50 last:border-b-0 hover:bg-blue-50">
                                <td v-if="!removed" class="w-10 px-3 py-3">
                                    <input type="checkbox" :aria-label="`Select ${record.full_name}`" :checked="selectedSet.has(record.id)"
                                        class="h-4 w-4 accent-blue-600" @change="toggleSelected(record.id)" />
                                </td>
                                <td v-for="(_, field) in headers" :key="field"
                                    :class="{
                                        'whitespace-nowrap font-mono text-xs text-slate-600': field === 'student_id',
                                        'min-w-40 font-medium text-slate-900': field === 'full_name',
                                        'max-w-56 break-all text-slate-700': field === 'email',
                                        'min-w-44 text-slate-700': field === 'department' || field === 'course',
                                        'whitespace-nowrap': field === 'year_level',
                                        'text-center': field === 'section',
                                    }"
                                    class="px-4 py-3 align-middle leading-snug">
                                    <span v-if="field === 'section'" class="inline-block min-w-7 rounded-md bg-slate-100 px-1.5 py-1 text-xs font-semibold text-slate-700">{{ record[field] }}</span>
                                    <span v-else-if="field === 'email' && !record[field]" class="text-amber-700">Missing email</span>
                                    <template v-else>{{ record[field] }}</template>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <template v-if="!removed">
                                        <Button size="sm" variant="outline" @click="startEmailEdit(record)">Edit Email</Button>
                                        <Button size="sm" variant="destructive" class="ml-2" @click="startDelete(record)">Delete</Button>
                                    </template>
                                    <template v-else>
                                        <Button size="sm" variant="outline" @click="detail = record">View details</Button>
                                        <Button size="sm" variant="outline" class="ml-2" @click="startRestore(record)">Restore Student</Button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="!records.data.length"><td :colspan="Object.keys(headers).length + (removed ? 1 : 2)" class="p-4 text-center">No student records found.</td></tr>
                        </tbody>
                    </table>
                </div>
                <nav class="flex flex-wrap items-center justify-between gap-3 border-t bg-slate-50 px-4 py-2.5 text-sm" aria-label="Student record pages">
                    <span class="tabular-nums text-slate-600">
                        <strong class="font-semibold text-slate-900">{{ records.total.toLocaleString() }}</strong>
                        {{ records.total === 1 ? 'record' : 'records' }}
                        <span aria-hidden="true" class="mx-1 text-slate-400">·</span>
                        Page {{ records.current_page }} of {{ records.last_page }}
                    </span>
                    <div class="flex items-center gap-2">
                        <component v-for="control in [
                            { label: 'Previous', url: records.prev_page_url, arrow: '←' },
                            { label: 'Next', url: records.next_page_url, arrow: '→' },
                        ]" :key="control.label" :is="control.url ? Link : 'span'" :href="control.url || undefined"
                            :aria-disabled="control.url ? undefined : true"
                            class="inline-flex min-h-9 items-center gap-1.5 rounded-md border px-3 font-medium"
                            :class="control.url ? 'bg-white text-slate-700 shadow-sm transition-colors hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-slate-500' : 'bg-slate-100 text-slate-500'">
                            <span v-if="control.label === 'Previous'" aria-hidden="true">{{ control.arrow }}</span>
                            {{ control.label }}
                            <span v-if="control.label === 'Next'" aria-hidden="true">{{ control.arrow }}</span>
                        </component>
                    </div>
                </nav>
            </section>

            <Dialog :show="!!detail" :title="removed ? 'Removed student details' : 'Student details'" @close="detail = null">
                <dl class="space-y-2 text-sm">
                    <div v-for="(label, field) in headers" :key="field"><dt class="font-semibold">{{ label }}</dt><dd>{{ detail?.[field] }}</dd></div>
                </dl>
                <section v-if="!removed" class="mt-5 border-t pt-4">
                    <h3 class="mb-2 text-sm font-semibold">Email update history</h3>
                    <p v-if="!detail?.email_updates?.length" class="text-sm text-muted-foreground">No manual email updates recorded.</p>
                    <ul v-else class="max-h-56 divide-y overflow-auto rounded-md border text-sm">
                        <li v-for="(change, index) in detail.email_updates" :key="`${change.record_id}-${change.created_at}-${index}`" class="space-y-1 p-3">
                            <p><span class="font-medium">Old:</span> {{ change.old_email || 'None' }}</p>
                            <p><span class="font-medium">New:</span> {{ change.new_email }}</p>
                            <p class="text-xs text-muted-foreground">Updated by {{ change.administrator_name || 'Administrator' }} · {{ change.created_at }}</p>
                        </li>
                    </ul>
                </section>
            </Dialog>
            <Dialog :show="!!emailEdit" title="Update official student email" :description="emailEdit?.full_name" @close="emailEdit = null">
                <form class="space-y-4" @submit.prevent="saveEmail">
                    <p class="text-sm text-muted-foreground">Student ID: <span class="font-mono">{{ emailEdit?.student_id }}</span></p>
                    <div class="space-y-1.5">
                        <label for="official-record-email" class="text-sm font-medium">Email address</label>
                        <Input id="official-record-email" v-model="emailForm.email" type="email" autocomplete="email" required :error="!!emailForm.errors.email" />
                        <p v-if="emailForm.errors.email" role="alert" class="text-sm text-destructive">{{ emailForm.errors.email }}</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="outline" @click="emailEdit = null">Cancel</Button>
                        <Button type="submit" :disabled="emailForm.processing || !emailForm.email">{{ emailForm.processing ? 'Saving...' : 'Save email' }}</Button>
                    </div>
                </form>
            </Dialog>
            <Dialog :show="!!deleting || pendingBulkIds.length > 0" :title="pendingBulkIds.length ? `Remove ${pendingBulkIds.length} Official Student Records` : 'Remove Official Student Record'" :description="deleting?.full_name" @close="deleting = null; pendingBulkIds = []">
                <form class="space-y-4" @submit.prevent="deleteRecord">
                    <p class="text-sm">This moves {{ pendingBulkIds.length ? `${pendingBulkIds.length} selected records` : 'the record' }} to Removed Student Records. You can restore them later. Type <strong>DELETE</strong> to confirm.</p>
                    <div class="space-y-1.5">
                        <label for="delete-official-record" class="text-sm font-medium">Confirmation</label>
                        <Input id="delete-official-record" v-model="deleteConfirmText" autocomplete="off" placeholder="Type DELETE" :error="!!deleteForm.errors.confirmation" />
                        <p v-if="deleteForm.errors.confirmation" role="alert" class="text-sm text-destructive">{{ deleteForm.errors.confirmation }}</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="outline" @click="deleting = null">Cancel</Button>
                        <Button type="submit" variant="destructive" :disabled="deleteForm.processing || deleteConfirmText !== 'DELETE'">
                            {{ deleteForm.processing ? 'Removing...' : 'Delete Record' }}
                        </Button>
                    </div>
                </form>
            </Dialog>
            <Dialog :show="!!restoring" title="Restore Student" @close="restoring = null">
                <p class="text-sm">Return {{ restoring?.full_name }} to Official Student Records?</p>
                <label v-if="absent" class="mt-3 flex gap-2 text-sm">
                    <input v-model="restore.acknowledged" type="checkbox" />
                    I confirm restoration even though this student is absent from the latest authoritative list.
                </label>
                <p v-if="restore.errors.restore" role="alert" class="text-sm text-destructive">{{ restore.errors.restore }}</p>
                <div class="mt-4 flex justify-end gap-2">
                    <Button variant="outline" @click="restoring = null">Cancel</Button>
                    <Button :disabled="restore.processing || (absent && !restore.acknowledged)" @click="restoreStudent">Restore Student</Button>
                </div>
            </Dialog>

            <section v-if="!removed && imports.length" class="space-y-2 rounded-xl border bg-white p-4">
                <h2 class="pb-2 font-semibold">Recent import activity</h2>
                <ul class="max-h-96 divide-y overflow-y-auto rounded-lg border text-sm">
                    <li v-for="entry in imports" :key="entry.id">
                        <Link :href="url" :data="{ import: entry.id }"
                            :aria-current="Number(batch) === entry.id ? 'true' : undefined"
                            :class="Number(batch) === entry.id ? 'bg-blue-50' : 'hover:bg-muted'"
                            class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset">
                            <div class="min-w-0">
                                <p class="break-words font-medium">{{ entry.filename }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    <time :datetime="entry.created_at.replace(' ', 'T')">{{ entry.created_at }}</time>
                                    · Administrator #{{ entry.administrator_id }}
                                </p>
                            </div>
                            <span class="shrink-0 text-xs font-medium text-blue-700">
                                {{ Number(batch) === entry.id ? 'Selected summary' : 'View summary →' }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
