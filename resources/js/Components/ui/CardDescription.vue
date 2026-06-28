<script setup>
defineProps({
    class: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <p
        :class="['text-sm', $props.class]"
        style="color: var(--sscevs-muted);"
    >
        <slot />
    </p>
</template>
