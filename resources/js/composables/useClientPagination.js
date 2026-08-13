import { computed, ref, unref, watch } from 'vue';

export const TABLE_PAGE_SIZE = 10;

export function useClientPagination(source, perPage = TABLE_PAGE_SIZE) {
    const page = ref(1);

    const all = computed(() => {
        const value = typeof source === 'function' ? source() : unref(source);
        return Array.isArray(value) ? value : [];
    });

    const total = computed(() => all.value.length);
    const lastPage = computed(() => Math.max(1, Math.ceil(total.value / perPage) || 1));

    watch(lastPage, (next) => {
        if (page.value > next) {
            page.value = next;
        }
    });

    const items = computed(() => {
        const start = (page.value - 1) * perPage;
        return all.value.slice(start, start + perPage);
    });

    const meta = computed(() => {
        const count = total.value;
        return {
            current_page: page.value,
            last_page: lastPage.value,
            per_page: perPage,
            total: count,
            from: count === 0 ? 0 : (page.value - 1) * perPage + 1,
            to: Math.min(page.value * perPage, count),
        };
    });

    function setPage(next) {
        const parsed = Number(next);
        if (!Number.isFinite(parsed) || parsed < 1) {
            return;
        }

        page.value = Math.min(Math.trunc(parsed), lastPage.value);
    }

    return { page, items, meta, setPage };
}
