<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import lottie from 'lottie-web';

const props = defineProps({
    src: {
        type: String,
        required: true,
    },
    loop: {
        type: Boolean,
        default: true,
    },
    autoplay: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['complete']);

const container = ref(null);

let animation = null;

onMounted(() => {
    if (!container.value) {
        return;
    }

    animation = lottie.loadAnimation({
        container: container.value,
        renderer: 'svg',
        loop: props.loop,
        autoplay: props.autoplay,
        path: props.src,
    });

    animation.addEventListener('complete', () => {
        emit('complete');
    });
});

onUnmounted(() => {
    animation?.destroy();
    animation = null;
});
</script>

<template>
    <div ref="container" class="lottie-animation" />
</template>

<style scoped>
.lottie-animation {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.lottie-animation :deep(svg) {
    width: 100% !important;
    height: 100% !important;
    max-width: 100%;
}
</style>
