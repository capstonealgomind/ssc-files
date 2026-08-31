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
    <Link :href="href" class="flex items-center gap-2 sm:gap-3 shrink min-w-0 max-w-[calc(100%-3.5rem)]" @click="$emit('click', $event)">
        <img
            src="/images/bcc.png"
            alt="Baao Community College"
            class="h-10 w-auto max-w-[4.25rem] min-[400px]:h-11 min-[400px]:max-w-[4.75rem] sm:h-12 sm:max-w-none object-contain shrink-0"
        />
        <img
            src="/images/ssc.png"
            alt="Supreme Student Council"
            class="h-10 w-auto max-w-[4.25rem] min-[400px]:h-11 min-[400px]:max-w-[4.75rem] sm:h-12 sm:max-w-none object-contain shrink-0"
        />
        <span class="guest-brand-name" aria-label="SSC-EVS">SSC-EVS</span>
    </Link>
</template>
