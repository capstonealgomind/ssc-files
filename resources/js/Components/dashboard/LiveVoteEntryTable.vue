<script setup>
import Card from '@/Components/ui/Card.vue';

const props = defineProps({
    entries: { type: Array, default: () => [] },
});

const statusStyles = {
    recorded: {
        label: 'Recorded',
        background: 'hsl(221 83% 53% / 0.12)',
        color: 'hsl(221 83% 40%)',
    },
    verified: {
        label: 'Verified',
        background: 'hsl(142 76% 94%)',
        color: 'hsl(142 71% 29%)',
    },
};

function voterId(entry) {
    return entry.voter_id ?? entry.voter ?? '—';
}

function statusStyle(status) {
    return statusStyles[status] ?? statusStyles.recorded;
}
</script>

<template>
    <Card class="overflow-hidden">
        <div
            class="flex flex-col gap-3 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-6"
            style="border-color: hsl(240 5.9% 90%);"
        >
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-semibold tracking-tight" style="color: hsl(240 10% 3.9%);">
                        Live Vote Entry
                    </h3>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
                        style="background-color: hsl(142 76% 94%); color: hsl(142 71% 29%);"
                    >
                        <span class="relative flex h-2 w-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                                style="background-color: hsl(142 71% 45%);"
                            />
                            <span
                                class="relative inline-flex h-2 w-2 rounded-full"
                                style="background-color: hsl(142 71% 29%);"
                            />
                        </span>
                        Live
                    </span>
                </div>
                <p class="text-sm mt-1" style="color: hsl(240 3.8% 46.1%);">
                    Real-time ballot submissions as voters cast their votes
                </p>
            </div>
            <span
                class="inline-flex w-fit items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                style="background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);"
            >
                {{ entries.length }} recent {{ entries.length === 1 ? 'entry' : 'entries' }}
            </span>
        </div>

        <div v-if="entries.length === 0"
            class="flex flex-col items-center justify-center py-14 px-4 text-center">
            <div class="h-12 w-12 rounded-full flex items-center justify-center mb-3"
                style="background:hsl(240 4.8% 95.9%);">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    style="color:hsl(240 3.8% 46.1%);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">No votes yet</p>
            <p class="text-xs mt-1" style="color:hsl(240 3.8% 46.1%);">Entries will appear here when voting begins.</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b"
                        style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);"
                    >
                        <th class="h-10 px-4 text-left align-middle font-medium whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                            Time
                        </th>
                        <th class="h-10 px-4 text-left align-middle font-medium whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                            Voter ID
                        </th>
                        <th class="h-10 px-4 text-left align-middle font-medium whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                            Department
                        </th>
                        <th class="h-10 px-4 text-left align-middle font-medium whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                            Position
                        </th>
                        <th class="h-10 px-4 text-left align-middle font-medium whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                            Candidate
                        </th>
                        <th class="h-10 px-4 text-left align-middle font-medium whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(entry, index) in entries"
                        :key="index"
                        class="border-b transition-colors hover:bg-gray-50"
                        style="border-color: hsl(240 5.9% 90%);"
                    >
                        <td class="px-4 py-3 align-middle whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                            {{ entry.time }}
                        </td>
                        <td class="px-4 py-3 align-middle font-medium whitespace-nowrap" style="color: hsl(240 10% 3.9%);">
                            {{ voterId(entry) }}
                        </td>
                        <td class="px-4 py-3 align-middle whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                            {{ entry.department }}
                        </td>
                        <td class="px-4 py-3 align-middle whitespace-nowrap" style="color: hsl(240 10% 3.9%);">
                            {{ entry.position }}
                        </td>
                        <td class="px-4 py-3 align-middle whitespace-nowrap font-medium" style="color: hsl(240 10% 3.9%);">
                            {{ entry.candidate }}
                        </td>
                        <td class="px-4 py-3 align-middle">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :style="{
                                    backgroundColor: statusStyle(entry.status).background,
                                    color: statusStyle(entry.status).color,
                                }"
                            >
                                {{ statusStyle(entry.status).label }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Card>
</template>
