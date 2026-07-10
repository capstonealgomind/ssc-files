<script setup>
import { computed } from 'vue';
import { VisAxis, VisGroupedBar, VisXYContainer } from '@unovis/vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardDescription from '@/Components/ui/CardDescription.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import { ChartContainer } from '@/Components/ui/chart';

const props = defineProps({
    data: { type: Array, default: () => [] },
});

const chartConfig = {
    queued: {
        label: 'Queued',
        color: 'hsl(221 83% 53%)',
    },
    completed: {
        label: 'Completed',
        color: 'hsl(142 71% 45%)',
    },
    failed: {
        label: 'Failed',
        color: 'hsl(0 84% 60%)',
    },
};

const chartData = computed(() =>
    props.data.map((row) => ({
        label: row.label,
        queued: Number(row.queued ?? 0),
        completed: Number(row.completed ?? 0),
        failed: Number(row.failed ?? 0),
    })),
);

const x = (_d, i) => i;
const y = [
    (d) => d.queued,
    (d) => d.completed,
    (d) => d.failed,
];
const color = (_d, i) => [
    chartConfig.queued.color,
    chartConfig.completed.color,
    chartConfig.failed.color,
][i];

const tickValues = computed(() => chartData.value.map((_, i) => i));
const tickFormat = (v) => chartData.value[v]?.label ?? '';
const xDomain = computed(() => [-0.5, Math.max(chartData.value.length - 0.5, 0.5)]);
</script>

<template>
    <Card class="overflow-hidden">
        <CardHeader>
            <CardTitle>Incoming vote traffic</CardTitle>
            <CardDescription>
                Ballot submissions queued, completed, and failed over the last 12 minutes
            </CardDescription>
            <div class="flex flex-wrap items-center gap-4 text-xs mt-2">
                <span class="inline-flex items-center gap-1.5" style="color: hsl(240 3.8% 46.1%);">
                    <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: chartConfig.queued.color }" />
                    Queued
                </span>
                <span class="inline-flex items-center gap-1.5" style="color: hsl(240 3.8% 46.1%);">
                    <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: chartConfig.completed.color }" />
                    Completed
                </span>
                <span class="inline-flex items-center gap-1.5" style="color: hsl(240 3.8% 46.1%);">
                    <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: chartConfig.failed.color }" />
                    Failed
                </span>
            </div>
        </CardHeader>

        <CardContent>
            <div
                v-if="chartData.length === 0"
                class="flex min-h-[250px] items-center justify-center text-sm"
                style="color: hsl(240 3.8% 46.1%);"
            >
                No queue traffic yet.
            </div>

            <ChartContainer
                v-else
                :config="chartConfig"
                class="min-h-[250px] w-full"
            >
                <VisXYContainer
                    :data="chartData"
                    :height="250"
                    :x-domain="xDomain"
                    class="w-full"
                >
                    <VisGroupedBar
                        :x="x"
                        :y="y"
                        :color="color"
                        :rounded-corners="3"
                        :group-max-width="28"
                    />
                    <VisAxis
                        type="x"
                        :tick-line="false"
                        :domain-line="false"
                        :grid-line="false"
                        :tick-values="tickValues"
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
