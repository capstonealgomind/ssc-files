<script setup>
import CandidateCard from '@/Components/elections/CandidateCard.vue';
import { reportVoterActivity } from '@/composables/useVoterInactivityLogout';

defineProps({
    group: {
        type: Object,
        required: true,
    },
    selectable: {
        type: Boolean,
        default: false,
    },
    showPlatform: {
        type: Boolean,
        default: false,
    },
    labelStyle: {
        type: Object,
        default: () => ({ color: 'hsl(240 3.8% 46.1%)' }),
    },
    isSelected: {
        type: Function,
        default: () => false,
    },
});

const emit = defineEmits(['select']);

function onCarouselScroll() {
    reportVoterActivity();
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-2 mb-2">
            <p class="text-xs font-semibold uppercase tracking-wide" :style="labelStyle">
                {{ group.position }}
            </p>
            <p
                v-if="group.candidates.length > 1"
                class="text-[10px] font-medium md:hidden"
                style="color: hsl(240 3.8% 46.1%);"
            >
                Swipe to browse
            </p>
        </div>

        <!-- Mobile: horizontal swipe carousel per position -->
        <div
            class="candidate-carousel md:hidden flex gap-3 overflow-x-auto overflow-y-visible py-2 -mx-1 px-1"
            role="list"
            :aria-label="`${group.position} candidates`"
            @scroll.passive="onCarouselScroll"
        >
            <div
                v-for="candidate in group.candidates"
                :key="candidate.id"
                class="candidate-slide shrink-0 w-[min(82vw,280px)] max-w-[280px] py-1"
                role="listitem"
            >
                <CandidateCard
                    :candidate="candidate"
                    :selectable="selectable"
                    :selected="isSelected(candidate.id)"
                    :show-platform="showPlatform"
                    fluid
                    @select="emit('select', candidate.id)"
                />
            </div>
        </div>

        <!-- Desktop: grid layout -->
        <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 py-1">
            <CandidateCard
                v-for="candidate in group.candidates"
                :key="candidate.id"
                :candidate="candidate"
                :selectable="selectable"
                :selected="isSelected(candidate.id)"
                :show-platform="showPlatform"
                fluid
                @select="emit('select', candidate.id)"
            />
        </div>
    </div>
</template>

<style scoped>
.candidate-carousel {
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-x pan-y;
    scrollbar-width: none;
}

.candidate-carousel::-webkit-scrollbar {
    display: none;
}

.candidate-slide {
    scroll-snap-align: center;
}
</style>
