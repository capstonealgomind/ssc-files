<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    siteKey: {
        type: String,
        required: true,
    },
    resetKey: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['token', 'error', 'expired']);

const containerRef = ref(null);
let widgetId = null;
let scriptPromise = null;
let isRendering = false;

function loadTurnstileScript() {
    if (window.turnstile) {
        return Promise.resolve();
    }

    if (scriptPromise) {
        return scriptPromise;
    }

    scriptPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Cloudflare Turnstile.'));
        document.head.appendChild(script);
    });

    return scriptPromise;
}

async function renderWidget() {
    if (isRendering || !containerRef.value || !props.siteKey) {
        return;
    }

    await loadTurnstileScript();
    await nextTick();

    if (!containerRef.value || !window.turnstile) {
        return;
    }

    isRendering = true;

    try {
        if (widgetId !== null) {
            window.turnstile.remove(widgetId);
            widgetId = null;
        }

        containerRef.value.innerHTML = '';

        widgetId = window.turnstile.render(containerRef.value, {
            sitekey: props.siteKey,
            theme: 'light',
            size: 'normal',
            callback: (token) => emit('token', token),
            'error-callback': (errorCode) => emit('error', errorCode),
            'expired-callback': () => {
                emit('expired');
                emit('token', '');
            },
        });
    } finally {
        isRendering = false;
    }
}

function resetWidget() {
    if (window.turnstile && widgetId !== null) {
        window.turnstile.reset(widgetId);
        emit('token', '');
    }
}

watch(() => props.resetKey, () => {
    resetWidget();
});

watch(() => props.siteKey, () => {
    void renderWidget();
});

onMounted(() => {
    void renderWidget();
});

onUnmounted(() => {
    if (window.turnstile && widgetId !== null) {
        window.turnstile.remove(widgetId);
        widgetId = null;
    }
});
</script>

<template>
    <div ref="containerRef" class="cf-turnstile-wrap" />
</template>

<style scoped>
.cf-turnstile-wrap {
    min-height: 65px;
}
</style>
