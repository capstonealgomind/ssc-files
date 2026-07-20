<template>
  <div
    ref="rootRef"
    class="relative w-full h-full"
    :style="
      {
        '--segments-x': segments,
        '--segments-y': segments,
        '--circ': 'calc(var(--radius) * 3.14)',
        '--rot-y': 'calc((360deg / var(--segments-x)) / 2)',
        '--rot-x': 'calc((360deg / var(--segments-y)) / 2)',
        '--item-width': 'calc(var(--circ) / var(--segments-x))',
        '--item-height': 'calc(var(--circ) / var(--segments-y))'
      }
    "
  >
    <main
      ref="mainRef"
      class="absolute inset-0 place-items-center grid bg-transparent overflow-hidden touch-none select-none"
    >
      <div
        class="place-items-center grid w-full h-full"
        :style="
          {
            perspective: 'calc(var(--radius) * 2)',
            perspectiveOrigin: '50% 50%',
            contain: 'layout paint size'
          }
        "
      >
        <div
          ref="sphereRef"
          :style="
            {
              willChange: 'transform',
              transformStyle: 'preserve-3d',
              transform: 'translateZ(calc(var(--radius) * -1))'
            }
          "
        >
          <div
            v-for="(item, i) in items"
            :key="`${item.x},${item.y},${i}`"
            :data-src="item.src"
            :data-offset-x="item.x"
            :data-offset-y="item.y"
            :data-size-x="item.sizeX"
            :data-size-y="item.sizeY"
            :style="
              {
                '--offset-x': item.x,
                '--offset-y': item.y,
                '--item-size-x': item.sizeX,
                '--item-size-y': item.sizeY,
                width: 'calc(var(--item-width) * var(--item-size-x))',
                height: 'calc(var(--item-height) * var(--item-size-y))',
                position: 'absolute',
                top: '-999px',
                bottom: '-999px',
                left: '-999px',
                right: '-999px',
                margin: 'auto',
                transformStyle: 'preserve-3d',
                transformOrigin: '50% 50%',
                backfaceVisibility: 'hidden',
                transition: 'transform 300ms',
                transform: `rotateY(calc(var(--rot-y) * (var(--offset-x) + ((var(--item-size-x) - 1) / 2)) + var(--rot-y-delta, 0deg))) rotateX(calc(var(--rot-x) * (var(--offset-y) - ((var(--item-size-y) - 1) / 2)) + var(--rot-x-delta, 0deg))) translateZ(var(--radius))`
              }
            "
          >
            <div
              :style="
                {
                  position: 'absolute',
                  display: 'block',
                  inset: 'var(--tile-inset-block, 10px) var(--tile-inset-inline, 10px)',
                  borderRadius: 'var(--tile-radius, 12px)',
                  background: 'transparent',
                  overflow: 'hidden',
                  transformStyle: 'preserve-3d',
                  backfaceVisibility: 'hidden',
                  transition: 'transform 300ms',
                  cursor: 'pointer',
                  touchAction: 'manipulation',
                  WebkitTapHighlightColor: 'transparent',
                  transform: 'translateZ(0)',
                  pointerEvents: 'auto'
                }
              "
              role="button"
              tabindex="0"
              :aria-label="item.alt || 'Open image'"
              @click="onTileClick"
              @pointerup="onTilePointerUp"
            >
              <img
                :src="item.src"
                :draggable="false"
                :alt="item.alt"
                :style="
                  {
                    width: '100%',
                    height: '100%',
                    objectFit: 'cover',
                    pointerEvents: 'none',
                    backfaceVisibility: 'hidden',
                    filter: 'var(--image-filter, none)'
                  }
                "
              />
            </div>
          </div>
        </div>
      </div>

      <div
        class="z-[3] absolute inset-0 m-auto pointer-events-none"
        :style="
          {
            backgroundImage: 'radial-gradient(rgba(235,235,235,0) 65%, var(--overlay-blur-color, #120F17) 100%)'
          }
        "
      />
      <div
        class="z-[3] absolute inset-0 m-auto pointer-events-none"
        :style="
          {
            WebkitMaskImage: 'radial-gradient(rgba(235,235,235,0) 70%, var(--overlay-blur-color, #120F17) 90%)',
            maskImage: 'radial-gradient(rgba(235,235,235,0) 70%, var(--overlay-blur-color, #120F17) 90%)',
            backdropFilter: 'blur(3px)'
          }
        "
      />

      <div
        ref="viewerRef"
        class="z-20 absolute inset-0 flex justify-center items-center pointer-events-none"
        :style="{ padding: 'var(--viewer-pad)' }"
      >
        <div
          ref="scrimRef"
          class="z-10 absolute inset-0 bg-black/40 transition-opacity duration-500 ease-linear"
          :style="
            {
              backdropFilter: 'blur(3px)',
              opacity: isEnlarging ? 1 : 0,
              pointerEvents: isEnlarging ? 'all' : 'none'
            }
          "
        />
        <div
          ref="frameRef"
          class="flex h-full aspect-square"
          :style="{ borderRadius: 'var(--enlarge-radius, 32px)' }"
        />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';

