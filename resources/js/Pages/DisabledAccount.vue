<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';

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

function initials(name) {
    if (!name) return '?';
    return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
}

function logout() {
    router.post('/logout');
}

const rows = computed(() => [
    { label: 'Voter ID', value: props.account.voter_id_number },
    { label: 'Student ID', value: props.account.student_id_number },
    { label: 'Email', value: props.account.email },
    { label: 'Department', value: props.account.department },
    { label: 'Course', value: props.account.course },
    { label: 'Year level', value: props.account.year_level },
].filter((row) => row.value));

const appealPending = computed(() => props.appeal?.status === 'pending');
const appealRejected = computed(() => props.appeal?.status === 'rejected');
</script>

<template>
    <GuestLayout>
        <Head title="Account Disabled" />

        <div class="w-full max-w-lg lg:max-w-5xl">
            <div class="guest-card p-6 sm:p-8">
                <div class="flex flex-col items-center text-center lg:flex-row lg:items-center lg:text-left gap-4 lg:gap-5 mb-6">
                    <div
                        class="h-16 w-16 rounded-full overflow-hidden flex items-center justify-center text-lg font-semibold shrink-0"
                        style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);"
                    >
                        <img
                            v-if="account.profile_photo_url"
                            :src="account.profile_photo_url"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                        <template v-else>{{ initials(account.name) }}</template>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold tracking-tight guest-title">
                            {{ account.name }}
                        </h1>
                        <span
                            class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide"
                            style="background: hsl(0 84% 94%); color: hsl(0 72% 35%);"
                        >
                            {{ account.status_label }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-6 lg:items-start">
                    <div class="lg:col-span-7 space-y-4">
                        <div
                            class="rounded-lg border px-3 py-3 text-sm leading-relaxed"
                            style="border-color: hsl(0 84% 90%); background: hsl(0 86% 97%); color: hsl(0 50% 30%);"
                        >
                            Your account is <strong>Disabled</strong> because you did not update your year level
                            before the deadline. You cannot vote until this is resolved.
                        </div>

                        <div
                            v-if="appealPending"
                            class="rounded-lg border px-3 py-3 text-sm leading-relaxed"
                            style="border-color: hsl(43 70% 80%); background: hsl(43 60% 94%); color: hsl(43 70% 28%);"
                        >
                            Your appeal was submitted on {{ appeal.submitted_at }} and is waiting for review.
                        </div>

                        <div
                            v-else-if="appealRejected"
                            class="rounded-lg border px-3 py-3 text-sm leading-relaxed space-y-1"
                            style="border-color: hsl(0 84% 90%); background: hsl(0 86% 97%); color: hsl(0 50% 30%);"
                        >
                            <p>Your previous appeal was rejected.</p>
                            <p v-if="appeal.admin_notes" class="text-xs">Admin note: {{ appeal.admin_notes }}</p>
                        </div>

                        <div
                            class="rounded-lg border px-3 py-3 text-sm leading-relaxed space-y-2"
                            style="border-color: hsl(240 5.9% 90%); background: hsl(240 4.8% 98%); color: hsl(240 10% 3.9%);"
                        >
                            <p class="font-semibold">Why you need to update your year level</p>
                            <p class="guest-muted">
                                Your year level must match your real standing in school. If it is left unchanged,
                                someone could keep voting as if they were still in an earlier year, even after
                                they should no longer be allowed to vote.
                            </p>
                            <ul class="list-disc pl-5 space-y-1.5 guest-muted">
                                <li>
                                    This stops people from using an old year level to stay in the election longer
                                    than they should.
                                </li>
                                <li>
                                    It also stops repeat or unfair voting by students who are no longer eligible.
                                </li>
                                <li>
                                    Only students who are truly eligible for this school year should be able to vote.
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="lg:col-span-5 space-y-4">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 rounded-lg border overflow-hidden" style="border-color: hsl(240 5.9% 90%);">
                            <div
                                v-for="row in rows"
                                :key="row.label"
                                class="px-4 py-3 border-b last:border-b-0 odd:sm:border-r lg:odd:border-r-0 xl:odd:border-r text-sm"
                                style="border-color: hsl(240 5.9% 90%);"
                            >
                                <dt class="guest-muted text-xs mb-0.5">{{ row.label }}</dt>
                                <dd class="font-medium break-all" style="color: hsl(240 10% 3.9%);">
                                    {{ row.value }}
                                </dd>
                            </div>
                        </dl>

                        <div class="space-y-3">
                            <Link href="/account-disabled/appeal" class="block">
                                <Button class="w-full" variant="navy">
                                    {{
                                        appealPending
                                            ? 'View appeal'
                                            : appealRejected
                                              ? 'Submit a new appeal'
                                              : 'Submit an appeal'
                                    }}
                                </Button>
                            </Link>
                            <Button class="w-full" variant="outline" @click="logout">
                                Log out
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
