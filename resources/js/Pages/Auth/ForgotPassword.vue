<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';

const form = useForm({
    email: '',
});

function submit() {
    form.post('/forgot-password');
}
</script>

<template>
    <GuestLayout show-back-home>
        <Head title="Forgot password" />

        <div class="w-full max-w-sm">
            <div class="guest-card p-6 sm:p-8">
                <div class="mb-6">
                    <h1 class="text-xl font-semibold tracking-tight mb-1 guest-title">Forgot password</h1>
                    <p class="text-sm guest-muted">Enter the email on your account. We will send a code if it is registered.</p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
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

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        {{ form.processing ? 'Checking...' : 'Send code' }}
                    </Button>
                </form>

                <p class="mt-5 text-center text-sm guest-muted">
                    <Link href="/login" class="guest-link underline underline-offset-4">Back to sign in</Link>
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