const props = defineProps({
  fit: { type: Number, default: 0.5 },
  fitBasis: { type: String, default: 'auto' },
  minRadius: { type: Number, default: 600 },
  maxRadius: { type: Number, default: Infinity },
  padFactor: { type: Number, default: 0.25 },
  overlayBlurColor: { type: String, default: '#120F17' },
  maxVerticalRotationDeg: { type: Number, default: 5 },
  dragSensitivity: { type: Number, default: 20 },
  enlargeTransitionMs: { type: Number, default: 300 },
  segments: { type: Number, default: 35 },
  dragDampening: { type: Number, default: 2 },
  openedImageWidth: { type: String, default: '250px' },
  openedImageHeight: { type: String, default: '350px' },
  imageBorderRadius: { type: String, default: '30px' },
  openedImageBorderRadius: { type: String, default: '30px' },
  grayscale: { type: Boolean, default: true },
  images: { type: Array, default: () => [] },
  autoplay: { type: Boolean, default: true },
  autoplaySpeed: { type: Number, default: 0.18 },
  autoplayResumeDelay: { type: Number, default: 1800 },
});

const rootRef = useTemplateRef('rootRef');
const mainRef = useTemplateRef('mainRef');
const sphereRef = useTemplateRef('sphereRef');
const viewerRef = useTemplateRef('viewerRef');
const scrimRef = useTemplateRef('scrimRef');
const frameRef = useTemplateRef('frameRef');

const isEnlarging = ref(false);

const rotationRef = { x: 0, y: 0 };
const startRotRef = { x: 0, y: 0 };
let startPosRef = null;
let draggingRef = false;
let movedRef = false;
let inertiaRAF = null;
let openingRef = false;
let openStartedAtRef = 0;
let lastDragEndAt = 0;
let focusedElRef = null;
let originalTilePositionRef = null;
let scrollLockedRef = false;
let resizeObserver = null;
let keydownHandler = null;
let autoplayRAF = null;
let autoplayResumeAt = 0;

const clamp = (v, min, max) => Math.min(Math.max(v, min), max);
const normalizeAngle = (d) => ((d % 360) + 360) % 360;
const wrapAngleSigned = (deg) => {
  const a = (((deg + 180) % 360) + 360) % 360;
  return a - 180;
};
const getDataNumber = (el, name, fallback) => {
  const attr = el.dataset[name] ?? el.getAttribute(`data-${name}`);
  const n = attr == null ? NaN : parseFloat(attr);
  return Number.isFinite(n) ? n : fallback;
};

function buildItems(pool, seg) {
  const xCols = Array.from({ length: seg }, (_, i) => -37 + i * 2);
  const evenYs = [-4, -2, 0, 2, 4];
  const oddYs = [-3, -1, 1, 3, 5];
  const coords = xCols.flatMap((x, c) => {
    const ys = c % 2 === 0 ? evenYs : oddYs;
    return ys.map(y => ({ x, y, sizeX: 2, sizeY: 2 }));
  });
  const totalSlots = coords.length;
  if (pool.length === 0) return coords.map(c => ({ ...c, src: '', alt: '' }));
  if (pool.length > totalSlots) {
    console.warn(`[DomeGallery] ${pool.length} images > ${totalSlots} tiles. Some won't show.`);
  }
  const normalizedImages = pool.map(img =>
    typeof img === 'string' ? { src: img, alt: '' } : { src: img.src || '', alt: img.alt || '' }
  );
  const usedImages = Array.from(
    { length: totalSlots },
    (_, i) => normalizedImages[i % normalizedImages.length]
  );
  for (let i = 1; i < usedImages.length; i++) {
    if (usedImages[i].src === usedImages[i - 1].src) {
      for (let j = i + 1; j < usedImages.length; j++) {
        if (usedImages[j].src !== usedImages[i].src) {
          [usedImages[i], usedImages[j]] = [usedImages[j], usedImages[i]];
          break;
        }
      }
    }
  }
  return coords.map((c, i) => ({ ...c, src: usedImages[i].src, alt: usedImages[i].alt ?? '' }));
}

