<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';

const props = defineProps({
    account: {
        type: Object,
        required: true,
    },
    appeal: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    reason: '',
});

const appealPending = computed(() => props.appeal?.status === 'pending');
const appealRejected = computed(() => props.appeal?.status === 'rejected');
const canSubmit = computed(() => !appealPending.value);

function submitAppeal() {
    form.post('/account-disabled/appeal', { preserveScroll: true });
}
</script>

<template>
    <GuestLayout>
        <Head title="Year Level Appeal" />

        <div class="w-full max-w-lg">
            <div class="guest-card p-6 sm:p-8">
                <div class="mb-5">
                    <Link
                        href="/account-disabled"
                        class="inline-flex items-center gap-1.5 text-sm guest-muted hover:underline"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </Link>
                    <h1 class="mt-3 text-xl font-semibold tracking-tight guest-title">
                        Year level appeal
                    </h1>
                    <p class="mt-1.5 text-sm guest-muted leading-relaxed">
                        Explain why you did not update your year level before the deadline.
                        An administrator will review this before deciding whether to restore your account.
                    </p>
                </div>

                <div
                    class="rounded-lg border px-3 py-3 mb-5 text-sm space-y-1.5"
                    style="border-color: hsl(240 5.9% 90%); background: hsl(240 4.8% 98%);"
                >
                    <p class="font-medium" style="color: hsl(240 10% 3.9%);">{{ account.name }}</p>
                    <p class="guest-muted text-xs">
                        {{ account.voter_id_number }}
                        <span v-if="account.year_level"> · {{ account.year_level }}</span>
                        <span v-if="account.course"> · {{ account.course }}</span>
                    </p>
                </div>

                <div
                    v-if="appealPending"
                    class="rounded-lg border px-3 py-3 mb-5 text-sm space-y-2"
                    style="border-color: hsl(43 70% 80%); background: hsl(43 60% 94%); color: hsl(43 70% 28%);"
                >
                    <p class="font-semibold">Appeal submitted</p>
                    <p>Submitted on {{ appeal.submitted_at }}. Please wait for an administrator to review it.</p>
                    <p class="whitespace-pre-wrap leading-relaxed" style="color: hsl(240 10% 3.9%);">
                        {{ appeal.reason }}
                    </p>
                </div>

                <div
                    v-else-if="appealRejected"
                    class="rounded-lg border px-3 py-3 mb-5 text-sm space-y-1"
                    style="border-color: hsl(0 84% 90%); background: hsl(0 86% 97%); color: hsl(0 50% 30%);"
                >
                    <p class="font-semibold">Previous appeal rejected</p>
                    <p v-if="appeal.admin_notes">{{ appeal.admin_notes }}</p>
                    <p v-else>You may submit a new appeal below.</p>
                </div>

                <form v-if="canSubmit" class="space-y-4" @submit.prevent="submitAppeal">
                    <div class="space-y-2">
                        <Label for="reason">Why did you not update your year level on time?</Label>
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            rows="6"
                            maxlength="2000"
                            class="flex w-full min-w-0 rounded-md border border-[var(--sscevs-border)] bg-white px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--sscevs-blue)]"
                            placeholder="Describe what happened — for example illness, delayed enrollment, or another reason you missed the deadline."
                        />
                        <div class="flex items-start justify-between gap-3">
                            <InputError :message="form.errors.reason" />
                            <p class="text-xs guest-muted shrink-0 ml-auto">
                                {{ form.reason.length }}/2000
                            </p>
                        </div>
                    </div>

                    <Button
                        type="submit"
                        variant="navy"
                        class="w-full"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Submitting...' : 'Submit appeal' }}
                    </Button>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
