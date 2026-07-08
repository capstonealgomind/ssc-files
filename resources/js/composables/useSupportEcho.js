import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

let echoInstance = null;

function getEcho(page) {
    const { key, cluster } = page.props.pusher ?? {};

    if (!key || !cluster) {
        return null;
    }

    if (!echoInstance) {
        window.Pusher = Pusher;

        const csrf = page.props.csrf_token
            ?? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            ?? '';

        echoInstance = new Echo({
            broadcaster: 'pusher',
            key,
            cluster,
            forceTLS: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrf,
                },
            },
        });
    }

    return echoInstance;
}

export function useSupportEcho(ticketId, onMessage) {
    const page = usePage();
    const echo = getEcho(page);

    if (!echo || !ticketId) {
        return;
    }

    echo.private(`support-ticket.${ticketId}`)
        .listen('.message.sent', (payload) => {
            if (payload?.message) {
                onMessage(payload.message);
            }
        });

    onUnmounted(() => {
        echo.leave(`support-ticket.${ticketId}`);
    });
}
