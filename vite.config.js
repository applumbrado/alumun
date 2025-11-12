import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
// import {inject} from "vue";
// inject({
//     $: 'jquery',
//     jQuery: 'jquery',
// }),


export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
                reactivityTransform: true,
            },
        }),
    ],
    server: {
        // host: '192.168.56.88',
        host: '192.168.255.2',
        // host: true,
        mimetype: 'text/html',
        watch: {
            usePolling: true,
        },
    },
    optimizeDeps: {
        include: ['jquery', 'select2', 'datatables.net-vue3', 'datatables.net-dt'],
    },
    build: {
        chunkSizeWarningLimit: 10000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return 'vendor'
                    }
                }
            }
        }
    }
});
