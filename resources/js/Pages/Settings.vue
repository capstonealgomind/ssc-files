<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/ui/Button.vue";
import Dialog from "@/Components/ui/Dialog.vue";
import Sheet from "@/Components/ui/Sheet.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";
import Select from "@/Components/ui/Select.vue";
import Switch from "@/Components/ui/Switch.vue";
import LocationRangeMap from "@/Components/LocationRangeMap.vue";
import InputError from "@/Components/ui/InputError.vue";
import { useToast } from "@/composables/useToast";

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
    positions: {
        type: Array,
        default: () => [],
    },
    partylists: {
        type: Array,
        default: () => [],
    },
    departmentColors: {
        type: Object,
        default: () => ({}),
    },
    locationRange: {
        type: Object,
        default: () => ({
            is_enabled: false,
            latitude: null,
            longitude: null,
            range_meters: null,
        }),
    },
    dtsRegistration: {
        type: Object,
        default: () => ({
            is_enabled: false,
            starts_at: "",
            ends_at: "",
            status: "unrestricted",
            status_label: "Not scheduled",
        }),
    },
    uaManagement: {
        type: Object,
        default: () => ({
            is_enabled: true,
            idle_seconds: 60,
            countdown_seconds: 10,
            sound_enabled: true,
        }),
    },
    schoolYear: {
        type: Object,
        default: () => ({
            start_year: new Date().getFullYear(),
            end_year: new Date().getFullYear() + 1,
            label: "",
        }),
    },
    sscMembers: {
        type: Array,
        default: () => [],
    },
    galleryImages: {
        type: Array,
        default: () => [],
    },
    gallerySetting: {
        type: Object,
        default: () => ({
            style: "dome",
        }),
    },
    initialAdvancedTab: {
        type: String,
        default: null,
    },
});

const { error: toastError, success: toastSuccess } = useToast();

const DEFAULT_DEPARTMENT_COLOR = "Blue";

const tabs = [
    { id: "departments", label: "Departments" },
    { id: "courses", label: "Courses" },
    { id: "yearLevels", label: "Year levels" },
    { id: "positions", label: "Positions" },
    { id: "partylists", label: "Partylists" },
];

const advancedTabs = [
    { id: "rangeLimit", label: "Range limit" },
    { id: "dtsRegistration", label: "D&TS Registration" },
    { id: "schoolYear", label: "School year" },
    { id: "uaManagement", label: "UA Management" },
    { id: "sscMembers", label: "SSC members" },
    { id: "gallery", label: "Gallery" },
];

const settingsView = ref("academic");
const activeTab = ref("departments");
const activeAdvancedTab = ref("rangeLimit");
const advancedDropdownOpen = ref(false);
const advancedDropdownRef = ref(null);
const detectingLocation = ref(false);
const sscMembersInput = ref(null);
const pendingSscMemberPreviews = ref([]);
const galleryInput = ref(null);
const pendingGalleryPreviews = ref([]);

const rangeForm = useForm({
    is_enabled: props.locationRange.is_enabled ?? false,
    latitude: props.locationRange.latitude ?? "",
    longitude: props.locationRange.longitude ?? "",
    range_meters: props.locationRange.range_meters ?? "",
});

const dtsRegistrationForm = useForm({
    is_enabled: props.dtsRegistration.is_enabled ?? false,
    starts_at: props.dtsRegistration.starts_at ?? "",
    ends_at: props.dtsRegistration.ends_at ?? "",
});

const uaManagementForm = useForm({
    is_enabled: props.uaManagement.is_enabled ?? true,
    idle_seconds: props.uaManagement.idle_seconds ?? 60,
    countdown_seconds: props.uaManagement.countdown_seconds ?? 10,
    sound_enabled: props.uaManagement.sound_enabled ?? true,
});

const schoolYearForm = useForm({
    start_year: String(props.schoolYear.start_year ?? new Date().getFullYear()),
    end_year: String(props.schoolYear.end_year ?? new Date().getFullYear() + 1),
});

watch(
    () => props.locationRange,
    (value) => {
        rangeForm.is_enabled = value.is_enabled ?? false;
        rangeForm.latitude = value.latitude ?? "";
        rangeForm.longitude = value.longitude ?? "";
        rangeForm.range_meters = value.range_meters ?? "";
    },
    { deep: true },
);

watch(
    () => props.dtsRegistration,
    (value) => {
        dtsRegistrationForm.is_enabled = value.is_enabled ?? false;
        dtsRegistrationForm.starts_at = value.starts_at ?? "";
        dtsRegistrationForm.ends_at = value.ends_at ?? "";
    },
    { deep: true },
);

watch(
    () => props.uaManagement,
    (value) => {
        uaManagementForm.is_enabled = value.is_enabled ?? true;
        uaManagementForm.idle_seconds = value.idle_seconds ?? 60;
        uaManagementForm.countdown_seconds = value.countdown_seconds ?? 10;
        uaManagementForm.sound_enabled = value.sound_enabled ?? true;
    },
    { deep: true },
);

watch(
    () => props.schoolYear,
    (value) => {
        schoolYearForm.start_year = String(value.start_year ?? new Date().getFullYear());
        schoolYearForm.end_year = String(value.end_year ?? new Date().getFullYear() + 1);
    },
    { deep: true },
);

const showCreateSheet = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const showDeleteAllSscDialog = ref(false);
const deletingAllSscMembers = ref(false);
const showDeleteAllGalleryDialog = ref(false);
const deletingAllGalleryImages = ref(false);
const editingItem = ref(null);
const deletingItem = ref(null);

const departmentForm = useForm({
    name: "",
    acronym: "",
    color: DEFAULT_DEPARTMENT_COLOR,
});
const courseForm = useForm({
    name: "",
    department_id: "",
    duration_years: "4",
});
const yearLevelForm = useForm({ name: "", sort_order: "" });
const positionForm = useForm({ name: "", sort_order: "" });
const partylistForm = useForm({ name: "", acronym: "", description: "" });
const sscMembersForm = useForm({
    images: [],
});
const galleryForm = useForm({
    images: [],
});
const galleryStyleForm = useForm({
    style: props.gallerySetting.style ?? "dome",
});

watch(
    () => props.gallerySetting,
    (value) => {
        galleryStyleForm.style = value?.style ?? "dome";
    },
    { deep: true },
);

const advancedSettingsDescription = computed(() => {
    if (activeAdvancedTab.value === "sscMembers") {
        return "Upload and manage SSC member images.";
    }

    if (activeAdvancedTab.value === "gallery") {
        return "Upload gallery images and choose Dome or Circular display style.";
    }

    if (activeAdvancedTab.value === "dtsRegistration") {
        return "Schedule when voter registration opens and closes.";
    }

    if (activeAdvancedTab.value === "schoolYear") {
        return "Set the current school year used for voter account expiry.";
    }

    if (activeAdvancedTab.value === "uaManagement") {
        return "Configure voter inactivity prompts and auto-logout timing.";
    }

    return "Configure location-based access restrictions for the voting site.";
});

const dtsRegistrationStatusLabel = computed(() => props.dtsRegistration.status_label ?? "Not scheduled");

const departmentOptions = computed(() =>
    props.departments.map((item) => ({
        value: String(item.id),
        label: item.acronym ? `${item.acronym} — ${item.name}` : item.name,
    })),
);

const departmentColorOptions = computed(() =>
    Object.keys(props.departmentColors).map((name) => ({
        value: name,
        label: name,
    })),
);

const courseDurationOptions = [
    { value: "1", label: "1 year" },
    { value: "2", label: "2 years" },
    { value: "3", label: "3 years" },
    { value: "4", label: "4 years" },
    { value: "5", label: "5 years" },
    { value: "6", label: "6 years" },
];

function formatCourseDuration(years) {
    return `${years} ${years === 1 ? "year" : "years"}`;
}

function departmentColorHex(colorName) {
    return (
        props.departmentColors[colorName] ||
        props.departmentColors[DEFAULT_DEPARTMENT_COLOR] ||
        "#2563eb"
    );
}

const currentItems = computed(() => {
    if (activeTab.value === "departments") return props.departments;
    if (activeTab.value === "courses") return props.courses;
    if (activeTab.value === "yearLevels") return props.yearLevels;
    if (activeTab.value === "partylists") return props.partylists;
    return props.positions;
});

const currentCountLabel = computed(() => {
    const count = currentItems.value.length;
    const label =
        activeTab.value === "departments"
            ? "department"
            : activeTab.value === "courses"
              ? "course"
              : activeTab.value === "yearLevels"
                ? "year level"
                : activeTab.value === "partylists"
                  ? "partylist"
                  : "position";
    return `${count} ${count === 1 ? label : `${label}s`}`;
});

function activeForm() {
    if (activeTab.value === "departments") return departmentForm;
    if (activeTab.value === "courses") return courseForm;
    if (activeTab.value === "yearLevels") return yearLevelForm;
    if (activeTab.value === "partylists") return partylistForm;
    return positionForm;
}

