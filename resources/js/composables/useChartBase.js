/**
 * Shared ApexCharts base options honoring the tenant primary/secondary CSS vars.
 * Mirrors the old Alpine dashCharts.base().
 */
export function chartBase() {
    const styles = getComputedStyle(document.documentElement);
    const primary = styles.getPropertyValue('--color-primary').trim()
        || styles.getPropertyValue('--ui-primary').trim()
        || '#228c70';
    const secondary = styles.getPropertyValue('--color-secondary').trim()
        || styles.getPropertyValue('--ui-secondary').trim()
        || '#1a6f59';

    return {
        chart: { fontFamily: 'inherit', toolbar: { show: false }, animations: { speed: 350 } },
        colors: [primary, '#b91c1c', secondary, '#b45309', '#2563eb'],
        grid: { borderColor: 'rgba(29, 42, 42, 0.08)', strokeDashArray: 4 },
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontSize: '11px', markers: { size: 4 } },
        stroke: { width: 2, curve: 'smooth' },
    };
}
