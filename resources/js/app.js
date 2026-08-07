import 'instant.page';

import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';
import morph from '@alpinejs/morph';
import ajax from '@imacrayon/alpine-ajax';

window.Turbo = Turbo;

/*
 * Turbo only takes over the links that ask for it, with data-turbo="true". Everything
 * else, every form included, keeps the browser's own behaviour. Turning a screen into a
 * Turbo one is therefore a deliberate act, rather than something that happens to it.
 */
Turbo.session.drive = false;

window.Alpine = Alpine;

Alpine.plugin(morph);
Alpine.plugin(ajax);

/*
 * Morph patches the DOM that is already there instead of replacing it, so the Alpine state
 * hanging off a node survives an update, and a form nested inside the region it refreshes
 * stays connected once that region has been updated.
 */
ajax.configure({ mergeStrategy: 'morph' });

/*
 * The files in `fileList` bigger than maxKilobytes. An upload form uses it to
 * turn an oversized picture away in the browser, with something readable to
 * say, rather than letting the request go and bounce off the web server.
 */
window.oversizedFiles = (fileList, maxKilobytes) =>
    Array.from(fileList ?? []).filter((file) => file.size > maxKilobytes * 1024);

/*
 * The theme the visitor picked, kept in local storage so it survives a reload.
 * The `.dark` class on <html> is what every token in app.css reads, and the
 * inline script in partials/meta sets it before the first paint so there is no
 * flash of the wrong theme. This store only has to keep the two in sync.
 *
 * Turbo never replaces <html>, so the theme also survives a Turbo navigation.
 */
Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),

    set(dark) {
        this.dark = dark;
        document.documentElement.classList.toggle('dark', dark);

        try {
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        } catch (e) {
            // Local storage is unavailable in private mode on some browsers.
        }
    },

    toggle() {
        this.set(! this.dark);
    },
});

/*
 * Turbo fires turbo:load on the first paint as well as after every navigation it drives.
 * The first one starts Alpine, which walks the whole page; the ones after it only have to
 * walk the body Turbo has just swapped in.
 */
document.addEventListener('turbo:load', () => {
    if (! document.documentElement.dataset.alpine) {
        document.documentElement.dataset.alpine = 'started';
        Alpine.start();

        return;
    }

    Alpine.initTree(document.body);
});
