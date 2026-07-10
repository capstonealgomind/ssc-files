<script setup>
import { computed } from 'vue';
import Card from '@/Components/ui/Card.vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    subtitle: { type: String, default: '' },
    sparkline: { type: Array, default: () => [] },
});

const data = computed(() => {
    const points = props.sparkline.length ? props.sparkline.map(Number) : [0];
    return points.length === 1 ? [0, ...points] : points;
});

const chartWidth = 320;
const chartHeight = 80;
const padding = { top: 8, right: 8, bottom: 8, left: 8 };
const plotWidth = chartWidth - padding.left - padding.right;
const plotHeight = chartHeight - padding.top - padding.bottom;

const points = computed(() => {
    const values = data.value;
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    const step = values.length > 1 ? plotWidth / (values.length - 1) : 0;

    return values.map((value, index) => ({
        x: padding.left + index * step,
        y: padding.top + plotHeight - ((value - min) / range) * plotHeight,
    }));
});

const linePath = computed(() => {
    const pts = points.value;
    if (pts.length < 2) {
        return '';
    }

    let d = `M ${pts[0].x} ${pts[0].y}`;

    for (let i = 0; i < pts.length - 1; i += 1) {
        const current = pts[i];
        const next = pts[i + 1];
        const cx = (current.x + next.x) / 2;
        d += ` C ${cx} ${current.y}, ${cx} ${next.y}, ${next.x} ${next.y}`;
    }

    return d;
});
</script>

<template>
    <Card class="flex flex-col h-full">
        <div class="px-5 pt-5">
            <p class="text-sm font-medium" style="color: hsl(240 3.8% 46.1%);">
                Total Votes Cast
            </p>
            <p class="text-3xl font-bold tracking-tight mt-1" style="color: hsl(240 10% 3.9%);">
                {{ Number(value).toLocaleString() }}
            </p>
            <p class="text-xs mt-1" style="color: hsl(240 3.8% 46.1%);">
                {{ subtitle || 'Position-level votes recorded' }}
            </p>
        </div>

        <div class="mt-auto px-3 pb-4 pt-6">
            <svg
                :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                class="w-full h-20"
                aria-hidden="true"
            >
                <path
                    :d="linePath"
                    fill="none"
                    stroke="hsl(240 5.9% 10%)"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <circle
                    v-for="(point, index) in points"
                    :key="index"
                    :cx="point.x"
                    :cy="point.y"
                    r="3.5"
                    fill="hsl(0 0% 100%)"
                    stroke="hsl(240 5.9% 10%)"
                    stroke-width="2"
                />
            </svg>
        </div>
    </Card>
</template>
