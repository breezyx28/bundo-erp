import '../css/inertia.css';

import { createInertiaApp } from '@inertiajs/vue3';
import ui from '@nuxt/ui/vue-plugin';
import { addCollection } from '@iconify/vue';
import heroicons from '@iconify-json/heroicons/icons.json';
import lucide from '@iconify-json/lucide/icons.json';
import tabler from '@iconify-json/tabler/icons.json';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { ZiggyVue } from 'ziggy-js';

// Register icon collections locally so @nuxt/ui's UIcon resolves them from the
// bundle instead of the Iconify HTTP API (which the strict CSP blocks).
addCollection(heroicons);
addCollection(lucide);
addCollection(tabler);

const appName = import.meta.env.VITE_APP_NAME || 'Mazin Shoes';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ui)
            .use(ZiggyVue)
            .use(VueApexCharts)
            .mount(el);
    },
    progress: {
        color: '#228c70',
    },
});
