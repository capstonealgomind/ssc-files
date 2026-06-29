<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import InputError from '@/Components/ui/InputError.vue';

const props = defineProps({
    maskedEmail:   { type: String, default: '' },
    expiryMinutes: { type: Number, default: 10 },
});

const page = usePage();
const flashStatus = computed(() => page.props.flash?.status ?? null);

// ── OTP digit inputs (6 boxes) ─────────────────────────────────────────────
const digits   = ref(['', '', '', '', '', '']);
const inputRefs = ref([]);

const otpValue = computed(() => digits.value.join(''));

const form = useForm({ otp: '' });

watch(otpValue, (v) => {
    form.otp = v;
});

function onDigitInput(index, event) {
    const val = event.target.value.replace(/\D/g, '').slice(-1);
    digits.value[index] = val;

    if (val && index < 5) {
        inputRefs.value[index + 1]?.focus();
    }

    if (otpValue.value.length === 6) {
        submit();
    }
}

function onKeydown(index, event) {
    if (event.key === 'Backspace') {
        if (digits.value[index] === '' && index > 0) {
            digits.value[index - 1] = '';
            inputRefs.value[index - 1]?.focus();
        } else {
            digits.value[index] = '';
        }
    }

    if (event.key === 'ArrowLeft' && index > 0) {
        inputRefs.value[index - 1]?.focus();
    }

    if (event.key === 'ArrowRight' && index < 5) {
        inputRefs.value[index + 1]?.focus();
    }
}

function onPaste(event) {
    event.preventDefault();
    const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    pasted.split('').forEach((char, i) => {
        digits.value[i] = char;
    });
    const nextIndex = Math.min(pasted.length, 5);
    inputRefs.value[nextIndex]?.focus();

    if (pasted.length === 6) {
        submit();
    }
}

// ── Countdown timer ────────────────────────────────────────────────────────
const secondsLeft  = ref(props.expiryMinutes * 60);
const timerExpired = computed(() => secondsLeft.value <= 0);

const timerLabel = computed(() => {
    if (timerExpired.value) return 'Code expired';
    const m = Math.floor(secondsLeft.value / 60);
    const s = secondsLeft.value % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

let timerInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        if (secondsLeft.value > 0) {
            secondsLeft.value--;
        } else {
            clearInterval(timerInterval);
        }
    }, 1000);

    inputRefs.value[0]?.focus();
});

// ── Submit ─────────────────────────────────────────────────────────────────
function submit() {
    if (otpValue.value.length !== 6 || form.processing) return;

    form.otp = otpValue.value;
    form.post('/register/verify-otp', {
        onError() {
            // Clear all digits on error
            digits.value = ['', '', '', '', '', ''];
            inputRefs.value[0]?.focus();
        },
    });
}

// ── Resend ─────────────────────────────────────────────────────────────────
const resendCooldown = ref(0);
const canResend      = computed(() => resendCooldown.value === 0);

let cooldownInterval = null;

function resend() {
    if (!canResend.value) return;

    router.post('/register/resend-otp', {}, {
        onSuccess() {
            secondsLeft.value  = props.expiryMinutes * 60;
            resendCooldown.value = 60;
            digits.value = ['', '', '', '', '', ''];
            inputRefs.value[0]?.focus();

            clearInterval(cooldownInterval);
            cooldownInterval = setInterval(() => {
                if (resendCooldown.value > 0) {
                    resendCooldown.value--;
                } else {
                    clearInterval(cooldownInterval);
                }
            }, 1000);
        },
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Verify Email" />

        <div class="w-full max-w-sm">
            <!-- Step indicator -->
            <div class="flex items-center justify-center gap-0 mb-6">
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold guest-step-done">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-xs mt-1 guest-muted">Your info</span>
                </div>
                <div class="h-px w-10 mb-4 guest-step-line-active"></div>
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold guest-step-done">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-xs mt-1 guest-muted">Scan ID</span>
                </div>
                <div class="h-px w-10 mb-4 guest-step-line-active"></div>
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold guest-step-active">3</div>
                    <span class="text-xs mt-1 font-medium guest-title">Verify</span>
                </div>
            </div>

            <div class="guest-card p-6 sm:p-8">
                <div class="flex justify-center mb-5">
                    <div class="h-14 w-14 rounded-full flex items-center justify-center guest-feature-icon">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>

                <div class="text-center mb-6">
                    <h1 class="text-xl font-semibold tracking-tight mb-1 guest-title">Check your email</h1>
                    <p class="text-sm guest-muted">
                        A verification code is being sent to
                        <span class="font-medium guest-title">{{ maskedEmail }}</span>.
                        Enter it below once it arrives.
                    </p>
                    <p class="text-xs mt-2 guest-muted">
                        Check Spam or Promotions if you don't see it, then mark it as "Not spam".
                    </p>
                </div>

                <!-- OTP digit inputs -->
                <div class="flex justify-center gap-2 mb-5">
                    <input
                        v-for="(_, i) in 6"
                        :key="i"
                        :ref="el => inputRefs[i] = el"
                        :value="digits[i]"
                        maxlength="1"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        class="h-12 w-10 text-center text-lg font-semibold rounded-md border transition-colors focus:outline-none focus:ring-2 guest-title"
                        :class="form.errors.otp
                            ? 'border-destructive focus:ring-destructive'
                            : 'border-[var(--sscevs-border)] focus:ring-[var(--sscevs-blue)]'"
                        @input="onDigitInput(i, $event)"
                        @keydown="onKeydown(i, $event)"
                        @paste="onPaste"
                    />
                </div>

                <InputError :message="form.errors.otp" class="text-center mb-4" />

                <p
                    v-if="flashStatus"
                    class="text-xs text-center mb-4 font-medium text-[var(--sscevs-blue-dark)]"
                >
                    {{ flashStatus }}
                </p>

                <!-- Timer -->
                <div class="flex items-center justify-center gap-1.5 mb-5 text-sm" :class="timerExpired ? 'text-destructive' : 'guest-muted'">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ timerLabel }}</span>
                </div>

                <!-- Submit button -->
                <Button
                    type="button"
                    class="w-full mb-4"
                    :disabled="otpValue.length !== 6 || form.processing"
                    @click="submit"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ form.processing ? 'Verifying…' : 'Verify email' }}
                </Button>

                <div class="text-center">
                    <p class="text-sm guest-muted">
                        Didn't receive the code?
                        <button
                            v-if="canResend"
                            type="button"
                            class="guest-link underline underline-offset-4 transition-colors"
                            @click="resend"
                        >
                            Resend code
                        </button>
                        <span v-else class="guest-muted">
                            Resend in {{ resendCooldown }}s
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
