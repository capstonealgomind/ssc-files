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
                class="fixed inset-0"
                style="background-color: rgba(0, 0, 0, 0.5);"
                @click="$emit('close')"
            />

            <div
                class="relative z-10 w-full max-w-md rounded-xl border shadow-lg"
                style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
            >
                <div class="flex items-start justify-between border-b px-6 py-4" style="border-color: hsl(240 5.9% 90%);">
                    <div>
                        <h2 class="text-lg font-semibold" style="color: hsl(240 10% 3.9%);">{{ title }}</h2>
                        <p v-if="description" class="text-sm mt-1" style="color: hsl(240 3.8% 46.1%);">{{ description }}</p>
                    </div>
                    <button
                        type="button"
                        class="p-1 rounded-md transition-colors hover:bg-gray-100"
                        style="color: hsl(240 3.8% 46.1%);"
                        @click="$emit('close')"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-4">
                    <slot />
                </div>
            </div>
        </div>
    </Teleport>
</template>
