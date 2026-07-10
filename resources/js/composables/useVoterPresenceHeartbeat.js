import { onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

function detectDevice() {
    if (typeof window === 'undefined') return 'desktop';

    const ua = navigator.userAgent || '';
    const coarse = window.matchMedia?.('(pointer: coarse)')?.matches;
    const narrow = window.matchMedia?.('(max-width: 768px)')?.matches;

    if (/Mobi|Android|iPhone|iPad|iPod/i.test(ua) || (coarse && narrow)) {
        return 'mobile';
    }

    return 'desktop';
}

export function useVoterPresenceHeartbeat(isVoter) {
    const page = usePage();
    let timer = null;

    async function sendHeartbeat() {
        if (!isVoter?.value) return;

        const token = page.props.csrf_token;
        if (!token) return;

        try {
            await fetch('/presence/heartbeat', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ device: detectDevice() }),
            });
        } catch {
            // Ignore transient network errors; next tick will retry.
        }
    }

    function start() {
        stop();
        if (!isVoter?.value) return;
        sendHeartbeat();
        timer = window.setInterval(sendHeartbeat, 30000);
    }

    function stop() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    onMounted(() => {
        start();
    });

    onUnmounted(() => {
        stop();
    });

    watch(
        () => isVoter?.value,
        () => start(),
    );

    return { sendHeartbeat };
}
