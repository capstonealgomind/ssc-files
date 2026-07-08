<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useRegistrationCountdown } from '@/composables/useRegistrationCountdown';

const props = defineProps({
    registrationWindow: {
        type: Object,
        default: null,
    },
    variant: {
        type: String,
        default: 'default',
    },
    align: {
        type: String,
        default: 'start',
    },
});

const page = usePage();
const source = computed(() => props.registrationWindow ?? page.props.registrationWindow ?? null);

const {
    countdown,
    isScheduled,
    isClosed,
    scheduleMessage,
    startsAt,
    endsAt,
} = useRegistrationCountdown(source);

const showPanel = computed(() => isScheduled.value && (countdown.value || isClosed.value));

const alignClass = computed(() => {
    if (props.align === 'center') return 'items-center text-center';
    if (props.align === 'end') return 'items-end text-right';
    return 'items-start text-left';
});
</script>

<template>
    <div
        v-if="showPanel"
        class="flex flex-col gap-2"
        :class="[
            alignClass,
            variant === 'hero' ? 'guest-registration-countdown-hero' : '',
            variant === 'compact' ? 'guest-registration-countdown-compact' : '',
            variant === 'nav' ? 'guest-registration-countdown-nav' : '',
        ]"
    >
        <template v-if="countdown && !countdown.expired">
            <p class="guest-registration-countdown-label">
                {{ countdown.label }}
            </p>

            <div class="guest-registration-countdown-grid">
                <div class="guest-registration-countdown-unit">
                    <span class="guest-registration-countdown-value">{{ countdown.days }}</span>
                    <span class="guest-registration-countdown-caption">Days</span>
                </div>
                <div class="guest-registration-countdown-unit">
                    <span class="guest-registration-countdown-value">{{ String(countdown.hours).padStart(2, '0') }}</span>
                    <span class="guest-registration-countdown-caption">Hours</span>
                </div>
                <div class="guest-registration-countdown-unit">
                    <span class="guest-registration-countdown-value">{{ String(countdown.minutes).padStart(2, '0') }}</span>
                    <span class="guest-registration-countdown-caption">Mins</span>
                </div>
                <div class="guest-registration-countdown-unit">
                    <span class="guest-registration-countdown-value">{{ String(countdown.seconds).padStart(2, '0') }}</span>
                    <span class="guest-registration-countdown-caption">Secs</span>
                </div>
            </div>

            <p v-if="countdown.targetLabel" class="guest-registration-countdown-date">
                {{ countdown.status === 'upcoming' ? 'Opens' : 'Closes' }}:
                {{ countdown.targetLabel }}
            </p>
        </template>

        <template v-else-if="isClosed">
            <p class="guest-registration-countdown-label">Registration closed</p>
            <p v-if="endsAt" class="guest-registration-countdown-date">
                Closed on {{ endsAt }}
            </p>
            <p v-else-if="scheduleMessage" class="guest-registration-countdown-date">
                {{ scheduleMessage }}
            </p>
        </template>

        <template v-else-if="startsAt || endsAt">
            <p class="guest-registration-countdown-date">
                <span v-if="startsAt">Opens: {{ startsAt }}</span>
                <span v-if="startsAt && endsAt"> · </span>
                <span v-if="endsAt">Closes: {{ endsAt }}</span>
            </p>
        </template>
    </div>
</template>
