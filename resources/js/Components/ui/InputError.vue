<script setup>
defineProps({
    message: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <p
        v-if="message"
        class="text-xs font-medium"
        style="color: hsl(0 84.2% 60.2%);"
    >
        {{ message }}
    </p>
</template>
