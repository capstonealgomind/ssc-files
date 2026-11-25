<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ attempts: { type: Object, required: true } });

const form = useForm({});
const deletingId = ref(null);
const reviewAttempt = ref(null);
const imageFailed = ref(false);

function openReview(attempt) {
    imageFailed.value = false;
    reviewAttempt.value = attempt;
}

function deleteAttempt(attempt) {
    if (form.processing || !window.confirm(`Permanently delete the registration attempt for ${attempt.name}?`)) {
        return;
    }

    deletingId.value = attempt.id;
    form.delete(`/voters/unlisted-registrations/${attempt.id}`, {
        preserveScroll: true,
        onFinish: () => { deletingId.value = null; },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Not on Official List" />
        <template #header><h1 class="text-base font-semibold">Not on Official List</h1></template>
        <div class="space-y-4">
            <Link
                href="/voters"
                class="inline-flex min-h-9 items-center gap-2 rounded-md border bg-white px-3 py-1.5 text-sm font-medium shadow-sm transition-colors hover:bg-muted focus-visible:outline-2 focus-visible:outline-offset-2"
            >
                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 19-7-7 7-7M5 12h14" />
                </svg>
                Back to Voters
            </Link>
            <p class="text-sm text-muted-foreground">
                This page lists registration attempts with an ID missing from the official list or academic details that do not match the official record.
                Select <strong class="font-semibold text-foreground">Review</strong> to view the uploaded school ID, if available.
            </p>
            <div class="admin-table-scroll rounded-xl border bg-white">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b bg-muted">
                        <th class="p-3">Student ID</th><th class="p-3">Name</th>
                        <th class="p-3">Verified Email</th><th class="p-3">Department</th><th class="p-3">Course</th><th class="p-3">Year Level</th><th class="p-3">Section</th><th class="p-3">Review Note</th><th class="p-3">Submitted</th>
                        <th scope="col" class="p-3">Actions</th>
                    </tr></thead>
                    <tbody>
                        <tr v-for="attempt in attempts.data" :key="attempt.id" class="border-b">
                            <td class="p-3">{{ attempt.student_id }}</td><td class="p-3">{{ attempt.name }}</td>
                            <td class="p-3">{{ attempt.email }}</td><td class="p-3">{{ attempt.department ?? '—' }}</td>
                            <td class="p-3">{{ attempt.course ?? '—' }}</td>
                            <td class="p-3">{{ attempt.year_level ?? '—' }}</td>
                            <td class="p-3">{{ attempt.section ?? '—' }}</td>
                            <td class="p-3">{{ attempt.reason ?? 'Student ID not found on official list' }}</td>
                            <td class="p-3">{{ attempt.submitted_at }}</td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <Button type="button" variant="outline" size="sm" @click="openReview(attempt)">Review</Button>
                                    <Button type="button" variant="destructive" size="sm" :disabled="form.processing" @click="deleteAttempt(attempt)">
                                        {{ deletingId === attempt.id ? 'Deleting…' : 'Delete' }}
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!attempts.total"><td colspan="10" class="p-4 text-center">No unlisted registrations yet.</td></tr>
                    </tbody>
                </table>
            </div>
            <nav class="flex gap-4 text-sm" aria-label="Registration pages">
                <span>{{ attempts.total }} attempts</span>
                <Link v-if="attempts.prev_page_url" :href="attempts.prev_page_url" class="underline">Previous</Link>
                <Link v-if="attempts.next_page_url" :href="attempts.next_page_url" class="underline">Next</Link>
            </nav>
        </div>
        <Dialog :show="!!reviewAttempt" title="Review student ID" :description="reviewAttempt?.name" size="xl" @close="reviewAttempt = null">
            <img v-if="reviewAttempt?.has_id_image && !imageFailed"
                :key="reviewAttempt.id"
                :src="`/voters/unlisted-registrations/${reviewAttempt.id}/id-image`"
                alt="Uploaded student ID"
                class="mx-auto max-h-[65vh] max-w-full rounded-lg object-contain"
                @error="imageFailed = true" />
            <p v-else class="rounded-lg bg-muted p-4 text-sm" role="status">
                {{ imageFailed ? 'The uploaded ID could not be loaded.' : 'No ID image was saved for this attempt.' }}
            </p>
            <template #footer>
                <Button variant="outline" @click="reviewAttempt = null">Close</Button>
            </template>
        </Dialog>
    </AppLayout>
</template>
