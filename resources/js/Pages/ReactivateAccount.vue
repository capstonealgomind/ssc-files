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

    <div class="guest-shell">
        <header class="guest-header relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="min-h-16 flex items-center justify-between gap-3 py-1.5">
                    <GuestHeaderBrand @click="closeMobileMenu" />
                    <nav class="guest-nav-links">
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
                        class="guest-nav-menu-button"
                        :aria-expanded="mobileMenuOpen"
                        aria-label="Toggle menu"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <svg
                            v-if="!mobileMenuOpen"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                v-show="mobileMenuOpen"
                class="guest-nav-mobile-backdrop"
                @click="closeMobileMenu"
            />

            <div v-show="mobileMenuOpen" class="guest-nav-mobile-panel">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-4 pt-3 space-y-2">
                    <Link href="/reactivation-status" class="block" @click="closeMobileMenu">
                        <Button variant="ghost" size="sm" class="w-full justify-start">Reactivation Status</Button>
                    </Link>
                    <Link href="/" class="block" @click="closeMobileMenu">
                        <Button variant="ghost" size="sm" class="w-full justify-start">Home</Button>
                    </Link>
                    <Link href="/login" class="block" @click="closeMobileMenu">
                        <Button variant="navy" size="sm" class="w-full">Log in</Button>
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1 w-full min-w-0 py-8 sm:py-12 px-4 sm:px-6 bg-white">
            <div class="max-w-lg mx-auto w-full space-y-5 sm:space-y-6">
                <div class="text-center space-y-2 px-1">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight guest-title">
                        Reactivate Account
                    </h1>
                    <p class="text-sm guest-muted leading-relaxed">
                        If your voter account expired after your course duration, validate your voter ID and submit a reactivation request.
                    </p>
                </div>

                <Card class="overflow-hidden">
                    <CardHeader class="px-4 sm:px-6">
                        <CardTitle class="text-lg sm:text-xl">Request form</CardTitle>
                        <CardDescription>
                            Use your voter ID to start. After approval, you can log in again.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5 sm:space-y-6 px-4 sm:px-6">
                        <div
                            v-if="reactivationNumber"
                            class="rounded-lg border border-[var(--sscevs-blue)]/30 bg-[var(--sscevs-blue-light)] p-3 sm:p-4 space-y-3"
                        >
                            <p class="text-sm font-medium text-[var(--sscevs-blue-dark)]">
                                Request submitted. Save your reactivation number:
                            </p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 min-w-0">
                                <code class="text-base sm:text-lg font-semibold tracking-wide text-[var(--sscevs-black)] break-all min-w-0">
                                    {{ reactivationNumber }}
                                </code>
                                <Button type="button" size="sm" variant="outline" class="w-full sm:w-auto shrink-0" @click="copyNumber">
                                    {{ copied ? 'Copied' : 'Copy' }}
                                </Button>
                            </div>
                            <p class="text-xs text-[var(--sscevs-muted)] leading-relaxed">
                                Use this number on Reactivation Status to check when your account is active again.
                            </p>
                            <Link href="/reactivation-status" class="block sm:inline-block">
                                <Button type="button" size="sm" variant="navy" class="w-full sm:w-auto">Check status</Button>
                            </Link>
                        </div>

                        <template v-else>
                            <form class="space-y-4" @submit.prevent="validateVoter">
                                <div class="space-y-2">
                                    <Label for="voter_id">Voter ID</Label>
                                    <Input
                                        id="voter_id"
                                        v-model="validateForm.voter_id"
                                        class="w-full min-w-0"
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
                                    class="w-full sm:w-auto"
                                    :disabled="validateForm.processing"
                                >
                                    {{ validateForm.processing ? 'Validating...' : 'Validate' }}
                                </Button>
                            </form>

                            <div
                                v-if="validatedVoter"
                                class="rounded-lg border border-[var(--sscevs-border)] bg-white p-3 sm:p-4 space-y-2.5 text-sm min-w-0"
                            >
                                <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-3 min-w-0">
                                    <span class="text-[var(--sscevs-muted)] shrink-0">Name</span>
                                    <span class="font-medium break-words sm:text-right">{{ validatedVoter.name }}</span>
                                </div>
                                <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-3 min-w-0">
                                    <span class="text-[var(--sscevs-muted)] shrink-0">Voter ID</span>
                                    <span class="font-medium font-mono break-all sm:text-right">{{ validatedVoter.voter_id_number }}</span>
                                </div>
                                <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-3 min-w-0">
                                    <span class="text-[var(--sscevs-muted)] shrink-0">Course</span>
                                    <span class="font-medium break-words sm:text-right">
                                        {{ validatedVoter.course ?? '—' }} ({{ validatedVoter.course_duration ?? 0 }} yrs)
                                    </span>
                                </div>
                                <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-3 min-w-0">
                                    <span class="text-[var(--sscevs-muted)] shrink-0">Year level on file</span>
                                    <span class="font-medium sm:text-right">{{ validatedVoter.year_level ?? '—' }}</span>
                                </div>
                                <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-3 min-w-0">
                                    <span class="text-[var(--sscevs-muted)] shrink-0">Remaining course years</span>
                                    <span class="font-medium sm:text-right">
                                        {{ validatedVoter.remaining_years }}
                                        <span class="text-xs text-[var(--sscevs-muted)]">(duration − year level)</span>
                                    </span>
                                </div>
                            </div>

                            <form v-if="validatedVoter" class="space-y-4" @submit.prevent="submitRequest">
                                <div class="space-y-2">
                                    <Label for="full_name">Full name</Label>
                                    <Input id="full_name" v-model="requestForm.full_name" class="w-full min-w-0" />
                                    <InputError :message="requestForm.errors.full_name" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="year_stopped">Year you stopped</Label>
                                    <Input
                                        id="year_stopped"
                                        v-model="requestForm.year_stopped"
                                        class="w-full min-w-0"
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
                                        class="flex w-full min-w-0 rounded-md border border-[var(--sscevs-border)] bg-white px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--sscevs-blue)]"
                                        placeholder="Briefly explain why you left and are returning"
                                    />
                                    <InputError :message="requestForm.errors.reason" />
                                </div>
                                <div class="flex flex-col-reverse sm:flex-row gap-2">
                                    <Link href="/reactivate?reset=1" class="block sm:inline-block">
                                        <Button type="button" variant="outline" class="w-full sm:w-auto">Start over</Button>
                                    </Link>
                                    <Button type="submit" variant="navy" class="w-full sm:w-auto" :disabled="requestForm.processing">
                                        {{ requestForm.processing ? 'Submitting...' : 'Submit request' }}
                                    </Button>
                                </div>
                            </form>
                        </template>
                    </CardContent>
                </Card>
            </div>
        </main>
    </div>
</template>
