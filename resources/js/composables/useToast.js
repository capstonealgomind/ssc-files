import { toast } from 'vue-sonner';

let lastFlashKey = '';
let lastFlashAt = 0;

function notify(type, title, description = null) {
    if (description) {
        const options = { description };
        if (type === 'success') {
            toast.success(title, options);
        } else {
            toast.error(title, options);
        }
        return;
    }

    if (type === 'success') {
        toast.success(title);
        return;
    }

    toast.error(title);
}

export function useToast() {
    return {
        success(title, description = null) {
            notify('success', title, description);
        },
        error(title, description = null) {
            notify('error', title, description);
        },
        message(title, description = null) {
            notify('success', title, description);
        },
    };
}

export function showFlashToast(flash) {
    if (!flash) {
        return;
    }

    const entries = [];
    if (flash.success) {
        entries.push(['success', flash.success]);
    }
    if (flash.error) {
        entries.push(['error', flash.error]);
    }

    for (const [type, message] of entries) {
        const key = `${type}:${message}`;
        const now = Date.now();
        if (key === lastFlashKey && now - lastFlashAt < 2000) {
            continue;
        }
        lastFlashKey = key;
        lastFlashAt = now;
        notify(type, message);
    }
}
