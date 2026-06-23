<script setup>
import { computed } from 'vue';
import Card from '@/Components/ui/Card.vue';

const data = [6200, 7100, 7800, 8400, 8900, 9530];

const chartWidth = 320;
const chartHeight = 80;
const padding = { top: 8, right: 8, bottom: 8, left: 8 };
const plotWidth = chartWidth - padding.left - padding.right;
const plotHeight = chartHeight - padding.top - padding.bottom;

const points = computed(() => {
    const min = Math.min(...data);
    const max = Math.max(...data);
    const range = max - min || 1;
    const step = plotWidth / (data.length - 1);

    return data.map((value, index) => ({
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
                9,530
            </p>
            <p class="text-xs mt-1" style="color: hsl(240 3.8% 46.1%);">
                +20.1% from last month
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
