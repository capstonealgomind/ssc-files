<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SupportChat from '@/Components/SupportChat.vue';
import Button from '@/Components/ui/Button.vue';

const props = defineProps({
    tickets: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    filter: { type: String, default: null },
    selectedTicket: { type: Object, default: null },
});

const messageForm = useForm({ body: '' });
const approveForm = useForm({});
const rejectForm = useForm({});
const closeForm = useForm({});

const canReply = computed(() => props.selectedTicket?.can_chat ?? false);
const isPending = computed(() => props.selectedTicket?.status === 'pending');
const isMobileChat = computed(() => !!props.selectedTicket);

const waitingMessage = computed(() => {
    if (!props.selectedTicket) return '';
    if (props.selectedTicket.status === 'pending') {
        return 'Approve this ticket to start messaging the voter.';
    }
    return 'This ticket is no longer open for replies.';
});

function ticketHref(ticketId) {
    const params = props.filter ? `?status=${props.filter}` : '';
    return `/support/tickets/${ticketId}${params}`;
}

function supportIndexHref(status = null) {
    return status ? `/support?status=${status}` : '/support';
}

function applyFilter(status) {
    router.get(supportIndexHref(status), {}, { preserveState: true, preserveScroll: true });
}

function sendMessage(body) {
    if (!props.selectedTicket) return;
    messageForm.body = body;
    messageForm.post(`/support/tickets/${props.selectedTicket.id}/messages`, {
        preserveScroll: true,
        onSuccess: () => messageForm.reset(),
    });
}

function approveTicket() {
    if (!props.selectedTicket) return;
    approveForm.post(`/support/tickets/${props.selectedTicket.id}/approve`, { preserveScroll: true });
}

function rejectTicket() {
    if (!props.selectedTicket) return;
    rejectForm.post(`/support/tickets/${props.selectedTicket.id}/reject`, { preserveScroll: true });
}

function closeTicket() {
    if (!props.selectedTicket) return;
    closeForm.post(`/support/tickets/${props.selectedTicket.id}/close`, { preserveScroll: true });
}

function statusStyle(status) {
    if (status === 'approved') return { color: 'hsl(142 71% 25%)', background: 'hsl(142 76% 94%)' };
    if (status === 'pending') return { color: 'hsl(38 62% 30%)', background: 'hsl(38 92% 94%)' };
    if (status === 'rejected') return { color: 'hsl(0 72% 35%)', background: 'hsl(0 86% 94%)' };
    return { color: 'hsl(240 3.8% 46.1%)', background: 'hsl(240 4.8% 95.9%)' };
}

function isSelected(ticketId) {
    return props.selectedTicket?.id === ticketId;
}

let pollTimer = null;

function isPollingPaused() {
    return document.hidden
        || messageForm.processing
        || approveForm.processing
        || rejectForm.processing
        || closeForm.processing;
}

function pollTickets() {
    if (isPollingPaused()) {
        return;
    }

    router.reload({
        only: props.selectedTicket ? ['tickets', 'selectedTicket'] : ['tickets'],
        preserveScroll: true,
        preserveState: true,
    });
}

onMounted(() => {
    pollTimer = setInterval(pollTickets, 5000);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});
</script>

