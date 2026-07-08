<script setup>
import Dialog from '@/Components/ui/Dialog.vue';
import Button from '@/Components/ui/Button.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    secondsLeft: {
        type: Number,
        default: 10,
    },
});

defineEmits(['confirm']);
</script>

<template>
    <Dialog
        :show="show"
        title="Are you still there?"
        description="You've been inactive for a while."
        persistent
    >
        <div class="space-y-3">
            <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                For your security, you'll be signed out automatically unless you confirm you're still using SSCEVS.
            </p>
            <p
                class="text-sm font-semibold"
                style="color: hsl(240 10% 3.9%);"
            >
                Signing out in {{ secondsLeft }} second{{ secondsLeft === 1 ? '' : 's' }}...
            </p>
        </div>

        <template #footer>
            <div class="flex justify-end">
                <Button type="button" @click.stop="$emit('confirm')">
                    Yes, I'm here
                </Button>
            </div>
        </template>
    </Dialog>
</template>