const items = computed(() => buildItems(props.images ?? [], props.segments));

function computeItemBaseRotation(offsetX, offsetY, sizeX, sizeY, segments) {
  const unit = 360 / segments / 2;
  return {
    rotateY: unit * (offsetX + (sizeX - 1) / 2),
    rotateX: unit * (offsetY - (sizeY - 1) / 2)
  };
}

const applyTransform = (xDeg, yDeg) => {
  const el = sphereRef.value;
  if (el) el.style.transform = `translateZ(calc(var(--radius) * -1)) rotateX(${xDeg}deg) rotateY(${yDeg}deg)`;
};

const GALLERY_RADIUS_FALLBACK = '30px';

const applyRootVars = () => {
  const root = rootRef.value;
  if (!root) return;
  const tileRadius = props.imageBorderRadius || GALLERY_RADIUS_FALLBACK;
  const enlargeRadius = props.openedImageBorderRadius || tileRadius;
  root.style.setProperty('--overlay-blur-color', props.overlayBlurColor);
  root.style.setProperty('--tile-radius', tileRadius);
  root.style.setProperty('--enlarge-radius', enlargeRadius);
  root.style.setProperty('--tile-inset-block', '10px');
  root.style.setProperty('--tile-inset-inline', '10px');
  root.style.setProperty('--image-filter', props.grayscale ? 'grayscale(1)' : 'none');
};

const computeRadius = () => {
  const root = rootRef.value;
  if (!root) return;
  // Prefer layout size (ignores CSS scale) so mobile "desktop frame" stays accurate
  const w = Math.max(1, root.clientWidth || root.getBoundingClientRect().width);
  const h = Math.max(1, root.clientHeight || root.getBoundingClientRect().height);
  const minDim = Math.min(w, h);
  const maxDim = Math.max(w, h);
  const aspect = w / h;
  let basis;
  switch (props.fitBasis) {
    case 'min':
      basis = minDim;
      break;
    case 'max':
      basis = maxDim;
      break;
    case 'width':
      basis = w;
      break;
    case 'height':
      basis = h;
      break;
    default:
      basis = aspect >= 1.3 ? w : minDim;
  }
  let radius = basis * props.fit;
  radius = Math.min(radius, h * 1.35);
  radius = clamp(radius, props.minRadius, props.maxRadius);
  const viewerPad = Math.max(8, Math.round(minDim * props.padFactor));
  root.style.setProperty('--radius', `${Math.round(radius)}px`);
  root.style.setProperty('--viewer-pad', `${viewerPad}px`);
  applyRootVars();
  applyTransform(rotationRef.x, rotationRef.y);

  const enlargedOverlay = viewerRef.value?.querySelector('.dg-enlarge');
  if (enlargedOverlay && frameRef.value && mainRef.value) {
    const frameR = frameRef.value.getBoundingClientRect();
    const mainR = mainRef.value.getBoundingClientRect();
    if (props.openedImageWidth && props.openedImageHeight) {
      const tmp = document.createElement('div');
      tmp.style.cssText = `position:absolute;visibility:hidden;pointer-events:none;width:${props.openedImageWidth};height:${props.openedImageHeight};`;
      document.body.appendChild(tmp);
      const tmpR = tmp.getBoundingClientRect();
      document.body.removeChild(tmp);
      enlargedOverlay.style.left = `${frameR.left - mainR.left + (frameR.width - tmpR.width) / 2}px`;
      enlargedOverlay.style.top = `${frameR.top - mainR.top + (frameR.height - tmpR.height) / 2}px`;
    } else {
      enlargedOverlay.style.left = `${frameR.left - mainR.left}px`;
      enlargedOverlay.style.top = `${frameR.top - mainR.top}px`;
      enlargedOverlay.style.width = `${frameR.width}px`;
      enlargedOverlay.style.height = `${frameR.height}px`;
    }
  }
};

