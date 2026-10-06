<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        default: undefined,
    },
    error: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: 'Select date and time',
    },
    clearable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const triggerRef = ref(null);
const panelRef = ref(null);
const panelStyle = ref({});
const viewYear = ref(new Date().getFullYear());
const viewMonth = ref(new Date().getMonth() + 1);
const draft = ref(blankDraft());

const instanceId = `dt-${Math.random().toString(36).slice(2)}`;
const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

function blankDraft() {
    const now = new Date();

    return {
        y: now.getFullYear(),
        m: now.getMonth() + 1,
        d: now.getDate(),
        hh: 8,
        mm: 0,
    };
}

function parseValue(value) {
    if (!value || !value.includes('T')) {
        return null;
    }

    const [datePart, timePart] = value.split('T');
    const [y, m, d] = datePart.split('-').map(Number);
    const [hh, mm] = timePart.split(':').map(Number);

    if (!y || !m || !d || Number.isNaN(hh) || Number.isNaN(mm)) {
        return null;
    }

    return { y, m, d, hh, mm };
}

function pad(value) {
    return String(value).padStart(2, '0');
}

function toValue(parts) {
    return `${parts.y}-${pad(parts.m)}-${pad(parts.d)}T${pad(parts.hh)}:${pad(parts.mm)}`;
}

function formatLabel(value) {
    const parts = parseValue(value);

    if (!parts) {
        return '';
    }

    return new Date(parts.y, parts.m - 1, parts.d, parts.hh, parts.mm).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

const display = computed(() => formatLabel(props.modelValue));

const monthLabel = computed(() => new Date(viewYear.value, viewMonth.value - 1, 1).toLocaleString('en-US', {
    month: 'long',
    year: 'numeric',
}));

const calendarCells = computed(() => {
    const first = new Date(viewYear.value, viewMonth.value - 1, 1).getDay();
    const count = new Date(viewYear.value, viewMonth.value, 0).getDate();
    const cells = Array.from({ length: first }, () => null);

    for (let day = 1; day <= count; day += 1) {
        cells.push(day);
    }

    return cells;
});

const hour12 = computed(() => {
    const hour = draft.value.hh % 12;

    return hour === 0 ? 12 : hour;
});

const period = computed(() => (draft.value.hh >= 12 ? 'PM' : 'AM'));

const minuteOptions = computed(() => {
    const minutes = [];

    for (let minute = 0; minute < 60; minute += 5) {
        minutes.push(minute);
    }

    if (!minutes.includes(draft.value.mm)) {
        minutes.push(draft.value.mm);
        minutes.sort((a, b) => a - b);
    }

    return minutes;
});

const draftLabel = computed(() => formatLabel(toValue(draft.value)));

const today = new Date();

function isSelected(day) {
    return day === draft.value.d
        && viewMonth.value === draft.value.m
        && viewYear.value === draft.value.y;
}

function isToday(day) {
    return day === today.getDate()
        && viewMonth.value === today.getMonth() + 1
        && viewYear.value === today.getFullYear();
}

function syncDraft() {
    const parsed = parseValue(props.modelValue) ?? blankDraft();
    draft.value = { ...parsed };
    viewYear.value = parsed.y;
    viewMonth.value = parsed.m;
}

function placePanel() {
    const trigger = triggerRef.value;

    if (!trigger) {
        return;
    }

    const rect = trigger.getBoundingClientRect();
    const width = 304;
    const height = 430;
    let left = rect.left;

    if (left + width > window.innerWidth - 8) {
        left = window.innerWidth - width - 8;
    }

    left = Math.max(8, left);

    let top = rect.bottom + 6;

    if (top + height > window.innerHeight - 8) {
        top = Math.max(8, rect.top - height - 6);
    }

    panelStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        width: `${width}px`,
    };
}

function onDocumentMouseDown(event) {
    const target = event.target;

    if (triggerRef.value?.contains(target) || panelRef.value?.contains(target)) {
        return;
    }

    open.value = false;
}

