<script setup>
import { computed, ref } from 'vue';
import Card from '@/Components/ui/Card.vue';

const props = defineProps({
    points: { type: Array, default: () => [] },
    latest: { type: Number, default: 0 },
    subtitle: { type: String, default: '' },
});

const data = computed(() => {
    if (props.points.length) {
        return props.points.map((item) => ({
            phase: item.label,
            voters: Number(item.voters) || 0,
        }));
    }

    return [
        { phase: 'Mon', voters: 0 },
        { phase: 'Tue', voters: 0 },
        { phase: 'Wed', voters: 0 },
        { phase: 'Thu', voters: 0 },
        { phase: 'Fri', voters: 0 },
        { phase: 'Sat', voters: 0 },
        { phase: 'Sun', voters: 0 },
    ];
});

const latestVoters = computed(() => props.latest || data.value[data.value.length - 1]?.voters || 0);

const maxVoters = computed(() => {
    const peak = Math.max(...data.value.map((item) => item.voters), 0);
    return peak > 0 ? Math.ceil(peak * 1.1) : 10;
});

const gridLines = 4;

const chartWidth = 360;
const chartHeight = 148;
const padding = { top: 10, right: 12, bottom: 10, left: 12 };

const plotWidth = chartWidth - padding.left - padding.right;
const plotHeight = chartHeight - padding.top - padding.bottom;

const chartPoints = computed(() => {
    const items = data.value;
    const step = items.length > 1 ? plotWidth / (items.length - 1) : 0;

    return items.map((item, index) => {
        const x = padding.left + index * step;
        const y = padding.top + plotHeight - (item.voters / maxVoters.value) * plotHeight;

        return { ...item, x, y };
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
const activePhase = ref(null);

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

const linePath = computed(() => buildSmoothPath(chartPoints.value));
const areaPath = computed(() => buildSmoothPath(chartPoints.value, true));

function gridY(index) {
    return padding.top + (plotHeight / gridLines) * index;
}

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

function showTooltip(point) {
    const { x, y } = svgPointToWrap(point.x, point.y);
    activePhase.value = point.phase;
    tooltip.value = {
        show: true,
        x,
        y: Math.max(y - 6, 8),
        title: point.phase,
        value: `${Number(point.voters).toLocaleString()} ${point.voters === 1 ? 'ballot' : 'ballots'}`,
    };
}

function hideTooltip() {
    activePhase.value = null;
    tooltip.value = { ...tooltip.value, show: false };
}
</script>

<template>
    <Card class="overflow-hidden h-full flex flex-col">
        <div class="px-5 pt-4 pb-1">
            <p class="text-sm font-medium" style="color: hsl(240 3.8% 46.1%);">
                Ballots Over Time
            </p>
            <p class="text-2xl font-bold tracking-tight mt-0.5" style="color: hsl(240 10% 3.9%);">
                {{ Number(latestVoters).toLocaleString() }}
            </p>
            <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                {{ subtitle || 'Cumulative ballots this week' }}
            </p>
        </div>

        <div class="px-4 pb-3 flex-1 flex items-end">
            <div ref="chartWrap" class="relative w-full">
                <svg
                    ref="svgEl"
                    :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                    class="w-full h-auto max-h-[148px]"
                    role="img"
                    aria-label="Line chart showing cumulative ballots over time"
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
                        class="pointer-events-none"
                    />

                    <path
                        :d="linePath"
                        fill="none"
                        stroke="hsl(221 83% 53%)"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="pointer-events-none"
                    />

                    <g v-for="point in chartPoints" :key="point.phase">
                        <circle
                            :cx="point.x"
                            :cy="point.y"
                            :r="activePhase === point.phase ? 5.5 : 4"
                            fill="hsl(0 0% 100%)"
                            stroke="hsl(221 83% 53%)"
                            stroke-width="2"
                            class="pointer-events-none transition-[r] duration-150"
                        />
                        <circle
                            :cx="point.x"
                            :cy="point.y"
                            r="14"
                            fill="transparent"
                            class="cursor-pointer"
                            @mouseenter="showTooltip(point)"
                            @mousemove="showTooltip(point)"
                            @mouseleave="hideTooltip"
                        />
                    </g>
                </svg>

                <div
                    v-show="tooltip.show"
                    class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-md px-2.5 py-1.5 text-xs shadow-lg"
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
