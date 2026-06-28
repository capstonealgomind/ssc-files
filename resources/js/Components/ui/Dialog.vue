<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    wide: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 cursor-pointer"
                style="background-color: rgba(0, 0, 0, 0.5);"
                @click="$emit('close')"
            />

            <div
                class="relative z-10 flex w-full max-h-[90vh] flex-col overflow-hidden rounded-xl border shadow-lg"
                :class="wide ? 'max-w-4xl' : 'max-w-md'"
                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
            >
                <div class="flex shrink-0 items-start justify-between border-b px-6 py-4" style="border-color: hsl(240 5.9% 90%);">
                    <div class="min-w-0 pr-4">
                        <h2 class="text-lg font-semibold" style="color: hsl(240 10% 3.9%);">{{ title }}</h2>
                        <p v-if="description" class="text-sm mt-1" style="color: hsl(240 3.8% 46.1%);">{{ description }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-md p-1 transition-colors hover:bg-gray-100 cursor-pointer"
                        style="color: hsl(240 3.8% 46.1%);"
                        @click="$emit('close')"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                    <slot />
                </div>

                <div
                    v-if="$slots.footer"
                    class="shrink-0 border-t px-6 py-4"
                    style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 98%);"
                >
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
