<script setup>
import { fabStackClass } from '@/composables/useFloatingFabStack';
import { useCalculatorModal } from '@/composables/useCalculatorModal';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    stackIndex: { type: Number, default: 0 },
});

const { t } = useTrans();
const { openModal } = useCalculatorModal();
</script>

<template>
    <UButton
        icon="i-heroicons-calculator"
        size="xl"
        :class="['fixed end-6 z-40 rounded-full shadow-xl', fabStackClass(props.stackIndex)]"
        :aria-label="t('preferences.calculator_open')"
        @click="openModal"
    />
</template>
