<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const open = ref(false);
const messages = ref([
    {
        id: 1,
        role: 'assistant',
        content:
            "Hi! I'm AIVA, your SSCEVS assistant. Ask me about registration, login, voting, Live Standing, reactivation, or anything about using this election portal.",
        off_topic: false,
    },
]);
const input = ref('');
const loading = ref(false);
const error = ref('');
const listRef = ref(null);
const panelRef = ref(null);

function scrollToBottom() {
    nextTick(() => {
        if (listRef.value) {
            listRef.value.scrollTop = listRef.value.scrollHeight;
        }
    });
}

function toggle() {
    open.value = !open.value;
    error.value = '';
    if (open.value) {
        scrollToBottom();
    }
}

function close() {
    open.value = false;
}

async function sendMessage() {
    const text = input.value.trim();
    if (!text || loading.value) return;

    error.value = '';
    messages.value.push({ id: Date.now(), role: 'user', content: text, off_topic: false });
    input.value = '';
    loading.value = true;
    scrollToBottom();

    const history = messages.value
        .slice(0, -1)
        .filter((m) => m.id !== 1)
        .map((m) => ({ role: m.role, content: m.content }));

    try {
        const { data } = await window.axios.post('/aiva/chat', {
            message: text,
            history,
        });

        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            content: data.reply,
            off_topic: data.off_topic ?? false,
        });
    } catch (e) {
        if (e.response?.status === 419) {
            error.value = 'Your session expired. Please refresh the page and try again.';
        } else if (e.response?.status === 429) {
            error.value = 'Too many messages. Please wait a moment and try again.';
        } else {
            error.value = e.response?.data?.error ?? 'Something went wrong. Please try again.';
        }
    } finally {
        loading.value = false;
        scrollToBottom();
    }
}

function onDocumentKeydown(event) {
    if (event.key === 'Escape' && open.value) {
        close();
    }
}

watch(open, (isOpen) => {
    document.body.classList.toggle('aiva-chat-open', isOpen);
});

onMounted(() => {
    document.addEventListener('keydown', onDocumentKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onDocumentKeydown);
    document.body.classList.remove('aiva-chat-open');
});
</script>

<template>
    <div class="aiva-root">
        <Transition name="aiva-panel">
            <div
                v-if="open"
                ref="panelRef"
                class="aiva-panel"
                role="dialog"
                aria-label="AIVA assistant chat"
            >
                <header class="aiva-panel-header">
                    <div class="aiva-panel-brand">
                        <img
                            src="/images/aissistant/aiva_logo.png"
                            alt="AIVA"
                            class="aiva-panel-avatar"
                        />
                        <div class="min-w-0">
                            <p class="aiva-panel-title">AIVA</p>
                            <p class="aiva-panel-subtitle">SSCEVS AI Assistant</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="aiva-icon-btn"
                        aria-label="Close AIVA"
                        @click="close"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </header>

                <div ref="listRef" class="aiva-messages">
                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="aiva-row"
                        :class="message.role === 'user' ? 'aiva-row-user' : 'aiva-row-assistant'"
                    >
                        <img
                            v-if="message.role === 'assistant'"
                            src="/images/aissistant/aiva_logo.png"
                            alt=""
                            class="aiva-bubble-avatar"
                        />
                        <div
                            class="aiva-bubble"
                            :class="{
                                'aiva-bubble-user': message.role === 'user',
                                'aiva-bubble-assistant': message.role === 'assistant',
                                'aiva-bubble-offtopic': message.off_topic,
                            }"
                        >
                            <p
                                v-if="message.off_topic"
                                class="aiva-offtopic-label"
                            >
                                Outside system scope
                            </p>
                            <p class="aiva-bubble-text">{{ message.content }}</p>
                        </div>
                    </div>

                    <div v-if="loading" class="aiva-row aiva-row-assistant">
                        <img
                            src="/images/aissistant/aiva_logo.png"
                            alt=""
                            class="aiva-bubble-avatar"
                        />
                        <div class="aiva-bubble aiva-bubble-assistant aiva-typing">
                            <span /><span /><span />
                        </div>
                    </div>
                </div>

                <form class="aiva-composer" @submit.prevent="sendMessage">
                    <input
                        v-model="input"
                        type="text"
                        class="aiva-input"
                        placeholder="Ask AIVA about SSCEVS..."
                        :disabled="loading"
                        maxlength="1000"
                        autocomplete="off"
                    />
                    <button
                        type="submit"
                        class="aiva-send"
                        :disabled="loading || !input.trim()"
                        aria-label="Send message"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                    </button>
                </form>
                <p v-if="error" class="aiva-error">{{ error }}</p>
            </div>
        </Transition>

        <button
            type="button"
            class="aiva-fab"
            :class="{ 'aiva-fab-open': open }"
            :aria-expanded="open"
            aria-label="Open AIVA assistant"
            @click="toggle"
        >
            <span class="aiva-fab-pulse" aria-hidden="true" />
            <img
                src="/images/aissistant/aiva_logo.png"
                alt="AIVA"
                class="aiva-fab-logo"
            />
            <span class="aiva-fab-label">AIVA</span>
        </button>
    </div>
