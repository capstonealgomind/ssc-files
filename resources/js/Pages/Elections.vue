<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Sheet from '@/Components/ui/Sheet.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import InputError from '@/Components/ui/InputError.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    elections: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
});

const { error: toastError } = useToast();

const showCreateSheet = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const editingElection = ref(null);
const deletingElection = ref(null);

const defaultFormState = {
    title: '',
    description: '',
    event_starts_at: '',
    event_ends_at: '',
    voting_starts_at: '',
    voting_ends_at: '',
    status: 'draft',
};

const createForm = useForm({ ...defaultFormState });
const editForm = useForm({ ...defaultFormState });

const statusStyles = {
    draft: { backgroundColor: 'hsl(240 4.8% 95.9%)', color: 'hsl(240 5.9% 10%)' },
    scheduled: { backgroundColor: 'hsl(221 83% 94%)', color: 'hsl(221 83% 35%)' },
    active: { backgroundColor: 'hsl(142 76% 94%)', color: 'hsl(142 71% 29%)' },
    closed: { backgroundColor: 'hsl(240 5.9% 10%)', color: 'hsl(0 0% 98%)' },
};

function statusStyle(status) {
    return statusStyles[status] || statusStyles.draft;
}

function fillForm(form, election) {
    form.title = election.title;
    form.description = election.description || '';
    form.event_starts_at = election.event_starts_at || '';
    form.event_ends_at = election.event_ends_at || '';
    form.voting_starts_at = election.voting_starts_at || '';
    form.voting_ends_at = election.voting_ends_at || '';
    form.status = election.status || 'draft';
    form.clearErrors();
}

function openCreateSheet() {
    createForm.reset();
    createForm.status = 'draft';
    createForm.clearErrors();
    showCreateSheet.value = true;
}

function closeCreateSheet() {
    showCreateSheet.value = false;
    createForm.reset();
    createForm.clearErrors();
}

function openEditDialog(election) {
    editingElection.value = election;
    fillForm(editForm, election);
    showEditDialog.value = true;
}

