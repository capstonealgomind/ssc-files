<script setup>
defineProps({
    class: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <h3
        :class="['text-base font-semibold leading-none tracking-tight', $props.class]"
        style="color: var(--sscevs-black);"
    >
        <slot />
    </h3>
</template>
