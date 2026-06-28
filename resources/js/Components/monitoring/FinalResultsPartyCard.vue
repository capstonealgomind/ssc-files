<script setup>
function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase();
}

defineProps({
    party: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div
        class="rounded-xl border overflow-hidden sscevs-panel"
        style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
    >
        <div
            class="flex items-center justify-between gap-3 px-4 py-3 border-b"
            style="background-color: hsl(240 4.8% 98%); border-color: hsl(240 5.9% 90%);"
        >
            <h3 class="text-sm font-semibold" style="color: hsl(240 10% 3.9%);">
                {{ party.label }}
            </h3>
            <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold tabular-nums shrink-0"
                style="background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);"
            >
                {{ party.total_votes.toLocaleString() }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color: hsl(240 5.9% 90%);">
                        <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Candidate</th>
                        <th class="h-10 px-4 text-left align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Position</th>
                        <th class="h-10 px-4 text-right align-middle font-medium" style="color: hsl(240 3.8% 46.1%);">Votes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="candidate in party.candidates"
                        :key="`${candidate.name}-${candidate.position}`"
                        class="border-b last:border-b-0"
                        style="border-color: hsl(240 5.9% 94%);"
                    >
                        <td class="px-4 py-3 align-middle">
                            <div class="flex items-center gap-3 min-w-0">
                                <img
                                    v-if="candidate.photo_url"
                                    :src="candidate.photo_url"
                                    :alt="candidate.name"
                                    class="h-9 w-9 rounded-full object-cover shrink-0 border"
                                    style="border-color: hsl(240 5.9% 90%);"
                                />
                                <div
                                    v-else
                                    class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 border"
                                    style="background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%); border-color: hsl(240 5.9% 90%);"
                                >
                                    {{ getInitials(candidate.name) }}
                                </div>
                                <span class="font-semibold truncate" style="color: hsl(240 10% 3.9%);">
                                    {{ candidate.name }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-middle" style="color: hsl(240 3.8% 46.1%);">
                            {{ candidate.position }}
                        </td>
                        <td class="px-4 py-3 align-middle text-right font-bold tabular-nums" style="color: hsl(240 10% 3.9%);">
                            {{ candidate.votes.toLocaleString() }}
                        </td>
                    </tr>
                    <tr v-if="party.candidates.length === 0">
                        <td colspan="3" class="px-4 py-8 text-center" style="color: hsl(240 3.8% 46.1%);">
                            No candidates in this group.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
