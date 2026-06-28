<script setup>
const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: '',
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
    autocomplete: {
        type: String,
        default: undefined,
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :autocomplete="autocomplete"
        :class="[
            'flex h-9 w-full min-w-0 rounded-md border bg-white px-3 py-1 text-sm text-[var(--sscevs-black)] shadow-sm transition-colors placeholder:text-[var(--sscevs-muted)] focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50',
            error
                ? 'border-[hsl(0_84.2%_60.2%)] focus-visible:ring-[hsl(0_84.2%_60.2%)]'
                : 'border-[var(--sscevs-border)] focus-visible:ring-[var(--sscevs-blue)] focus-visible:border-[var(--sscevs-blue)]',
        ]"
        @input="emit('update:modelValue', $event.target.value)"
    />
</template>
