import ApexCharts from 'apexcharts';
import 'flyonui/flyonui';

window.ApexCharts = ApexCharts;

function reinitFlyonUI() {
    if (typeof window.HSStaticMethods !== 'undefined') {
        window.HSStaticMethods.autoInit();
    }
}

document.addEventListener('livewire:navigated', reinitFlyonUI);

document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', () => {
        reinitFlyonUI();
    });
});

reinitFlyonUI();
