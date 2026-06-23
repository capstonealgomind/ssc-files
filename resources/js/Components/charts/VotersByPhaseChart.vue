<script setup>
import { computed } from 'vue';
import Card from '@/Components/ui/Card.vue';

const data = [
    { phase: 'Phase 1', voters: 820 },
    { phase: 'Phase 2', voters: 980 },
    { phase: 'Phase 3', voters: 1120 },
    { phase: 'Phase 4', voters: 1260 },
    { phase: 'Phase 5', voters: 1380 },
    { phase: 'Phase 6', voters: 1450 },
];

const latestVoters = computed(() => data[data.length - 1].voters);

const maxVoters = 1500;
const gridLines = 4;

const chartWidth = 360;
const chartHeight = 220;
const padding = { top: 12, right: 12, bottom: 12, left: 12 };

const plotWidth = chartWidth - padding.left - padding.right;
const plotHeight = chartHeight - padding.top - padding.bottom;

const points = computed(() => {
    const step = plotWidth / (data.length - 1);

    return data.map((item, index) => {
        const x = padding.left + index * step;
        const y = padding.top + plotHeight - (item.voters / maxVoters) * plotHeight;

        return { ...item, x, y };
    });
});

function buildSmoothPath(pointList, closeToBaseline = false) {
    if (pointList.length < 2) {
        return '';
    }

    let d = `M ${pointList[0].x} ${pointList[0].y}`;

    for (let i = 0; i < pointList.length - 1; i += 1) {
        const current = pointList[i];
        const next = pointList[i + 1];
        const cx = (current.x + next.x) / 2;
        d += ` C ${cx} ${current.y}, ${cx} ${next.y}, ${next.x} ${next.y}`;
    }

    if (closeToBaseline) {
        const baseline = padding.top + plotHeight;
        const last = pointList[pointList.length - 1];
        const first = pointList[0];
        d += ` L ${last.x} ${baseline} L ${first.x} ${baseline} Z`;
    }

    return d;
}

const linePath = computed(() => buildSmoothPath(points.value));
const areaPath = computed(() => buildSmoothPath(points.value, true));

function gridY(index) {
    return padding.top + (plotHeight / gridLines) * index;
}
</script>

<template>
    <Card class="overflow-hidden h-full flex flex-col">
        <div class="px-6 pt-6 pb-2">
            <p class="text-sm font-medium" style="color: hsl(240 3.8% 46.1%);">
                Voters by Voting Phase
            </p>
            <p class="text-3xl font-bold tracking-tight mt-1" style="color: hsl(240 10% 3.9%);">
                +{{ latestVoters.toLocaleString() }}
            </p>
            <p class="text-xs mt-1" style="color: hsl(240 3.8% 46.1%);">
                +18.2% from last phase
            </p>
        </div>

        <div class="px-4 pb-5 flex-1 flex items-end">
            <svg
                :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                class="w-full h-auto"
                role="img"
                aria-label="Line chart showing voters by voting phase"
            >
                <defs>
                    <linearGradient id="voters-line-gradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="hsl(221 83% 53% / 0.35)" />
                        <stop offset="100%" stop-color="hsl(221 83% 53% / 0)" />
                    </linearGradient>
                </defs>

                <g v-for="index in gridLines + 1" :key="index">
                    <line
                        :x1="padding.left"
                        :y1="gridY(index - 1)"
                        :x2="chartWidth - padding.right"
                        :y2="gridY(index - 1)"
                        stroke="hsl(240 5.9% 90%)"
                        stroke-width="1"
                    />
                </g>

                <path
                    :d="areaPath"
                    fill="url(#voters-line-gradient)"
                />

                <path
                    :d="linePath"
                    fill="none"
                    stroke="hsl(221 83% 53%)"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />

                <circle
                    v-for="point in points"
                    :key="point.phase"
                    :cx="point.x"
                    :cy="point.y"
                    r="4"
                    fill="hsl(0 0% 100%)"
                    stroke="hsl(221 83% 53%)"
                    stroke-width="2"
                />
            </svg>
        </div>
    </Card>
</template>
