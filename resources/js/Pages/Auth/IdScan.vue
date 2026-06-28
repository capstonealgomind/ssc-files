<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';

const videoEl       = ref(null);
const canvasEl      = ref(null);
const fileInputRef  = ref(null);
const stream        = ref(null);
const capturedImage = ref(null);
const blurScore     = ref(null);
const imageQuality  = ref(null);
const cameraError   = ref(null);
const cameraReady   = ref(false);
const submitting    = ref(false);
const submitError   = ref(null);
const uploadError   = ref(null);
const inputMode     = ref('camera');
const isDragging    = ref(false);

const BLUR_THRESHOLD_GOOD = 60;
const BLUR_THRESHOLD_WARN = 15;
const MAX_FILE_SIZE       = 10 * 1024 * 1024;
const ALLOWED_TYPES       = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

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
            ? 'Camera access denied. Please allow camera access or upload an image instead.'
            : 'Unable to access camera. You can upload an image of your ID instead.';
    }
}

function stopCamera() {
    stream.value?.getTracks().forEach(t => t.stop());
    stream.value = null;
    cameraReady.value = false;
}

function switchMode(mode) {
    if (inputMode.value === mode || capturedImage.value) return;

    inputMode.value = mode;
    uploadError.value = null;
    submitError.value = null;

    if (mode === 'camera') {
        startCamera();
    } else {
        stopCamera();
    }
}

function capture() {
    const video  = videoEl.value;
    const canvas = canvasEl.value;
    if (!video || !canvas || !cameraReady.value) return;

    const vw = video.videoWidth;
    const vh = video.videoHeight;

    const cropW = Math.round(vw * 0.62);
    const cropH = Math.round(cropW * 1.586);
    const cropX = Math.round((vw - cropW) / 2);
    const cropY = Math.round((vh - cropH) / 2);

    canvas.width  = cropW;
    canvas.height = cropH;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);

    applyQualityFromCanvas(canvas);
}

function processImageFile(file) {
    uploadError.value = null;

    if (!ALLOWED_TYPES.includes(file.type)) {
        uploadError.value = 'Please upload a JPEG, PNG, or WebP image.';
        return;
    }

    if (file.size > MAX_FILE_SIZE) {
        uploadError.value = 'Image must be smaller than 10 MB.';
        return;
    }

    const img = new Image();
    const url = URL.createObjectURL(file);

    img.onload = () => {
        URL.revokeObjectURL(url);
        processImageElement(img);
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
    };

    img.onerror = () => {
        URL.revokeObjectURL(url);
        uploadError.value = 'Failed to load image. Please try another file.';
    };

    img.src = url;
}

function processImageElement(img) {
    const canvas = canvasEl.value;
    if (!canvas) return;

    let w = img.naturalWidth;
    let h = img.naturalHeight;
    const maxDim = 1920;

    if (Math.max(w, h) > maxDim) {
        const scale = maxDim / Math.max(w, h);
        w = Math.round(w * scale);
        h = Math.round(h * scale);
    }

    canvas.width  = w;
    canvas.height = h;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, w, h);

    applyQualityFromCanvas(canvas);
}

function applyQualityFromCanvas(canvas) {
    const quality      = assessBlur(canvas);
    blurScore.value    = quality.score;
    imageQuality.value = quality.score >= BLUR_THRESHOLD_GOOD ? 'good'
                       : quality.score >= BLUR_THRESHOLD_WARN ? 'warn'
                       : 'blurry';

    capturedImage.value = canvas.toDataURL('image/jpeg', 0.92);
    stopCamera();
}

function triggerUpload() {
    fileInputRef.value?.click();
}

function handleFileSelect(event) {
    const file = event.target.files?.[0];
    if (file) {
        processImageFile(file);
    }
}

function handleDrop(event) {
    event.preventDefault();
    isDragging.value = false;

    const file = event.dataTransfer?.files?.[0];
    if (file) {
        processImageFile(file);
    }
}

function handleDragOver(event) {
    event.preventDefault();
    isDragging.value = true;
}

function handleDragLeave() {
    isDragging.value = false;
}

