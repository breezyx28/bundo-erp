import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import laravel from 'laravel-vite-plugin';
import { fontsource } from 'laravel-vite-plugin/fonts';
import vue from '@vitejs/plugin-vue';
import ui from '@nuxt/ui/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/inertia.js',
            ],
            refresh: true,
            fonts: [
                fontsource('Roboto', {
                    weights: [400, 500, 700],
                }),
            ],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // Nuxt UI bundles @tailwindcss/vite internally, so we do NOT add
        // a standalone tailwindcss() plugin here (would double-register).
        ui({
            router: 'inertia',
            colorMode: false,
            ui: {
                colors: {
                    primary: 'emerald',
                    secondary: 'teal',
                    neutral: 'slate',
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            'ziggy-js': fileURLToPath(new URL('./vendor/tightenco/ziggy', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
