<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';
import CloudflareTurnstile from '@/Components/CloudflareTurnstile.vue';
import { useToast } from '@/composables/useToast';
import { useRegistrationWindow } from '@/composables/useRegistrationWindow';

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
    turnstileSiteKey: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    turnstile_token: '',
});

const { error } = useToast();
const { isRegistrationOpen } = useRegistrationWindow();
const turnstileResetKey = ref(0);
const turnstileLoadError = ref('');
const turnstileEnabled = Boolean(props.turnstileSiteKey);

function onTurnstileToken(token) {
    form.turnstile_token = token;
    turnstileLoadError.value = '';
}

function onTurnstileError(errorCode) {
    form.turnstile_token = '';

    if (String(errorCode) === '400020') {
        turnstileLoadError.value = 'Cloudflare could not verify this page. Add localhost and 127.0.0.1 under Hostname Management in your Cloudflare Turnstile widget.';
        return;
    }

    turnstileLoadError.value = 'Security verification failed to load. Please refresh the page and try again.';
}

function resetTurnstile() {
    form.turnstile_token = '';
    turnstileLoadError.value = '';
    turnstileResetKey.value += 1;
}

function submit() {
    if (turnstileEnabled && !form.turnstile_token) {
        error('Verification required', 'Please complete the security check before signing in.');
        return;
    }

    form.post('/login', {
        onFinish: () => {
            form.reset('password', 'turnstile_token');
            resetTurnstile();
        },
        onError: () => {
            resetTurnstile();
            error(
                'Login failed',
                form.errors.email
                || form.errors.password
                || form.errors.turnstile_token
                || 'The credentials you entered do not match our records.',
            );
        },
    });
}
</script>

<template>
    <GuestLayout show-back-home>
        <Head title="Log in" />

        <div class="w-full max-w-sm">
            <!-- Card -->
            <div class="guest-card p-6 sm:p-8">
                <div class="mb-6">
                    <h1 class="text-xl font-semibold tracking-tight mb-1 guest-title">Welcome back</h1>
                    <p class="text-sm guest-muted">Enter your credentials to access your account</p>
                </div>

                <div v-if="status" class="mb-4 rounded-md px-3 py-2 text-sm font-medium guest-success-surface">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Email -->
                    <div class="space-y-1.5">
                        <Label html-for="email">Email address</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="you@example.com"
                            autocomplete="email"
                            :error="!!form.errors.email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <Label html-for="password">Password</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            :error="!!form.errors.password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Remember me -->
                    <div class="flex items-center gap-2">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border border-[var(--sscevs-border)] cursor-pointer accent-[var(--sscevs-blue)]"
                        />
                        <label for="remember" class="text-sm cursor-pointer guest-muted">Remember me</label>
                    </div>

                    <div v-if="turnstileEnabled" class="space-y-1.5">
                        <CloudflareTurnstile
                            :site-key="turnstileSiteKey"
                            :reset-key="turnstileResetKey"
                            @token="onTurnstileToken"
                            @error="onTurnstileError"
                            @expired="form.turnstile_token = ''"
                        />
                        <InputError :message="form.errors.turnstile_token || turnstileLoadError" />
                    </div>

                    <!-- Submit -->
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing || (turnstileEnabled && !form.turnstile_token)"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ form.processing ? 'Signing in...' : 'Sign in' }}
                    </Button>
                </form>

                <!-- Divider -->
                <div class="relative my-5">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-[var(--sscevs-border)]"></span>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="px-2 bg-white guest-muted">or</span>
                    </div>
                </div>

                <!-- Register link -->
                <p class="text-center text-sm guest-muted">
                    Don't have an account?
                    <Link
                        v-if="isRegistrationOpen"
                        href="/register"
                        class="guest-link underline underline-offset-4"
                    >
                        Register
                    </Link>
                    <span
                        v-else
                        class="guest-link guest-link-disabled underline underline-offset-4"
                        aria-disabled="true"
                    >
                        Register
                    </span>
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