</template>

<style scoped>
.aiva-root {
    position: fixed;
    right: max(1rem, env(safe-area-inset-right));
    bottom: max(1rem, env(safe-area-inset-bottom));
    z-index: 60;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
    pointer-events: none;
}

.aiva-root > * {
    pointer-events: auto;
}

.aiva-fab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.7rem 0.3rem 0.3rem;
    border: 1px solid hsl(215 40% 85%);
    border-radius: 999px;
    background: hsl(0 0% 100%);
    box-shadow:
        0 8px 22px hsl(215 79% 13% / 0.16),
        0 2px 6px hsl(215 79% 13% / 0.07);
    color: var(--sscevs-navy, hsl(215 79% 13%));
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.aiva-fab:hover {
    transform: translateY(-2px);
    box-shadow:
        0 12px 28px hsl(215 79% 13% / 0.2),
        0 3px 8px hsl(215 79% 13% / 0.09);
}

.aiva-fab-open {
    transform: scale(0.96);
}

.aiva-fab-pulse {
    position: absolute;
    inset: -3px;
    border-radius: inherit;
    border: 2px solid hsl(215 85% 42% / 0.35);
    animation: aiva-pulse 2.4s ease-out infinite;
    pointer-events: none;
}

.aiva-fab-logo {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 999px;
    object-fit: cover;
    background: hsl(215 70% 96%);
}

.aiva-fab-label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    line-height: 1;
}

.aiva-panel {
    width: min(22.5rem, calc(100vw - 2rem));
    height: min(32rem, calc(100dvh - 6.5rem));
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 1rem;
    border: 1px solid hsl(215 30% 88%);
    background: hsl(0 0% 100%);
    box-shadow:
        0 24px 60px hsl(215 79% 13% / 0.22),
        0 8px 20px hsl(215 79% 13% / 0.1);
}

.aiva-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.85rem 0.95rem;
    border-bottom: 1px solid hsl(215 30% 90%);
    background: linear-gradient(135deg, hsl(215 70% 97%) 0%, hsl(0 0% 100%) 70%);
}

.aiva-panel-brand {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
}

.aiva-panel-avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 999px;
    object-fit: cover;
    border: 1px solid hsl(215 40% 88%);
    background: hsl(215 70% 96%);
}

.aiva-panel-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--sscevs-navy, hsl(215 79% 13%));
    line-height: 1.2;
}

.aiva-panel-subtitle {
    font-size: 0.7rem;
    color: hsl(215 15% 42%);
}

.aiva-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    color: hsl(215 15% 42%);
    transition: background-color 0.15s ease;
}

.aiva-icon-btn:hover {
    background: hsl(215 70% 96%);
}

