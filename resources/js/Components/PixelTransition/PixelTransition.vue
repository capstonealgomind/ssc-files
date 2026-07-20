<template>
  <div
    ref="containerRef"
    class="pixelated-image-card"
    :class="className"
    :style="containerStyle"
    role="button"
    tabindex="0"
    @pointerenter="handlePointerEnter"
    @pointerleave="handlePointerLeave"
    @click="handleClick"
    @keydown.enter.prevent="handleClick"
    @keydown.space.prevent="handleClick"
  >
    <div :style="{ paddingTop: aspectRatio }" />
    <div class="pixelated-image-card__default" :aria-hidden="isActive">
      <slot name="first">
        <div v-if="typeof firstContent === 'string'" v-html="firstContent" />
      </slot>
    </div>
    <div
      ref="activeRef"
      class="pixelated-image-card__active"
      :aria-hidden="!isActive"
    >
      <slot name="second">
        <div v-if="typeof secondContent === 'string'" v-html="secondContent" />
      </slot>
    </div>
    <div ref="pixelGridRef" class="pixelated-image-card__pixels" />
  </div>
</template>

<script setup>
import { gsap } from 'gsap';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
  firstContent: {
    type: [String, Object],
    default: undefined,
  },
  secondContent: {
    type: [String, Object],
    default: undefined,
  },
  gridSize: {
    type: Number,
    default: 7,
  },
  pixelColor: {
    type: String,
    default: 'currentColor',
  },
  animationStepDuration: {
    type: Number,
    default: 0.3,
  },
  once: {
    type: Boolean,
    default: false,
  },
  /** When true, only plays the pixel flash (no alternate content swap). */
  animationOnly: {
    type: Boolean,
    default: false,
  },
  aspectRatio: {
    type: String,
    default: '100%',
  },
  className: {
    type: String,
    default: '',
  },
  containerStyle: {
    type: Object,
    default: () => ({}),
  },
});

const containerRef = ref(null);
const pixelGridRef = ref(null);
const activeRef = ref(null);
const delayedCallRef = ref(null);
const isActive = ref(false);
const isAnimating = ref(false);
const isCoarsePointer = ref(false);

function buildPixelGrid() {
  const pixelGridEl = pixelGridRef.value;
  if (!pixelGridEl) {
    return;
  }

  pixelGridEl.innerHTML = '';

  for (let row = 0; row < props.gridSize; row++) {
    for (let col = 0; col < props.gridSize; col++) {
      const pixel = document.createElement('div');
      pixel.classList.add('pixelated-image-card__pixel');
      pixel.style.backgroundColor = props.pixelColor;

      const size = 100 / props.gridSize;
      pixel.style.width = `${size}%`;
      pixel.style.height = `${size}%`;
      pixel.style.left = `${col * size}%`;
      pixel.style.top = `${row * size}%`;
      pixelGridEl.appendChild(pixel);
    }
  }
}

function getPixels() {
  const pixelGridEl = pixelGridRef.value;
  if (!pixelGridEl) {
    return [];
  }

  return pixelGridEl.querySelectorAll('.pixelated-image-card__pixel');
}

function playPixelFlash() {
  const pixels = getPixels();
  if (!pixels.length || isAnimating.value) {
    return;
  }

  isAnimating.value = true;
  gsap.killTweensOf(pixels);
  if (delayedCallRef.value) {
    delayedCallRef.value.kill();
  }

  gsap.set(pixels, { display: 'none' });

  const totalPixels = pixels.length;
  const staggerDuration = props.animationStepDuration / totalPixels;

  gsap.to(pixels, {
    display: 'block',
    duration: 0,
    stagger: {
      each: staggerDuration,
      from: 'random',
    },
  });

  gsap.to(pixels, {
    display: 'none',
    duration: 0,
    delay: props.animationStepDuration,
    stagger: {
      each: staggerDuration,
      from: 'random',
    },
    onComplete: () => {
      isAnimating.value = false;
    },
  });
}

function animatePixels(activate) {
  isActive.value = activate;

  const activeEl = activeRef.value;
  const pixels = getPixels();
  if (!activeEl || !pixels.length) {
    return;
  }

  gsap.killTweensOf(pixels);
  if (delayedCallRef.value) {
    delayedCallRef.value.kill();
  }

  gsap.set(pixels, { display: 'none' });

  const totalPixels = pixels.length;
  const staggerDuration = props.animationStepDuration / totalPixels;

  gsap.to(pixels, {
    display: 'block',
    duration: 0,
    stagger: {
      each: staggerDuration,
      from: 'random',
    },
  });

  delayedCallRef.value = gsap.delayedCall(props.animationStepDuration, () => {
    activeEl.style.display = activate ? 'flex' : 'none';
    activeEl.style.pointerEvents = activate ? 'none' : '';
  });

  gsap.to(pixels, {
    display: 'none',
    duration: 0,
    delay: props.animationStepDuration,
    stagger: {
      each: staggerDuration,
      from: 'random',
    },
  });
}

function handlePointerEnter(event) {
  if (event.pointerType === 'touch') {
    return;
  }

  if (props.animationOnly) {
    playPixelFlash();
    return;
  }

  if (!isActive.value) {
    animatePixels(true);
  }
}

function handlePointerLeave(event) {
  if (event.pointerType === 'touch' || props.animationOnly) {
    return;
  }

  if (isActive.value && !props.once) {
    animatePixels(false);
  }
}

function handleClick() {
  if (props.animationOnly) {
    playPixelFlash();
    return;
  }

  // Touch / coarse pointers use click to toggle
  if (!isCoarsePointer.value) {
    return;
  }

  if (!isActive.value) {
    animatePixels(true);
  } else if (isActive.value && !props.once) {
    animatePixels(false);
  }
}

onMounted(() => {
  buildPixelGrid();
  isCoarsePointer.value =
    window.matchMedia('(pointer: coarse)').matches ||
    navigator.maxTouchPoints > 0;
});

onUnmounted(() => {
  if (delayedCallRef.value) {
    delayedCallRef.value.kill();
  }

  const pixels = getPixels();
  if (pixels.length) {
    gsap.killTweensOf(pixels);
  }
});

watch(
  () => [props.gridSize, props.pixelColor],
  () => {
    buildPixelGrid();
  },
);
</script>

<style src="./PixelTransition.css"></style>
