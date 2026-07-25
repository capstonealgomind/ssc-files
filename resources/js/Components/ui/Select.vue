<script setup>
defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: Boolean,
        default: false,
    },
    id: {
        type: String,
        default: undefined,
    },
    allowEmpty: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="relative w-full min-w-0">
        <select
            :id="id"
            :value="modelValue"
            :disabled="disabled"
            :class="[
                'sscevs-select flex h-9 w-full min-w-0 cursor-pointer rounded-md border bg-white py-1 pl-3 pr-9 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50',
                error
                    ? 'border-[hsl(0_84.2%_60.2%)] focus-visible:ring-[hsl(0_84.2%_60.2%)]'
                    : 'border-[hsl(240_5.9%_90%)] focus-visible:ring-[hsl(240_5.9%_10%)]',
            ]"
            style="color: hsl(240 10% 3.9%);"
            @change="emit('update:modelValue', $event.target.value)"
        >
            <option
                v-if="placeholder"
                value=""
                :disabled="!allowEmpty"
            >
                {{ placeholder }}
            </option>
            <option
                v-for="opt in options"
                :key="opt.value"
                :value="opt.value"
                :disabled="Boolean(opt.disabled)"
            >
                {{ opt.label }}
            </option>
        </select>

        <svg
            class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2"
            style="color: hsl(240 3.8% 46.1%);"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </div>
</template>
