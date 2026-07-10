<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
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
const chartHeight = 230;
const padding = { top: 12, right: 12, bottom: 36, left: 34 };

const plotWidth = chartWidth - padding.left - padding.right;
const plotHeight = chartHeight - padding.top - padding.bottom;

/** Cap slot width so few departments stay near the Y-axis instead of stretching across the card. */
const MAX_BAR_SLOT = 84;

const bars = computed(() => {
    const items = chartData.value;
    const count = Math.max(items.length, 1);
    const slotWidth = Math.min(MAX_BAR_SLOT, plotWidth / count);
    const barWidth = Math.min(40, Math.max(16, slotWidth * 0.58));

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
            hitHeight: Math.max(height, 12),
            hitY: padding.top + plotHeight - Math.max(height, 12),
            labelX: x + barWidth / 2,
            labelY: padding.top + plotHeight + 18,
            slotX: padding.left + index * slotWidth,
            slotWidth,
        };
    });
});

const chartWrap = ref(null);
const svgEl = ref(null);
const tooltip = ref({
    show: false,
    x: 0,
    y: 0,
    title: '',
    value: '',
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

/** Map SVG viewBox coords into the scrollable chart wrapper. */
function svgPointToWrap(svgX, svgY) {
    const wrap = chartWrap.value;
    const svg = svgEl.value;
    if (!wrap || !svg) {
        return { x: 0, y: 0 };
    }

    const wrapRect = wrap.getBoundingClientRect();
    const svgRect = svg.getBoundingClientRect();
    const scaleX = svgRect.width / chartWidth;
    const scaleY = svgRect.height / chartHeight;

    return {
        x: svgRect.left - wrapRect.left + wrap.scrollLeft + svgX * scaleX,
        y: svgRect.top - wrapRect.top + wrap.scrollTop + svgY * scaleY,
    };
}

function showTooltip(bar) {
    const tipX = bar.x + bar.width / 2;
    const tipY = bar.height > 0 ? bar.y : padding.top + plotHeight;
    const { x, y } = svgPointToWrap(tipX, tipY);

    tooltip.value = {
        show: true,
        x,
        y: Math.max(y - 6, 8),
        title: bar.department,
        value: `${Number(bar.votes).toLocaleString()} ${bar.votes === 1 ? 'vote' : 'votes'}`,
    };
}

function hideTooltip() {
    tooltip.value = { ...tooltip.value, show: false };
}

function onViewportChange() {
    hideTooltip();
}

onMounted(() => {
    window.addEventListener('resize', onViewportChange);
    window.visualViewport?.addEventListener('resize', onViewportChange);
});

onUnmounted(() => {
    window.removeEventListener('resize', onViewportChange);
    window.visualViewport?.removeEventListener('resize', onViewportChange);
});
</script>

<template>
    <Card class="overflow-hidden h-full min-w-0 w-full flex flex-col">
        <div class="px-3 sm:px-5 pt-3 sm:pt-4 pb-1 shrink-0">
            <h3 class="text-sm sm:text-base font-semibold tracking-tight" style="color: hsl(240 10% 3.9%);">
                Votes by Department
            </h3>
            <p class="text-xs sm:text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                Ballots cast by academic department
            </p>
        </div>

        <div class="px-1 sm:px-2 pb-2 sm:pb-3 flex-1 min-w-0">
            <div ref="chartWrap" class="relative w-full min-w-0">
                <svg
                    ref="svgEl"
                    :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                    class="block w-full h-[clamp(9rem,28vw,14.5rem)]"
                    preserveAspectRatio="none"
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
                            :x="padding.left - 8"
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
                            class="pointer-events-none"
                        />
                        <text
                            :x="bar.labelX"
                            :y="bar.labelY"
                            text-anchor="middle"
                            fill="hsl(240 3.8% 46.1%)"
                            font-size="10"
                            font-weight="500"
                            class="pointer-events-none"
                        >
                            {{ bar.department }}
                        </text>
                        <!-- Hit target matches the bar only (min height for tiny values) -->
                        <rect
                            :x="bar.x"
                            :y="bar.hitY"
                            :width="bar.width"
                            :height="bar.hitHeight"
                            fill="transparent"
                            class="cursor-pointer"
                            @mouseenter="showTooltip(bar)"
                            @mousemove="showTooltip(bar)"
                            @mouseleave="hideTooltip"
                        />
                    </g>
                </svg>

                <div
                    v-show="tooltip.show"
                    class="pointer-events-none absolute z-10 max-w-[min(12rem,70%)] -translate-x-1/2 -translate-y-full rounded-md px-2.5 py-1.5 text-[11px] sm:text-xs shadow-lg"
                    :style="{
                        left: `${tooltip.x}px`,
                        top: `${tooltip.y}px`,
                        backgroundColor: 'hsl(240 10% 3.9%)',
                        color: 'hsl(0 0% 100%)',
                    }"
                    role="tooltip"
                >
                    <p class="font-semibold leading-tight">{{ tooltip.title }}</p>
                    <p class="mt-0.5 opacity-90">{{ tooltip.value }}</p>
                </div>
            </div>
        </div>
    </Card>
</template>
