<script setup>
defineProps({
    position: {
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
            class="flex items-center justify-between px-4 py-3 border-b"
            style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 98%);"
        >
            <h3 class="text-sm font-semibold" style="color: hsl(262 83% 35%);">
                {{ position.name }}
            </h3>
            <span
                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium tabular-nums"
                style="background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);"
            >
                {{ position.total_votes }}
            </span>
        </div>

        <div class="divide-y" style="divide-color: hsl(240 5.9% 94%);">
            <div
                v-for="candidate in position.candidates"
                :key="candidate.id"
                class="px-4 py-3"
            >
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex items-start gap-2 min-w-0">
                        <svg
                            v-if="candidate.show_trophy"
                            class="h-4 w-4 shrink-0 mt-0.5"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            style="color: hsl(43 96% 56%);"
                            aria-hidden="true"
                        >
                            <path d="M6 4h12v2.5c0 2.5-1.5 4.5-3.5 5.5L12 18l-2.5-6C7.5 11 6 9 6 6.5V4zm2 0v2.5c0 1.6.9 3 2.2 3.7L12 13.5l1.8-3.3C15.1 9.5 16 8.1 16 6.5V4H8z" />
                        </svg>
                        <span
                            v-else
                            class="w-4 shrink-0"
                            aria-hidden="true"
                        />
                        <p class="text-sm leading-snug min-w-0">
                            <span class="font-semibold" style="color: hsl(240 10% 3.9%);">
                                {{ candidate.name }}
                            </span>
                            <span class="font-normal" style="color: hsl(240 3.8% 46.1%);">
                                ({{ candidate.partylist_label }})
                            </span>
                        </p>
                    </div>
                    <p class="text-sm font-medium shrink-0 tabular-nums" style="color: hsl(240 10% 3.9%);">
                        {{ candidate.votes }} ({{ candidate.percentage }}%)
                    </p>
                </div>

                <div
                    class="h-2 rounded-full overflow-hidden"
                    style="background-color: hsl(240 4.8% 95.9%);"
                >
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :style="{
                            width: `${candidate.percentage}%`,
                            backgroundColor: candidate.is_leader
                                ? 'hsl(262 83% 58%)'
                                : 'hsl(240 5.9% 82%)',
                        }"
                    />
                </div>
            </div>

            <div
                v-if="position.candidates.length === 0"
                class="px-4 py-8 text-center text-sm"
                style="color: hsl(240 3.8% 46.1%);"
            >
                No candidates for this position.
            </div>
        </div>
    </div>
</template>
