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
});

const page = usePage();
const { error: toastError } = useToast();

const currentUserId = computed(() => page.props.auth.user?.id);
const adminEmailDomain = computed(() => page.props.adminEmailDomain || 'sscevs.admin.com');
const adminEmailSuffix = computed(() => `@${adminEmailDomain.value}`);

const showCreateSheet = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const editingAdmin = ref(null);
const deletingAdmin = ref(null);

const createForm = useForm({
    name: '',
    email_local: '',
    password: '',
    password_confirmation: '',
});

const editForm = useForm({
    name: '',
    email_local: '',
    password: '',
    password_confirmation: '',
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

function openEditDialog(admin) {
    editingAdmin.value = admin;
    editForm.name = admin.name;
    editForm.email_local = admin.email_local || admin.email.replace(adminEmailSuffix.value, '');
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.clearErrors();
    showEditDialog.value = true;
}

function closeEditDialog() {
    showEditDialog.value = false;
    editingAdmin.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function submitEdit() {
    editForm.put(`/accounts/${editingAdmin.value.id}`, {
        preserveScroll: true,
        onSuccess: () => handleActionSuccess(closeEditDialog),
        onError: () => {
            toastError(
                'Could not update account',
                Object.values(editForm.errors)[0] || 'Please check the form and try again.',
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
                    <h2 class="text-lg font-semibold" style="color: hsl(240 10% 3.9%);">Admin accounts</h2>
                    <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                        Admin emails use {{ adminEmailSuffix }}. Public registration with normal emails creates voter accounts.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                        style="background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);"
                    >
                        {{ admins.length }} {{ admins.length === 1 ? 'admin' : 'admins' }}
                    </span>
                    <Button @click="openCreateSheet">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add admin account
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
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Name</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Email</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Role</th>
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
                                            class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0"
                                            style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);"
                                        >
                                            {{ getInitials(admin.name) }}
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
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                        style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);"
                                    >
                                        {{ admin.role }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                                    {{ admin.created_at }}
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="openEditDialog(admin)"
                                        >
                                            Edit
                                        </Button>
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
        </div>

        <!-- Create admin slide-over -->
        <Sheet
            :show="showCreateSheet"
            title="Create administrator"
            :description="`Admin accounts use the ${adminEmailSuffix} email domain.`"
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
                    <Label html-for="create-email-local">Admin email</Label>
                    <div class="flex items-stretch">
                        <div class="flex-1 [&_input]:rounded-r-none [&_input]:border-r-0">
                            <Input
                                id="create-email-local"
                                v-model="createForm.email_local"
                                type="text"
                                placeholder="john.doe"
                                autocomplete="off"
                                :error="!!createForm.errors.email_local"
                            />
                        </div>
                        <span
                            class="inline-flex items-center rounded-r-md border border-l-0 px-3 text-sm whitespace-nowrap"
                            style="background-color: hsl(240 4.8% 95.9%); border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
                        >
                            {{ adminEmailSuffix }}
                        </span>
                    </div>
                    <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                        Preview: {{ createForm.email_local || 'username' }}{{ adminEmailSuffix }}
                    </p>
                    <InputError :message="createForm.errors.email_local" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="create-password">Password</Label>
                    <Input
                        id="create-password"
                        v-model="createForm.password"
                        type="password"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        :error="!!createForm.errors.password"
                    />
                    <InputError :message="createForm.errors.password" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="create-password-confirmation">Confirm password</Label>
                    <Input
                        id="create-password-confirmation"
                        v-model="createForm.password_confirmation"
                        type="password"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        :error="!!createForm.errors.password_confirmation"
                    />
                    <InputError :message="createForm.errors.password_confirmation" />
                </div>
            </form>

            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <Button type="button" variant="outline" @click="closeCreateSheet">
                        Cancel
                    </Button>
                    <Button type="submit" form="create-admin-form" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Creating...' : 'Create admin account' }}
                    </Button>
                </div>
            </template>
        </Sheet>

        <!-- Edit dialog -->
        <Dialog
            :show="showEditDialog"
            title="Edit admin account"
            description="Update administrator account details."
            @close="closeEditDialog"
        >
            <form class="space-y-4" @submit.prevent="submitEdit">
                <div class="space-y-1.5">
                    <Label html-for="edit-name">Full name</Label>
                    <Input
                        id="edit-name"
                        v-model="editForm.name"
                        type="text"
                        placeholder="John Doe"
                        :error="!!editForm.errors.name"
                    />
                    <InputError :message="editForm.errors.name" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="edit-email-local">Admin email</Label>
                    <div class="flex items-stretch">
                        <div class="flex-1 [&_input]:rounded-r-none [&_input]:border-r-0">
                            <Input
                                id="edit-email-local"
                                v-model="editForm.email_local"
                                type="text"
                                placeholder="john.doe"
                                :error="!!editForm.errors.email_local"
                            />
                        </div>
                        <span
                            class="inline-flex items-center rounded-r-md border border-l-0 px-3 text-sm whitespace-nowrap"
                            style="background-color: hsl(240 4.8% 95.9%); border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
                        >
                            {{ adminEmailSuffix }}
                        </span>
                    </div>
                    <InputError :message="editForm.errors.email_local" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="edit-password">New password (optional)</Label>
                    <Input
                        id="edit-password"
                        v-model="editForm.password"
                        type="password"
                        placeholder="••••••••"
                        :error="!!editForm.errors.password"
                    />
                    <InputError :message="editForm.errors.password" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="edit-password-confirmation">Confirm password</Label>
                    <Input
                        id="edit-password-confirmation"
                        v-model="editForm.password_confirmation"
                        type="password"
                        placeholder="••••••••"
                        :error="!!editForm.errors.password_confirmation"
                    />
                    <InputError :message="editForm.errors.password_confirmation" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" @click="closeEditDialog">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                </div>
            </form>
        </Dialog>

        <!-- Delete confirmation dialog -->
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
    </AppLayout>
</template>
