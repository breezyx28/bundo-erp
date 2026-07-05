import { computed, ref } from 'vue';

function parseNum(value) {
    const n = Number.parseFloat(String(value).replace(/,/g, ''));
    return Number.isFinite(n) ? n : 0;
}

function formatNum(value, decimals = 8) {
    const n = Number(value);
    if (!Number.isFinite(n)) {
        return '0';
    }
    const rounded = Number.parseFloat(n.toFixed(decimals));
    return String(rounded);
}

/**
 * Business calculator: standard keypad, tax/discount, margin, currency conversion.
 *
 * @param {import('vue').Ref<number>|number} exchangeRateRef SDG per 1 USD
 */
export function useCalculator(exchangeRateRef = 1) {
    const mode = ref('standard');
    const display = ref('0');
    const memory = ref(0);
    const previousValue = ref(null);
    const operator = ref(null);
    const waitingForOperand = ref(false);

    const taxAmount = ref('');
    const taxRate = ref('');
    const discountAmount = ref('');
    const discountRate = ref('');
    const costPrice = ref('');
    const sellingPrice = ref('');
    const currencyAmount = ref('');
    const currencyFrom = ref('SDG');

    const exchangeRate = computed(() => {
        const rate = typeof exchangeRateRef === 'object' && exchangeRateRef?.value != null
            ? exchangeRateRef.value
            : exchangeRateRef;
        return Number(rate) > 0 ? Number(rate) : 1;
    });

    const currentValue = computed(() => parseNum(display.value));

    const businessResult = computed(() => {
        if (mode.value === 'tax') {
            const amount = parseNum(taxAmount.value);
            const rate = parseNum(taxRate.value);
            const tax = amount * (rate / 100);
            return {
                primary: formatNum(amount + tax),
                lines: [
                    { label: 'tax', value: formatNum(tax) },
                    { label: 'net', value: formatNum(amount) },
                    { label: 'gross', value: formatNum(amount + tax) },
                ],
            };
        }

        if (mode.value === 'discount') {
            const amount = parseNum(discountAmount.value);
            const rate = parseNum(discountRate.value);
            const savings = amount * (rate / 100);
            return {
                primary: formatNum(amount - savings),
                lines: [
                    { label: 'savings', value: formatNum(savings) },
                    { label: 'original', value: formatNum(amount) },
                    { label: 'final', value: formatNum(amount - savings) },
                ],
            };
        }

        if (mode.value === 'margin') {
            const cost = parseNum(costPrice.value);
            const sell = parseNum(sellingPrice.value);
            const profit = sell - cost;
            const marginPct = sell > 0 ? (profit / sell) * 100 : 0;
            const markupPct = cost > 0 ? (profit / cost) * 100 : 0;
            return {
                primary: formatNum(marginPct),
                lines: [
                    { label: 'markup', value: formatNum(markupPct) },
                    { label: 'profit', value: formatNum(profit) },
                    { label: 'margin', value: formatNum(marginPct) },
                ],
            };
        }

        if (mode.value === 'currency') {
            const amount = parseNum(currencyAmount.value);
            const rate = exchangeRate.value;
            const converted = currencyFrom.value === 'SDG'
                ? amount / rate
                : amount * rate;
            return {
                primary: formatNum(converted),
                lines: [
                    {
                        label: currencyFrom.value === 'SDG' ? 'usd' : 'sdg',
                        value: formatNum(converted),
                    },
                ],
            };
        }

        return { primary: display.value, lines: [] };
    });

    function resetStandard() {
        display.value = '0';
        previousValue.value = null;
        operator.value = null;
        waitingForOperand.value = false;
    }

    function inputDigit(digit) {
        if (waitingForOperand.value) {
            display.value = String(digit);
            waitingForOperand.value = false;
            return;
        }
        display.value = display.value === '0' ? String(digit) : display.value + digit;
    }

    function inputDecimal() {
        if (waitingForOperand.value) {
            display.value = '0.';
            waitingForOperand.value = false;
            return;
        }
        if (!display.value.includes('.')) {
            display.value += '.';
        }
    }

    function toggleSign() {
        if (display.value.startsWith('-')) {
            display.value = display.value.slice(1);
        } else if (display.value !== '0') {
            display.value = `-${display.value}`;
        }
    }

    function inputPercent() {
        display.value = formatNum(currentValue.value / 100);
    }

    function performOperation(nextOperator) {
        const input = currentValue.value;

        if (previousValue.value == null) {
            previousValue.value = input;
        } else if (operator.value) {
            const result = calculate(previousValue.value, input, operator.value);
            display.value = formatNum(result);
            previousValue.value = result;
        }

        waitingForOperand.value = true;
        operator.value = nextOperator;
    }

    function calculate(first, second, op) {
        switch (op) {
            case '+':
                return first + second;
            case '-':
                return first - second;
            case '*':
                return first * second;
            case '/':
                return second === 0 ? 0 : first / second;
            default:
                return second;
        }
    }

    function equals() {
        if (operator.value == null || previousValue.value == null) {
            return;
        }
        const result = calculate(previousValue.value, currentValue.value, operator.value);
        display.value = formatNum(result);
        previousValue.value = null;
        operator.value = null;
        waitingForOperand.value = true;
    }

    function clearAll() {
        resetStandard();
    }

    function backspace() {
        if (waitingForOperand.value) {
            return;
        }
        if (display.value.length <= 1 || (display.value.length === 2 && display.value.startsWith('-'))) {
            display.value = '0';
            return;
        }
        display.value = display.value.slice(0, -1);
    }

    function memoryClear() {
        memory.value = 0;
    }

    function memoryRecall() {
        display.value = formatNum(memory.value);
        waitingForOperand.value = true;
    }

    function memoryAdd() {
        memory.value += currentValue.value;
    }

    function memorySubtract() {
        memory.value -= currentValue.value;
    }

    function setMode(nextMode) {
        mode.value = nextMode;
    }

    function press(key) {
        if (mode.value !== 'standard') {
            return;
        }

        if (key >= '0' && key <= '9') {
            inputDigit(key);
            return;
        }

        switch (key) {
            case '.':
                inputDecimal();
                break;
            case '+':
            case '-':
            case '*':
            case '/':
                performOperation(key);
                break;
            case '=':
            case 'Enter':
                equals();
                break;
            case 'Backspace':
                backspace();
                break;
            case 'Escape':
                clearAll();
                break;
            case '%':
                inputPercent();
                break;
            default:
                break;
        }
    }

    function copyResult() {
        const text = mode.value === 'standard' ? display.value : businessResult.value.primary;
        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(text);
        }
    }

    return {
        mode,
        display,
        memory,
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
        setMode,
        inputDigit,
        inputDecimal,
        toggleSign,
        inputPercent,
        performOperation,
        equals,
        clearAll,
        backspace,
        memoryClear,
        memoryRecall,
        memoryAdd,
        memorySubtract,
        press,
        copyResult,
        resetStandard,
    };
}
