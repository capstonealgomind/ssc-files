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
    voterIdNumber: String,
});
</script>

<template>
    <Head title="Registration Successful" />

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
                            <div class="h-14 w-14 rounded-full flex items-center justify-center guest-success-surface">
                                <svg class="h-7 w-7 text-[var(--sscevs-blue)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                        <CardTitle class="text-xl">Registration submitted</CardTitle>
                        <CardDescription class="mt-2">
                            Your voter registration has been saved. Verify your email to continue.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <!-- Voter ID -->
                        <div class="rounded-lg border px-4 py-3 text-center guest-surface">
                            <p class="text-xs font-medium uppercase tracking-wide mb-1 guest-muted">
                                Your Voter ID
                            </p>
                            <p class="text-xl font-bold font-mono select-all guest-title">
                                {{ voterIdNumber }}
                            </p>
                            <p class="text-xs mt-2 guest-muted">
                                Save this ID to check your registration status later.
                            </p>
                        </div>

                        <!-- Email notice -->
                        <div class="guest-card p-4 flex gap-3">
                            <div class="h-8 w-8 shrink-0 rounded-full flex items-center justify-center guest-feature-icon">
                                <svg class="h-4 w-4 text-[var(--sscevs-blue)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium guest-title">Check your email</p>
                                <p class="text-xs mt-1 leading-relaxed guest-muted">
                                    We sent a verification link and 6-digit code. Click the link to enter your code and complete verification.
                                </p>
                            </div>
                        </div>

                        <!-- Steps -->
                        <ol class="space-y-2 text-xs guest-muted">
                            <li class="flex items-start gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold guest-step-active">1</span>
                                Open the verification email
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold guest-step-active">2</span>
                                Click "Verify email address"
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold guest-step-active">3</span>
                                Enter the 6-digit code
                            </li>
                        </ol>

                        <!-- Actions -->
                        <div class="flex flex-col gap-2 pt-2">
                            <Link href="/register/verify-otp">
                                <Button class="w-full">Enter verification code</Button>
                            </Link>
                            <Link href="/check-status">
                                <Button variant="outline" class="w-full">Check my status</Button>
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
