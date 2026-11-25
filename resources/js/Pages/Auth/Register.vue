<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import InputError from '@/Components/ui/InputError.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    scannedDetails: { type: Object, default: null },
    hasScannedId: { type: Boolean, default: false },
    scannedDocumentType: { type: String, default: 'school_id' },
    idPreviewUrl: { type: String, default: null },
    sections: { type: Array, default: () => [] },
    departments: {
        type: Array,
        default: () => [],
    },
    courses: {
        type: Array,
        default: () => [],
    },
    yearLevels: {
        type: Array,
        default: () => [],
    },
    registrationWindow: {
        type: Object,
        default: () => ({
            is_scheduled: false,
            is_open: true,
            status: 'unrestricted',
            starts_at: null,
            ends_at: null,
            message: null,
        }),
    },
});

const form = useForm('RegistrationDetails', {
    name: props.scannedDetails?.name ?? '',
    email: '',
    student_id_number: props.scannedDetails?.student_id ?? '',
    department_id: String(props.scannedDetails?.department_id ?? ''),
    course_id: String(props.scannedDetails?.course_id ?? ''),
    year_level_id: String(props.scannedDetails?.year_level_id ?? ''),
    section: props.scannedDetails?.section ?? '',
    password: '',
    password_confirmation: '',
}).dontRemember('password', 'password_confirmation');
const idFileInput = ref(null);
const registrationFields = ref(null);
const scanningId = ref(false);
const documentType = ref(props.scannedDocumentType || 'school_id');
const scanError = ref('');
const scanMessage = ref('');
const scanPhotoQuality = ref('');
const scanPhotoIssue = ref('');
const showIdPreview = ref(false);
const replacingScan = ref(false);
const activeScanDetails = ref(props.scannedDetails ? { ...props.scannedDetails } : null);
const activePreviewUrl = ref(props.idPreviewUrl);
const activeHasScan = ref(props.hasScannedId);
const activeScannedDocumentType = ref(props.scannedDocumentType);

function missingNameGuidance() {
    if (scanPhotoQuality.value === 'blurry') {
        return 'Full name not detected: this photo looks blurry. Retake it in focus, closer to the Name row, with all three name fields visible.';
    }
    if (scanPhotoIssue.value === 'sideways') {
        return 'Full name not detected: the form appears sideways. Rotate it upright, then retake or replace the photo.';
    }
    if (scanPhotoIssue.value === 'tilted') {
        return 'Full name not detected: the Name row is tilted. Straighten the form and replace the photo.';
    }
    if (scanPhotoIssue.value === 'small') {
        return 'Full name not detected: the Name row is too small in this photo. Crop or zoom closer to "Name:" and the Last Name, First Name, and Middle Name labels, then replace the photo.';
    }
    if (scanPhotoQuality.value === 'warn') {
        return 'Full name not detected: the photo has low sharpness. Retake it in focus and closer to the Name row.';
    }
    return 'OCR could not locate a readable full name row. Make sure "Name:" and all three name labels are visible, then crop closer and replace the photo.';
}

watch([() => props.idPreviewUrl, () => props.scannedDetails, () => props.scannedDocumentType], ([previewUrl, details, scannedDocumentType], previous) => {
    activePreviewUrl.value = previewUrl ?? null;
    activeScanDetails.value = details ? { ...details } : null;
    activeHasScan.value = Boolean(details && previewUrl);
    activeScannedDocumentType.value = scannedDocumentType ?? 'school_id';
    if (!details) return;
    scanPhotoIssue.value = details.name_issue ?? '';
    const isNewScan = Boolean(previewUrl) && previewUrl !== (previous?.[0] ?? null);

    if (isNewScan || replacingScan.value) {
        // A replacement photo must replace every prior OCR-derived value.
        form.name = details.name ?? '';
        form.student_id_number = details.student_id ?? '';
        form.department_id = String(details.department_id ?? '');
        form.course_id = String(details.course_id ?? '');
        form.year_level_id = String(details.year_level_id ?? '');
        form.section = details.section ?? '';
    } else {
        if (!form.name) form.name = details.name ?? '';
        if (!form.student_id_number) form.student_id_number = details.student_id ?? '';
        if (!form.department_id && details.department_id) form.department_id = String(details.department_id);
        if (!form.course_id && details.course_id) form.course_id = String(details.course_id);
        if (!form.year_level_id && details.year_level_id) form.year_level_id = String(details.year_level_id);
        if (!form.section && details.section) form.section = details.section;
    }

    const unreadFields = [
        !details.name && 'full name',
        !details.student_id && 'Student ID',
        !details.year_course && 'Year & Course',
    ].filter(Boolean);
    if (!details.name) {
        scanMessage.value = missingNameGuidance();
    } else {
        scanMessage.value = unreadFields.length
            ? `OCR could not read ${unreadFields.join(', ')}. Enter the missing details manually; they will be checked against the official student list after email verification.`
            : 'Document scanned. Review the detected details and complete the form.';
    }
}, { immediate: true });

