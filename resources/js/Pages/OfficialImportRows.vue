<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';

const props = defineProps(['duplicates', 'filters', 'rows']);
const base = '/official-student-records';
const title = props.duplicates ? 'Duplicates Skipped' : 'Import Results';
const filters = reactive({ ...props.filters });
const detail = ref(null);
const columns = { student_id: 'Student ID', full_name: 'Full Name', email: 'Email', department: 'Department', course: 'Course', year_level: 'Year Level', section: 'Section' };
function search() {
    router.get(base + '/import-rows',
        Object.fromEntries(Object.entries(filters).filter(([, value]) => value)), { preserveState: true });
}
</script>

<template>
    <AppLayout>
        <Head :title="title" />
        <template #header><h1 class="font-semibold">{{ title }}</h1></template>
        <div class="space-y-4">
            <Link :href="base" :data="{ import: filters.batch }" class="text-sm underline">Back to Official Student Records</Link>
            <form class="flex flex-wrap gap-2" @submit.prevent="search">
                <input v-model="filters.search" aria-label="Search rows" placeholder="Search rows" class="rounded border p-2" />
                <input v-model="filters.date" aria-label="Import date" type="date" class="rounded border p-2" />
                <input v-model="filters.department" aria-label="Department" placeholder="Department" class="rounded border p-2" />
                <input v-model="filters.course" aria-label="Course" placeholder="Course" class="rounded border p-2" />
                <Button type="submit">Apply filters</Button>
            </form>
            <p class="text-sm">{{ rows.total }} {{ duplicates ? 'duplicate rows' : 'results' }}</p>
            <div class="admin-table-scroll rounded-xl border bg-white">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b">
                        <th class="p-3">Excel Row Number</th>
                        <th v-for="label in columns" :key="label" class="p-3">{{ label }}</th>
                        <th class="p-3">{{ duplicates ? 'Duplicate Reason' : 'Result' }}</th>
                        <th class="p-3">Import Date</th><th class="p-3">Uploaded Filename</th><th class="p-3">Actions</th>
                    </tr></thead>
                    <tbody>
                        <tr v-for="row in rows.data" :key="row.id" class="border-b">
                            <td class="p-3">{{ row.row_number ?? '—' }}</td>
                            <td v-for="(_, key) in columns" :key="key" class="p-3">{{ row.details.values?.[key] ?? '—' }}</td>
                            <td class="p-3">{{ row.details.message ?? row.category }}</td>
                            <td class="p-3">{{ row.created_at }}</td><td class="p-3">{{ row.filename }}</td>
                            <td class="p-3">
                                <Button size="sm" variant="outline" @click="detail = row">View details</Button>
                                <Button v-if="duplicates && !row.reviewed_at" size="sm" variant="outline" @click="router.post(base + '/duplicate-rows/' + row.id + '/review', {}, { preserveScroll: true })">Mark reviewed</Button>
                                <span v-if="row.reviewed_at" class="block">Reviewed</span>
                            </td>
                        </tr>
                        <tr v-if="!rows.total"><td colspan="11" class="p-4 text-center">No saved rows match these filters.</td></tr>
                    </tbody>
                </table>
            </div>
            <nav class="flex gap-4 text-sm">
                <Link v-if="rows.prev_page_url" :href="rows.prev_page_url" class="underline">Previous</Link>
                <Link v-if="rows.next_page_url" :href="rows.next_page_url" class="underline">Next</Link>
            </nav>
            <Dialog :show="!!detail" title="Import row details" @close="detail = null">
                <p class="mb-2 text-sm">{{ detail?.details.message }}</p>
                <dl class="space-y-2 text-sm">
                    <div v-for="(value, key) in detail?.details.values" :key="key">
                        <dt class="font-semibold">{{ columns[key] ?? key }}</dt><dd>{{ value }}</dd>
                    </div>
                </dl>
                <div v-if="detail?.details.first_values || detail?.details.previous" class="mt-4 border-t pt-3 text-sm">
                    <h3 class="font-semibold">{{ detail.details.first_values ? 'First valid occurrence · Row ' + detail.details.first_row : 'Before this import' }}</h3>
                    <p v-for="(value, key) in (detail.details.first_values ?? detail.details.previous)" :key="key">{{ columns[key] ?? key }} · {{ value }}</p>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>
