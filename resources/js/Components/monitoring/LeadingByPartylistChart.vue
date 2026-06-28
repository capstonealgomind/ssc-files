<script setup>
import { computed } from 'vue';
import { VisAxis, VisGroupedBar, VisXYContainer } from '@unovis/vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardDescription from '@/Components/ui/CardDescription.vue';
import CardFooter from '@/Components/ui/CardFooter.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import { ChartContainer } from '@/Components/ui/chart';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    subtitle: {
        type: String,
        default: 'Positions currently led by each partylist',
    },
});

const palette = [
    'hsl(221 83% 53%)',
    'hsl(262 83% 58%)',
    'hsl(142 71% 45%)',
    'hsl(38 92% 50%)',
    'hsl(0 72% 51%)',
    'hsl(199 89% 48%)',
    'hsl(280 65% 60%)',
    'hsl(173 58% 39%)',
];

const chartConfig = {
    positions: {
        label: 'Leading positions',
        color: 'hsl(221 83% 53%)',
    },
};

const chartData = computed(() =>
    [...props.items]
        .sort((a, b) => (b.count ?? 0) - (a.count ?? 0))
        .map((item, index) => ({
            label: item.label,
            count: item.count ?? 0,
            color: palette[index % palette.length],
        })),
);

const summary = computed(() => {
    const total = chartData.value.reduce((sum, item) => sum + item.count, 0);
    const top = chartData.value[0] ?? null;
    return { total, top, partylistCount: chartData.value.length };
});

const x = (_d, i) => i;
const y = (d) => d.count;
const barColor = (d) => d.color;

const tickValues = computed(() => chartData.value.map((_, i) => i));
const tickFormat = (v) => chartData.value[v]?.label ?? '';
const xDomain = computed(() => [-0.5, Math.max(0, chartData.value.length - 0.5)]);
</script>

<template>
    <Card class="overflow-hidden">
        <CardHeader>
            <CardTitle>Leading positions by partylist</CardTitle>
            <CardDescription>{{ subtitle }}</CardDescription>
        </CardHeader>

        <CardContent>
            <div
                v-if="chartData.length === 0"
                class="flex min-h-[250px] items-center justify-center text-sm"
                style="color: hsl(240 3.8% 46.1%);"
            >
                No vote data yet.
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
                        :color="barColor"
                        :rounded-corners="4"
                        :bar-padding="0.1"
                        :group-padding="0"
                        :group-max-width="64"
                    />
                    <VisAxis
                        type="x"
                        :x="x"
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

        <CardFooter
            v-if="chartData.length > 0"
            class="flex-col items-start gap-2 border-t pt-6 text-sm"
            style="border-color: hsl(240 5.9% 90%);"
        >
            <div class="flex items-center gap-2 font-medium leading-none" style="color: hsl(240 10% 3.9%);">
                <template v-if="summary.top">
                    {{ summary.top.label }} leads {{ summary.top.count }}
                    {{ summary.top.count === 1 ? 'position' : 'positions' }}
                </template>
                <svg
                    v-if="summary.total > 0"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="h-4 w-4"
                >
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                    <polyline points="16 7 22 7 22 13" />
                </svg>
            </div>
            <p class="leading-none" style="color: hsl(240 3.8% 46.1%);">
                {{ summary.total }} leading
                {{ summary.total === 1 ? 'position' : 'positions' }}
                across {{ summary.partylistCount }}
                {{ summary.partylistCount === 1 ? 'partylist' : 'partylists' }}.
            </p>
        </CardFooter>
    </Card>
</template>
