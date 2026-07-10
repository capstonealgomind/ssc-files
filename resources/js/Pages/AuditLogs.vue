<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/ui/Input.vue';

const props = defineProps({
    logs: { type: Array, default: () => [] },
});

const search = ref('');

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.logs;

    return props.logs.filter((log) =>
        [log.voter_name, log.voter_email, log.voter_id_number, log.election, log.receipt_number, log.status]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(q)),
    );
});

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
</script>

<template>
    <AppLayout>
        <Head title="Audit Logs" />
        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Audit Logs</h1>
        </template>

        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                    Ballot submission history for election auditing.
                </p>
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search voter, election, receipt…"
                    class="sm:w-72"
                />
            </div>

            <div
                class="rounded-xl border overflow-hidden"
                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);">
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Voter</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Election</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Status</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Receipt</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Queued</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Processed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filtered.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-sm" style="color: hsl(240 3.8% 46.1%);">
                                    No audit log entries found.
                                </td>
                            </tr>
                            <tr
                                v-for="log in filtered"
                                :key="log.id"
                                class="border-b transition-colors hover:bg-gray-50"
                                style="border-color: hsl(240 5.9% 90%);"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <p class="font-medium" style="color: hsl(240 10% 3.9%);">{{ log.voter_name || '—' }}</p>
                                    <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                        {{ log.voter_id_number || log.voter_email || '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                    {{ log.election || '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded text-xs font-semibold capitalize"
                                        :style="{ background: statusStyle(log.status).bg, color: statusStyle(log.status).color }"
                                    >
                                        {{ log.status }}
                                    </span>
                                    <p
                                        v-if="log.error_message"
                                        class="text-xs mt-1 max-w-[14rem]"
                                        style="color: hsl(0 62% 35%);"
                                    >
                                        {{ log.error_message }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 align-middle font-mono text-xs" style="color: hsl(240 10% 3.9%);">
                                    {{ log.receipt_number || '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                    {{ log.queued_at || '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                    {{ log.processed_at || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
