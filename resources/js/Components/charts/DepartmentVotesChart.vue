<script setup>
import { computed } from 'vue';
import Card from '@/Components/ui/Card.vue';

const data = [
    { department: 'BPEd', votes: 700 },
    { department: 'BSIS', votes: 500 },
    { department: 'ACT', votes: 550 },
    { department: 'BSIT AMT', votes: 400 },
    { department: 'BET AMT', votes: 800 },
    { department: 'BindTech AMT', votes: 900 },
    { department: 'BSE', votes: 1050 },
    { department: 'BEED', votes: 950 },
    { department: 'BSIT ELX', votes: 1150 },
    { department: 'BET ELX', votes: 1300 },
];

const maxVotes = 1450;
const yTicks = [0, 150, 350, 500, 750, 990, 1150, 1300, 1450];

const chartWidth = 920;
const chartHeight = 280;
const padding = { top: 16, right: 16, bottom: 44, left: 48 };

const plotWidth = chartWidth - padding.left - padding.right;
const plotHeight = chartHeight - padding.top - padding.bottom;

const bars = computed(() => {
    const slotWidth = plotWidth / data.length;
    const barWidth = Math.min(42, slotWidth * 0.58);

    return data.map((item, index) => {
        const height = (item.votes / maxVotes) * plotHeight;
        const x = padding.left + index * slotWidth + (slotWidth - barWidth) / 2;
        const y = padding.top + plotHeight - height;

        return {
            ...item,
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
    return padding.top + plotHeight - (value / maxVotes) * plotHeight;
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
                Sample turnout across academic departments
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
