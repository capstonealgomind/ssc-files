<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';
import { useToast } from '@/composables/useToast';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const { error } = useToast();

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: () => {
            error(
                'Registration failed',
                Object.values(form.errors)[0]
                || 'Please review your details and try again.',
            );
        },
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="w-full max-w-sm">
            <!-- Card -->
            <div class="rounded-xl border shadow-sm p-6 sm:p-8" style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-xl font-semibold tracking-tight mb-1" style="color: hsl(240 10% 3.9%);">Create an account</h1>
                    <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">Register as a voter using your normal email address</p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Full Name -->
                    <div class="space-y-1.5">
                        <Label html-for="name">Full name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Juan dela Cruz"
                            autocomplete="name"
                            :error="!!form.errors.name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

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
                            autocomplete="new-password"
                            :error="!!form.errors.password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-1.5">
                        <Label html-for="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="new-password"
                            :error="!!form.errors.password_confirmation"
                        />
                        <InputError :message="form.errors.password_confirmation" />
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
                        {{ form.processing ? 'Creating account...' : 'Create account' }}
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

                <!-- Login link -->
                <p class="text-center text-sm" style="color: hsl(240 3.8% 46.1%);">
                    Already have an account?
                    <Link href="/login" class="font-medium underline underline-offset-4" style="color: hsl(240 10% 3.9%);">
                        Sign in
                    </Link>
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
