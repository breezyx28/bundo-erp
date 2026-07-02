import { ref } from 'vue';

/**
 * Currency formatting + SDG→display conversion, mirroring the server Money helper
 * and the dashboard/report currency toggle.
 *
 * @param {Record<string, {symbol: string, decimals: number}>} currencies
 * @param {number} rate  SDG per USD exchange rate.
 * @param {string} initial  Starting currency code.
 */
export function useMoney(currencies, rate, initial = 'SDG') {
    const currency = ref(initial);

    function toggle() {
        currency.value = currency.value === 'SDG' ? 'USD' : 'SDG';
    }

    function conv(sdg) {
        const r = Number(rate) || 0;
        const amount = Number(sdg) || 0;
        return currency.value === 'USD' && r > 0 ? amount / r : amount;
    }

    function fmt(sdg) {
        const config = currencies[currency.value] ?? { symbol: currency.value, decimals: 2 };
        return `${config.symbol} ${conv(sdg).toLocaleString('en-US', {
            minimumFractionDigits: config.decimals,
            maximumFractionDigits: config.decimals,
        })}`;
    }

    return { currency, toggle, conv, fmt };
}
