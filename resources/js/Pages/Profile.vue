<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    profile: { type: Object, required: true },
});

const photoInput = ref(null);
const showPasswordDialog = ref(false);
const photoForm = useForm({ profile_photo: null });
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const { success, error } = useToast();

const isVoter = computed(() => props.profile.role === 'voter');

const initials = computed(() =>
    (props.profile.name ?? '?').split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase(),
);

const verificationBadge = computed(() => {
    if (!isVoter.value) {
        return { label: props.profile.role, color: 'hsl(221 83% 35%)', bg: 'hsl(221 83% 94%)' };
    }

    if (props.profile.is_verified) {
        return { label: 'Verified Voter', color: 'hsl(142 71% 25%)', bg: 'hsl(142 76% 94%)' };
    }

    return { label: 'Pending Verification', color: 'hsl(38 62% 30%)', bg: 'hsl(38 92% 94%)' };
});

function statusLabel(value) {
    if (!value) return '—';
    return String(value).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function profileRows(items) {
    return items.filter((item) => item.value);
}

function isPositiveStatus(label, value) {
    const v = String(value).toLowerCase();
    if (label === 'Account Status') return v === 'verified';
    return ['verified', 'completed', 'approved', 'active'].includes(v);
}

function openPhotoPicker() {
    photoInput.value?.click();
}

function onPhotoSelected(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    photoForm.profile_photo = file;
    photoForm.post('/profile/photo', {
        forceFormData: true,
        preserveScroll: true,
        onError: () => error('Upload failed', 'Please choose an image file up to 5MB.'),
        onFinish: () => {
            photoForm.reset();
            if (photoInput.value) photoInput.value.value = '';
        },
    });
}

function openPasswordDialog() {
    passwordForm.reset();
    passwordForm.clearErrors();
    showPasswordDialog.value = true;
}

function closePasswordDialog() {
    showPasswordDialog.value = false;
    passwordForm.reset();
    passwordForm.clearErrors();
}

function submitPasswordUpdate() {
    passwordForm.post('/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            closePasswordDialog();
            success('Password updated', 'Your password has been changed successfully.');
        },
        onError: () => error('Update failed', 'Please check your current password and try again.'),
    });
}
</script>

