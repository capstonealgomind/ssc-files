<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    committees: {
        type: Array,
        default: () => [],
    },
    pages: {
        type: Array,
        default: () => [],
    },
});

const { error: toastError } = useToast();

const selectedId = ref(props.committees[0]?.id ?? null);

const selectedCommittee = computed(() =>
    props.committees.find((c) => c.id === selectedId.value) ?? null,
);

const form = useForm({
    pages: [],
});

watch(
    selectedCommittee,
    (committee) => {
        form.pages = committee ? [...(committee.allowed_pages || [])] : [];
        form.clearErrors();
    },
    { immediate: true },
);

watch(
    () => props.committees,
    (list) => {
        if (!list.length) {
            selectedId.value = null;
            return;
        }
        if (!list.some((c) => c.id === selectedId.value)) {
            selectedId.value = list[0].id;
        }
    },
);

function selectCommittee(id) {
    selectedId.value = id;
}

function togglePage(key) {
    if (form.pages.includes(key)) {
        form.pages = form.pages.filter((k) => k !== key);
    } else {
        form.pages = [...form.pages, key];
    }
}

function isChecked(key) {
    return form.pages.includes(key);
}

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
}

function save() {
    if (!selectedCommittee.value) return;

    form.put(`/permissions/${selectedCommittee.value.id}`, {
        preserveScroll: true,
        onError: () => {
            toastError(
                'Could not save permissions',
                Object.values(form.errors)[0] || 'Please try again.',
            );
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Permissions" />

        <template #header>
            <h1 class="text-base font-semibold" style="color: hsl(240 10% 3.9%);">Permissions</h1>
        </template>

        <div class="w-full space-y-4">
            <div>
                <h2 class="text-lg font-semibold" style="color: hsl(240 10% 3.9%);">Permissions</h2>
                <p class="text-sm mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                    Assign operational page access for each committee account.
                </p>
            </div>

            <div
                v-if="!committees.length"
                class="rounded-lg border px-6 py-12 text-center text-sm"
                style="border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
            >
                No committee accounts yet. Create one under Accounts first.
            </div>

            <div
                v-else
                class="grid gap-4 lg:grid-cols-[minmax(0,280px)_minmax(0,1fr)]"
            >
                <div
                    class="rounded-lg border overflow-hidden"
                    style="border-color: hsl(240 5.9% 90%); background: #fff;"
                >
                    <div
                        class="px-4 py-3 border-b text-xs font-semibold uppercase tracking-wide"
                        style="border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
                    >
                        Committee accounts
                    </div>
                    <ul class="max-h-[70vh] overflow-y-auto divide-y" style="border-color: hsl(240 5.9% 90%);">
                        <li
                            v-for="committee in committees"
                            :key="committee.id"
                        >
                            <button
                                type="button"
                                class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors"
                                :style="selectedId === committee.id
                                    ? 'background: hsl(221 83% 97%);'
                                    : ''"
                                @click="selectCommittee(committee.id)"
                            >
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold overflow-hidden"
                                    style="background: hsl(221 83% 53%); color: #fff;"
                                >
                                    <img
                                        v-if="committee.profile_photo_url"
                                        :src="committee.profile_photo_url"
                                        :alt="committee.name"
                                        class="h-full w-full object-cover"
                                    >
                                    <template v-else>
                                        {{ getInitials(committee.name) }}
                                    </template>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium truncate" style="color: hsl(240 10% 3.9%);">
                                        {{ committee.name }}
                                    </span>
                                    <span class="block text-xs truncate" style="color: hsl(240 3.8% 46.1%);">
                                        {{ committee.email }}
                                    </span>
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div
                    class="rounded-lg border"
                    style="border-color: hsl(240 5.9% 90%); background: #fff;"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-b"
                        style="border-color: hsl(240 5.9% 90%);"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-semibold overflow-hidden"
                                style="background: hsl(221 83% 53%); color: #fff;"
                            >
                                <img
                                    v-if="selectedCommittee?.profile_photo_url"
                                    :src="selectedCommittee.profile_photo_url"
                                    :alt="selectedCommittee.name"
                                    class="h-full w-full object-cover"
                                >
                                <template v-else>
                                    {{ getInitials(selectedCommittee?.name) }}
                                </template>
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold truncate" style="color: hsl(240 10% 3.9%);">
                                    {{ selectedCommittee?.name }}
                                </h3>
                                <p class="text-xs mt-0.5 truncate" style="color: hsl(240 3.8% 46.1%);">
                                    {{ selectedCommittee?.email }}
                                </p>
                            </div>
                        </div>
                        <Button :disabled="form.processing" @click="save">
                            {{ form.processing ? 'Saving…' : 'Save permissions' }}
                        </Button>
                    </div>

                    <div class="p-4 space-y-2">
                        <p class="text-xs mb-3" style="color: hsl(240 3.8% 46.1%);">
                            Select which pages this committee account can open. Profile access is always available.
                        </p>
                        <label
                            v-for="page in pages"
                            :key="page.key"
                            class="flex items-start gap-3 rounded-md border px-3 py-3 cursor-pointer transition-colors hover:bg-slate-50"
                            style="border-color: hsl(240 5.9% 90%);"
                        >
                            <input
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300"
                                style="accent-color: hsl(221 83% 53%);"
                                :checked="isChecked(page.key)"
                                @change="togglePage(page.key)"
                            >
                            <span class="min-w-0">
                                <span class="block text-sm font-medium" style="color: hsl(240 10% 3.9%);">
                                    {{ page.label }}
                                </span>
                                <span class="block text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                                    {{ page.description }}
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