function assessImageQuality(canvas) {
    const width = 480;
    const height = Math.max(1, Math.round(canvas.height * width / canvas.width));
    const sample = document.createElement('canvas');
    sample.width = width;
    sample.height = height;
    const context = sample.getContext('2d', { willReadFrequently: true });
    context.drawImage(canvas, 0, 0, width, height);
    const { data } = context.getImageData(0, 0, width, height);
    const gray = (i) => data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114;
    let sum = 0;
    let squared = 0;
    let count = 0;
    for (let y = 1; y < height - 1; y += 1) {
        for (let x = 1; x < width - 1; x += 1) {
            const i = (y * width + x) * 4;
            const lap = -4 * gray(i)
                + gray(((y - 1) * width + x) * 4)
                + gray(((y + 1) * width + x) * 4)
                + gray((y * width + x - 1) * 4)
                + gray((y * width + x + 1) * 4);
            sum += lap;
            squared += lap * lap;
            count += 1;
        }
    }
    const mean = sum / count;
    const variance = Math.max(0, squared / count - mean * mean);
    return variance >= 60 ? 'good' : variance >= 8 ? 'warn' : 'blurry';
}

function handleIdUpload(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    scanError.value = '';
    scanMessage.value = '';
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        scanError.value = `Choose a JPEG, PNG, or WebP photo of your ${documentType.value === 'enrollment_form' ? 'enrollment form' : 'school ID'}.`;
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        scanError.value = 'The image must be smaller than 10 MB.';
        return;
    }

    const image = new Image();
    const objectUrl = URL.createObjectURL(file);
    image.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        scanError.value = 'The image could not be read. Choose another photo.';
    };
    image.onload = () => {
        URL.revokeObjectURL(objectUrl);
        const scale = Math.min(1, 1920 / Math.max(image.naturalWidth, image.naturalHeight));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(image.naturalWidth * scale);
        canvas.height = Math.round(image.naturalHeight * scale);
        canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
        const quality = assessImageQuality(canvas);
        scanPhotoQuality.value = quality;
        scanPhotoIssue.value = '';
        replacingScan.value = true;
        form.name = '';
        form.student_id_number = '';
        form.department_id = '';
        form.course_id = '';
        form.year_level_id = '';
        form.section = '';
        activeScanDetails.value = null;
        activePreviewUrl.value = null;
        activeHasScan.value = false;
        scanningId.value = true;
        router.post('/register/id-scan', {
            image: canvas.toDataURL('image/jpeg', 0.92),
            image_quality: quality,
            document_type: documentType.value,
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                const latestDetails = page?.props?.scannedDetails ?? null;
                activeScanDetails.value = latestDetails ? { ...latestDetails } : null;
                activePreviewUrl.value = page?.props?.idPreviewUrl ?? null;
                activeHasScan.value = Boolean(page?.props?.hasScannedId && latestDetails);
                activeScannedDocumentType.value = page?.props?.scannedDocumentType ?? documentType.value;
                if (latestDetails) {
                    scanPhotoIssue.value = latestDetails.name_issue ?? '';
                    form.name = latestDetails.name ?? '';
                    form.student_id_number = latestDetails.student_id ?? '';
                    form.department_id = String(latestDetails.department_id ?? '');
                    form.course_id = String(latestDetails.course_id ?? '');
                    form.year_level_id = String(latestDetails.year_level_id ?? '');
                    form.section = latestDetails.section ?? '';
                }
                if (!latestDetails?.name) {
                    scanMessage.value = missingNameGuidance();
                }
                replacingScan.value = false;
                nextTick(() => {
                    registrationFields.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },
            onError: (errors) => {
                activeScanDetails.value = null;
                activePreviewUrl.value = null;
                activeHasScan.value = false;
                replacingScan.value = false;
                scanError.value = Object.values(errors)[0] || 'The ID scan failed. Try a clearer photo.';
            },
            onFinish: () => { scanningId.value = false; replacingScan.value = false; },
        });
    };
    image.src = objectUrl;
}
const sectionOptions = computed(() => props.sections
    .filter((section) => String(section.course_id) === form.course_id && String(section.year_level_id) === form.year_level_id)
    .map(({ letter }) => ({ value: letter, label: 'Section ' + letter })));