<template>
    <AppLayout>
        <Head title="My Profile" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">My Profile</h1>
        </template>

        <div class="flex flex-col gap-5 min-h-full">
            <!-- Hero -->
            <div class="rounded-xl border overflow-hidden sscevs-panel"
                style="border-color:hsl(240 5.9% 90%); background:linear-gradient(135deg, hsl(221 83% 98%) 0%, #fff 55%, hsl(43 60% 97%) 100%);">
                <div class="px-4 py-5 sm:px-8 sm:py-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6 lg:gap-8">
                        <div class="flex items-center gap-3 sm:gap-4 min-w-0 flex-1">
                            <div class="relative shrink-0">
                                <div class="h-14 w-14 sm:h-20 sm:w-20 rounded-2xl overflow-hidden flex items-center justify-center text-lg sm:text-2xl font-bold shadow-sm"
                                    style="background:var(--sscevs-navy); color:#fff;">
                                    <img
                                        v-if="profile.profile_photo_url"
                                        :src="profile.profile_photo_url"
                                        alt="Profile photo"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else>{{ initials }}</span>
                                </div>
                                <button
                                    type="button"
                                    class="absolute -bottom-1 -right-1 h-7 w-7 sm:h-8 sm:w-8 rounded-full flex items-center justify-center border-2 border-white shadow-md transition-opacity hover:opacity-90 disabled:opacity-60"
                                    style="background:var(--sscevs-navy); color:#fff;"
                                    title="Change profile photo"
                                    :disabled="photoForm.processing"
                                    @click="openPhotoPicker"
                                >
                                    <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <input
                                    ref="photoInput"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="onPhotoSelected"
                                />
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-base sm:text-2xl font-bold break-words leading-tight" style="color:hsl(240 10% 3.9%);">
                                    {{ profile.name }}
                                </h2>
                                <p class="text-xs sm:text-sm mt-1 break-all" style="color:hsl(240 3.8% 46.1%);">{{ profile.email }}</p>
                                <div class="flex flex-wrap items-center gap-2 mt-2 sm:mt-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize"
                                        :style="{ color: verificationBadge.color, background: verificationBadge.bg }">
                                        {{ verificationBadge.label }}
                                    </span>
                                    <span v-if="isVoter && profile.voter_id_number"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono font-medium break-all"
                                        style="background:hsl(240 4.8% 95.9%); color:hsl(240 10% 3.9%);">
                                        {{ profile.voter_id_number }}
                                    </span>
                                    <span v-if="isVoter && profile.account_duration"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                        style="background:hsl(221 83% 94%); color:hsl(221 83% 35%);"
                                        title="Account duration based on course length">
                                        {{ profile.account_duration }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-if="isVoter" class="flex flex-row flex-wrap items-center justify-end gap-x-4 gap-y-2 shrink-0 self-end sm:self-center sm:ml-4 lg:ml-8">
                            <Link
                                href="/my-votes"
                                class="inline-flex items-center gap-1 text-sm font-semibold underline-offset-4 hover:underline transition-colors"
                                style="color:var(--sscevs-navy);"
                            >
                                View My Votes
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </Link>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-sm font-semibold underline-offset-4 hover:underline transition-colors"
                                style="color:var(--sscevs-navy);"
                                @click="openPasswordDialog"
                            >
                                Update Password
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="isVoter" class="grid grid-cols-2 sm:grid-cols-4 border-t gap-px"
                    style="border-color:hsl(240 5.9% 90%); background:hsl(240 5.9% 90%);">
                    <div class="px-3 py-3 sm:px-5 sm:py-4 bg-white">
                        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Student ID</p>
                        <p class="text-xs sm:text-sm font-semibold font-mono mt-0.5 break-all" style="color:hsl(240 10% 3.9%);">{{ profile.student_id_number || '—' }}</p>
                    </div>
                    <div class="px-3 py-3 sm:px-5 sm:py-4 bg-white">
                        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Department</p>
                        <p class="text-xs sm:text-sm font-semibold mt-0.5 break-words line-clamp-2" style="color:hsl(240 10% 3.9%);">{{ profile.department || '—' }}</p>
                    </div>
                    <div class="px-3 py-3 sm:px-5 sm:py-4 bg-white">
                        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Course</p>
                        <p class="text-xs sm:text-sm font-semibold mt-0.5 break-words line-clamp-3" style="color:hsl(240 10% 3.9%);">{{ profile.course || '—' }}</p>
                    </div>
                    <div class="px-3 py-3 sm:px-5 sm:py-4 bg-white">
                        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Year Level</p>
                        <p class="text-xs sm:text-sm font-semibold mt-0.5" style="color:hsl(240 10% 3.9%);">{{ profile.year_level || '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 flex-1 items-stretch">
                <!-- Left panels -->
                <div class="lg:col-span-7 xl:col-span-8 flex flex-col gap-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                        <section class="rounded-xl border sscevs-panel p-4 sm:p-5" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center" style="background:hsl(221 83% 94%);">
                                <svg class="h-4 w-4" style="color:var(--sscevs-blue);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">Account Information</h3>
                        </div>
                        <dl class="space-y-3">
                            <div
                                v-for="row in profileRows([
                                    { label: 'Full Name', value: profile.name },
                                    { label: 'Email', value: profile.email },
                                    { label: 'Role', value: profile.role },
                                    { label: 'Registered', value: profile.registered_at },
                                ])"
                                :key="row.label"
                                class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-4 py-2 border-b last:border-0"
                                style="border-color:hsl(240 5.9% 90%);"
                            >
                                <dt class="text-xs font-medium" style="color:hsl(240 3.8% 46.1%);">{{ row.label }}</dt>
                                <dd class="text-sm font-medium break-words sm:text-right capitalize" style="color:hsl(240 10% 3.9%);">{{ row.value }}</dd>
                            </div>
                        </dl>
                    </section>

                        <section v-if="isVoter" class="rounded-xl border sscevs-panel p-4 sm:p-5" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="h-8 w-8 rounded-lg flex items-center justify-center" style="background:hsl(262 83% 94%);">
                                    <svg class="h-4 w-4" style="color:hsl(262 60% 45%);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">Voter Information</h3>
                            </div>
                            <dl class="space-y-3">
                                <div
                                    v-for="row in profileRows([
                                        { label: 'Voter ID', value: profile.voter_id_number },
                                        { label: 'Student ID', value: profile.student_id_number },
                                    ])"
                                    :key="row.label"
                                    class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-4 py-2 border-b last:border-0"
                                    style="border-color:hsl(240 5.9% 90%);"
                                >
                                    <dt class="text-xs font-medium" style="color:hsl(240 3.8% 46.1%);">{{ row.label }}</dt>
                                    <dd class="text-sm font-medium font-mono break-all sm:text-right" style="color:hsl(240 10% 3.9%);">{{ row.value }}</dd>
                                </div>
                            </dl>
                        </section>
                    </div>

                    <section v-if="isVoter" class="rounded-xl border sscevs-panel p-4 sm:p-5" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="h-8 w-8 rounded-lg flex items-center justify-center" style="background:hsl(43 60% 92%);">
                                    <svg class="h-4 w-4" style="color:var(--sscevs-gold-dark);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">Academic Information</h3>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-stretch">
                                <div class="rounded-lg p-4 h-full" style="background:hsl(240 4.8% 98%);">
                                    <p class="text-xs font-medium" style="color:hsl(240 3.8% 46.1%);">Department</p>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span
                                            v-if="profile.department_acronym"
                                            class="inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[10px] font-bold text-white shrink-0"
                                            :style="{ background: profile.department_color_hex || 'var(--sscevs-blue)' }"
                                        >
                                            {{ profile.department_acronym }}
                                        </span>
                                        <p class="text-sm font-semibold break-words" style="color:hsl(240 10% 3.9%);">{{ profile.department || '—' }}</p>
                                    </div>
                                </div>
                                <div class="rounded-lg p-4 h-full" style="background:hsl(240 4.8% 98%);">
                                    <p class="text-xs font-medium" style="color:hsl(240 3.8% 46.1%);">Course</p>
                                    <p class="text-sm font-semibold mt-1.5 break-words leading-snug" style="color:hsl(240 10% 3.9%);">{{ profile.course || '—' }}</p>
                                </div>
                                <div class="rounded-lg p-4 h-full" style="background:hsl(240 4.8% 98%);">
                                    <p class="text-xs font-medium" style="color:hsl(240 3.8% 46.1%);">Year Level</p>
                                    <p class="text-sm font-semibold mt-1.5" style="color:hsl(240 10% 3.9%);">{{ profile.year_level || '—' }}</p>
                                </div>
                            </div>
                        </section>
                </div>

                <!-- Right column -->
                <div v-if="isVoter" class="lg:col-span-5 xl:col-span-4 flex flex-col gap-5">
                    <section v-if="profile.id_image_url" class="rounded-xl border sscevs-panel p-4 sm:p-5 flex flex-col" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center" style="background:hsl(142 76% 92%);">
                                <svg class="h-4 w-4" style="color:hsl(142 71% 35%);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">Registered ID</h3>
                        </div>
                        <div class="flex-1 flex items-center justify-center rounded-lg p-4 min-h-[160px]"
                            style="background:hsl(240 4.8% 98%); border:1px dashed hsl(240 5.9% 82%);">
                            <img
                                :src="profile.id_image_url"
                                alt="Registered ID"
                                class="max-w-full max-h-[160px] sm:max-h-[200px] w-auto object-contain rounded-md shadow-sm mx-auto"
                                style="border:1px solid hsl(240 5.9% 90%);"
                            />
                        </div>
                    </section>

                    <section class="rounded-xl border sscevs-panel p-4 sm:p-5 lg:flex-1" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center" style="background:hsl(142 76% 92%);">
                                <svg class="h-4 w-4" style="color:hsl(142 71% 35%);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">Verification Status</h3>
                        </div>
                        <ul class="space-y-2">
                            <li
                                v-for="row in profileRows([
                                    { label: 'Account Status', value: profile.is_verified ? 'Verified' : 'Pending' },
                                    { label: 'Email Status', value: statusLabel(profile.email_status) },
                                    { label: 'OCR Status', value: statusLabel(profile.ocr_status) },
                                    { label: 'Admin Review', value: statusLabel(profile.verification_status) },
                                    { label: 'Registration', value: statusLabel(profile.registration_status) },
                                ])"
                                :key="row.label"
                                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between rounded-lg px-3 py-2.5"
                                style="background:hsl(240 4.8% 98%);"
                            >
                                <span class="text-sm" style="color:hsl(240 3.8% 46.1%);">{{ row.label }}</span>
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-1 rounded-full shrink-0 self-start sm:self-auto"
                                    :style="isPositiveStatus(row.label, row.value)
                                        ? { color: 'hsl(142 71% 25%)', background: 'hsl(142 76% 94%)' }
                                        : { color: 'hsl(38 62% 30%)', background: 'hsl(38 92% 94%)' }">
                                    <svg v-if="isPositiveStatus(row.label, row.value)" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ row.value }}
                                </span>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>

        <Dialog
            :show="showPasswordDialog"
            title="Update Password"
            description="Enter your current password, then choose a new one."
            @close="closePasswordDialog"
        >
            <form class="space-y-4" @submit.prevent="submitPasswordUpdate">
                <div class="space-y-1.5">
                    <Label html-for="current-password">Current password</Label>
                    <Input
                        id="current-password"
                        v-model="passwordForm.current_password"
                        type="password"
                        autocomplete="current-password"
                        :error="!!passwordForm.errors.current_password"
                    />
                    <InputError :message="passwordForm.errors.current_password" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="new-password">New password</Label>
                    <Input
                        id="new-password"
                        v-model="passwordForm.password"
                        type="password"
                        autocomplete="new-password"
                        :error="!!passwordForm.errors.password"
                    />
                    <InputError :message="passwordForm.errors.password" />
                </div>

                <div class="space-y-1.5">
                    <Label html-for="confirm-password">Confirm new password</Label>
                    <Input
                        id="confirm-password"
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        :error="!!passwordForm.errors.password_confirmation"
                    />
                    <InputError :message="passwordForm.errors.password_confirmation" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" @click="closePasswordDialog">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="passwordForm.processing">
                        {{ passwordForm.processing ? 'Updating...' : 'Update password' }}
                    </Button>
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
