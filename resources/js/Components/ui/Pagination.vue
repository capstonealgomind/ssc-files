<script setup>
import { computed } from 'vue';
import Button from '@/Components/ui/Button.vue';

const props = defineProps({
    currentPage: { type: Number, default: 1 },
    lastPage: { type: Number, default: 1 },
    total: { type: Number, default: 0 },
    from: { type: Number, default: 0 },
    to: { type: Number, default: 0 },
});

const emit = defineEmits(['change']);

const showPager = computed(() => (props.lastPage ?? 1) > 1);

const pageNumbers = computed(() => {
    const current = props.currentPage ?? 1;
    const last = props.lastPage ?? 1;
    const pages = [];

    for (let page = 1; page <= last; page += 1) {
        if (page === 1 || page === last || Math.abs(page - current) <= 1) {
            pages.push(page);
            continue;
        }

        if (pages[pages.length - 1] !== '…') {
            pages.push('…');
        }
    }

    return pages;
});

function goToPage(page) {
    if (!page || page === '…' || page === props.currentPage) {
        return;
    }

    if (page < 1 || page > props.lastPage) {
        return;
    }

    emit('change', page);
}
</script>

<template>
    <div
        v-if="total > 0"
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t"
        style="border-color: hsl(240 5.9% 90%);"
    >
        <p class="text-xs" style="color: hsl(240 3.8% 46.1%)">
            Showing {{ from || 0 }}–{{ to || 0 }} of {{ total }}
        </p>
        <div v-if="showPager" class="flex flex-wrap items-center gap-1.5">
            <Button
                type="button"
                size="sm"
                variant="outline"
                :disabled="currentPage <= 1"
                @click="goToPage(currentPage - 1)"
            >
                Previous
            </Button>
            <template v-for="(page, index) in pageNumbers" :key="`${page}-${index}`">
                <span
                    v-if="page === '…'"
                    class="px-1 text-xs"
                    style="color: hsl(240 3.8% 46.1%)"
                >
                    …
                </span>
                <Button
                    v-else
                    type="button"
                    size="sm"
                    :variant="page === currentPage ? 'navy' : 'outline'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </Button>
            </template>
            <Button
                type="button"
                size="sm"
                variant="outline"
                :disabled="currentPage >= lastPage"
                @click="goToPage(currentPage + 1)"
            >
                Next
            </Button>
        </div>
    </div>
</template>