function onDocumentKeyDown(event) {
    if (event.key === 'Escape') {
        open.value = false;
    }
}

function onOtherOpen(event) {
    if (event.detail !== instanceId) {
        open.value = false;
    }
}

function onViewportChange() {
    if (open.value) {
        placePanel();
    }
}

function openPicker() {
    window.dispatchEvent(new CustomEvent('sscevs-datetime-open', { detail: instanceId }));
    syncDraft();
    open.value = true;
}

function togglePicker() {
    if (open.value) {
        open.value = false;
        return;
    }

    openPicker();
}

function shiftMonth(delta) {
    const next = new Date(viewYear.value, viewMonth.value - 1 + delta, 1);
    viewYear.value = next.getFullYear();
    viewMonth.value = next.getMonth() + 1;
}

function selectDay(day) {
    draft.value = {
        ...draft.value,
        y: viewYear.value,
        m: viewMonth.value,
        d: day,
    };
}

function setHour12(hour) {
    const normalized = Number(hour) % 12;
    draft.value = {
        ...draft.value,
        hh: normalized + (period.value === 'PM' ? 12 : 0),
    };
}

function setPeriod(nextPeriod) {
    const normalized = draft.value.hh % 12;
    draft.value = {
        ...draft.value,
        hh: normalized + (nextPeriod === 'PM' ? 12 : 0),
    };
}

function setMinute(minute) {
    draft.value = {
        ...draft.value,
        mm: Number(minute),
    };
}

function apply() {
    emit('update:modelValue', toValue(draft.value));
    open.value = false;
}

function clearValue() {
    emit('update:modelValue', '');
    open.value = false;
}

watch(open, (isOpen) => {
    if (isOpen) {
        placePanel();
        document.addEventListener('mousedown', onDocumentMouseDown);
        document.addEventListener('keydown', onDocumentKeyDown);
        return;
    }

    document.removeEventListener('mousedown', onDocumentMouseDown);
    document.removeEventListener('keydown', onDocumentKeyDown);
});

window.addEventListener('sscevs-datetime-open', onOtherOpen);
window.addEventListener('resize', onViewportChange);
window.addEventListener('scroll', onViewportChange, true);

onBeforeUnmount(() => {
    window.removeEventListener('sscevs-datetime-open', onOtherOpen);
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
    document.removeEventListener('mousedown', onDocumentMouseDown);
    document.removeEventListener('keydown', onDocumentKeyDown);
});
</script>

