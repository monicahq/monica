import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Serve the dev server over plain http. Left to itself the plugin
            // picks up the Herd certificate and switches to https, and a
            // browser that does not trust that certificate drops every asset
            // without saying so.
            detectTls: false,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