function closeEditDialog() {
    showEditDialog.value = false;
    editingElection.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function openDeleteDialog(election) {
    deletingElection.value = election;
    showDeleteDialog.value = true;
}

function closeDeleteDialog() {
    showDeleteDialog.value = false;
    deletingElection.value = null;
}

function handleError(form) {
    toastError(
        'Action failed',
        Object.values(form.errors)[0] || 'Please check the form and try again.',
    );
}

function submitCreate() {
    createForm.post('/elections', {
        preserveScroll: true,
        onSuccess: () => closeCreateSheet(),
        onError: () => handleError(createForm),
    });
}

function submitEdit() {
    editForm.put(`/elections/${editingElection.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditDialog(),
        onError: () => handleError(editForm),
    });
}

function confirmDelete() {
    if (!deletingElection.value) return;

    router.delete(`/elections/${deletingElection.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeDeleteDialog(),
        onError: () => {
            toastError('Delete failed', 'Unable to remove this election. Please try again.');
            closeDeleteDialog();
        },
    });
}

const activeCount = computed(() => props.elections.filter((e) => e.status === 'active').length);
</script>

<template>
    <AppLayout>
        <Head title="Elections" />

        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Elections</h1>
        </template>

        <div class="w-full space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold" style="color: hsl(240 10% 3.9%);">Election events</h2>
                    <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                        Create and manage election events, schedules, and voting periods.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                        style="background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);"
                    >
                        {{ elections.length }} total · {{ activeCount }} active
                    </span>
                    <Button @click="openCreateSheet">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create election
                    </Button>
                </div>
            </div>

            <div
                class="rounded-xl border overflow-hidden"
                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);">
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Election</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Event schedule</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Voting period</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Status</th>
                                <th class="h-10 px-4 text-right align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="election in elections"
                                :key="election.id"
                                class="border-b transition-colors hover:bg-gray-50"
                                style="border-color: hsl(240 5.9% 90%);"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <p class="font-medium" style="color: hsl(240 10% 3.9%);">{{ election.title }}</p>
                                    <p v-if="election.description" class="text-xs mt-0.5 line-clamp-2" style="color: hsl(240 3.8% 46.1%);">
                                        {{ election.description }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 align-middle max-w-xs" style="color: hsl(240 3.8% 46.1%);">
                                    {{ election.event_schedule }}
                                </td>
                                <td class="px-4 py-3 align-middle max-w-xs" style="color: hsl(240 3.8% 46.1%);">
                                    {{ election.voting_period }}
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                        :style="statusStyle(election.status)"
                                    >
                                        {{ election.status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button variant="outline" size="sm" @click="openEditDialog(election)">Edit</Button>
                                        <Button variant="destructive" size="sm" @click="openDeleteDialog(election)">Delete</Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="elections.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center" style="color: hsl(240 3.8% 46.1%);">
                                    No elections yet. Create one to get started.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Sheet
            :show="showCreateSheet"
            title="Create election"
            description="Set up the event details, schedule, and voting period."
            @close="closeCreateSheet"
        >
            <form id="create-election-form" class="space-y-4" @submit.prevent="submitCreate">
                <div class="space-y-1.5">
                    <Label html-for="create-title">Election title</Label>
                    <Input
                        id="create-title"
                        v-model="createForm.title"
                        type="text"
                        placeholder="2026 Student Council General Election"
                        :error="!!createForm.errors.title"
                    />
                    <InputError :message="createForm.errors.title" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="create-description">Description</Label>
                    <textarea
                        id="create-description"
                        v-model="createForm.description"
                        rows="3"
                        placeholder="Brief overview of this election event..."
                        class="flex min-h-[80px] w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-[hsl(240_3.8%_46.1%)] focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="createForm.errors.description
                            ? 'border-[hsl(0_84.2%_60.2%)] focus-visible:ring-[hsl(0_84.2%_60.2%)]'
                            : 'border-[hsl(240_5.9%_90%)] focus-visible:ring-[hsl(240_5.9%_10%)]'"
                        style="color: hsl(240 10% 3.9%);"
                    />
                    <InputError :message="createForm.errors.description" />
                </div>

                <div class="rounded-lg border p-4 space-y-4" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 98%);">
                    <div>
                        <p class="text-sm font-medium" style="color: hsl(240 10% 3.9%);">Event schedule</p>
                        <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">Optional window for the overall election event.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label html-for="create-event-starts">Starts</Label>
                            <Input
                                id="create-event-starts"
                                v-model="createForm.event_starts_at"
                                type="datetime-local"
                                :error="!!createForm.errors.event_starts_at"
                            />
                            <InputError :message="createForm.errors.event_starts_at" />
                        </div>
                        <div class="space-y-1.5">
                            <Label html-for="create-event-ends">Ends</Label>
                            <Input
                                id="create-event-ends"
                                v-model="createForm.event_ends_at"
                                type="datetime-local"
                                :error="!!createForm.errors.event_ends_at"
                            />
                            <InputError :message="createForm.errors.event_ends_at" />
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border p-4 space-y-4" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 98%);">
                    <div>
                        <p class="text-sm font-medium" style="color: hsl(240 10% 3.9%);">Voting period</p>
                        <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">When voters can cast their ballots.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label html-for="create-voting-starts">Voting opens</Label>
                            <Input
                                id="create-voting-starts"
                                v-model="createForm.voting_starts_at"
                                type="datetime-local"
                                :error="!!createForm.errors.voting_starts_at"
                            />
                            <InputError :message="createForm.errors.voting_starts_at" />
                        </div>
                        <div class="space-y-1.5">
                            <Label html-for="create-voting-ends">Voting closes</Label>
                            <Input
                                id="create-voting-ends"
                                v-model="createForm.voting_ends_at"
                                type="datetime-local"
                                :error="!!createForm.errors.voting_ends_at"
                            />
                            <InputError :message="createForm.errors.voting_ends_at" />
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label html-for="create-status">Status</Label>
                    <Select
                        id="create-status"
                        v-model="createForm.status"
                        :options="statusOptions"
                        placeholder="Select status"
                        :error="!!createForm.errors.status"
                    />
                    <InputError :message="createForm.errors.status" />
                </div>
            </form>

            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <Button type="button" variant="outline" @click="closeCreateSheet">Cancel</Button>
                    <Button type="submit" form="create-election-form" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Creating...' : 'Create election' }}
                    </Button>
                </div>
            </template>
        </Sheet>

        <Dialog
            :show="showEditDialog"
            wide
            title="Edit election"
            description="Update event details, schedule, and voting period."
            @close="closeEditDialog"
        >
            <form id="edit-election-form" class="min-w-0 space-y-5" @submit.prevent="submitEdit">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_200px] md:items-start">
                    <div class="min-w-0 space-y-1.5">
                        <Label html-for="edit-title">Election title</Label>
                        <Input
                            id="edit-title"
                            v-model="editForm.title"
                            type="text"
                            :error="!!editForm.errors.title"
                        />
                        <InputError :message="editForm.errors.title" />
                    </div>

                    <div class="min-w-0 space-y-1.5">
                        <Label html-for="edit-status">Status</Label>
                        <Select
                            id="edit-status"
                            v-model="editForm.status"
                            :options="statusOptions"
                            placeholder="Select status"
                            :error="!!editForm.errors.status"
                        />
                        <InputError :message="editForm.errors.status" />
                    </div>
                </div>

                <div class="min-w-0 space-y-1.5">
                    <Label html-for="edit-description">Description</Label>
                    <textarea
                        id="edit-description"
                        v-model="editForm.description"
                        rows="3"
                        class="flex min-h-[88px] w-full min-w-0 rounded-md border bg-white px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1"
                        :class="editForm.errors.description
                            ? 'border-[hsl(0_84.2%_60.2%)] focus-visible:ring-[hsl(0_84.2%_60.2%)]'
                            : 'border-[hsl(240_5.9%_90%)] focus-visible:ring-[hsl(240_5.9%_10%)]'"
                        style="color: hsl(240 10% 3.9%);"
                    />
                    <InputError :message="editForm.errors.description" />
                </div>

                <div class="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="min-w-0 rounded-lg border p-4 space-y-4" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 98%);">
                        <div>
                            <p class="text-sm font-medium" style="color: hsl(240 10% 3.9%);">Event schedule</p>
                            <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">Overall election event window.</p>
                        </div>
                        <div class="space-y-3">
                            <div class="min-w-0 space-y-1.5">
                                <Label html-for="edit-event-starts">Starts</Label>
                                <Input
                                    id="edit-event-starts"
                                    v-model="editForm.event_starts_at"
                                    type="datetime-local"
                                    :error="!!editForm.errors.event_starts_at"
                                />
                                <InputError :message="editForm.errors.event_starts_at" />
                            </div>
                            <div class="min-w-0 space-y-1.5">
                                <Label html-for="edit-event-ends">Ends</Label>
                                <Input
                                    id="edit-event-ends"
                                    v-model="editForm.event_ends_at"
                                    type="datetime-local"
                                    :error="!!editForm.errors.event_ends_at"
                                />
                                <InputError :message="editForm.errors.event_ends_at" />
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0 rounded-lg border p-4 space-y-4" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 98%);">
                        <div>
                            <p class="text-sm font-medium" style="color: hsl(240 10% 3.9%);">Voting period</p>
                            <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">When voters can cast ballots.</p>
                        </div>
                        <div class="space-y-3">
                            <div class="min-w-0 space-y-1.5">
                                <Label html-for="edit-voting-starts">Voting opens</Label>
                                <Input
                                    id="edit-voting-starts"
                                    v-model="editForm.voting_starts_at"
                                    type="datetime-local"
                                    :error="!!editForm.errors.voting_starts_at"
                                />
                                <InputError :message="editForm.errors.voting_starts_at" />
                            </div>
                            <div class="min-w-0 space-y-1.5">
                                <Label html-for="edit-voting-ends">Voting closes</Label>
                                <Input
                                    id="edit-voting-ends"
                                    v-model="editForm.voting_ends_at"
                                    type="datetime-local"
                                    :error="!!editForm.errors.voting_ends_at"
                                />
                                <InputError :message="editForm.errors.voting_ends_at" />
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="closeEditDialog">Cancel</Button>
                    <Button type="submit" form="edit-election-form" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                </div>
            </template>
        </Dialog>

        <Dialog
            :show="showDeleteDialog"
            title="Delete election"
            description="This action cannot be undone."
            @close="closeDeleteDialog"
        >
            <p class="text-sm mb-6" style="color: hsl(240 3.8% 46.1%);">
                Are you sure you want to delete
                <span class="font-medium" style="color: hsl(240 10% 3.9%);">{{ deletingElection?.title }}</span>?
            </p>

            <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="closeDeleteDialog">Cancel</Button>
                <Button type="button" variant="destructive" @click="confirmDelete">Delete election</Button>
            </div>
        </Dialog>
    </AppLayout>
</template>
