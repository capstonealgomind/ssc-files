<script setup>
import { computed, nextTick, ref, watch } from "vue";

const props = defineProps({
    entries: {
        type: Array,
        default: () => [],
    },
});

/** Oldest of the latest 10 at top, newest at bottom — new votes flow upward. */
const displayEntries = computed(() => [...(props.entries ?? [])].reverse());

const knownIds = ref(new Set());
const enteringIds = ref(new Set());
const seeded = ref(false);

watch(
    displayEntries,
    async (rows) => {
        const next = rows ?? [];
        const ids = next.map((r) => r.id).filter((id) => id != null);

        if (!seeded.value) {
            knownIds.value = new Set(ids);
            seeded.value = true;
            return;
        }

        const fresh = ids.filter((id) => !knownIds.value.has(id));
        if (!fresh.length) {
            knownIds.value = new Set(ids);
            return;
        }

        enteringIds.value = new Set(fresh);
        knownIds.value = new Set(ids);
        await nextTick();
        window.setTimeout(() => {
            enteringIds.value = new Set();
        }, 900);
    },
    { immediate: true, deep: true },
);

function isEntering(id) {
    return enteringIds.value.has(id);
}

function rowKey(entry, index) {
    return (
        entry?.id ??
        `vote-${index}-${entry?.voter_id ?? ""}-${entry?.position ?? ""}`
    );
}
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <div class="flex items-center justify-between border-b px-5 py-4">
            <div>
                <h3
                    class="flex items-center gap-2 text-base font-semibold text-gray-900"
                >
                    Live Vote Entry
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700"
                    >
                        <span
                            class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                        />
                        Live
                    </span>
                </h3>
                <p class="mt-0.5 text-xs text-gray-500">
                    Real-time ballot submissions as voters cast their votes.
                </p>
            </div>
            <span
                class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600"
            >
                {{ displayEntries.length }} recent
                {{ displayEntries.length === 1 ? "entry" : "entries" }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr
                        class="border-b bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        <th class="px-5 py-3">Time</th>
                        <th class="px-5 py-3">Voter ID</th>
                        <th class="px-5 py-3">Department</th>
                        <th class="px-5 py-3">Position</th>
                        <th class="px-5 py-3">Candidate</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <TransitionGroup
                    name="ballot-feed"
                    tag="tbody"
                    class="ballot-feed-body"
                >
                    <tr
                        v-for="(entry, index) in displayEntries"
                        :key="rowKey(entry, index)"
                        class="border-b hover:bg-gray-50 ballot-feed-row"
                        :class="{ 'ballot-feed-row-new': isEntering(entry.id) }"
                    >
                        <td
                            class="whitespace-nowrap px-5 py-3 font-mono text-xs text-gray-600"
                        >
                            {{ entry.time }}
                        </td>
                        <td
                            class="whitespace-nowrap px-5 py-3 font-medium text-gray-900"
                        >
                            {{ entry.voter_id }}
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                            {{ entry.department }}
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                            {{ entry.position }}
                        </td>
                        <td
                            class="whitespace-nowrap px-5 py-3 font-medium text-gray-900"
                        >
                            {{ entry.candidate }}
                        </td>
                        <td class="whitespace-nowrap px-5 py-3">
                            <span
                                class="inline-flex rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-medium text-sky-700"
                            >
                                {{ entry.status }}
                            </span>
                        </td>
                    </tr>
                </TransitionGroup>
            </table>

            <div
                v-if="!displayEntries.length"
                class="px-5 py-10 text-center text-sm text-gray-500"
            >
                No votes recorded yet. Entries will appear here as ballots are
                cast.
            </div>
        </div>
    </div>
</template>
