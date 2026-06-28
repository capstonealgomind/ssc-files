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
        style="color: hsl(240 10% 3.9%);"
    >
        <slot />
    </h3>
</template>