watch(() => [form.course_id, form.year_level_id], () => {
    if (!sectionOptions.value.some((option) => option.value === form.section)) {
        form.section = '';
    }
});

const departmentOptions = computed(() =>
    props.departments.map((item) => ({ value: String(item.id), label: item.name })),
);

const courseOptions = computed(() =>
    props.courses
        .filter((item) => String(item.department_id) === form.department_id)
        .map((item) => ({
            value: String(item.id),
            label: `${item.name} (${item.duration_years} ${item.duration_years === 1 ? 'year' : 'years'})`,
        })),
);

const selectedCourse = computed(() =>
    props.courses.find((item) => String(item.id) === form.course_id),
);

const yearLevelOptions = computed(() => {
    const maxYears = selectedCourse.value?.duration_years;

    return props.yearLevels
        .filter((item) => !maxYears || item.sort_order <= maxYears)
        .map((item) => ({ value: String(item.id), label: item.name }));
});

watch(() => form.department_id, () => {
    const validCourse = courseOptions.value.some((option) => option.value === form.course_id);
    if (!validCourse) {
        form.course_id = '';
    }
});

watch(() => form.course_id, () => {
    const validYearLevel = yearLevelOptions.value.some((option) => option.value === form.year_level_id);
    if (!validYearLevel) {
        form.year_level_id = '';
    }
});

const { error } = useToast();
const nameStatus = ref('idle');
const nameMessage = ref('');
let nameTimer = null;
let nameAbort = null;
const emailStatus = ref('idle');
const emailMessage = ref('');
let emailTimer = null;
let emailAbort = null;

function letterCount(value) {
    return (String(value).normalize('NFD').replace(/\p{M}/gu, '').match(/\p{L}/gu) || []).length;
}

watch(() => form.name, (value) => {
    window.clearTimeout(nameTimer);
    nameAbort?.abort();

    if (form.errors.name) {
        form.clearErrors('name');
    }

    const trimmed = String(value ?? '').trim();

    if (letterCount(trimmed) < 2) {
        nameStatus.value = 'idle';
        nameMessage.value = '';
        return;
    }

    nameStatus.value = 'checking';
    nameMessage.value = 'Checking this name...';

    nameTimer = window.setTimeout(async () => {
        const controller = new AbortController();
        nameAbort = controller;

        try {
            const { data } = await window.axios.get('/register/check-name', {
                params: { name: trimmed },
                signal: controller.signal,
            });

            if (data.available === null) {
                nameStatus.value = 'idle';
                nameMessage.value = '';
                return;
            }

            nameStatus.value = data.available ? 'available' : 'unavailable';
            nameMessage.value = data.message || '';
        } catch (requestError) {
            if (requestError?.code === 'ERR_CANCELED' || requestError?.name === 'CanceledError') {
                return;
            }

            nameStatus.value = 'idle';
            nameMessage.value = '';
        }
}, 300);
});

