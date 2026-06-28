<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardDescription from '@/Components/ui/CardDescription.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import GuestHeaderBrand from '@/Components/GuestHeaderBrand.vue';

defineProps({
    status: {
        type: Object,
        default: null,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    voter_id: '',
});

const submit = () => {
    form.post('/check-status', { preserveScroll: true });
};

const reset = () => {
    form.reset();
    form.clearErrors();
    router.visit('/check-status');
};

const statusLabel = (value) => {
    const labels = {
        verified: 'Verified',
        pending: 'Pending',
        processing: 'Processing',
        completed: 'Completed',
        failed: 'Failed',
        approved: 'Approved',
        rejected: 'Rejected',
    };
    return labels[value] ?? value;
};

const statusVariant = (value) => {
    if (['verified', 'completed', 'approved'].includes(value)) return 'success';
    if (value === 'processing') return 'warning';
    if (['failed', 'rejected'].includes(value)) return 'destructive';
    return 'muted';
};

const badgeClass = (value) => {
    const variants = {
        success: 'bg-[var(--sscevs-blue-light)] text-[var(--sscevs-blue-dark)] border-[var(--sscevs-blue)]/25',
        warning: 'bg-[var(--sscevs-gold-light)] text-[var(--sscevs-gold-dark)] border-[var(--sscevs-gold)]/35',
        destructive: 'bg-[hsl(0_84%_60%/0.1)] text-[hsl(0_72%_51%)] border-[hsl(0_84%_60%/0.2)]',
        muted: 'bg-white text-[var(--sscevs-muted)] border-[var(--sscevs-border)]',
    };
    return variants[statusVariant(value)];
};

const steps = (status) => [
    {
        key: 'email',
        title: 'Email verification',
        value: status.emailStatus,
        hint:
            status.emailStatus === 'pending'
                ? 'Check your inbox and click the verification link we sent you.'
                : status.emailStatus === 'verified'
                  ? 'Your email address has been confirmed.'
                  : null,
    },
    {
        key: 'school-id',
        title: 'School ID review',
        value: status.ocrStatus,
        hint:
            status.ocrStatus === 'pending'
                ? 'Your ID will be reviewed after you verify your email.'
                : status.ocrStatus === 'processing'
                  ? 'We are processing your school ID. This usually takes a minute or two.'
                  : status.ocrStatus === 'completed'
                    ? 'Your school ID was successfully processed.'
                    : status.ocrStatus === 'failed'
                      ? 'There was an issue with your ID. Please contact support.'
                      : null,
    },
    {
        key: 'account',
        title: 'Account approval',
        value: status.verificationStatus,
        hint:
            status.verificationStatus === 'pending' && status.emailStatus !== 'verified'
                ? 'Complete email verification first to continue.'
                : status.verificationStatus === 'pending'
                  ? 'Your registration is under admin review.'
                  : status.verificationStatus === 'approved'
                    ? 'Your account is approved. You can log in and vote.'
                    : status.verificationStatus === 'rejected'
                      ? 'Your registration was not approved. Contact support for help.'
                      : null,
    },
];
</script>

<template>
    <Head title="Check Registration Status" />

    <div class="guest-shell">
        <header class="guest-header">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="min-h-16 flex items-center justify-between gap-3 py-1.5">
                    <GuestHeaderBrand />
                    <nav class="flex items-center gap-2">
                        <Link href="/"><Button variant="ghost" size="sm">Home</Button></Link>
                        <Link href="/login"><Button variant="ghost" size="sm">Log in</Button></Link>
                        <Link href="/register"><Button variant="navy" size="sm">Register</Button></Link>
                    </nav>
                </div>
            </div>
        </header>

        <main class="flex-1 py-12 sm:py-16 px-4 bg-white">
            <div class="max-w-lg mx-auto space-y-6">
                <!-- Page heading -->
                <div class="text-center space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight guest-title">
                        Check your status
                    </h1>
                    <p class="text-sm guest-muted">
                        Enter your Voter ID to see where you are in the registration process.
                    </p>
                </div>

                <!-- Search form -->
                <Card>
                    <CardHeader>
                        <CardTitle>Voter ID lookup</CardTitle>
                        <CardDescription>
                            Your Voter ID was shown after registration, e.g. VID-2026-00001.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="space-y-2">
                                <Label html-for="voter_id">Voter ID</Label>
                                <Input
                                    id="voter_id"
                                    v-model="form.voter_id"
                                    placeholder="VID-2026-00001"
                                    :error="!!(form.errors.voter_id || errors.voter_id)"
                                    autocomplete="off"
                                />
                                <InputError :message="form.errors.voter_id || errors.voter_id" />
                            </div>
                            <div class="flex gap-2">
                                <Button type="submit" class="flex-1" :disabled="form.processing">
                                    {{ form.processing ? 'Checking…' : 'Check status' }}
                                </Button>
                                <Button v-if="status" type="button" variant="outline" @click="reset">
                                    Clear
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Results -->
                <template v-if="status">
                    <!-- Voter info -->
                    <Card>
                        <CardHeader class="pb-4">
                            <CardDescription>Registered voter</CardDescription>
                            <CardTitle class="text-xl mt-1">{{ status.name }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm font-mono guest-surface guest-title"
                            >
                                {{ status.voterIdNumber }}
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Verification steps -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Verification progress</CardTitle>
                            <CardDescription>Track each step of your registration.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ol class="space-y-0">
                                <li
                                    v-for="(step, index) in steps(status)"
                                    :key="step.key"
                                    class="relative flex gap-4 pb-6 last:pb-0"
                                >
                                    <!-- Connector line -->
                                    <div
                                        v-if="index < steps(status).length - 1"
                                        class="absolute left-[11px] top-6 bottom-0 w-px bg-[var(--sscevs-border)]"
                                    />

                                    <!-- Step dot -->
                                    <div
                        class="relative z-10 mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border"
                                        :class="
                                            statusVariant(step.value) === 'success'
                                                ? 'border-[var(--sscevs-blue)] bg-[var(--sscevs-blue)]'
                                                : statusVariant(step.value) === 'destructive'
                                                  ? 'border-[hsl(0_84%_60%)] bg-[hsl(0_84%_60%)]'
                                                  : statusVariant(step.value) === 'warning'
                                                    ? 'border-[var(--sscevs-gold)] bg-[var(--sscevs-gold)]'
                                                    : 'border-[var(--sscevs-border)] bg-white'
                                        "
                                    >
                                        <svg
                                            v-if="statusVariant(step.value) === 'success'"
                                            class="h-3.5 w-3.5 text-white"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="3"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg
                                            v-else-if="statusVariant(step.value) === 'destructive'"
                                            class="h-3.5 w-3.5 text-white"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="3"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span
                                            v-else-if="statusVariant(step.value) === 'warning'"
                                            class="h-2 w-2 rounded-full bg-[var(--sscevs-black)]"
                                        />
                                        <span
                                            v-else
                                            class="h-2 w-2 rounded-full bg-[var(--sscevs-border)]"
                                        />
                                    </div>

                                    <!-- Step content -->
                                    <div class="flex-1 min-w-0 pt-0.5">
                                        <div class="flex items-center justify-between gap-3 flex-wrap">
                                            <p class="text-sm font-medium guest-title">
                                                {{ step.title }}
                                            </p>
                                            <span
                                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                                :class="badgeClass(step.value)"
                                            >
                                                {{ statusLabel(step.value) }}
                                            </span>
                                        </div>
                                        <p v-if="step.hint" class="mt-1.5 text-xs leading-relaxed guest-muted">
                                            {{ step.hint }}
                                        </p>
                                    </div>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>

                    <!-- Overall outcome -->
                    <div v-if="status.isVerified" class="guest-success-surface rounded-xl p-4 flex gap-3">
                        <div class="h-8 w-8 shrink-0 rounded-full flex items-center justify-center bg-[var(--sscevs-blue)]/15">
                            <svg class="h-4 w-4 text-[var(--sscevs-blue)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-[var(--sscevs-blue-dark)]">You're all set</p>
                            <p class="text-xs mt-1 leading-relaxed guest-muted">
                                Your account is fully verified. You can log in and participate in elections.
                            </p>
                            <Link href="/login" class="inline-block mt-3">
                                <Button size="sm">Log in</Button>
                            </Link>
                        </div>
                    </div>

                    <div v-else class="guest-warning-surface rounded-xl p-4 flex gap-3">
                        <div class="h-8 w-8 shrink-0 rounded-full flex items-center justify-center bg-[var(--sscevs-gold)]/20">
                            <svg class="h-4 w-4 text-[var(--sscevs-gold-dark)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-[var(--sscevs-gold-dark)]">Verification in progress</p>
                            <p class="text-xs mt-1 leading-relaxed guest-muted">
                                Complete the steps above before logging in. Check back here anytime for updates.
                            </p>
                        </div>
                    </div>
                </template>
            </div>
        </main>

        <!-- Footer -->
        <footer class="guest-footer py-6 px-4 text-center bg-white">
            <p class="text-xs guest-muted">
                &copy; {{ new Date().getFullYear() }} SSCEVS. All rights reserved.
            </p>
        </footer>
    </div>
</template>