function resetActiveForm() {
    if (activeTab.value === "departments") {
        departmentForm.reset();
        departmentForm.color = DEFAULT_DEPARTMENT_COLOR;
        departmentForm.clearErrors();
        return;
    }

    if (activeTab.value === "courses") {
        courseForm.reset();
        courseForm.clearErrors();
        return;
    }

    if (activeTab.value === "yearLevels") {
        yearLevelForm.reset();
        yearLevelForm.clearErrors();
        return;
    }

    if (activeTab.value === "partylists") {
        partylistForm.reset();
        partylistForm.clearErrors();
        return;
    }

    positionForm.reset();
    positionForm.clearErrors();
}

function fillActiveForm(item) {
    if (activeTab.value === "departments") {
        departmentForm.name = item.name;
        departmentForm.acronym = item.acronym ?? "";
        departmentForm.color = item.color || DEFAULT_DEPARTMENT_COLOR;
        departmentForm.clearErrors();
        return;
    }

    if (activeTab.value === "courses") {
        courseForm.name = item.name;
        courseForm.department_id = String(item.department_id);
        courseForm.duration_years = String(item.duration_years ?? 4);
        courseForm.clearErrors();
        return;
    }

    if (activeTab.value === "yearLevels") {
        yearLevelForm.name = item.name;
        yearLevelForm.sort_order = String(item.sort_order ?? "");
        yearLevelForm.clearErrors();
        return;
    }

    if (activeTab.value === "partylists") {
        partylistForm.name = item.name;
        partylistForm.acronym = item.acronym ?? "";
        partylistForm.description = item.description ?? "";
        partylistForm.clearErrors();
        return;
    }

    positionForm.name = item.name;
    positionForm.sort_order = String(item.sort_order ?? "");
    positionForm.clearErrors();
}

function createEndpoint() {
    if (activeTab.value === "departments") return "/settings/departments";
    if (activeTab.value === "courses") return "/settings/courses";
    if (activeTab.value === "yearLevels") return "/settings/year-levels";
    if (activeTab.value === "partylists") return "/settings/partylists";
    return "/settings/positions";
}

function updateEndpoint(item) {
    if (activeTab.value === "departments")
        return `/settings/departments/${item.id}`;
    if (activeTab.value === "courses") return `/settings/courses/${item.id}`;
    if (activeTab.value === "yearLevels")
        return `/settings/year-levels/${item.id}`;
    if (activeTab.value === "partylists")
        return `/settings/partylists/${item.id}`;
    return `/settings/positions/${item.id}`;
}

function deleteEndpoint(item) {
    return updateEndpoint(item);
}

function createTitle() {
    if (activeTab.value === "departments") return "Add department";
    if (activeTab.value === "courses") return "Add course";
    if (activeTab.value === "yearLevels") return "Add year level";
    if (activeTab.value === "partylists") return "Add partylist";
    return "Add position";
}

function editTitle() {
    if (activeTab.value === "departments") return "Edit department";
    if (activeTab.value === "courses") return "Edit course";
    if (activeTab.value === "yearLevels") return "Edit year level";
    if (activeTab.value === "partylists") return "Edit partylist";
    return "Edit position";
}

function deleteTitle() {
    if (activeTab.value === "departments") return "Delete department";
    if (activeTab.value === "courses") return "Delete course";
    if (activeTab.value === "yearLevels") return "Delete year level";
    if (activeTab.value === "partylists") return "Delete partylist";
    return "Delete position";
}

function openCreateSheet() {
    resetActiveForm();
    showCreateSheet.value = true;
}

function closeCreateSheet() {
    showCreateSheet.value = false;
    resetActiveForm();
}

function openEditDialog(item) {
    editingItem.value = item;
    fillActiveForm(item);
    showEditDialog.value = true;
}

function closeEditDialog() {
    showEditDialog.value = false;
    editingItem.value = null;
    resetActiveForm();
}

function openDeleteDialog(item) {
    deletingItem.value = item;
    showDeleteDialog.value = true;
}

function closeDeleteDialog() {
    showDeleteDialog.value = false;
    deletingItem.value = null;
}

function handleError(form) {
    toastError(
        "Action failed",
        Object.values(form.errors)[0] || "Please check the form and try again.",
    );
}

function submitCreate() {
    const form = activeForm();

    form.post(createEndpoint(), {
        preserveScroll: true,
        onSuccess: () => closeCreateSheet(),
        onError: () => handleError(form),
    });
}

function submitEdit() {
    const form = activeForm();

    form.put(updateEndpoint(editingItem.value), {
        preserveScroll: true,
        onSuccess: () => closeEditDialog(),
        onError: () => handleError(form),
    });
}

function confirmDelete() {
    if (!deletingItem.value) return;

    router.delete(deleteEndpoint(deletingItem.value), {
        preserveScroll: true,
        onSuccess: () => closeDeleteDialog(),
        onError: () => {
            toastError(
                "Delete failed",
                "Unable to remove this item. It may still be in use.",
            );
            closeDeleteDialog();
        },
    });
}

function switchTab(tabId) {
    settingsView.value = "academic";
    activeTab.value = tabId;
    closeCreateSheet();
    closeEditDialog();
    closeDeleteDialog();
}

function switchAdvancedTab(tabId) {
    settingsView.value = "advanced";
    activeAdvancedTab.value = tabId;
    advancedDropdownOpen.value = false;
    closeCreateSheet();
    closeEditDialog();
    closeDeleteDialog();
}

function toggleAdvancedDropdown() {
    advancedDropdownOpen.value = !advancedDropdownOpen.value;
}

function handleClickOutside(event) {
    if (
        advancedDropdownRef.value &&
        !advancedDropdownRef.value.contains(event.target)
    ) {
        advancedDropdownOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside);

    if (
        props.initialAdvancedTab &&
        advancedTabs.some((tab) => tab.id === props.initialAdvancedTab)
    ) {
        switchAdvancedTab(props.initialAdvancedTab);
    }
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
    revokeSscMemberPreviews();
    revokeGalleryPreviews();
});

function detectLocation() {
    if (!navigator.geolocation) {
        toastError(
            "Geolocation unavailable",
            "Your browser does not support location detection.",
        );
        return;
    }

    detectingLocation.value = true;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            rangeForm.latitude = position.coords.latitude;
            rangeForm.longitude = position.coords.longitude;
            detectingLocation.value = false;

            const accuracy = position.coords.accuracy;
            const accuracyNote = Number.isFinite(accuracy)
                ? `GPS fix captured (±${Math.round(accuracy)} m). Adjust manually below if needed.`
                : "GPS coordinates captured from your device.";

            toastSuccess("GPS location detected", accuracyNote);
        },
        (error) => {
            detectingLocation.value = false;

            let message =
                "Unable to get a GPS fix. Try again near a window or outdoors.";
            if (error.code === error.PERMISSION_DENIED) {
                message =
                    "Allow location access in your browser to detect GPS coordinates.";
            } else if (error.code === error.TIMEOUT) {
                message =
                    "GPS timed out. Move to an open area with clear sky view and try again.";
            } else if (error.code === error.POSITION_UNAVAILABLE) {
                message =
                    "GPS signal unavailable on this device. Enter coordinates manually instead.";
            }

            toastError("GPS detection failed", message);
        },
        {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 20000,
        },
    );
}

function submitRangeLimit() {
    rangeForm.put("/settings/location-range", {
        preserveScroll: true,
        preserveState: true,
        onError: () => handleError(rangeForm),
    });
}

function submitDtsRegistration() {
    dtsRegistrationForm.put("/settings/dts-registration", {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => switchAdvancedTab("dtsRegistration"),
        onError: () => handleError(dtsRegistrationForm),
    });
}

function submitSchoolYear() {
    schoolYearForm
        .transform((data) => ({
            start_year: Number(data.start_year),
            end_year: Number(data.end_year),
        }))
        .put("/settings/school-year", {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => switchAdvancedTab("schoolYear"),
            onError: () => handleError(schoolYearForm),
            onFinish: () => schoolYearForm.transform((data) => data),
        });
}

function submitUaManagement() {
    uaManagementForm
        .transform((data) => ({
            ...data,
            idle_seconds: Number(data.idle_seconds),
            countdown_seconds: Number(data.countdown_seconds),
        }))
        .put("/settings/ua-management", {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => switchAdvancedTab("uaManagement"),
            onError: () => handleError(uaManagementForm),
        });
}

function revokeSscMemberPreviews() {
    pendingSscMemberPreviews.value.forEach((preview) => {
        URL.revokeObjectURL(preview.url);
    });
    pendingSscMemberPreviews.value = [];
}

function openSscMembersPicker() {
    sscMembersInput.value?.click();
}

function onSscMembersSelected(event) {
    const files = Array.from(event.target.files || []);
    if (!files.length) {
        return;
    }

    revokeSscMemberPreviews();
    sscMembersForm.images = files;
    pendingSscMemberPreviews.value = files.map((file) => ({
        name: file.name,
        url: URL.createObjectURL(file),
    }));
}

