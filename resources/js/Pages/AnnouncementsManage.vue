<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Sheet from '@/Components/ui/Sheet.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/ui/InputError.vue';
import { useToast } from '@/composables/useToast';

defineProps({
    announcements: {
        type: Array,
        default: () => [],
    },
});

const { error: toastError } = useToast();

const showCreateSheet = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const editingAnnouncement = ref(null);
const deletingAnnouncement = ref(null);
const createImagePreviews = ref([]);
const editImagePreviews = ref([]);
const isCreateDragging = ref(false);
const isEditDragging = ref(false);

const defaultFormState = {
    title: '',
    body: '',
    links: [],
    images: [],
    remove_image_paths: [],
};

const createForm = useForm({ ...defaultFormState });
const editForm = useForm({ ...defaultFormState });

function handleError(form) {
    toastError(
        'Action failed',
        Object.values(form.errors)[0] || 'Please check the form and try again.',
    );
}

function preparePayload(data) {
    const payload = {
        ...data,
        links: (data.links || []).filter((link) => link.label?.trim() && link.url?.trim()),
    };

    if (!(payload.images || []).some((file) => file instanceof File)) {
        delete payload.images;
    }

    return payload;
}

function hasUploadFiles(form) {
    return (form.images || []).some((file) => file instanceof File);
}

function revokePreviews(previews) {
    previews.forEach((preview) => URL.revokeObjectURL(preview.url));
}

function resetCreatePreviews() {
    revokePreviews(createImagePreviews.value);
    createImagePreviews.value = [];
}

function resetEditPreviews() {
    revokePreviews(editImagePreviews.value);
    editImagePreviews.value = [];
}

function addLink(form) {
    form.links.push({ label: '', url: '' });
}

function removeLink(form, index) {
    form.links.splice(index, 1);
}

function getPreviewList(previews) {
    return previews && typeof previews === 'object' && 'value' in previews
        ? previews.value
        : previews;
}

function addImages(form, previews, files) {
    const images = Array.from(files || []).filter((file) => {
        if (file.type?.startsWith('image/')) return true;
        return /\.(jpe?g|png|webp|gif)$/i.test(file.name || '');
    });
    if (!images.length) return;

    form.images = [...(form.images || []), ...images];

    const previewList = getPreviewList(previews);
    previewList.push(
        ...images.map((file) => ({
            name: file.name,
            url: URL.createObjectURL(file),
        })),
    );
}

function handleCreateImageSelection(event) {
    addImages(createForm, createImagePreviews, event.target.files);
    event.target.value = '';
}

function handleEditImageSelection(event) {
    addImages(editForm, editImagePreviews, event.target.files);
    event.target.value = '';
}

function handleCreateImageDrop(event) {
    isCreateDragging.value = false;
    addImages(createForm, createImagePreviews, event.dataTransfer?.files);
}

function handleEditImageDrop(event) {
    isEditDragging.value = false;
    addImages(editForm, editImagePreviews, event.dataTransfer?.files);
}

function removeNewImage(form, previews, index) {
    const [removed] = form.images.splice(index, 1);
    const previewList = getPreviewList(previews);
    const previewIndex = previewList.findIndex((preview) => preview.name === removed?.name);
    if (previewIndex >= 0) {
        URL.revokeObjectURL(previewList[previewIndex].url);
        previewList.splice(previewIndex, 1);
    }
}

function removeCreateImage(index) {
    removeNewImage(createForm, createImagePreviews, index);
}

function removeEditImage(index) {
    removeNewImage(editForm, editImagePreviews, index);
}

function toggleExistingImageRemoval(form, path) {
    const current = form.remove_image_paths || [];
    if (current.includes(path)) {
        form.remove_image_paths = current.filter((item) => item !== path);
        return;
    }

    form.remove_image_paths = [...current, path];
}

function isExistingImageMarkedForRemoval(form, path) {
    return (form.remove_image_paths || []).includes(path);
}

function fillForm(form, announcement) {
    form.defaults({
        title: announcement.title ?? '',
        body: announcement.body ?? '',
        links: announcement.links?.length
            ? announcement.links.map((link) => ({ ...link }))
            : [],
        images: [],
        remove_image_paths: [],
    });
    form.reset();
    form.clearErrors();
}

