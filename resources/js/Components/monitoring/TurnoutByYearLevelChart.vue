<script setup>
import { computed } from 'vue';
import { Orientation } from '@unovis/ts';
import { VisAxis, VisGroupedBar, VisXYContainer } from '@unovis/vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardDescription from '@/Components/ui/CardDescription.vue';
import CardFooter from '@/Components/ui/CardFooter.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import { ChartContainer } from '@/Components/ui/chart';

const props = defineProps({
    yearLevels: {
        type: Array,
        default: () => [],
    },
});

const chartConfig = {
    voted: {
        label: 'Voted',
        color: 'hsl(142 71% 45%)',
    },
};

// All year levels (show even with 0 votes so axis is populated)
const chartData = computed(() =>
    props.yearLevels.map((item) => ({
        label: item.label,
        voted: item.voted ?? 0,
        total_registered: item.total_registered ?? 0,
    })),
);

const summary = computed(() => {
    const voted = props.yearLevels.reduce((sum, item) => sum + (item.voted ?? 0), 0);
    const registered = props.yearLevels.reduce((sum, item) => sum + (item.total_registered ?? 0), 0);
    const turnout = registered > 0 ? Math.round((voted / registered) * 1000) / 10 : 0;
    return { voted, registered, turnout };
});

// In Unovis horizontal mode:
// x = category (index) → rendered on the left Y-axis
// y = value (voted) → bar extends horizontally on X-axis
const x = (_d, i) => i;
const y = (d) => d.voted;

const tickValues = computed(() => chartData.value.map((_, i) => i));
const tickFormat = (v) => chartData.value[v]?.label ?? '';

// xDomain controls category axis padding in horizontal mode
const xDomain = computed(() => [-0.5, chartData.value.length - 0.5]);
</script>

<template>
    <Card class="overflow-hidden h-full flex flex-col">
        <CardHeader>
            <CardTitle>Turnout by Year Level</CardTitle>
            <CardDescription>Ballots cast per year level</CardDescription>
        </CardHeader>

        <CardContent class="flex-1">
            <div
                v-if="chartData.length === 0"
                class="flex min-h-[250px] items-center justify-center text-sm"
                style="color: hsl(240 3.8% 46.1%);"
            >
                No year level data yet.
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
                        :color="chartConfig.voted.color"
                        :rounded-corners="4"
                        :orientation="Orientation.Horizontal"
                        :group-max-width="28"
                    />
                    <VisAxis
                        type="y"
                        :tick-line="false"
                        :domain-line="false"
                        :grid-line="false"
                        :tick-values="tickValues"
                        :tick-format="tickFormat"
                    />
                    <VisAxis
                        type="x"
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
                <span v-if="summary.registered > 0">
                    {{ summary.turnout }}% overall turnout
                </span>
                <span v-else>No registered voters yet</span>
                <svg
                    v-if="summary.voted > 0"
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
                {{ summary.voted }} of {{ summary.registered }} registered voters have voted
                across {{ chartData.length }} year levels.
            </p>
        </CardFooter>
    </Card>
</template>