watch([() => form.student_id_number, () => form.email], ([studentId, email]) => {
    window.clearTimeout(emailTimer);
    emailAbort?.abort();
    emailStatus.value = 'idle';
    emailMessage.value = '';
    if (form.errors.email) form.clearErrors('email');

    const normalizedId = String(studentId ?? '').trim();
    const normalizedEmail = String(email ?? '').trim();
    if (!/^(?=.*\d)[a-z0-9]+(?:-[a-z0-9]+)*$/i.test(normalizedId) || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizedEmail)) {
        return;
    }

    emailStatus.value = 'checking';
    emailMessage.value = 'Checking against your official student record...';
    emailTimer = window.setTimeout(async () => {
        const controller = new AbortController();
        emailAbort = controller;
        try {
            const { data } = await window.axios.get('/register/check-email', {
                params: { student_id_number: normalizedId, email: normalizedEmail },
                signal: controller.signal,
            });
            emailStatus.value = data.status ?? 'idle';
            emailMessage.value = data.message ?? '';
        } catch (requestError) {
            if (requestError?.code === 'ERR_CANCELED' || requestError?.name === 'CanceledError') return;
            emailStatus.value = 'idle';
            emailMessage.value = '';
        }
    }, 350);
});

onUnmounted(() => {
    window.clearTimeout(nameTimer);
    nameAbort?.abort();
    window.clearTimeout(emailTimer);
    emailAbort?.abort();
});

const registrationClosed = computed(() => !props.registrationWindow.is_open);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const passwordRules = computed(() => {
    const value = form.password || '';

    return [
        { key: 'upper', label: 'One uppercase letter (A–Z)', ok: /[A-Z]/.test(value) },
        { key: 'lower', label: 'One lowercase letter (a–z)', ok: /[a-z]/.test(value) },
        { key: 'number', label: 'One number (0–9)', ok: /[0-9]/.test(value) },
        { key: 'symbol', label: 'One symbol (!@#$%^&*…)', ok: /[^A-Za-z0-9]/.test(value) },
    ];
});

const passwordIsStrong = computed(() => passwordRules.value.every((rule) => rule.ok));

function submit() {
    if (registrationClosed.value) {
        return;
    }

    if (!activeHasScan.value || activeScannedDocumentType.value !== documentType.value) {
        scanError.value = `Upload and scan your ${documentType.value === 'enrollment_form' ? 'enrollment form' : 'school ID'} before continuing.`;
        return;
    }

    if (nameStatus.value === 'unavailable') {
        form.setError(
            'name',
            nameMessage.value || 'This name is already registered. Each person can have only one account.',
        );
        error(
            'Name already registered',
            'This name is already used. Each person can have only one account.',
        );
        return;
    }

    if (emailStatus.value === 'mismatch' || emailStatus.value === 'email_missing') {
        form.setError('email', emailMessage.value);
        error('Email does not match', emailMessage.value);
        return;
    }

    if (emailStatus.value === 'checking') {
        form.setError('email', 'Please wait while we check your email against your official student record.');
        return;
    }

    if (!passwordIsStrong.value) {
        form.setError(
            'password',
            'Password must include uppercase letters, lowercase letters, numbers, and a symbol.',
        );
        error(
            'Weak password',
            'Use uppercase, lowercase, a number, and a symbol.',
        );
        return;
    }

    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: () => {
            error(
                'Registration failed',
                Object.values(form.errors)[0]
                || 'Please review your details and try again.',
            );
        },
    });
}
</script>

