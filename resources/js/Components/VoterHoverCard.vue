<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    voter: { type: Object, required: true },
});

const CARD_WIDTH = 320;
const OPEN_DELAY_MS = 220;
const CLOSE_DELAY_MS = 160;

const open = ref(false);
const triggerRef = ref(null);
const cardRef = ref(null);
const coords = ref({ top: 0, left: 0 });

let openTimer = null;
let closeTimer = null;

const initials = computed(() => {
    const name = props.voter?.name;
    if (!name) {
        return '?';
    }

    return name.split(' ').map((part) => part[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
});

const risk = computed(() => {
    const score = props.voter?.fraud_score ?? 0;
    if (score >= 80) return { label: 'LOW', bg: 'hsl(142 76% 94%)', text: 'hsl(142 71% 29%)', dot: 'hsl(142 71% 45%)' };
    if (score >= 50) return { label: 'MODERATE', bg: 'hsl(38 92% 94%)', text: 'hsl(38 62% 30%)', dot: 'hsl(38 92% 50%)' };
    if (score >= 20) return { label: 'HIGH', bg: 'hsl(25 95% 94%)', text: 'hsl(25 75% 30%)', dot: 'hsl(25 95% 53%)' };
    return { label: 'CRITICAL', bg: 'hsl(0 84% 94%)', text: 'hsl(0 62% 35%)', dot: 'hsl(0 84% 60%)' };
});

const status = computed(() => {
    const voter = props.voter;
    if (voter?.is_disabled) return { label: 'Disabled', bg: 'hsl(0 84% 94%)', text: 'hsl(0 72% 35%)' };
    if (voter?.is_expired) return { label: 'Expired', bg: 'hsl(0 84% 94%)', text: 'hsl(0 72% 35%)' };
    if (voter?.is_verified) return { label: 'Approved', bg: 'hsl(221 83% 94%)', text: 'hsl(221 83% 35%)' };
    return { label: 'Pending', bg: 'hsl(38 92% 94%)', text: 'hsl(38 62% 30%)' };
});

const schoolYearBadge = computed(() => {
    if (typeof props.voter?.school_year_updated !== 'boolean') {
        return null;
    }

    const yearLabel = props.voter.school_year_label ? ` for ${props.voter.school_year_label}` : '';

    if (props.voter.school_year_updated) {
        return {
            label: 'Updated',
            bg: 'hsl(142 76% 94%)',
            text: 'hsl(142 71% 29%)',
            title: `Year level confirmed${yearLabel}`,
        };
    }

    if (!props.voter.is_verified) {
        return null;
    }

    return {
        label: 'Outdated',
        bg: 'hsl(38 92% 94%)',
        text: 'hsl(38 62% 30%)',
        title: `Year level not updated${yearLabel}`,
    };
});

const details = computed(() => {
    const rows = [
        { label: 'Student ID', value: props.voter?.student_id_number, mono: true },
        { label: 'Voter ID', value: props.voter?.voter_id_number, mono: true },
        { label: 'Email', value: props.voter?.email },
        { label: 'Department', value: props.voter?.department },
        { label: 'Course', value: props.voter?.course },
        { label: 'Year', value: props.voter?.year_level },
    ];

    if (props.voter?.is_verified) {
        rows.push({ label: 'Approved', value: props.voter?.verified_at });
    }

    if (schoolYearBadge.value) {
        const yearLabel = props.voter?.school_year_label ? ` (${props.voter.school_year_label})` : '';
        rows.push({
            label: 'School year',
            value: `${schoolYearBadge.value.label}${yearLabel}`,
        });
    }

    rows.push({ label: 'Expires', value: props.voter?.account_expires_at });

    return rows;
});

function formatRegistered(value) {
    if (!value) {
        return null;
    }

    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const registeredLabel = computed(() => formatRegistered(props.voter?.created_at));

function clearTimers() {
    if (openTimer) {
        window.clearTimeout(openTimer);
        openTimer = null;
    }
    if (closeTimer) {
        window.clearTimeout(closeTimer);
        closeTimer = null;
    }
}

function positionCard() {
    const el = triggerRef.value;
    if (!el) {
        return;
    }

    const rect = el.getBoundingClientRect();
    const gap = 8;
    const estimatedHeight = 400;
    let left = rect.left;
    let top = rect.bottom + gap;

    if (left + CARD_WIDTH > window.innerWidth - 12) {
        left = Math.max(12, window.innerWidth - CARD_WIDTH - 12);
    }
    if (left < 12) {
        left = 12;
    }

    if (top + estimatedHeight > window.innerHeight - 12 && rect.top > estimatedHeight) {
        top = Math.max(12, rect.top - estimatedHeight - gap);
    }

    coords.value = { top, left };
}

async function showCard() {
    clearTimers();
    positionCard();
    open.value = true;
    await nextTick();

    const card = cardRef.value;
    const trigger = triggerRef.value;
    if (!card || !trigger) {
        return;
    }

    const cardRect = card.getBoundingClientRect();
    const triggerRect = trigger.getBoundingClientRect();
    let { top, left } = coords.value;

    if (cardRect.bottom > window.innerHeight - 12) {
        top = Math.max(12, triggerRect.top - cardRect.height - 8);
    }
    if (left + cardRect.width > window.innerWidth - 12) {
        left = Math.max(12, window.innerWidth - cardRect.width - 12);
    }

    coords.value = { top, left };
}

function hideCard() {
    clearTimers();
    open.value = false;
}

function onTriggerEnter() {
    clearTimers();
    if (open.value) {
        return;
    }
    openTimer = window.setTimeout(showCard, OPEN_DELAY_MS);
}

function onTriggerLeave() {
    clearTimers();
    closeTimer = window.setTimeout(hideCard, CLOSE_DELAY_MS);
}

function onCardEnter() {
    clearTimers();
}

function onCardLeave() {
    clearTimers();
    closeTimer = window.setTimeout(hideCard, CLOSE_DELAY_MS);
}

function onScrollOrResize() {
    if (open.value) {
        hideCard();
    }
}

watch(open, (isOpen) => {
    if (isOpen) {
        window.addEventListener('scroll', onScrollOrResize, true);
        window.addEventListener('resize', onScrollOrResize);
        return;
    }

    window.removeEventListener('scroll', onScrollOrResize, true);
    window.removeEventListener('resize', onScrollOrResize);
});

onBeforeUnmount(() => {
    clearTimers();
    window.removeEventListener('scroll', onScrollOrResize, true);
    window.removeEventListener('resize', onScrollOrResize);
});
</script>

<template>
    <div
        ref="triggerRef"
        class="min-w-0"
        @mouseenter="onTriggerEnter"
        @mouseleave="onTriggerLeave"
        @focusin="onTriggerEnter"
        @focusout="onTriggerLeave"
    >
        <slot />
    </div>

    <Teleport to="body">
        <div
            v-if="open"
            ref="cardRef"
            class="fixed z-[80] w-[21rem] rounded-xl border shadow-xl overflow-hidden"
            :style="{
                top: `${coords.top}px`,
                left: `${coords.left}px`,
                background: '#fff',
                borderColor: 'hsl(240 5.9% 90%)',
            }"
            role="tooltip"
            @mouseenter="onCardEnter"
            @mouseleave="onCardLeave"
        >
            <div class="p-4 flex items-start gap-3" style="background: hsl(240 4.8% 98.5%);">
                <div
                    class="h-24 w-24 rounded-xl overflow-hidden flex items-center justify-center text-lg font-bold shrink-0"
                    style="background: hsl(240 5.9% 10%); color: #fff;"
                >
                    <img
                        v-if="voter.profile_photo_url"
                        :src="voter.profile_photo_url"
                        :alt="voter.name"
                        class="h-full w-full object-cover"
                    >
                    <template v-else>{{ initials }}</template>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold leading-tight truncate" style="color: hsl(240 10% 3.9%);">
                        {{ voter.name }}
                    </p>
                    <div class="mt-1.5 flex flex-wrap gap-1">
                        <span
                            class="text-[10px] font-semibold px-1.5 py-0.5 rounded"
                            :style="{ backgroundColor: status.bg, color: status.text }"
                        >
                            {{ status.label }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                            :style="{ backgroundColor: risk.bg, color: risk.text }"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: risk.dot }"></span>
                            {{ risk.label }} · {{ voter.fraud_score ?? 0 }}
                        </span>
                        <span
                            v-if="schoolYearBadge"
                            class="text-[10px] font-semibold px-1.5 py-0.5 rounded"
                            :style="{ backgroundColor: schoolYearBadge.bg, color: schoolYearBadge.text }"
                            :title="schoolYearBadge.title"
                        >
                            {{ schoolYearBadge.label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="px-4 py-3 space-y-2">
                <div
                    v-for="row in details"
                    :key="row.label"
                    class="flex items-start gap-3 text-xs"
                >
                    <span class="w-[5.5rem] shrink-0 pt-px" style="color: hsl(240 3.8% 46.1%);">{{ row.label }}</span>
                    <span
                        class="min-w-0 break-all"
                        :class="row.mono ? 'font-mono' : ''"
                        style="color: hsl(240 10% 3.9%);"
                    >
                        {{ row.value || '—' }}
                    </span>
                </div>
            </div>

            <div
                v-if="registeredLabel"
                class="px-4 py-2.5 text-[11px] border-t"
                style="border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
            >
                Registered {{ registeredLabel }}
            </div>
        </div>
    </Teleport>
</template>