function openCreateSheet() {
    createForm.reset();
    createForm.links = [];
    createForm.images = [];
    createForm.clearErrors();
    resetCreatePreviews();
    showCreateSheet.value = true;
}

function closeCreateSheet() {
    showCreateSheet.value = false;
    createForm.reset();
    createForm.clearErrors();
    resetCreatePreviews();
    isCreateDragging.value = false;
}

function openEditDialog(announcement) {
    editingAnnouncement.value = announcement;
    resetEditPreviews();
    fillForm(editForm, announcement);
    showEditDialog.value = true;
}

function closeEditDialog() {
    showEditDialog.value = false;
    editingAnnouncement.value = null;
    editForm.reset();
    editForm.clearErrors();
    resetEditPreviews();
    isEditDragging.value = false;
}

function openDeleteDialog(announcement) {
    deletingAnnouncement.value = announcement;
    showDeleteDialog.value = true;
}

function closeDeleteDialog() {
    showDeleteDialog.value = false;
    deletingAnnouncement.value = null;
}

function submitCreate() {
    const form = createForm.transform(preparePayload);
    const options = {
        preserveScroll: true,
        onSuccess: () => closeCreateSheet(),
        onError: () => handleError(createForm),
    };

    if (hasUploadFiles(createForm)) {
        form.post('/announcements', { ...options, forceFormData: true });
        return;
    }

    form.post('/announcements', options);
}

function submitEdit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeEditDialog(),
        onError: () => handleError(editForm),
    };

    if (hasUploadFiles(editForm)) {
        editForm
            .transform((data) => ({
                ...preparePayload(data),
                _method: 'put',
            }))
            .post(`/announcements/${editingAnnouncement.value.id}`, {
                ...options,
                forceFormData: true,
            });
        return;
    }

    editForm
        .transform(preparePayload)
        .put(`/announcements/${editingAnnouncement.value.id}`, options);
}

