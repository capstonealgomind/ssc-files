<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';
import { useToast } from '@/composables/useToast';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const { error } = useToast();

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
        onError: () => {
            error(
                'Login failed',
                form.errors.email
                || form.errors.password
                || 'The credentials you entered do not match our records.',
            );
        },
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="w-full max-w-sm">
            <!-- Card -->
            <div class="rounded-xl border shadow-sm p-6 sm:p-8" style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-xl font-semibold tracking-tight mb-1" style="color: hsl(240 10% 3.9%);">Welcome back</h1>
                    <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">Enter your credentials to access your account</p>
                </div>

                <!-- Status -->
                <div v-if="status" class="mb-4 rounded-md px-3 py-2 text-sm font-medium" style="background-color: hsl(142 76% 95%); color: hsl(142 71% 25%);">
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
                            class="h-4 w-4 rounded border cursor-pointer"
                            style="border-color: hsl(240 5.9% 90%); accent-color: hsl(240 5.9% 10%);"
                        />
                        <label for="remember" class="text-sm cursor-pointer" style="color: hsl(240 3.8% 46.1%);">Remember me</label>
                    </div>

                    <!-- Submit -->
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
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
                        <span class="w-full border-t" style="border-color: hsl(240 5.9% 90%);"></span>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="px-2" style="background-color: hsl(0 0% 100%); color: hsl(240 3.8% 46.1%);">or</span>
                    </div>
                </div>

                <!-- Register link -->
                <p class="text-center text-sm" style="color: hsl(240 3.8% 46.1%);">
                    Don't have an account?
                    <Link href="/register" class="font-medium underline underline-offset-4" style="color: hsl(240 10% 3.9%);">
                        Register
                    </Link>
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
