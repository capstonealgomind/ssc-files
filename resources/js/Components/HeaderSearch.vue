<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const query = ref('');
const results = ref([]);
const loading = ref(false);
const panelOpen = ref(false);
const activeIndex = ref(-1);
const rootRef = ref(null);
const inputRef = ref(null);
const panelRef = ref(null);
const panelStyle = ref({});
let debounceTimer = null;
let searchRequestId = 0;

const hasQuery = computed(() => query.value.trim().length > 0);
const showPanel = computed(() => panelOpen.value && hasQuery.value);

const typeLabels = {
    page: 'Page',
    voter: 'Voter',
    candidate: 'Candidate',
    election: 'Election',
    ticket: 'Ticket',
    announcement: 'Announcement',
    reactivation: 'Reactivation',
    account: 'Account',
    registration: 'Registration',
    receipt: 'Receipt',
    setting: 'Setting',
};

watch(query, (value) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    const trimmed = value.trim();
    if (!trimmed) {
        results.value = [];
        loading.value = false;
        activeIndex.value = -1;
        panelOpen.value = false;
        return;
    }

    panelOpen.value = true;
    loading.value = true;
    activeIndex.value = -1;
    debounceTimer = setTimeout(() => {
        runSearch(trimmed);
    }, 220);
});

watch(showPanel, async (visible) => {
    if (!visible) {
        return;
    }
    await nextTick();
    updatePanelPosition();
});

async function runSearch(term) {
    const requestId = ++searchRequestId;

    try {
        const { data } = await window.axios.get('/search', {
            params: { q: term },
        });

        if (requestId !== searchRequestId) {
            return;
        }

        results.value = Array.isArray(data?.results) ? data.results : [];
        activeIndex.value = -1;
        panelOpen.value = true;
        await nextTick();
        updatePanelPosition();
    } catch {
        if (requestId !== searchRequestId) {
            return;
        }
        results.value = [];
    } finally {
        if (requestId === searchRequestId) {
            loading.value = false;
        }
    }
}

function updatePanelPosition() {
    const el = rootRef.value;
    if (!el) {
        return;
    }

    const rect = el.getBoundingClientRect();
    panelStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 6}px`,
        left: `${rect.left}px`,
        width: `${Math.max(rect.width, 280)}px`,
        zIndex: 80,
    };
}

function clearSearch() {
    query.value = '';
    results.value = [];
    panelOpen.value = false;
    activeIndex.value = -1;
    inputRef.value?.focus();
}

function closePanel() {
    panelOpen.value = false;
    activeIndex.value = -1;
}

function chooseResult(result) {
    if (!result?.href) {
        return;
    }

    query.value = '';
    results.value = [];
    panelOpen.value = false;
    activeIndex.value = -1;
    router.visit(result.href);
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        if (showPanel.value) {
            event.preventDefault();
            closePanel();
        }
        return;
    }

    // Never jump to a page on Enter — user must click a result option.
    if (event.key === 'Enter') {
        event.preventDefault();
        return;
    }

    if (!showPanel.value || !results.value.length) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = activeIndex.value < results.value.length - 1
            ? activeIndex.value + 1
            : 0;
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value = activeIndex.value > 0
            ? activeIndex.value - 1
            : results.value.length - 1;
    }
}

function onDocumentClick(event) {
    const target = event.target;
    if (rootRef.value?.contains(target) || panelRef.value?.contains(target)) {
        return;
    }
    closePanel();
}

function onWindowChange() {
    if (showPanel.value) {
        updatePanelPosition();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('resize', onWindowChange);
    window.addEventListener('scroll', onWindowChange, true);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('resize', onWindowChange);
    window.removeEventListener('scroll', onWindowChange, true);
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
});
</script>

<template>
    <div ref="rootRef" class="app-header-search relative min-w-0 flex-1 max-w-md">
        <div class="app-header-search-input-wrap flex items-center gap-2 rounded-md border px-2.5 py-1.5">
            <svg class="h-4 w-4 shrink-0 pointer-events-none" style="color: #001f3f;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input
                ref="inputRef"
                v-model="query"
                type="text"
                class="min-w-0 flex-1 bg-transparent text-sm outline-none"
                style="color: #001f3f;"
                placeholder="Search pages, tabs, voters, settings…"
                autocomplete="off"
                aria-label="Search the website"
                :aria-expanded="showPanel"
                aria-controls="app-header-search-results"
                @focus="panelOpen = hasQuery"
                @keydown="onKeydown"
            />
            <button
                v-if="query"
                type="button"
                class="text-xs font-medium shrink-0 px-1"
                style="color: hsl(240 3.8% 46.1%);"
                @click="clearSearch"
            >
                Clear
            </button>
        </div>

        <Teleport to="body">
            <div
                v-if="showPanel"
                id="app-header-search-results"
                ref="panelRef"
                class="app-header-search-panel overflow-hidden rounded-lg border shadow-lg"
                role="listbox"
                :style="{ ...panelStyle, backgroundColor: '#ffffff' }"
            >
                <div class="px-3 py-2 border-b text-[11px] font-medium uppercase tracking-wide" style="border-color: hsl(215 60% 25% / 0.12); color: hsl(240 3.8% 46.1%);">
                    Choose a result
                </div>

                <div v-if="loading" class="px-3 py-3 text-sm" style="color: hsl(240 3.8% 46.1%);">
                    Searching…
                </div>

                <div
                    v-else-if="!results.length"
                    class="px-3 py-3 text-sm"
                    style="color: hsl(240 3.8% 46.1%);"
                >
                    No results for “{{ query.trim() }}”
                </div>

                <button
                    v-for="(result, index) in results"
                    :key="`${result.type}-${result.href}-${result.title}`"
                    type="button"
                    role="option"
                    class="app-header-search-result w-full flex items-start gap-2.5 px-3 py-2.5 text-left transition-colors"
                    :class="{ 'is-active': index === activeIndex }"
                    :aria-selected="index === activeIndex"
                    @mouseenter="activeIndex = index"
                    @click="chooseResult(result)"
                >
                    <span class="app-header-search-type mt-0.5 shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                        {{ typeLabels[result.type] || result.type }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium truncate" style="color: #001f3f;">{{ result.title }}</span>
                        <span class="block text-xs truncate mt-0.5" style="color: hsl(240 3.8% 46.1%);">{{ result.subtitle }}</span>
                    </span>
                    <span class="shrink-0 text-[11px] mt-0.5" style="color: hsl(221 83% 45%);">Open</span>
                </button>
            </div>
        </Teleport>
    </div>
</template>
