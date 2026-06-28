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
        style="color: hsl(240 3.8% 46.1%);"
    >
        <slot />
    </p>
</template>
