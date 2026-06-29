<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useSupportEcho } from '@/composables/useSupportEcho';

const props = defineProps({
    ticketId: { type: Number, required: true },
    messages: { type: Array, default: () => [] },
    canReply: { type: Boolean, default: false },
    replyAction: { type: String, required: true },
    placeholder: { type: String, default: 'Type your message...' },
    waitingMessage: { type: String, default: 'Waiting for admin approval before you can reply to this ticket.' },
    processing: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['submit']);

const page = usePage();
const localMessages = ref([...props.messages]);
const body = ref('');
const listRef = ref(null);

watch(() => props.messages, (value) => {
    localMessages.value = [...value];
    scrollToBottom();
}, { deep: true });

function scrollToBottom() {
    nextTick(() => {
        if (listRef.value) {
            listRef.value.scrollTop = listRef.value.scrollHeight;
        }
    });
}

function appendMessage(message) {
    if (localMessages.value.some((item) => item.id === message.id)) {
        return;
    }

    localMessages.value.push(message);
    scrollToBottom();
}

useSupportEcho(props.ticketId, appendMessage);

function isOwnMessage(message) {
    return message.user_id === page.props.auth.user?.id;
}

function roleLabel(role) {
    if (!role) return '';
    return String(role).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function submit() {
    const trimmed = body.value.trim();
    if (!trimmed || !props.canReply || props.processing) {
        return;
    }

    emit('submit', trimmed);
    body.value = '';
}

onMounted(scrollToBottom);
</script>

<template>
    <div class="flex flex-col h-full min-h-0">
        <div
            ref="listRef"
            class="flex-1 overflow-y-auto overflow-x-hidden space-y-3 min-h-0"
            :class="compact ? 'px-3 py-3' : 'p-4 rounded-lg border'"
            :style="compact
                ? { background: 'hsl(240 4.8% 98%)' }
                : { borderColor: 'hsl(240 5.9% 90%)', background: 'hsl(240 4.8% 98%)' }"
        >
            <div
                v-for="message in localMessages"
                :key="message.id"
                class="flex min-w-0"
                :class="isOwnMessage(message) ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="rounded-2xl px-3.5 py-2.5 shadow-sm min-w-0 max-w-[88%] sm:max-w-[75%]"
                    :style="message.is_staff
                        ? { background: 'hsl(240 4.8% 95.9%)', color: 'hsl(240 10% 3.9%)', border: '1px solid hsl(240 5.9% 90%)' }
                        : { background: 'hsl(221 83% 53%)', color: '#fff' }"
                >
                    <p class="text-[11px] font-semibold opacity-80 mb-1 truncate">
                        {{ message.author_name }}
                        <span v-if="message.author_role" class="font-normal">· {{ roleLabel(message.author_role) }}</span>
                    </p>
                    <p class="text-sm whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{{ message.body }}</p>
                    <p class="text-[10px] mt-1.5 opacity-70">{{ message.created_at }}</p>
                </div>
            </div>
        </div>

        <form
            v-if="canReply"
            class="shrink-0 flex gap-2 border-t bg-white"
            :class="compact ? 'p-3' : 'mt-4 p-0 pt-4 border-transparent'"
            :style="compact ? { borderColor: 'hsl(240 5.9% 90%)' } : undefined"
            @submit.prevent="submit"
        >
            <textarea
                v-model="body"
                rows="2"
                class="flex-1 min-w-0 rounded-lg border px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2"
                style="border-color:hsl(240 5.9% 90%);"
                :placeholder="placeholder"
                :disabled="processing"
            />
            <button
                type="submit"
                class="self-end px-4 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-60 shrink-0"
                style="background:var(--sscevs-navy);"
                :disabled="processing || !body.trim()"
            >
                {{ processing ? '...' : 'Send' }}
            </button>
        </form>

        <p
            v-else
            class="shrink-0 text-sm border-t px-4 py-3"
            style="background:hsl(38 92% 94%); color:hsl(38 62% 30%); border-color:hsl(240 5.9% 90%);"
        >
            {{ waitingMessage }}
        </p>
    </div>
</template>
