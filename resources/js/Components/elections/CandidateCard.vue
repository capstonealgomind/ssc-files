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

const hex = computed(() => props.candidate.department_color_hex || '#64748b');

const departmentName = computed(() =>
    props.candidate.department || props.candidate.department_name || null,
);

const courseName = computed(() =>
    props.candidate.course || props.candidate.course_name || null,
);

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
        : undefined,
    background: '#fff',
}));

const partylistBadgeStyle = computed(() => {
    if (props.candidate.partylist_id) {
        return {
            backgroundColor: 'hsl(262 83% 94%)',
            color: 'hsl(262 83% 35%)',
        };
    }

    return {
        backgroundColor: 'hsl(240 5.9% 10%)',
        color: '#fff',
    };
});

const platformPreview = computed(() => truncateText(props.candidate.platform, 100));

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
}

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
        class="rounded-xl overflow-hidden text-left border flex flex-col w-full max-w-none transition-shadow duration-150 ease-out hover:shadow-md"
        :class="[
            selectable ? 'cursor-pointer hover:-translate-y-0.5' : '',
            selected ? 'border-2' : '',
        ]"
        :style="cardStyle"
        @click="handleClick"
        @touchstart.passive="handleTouchStart"
        @touchmove.passive="handleTouchMove"
        @keydown="handleKeydown"
    >
        <!-- Photo -->
        <div class="relative bg-[hsl(240_4.8%_95.9%)]">
            <div class="absolute top-2.5 left-2.5 z-10 flex flex-col items-start gap-1">
                <span
                    v-if="candidate.partylist_label"
                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wide"
                    :style="partylistBadgeStyle"
                >
                    {{ candidate.partylist_label }}
                </span>
            </div>

            <div class="absolute top-2.5 right-2.5 z-10 flex items-start gap-1">
                <span
                    v-if="candidate.position"
                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold"
                    style="background-color: hsl(221 83% 94%); color: hsl(221 83% 35%);"
                >
                    {{ candidate.position }}
                </span>
                <div
                    v-if="selected"
                    class="h-6 w-6 rounded-full flex items-center justify-center shrink-0"
                    style="background: hsl(221 83% 53%); box-shadow: 0 2px 6px hsl(221 83% 53% / 0.4);"
                >
                    <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>

            <img
                v-if="candidate.photo_url"
                :src="candidate.photo_url"
                :alt="candidate.name"
                draggable="false"
                class="block w-full aspect-[4/5] object-cover pointer-events-none select-none"
            />
            <div
                v-else
                class="aspect-[4/5] flex flex-col items-center justify-center gap-2"
            >
                <div
                    class="h-16 w-16 rounded-full flex items-center justify-center text-lg font-semibold"
                    style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);"
                >
                    {{ getInitials(candidate.name) }}
                </div>
            </div>
        </div>

        <!-- Details -->
        <div
            class="flex flex-col flex-1 border-t space-y-2"
            :class="fluid ? 'p-2.5' : 'p-3.5'"
            style="border-color: hsl(240 5.9% 90%);"
        >
            <div>
                <p
                    class="font-semibold leading-tight break-words [overflow-wrap:anywhere]"
                    :class="fluid ? 'text-[13px]' : 'text-sm'"
                    style="color: hsl(240 10% 3.9%);"
                >
                    {{ candidate.name }}
                </p>
            </div>

            <div v-if="departmentName || courseName" class="space-y-0.5">
                <p
                    v-if="departmentName"
                    class="inline-flex items-center gap-1.5 font-medium break-words [overflow-wrap:anywhere]"
                    :class="fluid ? 'text-[11px] leading-snug' : 'text-xs'"
                    :style="{ color: hex }"
                >
                    <span
                        class="h-2 w-2 rounded-full shrink-0"
                        :style="{ backgroundColor: hex }"
                    />
                    {{ departmentName }}
                </p>
                <p
                    v-if="courseName"
                    class="leading-snug break-words [overflow-wrap:anywhere] line-clamp-2"
                    :class="fluid ? 'text-[11px]' : 'text-xs'"
                    style="color: hsl(240 3.8% 46.1%);"
                >
                    {{ courseName }}
                </p>
            </div>

            <div
                v-if="showPlatform && candidate.platform"
                class="flex items-start gap-1 min-w-0"
            >
                <p
                    class="leading-relaxed min-w-0 flex-1 break-words [overflow-wrap:anywhere]"
                    :class="fluid ? 'text-[11px] line-clamp-3' : 'text-xs line-clamp-2'"
                    style="color: hsl(240 5.9% 35%);"
                >
                    {{ platformPreview }}
                </p>
                <button
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
