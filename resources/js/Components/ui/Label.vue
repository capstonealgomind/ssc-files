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
        class="text-sm font-medium leading-none text-[var(--sscevs-black)] peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
    >
        <slot />
    </label>
</template>
