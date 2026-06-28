<script setup>
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import InputError from '@/Components/ui/InputError.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
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
});

const form = useForm({
    name: '',
    email: '',
    student_id_number: '',
    department_id: '',
    course_id: '',
    year_level_id: '',
    password: '',
    password_confirmation: '',
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

function submit() {
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
    <GuestLayout>
        <Head title="Register" />

        <div class="w-full max-w-sm">
            <!-- Step indicator -->
            <div class="flex items-center justify-center gap-0 mb-6">
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold" style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);">1</div>
                    <span class="text-xs mt-1 font-medium" style="color: hsl(240 10% 3.9%);">Your info</span>
                </div>
                <div class="h-px w-10 mb-4" style="background-color: hsl(240 5.9% 82%);"></div>
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold border-2" style="border-color: hsl(240 5.9% 82%); color: hsl(240 3.8% 46.1%);">2</div>
                    <span class="text-xs mt-1" style="color: hsl(240 3.8% 46.1%);">Scan ID</span>
                </div>
                <div class="h-px w-10 mb-4" style="background-color: hsl(240 5.9% 82%);"></div>
                <div class="flex flex-col items-center">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold border-2" style="border-color: hsl(240 5.9% 82%); color: hsl(240 3.8% 46.1%);">3</div>
                    <span class="text-xs mt-1" style="color: hsl(240 3.8% 46.1%);">Verify</span>
                </div>
            </div>

            <div class="rounded-xl border shadow-sm p-6 sm:p-8" style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);">
                <div class="mb-6">
                    <h1 class="text-xl font-semibold tracking-tight mb-1" style="color: hsl(240 10% 3.9%);">Create an account</h1>
                    <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">Register as a voter using your student details</p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label html-for="name">Full name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Juan dela Cruz"
                            autocomplete="name"
                            :error="!!form.errors.name"
                        />
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
                    </div>

                    <div class="space-y-1.5">
                        <Label html-for="password">Password</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="new-password"
                            :error="!!form.errors.password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="space-y-1.5">
                        <Label html-for="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="new-password"
                            :error="!!form.errors.password_confirmation"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ form.processing ? 'Saving...' : 'Continue to ID Scan' }}
                        <svg v-if="!form.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Button>
                </form>

                <div class="relative my-5">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t" style="border-color: hsl(240 5.9% 90%);"></span>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="px-2" style="background-color: hsl(0 0% 100%); color: hsl(240 3.8% 46.1%);">or</span>
                    </div>
                </div>

                <p class="text-center text-sm" style="color: hsl(240 3.8% 46.1%);">
                    Already have an account?
                    <Link href="/login" class="font-medium underline underline-offset-4" style="color: hsl(240 10% 3.9%);">
                        Sign in
                    </Link>
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
