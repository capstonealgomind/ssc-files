<script setup>
import { computed } from 'vue';
import { VisArea, VisAxis, VisLine, VisXYContainer } from '@unovis/vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardDescription from '@/Components/ui/CardDescription.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import { ChartContainer } from '@/Components/ui/chart';

const props = defineProps({
    data: { type: Array, default: () => [] },
    range: { type: String, default: '24h' },
});

const chartConfig = {
    mobile: {
        label: 'Mobile',
        color: 'hsl(173 58% 39%)',
    },
    desktop: {
        label: 'Desktop',
        color: 'hsl(221 83% 53%)',
    },
};

const chartData = computed(() =>
    props.data.map((row) => ({
        date: new Date(row.date),
        label: row.label,
        mobile: Number(row.mobile ?? 0),
        desktop: Number(row.desktop ?? 0),
        total: Number(row.mobile ?? 0) + Number(row.desktop ?? 0),
    })),
);

const yDomain = computed(() => {
    const max = chartData.value.reduce((peak, row) => Math.max(peak, row.total), 0);
    return [0, Math.max(5, Math.ceil(max * 1.2))];
});

const rangeLabel = computed(() => {
    if (props.range === '1h') return 'Last hour';
    if (props.range === '7d') return 'Last 7 days';
    return 'Last 24 hours';
});

const svgDefs = `
  <linearGradient id="fillDesktop" x1="0" y1="0" x2="0" y2="1">
    <stop offset="5%" stop-color="var(--color-desktop)" stop-opacity="0.8" />
    <stop offset="95%" stop-color="var(--color-desktop)" stop-opacity="0.1" />
  </linearGradient>
  <linearGradient id="fillMobile" x1="0" y1="0" x2="0" y2="1">
    <stop offset="5%" stop-color="var(--color-mobile)" stop-opacity="0.8" />
    <stop offset="95%" stop-color="var(--color-mobile)" stop-opacity="0.1" />
  </linearGradient>
`;

const x = (d) => d.date;
const yStacked = [(d) => d.mobile, (d) => d.desktop];
const yLines = [(d) => d.mobile, (d) => d.mobile + d.desktop];
const areaColor = (_d, i) => ['url(#fillMobile)', 'url(#fillDesktop)'][i];
const lineColor = (_d, i) => [chartConfig.mobile.color, chartConfig.desktop.color][i];

function tickFormat(value) {
    const date = new Date(value);
    if (props.range === '7d') {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
}
</script>

<template>
    <Card class="overflow-hidden pt-0">
        <CardHeader class="flex flex-col gap-2 border-b py-5 sm:flex-row sm:items-center" style="border-color: hsl(240 5.9% 90%);">
            <div class="grid flex-1 gap-1">
                <CardTitle>Online voters by device</CardTitle>
                <CardDescription>
                    Live presence trend for {{ rangeLabel.toLowerCase() }}
                </CardDescription>
            </div>
            <div class="flex items-center gap-4 text-xs sm:ml-auto">
                <span class="inline-flex items-center gap-1.5" style="color: hsl(240 3.8% 46.1%);">
                    <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: chartConfig.mobile.color }" />
                    Mobile
                </span>
                <span class="inline-flex items-center gap-1.5" style="color: hsl(240 3.8% 46.1%);">
                    <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: chartConfig.desktop.color }" />
                    Desktop
                </span>
            </div>
        </CardHeader>

        <CardContent class="px-2 pt-4 sm:px-6 sm:pt-6 pb-4">
            <div
                v-if="chartData.length === 0"
                class="flex min-h-[250px] items-center justify-center text-sm"
                style="color: hsl(240 3.8% 46.1%);"
            >
                No presence data yet. Online voters will appear as they use the system.
            </div>

            <ChartContainer
                v-else
                :config="chartConfig"
                class="aspect-auto h-[250px] w-full"
            >
                <VisXYContainer
                    :data="chartData"
                    :svg-defs="svgDefs"
                    :height="250"
                    :margin="{ left: 8, right: 8, top: 8, bottom: 4 }"
                    :y-domain="yDomain"
                    class="w-full"
                >
                    <VisArea
                        :x="x"
                        :y="yStacked"
                        :color="areaColor"
                        :opacity="0.6"
                    />
                    <VisLine
                        :x="x"
                        :y="yLines"
                        :color="lineColor"
                        :line-width="1.5"
                    />
                    <VisAxis
                        type="x"
                        :x="x"
                        :tick-line="false"
                        :domain-line="false"
                        :grid-line="false"
                        :num-ticks="6"
                        :tick-format="tickFormat"
                    />
                    <VisAxis
                        type="y"
                        :num-ticks="4"
                        :tick-line="false"
                        :domain-line="false"
                    />
                </VisXYContainer>
            </ChartContainer>
        </CardContent>
    </Card>
</template>
