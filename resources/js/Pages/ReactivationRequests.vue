<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';
import Dialog from '@/Components/ui/Dialog.vue';

const props = defineProps({
    requests: { type: Array, default: () => [] },
    statusFilter: { type: String, default: 'pending' },
    counts: {
        type: Object,
        default: () => ({ pending: 0, approved: 0, rejected: 0, all: 0 }),
    },
});

const selected = ref(null);
const showProcess = ref(false);

const processForm = useForm({
    action: 'approve',
    duration_years: 1,
    admin_notes: '',
});

const tabs = computed(() => [
    { key: 'pending', label: 'Pending', count: props.counts.pending },
    { key: 'approved', label: 'Approved', count: props.counts.approved },
    { key: 'rejected', label: 'Rejected', count: props.counts.rejected },
    { key: 'all', label: 'All', count: props.counts.all },
]);

function setFilter(status) {
    router.get('/reactivation-requests', { status }, { preserveState: true, replace: true });
}

function openProcess(item) {
    selected.value = item;
    processForm.reset();
    processForm.clearErrors();
    processForm.action = 'approve';
    processForm.duration_years = Math.max(1, item.voter?.remaining_years || 1);
    processForm.admin_notes = '';
    showProcess.value = true;
}

function closeProcess() {
    showProcess.value = false;
    selected.value = null;
    processForm.reset();
    processForm.clearErrors();
}

function submitProcess() {
    if (!selected.value) return;

    processForm.post(`/reactivation-requests/${selected.value.id}/process`, {
        preserveScroll: true,
        onSuccess: () => closeProcess(),
    });
}

function statusStyle(status) {
    if (status === 'approved') return { color: 'hsl(142 71% 25%)', background: 'hsl(142 76% 94%)' };
    if (status === 'pending') return { color: 'hsl(38 62% 30%)', background: 'hsl(38 92% 94%)' };
    if (status === 'rejected') return { color: 'hsl(0 72% 35%)', background: 'hsl(0 86% 94%)' };
    return { color: 'hsl(240 3.8% 46.1%)', background: 'hsl(240 4.8% 95.9%)' };
}
</script>

<template>
    <AppLayout>
        <Head title="Reactivation Requests" />

        <template #header>
            <h2 class="text-xl font-semibold" style="color: hsl(240 10% 3.9%)">
                Reactivation Requests
            </h2>
        </template>

        <div class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm transition-colors"
                    :class="
                        statusFilter === tab.key
                            ? 'border-[var(--sscevs-blue)] bg-[var(--sscevs-blue-light)] text-[var(--sscevs-blue-dark)]'
                            : 'border-[var(--sscevs-border)] bg-white text-[var(--sscevs-muted)] hover:bg-gray-50'
                    "
                    @click="setFilter(tab.key)"
                >
                    {{ tab.label }}
                    <span class="rounded-full bg-white/80 px-1.5 text-xs">{{ tab.count }}</span>
                </button>
            </div>

            <div class="overflow-hidden rounded-lg border border-[var(--sscevs-border)] bg-white">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-[var(--sscevs-border)] bg-gray-50 text-left text-[var(--sscevs-muted)]">
                            <tr>
                                <th class="px-4 py-3 font-medium">Number</th>
                                <th class="px-4 py-3 font-medium">Voter</th>
                                <th class="px-4 py-3 font-medium">Year stopped</th>
                                <th class="px-4 py-3 font-medium">Course math</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Submitted</th>
                                <th class="px-4 py-3 font-medium text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!requests.length">
                                <td colspan="7" class="px-4 py-10 text-center text-[var(--sscevs-muted)]">
                                    No reactivation requests in this filter.
                                </td>
                            </tr>
                            <tr
                                v-for="item in requests"
                                :key="item.id"
                                class="border-b border-[var(--sscevs-border)] last:border-0"
                            >
                                <td class="px-4 py-3 font-medium whitespace-nowrap">
                                    {{ item.reactivation_number }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ item.full_name }}</div>
                                    <div class="text-xs text-[var(--sscevs-muted)]">{{ item.voter_id_number }}</div>
                                </td>
                                <td class="px-4 py-3">{{ item.year_stopped }}</td>
                                <td class="px-4 py-3 text-xs text-[var(--sscevs-muted)]">
                                    <template v-if="item.voter">
                                        {{ item.voter.course ?? '—' }}
                                        ({{ item.voter.course_duration ?? 0 }} − {{ item.voter.year_level_order ?? 0 }}
                                        = {{ item.voter.remaining_years }} yrs left)
                                    </template>
                                    <template v-else>—</template>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium capitalize"
                                        :style="statusStyle(item.status)"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-[var(--sscevs-muted)]">
                                    {{ item.submitted_at }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button
                                        v-if="item.status === 'pending'"
                                        type="button"
                                        size="sm"
                                        @click="openProcess(item)"
                                    >
                                        Process
                                    </Button>
                                    <span v-else class="text-xs text-[var(--sscevs-muted)]">
                                        {{ item.processed_by ? `By ${item.processed_by}` : 'Processed' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Dialog
            :show="showProcess"
            title="Process reactivation"
            :description="selected ? `${selected.reactivation_number} · ${selected.full_name}` : ''"
            wide
            @close="closeProcess"
        >
            <form v-if="selected" class="space-y-4" @submit.prevent="submitProcess">
                <div class="rounded-md border border-[var(--sscevs-border)] bg-gray-50 p-3 text-sm space-y-1">
                    <p><span class="text-[var(--sscevs-muted)]">Reason:</span> {{ selected.reason }}</p>
                    <p><span class="text-[var(--sscevs-muted)]">Year stopped:</span> {{ selected.year_stopped }}</p>
                    <p v-if="selected.voter">
                        <span class="text-[var(--sscevs-muted)]">On file:</span>
                        {{ selected.voter.course }} · {{ selected.voter.year_level }}
                        · remaining {{ selected.voter.remaining_years }} yr(s)
                        (duration − year level)
                    </p>
                    <p v-if="selected.voter?.account_expires_at">
                        <span class="text-[var(--sscevs-muted)]">Expires:</span>
                        {{ selected.voter.account_expires_at }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label>Decision</Label>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            :variant="processForm.action === 'approve' ? 'navy' : 'outline'"
                            @click="processForm.action = 'approve'"
                        >
                            Approve &amp; add duration
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="processForm.action === 'reject' ? 'destructive' : 'outline'"
                            @click="processForm.action = 'reject'"
                        >
                            Reject
                        </Button>
                    </div>
                </div>

                <div v-if="processForm.action === 'approve'" class="space-y-2">
                    <Label for="duration_years">Years to add to account</Label>
                    <Input
                        id="duration_years"
                        v-model="processForm.duration_years"
                        type="number"
                        min="1"
                        max="10"
                    />
                    <p class="text-xs text-[var(--sscevs-muted)]">
                        Extends account_expires_at by this many years so the returning student can log in again.
                    </p>
                    <InputError :message="processForm.errors.duration_years" />
                </div>

                <div class="space-y-2">
                    <Label for="admin_notes">Admin notes (optional)</Label>
                    <textarea
                        id="admin_notes"
                        v-model="processForm.admin_notes"
                        rows="3"
                        class="flex w-full rounded-md border border-[var(--sscevs-border)] bg-white px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--sscevs-blue)]"
                    />
                    <InputError :message="processForm.errors.admin_notes" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" @click="closeProcess">Cancel</Button>
                    <Button type="submit" :disabled="processForm.processing">
                        {{ processForm.processing ? 'Saving...' : 'Confirm' }}
                    </Button>
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
