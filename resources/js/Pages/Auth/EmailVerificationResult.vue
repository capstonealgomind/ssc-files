<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Button from '@/Components/ui/Button.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardDescription from '@/Components/ui/CardDescription.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import GuestHeaderBrand from '@/Components/GuestHeaderBrand.vue';

defineProps({
    success: { type: Boolean, default: false },
    message: { type: String, default: '' },
    alreadyVerified: { type: Boolean, default: false },
    voterIdNumber: { type: String, default: null },
});
</script>

<template>
    <Head title="Email Verification" />

    <div class="guest-shell">
        <header class="guest-header">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="min-h-16 flex items-center justify-between gap-3 py-1.5">
                    <GuestHeaderBrand />
                </div>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center p-4 py-12 bg-white">
            <div class="w-full max-w-md">
                <Card>
                    <CardHeader class="text-center pb-2">
                        <div class="flex justify-center mb-4">
                            <div
                                v-if="success"
                                class="h-14 w-14 rounded-full flex items-center justify-center guest-success-surface"
                            >
                                <svg class="h-7 w-7 text-[var(--sscevs-blue)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div
                                v-else
                                class="h-14 w-14 rounded-full flex items-center justify-center"
                                style="background-color: hsl(0 84% 60% / 0.1)"
                            >
                                <svg class="h-7 w-7 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </div>

                        <CardTitle class="text-xl">
                            {{ success ? (alreadyVerified ? 'Already verified' : 'Email verified') : 'Verification failed' }}
                        </CardTitle>
                        <CardDescription class="mt-2">{{ message }}</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div
                            v-if="success && voterIdNumber"
                            class="rounded-lg border px-4 py-3 text-center guest-surface"
                        >
                            <p class="text-xs font-medium uppercase tracking-wide mb-1 guest-muted">
                                Your Voter ID
                            </p>
                            <p class="text-lg font-bold font-mono guest-title">
                                {{ voterIdNumber }}
                            </p>
                        </div>

                        <div
                            v-if="success && !alreadyVerified"
                            class="rounded-lg border p-4 space-y-2 guest-gold-surface"
                        >
                            <p class="text-sm font-medium guest-title">What happens next</p>
                            <ul class="text-xs space-y-1.5 guest-muted">
                                <li class="flex items-start gap-2">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--sscevs-gold)]" />
                                    Your school ID is being processed
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--sscevs-gold)]" />
                                    Your registration will be reviewed for approval
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--sscevs-gold)]" />
                                    You can log in once your account is approved
                                </li>
                            </ul>
                        </div>

                        <div class="flex flex-col gap-2 pt-2">
                            <Link v-if="success" href="/check-status">
                                <Button class="w-full">Check my status</Button>
                            </Link>
                            <Link href="/">
                                <Button variant="outline" class="w-full">Return to home</Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </main>

        <footer class="guest-footer py-6 px-4 text-center bg-white">
            <p class="text-xs guest-muted">
                &copy; {{ new Date().getFullYear() }} SSCEVS. All rights reserved.
            </p>
        </footer>
    </div>
</template>
