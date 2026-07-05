<script setup>
import { computed } from 'vue';
import { useFormDraftRegistry } from '@/composables/useFormDraftRegistry';
import { useFormDraftModal } from '@/composables/useFormDraftModal';
import { fabStackClass } from '@/composables/useFloatingFabStack';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    /** When true, render as compact topbar button; otherwise floating circle */
    inline: { type: Boolean, default: false },
    /** Stack index among floating FABs (0 = bottom, 1 = above calculator, etc.) */
    stackIndex: { type: Number, default: 1 },
});

const { t } = useTrans();
const { count } = useFormDraftRegistry();
const { openModal } = useFormDraftModal();

const floatingClass = computed(() => {
    if (props.inline) {
        return '';
    }
    return `fixed end-6 z-40 ${fabStackClass(props.stackIndex)}`;
});
</script>

<template>
    <template v-if="count > 0">
        <UChip v-if="inline" :text="String(count)" color="warning" size="sm">
            <UButton
                icon="i-heroicons-document-text"
                color="neutral"
                variant="ghost"
                :aria-label="t('common.unfinished_forms')"
                @click="openModal"
            />
        </UChip>

        <button
            v-else
            type="button"
            :class="floatingClass"
            class="flex size-12 items-center justify-center rounded-full bg-warning text-white shadow-lg ring-2 ring-default transition hover:scale-105"
            :aria-label="t('common.unfinished_forms')"
            @click="openModal"
        >
            <span class="relative">
                <UIcon name="i-heroicons-document-text" class="size-6" />
                <span class="absolute -top-2 -end-2 flex size-5 items-center justify-center rounded-full bg-error text-[10px] font-bold">
                    {{ count }}
                </span>
            </span>
        </button>
    </template>
</template>
