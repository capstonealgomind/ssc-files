<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    voter: { type: Object, required: true },
});

const { error: toastError } = useToast();

// ── Risk helpers ────────────────────────────────────────────────────────────
const risk = computed(() => {
    const s = props.voter.fraud_score ?? 0;
    if (s >= 80) return { label: 'LOW RISK',      color: 'hsl(142 71% 35%)', bg: 'hsl(142 76% 94%)' };
    if (s >= 50) return { label: 'MODERATE RISK', color: 'hsl(38 62% 30%)',  bg: 'hsl(38 92% 94%)' };
    if (s >= 20) return { label: 'HIGH RISK',     color: 'hsl(25 75% 30%)',  bg: 'hsl(25 95% 94%)' };
    return              { label: 'CRITICAL RISK', color: 'hsl(0 62% 35%)',   bg: 'hsl(0 84% 94%)' };
});

const initials = computed(() =>
    (props.voter.name ?? '?').split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase(),
);

// ── Score rows ──────────────────────────────────────────────────────────────
const scoreItems = computed(() => {
    const v = props.voter;
    const rows = [];
    const add = (label, points, active) => rows.push({ label, points, active });

    add('OCR name matches typed name', +20, v.ocr_name_match);
    add('Student ID matches OCR',      +20, v.ocr_student_id_match);
    add('Course matches OCR',          +10, v.ocr_course_match);
    add('Email verified',              +20, v.email_verified);
    add('Email matches name',          +20, v.email_name_match);
    add('Good image quality',          +10, v.image_quality === 'good');

    if (v.image_quality === 'blurry') add('Blurry ID image',               -20, true);
    if (!v.email_verified)            add('Email not verified',               0, false);
    if (!v.ocr_available)             add('OCR unavailable — no extraction',  0, false);
    if (v.image_quality === 'warn')   add('Slightly soft image (no penalty)', 0, false);

    return rows;
});

// ── Actions ─────────────────────────────────────────────────────────────────
const showVerifyDialog = ref(false);
const showRejectDialog = ref(false);
const showDeleteDialog = ref(false);
const deleteConfirmText = ref('');

const verifyForm = useForm({});
const rejectForm = useForm({});
const deleteForm = useForm({ confirmation: '' });
const canConfirmDelete = computed(() => deleteConfirmText.value === 'DELETE');

function confirmVerify() {
    verifyForm.post(`/voters/${props.voter.id}/verify`, {
        preserveScroll: true,
        onSuccess() {
            showVerifyDialog.value = false;
        },
        onError() { toastError('Action failed', 'Could not approve voter.'); },
    });
}
function confirmReject() {
    rejectForm.post(`/voters/${props.voter.id}/reject`, {
        preserveScroll: true,
        onSuccess() {
            showRejectDialog.value = false;
        },
        onError() { toastError('Action failed', 'Could not remove verification.'); },
    });
}

function openDeleteDialog() {
    deleteConfirmText.value = '';
    deleteForm.reset();
    deleteForm.clearErrors();
    showDeleteDialog.value = true;
}

function closeDeleteDialog() {
    showDeleteDialog.value = false;
    deleteConfirmText.value = '';
    deleteForm.reset();
    deleteForm.clearErrors();
}

