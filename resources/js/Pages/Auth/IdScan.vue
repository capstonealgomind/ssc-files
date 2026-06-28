<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';

// ── State ──────────────────────────────────────────────────────────────────
const videoEl       = ref(null);
const canvasEl      = ref(null);
const stream        = ref(null);
const capturedImage = ref(null);   // base64 data URL
const blurScore     = ref(null);
const imageQuality  = ref(null);   // 'good' | 'warn' | 'blurry'
const cameraError   = ref(null);
const cameraReady   = ref(false);
const submitting    = ref(false);
const submitError   = ref(null);

const BLUR_THRESHOLD_GOOD = 60;
const BLUR_THRESHOLD_WARN = 15;

// ── Camera ─────────────────────────────────────────────────────────────────
async function startCamera() {
    cameraError.value = null;
    cameraReady.value = false;

    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 720 }, height: { ideal: 1280 } },
        });
        if (videoEl.value) {
            videoEl.value.srcObject = stream.value;
            videoEl.value.onloadedmetadata = () => {
                videoEl.value.play();
                cameraReady.value = true;
            };
        }
    } catch (err) {
        cameraError.value = err.name === 'NotAllowedError'
            ? 'Camera access denied. Please allow camera access in your browser settings.'
            : 'Unable to access camera. Make sure a camera is connected and try again.';
    }
}

function stopCamera() {
    stream.value?.getTracks().forEach(t => t.stop());
    stream.value = null;
}

// ── Capture ─────────────────────────────────────────────────────────────────
function capture() {
    const video  = videoEl.value;
    const canvas = canvasEl.value;
    if (!video || !canvas || !cameraReady.value) return;

    const vw = video.videoWidth;
    const vh = video.videoHeight;

    // Portrait ID card: guide is 62% of frame width, CR80 ratio flipped
    const cropW = Math.round(vw * 0.62);
    const cropH = Math.round(cropW * 1.586);
    const cropX = Math.round((vw - cropW) / 2);
    const cropY = Math.round((vh - cropH) / 2);

    canvas.width  = cropW;
    canvas.height = cropH;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);

    const quality      = assessBlur(canvas);
    blurScore.value    = quality.score;
    imageQuality.value = quality.score >= BLUR_THRESHOLD_GOOD ? 'good'
                       : quality.score >= BLUR_THRESHOLD_WARN ? 'warn'
                       : 'blurry';

    capturedImage.value = canvas.toDataURL('image/jpeg', 0.92);
}

function retake() {
    capturedImage.value = null;
    blurScore.value     = null;
    imageQuality.value  = null;
    submitError.value   = null;
}

// ── Blur detection (Laplacian variance) ────────────────────────────────────
function assessBlur(canvas) {
    const sampleW = 480;
    const sampleH = Math.round(canvas.height * (sampleW / canvas.width));
    const tmp     = document.createElement('canvas');
    tmp.width     = sampleW;
    tmp.height    = sampleH;

    const sctx = tmp.getContext('2d');
    sctx.drawImage(canvas, 0, 0, sampleW, sampleH);

    const { data } = sctx.getImageData(0, 0, sampleW, sampleH);
    const w = sampleW, h = sampleH;
    const gray = (i) => data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114;

    const laps = [];
    for (let y = 1; y < h - 1; y++) {
        for (let x = 1; x < w - 1; x++) {
            const c   = (y * w + x) * 4;
            const lap = -4 * gray(c)
                + gray(((y - 1) * w + x) * 4)
                + gray(((y + 1) * w + x) * 4)
                + gray((y * w + (x - 1)) * 4)
                + gray((y * w + (x + 1)) * 4);
            laps.push(lap);
        }
    }

    const mean     = laps.reduce((s, v) => s + v, 0) / laps.length;
    const variance = laps.reduce((s, v) => s + (v - mean) ** 2, 0) / laps.length;
    return { score: variance };
}

// ── Computed ────────────────────────────────────────────────────────────────
const blurLabel = computed(() => {
    if (imageQuality.value === 'good')   return 'Sharp — good to go';
    if (imageQuality.value === 'warn')   return 'Slightly soft — readable, can continue';
    if (imageQuality.value === 'blurry') return 'Too blurry — please retake';
    return '';
});

const blurBarWidth = computed(() => {
    if (blurScore.value === null) return '0%';
    return `${Math.min(100, (blurScore.value / 200) * 100)}%`;
});