<template>
    <AppLayout main-flush>
        <Head :title="selectedTicket ? `Support ${selectedTicket.ticket_number}` : 'Support Tickets'" />
        <template #header>
            <div v-if="isMobileChat" class="flex items-center gap-2 min-w-0 lg:hidden">
                <Link :href="supportIndexHref(filter)" class="shrink-0 p-1 -ml-1 rounded-md hover:bg-gray-100" aria-label="Back to tickets">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate" style="color:hsl(240 10% 3.9%);">{{ selectedTicket.subject }}</p>
                    <p class="text-[11px] truncate" style="color:hsl(240 3.8% 46.1%);">{{ selectedTicket.ticket_number }}</p>
                </div>
            </div>
            <h1 v-else class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">Support Tickets</h1>
            <h1 v-if="isMobileChat" class="hidden lg:block text-base font-semibold" style="color:hsl(240 10% 3.9%);">Support Tickets</h1>
        </template>

        <div
            class="flex flex-col lg:flex-row flex-1 min-h-0 h-full lg:m-4 lg:rounded-xl lg:border lg:overflow-hidden"
            style="background:#fff; border-color:hsl(240 5.9% 90%);"
        >
            <!-- Ticket list -->
            <aside
                class="flex flex-col border-b lg:border-b-0 lg:border-r shrink-0 w-full lg:w-80 xl:w-96 min-h-0"
                style="border-color:hsl(240 5.9% 90%);"
                :class="isMobileChat ? 'hidden lg:flex' : 'flex flex-1 lg:flex-none'"
            >
                <div class="p-4 border-b shrink-0 space-y-3" style="border-color:hsl(240 5.9% 90%);">
                    <h2 class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">Tickets</h2>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="item in statuses"
                            :key="item.label"
                            type="button"
                            class="px-2.5 py-1 rounded-full text-[11px] font-semibold border transition-colors"
                            :style="(filter ?? null) === item.value
                                ? { background: 'var(--sscevs-navy)', color: '#fff', borderColor: 'var(--sscevs-navy)' }
                                : { background: '#fff', color: 'hsl(240 10% 3.9%)', borderColor: 'hsl(240 5.9% 90%)' }"
                            @click="applyFilter(item.value)"
                        >
                            {{ item.label }}
                        </button>
                    </div>
                </div>

                <div v-if="tickets.length === 0" class="flex-1 flex items-center justify-center p-6 text-center">
                    <p class="text-sm" style="color:hsl(240 3.8% 46.1%);">No tickets found</p>
                </div>

                <div v-else class="flex-1 overflow-y-auto min-h-0">
                    <Link
                        v-for="ticket in tickets"
                        :key="ticket.id"
                        :href="ticketHref(ticket.id)"
                        preserve-scroll
                        class="block px-4 py-3 border-b transition-colors"
                        :class="isSelected(ticket.id) ? 'bg-blue-50' : 'hover:bg-gray-50'"
                        style="border-color:hsl(240 5.9% 90%);"
                    >
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[11px] font-mono font-semibold" style="color:hsl(221 83% 45%);">{{ ticket.ticket_number }}</span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold" :style="statusStyle(ticket.status)">
                                {{ ticket.status_label }}
                            </span>
                        </div>
                        <p class="text-sm font-semibold mt-1 line-clamp-2" style="color:hsl(240 10% 3.9%);">{{ ticket.subject }}</p>
                        <p class="text-[11px] mt-1 truncate" style="color:hsl(240 3.8% 46.1%);">
                            {{ ticket.voter?.name }} · {{ ticket.last_message_at || ticket.created_at }}
                        </p>
                    </Link>
                </div>
            </aside>

            <!-- Conversation panel -->
            <section
                class="flex-1 flex flex-col min-w-0 min-h-0"
                :class="isMobileChat ? 'flex' : 'hidden lg:flex'"
            >
                <template v-if="selectedTicket">
                    <div class="hidden lg:block p-4 border-b shrink-0" style="border-color:hsl(240 5.9% 90%);">
                        <div class="flex flex-col gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-mono font-semibold" style="color:hsl(221 83% 45%);">{{ selectedTicket.ticket_number }}</span>
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold" :style="statusStyle(selectedTicket.status)">
                                        {{ selectedTicket.status_label }}
                                    </span>
                                </div>
                                <h2 class="text-base font-semibold mt-1 break-words" style="color:hsl(240 10% 3.9%);">{{ selectedTicket.subject }}</h2>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 text-xs" style="color:hsl(240 3.8% 46.1%);">
                                <p><span class="font-medium">Voter:</span> {{ selectedTicket.voter?.name }}</p>
                                <p><span class="font-medium">Email:</span> {{ selectedTicket.voter?.email }}</p>
                                <p><span class="font-medium">Student ID:</span> {{ selectedTicket.voter?.student_id_number || '—' }}</p>
                                <p><span class="font-medium">Category:</span> {{ selectedTicket.category_label }}</p>
                            </div>

                            <div v-if="isPending" class="flex flex-wrap gap-2">
                                <Button type="button" :disabled="approveForm.processing" @click="approveTicket">
                                    {{ approveForm.processing ? 'Approving...' : 'Approve' }}
                                </Button>
                                <Button type="button" variant="outline" :disabled="rejectForm.processing" @click="rejectTicket">
                                    Reject
                                </Button>
                            </div>
                            <div v-else-if="selectedTicket.status === 'approved'">
                                <Button type="button" variant="outline" :disabled="closeForm.processing" @click="closeTicket">
                                    Close Ticket
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="lg:hidden shrink-0 px-3 py-2 border-b flex flex-wrap gap-2" style="border-color:hsl(240 5.9% 90%); background:hsl(240 4.8% 98%);">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold" :style="statusStyle(selectedTicket.status)">
                            {{ selectedTicket.status_label }}
                        </span>
                        <span class="text-[11px] self-center" style="color:hsl(240 3.8% 46.1%);">{{ selectedTicket.voter?.name }}</span>
                        <div v-if="isPending" class="flex gap-2 ml-auto">
                            <Button type="button" class="text-xs px-2 py-1 h-auto" :disabled="approveForm.processing" @click="approveTicket">Approve</Button>
                            <Button type="button" variant="outline" class="text-xs px-2 py-1 h-auto" :disabled="rejectForm.processing" @click="rejectTicket">Reject</Button>
                        </div>
                    </div>

                    <div class="flex-1 min-h-0 flex flex-col">
                        <SupportChat
                            :ticket-id="selectedTicket.id"
                            :messages="selectedTicket.messages"
                            :can-reply="canReply"
                            :waiting-message="waitingMessage"
                            :processing="messageForm.processing"
                            reply-action="/support/tickets"
                            :placeholder="canReply ? 'Message the voter...' : 'Approve this ticket to start messaging.'"
                            compact
                            @submit="sendMessage"
                        />
                    </div>
                </template>

                <div v-else class="flex-1 flex items-center justify-center p-8 text-center">
                    <div>
                        <p class="text-sm font-medium" style="color:hsl(240 10% 3.9%);">Select a ticket</p>
                        <p class="text-xs mt-1" style="color:hsl(240 3.8% 46.1%);">Choose a ticket from the list to view and reply.</p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
