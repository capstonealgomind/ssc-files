<script setup>
import { computed, ref } from 'vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Button from '@/Components/ui/Button.vue';

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
    fluid: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select']);

const showPlatformDialog = ref(false);
const touchStartX = ref(0);
const touchStartY = ref(0);
const touchMoved = ref(false);

const hex = computed(() => props.candidate.department_color_hex || '#2563eb');

const rootTag = computed(() => {
    if (!props.selectable || props.fluid) {
        return 'div';
    }

    return 'button';
});

const cardStyle = computed(() => ({
    borderColor: props.selected ? 'hsl(221 83% 53%)' : 'hsl(240 5.9% 90%)',
    boxShadow: props.selected
        ? '0 8px 20px -6px hsl(221 83% 53% / 0.25)'
        : 'var(--sscevs-panel-shadow, 0 6px 18px -6px rgb(0 0 0 / 0.08))',
    background: '#fff',
}));

const badgeStyle = computed(() => ({
    backgroundColor: hex.value,
    color: '#fff',
    boxShadow: '0 2px 6px rgb(0 0 0 / 0.12)',
}));

const departmentLabelStyle = computed(() => ({
    color: hex.value,
}));

const partylistBadgeStyle = computed(() => {
    if (props.candidate.partylist_id) {
        return {
            backgroundColor: 'hsl(262 83% 94%)',
            color: 'hsl(262 83% 35%)',
            boxShadow: '0 2px 6px rgb(0 0 0 / 0.12)',
        };
    }

    return {
        backgroundColor: 'hsl(240 5.9% 10%)',
        color: '#fff',
        boxShadow: '0 2px 6px rgb(0 0 0 / 0.12)',
    };
});

const platformPreview = computed(() => truncateText(props.candidate.platform));

function truncateText(text, maxLength = 90) {
    if (!text || text.length <= maxLength) {
        return text;
    }

    const trimmed = text.slice(0, maxLength).trimEnd();
    const lastSpace = trimmed.lastIndexOf(' ');

    if (lastSpace > maxLength * 0.6) {
        return `${trimmed.slice(0, lastSpace)}…`;
    }

    return `${trimmed}…`;
}

function handleClick() {
    if (props.selectable && !touchMoved.value) {
        emit('select');
    }

    touchMoved.value = false;
}

function handleTouchStart(event) {
    const touch = event.touches[0];
    touchStartX.value = touch.clientX;
    touchStartY.value = touch.clientY;
    touchMoved.value = false;
}

function handleTouchMove(event) {
    const touch = event.touches[0];
    const deltaX = Math.abs(touch.clientX - touchStartX.value);
    const deltaY = Math.abs(touch.clientY - touchStartY.value);

    if (deltaX > 8 || deltaY > 8) {
        touchMoved.value = true;
    }
}

function handleKeydown(event) {
    if (!props.selectable) {
        return;
    }

    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        emit('select');
    }
}

function openPlatformDialog(event) {
    event.stopPropagation();
    showPlatformDialog.value = true;
}

function closePlatformDialog() {
    showPlatformDialog.value = false;
}
</script>

<template>
    <component
        :is="rootTag"
        :type="rootTag === 'button' ? 'button' : undefined"
        :role="selectable && rootTag === 'div' ? 'button' : undefined"
        :tabindex="selectable && rootTag === 'div' ? 0 : undefined"
        class="rounded-xl overflow-hidden text-left border-2 w-full transition-transform duration-150 ease-out"
        :class="[
            selectable && !fluid ? 'cursor-pointer hover:-translate-y-0.5' : selectable ? 'cursor-pointer' : '',
            fluid ? 'max-w-none mx-0' : 'max-w-[280px] mx-auto',
        ]"
        :style="cardStyle"
        @click="handleClick"
        @touchstart.passive="handleTouchStart"
        @touchmove.passive="handleTouchMove"
        @keydown="handleKeydown"
    >
        <div class="relative">
            <div class="absolute top-2.5 left-2.5 z-10 flex flex-col items-start gap-1">
                <span
                    v-if="candidate.department_acronym"
                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                    :style="badgeStyle"
                >
                    {{ candidate.department_acronym }}
                </span>
                <span
                    v-if="candidate.partylist_label"
                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wide"
                    :style="partylistBadgeStyle"
                >
                    {{ candidate.partylist_label }}
                </span>
            </div>

            <div
                class="w-full overflow-hidden bg-[hsl(240_4.8%_95.9%)] relative"
                :class="fluid ? 'aspect-[3/4] max-h-[38vh] min-h-[140px] sm:max-h-[42vh]' : 'aspect-[4/5]'"
            >
                <img
                    v-if="candidate.photo_url"
                    :src="candidate.photo_url"
                    :alt="candidate.name"
                    draggable="false"
                    class="absolute inset-0 w-full h-full object-cover object-top pointer-events-none select-none"
                />
                <div v-else class="absolute inset-0 flex items-center justify-center">
                    <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        style="color: hsl(240 3.8% 70%);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>

                <div
                    v-if="selected"
                    class="absolute top-2.5 right-2.5 h-6 w-6 rounded-full flex items-center justify-center z-10"
                    style="background: hsl(221 83% 53%); box-shadow: 0 2px 6px hsl(221 83% 53% / 0.4);"
                >
                    <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>

        <div
            class="border-t"
            :class="fluid ? 'px-2.5 py-2' : 'px-3 py-2.5'"
            style="border-color: hsl(240 5.9% 90%);"
        >
            <p
                class="font-semibold leading-tight break-words [overflow-wrap:anywhere]"
                :class="fluid ? 'text-[13px]' : 'text-sm'"
                style="color:hsl(240 10% 3.9%);"
            >
                {{ candidate.name }}
            </p>
            <p
                v-if="candidate.department"
                class="mt-0.5 font-medium break-words [overflow-wrap:anywhere]"
                :class="fluid ? 'text-[11px] leading-snug' : 'text-xs'"
                :style="departmentLabelStyle"
            >
                {{ candidate.department }}
            </p>
            <p
                v-if="candidate.course"
                class="mt-0.5 leading-snug break-words [overflow-wrap:anywhere]"
                :class="fluid ? 'text-[11px]' : 'text-xs'"
                style="color:hsl(240 3.8% 46.1%);"
            >
                {{ candidate.course }}
            </p>
            <div
                v-if="showPlatform && candidate.platform"
                class="flex items-start gap-1 mt-1.5 min-w-0"
            >
                <p
                    class="leading-relaxed min-w-0 flex-1 break-words [overflow-wrap:anywhere]"
                    :class="fluid ? 'text-[11px] line-clamp-3' : 'text-xs line-clamp-2'"
                    style="color:hsl(240 5.9% 35%);"
                >
                    {{ platformPreview }}
                </p>
                <button
                    v-if="candidate.platform"
                    type="button"
                    class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full transition-colors hover:bg-gray-100 mt-0.5"
                    style="color: hsl(221 83% 53%);"
                    aria-label="View full advocacies and platform"
                    @click="openPlatformDialog"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
            </div>
        </div>
    </component>

    <Dialog
        :show="showPlatformDialog"
        title="Advocacies & Platform"
        :description="candidate.name"
        wide
        @close="closePlatformDialog"
    >
        <p class="text-sm leading-relaxed whitespace-pre-wrap" style="color: hsl(240 5.9% 20%);">
            {{ candidate.platform }}
        </p>

        <div class="mt-6 flex justify-end">
            <Button type="button" variant="outline" @click="closePlatformDialog">Close</Button>
        </div>
    </Dialog>
</template>
