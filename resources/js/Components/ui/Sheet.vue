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
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50"
            >
                <div
                    class="fixed inset-0 cursor-pointer"
                    style="background-color: rgba(0, 0, 0, 0.5);"
                    @click="$emit('close')"
                />

                <Transition
                    enter-active-class="transition-transform duration-300 ease-out"
                    enter-from-class="translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition-transform duration-200 ease-in"
                    leave-from-class="translate-x-0"
                    leave-to-class="translate-x-full"
                >
                    <aside
                        v-if="show"
                        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col border-l shadow-2xl"
                        style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
                    >
                        <div
                            class="flex items-start justify-between border-b px-6 py-5 shrink-0"
                            style="border-color: hsl(240 5.9% 90%);"
                        >
                            <div>
                                <h2 class="text-lg font-semibold" style="color: hsl(240 10% 3.9%);">{{ title }}</h2>
                                <p
                                    v-if="description"
                                    class="text-sm mt-1"
                                    style="color: hsl(240 3.8% 46.1%);"
                                >
                                    {{ description }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="p-1.5 rounded-md transition-colors hover:bg-gray-100 shrink-0 cursor-pointer"
                                style="color: hsl(240 3.8% 46.1%);"
                                @click="$emit('close')"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto px-6 py-5">
                            <slot />
                        </div>

                        <div
                            v-if="$slots.footer"
                            class="border-t px-6 py-4 shrink-0"
                            style="border-color: hsl(240 5.9% 90%); background-color: hsl(240 4.8% 95.9%);"
                        >
                            <slot name="footer" />
                        </div>
                    </aside>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
