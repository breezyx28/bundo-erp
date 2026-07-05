<script setup>
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useFormDraftRegistry } from '@/composables/useFormDraftRegistry';
import { useFormDraftModal } from '@/composables/useFormDraftModal';
import { useTrans } from '@/composables/useTrans';
import { watch } from 'vue';

const { t } = useTrans();
const { drafts, remove, reload } = useFormDraftRegistry();
const { open, closeModal } = useFormDraftModal();

watch(open, (isOpen) => {
    if (isOpen) {
        reload();
    }
});

function timeAgo(iso) {
    if (!iso) return '';
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return t('common.draft_just_now');
    if (mins < 60) return `${mins}m`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h`;
    return `${Math.floor(hours / 24)}d`;
}

function continueDraft(draft) {
    closeModal();
    const base = route(draft.routeName, draft.routeParams ?? {});
    const separator = base.includes('?') ? '&' : '?';
    router.visit(`${base}${separator}draft=${encodeURIComponent(draft.key)}`);
}

function dismissDraft(key, event) {
    event?.stopPropagation();
    remove(key);
}

router.on('navigate', () => reload());
</script>

<template>
    <UModal v-model:open="open" :title="t('common.unfinished_forms')" :ui="{ content: 'sm:max-w-md' }">
        <template #body>
            <div v-if="drafts.length" class="divide-y divide-default">
                <div
                    v-for="draft in drafts"
                    :key="draft.key"
                    class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-highlighted">{{ draft.label }}</p>
                        <p class="text-xs text-dimmed">{{ timeAgo(draft.updatedAt) }}</p>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <UButton size="xs" color="primary" :label="t('common.continue_draft')" @click="continueDraft(draft)" />
                        <UButton size="xs" color="neutral" variant="ghost" icon="i-heroicons-x-mark" @click="dismissDraft(draft.key, $event)" />
                    </div>
                </div>
            </div>
            <p v-else class="py-4 text-center text-sm text-dimmed">
                {{ t('common.no_drafts') }}
            </p>
        </template>
    </UModal>
</template>
