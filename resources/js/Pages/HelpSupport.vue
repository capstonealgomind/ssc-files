<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';
import SupportChat from '@/Components/SupportChat.vue';

const props = defineProps({
    tickets: { type: Array, default: () => [] },
    categories: { type: Object, required: true },
    selectedTicket: { type: Object, default: null },
});

const showCreateDialog = ref(false);
const ticketForm = useForm({
    subject: '',
    category: 'account',
    message: '',
});
const messageForm = useForm({ body: '' });

const isMobileChat = computed(() => !!props.selectedTicket);

const canReply = computed(() => props.selectedTicket?.can_chat && props.selectedTicket?.status === 'approved');

const waitingMessage = computed(() => {
    if (!props.selectedTicket) return '';
    if (props.selectedTicket.status === 'pending') {
        return 'Waiting for admin approval before you can reply to this ticket.';
    }
    return 'This conversation is no longer open for replies.';
});

function openCreateDialog() {
    ticketForm.reset();
    ticketForm.clearErrors();
    showCreateDialog.value = true;
}

function closeCreateDialog() {
    showCreateDialog.value = false;
    ticketForm.reset();
    ticketForm.clearErrors();
}

function submitTicket() {
    ticketForm.post('/help/tickets', {
        preserveScroll: true,
        onSuccess: () => closeCreateDialog(),
    });
}

function sendMessage(body) {
    if (!props.selectedTicket) return;
    messageForm.body = body;
    messageForm.post(`/help/tickets/${props.selectedTicket.id}/messages`, {
        preserveScroll: true,
        onSuccess: () => messageForm.reset(),
    });
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
</script>

<template>
    <AppLayout main-flush>
        <Head :title="selectedTicket ? `Ticket ${selectedTicket.ticket_number}` : 'Help & Support'" />

        <template #header>
            <div v-if="isMobileChat" class="flex items-center gap-2 min-w-0 lg:hidden">
                <Link href="/help" class="shrink-0 p-1 -ml-1 rounded-md hover:bg-gray-100" aria-label="Back to tickets">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate" style="color:hsl(240 10% 3.9%);">{{ selectedTicket.subject }}</p>
                    <p class="text-[11px] truncate" style="color:hsl(240 3.8% 46.1%);">{{ selectedTicket.ticket_number }}</p>
                </div>
            </div>
            <h1 v-else class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">Help & Support</h1>
            <h1 v-if="isMobileChat" class="hidden lg:block text-base font-semibold" style="color:hsl(240 10% 3.9%);">Help & Support</h1>
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
                <div class="p-4 border-b shrink-0" style="border-color:hsl(240 5.9% 90%);">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">My Tickets</h2>
                        <Button type="button" class="text-xs px-3 py-1.5 h-auto" @click="openCreateDialog">
                            New Ticket
                        </Button>
                    </div>
                    <p class="text-xs mt-1" style="color:hsl(240 3.8% 46.1%);">Select a ticket to open the conversation.</p>
                </div>

                <div v-if="tickets.length === 0" class="flex-1 flex items-center justify-center p-6 text-center">
                    <div>
                        <p class="text-sm font-medium" style="color:hsl(240 10% 3.9%);">No tickets yet</p>
                        <p class="text-xs mt-1" style="color:hsl(240 3.8% 46.1%);">Create one to get help from staff.</p>
                    </div>
                </div>

                <div v-else class="flex-1 overflow-y-auto min-h-0">
                    <Link
                        v-for="ticket in tickets"
                        :key="ticket.id"
                        :href="`/help/tickets/${ticket.id}`"
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
                            {{ ticket.category_label }} · {{ ticket.last_message_at || ticket.created_at }}
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
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-mono font-semibold" style="color:hsl(221 83% 45%);">{{ selectedTicket.ticket_number }}</span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold" :style="statusStyle(selectedTicket.status)">
                                {{ selectedTicket.status_label }}
                            </span>
                            <span class="text-xs" style="color:hsl(240 3.8% 46.1%);">{{ selectedTicket.category_label }}</span>
                        </div>
                        <h2 class="text-base font-semibold mt-1 break-words" style="color:hsl(240 10% 3.9%);">{{ selectedTicket.subject }}</h2>
                        <p v-if="selectedTicket.status === 'pending'" class="text-xs mt-2 rounded-lg px-3 py-2"
                            style="background:hsl(38 92% 94%); color:hsl(38 62% 30%);">
                            Pending review. Staff will approve before you can reply.
                        </p>
                        <p v-else-if="selectedTicket.approved_by" class="text-xs mt-2" style="color:hsl(240 3.8% 46.1%);">
                            Approved by {{ selectedTicket.approved_by }} on {{ selectedTicket.approved_at }}
                        </p>
                    </div>

                    <div class="flex-1 min-h-0 flex flex-col">
                        <SupportChat
                            :ticket-id="selectedTicket.id"
                            :messages="selectedTicket.messages"
                            :can-reply="canReply"
                            :waiting-message="waitingMessage"
                            :processing="messageForm.processing"
                            reply-action="/help/tickets"
                            placeholder="Reply to staff..."
                            compact
                            @submit="sendMessage"
                        />
                    </div>
                </template>

                <div v-else class="flex-1 flex items-center justify-center p-8 text-center">
                    <div>
                        <p class="text-sm font-medium" style="color:hsl(240 10% 3.9%);">Select a ticket</p>
                        <p class="text-xs mt-1" style="color:hsl(240 3.8% 46.1%);">Choose a ticket from the list or create a new one.</p>
                    </div>
                </div>
            </section>
        </div>

        <Dialog
            :show="showCreateDialog"
            title="New Support Ticket"
            description="Describe your concern. Staff will review and approve your ticket before live chat begins."
            @close="closeCreateDialog"
        >
            <form class="space-y-4" @submit.prevent="submitTicket">
                <div class="space-y-1.5">
                    <Label html-for="ticket-subject">Subject</Label>
                    <Input
                        id="ticket-subject"
                        v-model="ticketForm.subject"
                        type="text"
                        placeholder="Brief summary of your issue"
                        :error="!!ticketForm.errors.subject"
                    />
                    <InputError :message="ticketForm.errors.subject" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="ticket-category">Category</Label>
                    <select
                        id="ticket-category"
                        v-model="ticketForm.category"
                        class="w-full rounded-md border px-3 py-2 text-sm"
                        style="border-color:hsl(240 5.9% 90%);"
                    >
                        <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <InputError :message="ticketForm.errors.category" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="ticket-message">Message</Label>
                    <textarea
                        id="ticket-message"
                        v-model="ticketForm.message"
                        rows="4"
                        class="w-full rounded-md border px-3 py-2 text-sm resize-none"
                        style="border-color:hsl(240 5.9% 90%);"
                        placeholder="Explain your concern in detail..."
                    />
                    <InputError :message="ticketForm.errors.message" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" @click="closeCreateDialog">Cancel</Button>
                    <Button type="submit" :disabled="ticketForm.processing">
                        {{ ticketForm.processing ? 'Submitting...' : 'Submit Ticket' }}
                    </Button>
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
