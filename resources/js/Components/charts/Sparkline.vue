<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        required: true,
    },
    color: {
        type: String,
        default: 'hsl(221 83% 53%)',
    },
    width: {
        type: Number,
        default: 96,
    },
    height: {
        type: Number,
        default: 36,
    },
});

const path = computed(() => {
    if (props.data.length < 2) {
        return '';
    }

    const values = props.data.map(Number);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min;
    const step = props.width / (values.length - 1);

    // Flat / all-zero series: draw a baseline instead of a fake upward curve
    if (range === 0) {
        const y = props.height - 2;
        return `M 0 ${y} L ${props.width} ${y}`;
    }

    const points = values.map((value, index) => ({
        x: index * step,
        y: props.height - ((value - min) / range) * (props.height - 4) - 2,
    }));

    let d = `M ${points[0].x} ${points[0].y}`;

    for (let i = 0; i < points.length - 1; i += 1) {
        const current = points[i];
        const next = points[i + 1];
        const cx = (current.x + next.x) / 2;
        d += ` C ${cx} ${current.y}, ${cx} ${next.y}, ${next.x} ${next.y}`;
    }

    return d;
});
</script>

<template>
    <svg
        :width="width"
        :height="height"
        :viewBox="`0 0 ${width} ${height}`"
        fill="none"
        aria-hidden="true"
        class="overflow-visible"
    >
        <path
            :d="path"
            :stroke="color"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>
