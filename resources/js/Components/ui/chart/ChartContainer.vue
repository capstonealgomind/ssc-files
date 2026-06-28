<script setup>
import { computed, useId } from 'vue';

const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
    class: {
        type: String,
        default: '',
    },
});

const chartId = useId().replace(/:/g, '');

const chartStyle = computed(() => {
    const style = {};

    Object.entries(props.config).forEach(([key, value]) => {
        if (value?.color) {
            style[`--color-${key}`] = value.color;
        }
    });

    return style;
});
</script>

<template>
    <div
        :data-chart="chartId"
        class="sscevs-chart-container w-full text-xs"
        :class="class"
        :style="chartStyle"
    >
        <slot />
    </div>
</template>