<template>
    <GuestLayout show-back-home reset-registration-on-home>
        <Head title="Register" />

        <div class="w-full max-w-sm lg:max-w-4xl">
            <!-- Short registration progress -->
            <div class="relative mx-auto mb-6 grid w-full max-w-xs grid-cols-2">
                <div class="absolute left-1/4 right-1/4 top-4 z-0 h-px guest-step-line-pending"></div>
                <div class="relative z-10 flex min-w-0 flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold guest-step-active">1</div>
                    <span class="mt-1 w-full px-1 text-center text-xs font-medium guest-title">Document &amp; Student Details</span>
                </div>
                <div class="relative z-10 flex min-w-0 flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold guest-step-pending">2</div>
                    <span class="mt-1 w-full px-1 text-center text-xs guest-muted">Verify Email</span>
                </div>
            </div>

            <div class="guest-card p-6 sm:p-8">
                <div class="mb-6 lg:mb-8">
                    <h1 class="text-xl font-semibold tracking-tight mb-1 guest-title">Create an account</h1>
                    <p class="text-sm guest-muted">Upload your School ID or Enrollment Form and enter your details. We scan and verify them before creating your account.</p>
                </div>

                <div
                    v-if="registrationClosed"
                    class="mb-6 rounded-lg border px-4 py-3 text-sm"
                    style="border-color:hsl(38 70% 85%); background:hsl(38 92% 94%); color:hsl(38 62% 30%);"
                >
                    <p class="font-semibold mb-1">Registration is not open</p>
                    <p>{{ registrationWindow.message }}</p>
                    <p v-if="registrationWindow.starts_at || registrationWindow.ends_at" class="mt-2 text-xs">
                        <span v-if="registrationWindow.starts_at">Opens: {{ registrationWindow.starts_at }}</span>
                        <span v-if="registrationWindow.starts_at && registrationWindow.ends_at"> · </span>
                        <span v-if="registrationWindow.ends_at">Closes: {{ registrationWindow.ends_at }}</span>
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6" :class="{ 'opacity-60 pointer-events-none': registrationClosed }">
                    <section class="rounded-xl border p-4 sm:p-5" style="border-color:var(--sscevs-border); background:var(--sscevs-blue-light);">
                        <h2 class="text-base font-semibold leading-snug guest-title">Verify your student document</h2>
                        <p class="mt-1.5 text-sm leading-5 guest-muted">Choose one document. We will scan it for your full name, course, and Student ID.</p>
                        <div class="mt-4 grid grid-cols-2 gap-2" role="group" aria-label="Choose verification document">
                            <button type="button" class="rounded-lg border px-3 py-2.5 text-sm font-semibold transition" :aria-pressed="documentType === 'school_id'" :class="documentType === 'school_id' ? 'border-blue-700 bg-blue-700 text-white' : 'border-[var(--sscevs-border)] bg-white guest-title'" @click="documentType = 'school_id'; scanError = ''">School ID</button>
                            <button type="button" class="rounded-lg border px-3 py-2.5 text-sm font-semibold transition" :aria-pressed="documentType === 'enrollment_form'" :class="documentType === 'enrollment_form' ? 'border-blue-700 bg-blue-700 text-white' : 'border-[var(--sscevs-border)] bg-white guest-title'" @click="documentType = 'enrollment_form'; scanError = ''">Enrollment Form</button>
                        </div>
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="min-w-0 md:flex-1">
                                <h3 class="mt-4 text-base font-semibold leading-snug guest-title">{{ documentType === 'enrollment_form' ? 'Enrollment Form verification' : 'School ID verification' }}</h3>
                                <p class="mt-1.5 max-w-prose text-sm leading-5 guest-muted">Upload a clear photo with your name, Student ID, and course visible. Blurry or unreadable photos must be uploaded again.</p>
                            </div>
                            <input ref="idFileInput" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="handleIdUpload" />
                            <Button type="button" class="w-full shrink-0 whitespace-nowrap md:w-auto" :disabled="scanningId" @click="idFileInput?.click()">
                                {{ scanningId ? 'Scanning document…' : activeHasScan ? 'Replace document photo' : 'Upload document' }}
                            </Button>
                        </div>
                        <div v-if="activeHasScan && activePreviewUrl && !replacingScan" class="mt-4 flex items-start gap-3 rounded-lg border border-white/70 bg-white/60 p-3">
                            <button
                                type="button"
                                class="shrink-0 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
                                aria-label="View uploaded verification document"
                                title="Click to view larger"
                                @click="showIdPreview = true"
                            >
                                <img
                                    :key="activePreviewUrl"
                                    :src="activePreviewUrl"
                                    alt="Uploaded verification document thumbnail"
                                    class="h-28 w-20 rounded-md border object-contain bg-white transition hover:shadow-md"
                                />
                            </button>
                            <div class="min-w-0 pt-1">
                                <p class="text-xs font-semibold" :style="{ color: activeScanDetails?.name ? 'hsl(142 71% 29%)' : 'hsl(0 72% 45%)' }" aria-live="polite">
                                    {{ scanMessage || 'Document scanned successfully.' }}
                                </p>
                                <p class="mt-1 text-xs leading-5 guest-muted">For enrollment forms, keep “Name:” and the Last Name, First Name, and Middle Name labels in frame. Use “Replace document photo” to upload a corrected photo.</p>
                            </div>
                        </div>
                        <div v-if="activeHasScan && activeScanDetails && !replacingScan" class="mt-3 rounded-lg border bg-white p-3" style="border-color:var(--sscevs-border);">
                            <dl class="mt-2 space-y-2 text-xs">
                                <div class="flex justify-between gap-3">
                                    <dt class="shrink-0 guest-muted">Full name</dt>
                                    <dd class="break-words text-right font-medium guest-title">{{ activeScanDetails.name || 'Not detected' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="shrink-0 guest-muted">Student ID</dt>
                                    <dd class="break-words text-right font-medium guest-title">{{ activeScanDetails.student_id || 'Not detected' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="shrink-0 guest-muted">Course</dt>
                                    <dd class="break-words text-right font-medium guest-title">{{ activeScanDetails.course || 'Not detected' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="shrink-0 guest-muted">Year &amp; Course</dt>
                                    <dd class="break-words text-right font-medium guest-title">{{ activeScanDetails.year_course || 'Not detected' }}</dd>
                                </div>
                            </dl>
                            <p v-if="!activeScanDetails.name || !activeScanDetails.student_id || !activeScanDetails.year_course" class="mt-2 text-xs leading-5 guest-muted">
                                Enter any details marked "Not detected" in the form below.
                            </p>
                        </div>
                        <p v-if="scanError" class="mt-3 text-xs font-medium text-destructive" role="alert">{{ scanError }}</p>
                        <InputError :message="form.errors.id_image" />
                    </section>

                    <div ref="registrationFields" class="grid scroll-mt-24 grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-4">
                        <!-- Left column -->
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <Label html-for="name">Full name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Last name, First name, MI"
                                    autocomplete="name"
                                    :error="!!form.errors.name || nameStatus === 'unavailable'"
                                />
                                <p
                                    v-if="nameMessage && nameStatus !== 'idle' && !form.errors.name"
                                    class="text-xs font-medium"
                                    :style="{
                                        color: nameStatus === 'available'
                                            ? 'hsl(142 71% 32%)'
                                            : nameStatus === 'unavailable'
                                                ? 'hsl(0 84.2% 60.2%)'
                                                : 'hsl(215 15% 38%)',
                                    }"
                                    aria-live="polite"
                                >
                                    {{ nameMessage }}
                                </p>
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="space-y-1.5">
                                <Label html-for="student_id_number">Student ID number</Label>
                                <Input
                                    id="student_id_number"
                                    v-model="form.student_id_number"
                                    type="text"
                                    placeholder="2024-00001"
                                    autocomplete="off"
                                    :error="!!form.errors.student_id_number"
                                />
                                <InputError :message="form.errors.student_id_number" />
                            </div>

                            <div class="space-y-1.5">
                                <Label html-for="email">Email address</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="you@example.com"
                                    autocomplete="email"
                                    :error="!!form.errors.email"
                                />
                                <InputError :message="form.errors.email" />
                                <p
                                    v-if="emailMessage && (emailStatus === 'mismatch' || emailStatus === 'email_missing')"
                                    class="text-xs text-destructive"
                                    role="alert"
                                >{{ emailMessage }}</p>
                                <p
                                    v-if="emailMessage && emailStatus !== 'mismatch' && emailStatus !== 'email_missing'"
                                    class="text-xs"
                                    :class="emailStatus === 'matched' ? 'text-emerald-700' : 'guest-muted'"
                                    role="status"
                                >{{ emailMessage }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 lg:col-start-2 lg:row-start-1 lg:row-span-2">
                            <div class="space-y-1.5">
                                <Label html-for="department_id">Department</Label>
                                <Select
                                    id="department_id"
                                    v-model="form.department_id"
                                    :options="departmentOptions"
                                    placeholder="Select department"
                                    :error="!!form.errors.department_id"
                                />
                                <InputError :message="form.errors.department_id" />
                            </div>

                            <div class="space-y-1.5">
                                <Label html-for="course_id">Course</Label>
                                <Select
                                    id="course_id"
                                    v-model="form.course_id"
                                    :options="courseOptions"
                                    :placeholder="form.department_id ? 'Select course' : 'Select a department first'"
                                    :disabled="!form.department_id"
                                    :error="!!form.errors.course_id"
                                />
                                <InputError :message="form.errors.course_id" />
                            </div>

                            <div class="space-y-1.5">
                                <Label html-for="year_level_id">Year level</Label>
                                <Select
                                    id="year_level_id"
                                    v-model="form.year_level_id"
                                    :options="yearLevelOptions"
                                    :placeholder="form.course_id ? 'Select year level' : 'Select a course first'"
                                    :disabled="!form.course_id"
                                    :error="!!form.errors.year_level_id"
                                />
                                <InputError :message="form.errors.year_level_id" />
                            </div>
                            <div class="space-y-2">
                                <Label html-for="section">Section</Label>
                                <Select
                                    id="section"
                                    v-model="form.section"
                                    :options="sectionOptions"
                                    :placeholder="!form.course_id || !form.year_level_id ? 'Select a course and year level first' : sectionOptions.length ? 'Select section' : 'No sections configured'"
                                    :disabled="!sectionOptions.length"
                                    :error="!!form.errors.section"
                                />
                                <InputError :message="form.errors.section" />
                            </div>
                        </div>

                        <div class="space-y-4 lg:col-start-1 lg:row-start-2">
                            <div class="space-y-1.5">
                                <Label html-for="password">Password</Label>
                                <div class="relative">
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        autocomplete="new-password"
                                        class="pr-10"
                                        :error="!!form.errors.password"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 guest-muted transition-colors hover:text-[var(--sscevs-black)]"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                        :aria-pressed="showPassword"
                                        @click="showPassword = !showPassword"
                                    >
                                        <svg
                                            v-if="!showPassword"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <ul class="mt-1.5 space-y-1 text-xs" aria-live="polite">
                                    <li
                                        v-for="rule in passwordRules"
                                        :key="rule.key"
                                        class="flex items-center gap-1.5"
                                        :style="{
                                            color: rule.ok
                                                ? 'hsl(142 71% 29%)'
                                                : 'hsl(240 3.8% 46.1%)',
                                        }"
                                    >
                                        <span aria-hidden="true">{{ rule.ok ? '✓' : '•' }}</span>
                                        <span>{{ rule.label }}</span>
                                    </li>
                                </ul>
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="space-y-1.5">
                                <Label html-for="password_confirmation">Confirm password</Label>
                                <div class="relative">
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        autocomplete="new-password"
                                        class="pr-10"
                                        :error="!!form.errors.password_confirmation"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 guest-muted transition-colors hover:text-[var(--sscevs-black)]"
                                        :aria-label="showPasswordConfirmation ? 'Hide password' : 'Show password'"
                                        :aria-pressed="showPasswordConfirmation"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                                    >
                                        <svg
                                            v-if="!showPasswordConfirmation"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <InputError :message="form.errors.password_confirmation" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-stretch lg:justify-end">
                    <Button type="submit" class="w-full lg:w-auto lg:min-w-48" :disabled="form.processing || nameStatus === 'unavailable' || emailStatus === 'checking' || emailStatus === 'mismatch' || emailStatus === 'email_missing'">
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ form.processing ? 'Saving...' : 'Continue to Email Verification' }}
                        <svg v-if="!form.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Button>
                    </div>
                </form>

                <div class="relative my-5">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-[var(--sscevs-border)]"></span>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="px-2 bg-white guest-muted">or</span>
                    </div>
                </div>

                <p class="text-center text-sm guest-muted">
                    Already have an account?
                    <Link href="/login" class="guest-link underline underline-offset-4">
                        Sign in
                    </Link>
                </p>
            </div>
        </div>

        <div
            v-if="showIdPreview && activePreviewUrl"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Uploaded verification document preview"
            @click.self="showIdPreview = false"
            @keydown.esc.window="showIdPreview = false"
        >
            <button
                type="button"
                class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                aria-label="Close ID preview"
                @click="showIdPreview = false"
            >
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke-width="2" stroke-linecap="round" />
                </svg>
            </button>
            <img
                :key="activePreviewUrl"
                :src="activePreviewUrl"
                alt="Full-size preview of the uploaded verification document"
                class="max-h-[88vh] max-w-[94vw] rounded-lg bg-white object-contain shadow-2xl"
            />
        </div>
    </GuestLayout>
</template>
