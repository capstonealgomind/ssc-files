<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

const page = usePage();
const open = ref(false);
const loading = ref(false);
const unreadCount = ref(0);
const notifications = ref([]);
let pollTimer = null;
let lastNotificationId = null;
let hasLoaded = false;

async function refreshNotifications() {
    if (loading.value || document.visibilityState === 'hidden') return;
    loading.value = true;
    try {
        const response = await fetch('/system-notifications', {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok) return;

        const data = await response.json();
        const latestId = data.notifications?.[0]?.id ?? null;
        if (hasLoaded && latestId && lastNotificationId && latestId !== lastNotificationId) {
            const newest = data.notifications[0];
            toast.info(newest.title, { description: newest.message });
        }
        lastNotificationId = latestId;
        hasLoaded = true;
        unreadCount.value = data.unreadCount ?? 0;
        notifications.value = data.notifications ?? [];
    } catch {
        // Keep the bell quiet when a brief network interruption occurs.
    } finally {
        loading.value = false;
    }
}

async function markRead(id) {
    try {
        await fetch(`/system-notifications/${encodeURIComponent(id)}/read`, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': page.props.csrf_token,
            },
            credentials: 'same-origin',
        });
    } finally {
        await refreshNotifications();
    }
}

async function openNotification(item) {
    open.value = false;
    if (!item.read) await markRead(item.id);
    router.visit(item.url || '/dashboard');
}

async function markAllRead() {
    try {
        await fetch('/system-notifications/read-all', {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': page.props.csrf_token,
            },
            credentials: 'same-origin',
        });
        await refreshNotifications();
    } catch {
        toast.error('Could not mark notifications as read. Try again.');
    }
}

function formatDate(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function onVisibilityChange() {
    if (document.visibilityState === 'visible') refreshNotifications();
}

onMounted(() => {
    refreshNotifications();
    pollTimer = setInterval(refreshNotifications, 15000);
    document.addEventListener('visibilitychange', onVisibilityChange);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
    document.removeEventListener('visibilitychange', onVisibilityChange);
});
</script>

<template>
    <div class="relative shrink-0">
        <button
            type="button"
            class="relative flex h-9 w-9 items-center justify-center rounded-md transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
            :aria-label="unreadCount ? `${unreadCount} unread notifications` : 'Notifications'"
            :aria-expanded="open"
            title="System notifications"
            @click.stop="open = !open; open && refreshNotifications()"
        >
            <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9a6 6 0 0 0-12 0v.75a8.967 8.967 0 0 1-2.311 6.022 23.848 23.848 0 0 0 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <span v-if="unreadCount" class="absolute -right-1 -top-1 min-w-4 rounded-full bg-red-600 px-1 text-center text-[10px] font-bold leading-4 text-white">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <section
            v-if="open"
            class="absolute right-0 z-[100] mt-2 w-[min(22rem,90vw)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
            aria-label="System notifications"
            @click.stop
        >
            <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Notifications</h2>
                    <p class="text-xs text-slate-500">{{ unreadCount }} unread</p>
                </div>
                <button
                    v-if="unreadCount"
                    type="button"
                    class="text-xs font-medium text-blue-700 hover:underline"
                    @click="markAllRead"
                >
                    Mark all read
                </button>
            </header>

            <div class="max-h-[min(65vh,28rem)] overflow-y-auto">
                <button
                    v-for="item in notifications"
                    :key="item.id"
                    type="button"
                    class="block w-full border-b border-slate-100 px-4 py-3 text-left transition-colors hover:bg-slate-50"
                    :class="item.read ? 'bg-white' : 'bg-blue-50/70'"
                    @click="openNotification(item)"
                >
                    <span class="flex items-start gap-2">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :class="item.read ? 'bg-transparent' : 'bg-blue-600'" />
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900">{{ item.title }}</span>
                            <span class="mt-0.5 block text-xs leading-relaxed text-slate-600">{{ item.message }}</span>
                            <span class="mt-1.5 block text-[11px] text-slate-400">{{ formatDate(item.createdAt) }}</span>
                        </span>
                    </span>
                </button>
                <p v-if="!notifications.length" class="px-4 py-8 text-center text-sm text-slate-500">
                    No notifications yet.
                </p>
            </div>
        </section>
    </div>
</template>
