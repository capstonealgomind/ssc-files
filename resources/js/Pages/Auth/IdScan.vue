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
const torchOn        = ref(false);
const torchSupported = ref(false);
const isMobile       = ref(false);
const cameras        = ref([]);
const activeCameraIndex = ref(0);
const lastFacing        = ref('environment');

const FRAME_INSET = 0.04;
const FRAME_WIDTH_RATIO = 1 - FRAME_INSET * 2;

const canSwitchCamera = computed(() => cameras.value.length > 1);
const showCameraSwitch = computed(() => canSwitchCamera.value || isMobile.value);
const activeFacing = computed(() => cameras.value[activeCameraIndex.value]?.facing ?? lastFacing.value);

const BLUR_THRESHOLD_GOOD = 60;
const BLUR_THRESHOLD_WARN = 15;
const MAX_FILE_SIZE       = 10 * 1024 * 1024;
const ALLOWED_TYPES       = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

function classifyFacing(label, index, total) {
    if (/back|rear|environment|trás|arrière/i.test(label)) {
        return 'environment';
    }
    if (/front|user|face|selfie|facial/i.test(label)) {
        return 'user';
    }
    if (total === 2) {
        return index === 0 ? 'user' : 'environment';
    }

    return index === total - 1 ? 'environment' : 'user';
}

async function enumerateCameras() {
    if (!navigator.mediaDevices?.enumerateDevices) {
        return;
    }

    if (cameras.value.length === 0) {
        const tempStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        tempStream.getTracks().forEach(t => t.stop());
    }

    const devices = await navigator.mediaDevices.enumerateDevices();
    const videoInputs = devices.filter(d => d.kind === 'videoinput');

    cameras.value = videoInputs.map((device, index) => ({
        deviceId: device.deviceId,
        label: device.label,
        facing: classifyFacing(device.label, index, videoInputs.length),
    }));
}

function stopCurrentStream() {
    if (stream.value) {
        const track = stream.value.getVideoTracks()[0];
        if (track && torchOn.value) {
            track.applyConstraints({ advanced: [{ torch: false }] }).catch(() => {});
        }
        stream.value.getTracks().forEach(t => t.stop());
    }

    stream.value = null;
    cameraReady.value = false;
    torchOn.value = false;
    torchSupported.value = false;
}

async function openCameraAtIndex(index) {
    const camera = cameras.value[index];
    if (!camera) {
        return false;
    }

    stopCurrentStream();
    cameraError.value = null;
    cameraReady.value = false;

    const attachStream = (mediaStream) => {
        stream.value = mediaStream;
        activeCameraIndex.value = index;
        lastFacing.value = camera.facing;

        if (videoEl.value) {
            videoEl.value.srcObject = mediaStream;
            videoEl.value.onloadedmetadata = () => {
                videoEl.value.play();
                cameraReady.value = true;
                checkTorchSupport();
            };
        }
    };

    try {
        attachStream(await navigator.mediaDevices.getUserMedia({
            video: {
                deviceId: { exact: camera.deviceId },
                width:  { ideal: 1920 },
                height: { ideal: 1080 },
            },
            audio: false,
        }));

        return true;
    } catch {
        try {
            attachStream(await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { exact: camera.facing },
                    width:  { ideal: 1920 },
                    height: { ideal: 1080 },
                },
                audio: false,
            }));

            return true;
        } catch {
            return false;
        }
    }
}

async function startCamera(preferredFacing = 'environment') {
    cameraError.value = null;

    try {
        await enumerateCameras();

        if (cameras.value.length > 0) {
            let targetIndex = cameras.value.findIndex(c => c.facing === preferredFacing);
            if (targetIndex === -1) {
                targetIndex = 0;
            }

            if (await openCameraAtIndex(targetIndex)) {
                return;
            }
        }

        const facing = isMobile.value ? preferredFacing : 'environment';
        stopCurrentStream();

        const mediaStream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: isMobile.value ? { exact: facing } : { ideal: facing },
                width:  { ideal: 1920 },
                height: { ideal: 1080 },
            },
            audio: false,
        });

        stream.value = mediaStream;
        lastFacing.value = facing;

        if (videoEl.value) {
            videoEl.value.srcObject = mediaStream;
            videoEl.value.onloadedmetadata = () => {
                videoEl.value.play();
                cameraReady.value = true;
                checkTorchSupport();
            };
        }
    } catch (err) {
        if (preferredFacing === 'environment') {
            await startCamera('user');
            return;
        }

        cameraError.value = err.name === 'NotAllowedError'
            ? 'Camera access denied. Please allow camera access or upload an image instead.'
            : 'Unable to access camera. You can upload an image of your ID instead.';
    }
}

