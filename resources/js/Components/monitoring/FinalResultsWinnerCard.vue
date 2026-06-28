<script setup>
import { computed } from 'vue';

const props = defineProps({
    winner: {
        type: Object,
        required: true,
    },
});

const initials = computed(() =>
    (props.winner.candidate_name ?? '?')
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

const hex = computed(() => props.winner.department_color_hex || '#2563eb');

const photoBorderStyle = computed(() => ({
    borderColor: hex.value,
}));

const initialsStyle = computed(() => ({
    backgroundColor: `${hex.value}18`,
    color: hex.value,
    borderColor: `${hex.value}55`,
}));

const badgeStyle = computed(() => ({
    backgroundColor: `${hex.value}22`,
    borderColor: 'hsl(0 0% 100%)',
}));

const badgeIconStyle = computed(() => ({
    color: hex.value,
}));
</script>

<template>
    <div
        class="rounded-xl border overflow-hidden sscevs-panel flex flex-col"
        style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
    >
        <div
            class="px-4 py-2.5 text-center border-b"
            style="background-color: hsl(240 4.8% 98%); border-color: hsl(240 5.9% 90%);"
        >
            <h3 class="text-sm font-bold" style="color: hsl(240 10% 3.9%);">
                {{ winner.position_name }}
            </h3>
        </div>

        <div class="flex-1 flex flex-col items-center text-center px-4 py-6">
            <div class="relative mb-4">
                <img
                    v-if="winner.photo_url"
                    :src="winner.photo_url"
                    :alt="winner.candidate_name"
                    class="h-20 w-20 rounded-full object-cover border-2"
                    :style="photoBorderStyle"
                />
                <div
                    v-else
                    class="h-20 w-20 rounded-full flex items-center justify-center text-lg font-semibold border-2"
                    :style="initialsStyle"
                >
                    {{ initials }}
                </div>

                <span
                    class="absolute -bottom-1 -right-1 h-7 w-7 rounded-full flex items-center justify-center border-2"
                    :style="badgeStyle"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        :style="badgeIconStyle"
                        aria-hidden="true"
                    >
                        <path d="M6 4h12v2.5c0 2.5-1.5 4.5-3.5 5.5L12 18l-2.5-6C7.5 11 6 9 6 6.5V4zm2 0v2.5c0 1.6.9 3 2.2 3.7L12 13.5l1.8-3.3C15.1 9.5 16 8.1 16 6.5V4H8z" />
                    </svg>
                </span>
            </div>

            <p class="text-base font-bold leading-snug" style="color: hsl(240 10% 3.9%);">
                {{ winner.candidate_name }}
            </p>
            <p class="text-sm mt-1" style="color: hsl(240 3.8% 46.1%);">
                {{ winner.partylist_name }}
            </p>
        </div>

        <div
            class="border-t px-4 py-3 flex items-center justify-between"
            style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 98%);"
        >
            <span class="text-xs font-medium" style="color: hsl(240 3.8% 46.1%);">Winning Votes</span>
            <span class="text-xl font-bold tabular-nums" style="color: hsl(240 10% 3.9%);">
                {{ winner.votes }}
            </span>
        </div>
    </div>
</template>