function retake() {
    capturedImage.value = null;
    blurScore.value     = null;
    imageQuality.value  = null;
    submitError.value   = null;
    uploadError.value   = null;

    if (inputMode.value === 'camera') {
        startCamera();
    }
}

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
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold guest-step-done">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <span class="text-xs mt-1 guest-muted">Your info</span>
                </div>
                <div class="h-px w-10 mb-4 guest-step-line-active"></div>
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold guest-step-active">2</div>
                    <span class="text-xs mt-1 font-medium guest-title">Scan ID</span>
                </div>
                <div class="h-px w-10 mb-4 guest-step-line-pending"></div>
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold guest-step-pending">3</div>
                    <span class="text-xs mt-1 guest-muted">Verify</span>
                </div>
            </div>

            <div class="guest-card overflow-hidden">
                <div class="px-6 py-5 border-b border-[var(--sscevs-border)]">
                    <h1 class="text-lg font-semibold guest-title">Submit Your School ID</h1>
                    <p class="text-sm mt-0.5 guest-muted">
                        Capture a photo with your camera or upload an existing image of your ID.
                    </p>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Mode toggle -->
                    <div
                        v-if="!capturedImage"
                        class="grid grid-cols-2 gap-1 rounded-lg p-1 guest-segment"
                    >
                        <button
                            type="button"
                            class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
                            :class="inputMode === 'camera' ? 'guest-segment-item-active' : 'guest-segment-item-inactive'"
                            @click="switchMode('camera')"
                        >
                            Use camera
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
                            :class="inputMode === 'upload' ? 'guest-segment-item-active' : 'guest-segment-item-inactive'"
                            @click="switchMode('upload')"
                        >
                            Upload image
                        </button>
                    </div>

                    <!-- Camera mode -->
                    <template v-if="!capturedImage && inputMode === 'camera'">
                        <div v-if="cameraError" class="rounded-lg border p-4 flex items-start gap-3" style="border-color: hsl(0 84.2% 82%); background-color: hsl(0 84.2% 97%);">
                            <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: hsl(0 84.2% 60.2%);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium" style="color: hsl(0 62.8% 30.6%);">Camera unavailable</p>
                                <p class="text-sm mt-0.5" style="color: hsl(0 62.8% 40%);">{{ cameraError }}</p>
                                <button
                                    type="button"
                                    class="text-sm font-medium mt-2 guest-link underline underline-offset-4"
                                    @click="switchMode('upload')"
                                >
                                    Upload an image instead
                                </button>
                            </div>
                        </div>

                        <div v-else class="space-y-4">
                            <div class="relative w-full overflow-hidden rounded-xl bg-black" style="aspect-ratio: 3/4;">
                                <video ref="videoEl" class="w-full h-full object-cover" autoplay muted playsinline />

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

                            <div class="flex justify-center">
                                <Button type="button" size="lg" :disabled="!cameraReady" @click="capture">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Capture ID
                                </Button>
                            </div>
                        </div>
                    </template>

                    <!-- Upload mode -->
                    <template v-if="!capturedImage && inputMode === 'upload'">
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            class="hidden"
                            @change="handleFileSelect"
                        />

                        <div
                            class="relative rounded-xl border-2 border-dashed transition-colors cursor-pointer"
                            :class="isDragging ? 'guest-dropzone-active' : 'guest-dropzone'"
                            style="aspect-ratio: 4/3;"
                            @click="triggerUpload"
                            @drop="handleDrop"
                            @dragover="handleDragOver"
                            @dragleave="handleDragLeave"
                        >
                            <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center">
                                <div class="h-12 w-12 rounded-full flex items-center justify-center mb-4 guest-feature-icon">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                </div>
                                <p class="text-sm font-medium guest-title">
                                    Click to upload or drag and drop
                                </p>
                                <p class="text-xs mt-1.5 guest-muted">
                                    JPEG, PNG, or WebP up to 10 MB
                                </p>
                            </div>
                        </div>

                        <div v-if="uploadError" class="rounded-lg border p-3 text-sm" style="border-color:hsl(0 84.2% 82%);background-color:hsl(0 84.2% 97%);color:hsl(0 62.8% 40%);">
                            {{ uploadError }}
                        </div>

                        <div class="flex justify-center">
                            <Button type="button" variant="outline" @click="triggerUpload">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Choose file
                            </Button>
                        </div>
                    </template>

                    <!-- Preview (camera or upload) -->
                    <div v-if="capturedImage" class="space-y-4">
                        <div class="relative rounded-xl overflow-hidden border border-[var(--sscevs-border)]" style="aspect-ratio: 3/4;">
                            <img :src="capturedImage" alt="ID preview" class="w-full h-full object-contain" style="background:#000;" />
                        </div>

                        <div class="rounded-lg border border-[var(--sscevs-border)] p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium guest-title">Image quality</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="imageQuality === 'good'
                                        ? 'guest-success-surface'
                                        : imageQuality === 'warn'
                                        ? 'guest-warning-surface'
                                        : 'text-destructive bg-destructive/10 border border-destructive/20'">
                                    {{ blurLabel }}
                                </span>
                            </div>
                            <div class="h-2 w-full rounded-full overflow-hidden bg-[var(--sscevs-blue-light)]">
                                <div class="h-2 rounded-full transition-all duration-500"
                                    :style="{ width: blurBarWidth, backgroundColor: blurBarColor }" />
                            </div>
                            <p v-if="imageQuality === 'blurry'" class="text-xs text-destructive">
                                The image is too blurry to read. Please {{ inputMode === 'upload' ? 'upload a clearer photo' : 'retake in better lighting' }}.
                            </p>
                            <p v-else-if="imageQuality === 'warn'" class="text-xs guest-accent-text">
                                Image is slightly soft but text is readable. You can continue or choose a sharper photo.
                            </p>
                            <p v-else-if="imageQuality === 'good'" class="text-xs text-[var(--sscevs-blue-dark)]">
                                Great image quality! Your ID will be processed automatically.
                            </p>
                        </div>
                    </div>

                    <div v-if="submitError" class="rounded-lg border p-3 text-sm" style="border-color:hsl(0 84.2% 82%);background-color:hsl(0 84.2% 97%);color:hsl(0 62.8% 40%);">
                        {{ submitError }}
                    </div>

                    <div v-if="capturedImage" class="flex items-center justify-end gap-3">
                        <Button type="button" variant="outline" @click="retake" :disabled="submitting">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ inputMode === 'upload' ? 'Choose another' : 'Retake' }}
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

                    <div class="rounded-lg p-4 space-y-2 guest-tips-box">
                        <p class="text-xs font-semibold guest-title">Tips for a good ID photo</p>
                        <ul class="space-y-1 text-xs guest-muted">
                            <li class="flex items-start gap-1.5"><span class="mt-0.5">•</span><span>Use good lighting — avoid shadows and glare</span></li>
                            <li class="flex items-start gap-1.5"><span class="mt-0.5">•</span><span>Make sure your name and student ID number are clearly visible</span></li>
                            <li class="flex items-start gap-1.5"><span class="mt-0.5">•</span><span>You can capture with your camera or upload a photo from your device</span></li>
                            <li class="flex items-start gap-1.5"><span class="mt-0.5">•</span><span>Accepted formats: JPEG, PNG, WebP (max 10 MB)</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <canvas ref="canvasEl" class="hidden" />
    </GuestLayout>
</template>
