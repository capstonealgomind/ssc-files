<script setup>
defineProps({
    class: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div
        :class="['sscevs-panel rounded-xl border', $props.class]"
    >
        <slot />
    </div>
</template>
