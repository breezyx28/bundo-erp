<script setup>
import { useTrans } from '@/composables/useTrans';

defineProps({
    result: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['copy']);
const { t } = useTrans();

const lineLabels = {
    tax: 'preferences.calculator_line_tax',
    net: 'preferences.calculator_line_net',
    gross: 'preferences.calculator_line_gross',
    savings: 'preferences.calculator_line_savings',
    original: 'preferences.calculator_line_original',
    final: 'preferences.calculator_line_final',
    markup: 'preferences.calculator_line_markup',
    profit: 'preferences.calculator_line_profit',
    margin: 'preferences.calculator_line_margin',
    usd: 'preferences.calculator_line_usd',
    sdg: 'preferences.calculator_line_sdg',
};
</script>

<template>
    <div class="rounded-lg border border-default bg-elevated p-3">
        <div class="flex items-center justify-between gap-2">
            <span class="font-mono text-2xl font-semibold text-highlighted">{{ result.primary }}</span>
            <UButton
                icon="i-heroicons-clipboard-document"
                color="neutral"
                variant="ghost"
                size="sm"
                :aria-label="t('preferences.calculator_copy')"
                @click="emit('copy')"
            />
        </div>
        <ul v-if="result.lines?.length" class="mt-2 space-y-1 text-sm text-muted">
            <li v-for="line in result.lines" :key="line.label" class="flex justify-between gap-2">
                <span>{{ t(lineLabels[line.label] ?? line.label) }}</span>
                <span class="font-mono text-highlighted">{{ line.value }}</span>
            </li>
        </ul>
    </div>
</template>
