<script setup>
import Card from '@/Components/ui/Card.vue';

const entries = [
    {
        id: 1,
        time: '2:41:08 PM',
        voter: 'V-10482',
        department: 'BSIT ELX',
        position: 'Governor',
        candidate: 'Maria Santos',
        status: 'recorded',
    },
    {
        id: 2,
        time: '2:40:54 PM',
        voter: 'V-10317',
        department: 'BSE',
        position: 'Vice Governor',
        candidate: 'Juan Dela Cruz',
        status: 'verified',
    },
    {
        id: 3,
        time: '2:40:41 PM',
        voter: 'V-10204',
        department: 'BEED',
        position: 'Secretary',
        candidate: 'Ana Reyes',
        status: 'recorded',
    },
    {
        id: 4,
        time: '2:40:29 PM',
        voter: 'V-10156',
        department: 'BPEd',
        position: 'Treasurer',
        candidate: 'Carlo Mendoza',
        status: 'verified',
    },
    {
        id: 5,
        time: '2:40:12 PM',
        voter: 'V-10089',
        department: 'BSIS',
        position: 'Governor',
        candidate: 'Maria Santos',
        status: 'recorded',
    },
    {
        id: 6,
        time: '2:39:58 PM',
        voter: 'V-10021',
        department: 'ACT',
        position: 'Auditor',
        candidate: 'Liza Fernandez',
        status: 'recorded',
    },
    {
        id: 7,
        time: '2:39:44 PM',
        voter: 'V-09987',
        department: 'BET ELX',
        position: 'PRO',
        candidate: 'Mark Villanueva',
        status: 'verified',
    },
    {
        id: 8,
        time: '2:39:30 PM',
        voter: 'V-09912',
        department: 'BindTech AMT',
        position: 'Governor',
        candidate: 'Elena Torres',
        status: 'recorded',
    },
];

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
</script>

<template>
    <Card class="overflow-hidden">
        <div
            class="flex flex-col gap-3 border-b px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
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
                {{ entries.length }} recent entries
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b"
                        style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);"
                    >
                        <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">
                            Time
                        </th>
                        <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">
                            Voter ID
                        </th>
                        <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">
                            Department
                        </th>
                        <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">
                            Position
                        </th>
                        <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">
                            Candidate
                        </th>
                        <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="entry in entries"
                        :key="entry.id"
                        class="border-b transition-colors hover:bg-gray-50"
                        style="border-color: hsl(240 5.9% 90%);"
                    >
                        <td class="px-4 py-3 align-middle whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                            {{ entry.time }}
                        </td>
                        <td class="px-4 py-3 align-middle font-medium whitespace-nowrap" style="color: hsl(240 10% 3.9%);">
                            {{ entry.voter }}
                        </td>
                        <td class="px-4 py-3 align-middle whitespace-nowrap" style="color: hsl(240 3.8% 46.1%);">
                            {{ entry.department }}
                        </td>
                        <td class="px-4 py-3 align-middle whitespace-nowrap" style="color: hsl(240 10% 3.9%);">
                            {{ entry.position }}
                        </td>
                        <td class="px-4 py-3 align-middle whitespace-nowrap" style="color: hsl(240 10% 3.9%);">
                            {{ entry.candidate }}
                        </td>
                        <td class="px-4 py-3 align-middle">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :style="{
                                    backgroundColor: statusStyles[entry.status].background,
                                    color: statusStyles[entry.status].color,
                                }"
                            >
                                {{ statusStyles[entry.status].label }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Card>
</template>
