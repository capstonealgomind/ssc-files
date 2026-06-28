<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
});

const colors = [
    { fill: 'hsl(142 71% 45%)', bg: 'hsl(142 76% 94%)', text: 'hsl(142 71% 29%)' },
    { fill: 'hsl(221 83% 53%)', bg: 'hsl(221 83% 94%)', text: 'hsl(221 83% 35%)' },
    { fill: 'hsl(240 5.9% 65%)', bg: 'hsl(240 4.8% 95.9%)', text: 'hsl(240 3.8% 46.1%)' },
    { fill: 'hsl(38 92% 50%)', bg: 'hsl(38 92% 94%)', text: 'hsl(38 62% 30%)' },
];

const total = computed(() =>
    props.data.reduce((sum, item) => sum + item.value, 0)
);

const segments = computed(() => {
    if (total.value === 0) {
        return props.data.map((item, i) => ({
            ...item,
            ...colors[i % colors.length],
            percent: 0,
            path: '',
        }));
    }

    let cumulative = 0;
    const cx = 80;
    const cy = 80;
    const r = 64;

    return props.data.map((item, i) => {
        const startAngle = (cumulative / total.value) * 2 * Math.PI - Math.PI / 2;
        cumulative += item.value;
        const endAngle = (cumulative / total.value) * 2 * Math.PI - Math.PI / 2;

        const x1 = cx + r * Math.cos(startAngle);
        const y1 = cy + r * Math.sin(startAngle);
        const x2 = cx + r * Math.cos(endAngle);
        const y2 = cy + r * Math.sin(endAngle);
        const largeArc = endAngle - startAngle > Math.PI ? 1 : 0;

        const path = item.value === 0
            ? ''
            : item.value === total.value
                ? `M ${cx} ${cy - r} A ${r} ${r} 0 1 1 ${cx - 0.01} ${cy - r} Z`
                : `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${largeArc} 1 ${x2} ${y2} Z`;

        return {
            ...item,
            ...colors[i % colors.length],
            percent: Math.round((item.value / total.value) * 100),
            path,
        };
    });
});
</script>

<template>
    <div class="flex flex-col sm:flex-row items-center gap-6">
        <div class="relative shrink-0">
            <svg width="160" height="160" viewBox="0 0 160 160">
                <circle cx="80" cy="80" r="64" fill="hsl(240 4.8% 95.9%)" />
                <path
                    v-for="(seg, i) in segments"
                    :key="i"
                    :d="seg.path"
                    :fill="seg.fill"
                    class="transition-all duration-500"
                />
                <circle cx="80" cy="80" r="38" fill="white" />
                <text x="80" y="76" text-anchor="middle" class="text-xl font-black" fill="hsl(240 10% 3.9%)">{{ total }}</text>
                <text x="80" y="92" text-anchor="middle" class="text-[10px] font-semibold" fill="hsl(240 3.8% 46.1%)">Total</text>
            </svg>
        </div>

        <div class="flex-1 w-full space-y-3">
            <div v-for="(seg, i) in segments" :key="i" class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="h-3 w-3 rounded-full shrink-0" :style="{ background: seg.fill }"></span>
                    <span class="text-sm font-semibold truncate" style="color:hsl(240 10% 3.9%);">{{ seg.label }}</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-sm font-bold" style="color:hsl(240 10% 3.9%);">{{ seg.value }}</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                        :style="{ background: seg.bg, color: seg.text }">{{ seg.percent }}%</span>
                </div>
            </div>
        </div>
    </div>
</template>
