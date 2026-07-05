<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import CalculatorResult from '@/components/CalculatorResult.vue';
import { useCalculator } from '@/composables/useCalculator';
import { useCalculatorModal } from '@/composables/useCalculatorModal';
import { useTrans } from '@/composables/useTrans';

const { t } = useTrans();
const page = usePage();
const { open, closeModal } = useCalculatorModal();

const exchangeRateProp = computed(() => page.props.money?.exchangeRate ?? 1);
const calc = useCalculator(exchangeRateProp);

const {
    mode,
    display,
    taxAmount,
    taxRate,
    discountAmount,
    discountRate,
    costPrice,
    sellingPrice,
    currencyAmount,
    currencyFrom,
    exchangeRate,
    businessResult,
} = calc;

const modes = [
    { id: 'standard', label: 'calculator_mode_standard' },
    { id: 'tax', label: 'calculator_mode_tax' },
    { id: 'discount', label: 'calculator_mode_discount' },
    { id: 'margin', label: 'calculator_mode_margin' },
    { id: 'currency', label: 'calculator_mode_currency' },
];

const standardKeys = [
    ['C', 'back', '%', '/'],
    ['7', '8', '9', '*'],
    ['4', '5', '6', '-'],
    ['1', '2', '3', '+'],
    ['±', '0', '.', '='],
];

function keyLabel(key) {
    if (key === 'back') return '⌫';
    if (key === '±') return '±';
    return key;
}

function handleKey(key) {
    switch (key) {
        case 'C':
            calc.clearAll();
            break;
        case 'back':
            calc.backspace();
            break;
        case '%':
            calc.inputPercent();
            break;
        case '±':
            calc.toggleSign();
            break;
        case '=':
            calc.equals();
            break;
        case '+':
        case '-':
        case '*':
        case '/':
            calc.performOperation(key);
            break;
        default:
            if (key === '.') {
                calc.inputDecimal();
            } else {
                calc.inputDigit(key);
            }
    }
}

function onWindowKeydown(event) {
    if (!open.value || mode.value !== 'standard') {
        return;
    }
    if (event.ctrlKey || event.metaKey || event.altKey) {
        return;
    }
    if (event.key === 'Escape') {
        closeModal();
        return;
    }
    calc.press(event.key);
    event.preventDefault();
}

watch(open, (isOpen) => {
    if (isOpen) {
        calc.resetStandard();
    }
});

onMounted(() => window.addEventListener('keydown', onWindowKeydown));
onUnmounted(() => window.removeEventListener('keydown', onWindowKeydown));

function toggleCurrencyFrom() {
    currencyFrom.value = currencyFrom.value === 'SDG' ? 'USD' : 'SDG';
}
</script>

<template>
    <UModal
        v-model:open="open"
        :title="t('preferences.calculator_title')"
        :ui="{ content: 'sm:max-w-sm' }"
    >
        <template #body>
            <div class="space-y-4">
                <div class="flex flex-wrap gap-1">
                    <UButton
                        v-for="m in modes"
                        :key="m.id"
                        size="xs"
                        :color="mode === m.id ? 'primary' : 'neutral'"
                        :variant="mode === m.id ? 'solid' : 'ghost'"
                        :label="t(`preferences.${m.label}`)"
                        @click="calc.setMode(m.id)"
                    />
                </div>

                <template v-if="mode === 'standard'">
                    <div class="flex items-center gap-2">
                        <UInput
                            v-model="display"
                            class="flex-1 font-mono text-end text-lg"
                            readonly
                        />
                        <UButton
                            icon="i-heroicons-clipboard-document"
                            color="neutral"
                            variant="ghost"
                            :aria-label="t('preferences.calculator_copy')"
                            @click="calc.copyResult()"
                        />
                    </div>

                    <div class="grid grid-cols-4 gap-1.5">
                        <UButton
                            v-for="key in standardKeys.flat()"
                            :key="key"
                            size="md"
                            :color="['+', '-', '*', '/', '='].includes(key) ? 'primary' : 'neutral'"
                            :variant="['+', '-', '*', '/', '='].includes(key) ? 'solid' : 'outline'"
                            class="font-mono"
                            :label="keyLabel(key)"
                            @click="handleKey(key)"
                        />
                    </div>

                    <div class="grid grid-cols-4 gap-1.5">
                        <UButton size="sm" color="neutral" variant="ghost" label="MC" @click="calc.memoryClear()" />
                        <UButton size="sm" color="neutral" variant="ghost" label="MR" @click="calc.memoryRecall()" />
                        <UButton size="sm" color="neutral" variant="ghost" label="M+" @click="calc.memoryAdd()" />
                        <UButton size="sm" color="neutral" variant="ghost" label="M−" @click="calc.memorySubtract()" />
                    </div>
                </template>

                <template v-else-if="mode === 'tax'">
                    <UFormField :label="t('preferences.calculator_amount')">
                        <UInput v-model="taxAmount" type="number" inputmode="decimal" />
                    </UFormField>
                    <UFormField :label="t('preferences.calculator_tax_rate')">
                        <UInput v-model="taxRate" type="number" inputmode="decimal" />
                    </UFormField>
                    <CalculatorResult :result="businessResult" @copy="calc.copyResult()" />
                </template>

                <template v-else-if="mode === 'discount'">
                    <UFormField :label="t('preferences.calculator_amount')">
                        <UInput v-model="discountAmount" type="number" inputmode="decimal" />
                    </UFormField>
                    <UFormField :label="t('preferences.calculator_discount_rate')">
                        <UInput v-model="discountRate" type="number" inputmode="decimal" />
                    </UFormField>
                    <CalculatorResult :result="businessResult" @copy="calc.copyResult()" />
                </template>

                <template v-else-if="mode === 'margin'">
                    <UFormField :label="t('preferences.calculator_cost')">
                        <UInput v-model="costPrice" type="number" inputmode="decimal" />
                    </UFormField>
                    <UFormField :label="t('preferences.calculator_selling')">
                        <UInput v-model="sellingPrice" type="number" inputmode="decimal" />
                    </UFormField>
                    <CalculatorResult :result="businessResult" @copy="calc.copyResult()" />
                </template>

                <template v-else-if="mode === 'currency'">
                    <div class="flex items-center gap-2">
                        <UFormField :label="t('preferences.calculator_amount')" class="flex-1">
                            <UInput v-model="currencyAmount" type="number" inputmode="decimal" />
                        </UFormField>
                        <UButton
                            class="mt-5 shrink-0"
                            color="neutral"
                            variant="outline"
                            :label="currencyFrom"
                            @click="toggleCurrencyFrom"
                        />
                    </div>
                    <p class="text-xs text-muted">
                        {{ t('preferences.calculator_rate_hint', { rate: exchangeRate }) }}
                    </p>
                    <CalculatorResult :result="businessResult" @copy="calc.copyResult()" />
                </template>
            </div>
        </template>
    </UModal>
</template>
