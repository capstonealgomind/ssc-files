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
    departments: {
        type: Array,
        default: () => [],
    },
    subtitle: {
        type: String,
        default: 'Voted vs registered voters by department',
    },
});

const chartConfig = {
    registered: {
        label: 'Registered',
        color: 'hsl(240 5.9% 82%)',
    },
    voted: {
        label: 'Voted',
        color: 'hsl(240 5.9% 10%)',
    },
};

const chartData = computed(() =>
    props.departments.map((item) => ({
        label: item.label,
        registered: item.total_registered ?? 0,
        voted: item.voted ?? 0,
        color_hex: item.color_hex,
    })),
);

const summary = computed(() => {
    const voted = props.departments.reduce((sum, item) => sum + (item.voted ?? 0), 0);
    const registered = props.departments.reduce((sum, item) => sum + (item.total_registered ?? 0), 0);
    const turnout = registered > 0 ? Math.round((voted / registered) * 1000) / 10 : 0;

    const topDepartment = [...props.departments]
        .filter((item) => (item.total_registered ?? 0) > 0)
        .sort((a, b) => {
            const rateA = (a.voted ?? 0) / a.total_registered;
            const rateB = (b.voted ?? 0) / b.total_registered;
            return rateB - rateA;
        })[0];

    return {
        voted,
        registered,
        turnout,
        topDepartment,
        departmentCount: props.departments.length,
    };
});

// Unovis requires numeric x values — use row index
const x = (_d, i) => i;
const y = [(d) => d.registered, (d) => d.voted];

// i here is the *series* index (0 = registered, 1 = voted)
const barColor = (d, i) => (i === 0 ? `${d.color_hex}55` : d.color_hex);

const tickFormat = (v) => chartData.value[v]?.label ?? '';
const tickValues = computed(() => chartData.value.map((_, i) => i));
const xDomain = computed(() => [-0.5, chartData.value.length - 0.5]);
</script>

<template>
    <Card class="overflow-hidden">
        <CardHeader>
            <CardTitle>Turnout by Department</CardTitle>
            <CardDescription>{{ subtitle }}</CardDescription>
        </CardHeader>

        <CardContent>
            <div
                v-if="chartData.length === 0"
                class="flex min-h-[250px] items-center justify-center text-sm"
                style="color: hsl(240 3.8% 46.1%);"
            >
                No department turnout data yet.
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
                        :num-ticks="3"
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
                <template v-if="summary.topDepartment">
                    {{ summary.voted }} of {{ summary.registered }} registered voters have cast ballots
                    across {{ summary.departmentCount }} departments.
                    Highest turnout: {{ summary.topDepartment.label }}.
                </template>
                <template v-else>
                    Showing voted vs registered across {{ summary.departmentCount }} departments.
                </template>
            </p>
        </CardFooter>
    </Card>
</template>