function confirmDelete() {
    if (!canConfirmDelete.value || deleteForm.processing) {
        return;
    }

    deleteForm.confirmation = 'DELETE';
    deleteForm.delete(`/voters/${props.voter.id}`, {
        onSuccess: () => closeDeleteDialog(),
        onError: () => {
            toastError(
                'Delete failed',
                deleteForm.errors.confirmation
                    || Object.values(deleteForm.errors)[0]
                    || 'Unable to delete this voter. Please try again.',
            );
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="`Voter — ${voter.name}`" />
        <template #header>
            <div class="flex items-center gap-2 text-sm">
                <button type="button" class="flex items-center gap-1 transition-colors"
                    style="color:hsl(240 3.8% 46.1%);"
                    @mouseenter="$event.target.style.color='hsl(240 10% 3.9%)'"
                    @mouseleave="$event.target.style.color='hsl(240 3.8% 46.1%)'"
                    @click="router.visit('/voters')">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Voters
                </button>
                <span style="color:hsl(240 5.9% 82%);">/</span>
                <span class="font-medium" style="color:hsl(240 10% 3.9%);">{{ voter.name }}</span>
            </div>
        </template>

        <div class="space-y-4">

            <!-- ── Header card ───────────────────────────────────────────── -->
            <div class="rounded-lg border" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-4">

                    <!-- Avatar + info -->
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="h-10 w-10 rounded-full overflow-hidden flex items-center justify-center text-sm font-bold shrink-0"
                            style="background:hsl(240 5.9% 10%); color:#fff;">
                            <img
                                v-if="voter.profile_photo_url"
                                :src="voter.profile_photo_url"
                                :alt="voter.name"
                                class="h-full w-full object-cover"
                            />
                            <template v-else>{{ initials }}</template>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-base" style="color:hsl(240 10% 3.9%);">{{ voter.name }}</span>
                                <!-- Fraud score badge -->
                                <span class="text-xs font-bold px-2 py-0.5 rounded"
                                    :style="{ background: risk.bg, color: risk.color }">
                                    {{ voter.fraud_score ?? 0 }} · {{ risk.label }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5 text-xs" style="color:hsl(240 3.8% 46.1%);">
                                <span>{{ voter.email }}</span>
                                <span v-if="voter.voter_id_number" class="font-mono">{{ voter.voter_id_number }}</span>
                                <span>Registered {{ voter.registered_at }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5 mt-1.5">
                                <span v-if="voter.email_verified" class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded"
                                    style="background:hsl(142 76% 94%); color:hsl(142 71% 29%);">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Email verified
                                </span>
                                <span v-else class="text-xs px-1.5 py-0.5 rounded"
                                    style="background:hsl(38 92% 94%); color:hsl(38 62% 30%);">
                                    Email unverified
                                </span>
                                <span v-if="voter.is_verified" class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded"
                                    style="background:hsl(221 83% 94%); color:hsl(221 83% 35%);">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Approved voter
                                </span>
                                <span v-else class="text-xs px-1.5 py-0.5 rounded"
                                    style="background:hsl(240 4.8% 95.9%); color:hsl(240 3.8% 46.1%);">
                                    Pending approval
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 shrink-0">
                        <Button v-if="!voter.is_verified" size="sm" @click="showVerifyDialog = true">
                            Approve Voter
                        </Button>
                        <Button v-else size="sm" variant="outline" @click="showRejectDialog = true">
                            Remove Approval
                        </Button>
                        <Button size="sm" variant="destructive" @click="openDeleteDialog">
                            Delete
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ── Main grid ─────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                <!-- Left: ID image -->
                <div class="rounded-lg border overflow-hidden" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                    <div class="px-4 py-3 border-b text-xs font-semibold uppercase tracking-wide" style="border-color:hsl(240 5.9% 90%); color:hsl(240 3.8% 46.1%);">
                        School ID
                    </div>
                    <div class="p-3">
                        <div v-if="voter.id_image_url" class="rounded overflow-hidden">
                            <img :src="voter.id_image_url" alt="School ID" class="w-full h-auto object-contain" style="background:#000; max-height:300px;" />
                        </div>
                        <div v-else class="rounded flex flex-col items-center justify-center py-10 gap-2"
                            style="background:hsl(240 4.8% 95.9%); border:1px dashed hsl(240 5.9% 82%);">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:hsl(240 3.8% 46.1%)">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-xs" style="color:hsl(240 3.8% 46.1%);">No image uploaded</p>
                        </div>
                    </div>

                    <!-- Registered details -->
                    <div class="border-t" style="border-color:hsl(240 5.9% 90%);">
                        <div class="px-4 py-3 border-b text-xs font-semibold uppercase tracking-wide" style="border-color:hsl(240 5.9% 90%); color:hsl(240 3.8% 46.1%);">
                            Registered Details
                        </div>
                        <div class="divide-y text-xs" style="divide-color:hsl(240 5.9% 95%);">
                            <div v-for="row in [
                                { label: 'Department', value: voter.department },
                                { label: 'Course',     value: voter.course },
                                { label: 'Year Level', value: voter.year_level },
                                { label: 'Student ID', value: voter.student_id_number, mono: true },
                            ]" :key="row.label" class="flex items-center gap-2 px-4 py-2">
                                <span class="w-20 shrink-0" style="color:hsl(240 3.8% 46.1%);">{{ row.label }}</span>
                                <span :class="row.mono ? 'font-mono' : ''" style="color:hsl(240 10% 3.9%);">{{ row.value || '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle: OCR vs Typed -->
                <div class="rounded-lg border overflow-hidden" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                    <div class="px-4 py-3 border-b text-xs font-semibold uppercase tracking-wide" style="border-color:hsl(240 5.9% 90%); color:hsl(240 3.8% 46.1%);">
                        OCR vs Typed Data
                    </div>
                    <div class="divide-y text-xs" style="divide-color:hsl(240 5.9% 95%);">

                        <!-- Name -->
                        <div class="px-4 py-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-medium" style="color:hsl(240 10% 3.9%);">Full Name</span>
                                <MatchBadge :match="voter.ocr_name_match" :available="!!voter.ocr_name" />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="rounded p-2 space-y-0.5" style="background:hsl(240 4.8% 95.9%);">
                                    <p style="color:hsl(240 3.8% 46.1%);">OCR</p>
                                    <p style="color:hsl(240 10% 3.9%);">{{ voter.ocr_name || '—' }}</p>
                                </div>
                                <div class="rounded p-2 space-y-0.5" style="background:hsl(240 4.8% 95.9%);">
                                    <p style="color:hsl(240 3.8% 46.1%);">Typed</p>
                                    <p style="color:hsl(240 10% 3.9%);">{{ voter.name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Student ID -->
                        <div class="px-4 py-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-medium" style="color:hsl(240 10% 3.9%);">Student ID</span>
                                <MatchBadge :match="voter.ocr_student_id_match" :available="!!voter.ocr_student_id" />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="rounded p-2 space-y-0.5" style="background:hsl(240 4.8% 95.9%);">
                                    <p style="color:hsl(240 3.8% 46.1%);">OCR</p>
                                    <p class="font-mono" style="color:hsl(240 10% 3.9%);">{{ voter.ocr_student_id || '—' }}</p>
                                </div>
                                <div class="rounded p-2 space-y-0.5" style="background:hsl(240 4.8% 95.9%);">
                                    <p style="color:hsl(240 3.8% 46.1%);">Typed</p>
                                    <p class="font-mono" style="color:hsl(240 10% 3.9%);">{{ voter.student_id_number }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Course -->
                        <div class="px-4 py-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-medium" style="color:hsl(240 10% 3.9%);">Course</span>
                                <MatchBadge :match="voter.ocr_course_match" :available="!!voter.ocr_course" />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="rounded p-2 space-y-0.5" style="background:hsl(240 4.8% 95.9%);">
                                    <p style="color:hsl(240 3.8% 46.1%);">OCR</p>
                                    <p style="color:hsl(240 10% 3.9%);">{{ voter.ocr_course || '—' }}</p>
                                </div>
                                <div class="rounded p-2 space-y-0.5" style="background:hsl(240 4.8% 95.9%);">
                                    <p style="color:hsl(240 3.8% 46.1%);">Typed</p>
                                    <p style="color:hsl(240 10% 3.9%);">{{ voter.course || '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Score breakdown -->
                <div class="rounded-lg border overflow-hidden" style="border-color:hsl(240 5.9% 90%); background:#fff;">
                    <div class="px-4 py-3 border-b flex items-center justify-between" style="border-color:hsl(240 5.9% 90%);">
                        <span class="text-xs font-semibold uppercase tracking-wide" style="color:hsl(240 3.8% 46.1%);">Score Breakdown</span>
                        <span class="text-sm font-bold" :style="{ color: risk.color }">{{ voter.fraud_score ?? 0 }} / 100</span>
                    </div>
                    <div class="divide-y text-xs" style="divide-color:hsl(240 5.9% 95%);">
                        <div v-for="item in scoreItems" :key="item.label"
                            class="flex items-center justify-between px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full shrink-0"
                                    :style="{
                                        backgroundColor: item.active && item.points < 0
                                            ? 'hsl(0 84% 60%)'
                                            : item.active
                                            ? 'hsl(142 71% 45%)'
                                            : 'hsl(240 5.9% 82%)'
                                    }"></span>
                                <span :style="{ color: item.active ? 'hsl(240 10% 3.9%)' : 'hsl(240 3.8% 65%)' }">
                                    {{ item.label }}
                                </span>
                            </div>
                            <span class="font-semibold tabular-nums"
                                :style="{
                                    color: item.active && item.points > 0
                                        ? 'hsl(142 71% 35%)'
                                        : item.active && item.points < 0
                                        ? 'hsl(0 62% 40%)'
                                        : 'hsl(240 3.8% 65%)'
                                }">
                                {{ item.active && item.points !== 0
                                    ? (item.points > 0 ? '+' : '') + item.points
                                    : item.points !== 0 ? '—' : '' }}
                            </span>
                        </div>
                        <!-- Total row -->
                        <div class="flex items-center justify-between px-4 py-2.5" style="background:hsl(240 4.8% 98%);">
                            <span class="font-semibold text-xs" style="color:hsl(240 10% 3.9%);">Total</span>
                            <span class="font-bold text-sm" :style="{ color: risk.color }">{{ voter.fraud_score ?? 0 }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Verify dialog ──────────────────────────────────────────────── -->
        <Dialog :show="showVerifyDialog" @close="showVerifyDialog = false" title="Approve Voter">
            <p class="text-sm" style="color:hsl(240 3.8% 46.1%);">
                Approve <strong style="color:hsl(240 10% 3.9%);">{{ voter.name }}</strong> as a verified voter?
                They will be able to cast their vote in active elections.
            </p>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <Button variant="outline" @click="showVerifyDialog = false">Cancel</Button>
                    <Button :disabled="verifyForm.processing" @click="confirmVerify">
                        <svg v-if="verifyForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Approve Voter
                    </Button>
                </div>
            </template>
        </Dialog>

        <!-- ── Reject dialog ──────────────────────────────────────────────── -->
        <Dialog :show="showRejectDialog" @close="showRejectDialog = false" title="Remove Approval">
            <p class="text-sm" style="color:hsl(240 3.8% 46.1%);">
                Remove approval for <strong style="color:hsl(240 10% 3.9%);">{{ voter.name }}</strong>?
                They will not be able to vote until re-approved.
            </p>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <Button variant="outline" @click="showRejectDialog = false">Cancel</Button>
                    <Button variant="destructive" :disabled="rejectForm.processing" @click="confirmReject">Remove Approval</Button>
                </div>
            </template>
        </Dialog>

        <!-- ── Delete dialog ──────────────────────────────────────────────── -->
        <Dialog
            :show="showDeleteDialog"
            title="Delete voter"
            description="This permanently removes the voter account and cannot be undone."
            :persistent="deleteForm.processing"
            @close="closeDeleteDialog"
        >
            <div class="space-y-4">
                <p class="text-sm" style="color:hsl(240 3.8% 46.1%);">
                    You are about to permanently delete
                    <strong style="color:hsl(240 10% 3.9%);">{{ voter.name }}</strong>.
                    Their votes, ballot receipts, and related records will also be removed.
                </p>

                <div class="space-y-2">
                    <label class="block text-sm font-medium" style="color:hsl(240 10% 3.9%);" for="voter-detail-delete-confirm">
                        Type <span class="font-mono font-bold">DELETE</span> to confirm
                    </label>
                    <input
                        id="voter-detail-delete-confirm"
                        v-model="deleteConfirmText"
                        type="text"
                        autocomplete="off"
                        placeholder="DELETE"
                        class="w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                        style="border-color:hsl(240 5.9% 90%);"
                        :disabled="deleteForm.processing"
                        @keydown.enter.prevent="confirmDelete"
                    />
                    <p v-if="deleteForm.errors.confirmation" class="text-xs" style="color:hsl(0 72% 40%);">
                        {{ deleteForm.errors.confirmation }}
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <Button variant="outline" :disabled="deleteForm.processing" @click="closeDeleteDialog">Cancel</Button>
                    <Button
                        variant="destructive"
                        :disabled="!canConfirmDelete || deleteForm.processing"
                        @click="confirmDelete"
                    >
                        {{ deleteForm.processing ? 'Deleting…' : 'Delete voter' }}
                    </Button>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<!-- ── MatchBadge ─────────────────────────────────────────────────────────── -->
<script>
const MatchBadge = {
    props: { match: Boolean, available: Boolean },
    template: `
        <span v-if="!available" class="text-xs px-1.5 py-0.5 rounded"
            style="background:hsl(240 4.8% 95.9%); color:hsl(240 3.8% 46.1%);">
            No OCR
        </span>
        <span v-else-if="match" class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded"
            style="background:hsl(142 76% 94%); color:hsl(142 71% 29%);">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            Match
        </span>
        <span v-else class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded"
            style="background:hsl(0 84.2% 94%); color:hsl(0 62.8% 40%);">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            Mismatch
        </span>
    `,
};
export default { components: { MatchBadge } };
</script>
