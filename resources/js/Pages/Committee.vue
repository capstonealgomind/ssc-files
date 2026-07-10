<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import InputError from '@/Components/ui/InputError.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    elections: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
    courses: {
        type: Array,
        default: () => [],
    },
    positionOptions: {
        type: Array,
        default: () => [],
    },
    partylistOptions: {
        type: Array,
        default: () => [],
    },
});

const { error: toastError } = useToast();

const form = useForm({
    election_id: '',
    name: '',
    position_id: '',
    department_id: '',
    course_id: '',
    partylist_id: '',
    platform: '',
    photo: null,
});

const photoPreview = ref(null);
const isDragging = ref(false);

const electionOptions = computed(() =>
    props.elections.map((item) => ({
        value: String(item.id),
        label: `${item.title} (${item.status_label})`,
    })),
);

const departmentOptions = computed(() => [
    { value: '', label: 'No department' },
    ...props.departments.map((item) => ({ value: String(item.id), label: item.name })),
]);

const courseOptions = computed(() => {
    if (!form.department_id) {
        return [];
    }

    return props.courses
        .filter((item) => String(item.department_id) === form.department_id)
        .map((item) => ({
            value: String(item.id),
            label: `${item.name} (${item.duration_years} ${item.duration_years === 1 ? 'year' : 'years'})`,
        }));
});

watch(() => form.department_id, () => {
    const validCourse = courseOptions.value.some((option) => option.value === form.course_id);
    if (!validCourse) {
        form.course_id = '';
    }
});

function setPhoto(file) {
    if (!file) return;
    form.photo = file;
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
    photoPreview.value = URL.createObjectURL(file);
}

function handlePhotoChange(event) {
    setPhoto(event.target.files?.[0] ?? null);
}

function handleDrop(event) {
    isDragging.value = false;
    setPhoto(event.dataTransfer?.files?.[0] ?? null);
}

function removePhoto() {
    form.photo = null;
    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
        photoPreview.value = null;
    }
}

function resetForm() {
    form.reset();
    form.clearErrors();
    removePhoto();
}

function submit() {
    form
        .transform((data) => {
            const payload = { ...data };
            if (!payload.photo) delete payload.photo;
            return payload;
        })
        .post('/committee/candidates', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                resetForm();
            },
            onError: () => {
                toastError(
                    'Action failed',
                    Object.values(form.errors)[0] || 'Please check the form and try again.',
                );
            },
        });
}
</script>

