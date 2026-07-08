import { computed, onUnmounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const DISCRETE_ACTIVITY_EVENTS = [
    'click',
    'pointerdown',
    'keydown',
    'touchstart',
];

const CONTINUOUS_ACTIVITY_EVENTS = [
    'mousemove',
    'pointermove',
];

const ACTIVITY_LISTENER_OPTIONS = { passive: true, capture: false };
const CONTINUOUS_ACTIVITY_PULSE_MS = 250;
const POST_CONFIRM_GRACE_MS = 3000;
const MOUSE_MOVE_THRESHOLD_PX = 10;
const COUNTDOWN_BEEP_URL = '/sound/u_edtmwfwu7c-beep-329314.mp3';
const COUNTDOWN_TICK_MS = 1000;

function resolveSeconds(value, fallback) {
    const seconds = Number(value);

    if (!Number.isFinite(seconds) || seconds <= 0) {
        return fallback;
    }

    return seconds;
}

export function useVoterInactivityLogout(isVoter) {
    const page = usePage();

    const uaManagement = computed(() => page.props.uaManagement ?? null);

    const isTrackingEnabled = computed(() => {
        if (!isVoter.value) {
            return false;
        }

        return Boolean(uaManagement.value?.is_enabled);
    });

    const warningSeconds = computed(() => resolveSeconds(uaManagement.value?.countdown_seconds, 10));
    const soundEnabled = computed(() => uaManagement.value?.sound_enabled !== false);

    const showWarning = ref(false);
    const secondsLeft = ref(warningSeconds.value);

    let idleTimeoutHandle = null;
    let countdownIntervalHandle = null;
    let countdownRunId = 0;
    let listenersAttached = false;
    let countdownBeep = null;
    let ignoreActivityUntil = 0;
    let lastContinuousActivityAt = 0;
    let lastPointerX = null;
    let lastPointerY = null;

    function getIdleMs() {
        return resolveSeconds(page.props.uaManagement?.idle_seconds, 60) * 1000;
    }

    function clearIdleTimeout() {
        if (idleTimeoutHandle) {
            window.clearTimeout(idleTimeoutHandle);
            idleTimeoutHandle = null;
        }
    }

    function scheduleIdleTimeout() {
        clearIdleTimeout();

        if (showWarning.value || document.hidden || !isTrackingEnabled.value) {
            return;
        }

        idleTimeoutHandle = window.setTimeout(() => {
            idleTimeoutHandle = null;
            showIdleWarning();
        }, getIdleMs());
    }

    function getCountdownBeep() {
        if (!countdownBeep) {
            countdownBeep = new Audio(COUNTDOWN_BEEP_URL);
            countdownBeep.preload = 'auto';
        }

        return countdownBeep;
    }

    function preloadCountdownBeep() {
        getCountdownBeep().load();
    }

    function playCountdownBeep() {
        if (!soundEnabled.value) {
            return;
        }

        const audio = getCountdownBeep();
        audio.currentTime = 0;
        audio.play().catch(() => {});
    }

    function stopCountdownBeep() {
        if (!countdownBeep) {
            return;
        }

        countdownBeep.pause();
        countdownBeep.currentTime = 0;
    }

    function clearCountdown() {
        countdownRunId += 1;

        if (countdownIntervalHandle) {
            window.clearInterval(countdownIntervalHandle);
            countdownIntervalHandle = null;
        }

        stopCountdownBeep();
    }

    function clearAllTimers() {
        clearIdleTimeout();
        clearCountdown();
    }

    function performLogout() {
        clearAllTimers();
        showWarning.value = false;
        router.post('/logout');
    }

    function startCountdown() {
        clearCountdown();
        secondsLeft.value = warningSeconds.value;
        playCountdownBeep();

        const runId = countdownRunId;

        countdownIntervalHandle = window.setInterval(() => {
            if (runId !== countdownRunId || !showWarning.value) {
                if (countdownIntervalHandle) {
                    window.clearInterval(countdownIntervalHandle);
                    countdownIntervalHandle = null;
                }
                return;
            }

            secondsLeft.value -= 1;

            if (secondsLeft.value <= 0) {
                performLogout();
                return;
            }

            playCountdownBeep();
        }, COUNTDOWN_TICK_MS);
    }

    function showIdleWarning() {
        if (showWarning.value) {
            return;
        }

        clearIdleTimeout();
        showWarning.value = true;
        startCountdown();
    }

    function markActivity() {
        if (Date.now() < ignoreActivityUntil) {
            return;
        }

        if (showWarning.value || document.hidden || !isTrackingEnabled.value) {
            return;
        }

        scheduleIdleTimeout();
    }

    function hasMeaningfulPointerMove(event) {
        const x = event.clientX;
        const y = event.clientY;

        if (lastPointerX === null || lastPointerY === null) {
            lastPointerX = x;
            lastPointerY = y;
            return false;
        }

        const movedEnough =
            Math.abs(x - lastPointerX) >= MOUSE_MOVE_THRESHOLD_PX
            || Math.abs(y - lastPointerY) >= MOUSE_MOVE_THRESHOLD_PX;

        if (movedEnough) {
            lastPointerX = x;
            lastPointerY = y;
        }

        return movedEnough;
    }

    function onActivity() {
        markActivity();
    }

    function onContinuousActivity(event) {
        const now = Date.now();

        if (now - lastContinuousActivityAt < CONTINUOUS_ACTIVITY_PULSE_MS) {
            return;
        }

        if (!hasMeaningfulPointerMove(event)) {
            return;
        }

        lastContinuousActivityAt = now;
        markActivity();
    }

    function onVisibilityChange() {
        if (document.hidden) {
            clearIdleTimeout();
            return;
        }

        markActivity();
    }

    function attachListeners() {
        if (listenersAttached) {
            return;
        }

        DISCRETE_ACTIVITY_EVENTS.forEach((event) => {
            document.addEventListener(event, onActivity, ACTIVITY_LISTENER_OPTIONS);
        });
        CONTINUOUS_ACTIVITY_EVENTS.forEach((event) => {
            document.addEventListener(event, onContinuousActivity, ACTIVITY_LISTENER_OPTIONS);
        });
        document.addEventListener('visibilitychange', onVisibilityChange);
        listenersAttached = true;
    }

    function detachListeners() {
        if (!listenersAttached) {
            return;
        }

        DISCRETE_ACTIVITY_EVENTS.forEach((event) => {
            document.removeEventListener(event, onActivity, ACTIVITY_LISTENER_OPTIONS);
        });
        CONTINUOUS_ACTIVITY_EVENTS.forEach((event) => {
            document.removeEventListener(event, onContinuousActivity, ACTIVITY_LISTENER_OPTIONS);
        });
        document.removeEventListener('visibilitychange', onVisibilityChange);
        listenersAttached = false;
    }

    function stopTracking() {
        clearAllTimers();
        detachListeners();
        showWarning.value = false;
        ignoreActivityUntil = 0;
        lastPointerX = null;
        lastPointerY = null;
    }

    function confirmStay() {
        clearIdleTimeout();
        clearCountdown();

        lastContinuousActivityAt = 0;
        lastPointerX = null;
        lastPointerY = null;
        ignoreActivityUntil = Date.now() + POST_CONFIRM_GRACE_MS;

        showWarning.value = false;

        idleTimeoutHandle = window.setTimeout(() => {
            idleTimeoutHandle = null;

            if (!showWarning.value && isTrackingEnabled.value) {
                showIdleWarning();
            }
        }, getIdleMs());
    }

    watch(isTrackingEnabled, (enabled) => {
        if (enabled) {
            preloadCountdownBeep();
            attachListeners();
            scheduleIdleTimeout();
            return;
        }

        stopTracking();
    }, { immediate: true });

    watch(warningSeconds, (value) => {
        if (!showWarning.value) {
            secondsLeft.value = value;
        }
    });

    watch(
        () => page.props.uaManagement?.idle_seconds,
        () => {
            if (!showWarning.value && isTrackingEnabled.value) {
                scheduleIdleTimeout();
            }
        },
    );

    watch(uaManagement, () => {
        if (!showWarning.value) {
            secondsLeft.value = warningSeconds.value;
        }
    }, { deep: true });

    onUnmounted(stopTracking);

    return {
        showWarning,
        secondsLeft,
        confirmStay,
        isTrackingEnabled,
    };
}
