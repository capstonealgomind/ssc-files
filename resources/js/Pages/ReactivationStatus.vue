<script setup>
import { ref } from 'vue';
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
    result: {
        type: Object,
        default: null,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    lookup: '',
});

const mobileMenuOpen = ref(false);

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}

function submit() {
    form.post('/reactivation-status', { preserveScroll: true });
}

function reset() {
    form.reset();
    form.clearErrors();
    router.visit('/reactivation-status');
}

function statusLabel(value) {
    const labels = {
        pending: 'Pending review',
        approved: 'Approved',
        rejected: 'Rejected',
        active: 'Active',
        expired: 'Expired',
        pending_reactivation: 'Pending reactivation',
    };
    return labels[value] ?? value;
}

function badgeClass(value) {
    if (value === 'approved' || value === 'active' || value === true) {
        return 'bg-[var(--sscevs-blue-light)] text-[var(--sscevs-blue-dark)] border-[var(--sscevs-blue)]/25';
    }
    if (value === 'pending' || value === 'pending_reactivation') {
        return 'bg-[var(--sscevs-gold-light)] text-[var(--sscevs-gold-dark)] border-[var(--sscevs-gold)]/35';
    }
    if (value === 'rejected' || value === 'expired') {
        return 'bg-[hsl(0_84%_60%/0.1)] text-[hsl(0_72%_51%)] border-[hsl(0_84%_60%/0.2)]';
    }
    return 'bg-white text-[var(--sscevs-muted)] border-[var(--sscevs-border)]';
}
</script>

<template>
    <Head title="Reactivation Status" />

    <div class="guest-shell">
        <header class="guest-header relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="min-h-16 flex items-center justify-between gap-3 py-1.5">
                    <GuestHeaderBrand @click="closeMobileMenu" />
                    <nav class="guest-nav-links">
                        <Link href="/reactivate"
                            ><Button variant="ghost" size="sm">Reactivate Account</Button></Link
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
                    <Link href="/reactivate" class="block" @click="closeMobileMenu">
                        <Button variant="ghost" size="sm" class="w-full justify-start">Reactivate Account</Button>
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
                        Reactivation Status
                    </h1>
                    <p class="text-sm guest-muted leading-relaxed">
                        Enter your reactivation number or voter ID to see if your account is active again.
                    </p>
                </div>

                <Card class="overflow-hidden">
                    <CardHeader class="px-4 sm:px-6">
                        <CardTitle class="text-lg sm:text-xl">Lookup</CardTitle>
                        <CardDescription>
                            Example: RACT-2026-00001 or your voter ID.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5 sm:space-y-6 px-4 sm:px-6">
                        <form class="space-y-4" @submit.prevent="submit">
                            <div class="space-y-2">
                                <Label for="lookup">Reactivation number or voter ID</Label>
                                <Input
                                    id="lookup"
                                    v-model="form.lookup"
                                    class="w-full min-w-0"
                                    placeholder="RACT-2026-00001 or voter ID"
                                    autocomplete="off"
                                />
                                <InputError :message="form.errors.lookup || errors.lookup" />
                            </div>
                            <div class="flex flex-col-reverse sm:flex-row gap-2">
                                <Button
                                    v-if="result"
                                    type="button"
                                    variant="outline"
                                    class="w-full sm:w-auto"
                                    @click="reset"
                                >
                                    Clear
                                </Button>
                                <Button type="submit" variant="navy" class="w-full sm:w-auto" :disabled="form.processing">
                                    {{ form.processing ? 'Checking...' : 'Check status' }}
                                </Button>
                            </div>
                        </form>

                        <div
                            v-if="result"
                            class="space-y-4 rounded-lg border border-[var(--sscevs-border)] p-3 sm:p-4 min-w-0"
                        >
                            <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2">
                                <span
                                    class="inline-flex w-fit max-w-full items-center rounded-md border px-2.5 py-0.5 text-xs font-medium break-words"
                                    :class="badgeClass(result.status)"
                                >
                                    Request: {{ statusLabel(result.status) }}
                                </span>
                                <span
                                    class="inline-flex w-fit max-w-full items-center rounded-md border px-2.5 py-0.5 text-xs font-medium break-words"
                                    :class="badgeClass(result.is_active ? 'active' : result.account_status)"
                                >
                                    Account: {{ result.is_active ? 'Active — you can log in' : statusLabel(result.account_status) }}
                                </span>
                            </div>

                            <dl class="grid gap-3 text-sm min-w-0">
                                <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-4 min-w-0">
                                    <dt class="text-[var(--sscevs-muted)] shrink-0">Reactivation no.</dt>
                                    <dd class="font-medium font-mono break-all sm:text-right">{{ result.reactivation_number }}</dd>
                                </div>
                                <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-4 min-w-0">
                                    <dt class="text-[var(--sscevs-muted)] shrink-0">Voter ID</dt>
                                    <dd class="font-medium font-mono break-all sm:text-right">{{ result.voter_id_number }}</dd>
                                </div>
                                <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-4 min-w-0">
                                    <dt class="text-[var(--sscevs-muted)] shrink-0">Full name</dt>
                                    <dd class="font-medium break-words sm:text-right">{{ result.full_name }}</dd>
                                </div>
                                <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-4 min-w-0">
                                    <dt class="text-[var(--sscevs-muted)] shrink-0">Year stopped</dt>
                                    <dd class="font-medium sm:text-right">{{ result.year_stopped }}</dd>
                                </div>
                                <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-4 min-w-0">
                                    <dt class="text-[var(--sscevs-muted)] shrink-0">Submitted</dt>
                                    <dd class="font-medium sm:text-right">{{ result.submitted_at }}</dd>
                                </div>
                                <div
                                    v-if="result.processed_at"
                                    class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-4 min-w-0"
                                >
                                    <dt class="text-[var(--sscevs-muted)] shrink-0">Processed</dt>
                                    <dd class="font-medium sm:text-right">{{ result.processed_at }}</dd>
                                </div>
                                <div
                                    v-if="result.duration_years_added"
                                    class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-4 min-w-0"
                                >
                                    <dt class="text-[var(--sscevs-muted)] shrink-0">Years added</dt>
                                    <dd class="font-medium sm:text-right">{{ result.duration_years_added }}</dd>
                                </div>
                                <div
                                    v-if="result.account_expires_at"
                                    class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-4 min-w-0"
                                >
                                    <dt class="text-[var(--sscevs-muted)] shrink-0">Account expires</dt>
                                    <dd class="font-medium sm:text-right">{{ result.account_expires_at }}</dd>
                                </div>
                            </dl>

                            <p v-if="result.admin_notes" class="text-sm text-[var(--sscevs-muted)] break-words leading-relaxed">
                                Admin notes: {{ result.admin_notes }}
                            </p>

                            <Link v-if="result.is_active" href="/login" class="block sm:inline-block">
                                <Button variant="navy" size="sm" class="w-full sm:w-auto">Go to login</Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </main>
    </div>
</template>
