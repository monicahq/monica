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
 * The slug a name would be given, for a form that wants to show one before the
 * server has settled it. It mirrors Laravel's `Str::slug` closely enough for a
 * preview, accents included; what is stored is always what the server decides.
 */
window.slugify = (value) =>
    (value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 64);

/*
 * Dragging the rows of one list into another order. Any screen whose rows are
 * numbered by a position can use it. The component sits on the
 * container of the rows and only keeps track of what is being dragged and what
 * it is hovering over, so the panel can grey the row that left and draw the line
 * where it would land.
 *
 * Saving is left to Blade: every row carries a hidden form aimed at the position
 * route, and the drop writes the position into it and submits it. That keeps the
 * csrf token, the method spoofing and the ajax target where the rest of the app
 * puts them.
 *
 * The rows are reached from the dropped-on element rather than from `$el`. Every
 * row declares an `x-data` of its own for the field it opens, so an expression
 * written in a row runs in a scope stack whose `$el` is that row, not the
 * container these methods belong to.
 */
Alpine.data('sortableRows', () => ({
    draggingId: null,
    hoveringId: null,

    start(event, id) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(id));
        this.draggingId = id;
    },

    over(event, id) {
        event.dataTransfer.dropEffect = 'move';
        this.hoveringId = id;
    },

    stop() {
        this.draggingId = null;
        this.hoveringId = null;
    },

    drop(event, targetId) {
        const draggedId = this.draggingId;
        const rows = event.currentTarget.closest('[data-sortable]');
        this.stop();

        if (! draggedId || draggedId === targetId || ! rows) {
            return;
        }

        const ids = Array.from(rows.querySelectorAll('[data-row-id]')).map((row) => Number(row.dataset.rowId));
        const from = ids.indexOf(draggedId);
        const to = ids.indexOf(targetId);

        if (from < 0 || to < 0) {
            return;
        }

        /*
         * A row always lands in front of the one it was dropped on. The server
         * takes it out of the list before putting it back, so the position it is
         * asked for is where the target sits once the dragged row is gone.
         */
        const form = rows.querySelector(`[data-position-form="${draggedId}"]`);
        form.querySelector('input[name="position"]').value = to > from ? to : to + 1;
        form.requestSubmit();
    },
}));

/*
 * Which columns of a table somebody chose to hide, kept in local storage so the
 * list looks the way they left it. It is a preference about looking rather than
 * about the account, so it stays in the browser and never reaches the server:
 * the same rows are always sent, and hiding one is a matter of not drawing it.
 *
 * The key is passed in by the screen, so two tables do not share one choice.
 */
Alpine.data('hiddenColumns', (key) => ({
    hidden: [],

    init() {
        try {
            this.hidden = JSON.parse(localStorage.getItem(key) ?? '[]');
        } catch (e) {
            // Local storage is unavailable in private mode on some browsers.
        }
    },

    shown(column) {
        return ! this.hidden.includes(column);
    },

    toggle(column) {
        this.hidden = this.shown(column)
            ? [...this.hidden, column]
            : this.hidden.filter((hidden) => hidden !== column);

        this.remember();
    },

    reset() {
        this.hidden = [];
        this.remember();
    },

    remember() {
        try {
            localStorage.setItem(key, JSON.stringify(this.hidden));
        } catch (e) {
            // Local storage is unavailable in private mode on some browsers.
        }
    },
}));

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