async function switchCamera() {
    const nextFacing = activeFacing.value === 'environment' ? 'user' : 'environment';

    if (canSwitchCamera.value) {
        let nextIndex = cameras.value.findIndex((camera, index) =>
            camera.facing === nextFacing && index !== activeCameraIndex.value,
        );

        if (nextIndex === -1) {
            nextIndex = (activeCameraIndex.value + 1) % cameras.value.length;
        }

        if (await openCameraAtIndex(nextIndex)) {
            return;
        }
    }

    await startCamera(nextFacing);
}

function checkTorchSupport() {
    const track = stream.value?.getVideoTracks()[0];
    if (!track?.getCapabilities) {
        return;
    }

    const caps = track.getCapabilities();
    torchSupported.value = !!caps.torch;
}

async function toggleTorch() {
    const track = stream.value?.getVideoTracks()[0];
    if (!track || !torchSupported.value) {
        return;
    }

    const next = !torchOn.value;

    try {
        await track.applyConstraints({ advanced: [{ torch: next }] });
        torchOn.value = next;
    } catch {
        try {
            await track.applyConstraints({ torch: next });
            torchOn.value = next;
        } catch {
            torchSupported.value = false;
        }
    }
}

function stopCamera() {
    stopCurrentStream();
    cameras.value = [];
    activeCameraIndex.value = 0;
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

    const displayW = video.clientWidth;
    const displayH = video.clientHeight;
    const vw = video.videoWidth;
    const vh = video.videoHeight;

    const frameX = displayW * FRAME_INSET;
    const frameY = displayH * FRAME_INSET;
    const frameW = displayW * FRAME_WIDTH_RATIO;
    const frameH = displayH * FRAME_WIDTH_RATIO;

    const scale   = Math.max(displayW / vw, displayH / vh);
    const offsetX = (vw * scale - displayW) / 2;
    const offsetY = (vh * scale - displayH) / 2;

    const cropX = Math.round((frameX + offsetX) / scale);
    const cropY = Math.round((frameY + offsetY) / scale);
    const cropW = Math.round(frameW / scale);
    const cropH = Math.round(frameH / scale);

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
        startCamera(lastFacing.value);
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

onMounted(() => {
    isMobile.value = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)
        || window.matchMedia('(pointer: coarse)').matches;
    startCamera();
});
onBeforeUnmount(stopCamera);
</script>

<template>
    <GuestLayout>
        <Head title="Scan Your ID" />

        <div class="w-full max-w-md min-w-0">
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
                <div class="px-4 py-5 sm:px-6 border-b border-[var(--sscevs-border)]">
                    <h1 class="text-lg font-semibold guest-title">Submit Your School ID</h1>
                    <p class="text-sm mt-0.5 guest-muted">
                        Capture a photo with your camera or upload an existing image of your ID.
                    </p>
                </div>

                <div class="p-4 sm:p-6 space-y-5 min-w-0">
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

                                <button
                                    v-if="showCameraSwitch && cameraReady"
                                    type="button"
                                    class="absolute top-3 left-3 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white transition-colors hover:bg-black/70"
                                    :aria-label="activeFacing === 'environment' ? 'Switch to front camera' : 'Switch to back camera'"
                                    @click="switchCamera"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>

                                <button
                                    v-if="torchSupported && cameraReady"
                                    type="button"
                                    class="absolute top-3 right-3 z-10 flex h-10 w-10 items-center justify-center rounded-full transition-colors"
                                    :class="torchOn ? 'bg-yellow-400 text-black' : 'bg-black/50 text-white'"
                                    :aria-label="torchOn ? 'Turn flash off' : 'Turn flash on'"
                                    @click="toggleTorch"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </button>

                                <div class="absolute inset-0 pointer-events-none">
                                    <div
                                        class="absolute rounded-lg"
                                        style="inset: 4%; border: 2px dashed rgba(255,255,255,0.9); box-shadow: 0 0 0 9999px rgba(0,0,0,0.45);"
                                    >
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

                        <div class="flex justify-center px-1">
                            <Button type="button" size="lg" class="w-full sm:w-auto max-w-full" :disabled="!cameraReady" @click="capture">
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

                        <div class="flex justify-center px-1">
                            <Button type="button" variant="outline" class="w-full sm:w-auto max-w-full" @click="triggerUpload">
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

                    <div v-if="capturedImage" class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end min-w-0">
                        <Button type="button" variant="outline" class="w-full sm:w-auto shrink-0 justify-center !whitespace-normal text-center" :disabled="submitting" @click="retake">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span class="sm:hidden">{{ inputMode === 'upload' ? 'Upload new image' : 'Retake photo' }}</span>
                            <span class="hidden sm:inline">{{ inputMode === 'upload' ? 'Choose another' : 'Retake' }}</span>
                        </Button>
                        <Button type="button" class="w-full sm:w-auto shrink-0 justify-center !whitespace-normal text-center" :disabled="!canSubmit" @click="submit">
                            <svg v-if="submitting" class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <span>{{ submitting ? 'Submitting…' : 'Submit & Continue' }}</span>
                            <svg v-if="!submitting" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
