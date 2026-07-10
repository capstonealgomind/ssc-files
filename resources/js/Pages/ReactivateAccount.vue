<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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

const props = defineProps({
    validatedVoter: {
        type: Object,
        default: null,
    },
    submitted: {
        type: Object,
        default: null,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const mobileMenuOpen = ref(false);
const copied = ref(false);

const reactivationNumber = computed(
    () => page.props.flash?.reactivation_number ?? null,
);

const validateForm = useForm({
    voter_id: props.validatedVoter?.voter_id_number ?? '',
});

const requestForm = useForm({
    voter_id: props.validatedVoter?.voter_id_number ?? '',
    full_name: props.validatedVoter?.name ?? '',
    year_stopped: '',
    reason: '',
});

watch(
    () => props.validatedVoter,
    (voter) => {
        if (!voter) return;
        requestForm.voter_id = voter.voter_id_number;
        requestForm.full_name = voter.name ?? '';
    },
    { immediate: true },
);

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}

function validateVoter() {
    validateForm.post('/reactivate/validate', { preserveScroll: true });
}

function submitRequest() {
    requestForm.post('/reactivate', { preserveScroll: true });
}

async function copyNumber() {
    if (!reactivationNumber.value) return;
    try {
        await navigator.clipboard.writeText(reactivationNumber.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        copied.value = false;
    }
}
</script>

<template>
    <Head title="Reactivate Account" />

    <div class="guest-shell min-h-screen">
        <header class="guest-header relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="min-h-16 flex items-center justify-between gap-3 py-1.5">
                    <GuestHeaderBrand @click="closeMobileMenu" />
                    <nav class="hidden md:flex items-center gap-2">
                        <Link href="/reactivation-status"
                            ><Button variant="ghost" size="sm">Reactivation Status</Button></Link
                        >
                        <Link href="/"
                            ><Button variant="ghost" size="sm">Home</Button></Link
                        >
                        <Link href="/login"
                            ><Button variant="navy" size="sm">Log in</Button></Link
                        >
                    </nav>
                    <button
                        type="button"
                        class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-md border border-[var(--sscevs-border)]"
                        aria-label="Toggle menu"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
            <div v-show="mobileMenuOpen" class="md:hidden border-t border-[var(--sscevs-border)] bg-white px-4 py-3 space-y-2">
                <Link href="/reactivation-status" class="block" @click="closeMobileMenu"
                    ><Button variant="ghost" size="sm" class="w-full justify-start">Reactivation Status</Button></Link
                >
                <Link href="/" class="block" @click="closeMobileMenu"
                    ><Button variant="ghost" size="sm" class="w-full justify-start">Home</Button></Link
                >
            </div>
        </header>

        <main class="max-w-xl mx-auto px-4 py-10 sm:py-14">
            <Card>
                <CardHeader>
                    <CardTitle>Reactivate Account</CardTitle>
                    <CardDescription>
                        If your voter account expired after your course duration, validate your voter ID and submit a reactivation request.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div
                        v-if="reactivationNumber"
                        class="rounded-lg border border-[var(--sscevs-blue)]/30 bg-[var(--sscevs-blue-light)] p-4 space-y-3"
                    >
                        <p class="text-sm font-medium text-[var(--sscevs-blue-dark)]">
                            Request submitted. Save your reactivation number:
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <code class="text-lg font-semibold tracking-wide text-[var(--sscevs-black)]">
                                {{ reactivationNumber }}
                            </code>
                            <Button type="button" size="sm" variant="outline" @click="copyNumber">
                                {{ copied ? 'Copied' : 'Copy' }}
                            </Button>
                        </div>
                        <p class="text-xs text-[var(--sscevs-muted)]">
                            Use this number on Reactivation Status to check when your account is active again.
                        </p>
                        <Link href="/reactivation-status">
                            <Button type="button" size="sm" variant="navy">Check status</Button>
                        </Link>
                    </div>

                    <template v-else>
                        <form class="space-y-4" @submit.prevent="validateVoter">
                            <div class="space-y-2">
                                <Label for="voter_id">Voter ID</Label>
                                <Input
                                    id="voter_id"
                                    v-model="validateForm.voter_id"
                                    :disabled="!!validatedVoter"
                                    placeholder="Enter your voter ID"
                                    autocomplete="off"
                                />
                                <InputError :message="validateForm.errors.voter_id || errors.voter_id" />
                            </div>
                            <Button
                                v-if="!validatedVoter"
                                type="submit"
                                variant="navy"
                                :disabled="validateForm.processing"
                            >
                                {{ validateForm.processing ? 'Validating...' : 'Validate' }}
                            </Button>
                        </form>

                        <div
                            v-if="validatedVoter"
                            class="rounded-lg border border-[var(--sscevs-border)] bg-white p-4 space-y-2 text-sm"
                        >
                            <p><span class="text-[var(--sscevs-muted)]">Name:</span> {{ validatedVoter.name }}</p>
                            <p><span class="text-[var(--sscevs-muted)]">Voter ID:</span> {{ validatedVoter.voter_id_number }}</p>
                            <p><span class="text-[var(--sscevs-muted)]">Course:</span> {{ validatedVoter.course ?? '—' }} ({{ validatedVoter.course_duration ?? 0 }} yrs)</p>
                            <p><span class="text-[var(--sscevs-muted)]">Year level on file:</span> {{ validatedVoter.year_level ?? '—' }}</p>
                            <p>
                                <span class="text-[var(--sscevs-muted)]">Remaining course years:</span>
                                {{ validatedVoter.remaining_years }}
                                <span class="text-xs text-[var(--sscevs-muted)]">(duration − year level)</span>
                            </p>
                        </div>

                        <form v-if="validatedVoter" class="space-y-4" @submit.prevent="submitRequest">
                            <div class="space-y-2">
                                <Label for="full_name">Full name</Label>
                                <Input id="full_name" v-model="requestForm.full_name" />
                                <InputError :message="requestForm.errors.full_name" />
                            </div>
                            <div class="space-y-2">
                                <Label for="year_stopped">Year you stopped</Label>
                                <Input
                                    id="year_stopped"
                                    v-model="requestForm.year_stopped"
                                    placeholder="e.g. 2nd Year"
                                />
                                <InputError :message="requestForm.errors.year_stopped" />
                            </div>
                            <div class="space-y-2">
                                <Label for="reason">Reason</Label>
                                <textarea
                                    id="reason"
                                    v-model="requestForm.reason"
                                    rows="4"
                                    class="flex w-full rounded-md border border-[var(--sscevs-border)] bg-white px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--sscevs-blue)]"
                                    placeholder="Briefly explain why you left and are returning"
                                />
                                <InputError :message="requestForm.errors.reason" />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button type="submit" variant="navy" :disabled="requestForm.processing">
                                    {{ requestForm.processing ? 'Submitting...' : 'Submit request' }}
                                </Button>
                                <Link href="/reactivate?reset=1">
                                    <Button type="button" variant="outline">Start over</Button>
                                </Link>
                            </div>
                        </form>
                    </template>
                </CardContent>
            </Card>
        </main>
    </div>
</template>