const blurBarColor = computed(() => {
    if (imageQuality.value === 'good')   return 'hsl(142 71% 45%)';
    if (imageQuality.value === 'warn')   return 'hsl(38 92% 50%)';
    return 'hsl(0 84.2% 60.2%)';
});

const canSubmit = computed(() =>
    capturedImage.value &&
    imageQuality.value !== 'blurry' &&
    !submitting.value,
);

// ── Submit ──────────────────────────────────────────────────────────────────
function submit() {
    if (!canSubmit.value) return;

    submitting.value  = true;
    submitError.value = null;

    router.post('/register/id-scan', {
        image:         capturedImage.value,
        image_quality: imageQuality.value,
    }, {
        onError(errors) {
            submitError.value = Object.values(errors)[0] || 'Upload failed. Please try again.';
            submitting.value  = false;
        },
        onFinish() {
            submitting.value = false;
        },
    });
}

// ── Lifecycle ───────────────────────────────────────────────────────────────
onMounted(startCamera);
onBeforeUnmount(stopCamera);
</script>

<template>
    <GuestLayout>
        <Head title="Scan Your ID" />

        <div class="w-full max-w-md">
            <!-- Step indicator -->
            <div class="flex items-center justify-center gap-0 mb-6">
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold" style="background-color: hsl(240 5.9% 82%); color: hsl(240 3.8% 46.1%);">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <span class="text-xs mt-1" style="color: hsl(240 3.8% 46.1%);">Your info</span>
                </div>
                <div class="h-px w-10 mb-4" style="background-color: hsl(240 5.9% 10%);"></div>
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold" style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);">2</div>
                    <span class="text-xs mt-1 font-medium" style="color: hsl(240 10% 3.9%);">Scan ID</span>
                </div>
                <div class="h-px w-10 mb-4" style="background-color: hsl(240 5.9% 82%);"></div>
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold border-2" style="border-color: hsl(240 5.9% 82%); color: hsl(240 3.8% 46.1%);">3</div>
                    <span class="text-xs mt-1" style="color: hsl(240 3.8% 46.1%);">Verify</span>
                </div>
            </div>

            <div class="rounded-xl border shadow-sm overflow-hidden" style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);">
                <div class="px-6 py-5 border-b" style="border-color: hsl(240 5.9% 90%);">
                    <h1 class="text-lg font-semibold" style="color: hsl(240 10% 3.9%);">Scan Your School ID</h1>
                    <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                        Hold your ID inside the frame, then tap <strong>Capture ID</strong>.
                    </p>
                </div>

                <div class="p-6 space-y-5">

                    <!-- Camera error -->
                    <div v-if="cameraError" class="rounded-lg border p-4 flex items-start gap-3" style="border-color: hsl(0 84.2% 82%); background-color: hsl(0 84.2% 97%);">
                        <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: hsl(0 84.2% 60.2%);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium" style="color: hsl(0 62.8% 30.6%);">Camera unavailable</p>
                            <p class="text-sm mt-0.5" style="color: hsl(0 62.8% 40%);">{{ cameraError }}</p>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <!-- Live camera -->
                        <div v-show="!capturedImage" class="relative w-full overflow-hidden rounded-xl bg-black" style="aspect-ratio: 3/4;">
                            <video ref="videoEl" class="w-full h-full object-cover" autoplay muted playsinline />

                            <!-- Guide overlay -->
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="absolute inset-0" style="background: rgba(0,0,0,0.45);"></div>
                                <div class="relative rounded-lg" style="width: 62%; aspect-ratio: 53.98/85.6; border: 2px dashed rgba(255,255,255,0.9); box-shadow: 0 0 0 9999px rgba(0,0,0,0.45);">
                                    <span class="absolute top-0 left-0 w-5 h-5 border-t-2 border-l-2 rounded-tl" style="border-color:#fff; margin:-2px 0 0 -2px;"></span>
                                    <span class="absolute top-0 right-0 w-5 h-5 border-t-2 border-r-2 rounded-tr" style="border-color:#fff; margin:-2px -2px 0 0;"></span>
                                    <span class="absolute bottom-0 left-0 w-5 h-5 border-b-2 border-l-2 rounded-bl" style="border-color:#fff; margin:0 0 -2px -2px;"></span>
                                    <span class="absolute bottom-0 right-0 w-5 h-5 border-b-2 border-r-2 rounded-br" style="border-color:#fff; margin:0 -2px -2px 0;"></span>
                                    <div class="absolute bottom-2 left-0 right-0 text-center">
                                        <span class="text-xs font-medium" style="color: rgba(255,255,255,0.85);">Align your school ID inside the frame</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!cameraReady" class="absolute inset-0 flex items-center justify-center bg-black">
                                <svg class="h-8 w-8 animate-spin" style="color: rgba(255,255,255,0.6);" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Captured preview -->
                        <div v-if="capturedImage" class="space-y-4">
                            <div class="relative rounded-xl overflow-hidden border" style="border-color: hsl(240 5.9% 90%); aspect-ratio: 3/4;">
                                <img :src="capturedImage" alt="Captured ID" class="w-full h-full object-contain" style="background:#000;" />
                            </div>

                            <!-- Image quality bar -->
                            <div class="rounded-lg border p-4 space-y-3" style="border-color: hsl(240 5.9% 90%);">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium" style="color: hsl(240 10% 3.9%);">Image quality</span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                        :style="imageQuality === 'good'
                                            ? 'background-color:hsl(142 76% 94%);color:hsl(142 71% 29%);'
                                            : imageQuality === 'warn'
                                            ? 'background-color:hsl(38 92% 94%);color:hsl(38 62% 30%);'
                                            : 'background-color:hsl(0 84.2% 94%);color:hsl(0 62.8% 40%);'">
                                        {{ blurLabel }}
                                    </span>
                                </div>
                                <div class="h-2 w-full rounded-full overflow-hidden" style="background-color: hsl(240 4.8% 95.9%);">
                                    <div class="h-2 rounded-full transition-all duration-500"
                                        :style="{ width: blurBarWidth, backgroundColor: blurBarColor }" />
                                </div>
                                <p v-if="imageQuality === 'blurry'" class="text-xs" style="color: hsl(0 62.8% 40%);">
                                    The image is too blurry to read. Please retake in better lighting and hold the camera steady.
                                </p>
                                <p v-else-if="imageQuality === 'warn'" class="text-xs" style="color: hsl(38 62% 35%);">
                                    Image is slightly soft but text is readable. You can continue or retake for a sharper scan.
                                </p>
                                <p v-else-if="imageQuality === 'good'" class="text-xs" style="color: hsl(142 71% 29%);">
                                    Great image quality! Your ID will be processed automatically.
                                </p>
                            </div>
                        </div>

                        <!-- Submit error -->
                        <div v-if="submitError" class="rounded-lg border p-3 text-sm" style="border-color:hsl(0 84.2% 82%);background-color:hsl(0 84.2% 97%);color:hsl(0 62.8% 40%);">
                            {{ submitError }}
                        </div>

                        <!-- Capture button -->
                        <div v-if="!capturedImage" class="flex justify-center">
                            <Button type="button" size="lg" :disabled="!cameraReady" @click="capture">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Capture ID
                            </Button>
                        </div>

                        <!-- Retake / Submit -->
                        <div v-else class="flex items-center justify-end gap-3">
                            <Button type="button" variant="outline" @click="retake" :disabled="submitting">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Retake
                            </Button>
                            <Button type="button" :disabled="!canSubmit" @click="submit">
                                <svg v-if="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                {{ submitting ? 'Submitting…' : 'Submit & Continue' }}
                                <svg v-if="!submitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Button>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="rounded-lg p-4 space-y-2" style="background-color: hsl(240 4.8% 95.9%);">
                        <p class="text-xs font-semibold" style="color: hsl(240 10% 3.9%);">Tips for a good scan</p>
                        <ul class="space-y-1 text-xs" style="color: hsl(240 3.8% 46.1%);">
                            <li class="flex items-start gap-1.5"><span class="mt-0.5">•</span><span>Use good lighting — avoid shadows and glare on the ID</span></li>
                            <li class="flex items-start gap-1.5"><span class="mt-0.5">•</span><span>Hold the camera steady and keep the ID flat</span></li>
                            <li class="flex items-start gap-1.5"><span class="mt-0.5">•</span><span>Fit your entire ID inside the guide frame</span></li>
                            <li class="flex items-start gap-1.5"><span class="mt-0.5">•</span><span>Make sure your name and student ID number are clearly visible</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <canvas ref="canvasEl" class="hidden" />
    </GuestLayout>
</template>
