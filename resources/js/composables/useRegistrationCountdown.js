import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

function countdownParts(milliseconds) {
    if (milliseconds <= 0) {
        return {
            days: 0,
            hours: 0,
            minutes: 0,
            seconds: 0,
            expired: true,
        };
    }

    const totalSeconds = Math.floor(milliseconds / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return { days, hours, minutes, seconds, expired: false };
}

export function useRegistrationCountdown(registrationWindow) {
    const now = ref(Date.now());
    let timer = null;
    let reloaded = false;

    onMounted(() => {
        timer = window.setInterval(() => {
            now.value = Date.now();
        }, 1000);
    });

    onUnmounted(() => {
        if (timer) {
            window.clearInterval(timer);
        }
    });

    const windowData = computed(() => {
        if (typeof registrationWindow === 'function') {
            return registrationWindow();
        }

        return registrationWindow?.value ?? registrationWindow ?? null;
    });

    const countdown = computed(() => {
        const data = windowData.value;

        if (!data?.show_countdown || !data.countdown_to_iso) {
            return null;
        }

        const targetMs = new Date(data.countdown_to_iso).getTime();
        const parts = countdownParts(targetMs - now.value);

        return {
            ...parts,
            label: data.countdown_label,
            targetLabel: data.status === 'upcoming' ? data.starts_at : data.ends_at,
            status: data.status,
        };
    });

    const isScheduled = computed(() => Boolean(windowData.value?.is_scheduled));
    const isClosed = computed(() => windowData.value?.status === 'closed');
    const isUpcoming = computed(() => windowData.value?.status === 'upcoming');
    const scheduleMessage = computed(() => windowData.value?.message ?? null);
    const startsAt = computed(() => windowData.value?.starts_at ?? null);
    const endsAt = computed(() => windowData.value?.ends_at ?? null);

    watch(
        () => countdown.value?.expired,
        (expired) => {
            if (expired && !reloaded) {
                reloaded = true;
                router.reload({ preserveScroll: true });
            }
        },
    );

    return {
        countdown,
        isScheduled,
        isClosed,
        isUpcoming,
        scheduleMessage,
        startsAt,
        endsAt,
        windowData,
    };
}
