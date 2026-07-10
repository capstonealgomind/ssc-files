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
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <select
        :id="id"
        :value="modelValue"
        :disabled="disabled"
        :class="[
            'flex h-9 w-full cursor-pointer rounded-md border bg-white px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50',
            error
                ? 'border-[hsl(0_84.2%_60.2%)] focus-visible:ring-[hsl(0_84.2%_60.2%)]'
                : 'border-[hsl(240_5.9%_90%)] focus-visible:ring-[hsl(240_5.9%_10%)]',
        ]"
        style="color: hsl(240 10% 3.9%);"
        @change="emit('update:modelValue', $event.target.value)"
    >
        <option value="" disabled>{{ placeholder }}</option>
        <option
            v-for="opt in options"
            :key="opt.value"
            :value="opt.value"
            :disabled="Boolean(opt.disabled)"
        >
            {{ opt.label }}
        </option>
    </select>
</template>
