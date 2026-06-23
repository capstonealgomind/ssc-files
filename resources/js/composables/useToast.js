import { toast } from 'vue-sonner';

function notify(type, title, description) {
    if (!description) {
        description = title;
        title = type === 'success' ? 'Success' : 'Error';
    }

    const options = { description };

    if (type === 'success') {
        toast.success(title, options);
        return;
    }

    toast.error(title, options);
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

    if (flash.success) {
        notify('success', 'All set', flash.success);
    }

    if (flash.error) {
        notify('error', 'Something went wrong', flash.error);
    }
}
