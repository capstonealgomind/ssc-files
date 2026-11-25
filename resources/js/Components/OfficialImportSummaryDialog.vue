<script setup>
import { ref, watch } from 'vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Button from '@/Components/ui/Button.vue';
import Pagination from '@/Components/ui/Pagination.vue';

const props = defineProps(['batch', 'category', 'title', 'total', 'metadata']);
const emit = defineEmits(['close']);
const search = ref('');
const rows = ref(null);
const loading = ref(false);
const error = ref('');
let request;

const fields = {
    student_id: 'Student ID', full_name: 'Full Name', email: 'Email', department: 'Department',
    course: 'Course', year_level: 'Year Level', section: 'Section',
};
const columns = Object.entries(fields);
const labels = { added: 'Added', updated: 'Updated', retained: 'Retained', removed: 'Removed', invalid: 'Invalid', duplicates: 'Duplicates Skipped', unapplied: 'Import rejected' };

const descriptions = {
    processed: 'Shows the student rows read from this upload and the result for each row.',
    added: 'Shows students added to Official Student Records during this import.',
    duplicates: 'Shows repeated Student IDs in the file and rows that matched an existing official record.',
    updated: 'Shows existing records that changed during this import. Expand Compare changes to see the previous and new details.',
    retained: 'Shows existing records classified as unchanged in this import.',
    removed: 'Shows students moved to Removed Student Records because they were missing from this Full Synchronization upload.',
    invalid: 'Shows rows that failed validation and the reason each row was skipped.',
};

async function load(page = 1) {
    request?.abort();
    if (!props.category || !props.batch) return;
    const controller = new AbortController();
    request = controller;
    loading.value = true;
    error.value = '';
    try {
        const response = await window.axios.get('/official-student-records/import-rows', {
            params: { batch: props.batch, category: props.category === 'processed' ? undefined : props.category, search: search.value, page },
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });
        if (!controller.signal.aborted) rows.value = response.data;
    } catch {
        if (!controller.signal.aborted) error.value = 'Could not load import results. Try again.';
    } finally {
        if (!controller.signal.aborted) loading.value = false;
    }
}

watch(() => [props.batch, props.category], () => {
    search.value = '';
    rows.value = null;
    load();
}, { immediate: true });
</script>

<template>
    <Dialog :show="!!category" :title="title" :description="descriptions[category]" size="xl" @close="emit('close')">
        <dl v-if="metadata" class="mb-4 flex flex-wrap items-start gap-x-6 gap-y-3 rounded-lg border bg-muted px-4 py-3">
            <div class="min-w-0 flex-1 basis-60">
                <dt class="text-xs font-medium text-muted-foreground">Import file</dt>
                <dd class="mt-1 text-sm font-medium [overflow-wrap:anywhere]">{{ metadata.filename }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-muted-foreground">Imported at</dt>
                <dd class="mt-1 text-sm tabular-nums">{{ metadata.date }}</dd>
            </div>
        </dl>
        <form class="mb-3 flex flex-wrap items-center gap-2" @submit.prevent="load()">
            <span class="mr-auto text-sm">{{ total }} records</span>
            <input v-model="search" type="search" maxlength="150" aria-label="Search import results" placeholder="Search records" class="rounded border p-2 text-sm" @search="load()" />
            <Button type="submit" variant="outline">Search</Button>
        </form>
        <p v-if="error" role="alert" class="text-sm text-destructive">{{ error }}</p>
        <p v-else-if="loading" role="status" class="text-sm">Loading records…</p>
        <ul v-else-if="rows?.data.length" class="max-h-[50vh] space-y-3 overflow-y-auto rounded-lg" aria-label="Import results">
            <li v-for="row in rows.data" :key="row.id" class="rounded-lg border bg-white p-3 text-sm [overflow-wrap:anywhere] sm:p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p v-if="row.row_number" class="font-semibold">Excel row {{ row.row_number }}</p>
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="row.category === 'invalid' ? 'bg-amber-100 text-amber-900' : 'bg-muted'">
                        {{ labels[row.category] }}
                    </span>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-3">
                    <div v-for="[key, label] in columns" :key="key">
                        <dt class="text-xs text-muted-foreground">{{ label }}</dt>
                        <dd class="mt-0.5 font-medium">{{ row.details.values?.[key] || '—' }}</dd>
                    </div>
                </dl>
                <div v-if="row.details.first_row || row.category === 'removed' || row.details.message || row.details.previous || row.details.first_values"
                    class="mt-3 space-y-2 rounded-md bg-muted px-3 py-2.5">
                    <p v-if="row.details.first_row">First matching row {{ row.details.first_row }}</p>
                    <p v-if="row.category === 'removed'">{{ row.details.removed_at ?? row.created_at }}</p>
                    <p v-if="row.details.message">{{ row.details.message }}</p>
                    <details v-if="row.details.previous || row.details.first_values">
                        <summary class="cursor-pointer underline">{{ row.details.previous ? 'Compare changes' : 'View first row' }}</summary>
                        <template v-for="(value, key) in row.details.previous ?? row.details.first_values" :key="key">
                            <p v-if="key !== 'status' && (!row.details.previous || value !== row.details.values[key])">
                                {{ fields[key] ?? key }} · {{ value }}<template v-if="row.details.previous"> → {{ row.details.values[key] }}</template>
                            </p>
                        </template>
                    </details>
                </div>
            </li>
        </ul>
        <p v-else-if="rows && total && !search" class="text-sm">Row details are unavailable for this import.</p>
        <p v-else class="text-sm">No records found for this category.</p>
        <Pagination v-if="rows && !loading" :current-page="rows.current_page" :last-page="rows.last_page"
            :from="rows.from ?? 0" :to="rows.to ?? 0" :total="rows.total" @change="load" />
        <div class="mt-4 flex justify-end"><Button variant="outline" @click="emit('close')">Close</Button></div>
    </Dialog>
</template>
