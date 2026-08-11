import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
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
            /*
             * Bundled rather than fetched from a font service at runtime, so a page
             * never waits on somebody else's server and no visitor is announced to
             * one. Bricolage Grotesque is display type and only ever sets a title,
             * so it needs the one weight the titles are set in.
             */
            fonts: [
                bunny('Bricolage Grotesque', {
                    weights: [600],
                }),
                bunny('Hanken Grotesk', {
                    weights: [400, 500, 600, 700],
                }),
                bunny('IBM Plex Mono', {
                    weights: [400, 500],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
