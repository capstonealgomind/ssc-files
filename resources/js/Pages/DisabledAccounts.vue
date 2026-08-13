<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Pagination from '@/Components/ui/Pagination.vue';

const props = defineProps({
    accounts: {
        type: Object,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
            from: 0,
            to: 0,
            prev_page_url: null,
            next_page_url: null,
        }),
    },
    counts: {
        type: Object,
        default: () => ({ disabled: 0, appeals: 0 }),
    },
});

const pendingAppeals = computed(() => props.counts.appeals ?? 0);
const rows = computed(() => props.accounts.data ?? []);

function appealIndicator(account) {
    if (account.has_pending_appeal || account.appeal_status === 'pending') {
        return {
            label: 'Appeal submitted',
            color: 'hsl(38 62% 30%)',
            background: 'hsl(38 92% 94%)',
        };
    }
    if (account.appeal_status === 'rejected') {
        return {
            label: 'Appeal rejected',
            color: 'hsl(0 72% 35%)',
            background: 'hsl(0 86% 94%)',
        };
    }
    return {
        label: 'No appeal',
        color: 'hsl(240 3.8% 46.1%)',
        background: 'hsl(240 4.8% 95.9%)',
    };
}

function goToPage(url) {
    if (!url) return;

    router.get(url, {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

function pageUrl(page) {
    const current = props.accounts.current_page ?? 1;
    if (page === current) return null;

    if (page === current - 1) return props.accounts.prev_page_url;
    if (page === current + 1) return props.accounts.next_page_url;

    const sample = props.accounts.next_page_url || props.accounts.prev_page_url;
    if (!sample) return `/disabled-accounts?page=${page}`;

    const url = new URL(sample, window.location.origin);
    url.searchParams.set('page', String(page));

    return `${url.pathname}?${url.searchParams.toString()}`;
}

function onPageChange(page) {
    goToPage(pageUrl(page));
}
</script>

<template>
    <AppLayout>
        <Head title="Disabled Accounts" />

        <template #header>
            <h2 class="text-xl font-semibold" style="color: hsl(240 10% 3.9%)">
                Disabled Accounts
            </h2>
        </template>

        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <p class="text-sm" style="color: hsl(240 3.8% 46.1%)">
                    Voters who missed the year level update deadline.
                    An orange badge means they already submitted an appeal.
                </p>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        style="background: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);"
                    >
                        {{ counts.disabled }} disabled
                    </span>
                    <span
                        v-if="pendingAppeals > 0"
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        style="background: hsl(38 92% 94%); color: hsl(38 62% 30%);"
                    >
                        {{ pendingAppeals }} appeal{{ pendingAppeals === 1 ? '' : 's' }}
                    </span>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border" style="border-color: hsl(240 5.9% 90%); background: #fff;">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full text-sm">
                        <thead>
                            <tr class="border-b" style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);">
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%)">Voter</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%)">Year level</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%)">Course</th>
                                <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%)">Appeal</th>
                                <th class="h-10 px-4 text-right align-middle font-medium" style="color: hsl(240 3.8% 46.1%)">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!rows.length">
                                <td colspan="5" class="px-4 py-10 text-center" style="color: hsl(240 3.8% 46.1%)">
                                    No disabled accounts right now.
                                </td>
                            </tr>
                            <tr
                                v-for="account in rows"
                                :key="account.id"
                                class="border-b last:border-0"
                                style="border-color: hsl(240 5.9% 90%)"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <div class="font-medium" style="color: hsl(240 10% 3.9%)">{{ account.name }}</div>
                                    <div class="text-xs" style="color: hsl(240 3.8% 46.1%)">
                                        {{ account.voter_id_number || account.email }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle" style="color: hsl(240 10% 3.9%)">
                                    {{ account.year_level || '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle max-w-[14rem]">
                                    <div class="truncate" style="color: hsl(240 10% 3.9%)">{{ account.course || '—' }}</div>
                                    <div class="text-xs truncate" style="color: hsl(240 3.8% 46.1%)">{{ account.department }}</div>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
                                        :style="{
                                            color: appealIndicator(account).color,
                                            background: appealIndicator(account).background,
                                        }"
                                    >
                                        {{ appealIndicator(account).label }}
                                    </span>
                                    <div
                                        v-if="account.appeal_submitted_at"
                                        class="text-[11px] mt-1"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ account.appeal_submitted_at }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle text-right">
                                    <Link :href="`/disabled-accounts/${account.id}`">
                                        <Button type="button" size="sm">Process</Button>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    :current-page="accounts.current_page"
                    :last-page="accounts.last_page"
                    :total="accounts.total"
                    :from="accounts.from"
                    :to="accounts.to"
                    @change="onPageChange"
                />
            </div>
        </div>
    </AppLayout>
</template>