function clearPendingSscMembers() {
    revokeSscMemberPreviews();
    sscMembersForm.reset();
    sscMembersForm.clearErrors();

    if (sscMembersInput.value) {
        sscMembersInput.value.value = "";
    }
}

function submitSscMembers() {
    if (!sscMembersForm.images.length) {
        toastError("No images selected", "Choose one or more images before saving.");
        return;
    }

    sscMembersForm.post("/settings/ssc-members", {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearPendingSscMembers();
        },
        onError: () => handleError(sscMembersForm),
    });
}

function deleteSscMember(imageId) {
    router.delete(`/settings/ssc-members/${imageId}`, {
        preserveScroll: true,
        onError: () => {
            toastError(
                "Delete failed",
                "Unable to remove this image. Please try again.",
            );
        },
    });
}

function openDeleteAllSscDialog() {
    showDeleteAllSscDialog.value = true;
}

function closeDeleteAllSscDialog() {
    showDeleteAllSscDialog.value = false;
}

function confirmDeleteAllSscMembers() {
    deletingAllSscMembers.value = true;

    router.delete("/settings/ssc-members", {
        preserveScroll: true,
        onFinish: () => {
            deletingAllSscMembers.value = false;
        },
        onSuccess: () => {
            closeDeleteAllSscDialog();
        },
        onError: () => {
            toastError(
                "Delete failed",
                "Unable to remove all images. Please try again.",
            );
            closeDeleteAllSscDialog();
        },
    });
}

function revokeGalleryPreviews() {
    pendingGalleryPreviews.value.forEach((preview) => {
        URL.revokeObjectURL(preview.url);
    });
    pendingGalleryPreviews.value = [];
}

function openGalleryPicker() {
    galleryInput.value?.click();
}

function onGallerySelected(event) {
    const files = Array.from(event.target.files || []);
    if (!files.length) {
        return;
    }

    revokeGalleryPreviews();
    galleryForm.images = files;
    pendingGalleryPreviews.value = files.map((file) => ({
        name: file.name,
        url: URL.createObjectURL(file),
    }));
}

function clearPendingGallery() {
    revokeGalleryPreviews();
    galleryForm.reset();
    galleryForm.clearErrors();

    if (galleryInput.value) {
        galleryInput.value.value = "";
    }
}

function submitGallery() {
    if (!galleryForm.images.length) {
        toastError("No images selected", "Choose one or more images before saving.");
        return;
    }

    galleryForm.post("/settings/gallery", {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearPendingGallery();
        },
        onError: () => handleError(galleryForm),
    });
}

function updateGalleryStyle(style) {
    if (
        galleryStyleForm.processing ||
        (galleryStyleForm.style === style &&
            props.gallerySetting.style === style)
    ) {
        return;
    }

    galleryStyleForm.style = style;
    galleryStyleForm.put("/settings/gallery/style", {
        preserveScroll: true,
        onSuccess: () => switchAdvancedTab("gallery"),
        onError: () => handleError(galleryStyleForm),
    });
}

function deleteGalleryImage(imageId) {
    router.delete(`/settings/gallery/${imageId}`, {
        preserveScroll: true,
        onError: () => {
            toastError(
                "Delete failed",
                "Unable to remove this image. Please try again.",
            );
        },
    });
}

function openDeleteAllGalleryDialog() {
    showDeleteAllGalleryDialog.value = true;
}

function closeDeleteAllGalleryDialog() {
    showDeleteAllGalleryDialog.value = false;
}

