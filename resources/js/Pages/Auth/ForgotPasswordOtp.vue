<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import InputError from '@/Components/ui/InputError.vue';

const props = defineProps({
    maskedEmail: { type: String, default: '' },
    expiryMinutes: { type: Number, default: 10 },
});

const digits = ref(['', '', '', '', '', '']);
const inputRefs = ref([]);
const form = useForm({ otp: '' });
const secondsLeft = ref(props.expiryMinutes * 60);
const resendCooldown = ref(0);

const otpValue = computed(() => digits.value.join(''));
const timerExpired = computed(() => secondsLeft.value <= 0);
const timerLabel = computed(() => {
    if (timerExpired.value) {
        return 'Code expired';
    }

    const minutes = Math.floor(secondsLeft.value / 60);
    const seconds = secondsLeft.value % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

let timerInterval = null;
let cooldownInterval = null;

watch(otpValue, (value) => {
    form.otp = value;
});

function onDigitInput(index, event) {
    const value = event.target.value.replace(/\D/g, '').slice(-1);
    digits.value[index] = value;

    if (value && index < 5) {
        inputRefs.value[index + 1]?.focus();
    }

    if (otpValue.value.length === 6) {
        submit();
    }
}

function onKeydown(index, event) {
    if (event.key === 'Backspace' && digits.value[index] === '' && index > 0) {
        digits.value[index - 1] = '';
        inputRefs.value[index - 1]?.focus();
    }
}

function onPaste(event) {
    event.preventDefault();
    const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    pasted.split('').forEach((char, index) => {
        digits.value[index] = char;
    });

    if (pasted.length === 6) {
        submit();
    }
}

function submit() {
    if (otpValue.value.length !== 6 || form.processing) {
        return;
    }

    form.otp = otpValue.value;
    form.post('/forgot-password/verify', {
        onError() {
            digits.value = ['', '', '', '', '', ''];
            inputRefs.value[0]?.focus();
        },
    });
}

function resend() {
    if (resendCooldown.value > 0) {
        return;
    }

    router.post('/forgot-password/resend', {}, {
        preserveScroll: true,
        onSuccess() {
            secondsLeft.value = props.expiryMinutes * 60;
            resendCooldown.value = 60;
            digits.value = ['', '', '', '', '', ''];
            inputRefs.value[0]?.focus();
            clearInterval(cooldownInterval);
            cooldownInterval = setInterval(() => {
                if (resendCooldown.value > 0) {
                    resendCooldown.value -= 1;
                } else {
                    clearInterval(cooldownInterval);
                }
            }, 1000);
        },
    });
}

onMounted(() => {
    timerInterval = setInterval(() => {
        if (secondsLeft.value > 0) {
            secondsLeft.value -= 1;
        }
    }, 1000);
    inputRefs.value[0]?.focus();
});

onUnmounted(() => {
    clearInterval(timerInterval);
    clearInterval(cooldownInterval);
});
</script>

<template>
    <GuestLayout show-back-home>
        <Head title="Enter code" />

        <div class="w-full max-w-sm">
            <div class="guest-card p-6 sm:p-8">
                <div class="mb-6">
                    <h1 class="text-xl font-semibold tracking-tight mb-1 guest-title">Enter the code</h1>
                    <p class="text-sm guest-muted">
                        We sent a 6-digit code to {{ maskedEmail }}. It expires in {{ timerLabel }}.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="flex justify-between gap-2" @paste="onPaste">
                        <input
                            v-for="(digit, index) in digits"
                            :key="index"
                            :ref="(el) => { inputRefs[index] = el }"
                            :value="digit"
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            class="h-12 w-11 rounded-md border text-center text-lg font-semibold"
                            style="border-color: var(--sscevs-border);"
                            :aria-label="`Digit ${index + 1}`"
                            @input="onDigitInput(index, $event)"
                            @keydown="onKeydown(index, $event)"
                        />
                    </div>
                    <InputError :message="form.errors.otp" />

                    <Button type="submit" class="w-full" :disabled="form.processing || otpValue.length !== 6">
                        {{ form.processing ? 'Checking...' : 'Verify code' }}
                    </Button>
                </form>

                <div class="mt-5 flex items-center justify-between text-sm">
                    <Link href="/forgot-password" class="guest-link underline underline-offset-4">Use a different email</Link>
                    <button
                        type="button"
                        class="guest-link underline underline-offset-4 disabled:opacity-50"
                        :disabled="resendCooldown > 0"
                        @click="resend"
                    >
                        {{ resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend code' }}
                    </button>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
