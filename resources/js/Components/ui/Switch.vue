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
        class="sscevs-switch"
        :aria-checked="model"
        :disabled="disabled"
        @click="model = !model"
    >
        <span class="sscevs-switch-knob" />
    </button>
</template>
