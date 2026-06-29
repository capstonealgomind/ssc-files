<script setup>
import { nextTick, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const messages = ref([
    {
        id: 1,
        role: 'assistant',
        content: 'Hi! I\'m the SSCEVS FAQ assistant. Ask me anything about using this election system — registration, voting, ballots, receipts, and your account.',
        off_topic: false,
    },
]);
const input = ref('');
const loading = ref(false);
const error = ref('');
const listRef = ref(null);

function scrollToBottom() {
    nextTick(() => {
        if (listRef.value) {
            listRef.value.scrollTop = listRef.value.scrollHeight;
        }
    });
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
        const { data } = await axios.post('/faq/chat', {
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
        error.value = e.response?.data?.error ?? 'Something went wrong. Please try again.';
    } finally {
        loading.value = false;
        scrollToBottom();
    }
}

onMounted(scrollToBottom);
</script>

<template>
    <AppLayout main-flush>
        <Head title="FAQ" />
        <template #header>
            <div class="min-w-0">
                <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">FAQ Assistant</h1>
                <p class="hidden sm:block text-[11px] truncate" style="color:hsl(240 3.8% 46.1%);">Ask about SSCEVS — registration, voting, and your account</p>
            </div>
        </template>

        <div class="flex flex-col flex-1 min-h-0 h-full lg:m-4 lg:rounded-xl lg:border lg:overflow-hidden"
            style="background:#fff; border-color:hsl(240 5.9% 90%);">
            <div class="hidden lg:flex px-4 py-3 border-b shrink-0 items-center gap-2"
                style="border-color:hsl(240 5.9% 90%); background:linear-gradient(135deg, hsl(221 83% 98%) 0%, #fff 100%);">
                <div class="h-8 w-8 rounded-lg flex items-center justify-center shrink-0"
                    style="background:hsl(221 83% 53%); color:#fff;">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">SSCEVS AI Assistant</p>
                    <p class="text-[11px]" style="color:hsl(240 3.8% 46.1%);">Answers questions about this system only</p>
                </div>
            </div>

            <div ref="listRef" class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-3 space-y-3 min-h-0"
                style="background:hsl(240 4.8% 98%);">
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="flex min-w-0"
                    :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="rounded-2xl px-3.5 py-2.5 shadow-sm min-w-0 max-w-[88%] sm:max-w-[75%]"
                        :style="message.role === 'user'
                            ? { background: 'hsl(221 83% 53%)', color: '#fff' }
                            : message.off_topic
                                ? { background: 'hsl(38 92% 94%)', color: 'hsl(38 62% 30%)', border: '1px solid hsl(38 70% 85%)' }
                                : { background: '#fff', color: 'hsl(240 10% 3.9%)', border: '1px solid hsl(240 5.9% 90%)' }"
                    >
                        <p v-if="message.off_topic" class="text-[10px] font-semibold uppercase tracking-wide mb-1 opacity-80">
                            Outside system scope
                        </p>
                        <p class="text-sm whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{{ message.content }}</p>
                    </div>
                </div>

                <div v-if="loading" class="flex justify-start">
                    <div class="rounded-2xl px-4 py-2.5 text-sm" style="background:#fff; border:1px solid hsl(240 5.9% 90%); color:hsl(240 3.8% 46.1%);">
                        Thinking...
                    </div>
                </div>
            </div>

            <form class="shrink-0 flex gap-2 border-t p-3 bg-white" style="border-color:hsl(240 5.9% 90%);" @submit.prevent="sendMessage">
                <input
                    v-model="input"
                    type="text"
                    class="flex-1 min-w-0 rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    style="border-color:hsl(240 5.9% 90%);"
                    placeholder="Ask about voting, registration, ballots..."
                    :disabled="loading"
                    maxlength="1000"
                />
                <button
                    type="submit"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-60 shrink-0"
                    style="background:var(--sscevs-navy);"
                    :disabled="loading || !input.trim()"
                >
                    Send
                </button>
            </form>

            <p v-if="error" class="shrink-0 px-3 pb-2 text-xs" style="color:hsl(0 72% 40%);">{{ error }}</p>
        </div>
    </AppLayout>
</template>
