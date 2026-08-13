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
             *
             * The subsets are asked for because a name is not a translation. Latin
             * alone covers the six languages the interface is in, and stops at the
             * people written down in it: the browser falls back a glyph at a time,
             * so Michał and Nguyễn are each set in two typefaces inside one word,
             * and the display face is the one that draws people's names.
             *
             * Each subset is a font face of its own with its own unicode-range, so
             * a page holding none of those letters still fetches only Latin. The
             * cost is the bundle on disk rather than anything a visitor waits for.
             */
            fonts: [
                bunny('Bricolage Grotesque', {
                    weights: [600],
                    subsets: ['latin', 'latin-ext', 'vietnamese'],
                }),
                bunny('Hanken Grotesk', {
                    weights: [400, 500, 600, 700],
                    subsets: ['latin', 'latin-ext', 'vietnamese'],
                }),
                bunny('IBM Plex Mono', {
                    weights: [400, 500],
                    subsets: ['latin', 'latin-ext', 'vietnamese'],
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
