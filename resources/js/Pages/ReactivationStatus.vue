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

    <div class="guest-shell min-h-screen">
        <header class="guest-header relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="min-h-16 flex items-center justify-between gap-3 py-1.5">
                    <GuestHeaderBrand @click="closeMobileMenu" />
                    <nav class="hidden md:flex items-center gap-2">
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
                <Link href="/reactivate" class="block" @click="closeMobileMenu"
                    ><Button variant="ghost" size="sm" class="w-full justify-start">Reactivate Account</Button></Link
                >
                <Link href="/" class="block" @click="closeMobileMenu"
                    ><Button variant="ghost" size="sm" class="w-full justify-start">Home</Button></Link
                >
            </div>
        </header>

        <main class="max-w-xl mx-auto px-4 py-10 sm:py-14">
            <Card>
                <CardHeader>
                    <CardTitle>Reactivation Status</CardTitle>
                    <CardDescription>
                        Enter your reactivation number or voter ID to see if your account is active again.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-2">
                            <Label for="lookup">Reactivation number or voter ID</Label>
                            <Input
                                id="lookup"
                                v-model="form.lookup"
                                placeholder="RACT-2026-00001 or voter ID"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.lookup || errors.lookup" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button type="submit" variant="navy" :disabled="form.processing">
                                {{ form.processing ? 'Checking...' : 'Check status' }}
                            </Button>
                            <Button v-if="result" type="button" variant="outline" @click="reset">Clear</Button>
                        </div>
                    </form>

                    <div v-if="result" class="space-y-4 rounded-lg border border-[var(--sscevs-border)] p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-medium"
                                :class="badgeClass(result.status)"
                            >
                                Request: {{ statusLabel(result.status) }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-medium"
                                :class="badgeClass(result.is_active ? 'active' : result.account_status)"
                            >
                                Account: {{ result.is_active ? 'Active — you can log in' : statusLabel(result.account_status) }}
                            </span>
                        </div>

                        <dl class="grid gap-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--sscevs-muted)]">Reactivation no.</dt>
                                <dd class="font-medium">{{ result.reactivation_number }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--sscevs-muted)]">Voter ID</dt>
                                <dd class="font-medium">{{ result.voter_id_number }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--sscevs-muted)]">Full name</dt>
                                <dd class="font-medium">{{ result.full_name }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--sscevs-muted)]">Year stopped</dt>
                                <dd class="font-medium">{{ result.year_stopped }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--sscevs-muted)]">Submitted</dt>
                                <dd class="font-medium">{{ result.submitted_at }}</dd>
                            </div>
                            <div v-if="result.processed_at" class="flex justify-between gap-4">
                                <dt class="text-[var(--sscevs-muted)]">Processed</dt>
                                <dd class="font-medium">{{ result.processed_at }}</dd>
                            </div>
                            <div v-if="result.duration_years_added" class="flex justify-between gap-4">
                                <dt class="text-[var(--sscevs-muted)]">Years added</dt>
                                <dd class="font-medium">{{ result.duration_years_added }}</dd>
                            </div>
                            <div v-if="result.account_expires_at" class="flex justify-between gap-4">
                                <dt class="text-[var(--sscevs-muted)]">Account expires</dt>
                                <dd class="font-medium">{{ result.account_expires_at }}</dd>
                            </div>
                        </dl>

                        <p v-if="result.admin_notes" class="text-sm text-[var(--sscevs-muted)]">
                            Admin notes: {{ result.admin_notes }}
                        </p>

                        <Link v-if="result.is_active" href="/login">
                            <Button variant="navy" size="sm">Go to login</Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </main>
    </div>
</template>
