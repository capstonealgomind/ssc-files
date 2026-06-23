<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div class="w-full space-y-6">
        <div class="rounded-xl border p-6 sm:p-8" style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);">
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div
                    v-if="icon"
                    class="h-14 w-14 rounded-xl flex items-center justify-center mb-4"
                    style="background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);"
                    v-html="icon"
                />
                <h2 class="text-lg font-semibold mb-2" style="color: hsl(240 10% 3.9%);">{{ title }}</h2>
                <p class="text-sm max-w-md" style="color: hsl(240 3.8% 46.1%);">{{ description }}</p>
            </div>
        </div>
    </div>
</template>