const lockScroll = () => {
  if (scrollLockedRef) return;
  scrollLockedRef = true;
  document.body.classList.add('dg-scroll-lock');
};

const unlockScroll = () => {
  if (!scrollLockedRef) return;
  if (rootRef.value?.getAttribute('data-enlarging') === 'true') return;
  scrollLockedRef = false;
  document.body.classList.remove('dg-scroll-lock');
};

const stopInertia = () => {
  if (inertiaRAF !== null) {
    cancelAnimationFrame(inertiaRAF);
    inertiaRAF = null;
  }
};

const stopAutoplay = () => {
  if (autoplayRAF !== null) {
    cancelAnimationFrame(autoplayRAF);
    autoplayRAF = null;
  }
};

const pauseAutoplay = () => {
  autoplayResumeAt = Number.POSITIVE_INFINITY;
};

const scheduleAutoplayResume = () => {
  autoplayResumeAt = performance.now() + (props.autoplayResumeDelay ?? 1800);
};

const startAutoplay = () => {
  if (!props.autoplay) {
    return;
  }

  stopAutoplay();

  const tick = () => {
    const canSpin =
      props.autoplay &&
      !draggingRef &&
      !focusedElRef &&
      !openingRef &&
      inertiaRAF === null &&
      performance.now() >= autoplayResumeAt;

    if (canSpin) {
      // Continuous flow right → left
      rotationRef.y = wrapAngleSigned(rotationRef.y - props.autoplaySpeed);
      applyTransform(rotationRef.x, rotationRef.y);
    }

    autoplayRAF = requestAnimationFrame(tick);
  };

  autoplayRAF = requestAnimationFrame(tick);
};

const startInertia = (vx, vy) => {
  const MAX_V = 1.4;
  let vX = clamp(vx, -MAX_V, MAX_V) * 80;
  let vY = clamp(vy, -MAX_V, MAX_V) * 80;
  let frames = 0;
  const d = clamp(props.dragDampening ?? 0.6, 0, 1);
  const frictionMul = 0.94 + 0.055 * d;
  const stopThreshold = 0.015 - 0.01 * d;
  const maxFrames = Math.round(90 + 270 * d);
  const step = () => {
    vX *= frictionMul;
    vY *= frictionMul;
    if (Math.abs(vX) < stopThreshold && Math.abs(vY) < stopThreshold) {
      inertiaRAF = null;
      return;
    }
    if (++frames > maxFrames) {
      inertiaRAF = null;
      return;
    }
    const nextX = clamp(rotationRef.x - vY / 200, -props.maxVerticalRotationDeg, props.maxVerticalRotationDeg);
    const nextY = wrapAngleSigned(rotationRef.y + vX / 200);
    rotationRef.x = nextX;
    rotationRef.y = nextY;
    applyTransform(nextX, nextY);
    inertiaRAF = requestAnimationFrame(step);
  };
  stopInertia();
  inertiaRAF = requestAnimationFrame(step);
};

const onDragStart = (e) => {
  if (focusedElRef) return;
  stopInertia();
  pauseAutoplay();
  draggingRef = true;
  movedRef = false;
  startRotRef.x = rotationRef.x;
  startRotRef.y = rotationRef.y;
  const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
  const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
  startPosRef = { x: clientX, y: clientY };
};

const onDragMove = (e) => {
  if (focusedElRef || !draggingRef || !startPosRef) return;
  const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
  const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
  const dxTotal = clientX - startPosRef.x;
  const dyTotal = clientY - startPosRef.y;
  if (!movedRef && dxTotal * dxTotal + dyTotal * dyTotal > 16) movedRef = true;
  const nextX = clamp(
    startRotRef.x - dyTotal / props.dragSensitivity,
    -props.maxVerticalRotationDeg,
    props.maxVerticalRotationDeg
  );
  const nextY = wrapAngleSigned(startRotRef.y + dxTotal / props.dragSensitivity);
  if (rotationRef.x !== nextX || rotationRef.y !== nextY) {
    rotationRef.x = nextX;
    rotationRef.y = nextY;
    applyTransform(nextX, nextY);
  }
};