.aiva-messages {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0.85rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    background: hsl(215 40% 98%);
}

.aiva-row {
    display: flex;
    align-items: flex-end;
    gap: 0.45rem;
    min-width: 0;
}

.aiva-row-user {
    justify-content: flex-end;
}

.aiva-row-assistant {
    justify-content: flex-start;
}

.aiva-bubble-avatar {
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid hsl(215 40% 88%);
    background: #fff;
}

.aiva-bubble {
    max-width: min(85%, 17rem);
    border-radius: 1rem;
    padding: 0.65rem 0.8rem;
    box-shadow: 0 1px 2px hsl(215 40% 20% / 0.06);
}

.aiva-bubble-user {
    background: var(--sscevs-navy, hsl(215 79% 13%));
    color: #fff;
    border-bottom-right-radius: 0.35rem;
}

.aiva-bubble-assistant {
    background: #fff;
    color: hsl(215 25% 16%);
    border: 1px solid hsl(215 30% 90%);
    border-bottom-left-radius: 0.35rem;
}

.aiva-bubble-offtopic {
    background: hsl(38 92% 94%);
    color: hsl(38 62% 30%);
    border-color: hsl(38 70% 85%);
}

.aiva-offtopic-label {
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.25rem;
    opacity: 0.85;
}

.aiva-bubble-text {
    font-size: 0.8125rem;
    line-height: 1.45;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.aiva-typing {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    min-height: 1.6rem;
}

.aiva-typing span {
    width: 0.35rem;
    height: 0.35rem;
    border-radius: 999px;
    background: hsl(215 20% 65%);
    animation: aiva-bounce 1.1s ease-in-out infinite;
}

.aiva-typing span:nth-child(2) {
    animation-delay: 0.15s;
}

.aiva-typing span:nth-child(3) {
    animation-delay: 0.3s;
}

.aiva-composer {
    display: flex;
    gap: 0.5rem;
    padding: 0.75rem;
    border-top: 1px solid hsl(215 30% 90%);
    background: #fff;
}

.aiva-input {
    flex: 1;
    min-width: 0;
    border: 1px solid hsl(215 30% 88%);
    border-radius: 0.65rem;
    padding: 0.55rem 0.75rem;
    font-size: 0.8125rem;
    outline: none;
}

.aiva-input:focus {
    border-color: hsl(215 85% 42%);
    box-shadow: 0 0 0 3px hsl(215 85% 42% / 0.15);
}

.aiva-send {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.65rem;
    background: var(--sscevs-navy, hsl(215 79% 13%));
    color: #fff;
    flex-shrink: 0;
    transition: opacity 0.15s ease;
}

.aiva-send:disabled {
    opacity: 0.45;
}

.aiva-error {
    padding: 0 0.75rem 0.65rem;
    font-size: 0.7rem;
    color: hsl(0 72% 40%);
    background: #fff;
}

.aiva-panel-enter-active,
.aiva-panel-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.aiva-panel-enter-from,
.aiva-panel-leave-to {
    opacity: 0;
    transform: translateY(12px) scale(0.96);
}

@keyframes aiva-pulse {
    0% {
        transform: scale(1);
        opacity: 0.7;
    }
    100% {
        transform: scale(1.18);
        opacity: 0;
    }
}

@keyframes aiva-bounce {
    0%,
    80%,
    100% {
        transform: translateY(0);
        opacity: 0.45;
    }
    40% {
        transform: translateY(-3px);
        opacity: 1;
    }
}

@media (max-width: 767px) {
    .aiva-fab-label {
        display: none;
    }

    .aiva-fab {
        padding: 0.28rem;
        border-radius: 999px;
        gap: 0;
    }

    .aiva-fab-logo {
        width: 2.85rem;
        height: 2.85rem;
    }

    .aiva-panel {
        width: calc(100vw - 1.5rem);
        height: min(70dvh, 30rem);
    }
}
</style>