function confirmDeleteAllGalleryImages() {
    deletingAllGalleryImages.value = true;

    router.delete("/settings/gallery", {
        preserveScroll: true,
        onFinish: () => {
            deletingAllGalleryImages.value = false;
        },
        onSuccess: () => {
            closeDeleteAllGalleryDialog();
        },
        onError: () => {
            toastError(
                "Delete failed",
                "Unable to remove all images. Please try again.",
            );
            closeDeleteAllGalleryDialog();
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Settings" />

        <template #header>
            <h1
                class="text-base font-semibold"
                style="color: hsl(240 10% 3.9%)"
            >
                Settings
            </h1>
        </template>

        <div class="w-full space-y-4">
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            >
                <div>
                    <h2
                        class="text-lg font-semibold"
                        style="color: hsl(240 10% 3.9%)"
                    >
                        {{
                            settingsView === "academic"
                                ? "Academic configuration"
                                : "Advanced settings"
                        }}
                    </h2>
                    <p
                        class="text-sm mt-0.5"
                        style="color: hsl(240 3.8% 46.1%)"
                    >
                        <template v-if="settingsView === 'academic'">
                            Manage departments, courses, year levels, positions,
                            and partylists.
                        </template>
                        <template v-else>
                            {{ advancedSettingsDescription }}
                        </template>
                    </p>
                </div>
                <div
                    v-if="settingsView === 'academic'"
                    class="flex items-center gap-3"
                >
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                        style="
                            background-color: hsl(240 4.8% 95.9%);
                            color: hsl(240 5.9% 10%);
                        "
                    >
                        {{ currentCountLabel }}
                    </span>
                    <Button @click="openCreateSheet">
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Add
                        {{
                            activeTab === "departments"
                                ? "department"
                                : activeTab === "courses"
                                  ? "course"
                                  : activeTab === "yearLevels"
                                    ? "year level"
                                    : activeTab === "partylists"
                                      ? "partylist"
                                      : "position"
                        }}
                    </Button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :style="
                        settingsView === 'academic' && activeTab === tab.id
                            ? 'background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);'
                            : 'background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);'
                    "
                    @click="switchTab(tab.id)"
                >
                    {{ tab.label }}
                </button>

                <div ref="advancedDropdownRef" class="relative">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                        :style="
                            settingsView === 'advanced'
                                ? 'background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);'
                                : 'background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);'
                        "
                        @click.stop="toggleAdvancedDropdown"
                    >
                        Advanced settings
                        <svg
                            class="h-4 w-4 transition-transform"
                            :class="advancedDropdownOpen ? 'rotate-180' : ''"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>

                    <div
                        v-if="advancedDropdownOpen"
                        class="absolute left-0 top-full z-20 mt-1 min-w-[10rem] rounded-md border py-1 shadow-md"
                        style="
                            background-color: hsl(0 0% 100%);
                            border-color: hsl(240 5.9% 90%);
                        "
                    >
                        <button
                            v-for="tab in advancedTabs"
                            :key="tab.id"
                            type="button"
                            class="block w-full px-3 py-2 text-left text-sm transition-colors hover:bg-gray-50"
                            :style="
                                settingsView === 'advanced' &&
                                activeAdvancedTab === tab.id
                                    ? 'color: hsl(240 5.9% 10%); font-weight: 600;'
                                    : 'color: hsl(240 3.8% 46.1%);'
                            "
                            @click="switchAdvancedTab(tab.id)"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <template v-if="settingsView === 'advanced'">
                    <button
                        v-for="tab in advancedTabs"
                        :key="`active-${tab.id}`"
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                        :style="
                            activeAdvancedTab === tab.id
                                ? 'background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);'
                                : 'background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);'
                        "
                        @click="switchAdvancedTab(tab.id)"
                    >
                        {{ tab.label }}
                    </button>
                </template>
            </div>

            <template
                v-if="
                    settingsView === 'advanced' &&
                    activeAdvancedTab === 'rangeLimit'
                "
            >
                <div
                    class="rounded-xl border p-6 space-y-6"
                    style="
                        background-color: hsl(0 0% 100%);
                        border-color: hsl(240 5.9% 90%);
                    "
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <h3
                                class="text-base font-semibold"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                Location range limit
                            </h3>
                            <p
                                class="text-sm"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                Restrict site access to users within a set
                                distance from the campus location.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <Label
                                html-for="range-limit-enabled"
                                class="text-sm font-medium"
                            >
                                {{
                                    rangeForm.is_enabled
                                        ? "Enabled"
                                        : "Disabled"
                                }}
                            </Label>
                            <Switch
                                id="range-limit-enabled"
                                v-model="rangeForm.is_enabled"
                            />
                        </div>
                    </div>

                    <div
                        class="rounded-lg border p-4 space-y-4"
                        :class="rangeForm.is_enabled ? '' : 'opacity-60'"
                        style="
                            border-color: hsl(240 5.9% 90%);
                            background-color: hsl(240 4.8% 98%);
                        "
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <div>
                                <p
                                    class="text-sm font-medium"
                                    style="color: hsl(240 10% 3.9%)"
                                >
                                    Campus location
                                </p>
                                <p
                                    class="text-xs mt-0.5"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    Detects your location through GPS, then you
                                    can fine-tune the coordinates below if
                                    needed.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="
                                    !rangeForm.is_enabled || detectingLocation
                                "
                                @click="detectLocation"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                {{
                                    detectingLocation
                                        ? "Detecting GPS..."
                                        : "Set location"
                                }}
                            </Button>
                        </div>

                        <div
                            class="rounded-md border px-3 py-2.5 text-xs space-y-1.5"
                            style="
                                border-color: hsl(214 60% 88%);
                                background-color: hsl(214 100% 97%);
                                color: hsl(215 40% 35%);
                            "
                        >
                            <p>
                                <span class="font-medium">Tip:</span>
                                Set location uses your device GPS. If the pin is
                                slightly off, type the latitude and longitude
                                manually to match the exact campus location,
                                then check the map preview below before saving.
                            </p>
                            <p>
                                For a more precise setup, open a map website
                                such as
                                <a
                                    href="https://www.google.com/maps"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-medium underline underline-offset-2"
                                    style="color: hsl(215 85% 42%)"
                                    >Google Maps</a
                                >,
                                <a
                                    href="https://mylocationapp.com/my-location/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-medium underline underline-offset-2"
                                    style="color: hsl(215 85% 42%)"
                                    >My Location App</a
                                >, or
                                <a
                                    href="https://www.latlong.net/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-medium underline underline-offset-2"
                                    style="color: hsl(215 85% 42%)"
                                    >LatLong.net</a
                                >, find your exact campus point, copy the
                                coordinates, and paste them into the fields
                                below.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label html-for="range-latitude"
                                    >Latitude</Label
                                >
                                <Input
                                    id="range-latitude"
                                    v-model="rangeForm.latitude"
                                    type="number"
                                    step="any"
                                    min="-90"
                                    max="90"
                                    placeholder="e.g. 14.599512"
                                    :disabled="!rangeForm.is_enabled"
                                    :error="!!rangeForm.errors.latitude"
                                />
                                <p
                                    class="text-xs"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    Valid range: -90 to 90
                                </p>
                                <InputError
                                    :message="rangeForm.errors.latitude"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label html-for="range-longitude"
                                    >Longitude</Label
                                >
                                <Input
                                    id="range-longitude"
                                    v-model="rangeForm.longitude"
                                    type="number"
                                    step="any"
                                    min="-180"
                                    max="180"
                                    placeholder="e.g. 120.984222"
                                    :disabled="!rangeForm.is_enabled"
                                    :error="!!rangeForm.errors.longitude"
                                />
                                <p
                                    class="text-xs"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    Valid range: -180 to 180
                                </p>
                                <InputError
                                    :message="rangeForm.errors.longitude"
                                />
                            </div>
                        </div>

                        <div class="space-y-1.5 max-w-xs">
                            <Label html-for="range-meters"
                                >Search range (meters)</Label
                            >
                            <Input
                                id="range-meters"
                                v-model="rangeForm.range_meters"
                                type="number"
                                min="1"
                                max="100000"
                                placeholder="e.g. 500"
                                :disabled="!rangeForm.is_enabled"
                                :error="!!rangeForm.errors.range_meters"
                            />
                            <p
                                class="text-xs"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                How far from the set location users can access
                                the site (in meters).
                            </p>
                            <InputError
                                :message="rangeForm.errors.range_meters"
                            />
                        </div>

                        <LocationRangeMap
                            :latitude="rangeForm.latitude"
                            :longitude="rangeForm.longitude"
                            :range-meters="rangeForm.range_meters"
                        />
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="button"
                            :disabled="rangeForm.processing"
                            @click="submitRangeLimit"
                        >
                            {{
                                rangeForm.processing
                                    ? "Saving..."
                                    : "Save range limit"
                            }}
                        </Button>
                    </div>
                </div>
            </template>

            <template
                v-if="
                    settingsView === 'advanced' &&
                    activeAdvancedTab === 'dtsRegistration'
                "
            >
                <div
                    class="rounded-xl border p-6 space-y-6"
                    style="
                        background-color: hsl(0 0% 100%);
                        border-color: hsl(240 5.9% 90%);
                    "
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <h3
                                class="text-base font-semibold"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                D&TS Registration
                            </h3>
                            <p
                                class="text-sm"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                Control when voters can register for the election
                                system. When disabled, registration stays open
                                anytime.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <Label
                                html-for="dts-registration-enabled"
                                class="text-sm font-medium"
                            >
                                {{
                                    dtsRegistrationForm.is_enabled
                                        ? "Enabled"
                                        : "Disabled"
                                }}
                            </Label>
                            <Switch
                                id="dts-registration-enabled"
                                v-model="dtsRegistrationForm.is_enabled"
                            />
                        </div>
                    </div>

                    <div
                        class="rounded-lg border px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                        style="
                            border-color: hsl(240 5.9% 90%);
                            background-color: hsl(240 4.8% 98%);
                        "
                    >
                        <p
                            class="text-sm font-medium"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Current status
                        </p>
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                            :style="
                                dtsRegistration.status === 'open'
                                    ? {
                                          background: 'hsl(142 76% 94%)',
                                          color: 'hsl(142 71% 29%)',
                                      }
                                    : dtsRegistration.status === 'upcoming'
                                      ? {
                                            background: 'hsl(221 83% 94%)',
                                            color: 'hsl(221 83% 35%)',
                                        }
                                      : {
                                            background: 'hsl(240 4.8% 95.9%)',
                                            color: 'hsl(240 5.9% 10%)',
                                        }
                            "
                        >
                            {{ dtsRegistrationStatusLabel }}
                        </span>
                    </div>

                    <div
                        class="rounded-lg border p-4 space-y-4"
                        :class="dtsRegistrationForm.is_enabled ? '' : 'opacity-60'"
                        style="
                            border-color: hsl(240 5.9% 90%);
                            background-color: hsl(240 4.8% 98%);
                        "
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label html-for="dts-registration-starts"
                                    >Registration opens</Label
                                >
                                <Input
                                    id="dts-registration-starts"
                                    v-model="dtsRegistrationForm.starts_at"
                                    type="datetime-local"
                                    :disabled="!dtsRegistrationForm.is_enabled"
                                    :error="!!dtsRegistrationForm.errors.starts_at"
                                />
                                <InputError
                                    :message="dtsRegistrationForm.errors.starts_at"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label html-for="dts-registration-ends"
                                    >Registration closes</Label
                                >
                                <Input
                                    id="dts-registration-ends"
                                    v-model="dtsRegistrationForm.ends_at"
                                    type="datetime-local"
                                    :disabled="!dtsRegistrationForm.is_enabled"
                                    :error="!!dtsRegistrationForm.errors.ends_at"
                                />
                                <InputError
                                    :message="dtsRegistrationForm.errors.ends_at"
                                />
                            </div>
                        </div>

                        <p
                            class="text-xs"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            Voters can only start or continue registration while
                            the current date and time is within this window.
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="button"
                            :disabled="dtsRegistrationForm.processing"
                            @click="submitDtsRegistration"
                        >
                            {{
                                dtsRegistrationForm.processing
                                    ? "Saving..."
                                    : "Save registration schedule"
                            }}
                        </Button>
                    </div>
                </div>
            </template>

            <template
                v-if="
                    settingsView === 'advanced' &&
                    activeAdvancedTab === 'schoolYear'
                "
            >
                <div
                    class="rounded-xl border p-6 space-y-6"
                    style="
                        background-color: hsl(0 0% 100%);
                        border-color: hsl(240 5.9% 90%);
                    "
                >
                    <div class="space-y-1">
                        <h3
                            class="text-base font-semibold"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            School year
                        </h3>
                        <p class="text-sm" style="color: hsl(240 3.8% 46.1%)">
                            Used to calculate when voter accounts expire. A 4th-year
                            student in a 4-year course expires on the school-year end
                            year, on the same month and day the account was created.
                        </p>
                    </div>

                    <div
                        class="rounded-lg border px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                        style="
                            border-color: hsl(240 5.9% 90%);
                            background-color: hsl(240 4.8% 98%);
                        "
                    >
                        <p
                            class="text-sm font-medium"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Current school year
                        </p>
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                            style="
                                background: hsl(221 83% 94%);
                                color: hsl(221 83% 35%);
                            "
                        >
                            {{ schoolYear.label || `${schoolYearForm.start_year} - ${schoolYearForm.end_year}` }}
                        </span>
                    </div>

                    <div
                        class="rounded-lg border p-4 space-y-4"
                        style="
                            border-color: hsl(240 5.9% 90%);
                            background-color: hsl(240 4.8% 98%);
                        "
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label html-for="school-year-start">Start year</Label>
                                <Input
                                    id="school-year-start"
                                    v-model="schoolYearForm.start_year"
                                    type="number"
                                    min="2000"
                                    step="1"
                                    :error="!!schoolYearForm.errors.start_year"
                                />
                                <InputError :message="schoolYearForm.errors.start_year" />
                            </div>
                            <div class="space-y-1.5">
                                <Label html-for="school-year-end">End year</Label>
                                <Input
                                    id="school-year-end"
                                    v-model="schoolYearForm.end_year"
                                    type="number"
                                    min="2001"
                                    step="1"
                                    :error="!!schoolYearForm.errors.end_year"
                                />
                                <InputError :message="schoolYearForm.errors.end_year" />
                            </div>
                        </div>

                        <p class="text-xs" style="color: hsl(240 3.8% 46.1%)">
                            Example: school year <strong>2026 - 2027</strong>, account
                            created Jul 10, 4th year / 4-year course → expires
                            <strong>Jul 10, 2027</strong>. Lower year levels get extra
                            years after 2027 (duration − year level).
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="button"
                            :disabled="schoolYearForm.processing"
                            @click="submitSchoolYear"
                        >
                            {{
                                schoolYearForm.processing
                                    ? "Saving..."
                                    : "Save school year"
                            }}
                        </Button>
                    </div>
                </div>
            </template>

            <template
                v-if="
                    settingsView === 'advanced' &&
                    activeAdvancedTab === 'uaManagement'
                "
            >
                <div
                    class="rounded-xl border p-6 space-y-6"
                    style="
                        background-color: hsl(0 0% 100%);
                        border-color: hsl(240 5.9% 90%);
                    "
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <h3
                                class="text-base font-semibold"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                UA Management
                            </h3>
                            <p
                                class="text-sm"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                Control voter inactivity detection. When enabled,
                                idle voters see an "Are you still there?" prompt
                                and are signed out if they do not confirm in time.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <Label
                                html-for="ua-management-enabled"
                                class="text-sm font-medium"
                            >
                                {{
                                    uaManagementForm.is_enabled
                                        ? "Enabled"
                                        : "Disabled"
                                }}
                            </Label>
                            <Switch
                                id="ua-management-enabled"
                                v-model="uaManagementForm.is_enabled"
                            />
                        </div>
                    </div>

                    <div
                        v-if="uaManagementForm.is_enabled"
                        class="flex items-center justify-between gap-4 rounded-lg border px-4 py-3"
                        style="
                            border-color: hsl(240 5.9% 90%);
                            background-color: hsl(240 4.8% 98%);
                        "
                    >
                        <div class="space-y-0.5">
                            <p
                                class="text-sm font-medium"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                Countdown sound
                            </p>
                            <p
                                class="text-xs"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                Play a beep every second while the Yes countdown
                                is running.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <Label
                                html-for="ua-sound-enabled"
                                class="text-sm font-medium"
                            >
                                {{
                                    uaManagementForm.sound_enabled
                                        ? "On"
                                        : "Off"
                                }}
                            </Label>
                            <Switch
                                id="ua-sound-enabled"
                                v-model="uaManagementForm.sound_enabled"
                            />
                        </div>
                    </div>

                    <div
                        v-if="uaManagementForm.is_enabled"
                        class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                    >
                        <div class="space-y-1.5">
                            <Label html-for="ua-idle-seconds"
                                >Inactivity time (seconds)</Label
                            >
                            <Input
                                id="ua-idle-seconds"
                                v-model="uaManagementForm.idle_seconds"
                                type="number"
                                min="15"
                                max="3600"
                                placeholder="60"
                            />
                            <InputError
                                :message="uaManagementForm.errors.idle_seconds"
                            />
                            <p
                                class="text-xs"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                How long a voter can stay inactive before the
                                prompt appears (15–3600 seconds).
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label html-for="ua-countdown-seconds"
                                >Yes countdown (seconds)</Label
                            >
                            <Input
                                id="ua-countdown-seconds"
                                v-model="uaManagementForm.countdown_seconds"
                                type="number"
                                min="5"
                                max="120"
                                placeholder="10"
                            />
                            <InputError
                                :message="
                                    uaManagementForm.errors.countdown_seconds
                                "
                            />
                            <p
                                class="text-xs"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                How long voters have to click Yes before they
                                are signed out automatically (5–120 seconds).
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-lg border px-4 py-3"
                        style="
                            border-color: hsl(240 5.9% 90%);
                            background-color: hsl(240 4.8% 98%);
                        "
                    >
                        <p
                            class="text-sm"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            <template v-if="uaManagementForm.is_enabled">
                                Voters will be prompted after
                                <strong>{{ uaManagementForm.idle_seconds || 60 }}</strong>
                                seconds of inactivity and logged out after
                                <strong>{{ uaManagementForm.countdown_seconds || 10 }}</strong>
                                seconds if they do not click Yes.
                                Countdown sound is
                                <strong>{{
                                    uaManagementForm.sound_enabled ? "on" : "off"
                                }}</strong>.
                            </template>
                            <template v-else>
                                Voter inactivity prompts and auto-logout are
                                currently turned off.
                            </template>
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="button"
                            :disabled="uaManagementForm.processing"
                            @click="submitUaManagement"
                        >
                            {{
                                uaManagementForm.processing
                                    ? "Saving..."
                                    : "Save UA settings"
                            }}
                        </Button>
                    </div>
                </div>
            </template>

            <template
                v-if="
                    settingsView === 'advanced' &&
                    activeAdvancedTab === 'sscMembers'
                "
            >
                <div
                    class="rounded-xl border p-6 space-y-6"
                    style="
                        background-color: hsl(0 0% 100%);
                        border-color: hsl(240 5.9% 90%);
                    "
                >
                    <div class="space-y-1">
                        <h3
                            class="text-base font-semibold"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            SSC members
                        </h3>
                        <p
                            class="text-sm"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            Upload multiple images of SSC members. Saved images
                            are stored for use across the site.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <p
                                    class="text-sm font-medium"
                                    style="color: hsl(240 10% 3.9%)"
                                >
                                    Upload images
                                </p>
                                <p
                                    class="text-xs mt-0.5"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    JPG, PNG, or WebP up to 5MB each.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                @click="openSscMembersPicker"
                            >
                                Choose images
                            </Button>
                        </div>

                        <input
                            ref="sscMembersInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            class="hidden"
                            @change="onSscMembersSelected"
                        />

                        <InputError :message="sscMembersForm.errors.images" />

                        <div
                            v-if="pendingSscMemberPreviews.length"
                            class="space-y-3"
                        >
                            <p
                                class="text-sm font-medium"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                Selected images ({{ pendingSscMemberPreviews.length }})
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                <div
                                    v-for="preview in pendingSscMemberPreviews"
                                    :key="preview.url"
                                    class="overflow-hidden rounded-lg border"
                                    style="border-color: hsl(240 5.9% 90%)"
                                >
                                    <div
                                        class="flex aspect-[3/4] items-center justify-center p-2"
                                        style="background-color: hsl(240 4.8% 98%)"
                                    >
                                        <img
                                            :src="preview.url"
                                            :alt="preview.name"
                                            class="max-h-full max-w-full object-contain"
                                        />
                                    </div>
                                    <p
                                        class="truncate px-2 py-1.5 text-xs"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ preview.name }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-end gap-2">
                            <Button
                                v-if="pendingSscMemberPreviews.length"
                                type="button"
                                variant="outline"
                                @click="clearPendingSscMembers"
                            >
                                Clear selection
                            </Button>
                            <Button
                                type="button"
                                :disabled="
                                    sscMembersForm.processing ||
                                    !pendingSscMemberPreviews.length
                                "
                                @click="submitSscMembers"
                            >
                                {{
                                    sscMembersForm.processing
                                        ? "Saving..."
                                        : "Save images"
                                }}
                            </Button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <p
                                class="text-sm font-medium"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                Saved images
                            </p>
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    style="
                                        background-color: hsl(240 4.8% 95.9%);
                                        color: hsl(240 5.9% 10%);
                                    "
                                >
                                    {{ sscMembers.length }}
                                </span>
                                <Button
                                    v-if="sscMembers.length"
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    @click="openDeleteAllSscDialog"
                                >
                                    Delete all
                                </Button>
                            </div>
                        </div>

                        <div
                            v-if="sscMembers.length"
                            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3"
                        >
                            <div
                                v-for="image in sscMembers"
                                :key="image.id"
                                class="group relative overflow-hidden rounded-lg border"
                                style="border-color: hsl(240 5.9% 90%)"
                            >
                                <div
                                    class="flex aspect-[3/4] items-center justify-center p-2"
                                    style="background-color: hsl(240 4.8% 98%)"
                                >
                                    <img
                                        :src="image.image_url"
                                        alt="SSC member"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                </div>
                                <button
                                    type="button"
                                    class="absolute top-2 right-2 rounded-md px-2 py-1 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100 sm:opacity-100"
                                    style="background-color: hsl(0 72% 51%)"
                                    @click="deleteSscMember(image.id)"
                                >
                                    Remove
                                </button>
                                <p
                                    class="px-2 py-1.5 text-xs"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    {{ image.created_at }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-else
                            class="rounded-lg border border-dashed px-4 py-8 text-center text-sm"
                            style="
                                border-color: hsl(240 5.9% 90%);
                                color: hsl(240 3.8% 46.1%);
                            "
                        >
                            No SSC member images saved yet.
                        </div>
                    </div>
                </div>
            </template>

            <template
                v-if="
                    settingsView === 'advanced' &&
                    activeAdvancedTab === 'gallery'
                "
            >
                <div
                    class="rounded-xl border p-6 space-y-6"
                    style="
                        background-color: hsl(0 0% 100%);
                        border-color: hsl(240 5.9% 90%);
                    "
                >
                    <div class="space-y-1">
                        <h3
                            class="text-base font-semibold"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Gallery
                        </h3>
                        <p
                            class="text-sm"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            Upload multiple gallery images. You can add, remove,
                            or clear all images anytime.
                        </p>
                    </div>

                    <div
                        class="rounded-lg border px-4 py-4 space-y-3"
                        style="
                            border-color: hsl(240 5.9% 90%);
                            background-color: hsl(240 4.8% 98%);
                        "
                    >
                        <div class="space-y-1">
                            <p
                                class="text-sm font-medium"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                Welcome page display
                            </p>
                            <p
                                class="text-xs"
                                style="color: hsl(240 3.8% 46.1%)"
                            >
                                Choose how gallery images appear on the Welcome
                                page.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                type="button"
                                class="rounded-lg border px-4 py-3 text-left transition-colors"
                                :style="
                                    galleryStyleForm.style === 'dome'
                                        ? {
                                              borderColor: 'hsl(215 85% 42%)',
                                              backgroundColor: 'hsl(215 70% 96%)',
                                          }
                                        : {
                                              borderColor: 'hsl(240 5.9% 90%)',
                                              backgroundColor: 'hsl(0 0% 100%)',
                                          }
                                "
                                :disabled="galleryStyleForm.processing"
                                @click="updateGalleryStyle('dome')"
                            >
                                <p
                                    class="text-sm font-semibold"
                                    style="color: hsl(240 10% 3.9%)"
                                >
                                    Dome Gallery
                                </p>
                                <p
                                    class="text-xs mt-1"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    3D sphere layout visitors can drag and open.
                                </p>
                            </button>

                            <button
                                type="button"
                                class="rounded-lg border px-4 py-3 text-left transition-colors"
                                :style="
                                    galleryStyleForm.style === 'circular'
                                        ? {
                                              borderColor: 'hsl(215 85% 42%)',
                                              backgroundColor: 'hsl(215 70% 96%)',
                                          }
                                        : {
                                              borderColor: 'hsl(240 5.9% 90%)',
                                              backgroundColor: 'hsl(0 0% 100%)',
                                          }
                                "
                                :disabled="galleryStyleForm.processing"
                                @click="updateGalleryStyle('circular')"
                            >
                                <p
                                    class="text-sm font-semibold"
                                    style="color: hsl(240 10% 3.9%)"
                                >
                                    Circular Gallery
                                </p>
                                <p
                                    class="text-xs mt-1"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    Curved scrolling strip with drag and wheel.
                                </p>
                            </button>
                        </div>

                        <InputError :message="galleryStyleForm.errors.style" />
                    </div>

                    <div class="space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <p
                                    class="text-sm font-medium"
                                    style="color: hsl(240 10% 3.9%)"
                                >
                                    Upload images
                                </p>
                                <p
                                    class="text-xs mt-0.5"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    JPG, PNG, or WebP up to 5MB each.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                @click="openGalleryPicker"
                            >
                                Choose images
                            </Button>
                        </div>

                        <input
                            ref="galleryInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            class="hidden"
                            @change="onGallerySelected"
                        />

                        <InputError :message="galleryForm.errors.images" />

                        <div
                            v-if="pendingGalleryPreviews.length"
                            class="space-y-3"
                        >
                            <p
                                class="text-sm font-medium"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                Selected images ({{ pendingGalleryPreviews.length }})
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                <div
                                    v-for="preview in pendingGalleryPreviews"
                                    :key="preview.url"
                                    class="overflow-hidden rounded-lg border"
                                    style="border-color: hsl(240 5.9% 90%)"
                                >
                                    <div
                                        class="flex aspect-[3/4] items-center justify-center p-2"
                                        style="background-color: hsl(240 4.8% 98%)"
                                    >
                                        <img
                                            :src="preview.url"
                                            :alt="preview.name"
                                            class="max-h-full max-w-full object-contain"
                                        />
                                    </div>
                                    <p
                                        class="truncate px-2 py-1.5 text-xs"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ preview.name }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-end gap-2">
                            <Button
                                v-if="pendingGalleryPreviews.length"
                                type="button"
                                variant="outline"
                                @click="clearPendingGallery"
                            >
                                Clear selection
                            </Button>
                            <Button
                                type="button"
                                :disabled="
                                    galleryForm.processing ||
                                    !pendingGalleryPreviews.length
                                "
                                @click="submitGallery"
                            >
                                {{
                                    galleryForm.processing
                                        ? "Saving..."
                                        : "Save images"
                                }}
                            </Button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <p
                                class="text-sm font-medium"
                                style="color: hsl(240 10% 3.9%)"
                            >
                                Saved images
                            </p>
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    style="
                                        background-color: hsl(240 4.8% 95.9%);
                                        color: hsl(240 5.9% 10%);
                                    "
                                >
                                    {{ galleryImages.length }}
                                </span>
                                <Button
                                    v-if="galleryImages.length"
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    @click="openDeleteAllGalleryDialog"
                                >
                                    Delete all
                                </Button>
                            </div>
                        </div>

                        <div
                            v-if="galleryImages.length"
                            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3"
                        >
                            <div
                                v-for="image in galleryImages"
                                :key="image.id"
                                class="group relative overflow-hidden rounded-lg border"
                                style="border-color: hsl(240 5.9% 90%)"
                            >
                                <div
                                    class="flex aspect-[3/4] items-center justify-center p-2"
                                    style="background-color: hsl(240 4.8% 98%)"
                                >
                                    <img
                                        :src="image.image_url"
                                        alt="Gallery image"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                </div>
                                <button
                                    type="button"
                                    class="absolute top-2 right-2 rounded-md px-2 py-1 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100 sm:opacity-100"
                                    style="background-color: hsl(0 72% 51%)"
                                    @click="deleteGalleryImage(image.id)"
                                >
                                    Remove
                                </button>
                                <p
                                    class="px-2 py-1.5 text-xs"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    {{ image.created_at }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-else
                            class="rounded-lg border border-dashed px-4 py-8 text-center text-sm"
                            style="
                                border-color: hsl(240 5.9% 90%);
                                color: hsl(240 3.8% 46.1%);
                            "
                        >
                            No gallery images saved yet.
                        </div>
                    </div>
                </div>
            </template>

            <div
                v-if="settingsView === 'academic'"
                class="rounded-xl border overflow-hidden"
                style="
                    background-color: hsl(0 0% 100%);
                    border-color: hsl(240 5.9% 90%);
                "
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b"
                                style="
                                    border-color: hsl(240 5.9% 90%);
                                    background-color: hsl(240 4.8% 95.9%);
                                "
                            >
                                <template v-if="activeTab === 'departments'">
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Acronym
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Department
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Color
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Created
                                    </th>
                                </template>
                                <template v-else-if="activeTab === 'courses'">
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Course
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Department
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Duration
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Created
                                    </th>
                                </template>
                                <template
                                    v-else-if="activeTab === 'yearLevels'"
                                >
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Year level
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Sort order
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Created
                                    </th>
                                </template>
                                <template
                                    v-else-if="activeTab === 'partylists'"
                                >
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Acronym
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Partylist
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Description
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Created
                                    </th>
                                </template>
                                <template v-else>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Position
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Sort order
                                    </th>
                                    <th
                                        class="h-10 px-4 text-left align-middle font-medium"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        Created
                                    </th>
                                </template>
                                <th
                                    class="h-10 px-4 text-right align-middle font-medium"
                                    style="color: hsl(240 3.8% 46.1%)"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="activeTab === 'departments'">
                                <tr
                                    v-for="item in departments"
                                    :key="item.id"
                                    class="border-b transition-colors hover:bg-gray-50"
                                    style="border-color: hsl(240 5.9% 90%)"
                                >
                                    <td class="px-4 py-3 align-middle">
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold tracking-wide"
                                            style="
                                                background-color: hsl(
                                                    240 4.8% 95.9%
                                                );
                                                color: hsl(240 10% 3.9%);
                                            "
                                        >
                                            {{ item.acronym || "—" }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle font-medium"
                                        style="color: hsl(240 10% 3.9%)"
                                    >
                                        {{ item.name }}
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="h-5 w-5 rounded-full border shrink-0"
                                                :style="{
                                                    backgroundColor:
                                                        departmentColorHex(
                                                            item.color,
                                                        ),
                                                    borderColor:
                                                        'hsl(240 5.9% 90%)',
                                                }"
                                            />
                                            <span
                                                class="text-sm capitalize"
                                                style="
                                                    color: hsl(240 3.8% 46.1%);
                                                "
                                                >{{ item.color }}</span
                                            >
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ item.created_at }}
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openEditDialog(item)"
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="openDeleteDialog(item)"
                                                >Delete</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="departments.length === 0">
                                    <td
                                        colspan="5"
                                        class="px-4 py-12 text-center"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        No departments found.
                                    </td>
                                </tr>
                            </template>

                            <template v-else-if="activeTab === 'courses'">
                                <tr
                                    v-for="item in courses"
                                    :key="item.id"
                                    class="border-b transition-colors hover:bg-gray-50"
                                    style="border-color: hsl(240 5.9% 90%)"
                                >
                                    <td
                                        class="px-4 py-3 align-middle font-medium"
                                        style="color: hsl(240 10% 3.9%)"
                                    >
                                        {{ item.name }}
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ item.department_name }}
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{
                                            formatCourseDuration(
                                                item.duration_years,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ item.created_at }}
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openEditDialog(item)"
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="openDeleteDialog(item)"
                                                >Delete</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="courses.length === 0">
                                    <td
                                        colspan="5"
                                        class="px-4 py-12 text-center"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        No courses found.
                                    </td>
                                </tr>
                            </template>

                            <template v-else-if="activeTab === 'yearLevels'">
                                <tr
                                    v-for="item in yearLevels"
                                    :key="item.id"
                                    class="border-b transition-colors hover:bg-gray-50"
                                    style="border-color: hsl(240 5.9% 90%)"
                                >
                                    <td
                                        class="px-4 py-3 align-middle font-medium"
                                        style="color: hsl(240 10% 3.9%)"
                                    >
                                        {{ item.name }}
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ item.sort_order }}
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ item.created_at }}
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openEditDialog(item)"
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="openDeleteDialog(item)"
                                                >Delete</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="yearLevels.length === 0">
                                    <td
                                        colspan="4"
                                        class="px-4 py-12 text-center"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        No year levels found.
                                    </td>
                                </tr>
                            </template>

                            <template v-else-if="activeTab === 'partylists'">
                                <tr
                                    v-for="item in partylists"
                                    :key="item.id"
                                    class="border-b transition-colors hover:bg-gray-50"
                                    style="border-color: hsl(240 5.9% 90%)"
                                >
                                    <td class="px-4 py-3 align-middle">
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold tracking-wide"
                                            style="
                                                background-color: hsl(
                                                    240 4.8% 95.9%
                                                );
                                                color: hsl(240 10% 3.9%);
                                            "
                                        >
                                            {{ item.acronym || "—" }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle font-medium"
                                        style="color: hsl(240 10% 3.9%)"
                                    >
                                        {{ item.name }}
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle max-w-xs truncate"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ item.description || "—" }}
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ item.created_at }}
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openEditDialog(item)"
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="openDeleteDialog(item)"
                                                >Delete</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="partylists.length === 0">
                                    <td
                                        colspan="5"
                                        class="px-4 py-12 text-center"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        No partylists found.
                                    </td>
                                </tr>
                            </template>

                            <template v-else>
                                <tr
                                    v-for="item in positions"
                                    :key="item.id"
                                    class="border-b transition-colors hover:bg-gray-50"
                                    style="border-color: hsl(240 5.9% 90%)"
                                >
                                    <td
                                        class="px-4 py-3 align-middle font-medium"
                                        style="color: hsl(240 10% 3.9%)"
                                    >
                                        {{ item.name }}
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ item.sort_order }}
                                    </td>
                                    <td
                                        class="px-4 py-3 align-middle"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        {{ item.created_at }}
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openEditDialog(item)"
                                                >Edit</Button
                                            >
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="openDeleteDialog(item)"
                                                >Delete</Button
                                            >
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="positions.length === 0">
                                    <td
                                        colspan="4"
                                        class="px-4 py-12 text-center"
                                        style="color: hsl(240 3.8% 46.1%)"
                                    >
                                        No positions found.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Sheet
            :show="showCreateSheet"
            :title="createTitle()"
            description="Fill in the details below to add a new record."
            @close="closeCreateSheet"
        >
            <form
                id="create-settings-form"
                class="space-y-4"
                @submit.prevent="submitCreate"
            >
                <template v-if="activeTab === 'departments'">
                    <div class="space-y-1.5">
                        <Label html-for="create-department-name"
                            >Department name</Label
                        >
                        <Input
                            id="create-department-name"
                            v-model="departmentForm.name"
                            type="text"
                            placeholder="College of Computer Studies"
                            :error="!!departmentForm.errors.name"
                        />
                        <InputError :message="departmentForm.errors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="create-department-acronym"
                            >Acronym / shortcut</Label
                        >
                        <Input
                            id="create-department-acronym"
                            v-model="departmentForm.acronym"
                            type="text"
                            placeholder="CCS"
                            maxlength="20"
                            :error="!!departmentForm.errors.acronym"
                        />
                        <InputError :message="departmentForm.errors.acronym" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="create-department-color">Color</Label>
                        <Select
                            id="create-department-color"
                            v-model="departmentForm.color"
                            :options="departmentColorOptions"
                            placeholder="Select color"
                            :error="!!departmentForm.errors.color"
                        />
                        <div class="flex flex-wrap gap-2 pt-1">
                            <button
                                v-for="(hex, name) in departmentColors"
                                :key="name"
                                type="button"
                                class="h-6 w-6 rounded-full border transition-transform hover:scale-110"
                                :style="{
                                    backgroundColor: hex,
                                    borderColor:
                                        departmentForm.color === name
                                            ? 'hsl(240 10% 3.9%)'
                                            : 'hsl(240 5.9% 90%)',
                                }"
                                :title="name"
                                @click="departmentForm.color = name"
                            />
                        </div>
                        <InputError :message="departmentForm.errors.color" />
                    </div>
                </template>

                <template v-else-if="activeTab === 'courses'">
                    <div class="space-y-1.5">
                        <Label html-for="create-course-department"
                            >Department</Label
                        >
                        <Select
                            id="create-course-department"
                            v-model="courseForm.department_id"
                            :options="departmentOptions"
                            placeholder="Select department"
                            :error="!!courseForm.errors.department_id"
                        />
                        <InputError
                            :message="courseForm.errors.department_id"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="create-course-name">Course name</Label>
                        <Input
                            id="create-course-name"
                            v-model="courseForm.name"
                            type="text"
                            placeholder="Bachelor of Science in Information Technology"
                            :error="!!courseForm.errors.name"
                        />
                        <InputError :message="courseForm.errors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="create-course-duration"
                            >Course duration</Label
                        >
                        <Select
                            id="create-course-duration"
                            v-model="courseForm.duration_years"
                            :options="courseDurationOptions"
                            placeholder="Select duration"
                            :error="!!courseForm.errors.duration_years"
                        />
                        <p class="text-xs" style="color: hsl(240 3.8% 46.1%)">
                            How many years this program runs (e.g., 2 years for
                            a 2-year course).
                        </p>
                        <InputError
                            :message="courseForm.errors.duration_years"
                        />
                    </div>
                </template>

                <template v-else-if="activeTab === 'yearLevels'">
                    <div class="space-y-1.5">
                        <Label html-for="create-year-level-name"
                            >Year level name</Label
                        >
                        <Input
                            id="create-year-level-name"
                            v-model="yearLevelForm.name"
                            type="text"
                            placeholder="1st Year"
                            :error="!!yearLevelForm.errors.name"
                        />
                        <InputError :message="yearLevelForm.errors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="create-year-level-sort"
                            >Sort order</Label
                        >
                        <Input
                            id="create-year-level-sort"
                            v-model="yearLevelForm.sort_order"
                            type="number"
                            min="0"
                            placeholder="1"
                            :error="!!yearLevelForm.errors.sort_order"
                        />
                        <InputError
                            :message="yearLevelForm.errors.sort_order"
                        />
                    </div>
                </template>

                <template v-else-if="activeTab === 'partylists'">
                    <div class="space-y-1.5">
                        <Label html-for="create-partylist-name"
                            >Partylist name</Label
                        >
                        <Input
                            id="create-partylist-name"
                            v-model="partylistForm.name"
                            type="text"
                            placeholder="United Student Alliance"
                            :error="!!partylistForm.errors.name"
                        />
                        <InputError :message="partylistForm.errors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="create-partylist-acronym"
                            >Acronym / shortcut</Label
                        >
                        <Input
                            id="create-partylist-acronym"
                            v-model="partylistForm.acronym"
                            type="text"
                            placeholder="USA"
                            maxlength="20"
                            :error="!!partylistForm.errors.acronym"
                        />
                        <InputError :message="partylistForm.errors.acronym" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="create-partylist-description"
                            >Description</Label
                        >
                        <Input
                            id="create-partylist-description"
                            v-model="partylistForm.description"
                            type="text"
                            placeholder="Serving students with integrity and action."
                            :error="!!partylistForm.errors.description"
                        />
                        <p class="text-xs" style="color: hsl(240 3.8% 46.1%)">
                            Optional motto or brief platform summary for this
                            slate.
                        </p>
                        <InputError
                            :message="partylistForm.errors.description"
                        />
                    </div>
                </template>

                <template v-else>
                    <div class="space-y-1.5">
                        <Label html-for="create-position-name"
                            >Position name</Label
                        >
                        <Input
                            id="create-position-name"
                            v-model="positionForm.name"
                            type="text"
                            placeholder="President"
                            :error="!!positionForm.errors.name"
                        />
                        <InputError :message="positionForm.errors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="create-position-sort"
                            >Sort order</Label
                        >
                        <Input
                            id="create-position-sort"
                            v-model="positionForm.sort_order"
                            type="number"
                            min="0"
                            placeholder="1"
                            :error="!!positionForm.errors.sort_order"
                        />
                        <p class="text-xs" style="color: hsl(240 3.8% 46.1%)">
                            Controls display order in candidate forms and lists.
                        </p>
                        <InputError :message="positionForm.errors.sort_order" />
                    </div>
                </template>
            </form>

            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeCreateSheet"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        form="create-settings-form"
                        :disabled="activeForm().processing"
                    >
                        {{ activeForm().processing ? "Saving..." : "Save" }}
                    </Button>
                </div>
            </template>
        </Sheet>

        <Dialog
            :show="showEditDialog"
            :title="editTitle()"
            description="Update the selected record."
            @close="closeEditDialog"
        >
            <form class="space-y-4" @submit.prevent="submitEdit">
                <template v-if="activeTab === 'departments'">
                    <div class="space-y-1.5">
                        <Label html-for="edit-department-name"
                            >Department name</Label
                        >
                        <Input
                            id="edit-department-name"
                            v-model="departmentForm.name"
                            type="text"
                            :error="!!departmentForm.errors.name"
                        />
                        <InputError :message="departmentForm.errors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="edit-department-acronym"
                            >Acronym / shortcut</Label
                        >
                        <Input
                            id="edit-department-acronym"
                            v-model="departmentForm.acronym"
                            type="text"
                            placeholder="CCS"
                            maxlength="20"
                            :error="!!departmentForm.errors.acronym"
                        />
                        <InputError :message="departmentForm.errors.acronym" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="edit-department-color">Color</Label>
                        <Select
                            id="edit-department-color"
                            v-model="departmentForm.color"
                            :options="departmentColorOptions"
                            placeholder="Select color"
                            :error="!!departmentForm.errors.color"
                        />
                        <div class="flex flex-wrap gap-2 pt-1">
                            <button
                                v-for="(hex, name) in departmentColors"
                                :key="name"
                                type="button"
                                class="h-6 w-6 rounded-full border transition-transform hover:scale-110"
                                :style="{
                                    backgroundColor: hex,
                                    borderColor:
                                        departmentForm.color === name
                                            ? 'hsl(240 10% 3.9%)'
                                            : 'hsl(240 5.9% 90%)',
                                }"
                                :title="name"
                                @click="departmentForm.color = name"
                            />
                        </div>
                        <InputError :message="departmentForm.errors.color" />
                    </div>
                </template>

                <template v-else-if="activeTab === 'courses'">
                    <div class="space-y-1.5">
                        <Label html-for="edit-course-department"
                            >Department</Label
                        >
                        <Select
                            id="edit-course-department"
                            v-model="courseForm.department_id"
                            :options="departmentOptions"
                            placeholder="Select department"
                            :error="!!courseForm.errors.department_id"
                        />
                        <InputError
                            :message="courseForm.errors.department_id"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="edit-course-name">Course name</Label>
                        <Input
                            id="edit-course-name"
                            v-model="courseForm.name"
                            type="text"
                            :error="!!courseForm.errors.name"
                        />
                        <InputError :message="courseForm.errors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="edit-course-duration"
                            >Course duration</Label
                        >
                        <Select
                            id="edit-course-duration"
                            v-model="courseForm.duration_years"
                            :options="courseDurationOptions"
                            placeholder="Select duration"
                            :error="!!courseForm.errors.duration_years"
                        />
                        <p class="text-xs" style="color: hsl(240 3.8% 46.1%)">
                            How many years this program runs (e.g., 2 years for
                            a 2-year course).
                        </p>
                        <InputError
                            :message="courseForm.errors.duration_years"
                        />
                    </div>
                </template>

                <template v-else-if="activeTab === 'yearLevels'">
                    <div class="space-y-1.5">
                        <Label html-for="edit-year-level-name"
                            >Year level name</Label
                        >
                        <Input
                            id="edit-year-level-name"
                            v-model="yearLevelForm.name"
                            type="text"
                            :error="!!yearLevelForm.errors.name"
                        />
                        <InputError :message="yearLevelForm.errors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="edit-year-level-sort"
                            >Sort order</Label
                        >
                        <Input
                            id="edit-year-level-sort"
                            v-model="yearLevelForm.sort_order"
                            type="number"
                            min="0"
                            :error="!!yearLevelForm.errors.sort_order"
                        />
                        <InputError
                            :message="yearLevelForm.errors.sort_order"
                        />
                    </div>
                </template>

                <template v-else-if="activeTab === 'partylists'">
                    <div class="space-y-1.5">
                        <Label html-for="edit-partylist-name"
                            >Partylist name</Label
                        >
                        <Input
                            id="edit-partylist-name"
                            v-model="partylistForm.name"
                            type="text"
                            :error="!!partylistForm.errors.name"
                        />
                        <InputError :message="partylistForm.errors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="edit-partylist-acronym"
                            >Acronym / shortcut</Label
                        >
                        <Input
                            id="edit-partylist-acronym"
                            v-model="partylistForm.acronym"
                            type="text"
                            placeholder="USA"
                            maxlength="20"
                            :error="!!partylistForm.errors.acronym"
                        />
                        <InputError :message="partylistForm.errors.acronym" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="edit-partylist-description"
                            >Description</Label
                        >
                        <Input
                            id="edit-partylist-description"
                            v-model="partylistForm.description"
                            type="text"
                            :error="!!partylistForm.errors.description"
                        />
                        <InputError
                            :message="partylistForm.errors.description"
                        />
                    </div>
                </template>

                <template v-else>
                    <div class="space-y-1.5">
                        <Label html-for="edit-position-name"
                            >Position name</Label
                        >
                        <Input
                            id="edit-position-name"
                            v-model="positionForm.name"
                            type="text"
                            :error="!!positionForm.errors.name"
                        />
                        <InputError :message="positionForm.errors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label html-for="edit-position-sort">Sort order</Label>
                        <Input
                            id="edit-position-sort"
                            v-model="positionForm.sort_order"
                            type="number"
                            min="0"
                            :error="!!positionForm.errors.sort_order"
                        />
                        <InputError :message="positionForm.errors.sort_order" />
                    </div>
                </template>

                <div class="flex justify-end gap-2 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeEditDialog"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="activeForm().processing">
                        {{
                            activeForm().processing
                                ? "Saving..."
                                : "Save changes"
                        }}
                    </Button>
                </div>
            </form>
        </Dialog>

        <Dialog
            :show="showDeleteDialog"
            :title="deleteTitle()"
            description="This action cannot be undone."
            @close="closeDeleteDialog"
        >
            <p class="text-sm mb-6" style="color: hsl(240 3.8% 46.1%)">
                Are you sure you want to delete
                <span class="font-medium" style="color: hsl(240 10% 3.9%)">{{
                    deletingItem?.name
                }}</span
                >?
            </p>

            <div class="flex justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    @click="closeDeleteDialog"
                    >Cancel</Button
                >
                <Button
                    type="button"
                    variant="destructive"
                    @click="confirmDelete"
                    >Delete</Button
                >
            </div>
        </Dialog>

        <Dialog
            :show="showDeleteAllSscDialog"
            title="Delete all SSC member images"
            description="This action cannot be undone."
            @close="closeDeleteAllSscDialog"
        >
            <p class="text-sm mb-6" style="color: hsl(240 3.8% 46.1%)">
                Are you sure you want to delete all
                <span class="font-medium" style="color: hsl(240 10% 3.9%)">{{
                    sscMembers.length
                }}</span>
                saved SSC member images? They will also be removed from the
                welcome page carousel.
            </p>

            <div class="flex justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="deletingAllSscMembers"
                    @click="closeDeleteAllSscDialog"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    :disabled="deletingAllSscMembers"
                    @click="confirmDeleteAllSscMembers"
                >
                    {{
                        deletingAllSscMembers
                            ? "Deleting..."
                            : "Delete all images"
                    }}
                </Button>
            </div>
        </Dialog>

        <Dialog
            :show="showDeleteAllGalleryDialog"
            title="Delete all gallery images"
            description="This action cannot be undone."
            @close="closeDeleteAllGalleryDialog"
        >
            <p class="text-sm mb-6" style="color: hsl(240 3.8% 46.1%)">
                Are you sure you want to delete all
                <span class="font-medium" style="color: hsl(240 10% 3.9%)">{{
                    galleryImages.length
                }}</span>
                saved gallery images?
            </p>

            <div class="flex justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="deletingAllGalleryImages"
                    @click="closeDeleteAllGalleryDialog"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    :disabled="deletingAllGalleryImages"
                    @click="confirmDeleteAllGalleryImages"
                >
                    {{
                        deletingAllGalleryImages
                            ? "Deleting..."
                            : "Delete all images"
                    }}
                </Button>
            </div>
        </Dialog>
    </AppLayout>
</template>