const onDragEnd = (e) => {
  if (!draggingRef) return;
  draggingRef = false;
  if (movedRef && startPosRef) {
    const clientX = 'changedTouches' in e ? (e.changedTouches[0]?.clientX ?? 0) : e.clientX;
    const clientY = 'changedTouches' in e ? (e.changedTouches[0]?.clientY ?? 0) : e.clientY;
    const vx = clamp(((clientX - startPosRef.x) / props.dragSensitivity) * 0.02, -1.2, 1.2);
    const vy = clamp(((clientY - startPosRef.y) / props.dragSensitivity) * 0.02, -1.2, 1.2);
    if (Math.abs(vx) > 0.005 || Math.abs(vy) > 0.005) startInertia(vx, vy);
    lastDragEndAt = performance.now();
  }
  movedRef = false;
  scheduleAutoplayResume();
};

const openItemFromElement = (el) => {
  if (openingRef) return;
  openingRef = true;
  openStartedAtRef = performance.now();
  pauseAutoplay();
  lockScroll();

  const parent = el.parentElement;
  if (!parent) {
    openingRef = false;
    unlockScroll();
    scheduleAutoplayResume();
    return;
  }

  focusedElRef = el;
  el.setAttribute('data-focused', 'true');

  const offsetX = getDataNumber(parent, 'offsetX', 0);
  const offsetY = getDataNumber(parent, 'offsetY', 0);
  const sizeX = getDataNumber(parent, 'sizeX', 2);
  const sizeY = getDataNumber(parent, 'sizeY', 2);

  const parentRot = computeItemBaseRotation(offsetX, offsetY, sizeX, sizeY, props.segments);
  const parentY = normalizeAngle(parentRot.rotateY);
  const globalY = normalizeAngle(rotationRef.y);
  let rotY = -(parentY + globalY) % 360;
  if (rotY < -180) rotY += 360;
  const rotX = -parentRot.rotateX - rotationRef.x;

  parent.style.setProperty('--rot-y-delta', `${rotY}deg`);
  parent.style.setProperty('--rot-x-delta', `${rotX}deg`);

  // Reference div: same geometry as the clickable tile so we can measure its
  // screen position. Must match the tile's inline styles exactly.
  const refDiv = document.createElement('div');
  refDiv.style.cssText = `
    position: absolute;
    display: block;
    inset: var(--tile-inset-block, 10px) var(--tile-inset-inline, 10px);
    opacity: 0;
    border-radius: var(--tile-radius, 12px);
    background: transparent;
    overflow: hidden;
    backface-visibility: hidden;
    transform: rotateX(${-parentRot.rotateX}deg) rotateY(${-parentRot.rotateY}deg) translateZ(0);
    pointer-events: none;
  `;
  parent.appendChild(refDiv);
  void refDiv.offsetHeight;

  const tileR = refDiv.getBoundingClientRect();
  const mainR = mainRef.value?.getBoundingClientRect();
  const frameR = frameRef.value?.getBoundingClientRect();

  if (!mainR || !frameR || tileR.width <= 0 || tileR.height <= 0) {
    openingRef = false;
    focusedElRef = null;
    parent.removeChild(refDiv);
    unlockScroll();
    scheduleAutoplayResume();
    return;
  }

  originalTilePositionRef = { left: tileR.left, top: tileR.top, width: tileR.width, height: tileR.height };
  el.style.visibility = 'hidden';
  el.style.zIndex = '0';

  const overlay = document.createElement('div');
  overlay.className = 'dg-enlarge';
  overlay.style.cssText = `
    position: absolute;
    left: ${frameR.left - mainR.left}px;
    top: ${frameR.top - mainR.top}px;
    width: ${frameR.width}px;
    height: ${frameR.height}px;
    opacity: 0;
    z-index: 30;
    will-change: transform, opacity;
    transform-origin: top left;
    transition: transform ${props.enlargeTransitionMs}ms ease, opacity ${props.enlargeTransitionMs}ms ease;
    border-radius: var(--enlarge-radius, 32px);
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
  `;

  const rawSrc = parent.dataset['src'] ?? el.querySelector('img')?.src ?? '';
  const img = document.createElement('img');
  img.src = rawSrc;
  img.style.cssText = 'width:100%;height:100%;object-fit:cover;filter:var(--image-filter,none);';
  overlay.appendChild(img);
  viewerRef.value?.appendChild(overlay);

  const tx0 = tileR.left - frameR.left;
  const ty0 = tileR.top - frameR.top;
  const sx0 = tileR.width / frameR.width;
  const sy0 = tileR.height / frameR.height;
  const validSx = isFinite(sx0) && sx0 > 0 ? sx0 : 1;
  const validSy = isFinite(sy0) && sy0 > 0 ? sy0 : 1;
  overlay.style.transform = `translate(${tx0}px, ${ty0}px) scale(${validSx}, ${validSy})`;

  setTimeout(() => {
    if (!overlay.parentElement) return;
    overlay.style.opacity = '1';
    overlay.style.transform = 'translate(0px, 0px) scale(1, 1)';
    rootRef.value?.setAttribute('data-enlarging', 'true');
    isEnlarging.value = true;
  }, 16);

  const wantsResize = props.openedImageWidth || props.openedImageHeight;
  if (wantsResize) {
    const onFirstEnd = (ev) => {
      if (ev.propertyName !== 'transform') return;
      overlay.removeEventListener('transitionend', onFirstEnd);
      const prevTransition = overlay.style.transition;
      overlay.style.transition = 'none';
      const tempW = props.openedImageWidth || `${frameR.width}px`;
      const tempH = props.openedImageHeight || `${frameR.height}px`;
      overlay.style.width = tempW;
      overlay.style.height = tempH;
      const newRect = overlay.getBoundingClientRect();
      overlay.style.width = `${frameR.width}px`;
      overlay.style.height = `${frameR.height}px`;
      void overlay.offsetWidth;
      overlay.style.transition = `left ${props.enlargeTransitionMs}ms ease, top ${props.enlargeTransitionMs}ms ease, width ${props.enlargeTransitionMs}ms ease, height ${props.enlargeTransitionMs}ms ease`;
      const centeredLeft = frameR.left - mainR.left + (frameR.width - newRect.width) / 2;
      const centeredTop = frameR.top - mainR.top + (frameR.height - newRect.height) / 2;
      requestAnimationFrame(() => {
        overlay.style.left = `${centeredLeft}px`;
        overlay.style.top = `${centeredTop}px`;
        overlay.style.width = tempW;
        overlay.style.height = tempH;
      });
      const cleanupSecond = () => {
        overlay.removeEventListener('transitionend', cleanupSecond);
        overlay.style.transition = prevTransition;
      };
      overlay.addEventListener('transitionend', cleanupSecond, { once: true });
    };
    overlay.addEventListener('transitionend', onFirstEnd);
  }
};

