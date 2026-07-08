<script setup>
defineProps({
    id: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const model = defineModel({ type: Boolean, default: false });
</script>

<template>
    <button
        :id="id"
        type="button"
        role="switch"
        :aria-checked="model"
        :disabled="disabled"
        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        :style="model
            ? 'background-color: hsl(240 5.9% 10%);'
            : 'background-color: hsl(240 4.8% 95.9%);'"
        @click="model = !model"
    >
        <span
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full shadow ring-0 transition-transform"
            :class="model ? 'translate-x-5' : 'translate-x-0'"
            style="background-color: hsl(0 0% 100%);"
        />
    </button>
</template>
