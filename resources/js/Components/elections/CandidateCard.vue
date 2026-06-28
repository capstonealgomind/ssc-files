<script setup>
import { computed } from 'vue';

const props = defineProps({
    candidate: {
        type: Object,
        required: true,
    },
    selected: {
        type: Boolean,
        default: false,
    },
    selectable: {
        type: Boolean,
        default: false,
    },
    showPlatform: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select']);

const hex = computed(() => props.candidate.department_color_hex || '#2563eb');

const cardStyle = computed(() => ({
    borderColor: hex.value,
    boxShadow: props.selected
        ? `0 0 0 2px ${hex.value}, 0 8px 20px -6px ${hex.value}44`
        : `0 0 0 1px ${hex.value}55, var(--sscevs-panel-shadow, 0 6px 18px -6px rgb(0 0 0 / 0.08))`,
    background: '#fff',
}));

const photoWrapStyle = computed(() => ({
    background: `linear-gradient(145deg, ${hex.value}22 0%, ${hex.value}0d 100%)`,
    borderBottom: `2px solid ${hex.value}`,
}));

const photoInnerStyle = computed(() => ({
    boxShadow: `inset 0 0 0 2px ${hex.value}66`,
    background: `${hex.value}12`,
}));

const badgeStyle = computed(() => ({
    backgroundColor: hex.value,
    color: '#fff',
    boxShadow: `0 2px 6px ${hex.value}55`,
}));

const footerStyle = computed(() => ({
    background: `linear-gradient(180deg, ${hex.value}0a 0%, #fff 100%)`,
}));

const departmentLabelStyle = computed(() => ({
    color: hex.value,
}));

function handleClick() {
    if (props.selectable) {
        emit('select');
    }
}
</script>

<template>
    <component
        :is="selectable ? 'button' : 'div'"
        :type="selectable ? 'button' : undefined"
        class="rounded-xl border overflow-hidden text-left transition-all w-full"
        :class="selectable ? 'cursor-pointer hover:-translate-y-0.5' : ''"
        :style="cardStyle"
        @click="handleClick"
    >
        <div class="p-2 pb-0 relative" :style="photoWrapStyle">
            <span
                v-if="candidate.department_acronym"
                class="absolute top-3 left-3 z-10 inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                :style="badgeStyle"
            >
                {{ candidate.department_acronym }}
            </span>

            <div class="aspect-square overflow-hidden rounded-lg relative" :style="photoInnerStyle">
                <img
                    v-if="candidate.photo_url"
                    :src="candidate.photo_url"
                    :alt="candidate.name"
                    class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        :style="{ color: `${hex}88` }">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>

                <div
                    v-if="selected"
                    class="absolute top-2 right-2 h-6 w-6 rounded-full flex items-center justify-center z-10"
                    :style="{ background: hex, boxShadow: `0 2px 6px ${hex}66` }"
                >
                    <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="px-3 py-2.5" :style="footerStyle">
            <p class="text-sm font-semibold leading-tight" style="color:hsl(240 10% 3.9%);">{{ candidate.name }}</p>
            <p v-if="candidate.department" class="text-xs mt-0.5 font-medium" :style="departmentLabelStyle">
                {{ candidate.department }}
            </p>
            <p v-if="showPlatform && candidate.platform" class="text-xs mt-1.5 leading-relaxed line-clamp-3"
                style="color:hsl(240 5.9% 35%);">
                {{ candidate.platform }}
            </p>
        </div>
    </component>
</template>
