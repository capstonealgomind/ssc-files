<script setup>
import { computed } from 'vue';
import Card from '@/Components/ui/Card.vue';

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
});

const chartData = computed(() =>
    props.data.length
        ? props.data
        : [{ department: 'No data', votes: 0 }],
);

const maxVotes = computed(() => {
    const peak = Math.max(...chartData.value.map((item) => Number(item.votes) || 0), 0);
    if (peak <= 0) return 10;
    const padded = Math.ceil(peak * 1.1);
    const step = padded <= 20 ? 5 : padded <= 100 ? 10 : padded <= 500 ? 50 : 100;
    return Math.ceil(padded / step) * step;
});

const yTicks = computed(() => {
    const max = maxVotes.value;
    const count = 5;
    const step = max / count;
    return Array.from({ length: count + 1 }, (_, i) => Math.round(i * step));
});

const chartWidth = 920;
const chartHeight = 280;
const padding = { top: 16, right: 16, bottom: 44, left: 48 };

const plotWidth = chartWidth - padding.left - padding.right;
const plotHeight = chartHeight - padding.top - padding.bottom;

const bars = computed(() => {
    const items = chartData.value;
    const slotWidth = plotWidth / Math.max(items.length, 1);
    const barWidth = Math.min(42, slotWidth * 0.58);

    return items.map((item, index) => {
        const votes = Number(item.votes) || 0;
        const height = (votes / maxVotes.value) * plotHeight;
        const x = padding.left + index * slotWidth + (slotWidth - barWidth) / 2;
        const y = padding.top + plotHeight - height;

        return {
            ...item,
            votes,
            x,
            y,
            width: barWidth,
            height,
            labelX: x + barWidth / 2,
            labelY: padding.top + plotHeight + 22,
        };
    });
});

function tickY(value) {
    return padding.top + plotHeight - (value / maxVotes.value) * plotHeight;
}

function roundedTopBarPath(x, y, width, height, radius = 6) {
    if (height <= 0) {
        return '';
    }

    const r = Math.min(radius, width / 2, height);

    return [
        `M ${x} ${y + height}`,
        `L ${x} ${y + r}`,
        `Q ${x} ${y} ${x + r} ${y}`,
        `L ${x + width - r} ${y}`,
        `Q ${x + width} ${y} ${x + width} ${y + r}`,
        `L ${x + width} ${y + height}`,
        'Z',
    ].join(' ');
}
</script>

<template>
    <Card class="overflow-hidden h-full flex flex-col">
        <div class="px-6 pt-6 pb-2">
            <h3 class="text-base font-semibold tracking-tight" style="color: hsl(240 10% 3.9%);">
                Votes by Department
            </h3>
            <p class="text-sm mt-1" style="color: hsl(240 3.8% 46.1%);">
                Ballots cast by academic department
            </p>
        </div>

        <div class="px-2 pb-4 sm:px-4 flex-1">
            <div class="w-full overflow-x-auto">
                <svg
                    :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                    class="min-w-[720px] w-full h-auto min-h-[220px]"
                    role="img"
                    aria-label="Bar chart showing votes by department"
                >
                    <defs>
                        <linearGradient id="department-bar-gradient" x1="0" y1="1" x2="0" y2="0">
                            <stop offset="0%" stop-color="hsl(230 65% 28%)" />
                            <stop offset="100%" stop-color="hsl(221 83% 53%)" />
                        </linearGradient>
                    </defs>

                    <g v-for="tick in yTicks" :key="tick">
                        <line
                            :x1="padding.left"
                            :y1="tickY(tick)"
                            :x2="chartWidth - padding.right"
                            :y2="tickY(tick)"
                            stroke="hsl(240 5.9% 90%)"
                            stroke-dasharray="4 4"
                            stroke-width="1"
                        />
                        <text
                            :x="padding.left - 10"
                            :y="tickY(tick)"
                            text-anchor="end"
                            dominant-baseline="middle"
                            fill="hsl(240 3.8% 46.1%)"
                            font-size="11"
                        >
                            {{ tick }}
                        </text>
                    </g>

                    <line
                        :x1="padding.left"
                        :y1="padding.top + plotHeight"
                        :x2="chartWidth - padding.right"
                        :y2="padding.top + plotHeight"
                        stroke="hsl(240 5.9% 90%)"
                        stroke-width="1"
                    />

                    <g v-for="bar in bars" :key="bar.department">
                        <path
                            :d="roundedTopBarPath(bar.x, bar.y, bar.width, bar.height)"
                            fill="url(#department-bar-gradient)"
                        />
                        <text
                            :x="bar.labelX"
                            :y="bar.labelY"
                            text-anchor="middle"
                            fill="hsl(240 3.8% 46.1%)"
                            font-size="10"
                            font-weight="500"
                        >
                            {{ bar.department }}
                        </text>
                    </g>
                </svg>
            </div>
        </div>
    </Card>
</template>
