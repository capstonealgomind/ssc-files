<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';

const props = defineProps({
    account: { type: Object, required: true },
    appeal: { type: Object, default: null },
    school_year_label: { type: String, default: '' },
});

const form = useForm({
    action: 'approve',
    admin_notes: '',
});

const initials = computed(() =>
    (props.account.name ?? '?').split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase(),
);

const hasPendingAppeal = computed(() => props.appeal?.status === 'pending');
const rows = computed(() => [
    { label: 'Voter ID', value: props.account.voter_id_number },
    { label: 'Student ID', value: props.account.student_id_number },
    { label: 'Email', value: props.account.email },
    { label: 'Department', value: props.account.department },
    { label: 'Course', value: props.account.course },
    { label: 'Year level', value: props.account.year_level },
    { label: 'Years left', value: props.account.remaining_years != null ? String(props.account.remaining_years) : null },
    { label: 'Expires', value: props.account.account_expires_at },
].filter((row) => row.value));

function appealStatusStyle(status) {
    if (status === 'pending') return { color: 'hsl(38 62% 30%)', background: 'hsl(38 92% 94%)' };
    if (status === 'approved') return { color: 'hsl(142 71% 25%)', background: 'hsl(142 76% 94%)' };
    if (status === 'rejected') return { color: 'hsl(0 72% 35%)', background: 'hsl(0 86% 94%)' };
    return { color: 'hsl(240 3.8% 46.1%)', background: 'hsl(240 4.8% 95.9%)' };
}

function submitProcess() {
    form.post(`/disabled-accounts/${props.account.id}/process`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="`Process ${account.name}`" />

        <template #header>
            <div class="flex flex-col gap-1 min-w-0">
                <Link
                    href="/disabled-accounts"
                    class="text-xs font-medium"
                    style="color: hsl(221 83% 45%);"
                >
                    ← Disabled accounts
                </Link>
                <h1 class="text-base font-semibold truncate" style="color: hsl(240 10% 3.9%);">
                    Process disabled account
                </h1>
            </div>
        </template>

        <div class="w-full space-y-4">
            <div class="rounded-xl border overflow-hidden" style="border-color: hsl(240 5.9% 90%); background: #fff;">
                <div class="px-5 py-4 flex items-center gap-4">
                    <div
                        class="h-12 w-12 rounded-full overflow-hidden flex items-center justify-center text-sm font-bold shrink-0"
                        style="background: hsl(240 5.9% 10%); color: #fff;"
                    >
                        <img
                            v-if="account.profile_photo_url"
                            :src="account.profile_photo_url"
                            :alt="account.name"
                            class="h-full w-full object-cover"
                        />
                        <template v-else>{{ initials }}</template>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold truncate" style="color: hsl(240 10% 3.9%);">{{ account.name }}</p>
                        <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                            Disabled for missing the year level update
                            <span v-if="school_year_label"> · {{ school_year_label }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                <div class="lg:col-span-7 rounded-xl border overflow-hidden" style="border-color: hsl(240 5.9% 90%); background: #fff;">
                    <div class="px-5 py-4 border-b" style="border-color: hsl(240 5.9% 90%);">
                        <h2 class="text-sm font-semibold" style="color: hsl(240 10% 3.9%);">Account information</h2>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2">
                        <div
                            v-for="row in rows"
                            :key="row.label"
                            class="px-5 py-3 border-b odd:sm:border-r text-sm"
                            style="border-color: hsl(240 5.9% 90%);"
                        >
                            <dt class="text-xs mb-0.5" style="color: hsl(240 3.8% 46.1%);">{{ row.label }}</dt>
                            <dd class="font-medium break-words" style="color: hsl(240 10% 3.9%);">{{ row.value }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="lg:col-span-5 space-y-4">
                    <div class="rounded-xl border overflow-hidden" style="border-color: hsl(240 5.9% 90%); background: #fff;">
                        <div class="px-5 py-4 border-b flex items-center justify-between gap-3" style="border-color: hsl(240 5.9% 90%);">
                            <h2 class="text-sm font-semibold" style="color: hsl(240 10% 3.9%);">Appeal</h2>
                            <span
                                v-if="appeal"
                                class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium capitalize"
                                :style="appealStatusStyle(appeal.status)"
                            >
                                {{ appeal.status }}
                            </span>
                        </div>

                        <div v-if="appeal" class="px-5 py-4 space-y-3 text-sm">
                            <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                                Submitted {{ appeal.submitted_at }}
                            </p>
                            <p class="whitespace-pre-wrap leading-relaxed" style="color: hsl(240 10% 3.9%);">
                                {{ appeal.reason }}
                            </p>
                            <p v-if="appeal.admin_notes" class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                                Admin notes: {{ appeal.admin_notes }}
                            </p>
                        </div>
                        <div v-else class="px-5 py-8 text-sm text-center" style="color: hsl(240 3.8% 46.1%);">
                            This voter has not submitted an appeal yet.
                        </div>
                    </div>

                    <form
                        class="rounded-xl border p-5 space-y-4"
                        style="border-color: hsl(240 5.9% 90%); background: #fff;"
                        @submit.prevent="submitProcess"
                    >
                        <div class="space-y-2">
                            <Label>Decision</Label>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    :variant="form.action === 'approve' ? 'navy' : 'outline'"
                                    @click="form.action = 'approve'"
                                >
                                    Restore account
                                </Button>
                                <Button
                                    v-if="hasPendingAppeal"
                                    type="button"
                                    size="sm"
                                    :variant="form.action === 'reject' ? 'destructive' : 'outline'"
                                    @click="form.action = 'reject'"
                                >
                                    Reject appeal
                                </Button>
                            </div>
                            <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                                Restoring lets the voter sign in and update their year level on Profile.
                                They cannot vote until that update is done.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="admin_notes">Admin notes (optional)</Label>
                            <textarea
                                id="admin_notes"
                                v-model="form.admin_notes"
                                rows="4"
                                class="flex w-full rounded-md border border-[var(--sscevs-border)] bg-white px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--sscevs-blue)]"
                            />
                            <InputError :message="form.errors.admin_notes" />
                        </div>

                        <div class="flex justify-end gap-2">
                            <Link href="/disabled-accounts">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Confirm' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
