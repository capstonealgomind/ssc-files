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
        :class="['rounded-xl border shadow-sm', $props.class]"
        style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
    >
        <slot />
    </div>
</template>