const closeEnlargedImage = () => {
  if (performance.now() - openStartedAtRef < 250) return;
  const el = focusedElRef;
  if (!el) return;
  const parent = el.parentElement;
  const overlay = viewerRef.value?.querySelector('.dg-enlarge');
  if (!overlay || !parent) return;

  const refDiv = Array.from(parent.children).find(
    (c) => c.style?.opacity === '0' && c !== el,
  );

  const originalPos = originalTilePositionRef;

  if (!originalPos) {
    overlay.remove();
    if (refDiv) refDiv.remove();
    parent.style.setProperty('--rot-y-delta', '0deg');
    parent.style.setProperty('--rot-x-delta', '0deg');
    el.style.visibility = '';
    el.style.zIndex = '0';
    focusedElRef = null;
    rootRef.value?.removeAttribute('data-enlarging');
    isEnlarging.value = false;
    openingRef = false;
    unlockScroll();
    scheduleAutoplayResume();
    return;
  }

  const currentRect = overlay.getBoundingClientRect();
  const rootRect = rootRef.value?.getBoundingClientRect();
  if (!rootRect) return;

  const origRel = {
    left: originalPos.left - rootRect.left,
    top: originalPos.top - rootRect.top,
    width: originalPos.width,
    height: originalPos.height
  };
  const overlayRel = {
    left: currentRect.left - rootRect.left,
    top: currentRect.top - rootRect.top,
    width: currentRect.width,
    height: currentRect.height
  };

  const animOverlay = document.createElement('div');
  animOverlay.style.cssText = `
    position: absolute;
    left: ${overlayRel.left}px;
    top: ${overlayRel.top}px;
    width: ${overlayRel.width}px;
    height: ${overlayRel.height}px;
    z-index: 9999;
    border-radius: var(--enlarge-radius, 32px);
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,.35);
    transition: all ${props.enlargeTransitionMs}ms ease-out;
    pointer-events: none;
    margin: 0;
    transform: none;
  `;
  const origImg = overlay.querySelector('img');
  if (origImg) {
    const imgClone = origImg.cloneNode();
    imgClone.style.cssText = 'width:100%;height:100%;object-fit:cover;filter:var(--image-filter,none);';
    animOverlay.appendChild(imgClone);
  }

  overlay.remove();
  rootRef.value?.removeAttribute('data-enlarging');
  isEnlarging.value = false;
  rootRef.value?.appendChild(animOverlay);
  void animOverlay.getBoundingClientRect();

  requestAnimationFrame(() => {
    animOverlay.style.left = `${origRel.left}px`;
    animOverlay.style.top = `${origRel.top}px`;
    animOverlay.style.width = `${origRel.width}px`;
    animOverlay.style.height = `${origRel.height}px`;
    animOverlay.style.opacity = '0';
  });

  const cleanup = () => {
    animOverlay.remove();
    originalTilePositionRef = null;
    if (refDiv) refDiv.remove();
    parent.style.transition = 'none';
    el.style.transition = 'none';
    parent.style.setProperty('--rot-y-delta', '0deg');
    parent.style.setProperty('--rot-x-delta', '0deg');
    requestAnimationFrame(() => {
      el.style.visibility = '';
      el.style.opacity = '0';
      el.style.zIndex = '0';
      focusedElRef = null;
      requestAnimationFrame(() => {
        parent.style.transition = 'transform 300ms';
        el.style.transition = 'opacity 300ms ease-out';
        requestAnimationFrame(() => {
          el.style.opacity = '1';
          setTimeout(() => {
            el.style.transition = '';
            el.style.opacity = '';
            openingRef = false;
            scheduleAutoplayResume();
            if (!draggingRef && rootRef.value?.getAttribute('data-enlarging') !== 'true') {
              document.body.classList.remove('dg-scroll-lock');
            }
          }, 300);
        });
      });
    });
  };

  animOverlay.addEventListener('transitionend', cleanup, { once: true });
};

