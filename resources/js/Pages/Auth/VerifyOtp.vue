<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import InputError from '@/Components/ui/InputError.vue';

const props = defineProps({
    maskedEmail:   { type: String, default: '' },
    expiryMinutes: { type: Number, default: 10 },
    emailMismatch: { type: Boolean, default: false },
    officialEmailMissing: { type: Boolean, default: false },
});

const page = usePage();
const flashStatus = computed(() => page.props.flash?.status ?? null);
const resendError = ref('');

// ── OTP digit inputs (6 boxes) ─────────────────────────────────────────────
const digits   = ref(['', '', '', '', '', '']);
const inputRefs = ref([]);

const otpValue = computed(() => digits.value.join(''));

const form = useForm({ otp: '' });
const emailForm = useForm({ email: '' });

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

function updateEmail() {
    if (!emailForm.email || emailForm.processing) return;

    emailForm.post('/register/update-email', {
        onSuccess() {
            emailForm.reset();
            secondsLeft.value = props.expiryMinutes * 60;
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

    resendError.value = '';
    router.post('/register/resend-otp', {}, {
        onError(errors) { resendError.value = errors.otp ?? 'Could not resend the code.'; },
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
            <div class="relative mx-auto mb-6 grid w-full max-w-xs grid-cols-2">
                <div class="absolute left-1/4 right-1/4 top-4 z-0 h-px guest-step-line-active"></div>
                <div class="relative z-10 flex min-w-0 flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold guest-step-done">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="mt-1 w-full px-1 text-center text-xs guest-muted">Document &amp; Student Details</span>
                </div>
                <div class="relative z-10 flex min-w-0 flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold guest-step-active">2</div>
                    <span class="mt-1 w-full px-1 text-center text-xs font-medium guest-title">Verify Email</span>
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

                <InputError :message="form.errors.otp || resendError" class="text-center mb-4" />

                <div v-if="emailMismatch && !officialEmailMissing" class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4">
                    <p class="mb-3 text-sm text-amber-900">
                        Wrong email account. Enter the email address listed in your official student record to receive a new verification code. If you no longer have access to that email, please visit the administrator to update your record.
                    </p>
                    <form class="space-y-2" @submit.prevent="updateEmail">
                        <input
                            v-model="emailForm.email"
                            type="email"
                            autocomplete="email"
                            placeholder="Email on your official student record"
                            class="w-full rounded-md border border-[var(--sscevs-border)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--sscevs-blue)]"
                        />
                        <InputError :message="emailForm.errors.email" />
                        <Button type="submit" class="w-full" :disabled="emailForm.processing || !emailForm.email">
                            {{ emailForm.processing ? 'Sending code...' : 'Use this email and send a new code' }}
                        </Button>
                    </form>
                </div>

                <div v-else-if="officialEmailMissing" class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                    Your official student record has no email address. Please visit the administrator to update your record before continuing registration.
                </div>

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
