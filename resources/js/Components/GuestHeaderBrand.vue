<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        default: '/',
    },
});

defineEmits(['click']);
</script>

<template>
    <Link :href="href" class="flex items-center gap-1.5 sm:gap-3 shrink min-w-0 max-w-[calc(100%-2.75rem)]" @click="$emit('click', $event)">
        <img
            src="/images/bcc.png"
            alt="Baao Community College"
            class="h-8 w-auto max-w-[3.25rem] min-[400px]:h-9 min-[400px]:max-w-[4rem] sm:h-11 sm:max-w-none object-contain shrink-0"
        />
        <img
            src="/images/ssc.png"
            alt="Supreme Student Council"
            class="h-8 w-auto max-w-[3.25rem] min-[400px]:h-9 min-[400px]:max-w-[4rem] sm:h-11 sm:max-w-none object-contain shrink-0"
        />
        <span class="guest-brand-name" aria-label="SSC-EVS">𝓢𝓢𝓒-𝓔𝓥𝓢</span>
    </Link>
</template>
