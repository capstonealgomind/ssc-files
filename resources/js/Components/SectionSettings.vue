<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Select from '@/Components/ui/Select.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import { useClientPagination } from '@/composables/useClientPagination';
import InputError from '@/Components/ui/InputError.vue';

const props = defineProps(['sections', 'departments', 'courses', 'yearLevels']);
const { items, meta, setPage } = useClientPagination(() => props.sections, 26);
const endpoint = '/settings/sections';
const open = ref(false);
const editing = ref(null);
const removing = ref(null);
const letters = Array.from({ length: 26 }, (_, index) => {
    const letter = String.fromCharCode(65 + index);
    return { value: letter, label: letter };
});
const form = useForm({
    department_id: '',
    course_id: '',
    year_level_id: '',
    letter: '',
});

const courseOptions = computed(() => props.courses
    .filter((course) => String(course.department_id) === String(form.department_id))
    .map((course) => ({
        value: String(course.id),
        label: course.name,
    })));

const years = computed(() => {
    const course = props.courses.find((item) => String(item.id) === String(form.course_id));
    return props.yearLevels
        .filter((year) => !course || Number(year.sort_order) <= Number(course.duration_years))
        .map((year) => ({ value: String(year.id), label: year.name }));
});

function options(items) {
    return items.map((item) => ({ value: String(item.id), label: item.name }));
}

function courseFor(courseId) {
    return props.courses.find((course) => String(course.id) === String(courseId));
}

function edit(section = null) {
    const course = section ? courseFor(section.course_id) : null;
    editing.value = section;
    form.defaults({
        department_id: course ? String(course.department_id) : '',
        course_id: section ? String(section.course_id) : '',
        year_level_id: section ? String(section.year_level_id) : '',
        letter: section?.letter ?? '',
    });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function save() {
    const visit = { preserveScroll: true, onSuccess: () => { open.value = false; } };
    if (editing.value) form.put(endpoint + '/' + editing.value.id, visit);
    else form.post(endpoint, visit);
}

function remove() {
    router.delete(endpoint + '/' + removing.value.id, {
        preserveScroll: true,
        onSuccess: () => { removing.value = null; },
    });
}
</script>

<template>
    <section class="space-y-4">
        <div class="flex items-center justify-end gap-3">
            <span class="rounded-full bg-muted px-3 py-1 text-sm">{{ sections.length }} sections</span>
            <Button @click="edit()">+ Add section</Button>
        </div>

        <div class="admin-table-scroll rounded-xl border bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted">
                    <tr>
                        <th v-for="label in ['Section', 'Department', 'Course', 'Year level', 'Actions']" :key="label"
                            scope="col" class="px-4 py-3 font-medium last:text-right">{{ label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="section in items" :key="section.id" class="border-b last:border-0">
                        <td class="px-4 py-3 font-medium">Section {{ section.letter }}</td>
                        <td class="px-4 py-3">{{ courseFor(section.course_id)?.department_name }}</td>
                        <td class="px-4 py-3">{{ courseFor(section.course_id)?.name }}</td>
                        <td class="px-4 py-3">{{ yearLevels.find((year) => year.id === section.year_level_id)?.name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" @click="edit(section)">Edit</Button>
                                <Button size="sm" variant="destructive" @click="removing = section">Delete</Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!sections.length"><td colspan="5" class="p-4 text-center">No sections found.</td></tr>
                </tbody>
            </table>
        </div>

        <Pagination :current-page="meta.current_page" :last-page="meta.last_page" :total="meta.total" :from="meta.from" :to="meta.to" @change="setPage" />

        <Dialog :show="open" :title="editing ? 'Edit section' : 'Add section'" @close="open = false">
            <form class="space-y-4" @submit.prevent="save">
                <div>
                    <label for="section-department" class="text-sm font-medium">Department</label>
                    <Select id="section-department" v-model="form.department_id" :options="options(departments)"
                        placeholder="Select department" @update:model-value="form.course_id = ''; form.year_level_id = ''" />
                    <InputError :message="form.errors.department_id" />
                </div>
                <div>
                    <label for="section-course" class="text-sm font-medium">Course</label>
                    <Select id="section-course" v-model="form.course_id" :options="courseOptions" :disabled="!form.department_id"
                        placeholder="Select course" @update:model-value="form.year_level_id = ''" />
                    <InputError :message="form.errors.course_id" />
                </div>
                <div>
                    <label for="section-year" class="text-sm font-medium">Year level</label>
                    <Select id="section-year" v-model="form.year_level_id" :options="years" :disabled="!form.course_id"
                        placeholder="Select year level" />
                    <InputError :message="form.errors.year_level_id" />
                </div>
                <div>
                    <label for="section-letter" class="text-sm font-medium">Section</label>
                    <Select id="section-letter" v-model="form.letter" :options="letters" placeholder="Select section" />
                    <InputError :message="form.errors.letter" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">Save section</Button>
                </div>
            </form>
        </Dialog>

        <Dialog :show="!!removing" title="Delete section" :description="'Remove Section ' + (removing?.letter ?? '') + ' from this course and year level?'" @close="removing = null">
            <div class="flex justify-end gap-2">
                <Button variant="outline" @click="removing = null">Cancel</Button>
                <Button variant="destructive" @click="remove">Delete section</Button>
            </div>
        </Dialog>
    </section>
</template>
