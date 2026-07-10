<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Sheet from '@/Components/ui/Sheet.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';
import { useToast } from '@/composables/useToast';

defineProps({
    admins: {
        type: Array,
        default: () => [],
    },
    committees: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const { error: toastError } = useToast();

const currentUserId = computed(() => page.props.auth.user?.id);
const adminEmailDomain = computed(() => page.props.adminEmailDomain || 'sscevs.admin.com');
const adminEmailSuffix = computed(() => `@${adminEmailDomain.value}`);
const committeeEmailDomain = computed(() => page.props.committeeEmailDomain || 'sscevs.committee.com');
const committeeEmailSuffix = computed(() => `@${committeeEmailDomain.value}`);

const activeTab = ref('admins');

const showCreateSheet = ref(false);
const showCreateCommitteeSheet = ref(false);
const showDeleteDialog = ref(false);
const showDeleteCommitteeDialog = ref(false);
const deletingAdmin = ref(null);
const deletingCommittee = ref(null);

const createForm = useForm({
    name: '',
    contact_email: '',
});

const createCommitteeForm = useForm({
    name: '',
    contact_email: '',
});

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
}

function handleActionSuccess(onClose) {
    onClose();
}

function openCreateSheet() {
    createForm.reset();
    createForm.clearErrors();
    showCreateSheet.value = true;
}

function closeCreateSheet() {
    showCreateSheet.value = false;
    createForm.reset();
    createForm.clearErrors();
}

function submitCreate() {
    createForm.post('/accounts', {
        preserveScroll: true,
        onSuccess: () => handleActionSuccess(closeCreateSheet),
        onError: () => {
            toastError(
                'Could not create account',
                Object.values(createForm.errors)[0] || 'Please check the form and try again.',
            );
        },
    });
}

function openCreateCommitteeSheet() {
    createCommitteeForm.reset();
    createCommitteeForm.clearErrors();
    showCreateCommitteeSheet.value = true;
}

function closeCreateCommitteeSheet() {
    showCreateCommitteeSheet.value = false;
    createCommitteeForm.reset();
    createCommitteeForm.clearErrors();
}

function submitCreateCommittee() {
    createCommitteeForm.post('/accounts/committee', {
        preserveScroll: true,
        onSuccess: () => {
            activeTab.value = 'committee';
            handleActionSuccess(closeCreateCommitteeSheet);
        },
        onError: () => {
            toastError(
                'Could not create committee account',
                Object.values(createCommitteeForm.errors)[0] || 'Please check the form and try again.',
            );
        },
    });
}

function openDeleteDialog(admin) {
    deletingAdmin.value = admin;
    showDeleteDialog.value = true;
}

function closeDeleteDialog() {
    showDeleteDialog.value = false;
    deletingAdmin.value = null;
}

function confirmDelete() {
    if (!deletingAdmin.value) return;

    router.delete(`/accounts/${deletingAdmin.value.id}`, {
        preserveScroll: true,
        onSuccess: () => handleActionSuccess(closeDeleteDialog),
        onError: () => {
            toastError(
                'Delete failed',
                'Unable to remove this admin account. Please try again.',
            );
            closeDeleteDialog();
        },
    });
}

function openDeleteCommitteeDialog(committee) {
    deletingCommittee.value = committee;
    showDeleteCommitteeDialog.value = true;
}

function closeDeleteCommitteeDialog() {
    showDeleteCommitteeDialog.value = false;
    deletingCommittee.value = null;
}

function confirmDeleteCommittee() {
    if (!deletingCommittee.value) return;

    router.delete(`/accounts/committee/${deletingCommittee.value.id}`, {
        preserveScroll: true,
        onSuccess: () => handleActionSuccess(closeDeleteCommitteeDialog),
        onError: () => {
            toastError(
                'Delete failed',
                'Unable to remove this committee account. Please try again.',
            );
            closeDeleteCommitteeDialog();
        },
    });
}

function canDelete(admin) {
    return admin.id !== currentUserId.value;
}
</script>

<template>
    <AppLayout>
        <Head title="Accounts" />

        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Accounts</h1>
        </template>

        <div class="w-full space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold" style="color: hsl(240 10% 3.9%);">Accounts</h2>
                    <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                        Manage administrator and election committee accounts.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Button v-if="activeTab === 'admins'" @click="openCreateSheet">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add admin account
                    </Button>
                    <Button v-else @click="openCreateCommitteeSheet">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add committee account
                    </Button>
                </div>
            </div>

            <!-- Tabs -->
            <div
                class="inline-flex rounded-lg border p-1 gap-1"
                style="background-color: hsl(240 4.8% 95.9%); border-color: hsl(240 5.9% 90%);"
            >
                <button
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :style="activeTab === 'admins'
                        ? 'background-color: hsl(0 0% 100%); color: hsl(240 10% 3.9%); box-shadow: 0 1px 2px rgb(0 0 0 / 0.06);'
                        : 'color: hsl(240 3.8% 46.1%);'"
                    @click="activeTab = 'admins'"
                >
                    Admins
                    <span
                        class="ml-1.5 inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                        style="background-color: hsl(240 5.9% 90%); color: hsl(240 5.9% 10%);"
                    >
                        {{ admins.length }}
                    </span>
                </button>
                <button
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :style="activeTab === 'committee'
                        ? 'background-color: hsl(0 0% 100%); color: hsl(240 10% 3.9%); box-shadow: 0 1px 2px rgb(0 0 0 / 0.06);'
                        : 'color: hsl(240 3.8% 46.1%);'"
                    @click="activeTab = 'committee'"
                >
                    Committee
                    <span
                        class="ml-1.5 inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                        style="background-color: hsl(240 5.9% 90%); color: hsl(240 5.9% 10%);"
                    >
                        {{ committees.length }}
                    </span>
                </button>
            </div>

            <!-- Admins table -->
            <div
                v-if="activeTab === 'admins'"
                class="rounded-xl border overflow-hidden"
                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
            >
                <div class="px-4 py-3 border-b" style="border-color: hsl(240 5.9% 90%);">
                    <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                        Enter a full name and contact email. The system generates a {{ adminEmailSuffix }} login and temporary password, then emails them to the contact address.
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);">
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Name</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Login email</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Contact email</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Created</th>
                                <th class="h-10 px-4 text-right align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="admin in admins"
                                :key="admin.id"
                                class="border-b transition-colors hover:bg-gray-50"
                                style="border-color: hsl(240 5.9% 90%);"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-8 w-8 rounded-full overflow-hidden flex items-center justify-center text-xs font-semibold shrink-0"
                                            style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);"
                                        >
                                            <img
                                                v-if="admin.profile_photo_url"
                                                :src="admin.profile_photo_url"
                                                :alt="admin.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <span v-else>{{ getInitials(admin.name) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium" style="color: hsl(240 10% 3.9%);">
                                                {{ admin.name }}
                                                <span
                                                    v-if="admin.id === currentUserId"
                                                    class="ml-1.5 text-xs font-normal"
                                                    style="color: hsl(240 3.8% 46.1%);"
                                                >
                                                    (you)
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                    {{ admin.email }}
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                    {{ admin.contact_email || '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                    {{ admin.created_at }}
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            :disabled="!canDelete(admin)"
                                            :title="!canDelete(admin) ? 'You cannot delete your own account' : 'Delete admin account'"
                                            @click="openDeleteDialog(admin)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="admins.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center" style="color: hsl(240 3.8% 46.1%);">
                                    No admin accounts found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Committee table -->
            <div
                v-else
                class="rounded-xl border overflow-hidden"
                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
            >
                <div class="px-4 py-3 border-b" style="border-color: hsl(240 5.9% 90%);">
                    <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                        Enter a full name and contact email. The system generates a {{ committeeEmailSuffix }} login and temporary password, then emails them to the contact address.
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);">
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Name</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Login email</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Contact email</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Created</th>
                                <th class="h-10 px-4 text-right align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="committee in committees"
                                :key="committee.id"
                                class="border-b transition-colors hover:bg-gray-50"
                                style="border-color: hsl(240 5.9% 90%);"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0"
                                            style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);"
                                        >
                                            {{ getInitials(committee.name) }}
                                        </div>
                                        <p class="font-medium" style="color: hsl(240 10% 3.9%);">
                                            {{ committee.name }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                    {{ committee.email }}
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                    {{ committee.contact_email || '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                    {{ committee.created_at }}
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            @click="openDeleteCommitteeDialog(committee)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="committees.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center" style="color: hsl(240 3.8% 46.1%);">
                                    No committee accounts found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create admin slide-over -->
        <Sheet
            :show="showCreateSheet"
            title="Create administrator"
            :description="`A login email ending in ${adminEmailSuffix} and a temporary password will be generated and sent to the contact email.`"
            @close="closeCreateSheet"
        >
            <form id="create-admin-form" class="space-y-4" @submit.prevent="submitCreate">
                <div class="space-y-1.5">
                    <Label html-for="create-name">Full name</Label>
                    <Input
                        id="create-name"
                        v-model="createForm.name"
                        type="text"
                        placeholder="John Doe"
                        autocomplete="name"
                        :error="!!createForm.errors.name"
                    />
                    <InputError :message="createForm.errors.name" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="create-contact-email">Contact email</Label>
                    <Input
                        id="create-contact-email"
                        v-model="createForm.contact_email"
                        type="email"
                        placeholder="john.doe@gmail.com"
                        autocomplete="email"
                        :error="!!createForm.errors.contact_email"
                    />
                    <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                        Credentials will be sent to this personal email address.
                    </p>
                    <InputError :message="createForm.errors.contact_email" />
                </div>
            </form>

            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <Button type="button" variant="outline" @click="closeCreateSheet">
                        Cancel
                    </Button>
                    <Button type="submit" form="create-admin-form" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Creating...' : 'Create & send credentials' }}
                    </Button>
                </div>
            </template>
        </Sheet>

        <!-- Create committee slide-over -->
        <Sheet
            :show="showCreateCommitteeSheet"
            title="Create committee account"
            :description="`A login email ending in ${committeeEmailSuffix} and a temporary password will be generated and sent to the contact email.`"
            @close="closeCreateCommitteeSheet"
        >
            <form id="create-committee-form" class="space-y-4" @submit.prevent="submitCreateCommittee">
                <div class="space-y-1.5">
                    <Label html-for="committee-name">Full name</Label>
                    <Input
                        id="committee-name"
                        v-model="createCommitteeForm.name"
                        type="text"
                        placeholder="Maria Santos"
                        autocomplete="name"
                        :error="!!createCommitteeForm.errors.name"
                    />
                    <InputError :message="createCommitteeForm.errors.name" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="committee-contact-email">Contact email</Label>
                    <Input
                        id="committee-contact-email"
                        v-model="createCommitteeForm.contact_email"
                        type="email"
                        placeholder="maria.santos@gmail.com"
                        autocomplete="email"
                        :error="!!createCommitteeForm.errors.contact_email"
                    />
                    <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                        Credentials will be sent to this personal email address.
                    </p>
                    <InputError :message="createCommitteeForm.errors.contact_email" />
                </div>
            </form>

            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <Button type="button" variant="outline" @click="closeCreateCommitteeSheet">
                        Cancel
                    </Button>
                    <Button type="submit" form="create-committee-form" :disabled="createCommitteeForm.processing">
                        {{ createCommitteeForm.processing ? 'Creating...' : 'Create & send credentials' }}
                    </Button>
                </div>
            </template>
        </Sheet>

        <!-- Delete admin confirmation -->
        <Dialog
            :show="showDeleteDialog"
            title="Delete admin account"
            description="This action cannot be undone."
            @close="closeDeleteDialog"
        >
            <p class="text-sm mb-6" style="color: hsl(240 3.8% 46.1%);">
                Are you sure you want to delete
                <span class="font-medium" style="color: hsl(240 10% 3.9%);">{{ deletingAdmin?.name }}</span>?
            </p>

            <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="closeDeleteDialog">
                    Cancel
                </Button>
                <Button type="button" variant="destructive" @click="confirmDelete">
                    Delete account
                </Button>
            </div>
        </Dialog>

        <!-- Delete committee confirmation -->
        <Dialog
            :show="showDeleteCommitteeDialog"
            title="Delete committee account"
            description="This action cannot be undone."
            @close="closeDeleteCommitteeDialog"
        >
            <p class="text-sm mb-6" style="color: hsl(240 3.8% 46.1%);">
                Are you sure you want to delete
                <span class="font-medium" style="color: hsl(240 10% 3.9%);">{{ deletingCommittee?.name }}</span>?
            </p>

            <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="closeDeleteCommitteeDialog">
                    Cancel
                </Button>
                <Button type="button" variant="destructive" @click="confirmDeleteCommittee">
                    Delete account
                </Button>
            </div>
        </Dialog>
    </AppLayout>
</template>