const onTileClick = (e) => {
  if (draggingRef) return;
  if (movedRef) return;
  if (performance.now() - lastDragEndAt < 80) return;
  if (openingRef) return;
  openItemFromElement(e.currentTarget);
};

const onTilePointerUp = (e) => {
  if (e.pointerType !== 'touch') return;
  if (draggingRef) return;
  if (movedRef) return;
  if (performance.now() - lastDragEndAt < 80) return;
  if (openingRef) return;
  openItemFromElement(e.currentTarget);
};

onMounted(() => {
  const root = rootRef.value;
  const main = mainRef.value;
  if (!root || !main) return;

  applyTransform(rotationRef.x, rotationRef.y);

  resizeObserver = new ResizeObserver(computeRadius);
  resizeObserver.observe(root);

  main.addEventListener('mousedown', onDragStart, { passive: true });
  main.addEventListener('touchstart', onDragStart, { passive: true });
  window.addEventListener('mousemove', onDragMove, { passive: true });
  window.addEventListener('touchmove', onDragMove, { passive: true });
  window.addEventListener('mouseup', onDragEnd);
  window.addEventListener('touchend', onDragEnd);

  scrimRef.value?.addEventListener('click', closeEnlargedImage);

  keydownHandler = (e) => {
    if (e.key === 'Escape') closeEnlargedImage();
  };
  window.addEventListener('keydown', keydownHandler);
  scheduleAutoplayResume();
  startAutoplay();
});

onUnmounted(() => {
  stopAutoplay();
  stopInertia();
  resizeObserver?.disconnect();
  const main = mainRef.value;
  if (main) {
    main.removeEventListener('mousedown', onDragStart);
    main.removeEventListener('touchstart', onDragStart);
  }
  scrimRef.value?.removeEventListener('click', closeEnlargedImage);
  window.removeEventListener('mousemove', onDragMove);
  window.removeEventListener('touchmove', onDragMove);
  window.removeEventListener('mouseup', onDragEnd);
  window.removeEventListener('touchend', onDragEnd);
  if (keydownHandler) window.removeEventListener('keydown', keydownHandler);
  document.body.classList.remove('dg-scroll-lock');
});

watch(() => props, computeRadius, {
  deep: true
});
</script>

<style>
.dg-scroll-lock {
  overflow: hidden !important;
}
</style>