<template>
    <AppLayout>
        <Head title="Candidate" />

        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Candidate</h1>
        </template>

        <div class="w-full space-y-5">
            <div>
                <h2 class="text-lg font-semibold" style="color: hsl(240 10% 3.9%);">Add Candidate</h2>
                <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                    Create election candidates for the student council ballot.
                </p>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    <!-- Left column: details + platform -->
                    <div class="lg:col-span-2 space-y-5">

                        <!-- Candidate details card -->
                        <div
                            class="rounded-xl border p-6 space-y-5"
                            style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
                        >
                            <div>
                                <h2 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Candidate details</h2>
                                <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                    Basic information about the candidate.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label html-for="election">Election</Label>
                                    <Select
                                        id="election"
                                        v-model="form.election_id"
                                        :options="electionOptions"
                                        placeholder="Select election"
                                        :error="!!form.errors.election_id"
                                    />
                                    <InputError :message="form.errors.election_id" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label html-for="name">Full name</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Maria Santos"
                                        :error="!!form.errors.name"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label html-for="position">Position</Label>
                                    <Select
                                        id="position"
                                        v-model="form.position_id"
                                        :options="positionOptions"
                                        placeholder="Select position"
                                        :error="!!form.errors.position_id"
                                    />
                                    <InputError :message="form.errors.position_id" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label html-for="department">Department (optional)</Label>
                                    <Select
                                        id="department"
                                        v-model="form.department_id"
                                        :options="departmentOptions"
                                        placeholder="Select department"
                                        :error="!!form.errors.department_id"
                                    />
                                    <InputError :message="form.errors.department_id" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label html-for="course">Course (optional)</Label>
                                    <Select
                                        id="course"
                                        v-model="form.course_id"
                                        :options="courseOptions"
                                        :placeholder="form.department_id ? 'Select course' : 'Select department first'"
                                        :disabled="!form.department_id"
                                        :error="!!form.errors.course_id"
                                    />
                                    <p v-if="!form.department_id" class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                                        Choose a department to see available courses.
                                    </p>
                                    <InputError :message="form.errors.course_id" />
                                </div>

                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label html-for="partylist">Partylist</Label>
                                    <Select
                                        id="partylist"
                                        v-model="form.partylist_id"
                                        :options="partylistOptions"
                                        placeholder="Select partylist"
                                        :error="!!form.errors.partylist_id"
                                    />
                                    <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                                        Choose a partylist slate, or select Independent if the candidate is not affiliated.
                                    </p>
                                    <InputError :message="form.errors.partylist_id" />
                                </div>
                            </div>
                        </div>

                        <!-- Platform card -->
                        <div
                            class="rounded-xl border p-6 space-y-4"
                            style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
                        >
                            <div>
                                <h2 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Advocacies &amp; Platform</h2>
                                <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                    Describe the candidate's key advocacies and election platform.
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <textarea
                                    id="platform"
                                    v-model="form.platform"
                                    rows="7"
                                    placeholder="Share the candidate's advocacies and platform..."
                                    class="flex w-full min-w-0 rounded-md border bg-white px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-[hsl(240_3.8%_46.1%)] focus-visible:outline-none focus-visible:ring-1 resize-none"
                                    :class="form.errors.platform
                                        ? 'border-[hsl(0_84.2%_60.2%)] focus-visible:ring-[hsl(0_84.2%_60.2%)]'
                                        : 'border-[hsl(240_5.9%_90%)] focus-visible:ring-[hsl(240_5.9%_10%)]'"
                                    style="color: hsl(240 10% 3.9%);"
                                />
                                <InputError :message="form.errors.platform" />
                            </div>
                        </div>
                    </div>

                    <!-- Right column: photo upload -->
                    <div class="space-y-5">
                        <div
                            class="rounded-xl border p-6 space-y-4"
                            style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
                        >
                            <div>
                                <h2 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Candidate Photo</h2>
                                <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                    Upload a photo (optional). JPG, PNG or WebP · max 2 MB.
                                </p>
                            </div>

                            <!-- Dashed drop zone -->
                            <label
                                for="photo"
                                class="flex flex-col items-center justify-center w-full rounded-xl border-2 border-dashed cursor-pointer transition-colors"
                                :style="{
                                    borderColor: isDragging ? 'hsl(240 5.9% 10%)' : 'hsl(240 5.9% 82%)',
                                    backgroundColor: isDragging ? 'hsl(240 4.8% 95.9%)' : 'transparent',
                                    minHeight: '220px',
                                }"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop"
                            >
                                <div v-if="photoPreview" class="flex flex-col items-center gap-3 py-6 px-4">
                                    <img
                                        :src="photoPreview"
                                        alt="Preview"
                                        class="h-36 w-36 rounded-full object-cover ring-4"
                                        style="--tw-ring-color: hsl(240 5.9% 90%); box-shadow: 0 0 0 4px hsl(240 5.9% 90%);"
                                    />
                                    <span class="text-xs" style="color: hsl(240 3.8% 46.1%);">Click or drag to replace</span>
                                </div>

                                <div v-else class="flex flex-col items-center gap-3 py-10 px-4 text-center">
                                    <div
                                        class="h-14 w-14 rounded-full flex items-center justify-center"
                                        style="background-color: hsl(240 4.8% 95.9%);"
                                    >
                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: hsl(240 3.8% 46.1%);">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium" style="color: hsl(240 10% 3.9%);">
                                            Click to upload
                                        </p>
                                        <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                            or drag and drop here
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <input
                                id="photo"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="sr-only"
                                @change="handlePhotoChange"
                            />

                            <InputError :message="form.errors.photo" />

                            <button
                                v-if="photoPreview"
                                type="button"
                                class="w-full text-xs text-center transition-colors hover:underline"
                                style="color: hsl(0 84.2% 60.2%);"
                                @click="removePhoto"
                            >
                                Remove photo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="mt-5 flex items-center justify-end gap-3">
                    <Button type="button" variant="outline" :disabled="form.processing" @click="resetForm">
                        Clear
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Adding...' : 'Add candidate' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