function confirmDelete() {
    if (!deletingAnnouncement.value) return;

    router.delete(`/announcements/${deletingAnnouncement.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeDeleteDialog(),
        onError: () => {
            toastError('Delete failed', 'Unable to remove this announcement. Please try again.');
            closeDeleteDialog();
        },
    });
}

const editingExistingImages = computed(() => {
    if (!editingAnnouncement.value) return [];

    return (editingAnnouncement.value.image_paths || []).map((path, index) => ({
        path,
        url: editingAnnouncement.value.images?.[index] || null,
    })).filter((item) => item.url);
});
</script>

<template>
    <AppLayout>
        <Head title="Announcements" />

        <template #header>
            <div class="flex items-center justify-between gap-3 min-w-0">
                <h1 class="text-base font-semibold truncate" style="color:hsl(240 10% 3.9%);">Announcements</h1>
                <Button variant="navy" class="shrink-0" @click="openCreateSheet">
                    New announcement
                </Button>
            </div>
        </template>

        <div v-if="announcements.length === 0"
            class="rounded-xl border bg-white p-8 text-center"
            style="border-color:hsl(240 5.9% 90%);">
            <p class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">No announcements yet</p>
            <p class="text-xs mt-1 mb-4" style="color:hsl(240 3.8% 46.1%);">Create your first announcement for voters.</p>
            <Button variant="navy" @click="openCreateSheet">Create announcement</Button>
        </div>

        <div v-else class="space-y-3">
            <article
                v-for="item in announcements"
                :key="item.id"
                class="rounded-xl border bg-white overflow-hidden"
                style="border-color:hsl(240 5.9% 90%);"
            >
                <div class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="min-w-0">
                            <h2 class="text-base font-bold leading-snug" style="color:hsl(240 10% 3.9%);">{{ item.title }}</h2>
                            <p class="text-xs mt-1" style="color:hsl(240 3.8% 46.1%);">
                                {{ item.created_at }}
                                <span v-if="item.author"> · {{ item.author }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                type="button"
                                class="text-xs font-semibold px-3 py-1.5 rounded-md border transition-colors hover:bg-gray-50"
                                style="border-color:hsl(240 5.9% 90%); color:hsl(240 10% 3.9%);"
                                @click="openEditDialog(item)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="text-xs font-semibold px-3 py-1.5 rounded-md border transition-colors hover:bg-red-50"
                                style="border-color:hsl(0 72% 90%); color:hsl(0 72% 40%);"
                                @click="openDeleteDialog(item)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <p class="text-sm leading-relaxed whitespace-pre-wrap" style="color:hsl(240 3.8% 46.1%);">{{ item.body }}</p>

                    <div v-if="item.links?.length" class="flex flex-wrap gap-2 mt-3">
                        <a
                            v-for="(link, index) in item.links"
                            :key="`${item.id}-link-${index}`"
                            :href="link.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-full"
                            style="background:hsl(221 83% 96%); color:hsl(221 83% 40%);"
                        >
                            {{ link.label }}
                        </a>
                    </div>

                    <div v-if="item.images?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                        <div
                            v-for="(image, index) in item.images"
                            :key="`${item.id}-image-${index}`"
                            class="rounded-lg border bg-gray-50 p-3 flex items-center justify-center"
                            style="border-color:hsl(240 5.9% 90%);"
                        >
                            <img
                                :src="image"
                                :alt="`${item.title} image ${index + 1}`"
                                class="w-full max-h-64 object-contain"
                            />
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <Sheet
            :show="showCreateSheet"
            wide
            title="New announcement"
            description="Publish an update for voters with text, links, and images."
            @close="closeCreateSheet"
        >
            <form class="flex flex-col gap-4 p-5 overflow-y-auto" @submit.prevent="submitCreate">
                <div class="space-y-2">
                    <Label for="create-title">Title</Label>
                    <Input id="create-title" v-model="createForm.title" maxlength="255" placeholder="Announcement title" />
                    <InputError :message="createForm.errors.title" />
                </div>

                <div class="space-y-2">
                    <Label for="create-body">Body</Label>
                    <textarea
                        id="create-body"
                        v-model="createForm.body"
                        rows="6"
                        maxlength="10000"
                        class="flex w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                        style="border-color:hsl(240 5.9% 90%);"
                        placeholder="Write the announcement details..."
                    />
                    <InputError :message="createForm.errors.body" />
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <Label>Links</Label>
                        <button
                            type="button"
                            class="text-xs font-semibold"
                            style="color:hsl(221 83% 40%);"
                            @click="addLink(createForm)"
                        >
                            Add link
                        </button>
                    </div>

                    <div v-if="!createForm.links.length" class="text-xs" style="color:hsl(240 3.8% 46.1%);">
                        Optional. Add buttons that link to external pages.
                    </div>

                    <div
                        v-for="(link, index) in createForm.links"
                        :key="`create-link-${index}`"
                        class="grid grid-cols-1 gap-2 rounded-lg border p-3"
                        style="border-color:hsl(240 5.9% 90%);"
                    >
                        <Input v-model="link.label" placeholder="Link label" />
                        <Input v-model="link.url" type="url" placeholder="https://example.com" />
                        <button
                            type="button"
                            class="text-xs font-semibold text-left"
                            style="color:hsl(0 72% 40%);"
                            @click="removeLink(createForm, index)"
                        >
                            Remove link
                        </button>
                    </div>
                    <InputError :message="createForm.errors.links" />
                </div>

                <div class="space-y-3">
                    <Label for="create-images">Images</Label>

                    <label
                        for="create-images"
                        class="flex flex-col items-center justify-center w-full rounded-xl border-2 border-dashed cursor-pointer transition-colors"
                        :style="{
                            borderColor: isCreateDragging ? 'hsl(221 83% 53%)' : 'hsl(240 5.9% 82%)',
                            backgroundColor: isCreateDragging ? 'hsl(221 83% 98%)' : 'hsl(240 4.8% 98%)',
                            minHeight: createImagePreviews.length ? 'auto' : '148px',
                        }"
                        @dragover.prevent="isCreateDragging = true"
                        @dragleave.prevent="isCreateDragging = false"
                        @drop.prevent="handleCreateImageDrop"
                    >
                        <div v-if="createImagePreviews.length" class="w-full p-4 space-y-3">
                            <p class="text-xs font-semibold text-center" style="color:hsl(240 10% 3.9%);">
                                Selected images ({{ createImagePreviews.length }}) · click to add more
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div
                                    v-for="(preview, index) in createImagePreviews"
                                    :key="`create-preview-${index}`"
                                    class="relative rounded-lg overflow-hidden border bg-white"
                                    style="border-color:hsl(240 5.9% 90%);"
                                >
                                    <div
                                        class="flex min-h-[120px] items-center justify-center p-2"
                                        style="background:hsl(240 4.8% 98%);"
                                    >
                                        <img :src="preview.url" :alt="preview.name" class="max-h-48 max-w-full object-contain" />
                                    </div>
                                    <button
                                        type="button"
                                        class="absolute top-2 right-2 rounded-md px-2 py-1 text-[10px] font-semibold text-white"
                                        style="background:rgba(0,0,0,0.65);"
                                        @click.prevent="removeCreateImage(index)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex flex-col items-center gap-2 py-6 px-4 text-center pointer-events-none">
                            <div
                                class="h-12 w-12 rounded-full flex items-center justify-center"
                                style="background:hsl(221 83% 94%);"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:hsl(221 83% 40%);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l3.682 3.682m0 0l1.159-1.159a2.25 2.25 0 013.182 0L21.75 12M3.75 19.5h16.5A2.25 2.25 0 0022.5 17.25V6.75A2.25 2.25 0 0020.25 4.5H3.75A2.25 2.25 0 001.5 6.75v10.5A2.25 2.25 0 003.75 19.5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">
                                    Click to upload images
                                </p>
                                <p class="text-xs mt-0.5" style="color:hsl(240 3.8% 46.1%);">
                                    or drag and drop here
                                </p>
                            </div>
                            <p class="text-[11px]" style="color:hsl(240 3.8% 46.1%);">
                                Optional · JPG, PNG, WebP, or GIF · up to 5MB each
                            </p>
                        </div>
                    </label>

                    <input
                        id="create-images"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        multiple
                        class="sr-only"
                        @change="handleCreateImageSelection"
                    />

                    <InputError :message="createForm.errors.images" />
                </div>

                <div class="flex gap-2 pt-2">
                    <Button type="button" variant="outline" class="flex-1" @click="closeCreateSheet">Cancel</Button>
                    <Button type="submit" variant="navy" class="flex-1" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Publishing...' : 'Publish' }}
                    </Button>
                </div>
            </form>
        </Sheet>

        <Dialog
            :show="showEditDialog"
            wide
            title="Edit announcement"
            description="Update the announcement content, links, or images."
            @close="closeEditDialog"
        >
            <form class="space-y-4" @submit.prevent="submitEdit">
                <div class="space-y-2">
                    <Label for="edit-title">Title</Label>
                    <Input id="edit-title" v-model="editForm.title" maxlength="255" />
                    <InputError :message="editForm.errors.title" />
                </div>

                <div class="space-y-2">
                    <Label for="edit-body">Body</Label>
                    <textarea
                        id="edit-body"
                        v-model="editForm.body"
                        rows="6"
                        maxlength="10000"
                        class="flex w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                        style="border-color:hsl(240 5.9% 90%);"
                    />
                    <InputError :message="editForm.errors.body" />
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <Label>Links</Label>
                        <button
                            type="button"
                            class="text-xs font-semibold"
                            style="color:hsl(221 83% 40%);"
                            @click="addLink(editForm)"
                        >
                            Add link
                        </button>
                    </div>

                    <div
                        v-for="(link, index) in editForm.links"
                        :key="`edit-link-${index}`"
                        class="grid grid-cols-1 gap-2 rounded-lg border p-3"
                        style="border-color:hsl(240 5.9% 90%);"
                    >
                        <Input v-model="link.label" placeholder="Link label" />
                        <Input v-model="link.url" type="url" placeholder="https://example.com" />
                        <button
                            type="button"
                            class="text-xs font-semibold text-left"
                            style="color:hsl(0 72% 40%);"
                            @click="removeLink(editForm, index)"
                        >
                            Remove link
                        </button>
                    </div>
                </div>

                <div v-if="editingExistingImages.length" class="space-y-2">
                    <Label>Current images</Label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="image in editingExistingImages"
                            :key="image.path"
                            type="button"
                            class="relative rounded-lg overflow-hidden border text-left"
                            :style="{
                                borderColor: isExistingImageMarkedForRemoval(editForm, image.path)
                                    ? 'hsl(0 72% 60%)'
                                    : 'hsl(240 5.9% 90%)',
                                opacity: isExistingImageMarkedForRemoval(editForm, image.path) ? 0.55 : 1,
                            }"
                            @click="toggleExistingImageRemoval(editForm, image.path)"
                        >
                            <div
                                class="flex min-h-[120px] items-center justify-center p-2 bg-gray-50"
                            >
                                <img :src="image.url" :alt="image.path" class="max-h-48 max-w-full object-contain" />
                            </div>
                            <span
                                class="absolute bottom-2 left-2 rounded-md px-2 py-1 text-[10px] font-semibold text-white"
                                style="background:rgba(0,0,0,0.65);"
                            >
                                {{ isExistingImageMarkedForRemoval(editForm, image.path) ? 'Will remove' : 'Remove' }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <Label for="edit-images">Add images</Label>

                    <label
                        for="edit-images"
                        class="flex flex-col items-center justify-center w-full rounded-xl border-2 border-dashed cursor-pointer transition-colors"
                        :style="{
                            borderColor: isEditDragging ? 'hsl(221 83% 53%)' : 'hsl(240 5.9% 82%)',
                            backgroundColor: isEditDragging ? 'hsl(221 83% 98%)' : 'hsl(240 4.8% 98%)',
                            minHeight: editImagePreviews.length ? 'auto' : '148px',
                        }"
                        @dragover.prevent="isEditDragging = true"
                        @dragleave.prevent="isEditDragging = false"
                        @drop.prevent="handleEditImageDrop"
                    >
                        <div v-if="editImagePreviews.length" class="w-full p-4 space-y-3">
                            <p class="text-xs font-semibold text-center" style="color:hsl(240 10% 3.9%);">
                                New images ({{ editImagePreviews.length }}) · click to add more
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div
                                    v-for="(preview, index) in editImagePreviews"
                                    :key="`edit-preview-${index}`"
                                    class="relative rounded-lg overflow-hidden border bg-white"
                                    style="border-color:hsl(240 5.9% 90%);"
                                >
                                    <div
                                        class="flex min-h-[120px] items-center justify-center p-2"
                                        style="background:hsl(240 4.8% 98%);"
                                    >
                                        <img :src="preview.url" :alt="preview.name" class="max-h-48 max-w-full object-contain" />
                                    </div>
                                    <button
                                        type="button"
                                        class="absolute top-2 right-2 rounded-md px-2 py-1 text-[10px] font-semibold text-white"
                                        style="background:rgba(0,0,0,0.65);"
                                        @click.prevent="removeEditImage(index)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex flex-col items-center gap-2 py-6 px-4 text-center pointer-events-none">
                            <div
                                class="h-12 w-12 rounded-full flex items-center justify-center"
                                style="background:hsl(221 83% 94%);"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:hsl(221 83% 40%);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l3.682 3.682m0 0l1.159-1.159a2.25 2.25 0 013.182 0L21.75 12M3.75 19.5h16.5A2.25 2.25 0 0022.5 17.25V6.75A2.25 2.25 0 0020.25 4.5H3.75A2.25 2.25 0 001.5 6.75v10.5A2.25 2.25 0 003.75 19.5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" style="color:hsl(240 10% 3.9%);">
                                    Click to upload images
                                </p>
                                <p class="text-xs mt-0.5" style="color:hsl(240 3.8% 46.1%);">
                                    or drag and drop here
                                </p>
                            </div>
                            <p class="text-[11px]" style="color:hsl(240 3.8% 46.1%);">
                                JPG, PNG, WebP, or GIF · up to 5MB each
                            </p>
                        </div>
                    </label>

                    <input
                        id="edit-images"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        multiple
                        class="sr-only"
                        @change="handleEditImageSelection"
                    />

                    <InputError :message="editForm.errors.images" />
                </div>

                <div class="flex gap-2 pt-2">
                    <Button type="button" variant="outline" class="flex-1" @click="closeEditDialog">Cancel</Button>
                    <Button type="submit" variant="navy" class="flex-1" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                </div>
            </form>
        </Dialog>

        <Dialog
            :show="showDeleteDialog"
            title="Delete announcement"
            :description="`Delete “${deletingAnnouncement?.title || 'this announcement'}”? This cannot be undone.`"
            @close="closeDeleteDialog"
        >
            <div class="flex gap-2 pt-2">
                <Button type="button" variant="outline" class="flex-1" @click="closeDeleteDialog">Cancel</Button>
                <Button type="button" variant="navy" class="flex-1" style="background:hsl(0 72% 40%);" @click="confirmDelete">
                    Delete
                </Button>
            </div>
        </Dialog>
    </AppLayout>
</template>
