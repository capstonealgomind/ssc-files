<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/ui/Input.vue';

const props = defineProps({
    attempts: { type: Array, default: () => [] },
});

const search = ref('');

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.attempts;

    return props.attempts.filter((attempt) =>
        [
            attempt.action,
            attempt.ip_address,
            attempt.device_fingerprint,
            attempt.user_name,
            attempt.user_email,
            attempt.voter_id_number,
        ]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(q)),
    );
});

function actionStyle(action) {
    if (action === 'otp_success' || action === 'register') {
        return { bg: 'hsl(142 76% 94%)', color: 'hsl(142 71% 29%)' };
    }
    if (String(action).includes('fail')) {
        return { bg: 'hsl(0 84% 94%)', color: 'hsl(0 62% 35%)' };
    }
    return { bg: 'hsl(221 83% 94%)', color: 'hsl(221 83% 35%)' };
}

function formatAction(action) {
    return String(action || '—').replaceAll('_', ' ');
}
</script>

<template>
    <AppLayout>
        <Head title="Registration Attempts" />
        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Registration Attempts</h1>
        </template>

        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                    Device and IP activity from voter registration and OTP flows.
                </p>
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search name, IP, action…"
                    class="sm:w-72"
                />
            </div>

            <div
                class="rounded-xl border overflow-hidden"
                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);">
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">When</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Action</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">User</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">IP Address</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Device</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filtered.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-sm" style="color: hsl(240 3.8% 46.1%);">
                                    No registration attempts found.
                                </td>
                            </tr>
                            <tr
                                v-for="attempt in filtered"
                                :key="attempt.id"
                                class="border-b transition-colors hover:bg-gray-50"
                                style="border-color: hsl(240 5.9% 90%);"
                            >
                                <td class="px-4 py-3 align-middle whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                                    {{ attempt.created_at || '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded text-xs font-semibold capitalize"
                                        :style="{ background: actionStyle(attempt.action).bg, color: actionStyle(attempt.action).color }"
                                    >
                                        {{ formatAction(attempt.action) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <p class="font-medium" style="color: hsl(240 10% 3.9%);">{{ attempt.user_name || '—' }}</p>
                                    <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                        {{ attempt.voter_id_number || attempt.user_email || '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 align-middle font-mono text-xs" style="color: hsl(240 10% 3.9%);">
                                    {{ attempt.ip_address || '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle font-mono text-xs max-w-[12rem] truncate" style="color: hsl(240 3.8% 46.1%);" :title="attempt.device_fingerprint">
                                    {{ attempt.device_fingerprint || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
