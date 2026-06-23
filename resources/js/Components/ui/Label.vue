<script setup>
defineProps({
    htmlFor: {
        type: String,
        default: undefined,
    },
});
</script>

<template>
    <label
        :for="htmlFor"
        class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
        style="color: hsl(240 10% 3.9%);"
    >
        <slot />
    </label>
</template>
