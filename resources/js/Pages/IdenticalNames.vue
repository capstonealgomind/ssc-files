<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';

defineProps({
    pairs: {
        type: Array,
        default: () => [],
    },
});

const flagTarget = ref(null);
const flagging = ref(false);

function openFlag(account) {
    flagTarget.value = account;
}

function closeFlag() {
    if (flagging.value) {
        return;
    }

    flagTarget.value = null;
}

function confirmFlag() {
    if (!flagTarget.value || flagging.value) {
        return;
    }

    flagging.value = true;
    router.post(`/voters/${flagTarget.value.id}/identical-name-flag`, {}, {
        preserveScroll: true,
        onFinish: () => {
            flagging.value = false;
            flagTarget.value = null;
        },
    });
}

function removeFlag(account) {
    router.delete(`/voters/${account.id}/identical-name-flag`, {
        preserveScroll: true,
    });
}

function differenceLabel(distance) {
    return distance === 1 ? '1 letter different' : `${distance} letters different`;
}
</script>

<template>
    <AppLayout>
        <Head title="Identical names" />
        <template #header>
            <h1 class="text-base font-semibold" style="color:hsl(240 10% 3.9%);">Identical names</h1>
        </template>

        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <p class="max-w-3xl text-sm" style="color: hsl(240 3.8% 46.1%);">
                    These accounts use names that are almost the same, with only one or two letters different.
                    One person could be trying to register twice and vote more than once.
                    Compare the existing account with the new one, then flag the new account if it should not vote.
                </p>
                <Link
                    href="/voters"
                    class="inline-flex h-8 items-center rounded-md border px-3 text-xs font-medium"
                    style="border-color: hsl(240 5.9% 90%); color: hsl(240 10% 3.9%); background: #fff;"
                >
                    Back to voters
                </Link>
            </div>

            <div
                v-if="pairs.length === 0"
                class="rounded-lg border px-4 py-10 text-center text-sm"
                style="border-color: hsl(240 5.9% 90%); background: #fff; color: hsl(240 3.8% 46.1%);"
            >
                No accounts have names this close right now.
            </div>

            <article
                v-for="(pair, index) in pairs"
                :key="`${pair.existing.id}-${pair.newer.id}`"
                class="rounded-lg border"
                style="border-color: hsl(240 5.9% 90%); background: #fff;"
            >
                <div class="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3" style="border-color: hsl(240 5.9% 90%);">
                    <p class="text-sm font-semibold" style="color: hsl(240 10% 3.9%);">
                        Pair {{ index + 1 }}
                    </p>
                    <span
                        class="rounded-full px-2.5 py-1 text-xs font-semibold"
                        style="background: hsl(38 92% 94%); color: hsl(25 75% 30%);"
                    >
                        {{ differenceLabel(pair.distance) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <section class="border-b p-4 lg:border-b-0 lg:border-r" style="border-color: hsl(240 5.9% 90%);">
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color: hsl(240 3.8% 46.1%);">Existing account</p>
                        <h2 class="mt-2 text-base font-semibold" style="color: hsl(240 10% 3.9%);">{{ pair.existing.name }}</h2>
                        <dl class="mt-3 space-y-1 text-sm" style="color: hsl(240 10% 3.9%);">
                            <div><span style="color: hsl(240 3.8% 46.1%);">Voter ID:</span> {{ pair.existing.voter_id_number || '—' }}</div>
                            <div><span style="color: hsl(240 3.8% 46.1%);">Student ID:</span> {{ pair.existing.student_id_number || '—' }}</div>
                            <div><span style="color: hsl(240 3.8% 46.1%);">Email:</span> {{ pair.existing.email || '—' }}</div>
                            <div><span style="color: hsl(240 3.8% 46.1%);">Department:</span> {{ pair.existing.department || '—' }}</div>
                            <div><span style="color: hsl(240 3.8% 46.1%);">Course:</span> {{ pair.existing.course || '—' }}</div>
                            <div><span style="color: hsl(240 3.8% 46.1%);">Registered:</span> {{ pair.existing.registered_at || '—' }}</div>
                        </dl>
                        <Link :href="`/voters/${pair.existing.id}`" class="mt-4 inline-flex text-sm font-medium underline underline-offset-2" style="color: hsl(221 83% 40%);">
                            Review existing account
                        </Link>
                    </section>

                    <section class="p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color: hsl(240 3.8% 46.1%);">New account</p>
                        <h2 class="mt-2 text-base font-semibold" style="color: hsl(240 10% 3.9%);">{{ pair.newer.name }}</h2>
                        <dl class="mt-3 space-y-1 text-sm" style="color: hsl(240 10% 3.9%);">
                            <div><span style="color: hsl(240 3.8% 46.1%);">Voter ID:</span> {{ pair.newer.voter_id_number || '—' }}</div>
                            <div><span style="color: hsl(240 3.8% 46.1%);">Student ID:</span> {{ pair.newer.student_id_number || '—' }}</div>
                            <div><span style="color: hsl(240 3.8% 46.1%);">Email:</span> {{ pair.newer.email || '—' }}</div>
                            <div><span style="color: hsl(240 3.8% 46.1%);">Department:</span> {{ pair.newer.department || '—' }}</div>
                            <div><span style="color: hsl(240 3.8% 46.1%);">Course:</span> {{ pair.newer.course || '—' }}</div>
                            <div><span style="color: hsl(240 3.8% 46.1%);">Registered:</span> {{ pair.newer.registered_at || '—' }}</div>
                        </dl>
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <Link :href="`/voters/${pair.newer.id}`" class="inline-flex text-sm font-medium underline underline-offset-2" style="color: hsl(221 83% 40%);">
                                Review new account
                            </Link>
                            <Button
                                v-if="!pair.newer.identical_name_flagged"
                                size="sm"
                                variant="destructive"
                                @click="openFlag(pair.newer)"
                            >
                                Flag new account
                            </Button>
                            <template v-else>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" style="background: hsl(25 95% 94%); color: hsl(25 75% 30%);">
                                    Flagged — cannot vote
                                </span>
                                <Button size="sm" variant="outline" @click="removeFlag(pair.newer)">
                                    Remove flag
                                </Button>
                            </template>
                        </div>
                    </section>
                </div>
            </article>
        </div>

        <Dialog
            :show="!!flagTarget"
            title="Flag this new account"
            description="This account will not be able to vote. Use this when the new name is the same person as the existing account."
            @close="closeFlag"
        >
            <p class="text-sm" style="color: hsl(240 10% 3.9%);">
                Flag <span class="font-semibold">{{ flagTarget?.name }}</span>?
            </p>
            <div class="mt-4 flex justify-end gap-2">
                <Button variant="outline" :disabled="flagging" @click="closeFlag">Cancel</Button>
                <Button variant="destructive" :disabled="flagging" @click="confirmFlag">
                    {{ flagging ? 'Flagging...' : 'Flag account' }}
                </Button>
            </div>
        </Dialog>
    </AppLayout>
</template>
