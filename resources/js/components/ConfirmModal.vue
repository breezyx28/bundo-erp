<script setup>
import { useTrans } from '@/composables/useTrans';

const open = defineModel('open', { type: Boolean, default: false });

defineProps({
    title: { type: String, default: '' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: '' },
    cancelLabel: { type: String, default: '' },
    confirmColor: { type: String, default: 'error' },
    confirmIcon: { type: String, default: 'i-lucide-trash-2' },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);

const { t } = useTrans();

function cancel() {
    open.value = false;
    emit('cancel');
}
</script>

<template>
    <UModal
        v-model:open="open"
        :title="title || t('common.confirm_delete')"
        :ui="{ content: 'sm:max-w-md' }"
    >
        <template #body>
            <p class="text-sm text-toned">
                {{ message || t('common.confirm_delete') }}
            </p>
        </template>

        <template #footer>
            <div class="flex w-full items-center justify-end gap-2">
                <UButton
                    color="neutral"
                    variant="ghost"
                    :label="cancelLabel || t('common.cancel')"
                    @click="cancel"
                />
                <UButton
                    :color="confirmColor"
                    :icon="confirmIcon"
                    :label="confirmLabel || t('common.delete')"
                    :loading="loading"
                    @click="emit('confirm')"
                />
            </div>
        </template>
    </UModal>
</template>
