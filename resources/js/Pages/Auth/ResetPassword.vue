<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmation = ref(false);

const passwordRules = computed(() => {
    const value = form.password || '';

    return [
        { key: 'upper', label: 'One uppercase letter (A–Z)', ok: /[A-Z]/.test(value) },
        { key: 'lower', label: 'One lowercase letter (a–z)', ok: /[a-z]/.test(value) },
        { key: 'number', label: 'One number (0–9)', ok: /[0-9]/.test(value) },
        { key: 'symbol', label: 'One symbol (!@#$%^&*…)', ok: /[^A-Za-z0-9]/.test(value) },
    ];
});

const passwordIsStrong = computed(() => passwordRules.value.every((rule) => rule.ok));

function submit() {
    if (!passwordIsStrong.value) {
        form.setError('password', 'Password must include uppercase letters, lowercase letters, a number, and a symbol.');
        return;
    }

    form.post('/forgot-password/reset');
}
</script>

<template>
    <GuestLayout show-back-home>
        <Head title="New password" />

        <div class="w-full max-w-sm">
            <div class="guest-card p-6 sm:p-8">
                <div class="mb-6">
                    <h1 class="text-xl font-semibold tracking-tight mb-1 guest-title">Choose a new password</h1>
                    <p class="text-sm guest-muted">After you save it, you will return to the sign-in page.</p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-1.5">
                        <Label html-for="password">New password</Label>
                        <div class="relative">
                            <Input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                class="pr-10"
                                :error="!!form.errors.password"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center px-3 guest-muted"
                                @click="showPassword = !showPassword"
                            >
                                {{ showPassword ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <ul class="space-y-1 text-xs">
                            <li
                                v-for="rule in passwordRules"
                                :key="rule.key"
                                :style="{ color: rule.ok ? 'hsl(142 71% 32%)' : 'hsl(215 15% 38%)' }"
                            >
                                {{ rule.label }}
                            </li>
                        </ul>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="space-y-1.5">
                        <Label html-for="password_confirmation">Confirm password</Label>
                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showConfirmation ? 'text' : 'password'"
                                autocomplete="new-password"
                                class="pr-10"
                                :error="!!form.errors.password_confirmation"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center px-3 guest-muted"
                                @click="showConfirmation = !showConfirmation"
                            >
                                {{ showConfirmation ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing || !passwordIsStrong">
                        {{ form.processing ? 'Saving...' : 'Save password' }}
                    </Button>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