<template>
    <div class="min-w-0">
        <button
            :id="id"
            ref="triggerRef"
            type="button"
            class="flex h-9 w-full min-w-0 items-center gap-2 rounded-md border bg-white px-3 text-left text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1"
            :class="error
                ? 'border-[hsl(0_84.2%_60.2%)] focus-visible:ring-[hsl(0_84.2%_60.2%)]'
                : 'border-[hsl(240_5.9%_90%)] focus-visible:ring-[hsl(240_5.9%_10%)]'"
            :aria-expanded="open"
            aria-haspopup="dialog"
            @click="togglePicker"
        >
            <svg class="h-4 w-4 shrink-0" style="color: hsl(240 3.8% 46.1%);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span
                class="truncate"
                :style="display ? 'color: hsl(240 10% 3.9%);' : 'color: hsl(240 3.8% 46.1%);'"
            >
                {{ display || placeholder }}
            </span>
        </button>

        <Teleport to="body">
            <div
                v-if="open"
                ref="panelRef"
                class="fixed z-[80] rounded-xl border bg-white p-3 shadow-xl"
                style="border-color: hsl(240 5.9% 90%);"
                :style="panelStyle"
                role="dialog"
                aria-label="Choose date and time"
            >
                <div class="mb-2 flex items-center justify-between">
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-[hsl(240_4.8%_95.9%)]"
                        style="color: hsl(240 10% 3.9%);"
                        aria-label="Previous month"
                        @click="shiftMonth(-1)"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <p class="text-sm font-semibold" style="color: hsl(240 10% 3.9%);">{{ monthLabel }}</p>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-[hsl(240_4.8%_95.9%)]"
                        style="color: hsl(240 10% 3.9%);"
                        aria-label="Next month"
                        @click="shiftMonth(1)"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center">
                    <span
                        v-for="weekday in weekdays"
                        :key="weekday"
                        class="py-1 text-[11px] font-medium"
                        style="color: hsl(240 3.8% 46.1%);"
                    >
                        {{ weekday }}
                    </span>
                    <span v-for="(day, index) in calendarCells" :key="`${monthLabel}-${index}`">
                        <button
                            v-if="day"
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-sm"
                            :style="isSelected(day)
                                ? 'background-color: hsl(215 85% 42%); color: white;'
                                : isToday(day)
                                    ? 'background-color: hsl(215 70% 96%); color: hsl(215 85% 34%);'
                                    : 'color: hsl(240 10% 3.9%);'"
                            @click="selectDay(day)"
                        >
                            {{ day }}
                        </button>
                    </span>
                </div>

                <div class="mt-3 border-t pt-3" style="border-color: hsl(240 5.9% 90%);">
                    <p class="mb-2 text-xs font-medium" style="color: hsl(240 3.8% 46.1%);">Time</p>
                    <div class="flex items-center gap-2">
                        <select
                            class="h-9 flex-1 rounded-md border bg-white px-2 text-sm"
                            style="border-color: hsl(240 5.9% 90%); color: hsl(240 10% 3.9%);"
                            :value="hour12"
                            aria-label="Hour"
                            @change="setHour12($event.target.value)"
                        >
                            <option v-for="hour in 12" :key="hour" :value="hour">{{ pad(hour) }}</option>
                        </select>
                        <span style="color: hsl(240 3.8% 46.1%);">:</span>
                        <select
                            class="h-9 flex-1 rounded-md border bg-white px-2 text-sm"
                            style="border-color: hsl(240 5.9% 90%); color: hsl(240 10% 3.9%);"
                            :value="draft.mm"
                            aria-label="Minute"
                            @change="setMinute($event.target.value)"
                        >
                            <option v-for="minute in minuteOptions" :key="minute" :value="minute">{{ pad(minute) }}</option>
                        </select>
                        <div class="flex overflow-hidden rounded-md border" style="border-color: hsl(240 5.9% 90%);">
                            <button
                                type="button"
                                class="h-9 px-2.5 text-xs font-medium"
                                :style="period === 'AM'
                                    ? 'background-color: hsl(215 85% 42%); color: white;'
                                    : 'background-color: white; color: hsl(240 10% 3.9%);'"
                                @click="setPeriod('AM')"
                            >
                                AM
                            </button>
                            <button
                                type="button"
                                class="h-9 px-2.5 text-xs font-medium"
                                :style="period === 'PM'
                                    ? 'background-color: hsl(215 85% 42%); color: white;'
                                    : 'background-color: white; color: hsl(240 10% 3.9%);'"
                                @click="setPeriod('PM')"
                            >
                                PM
                            </button>
                        </div>
                    </div>
                    <p class="mt-2 text-xs" style="color: hsl(240 10% 3.9%);">{{ draftLabel }}</p>
                </div>

                <div class="mt-3 flex items-center justify-end gap-2">
                    <button
                        v-if="clearable"
                        type="button"
                        class="h-8 rounded-md px-3 text-xs font-medium hover:bg-[hsl(240_4.8%_95.9%)]"
                        style="color: hsl(240 3.8% 46.1%);"
                        @click="clearValue"
                    >
                        Clear
                    </button>
                    <button
                        type="button"
                        class="h-8 rounded-md px-3 text-xs font-medium text-white"
                        style="background-color: hsl(215 85% 42%);"
                        @click="apply"
                    >
                        Set
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
