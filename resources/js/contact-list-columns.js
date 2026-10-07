/**
 * The editor for the columns of the list of people: which ones show, what
 * they are called and in what order. Nothing is saved until the form is sent.
 *
 * The columns live in the component's state and the markup is drawn from it,
 * so moving a column is moving an item of an array. A row is draggable only
 * while its grip is held, since a draggable row stops the heading input inside
 * it from selecting text in some browsers. The footer sits outside
 * the component, so the two talk through window events: the editor says when
 * something changed, and the footer asks it to put the defaults back. The
 * sentences it announces arrive translated, with their placeholders left in.
 */
export default (columns, defaults, wordings) => ({
    columns: columns.map((column) => ({ ...column })),
    armed: null,
    dragging: null,
    over: null,
    announcement: '',

    init() {
        this.reset = () => {
            this.columns = defaults.map((column) => ({ ...column }));
            this.changed();
        };

        window.addEventListener('contact-list-columns-reset', this.reset);
    },

    destroy() {
        window.removeEventListener('contact-list-columns-reset', this.reset);
    },

    changed() {
        window.dispatchEvent(new CustomEvent('contact-list-columns-changed'));
    },

    has(id) {
        return this.columns.some((column) => column.id === id);
    },

    add(column) {
        if (this.has(column.id)) {
            return;
        }

        this.columns.push({ ...column });
        this.changed();
        this.announce(wordings.added.replace(':heading', column.defaultHeading));
    },

    remove(index) {
        const [removed] = this.columns.splice(index, 1);

        this.changed();
        this.announce(wordings.removed.replace(':heading', removed.heading || removed.defaultHeading));
    },

    move(from, to) {
        if (to < 0 || to >= this.columns.length || from === to) {
            return;
        }

        const [moved] = this.columns.splice(from, 1);

        this.columns.splice(to, 0, moved);
        this.changed();
        this.announce(
            wordings.moved
                .replace(':position', to + 2)
                .replace(':total', this.columns.length + 1),
        );
    },

    moveByKey(event, index) {
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.move(index, index - 1);
            this.$nextTick(() => this.focusGrip(event.target, index - 1));
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.move(index, index + 1);
            this.$nextTick(() => this.focusGrip(event.target, index + 1));
        }
    },

    focusGrip(from, index) {
        const grips = from.closest('[data-column-editor]').querySelectorAll('[data-grip]');

        grips[Math.max(0, Math.min(index, grips.length - 1))]?.focus();
    },

    start(event, index) {
        this.dragging = index;
        event.dataTransfer.effectAllowed = 'move';
    },

    drop(index) {
        if (this.dragging !== null) {
            this.move(this.dragging, index);
        }

        this.stop();
    },

    stop() {
        this.armed = null;
        this.dragging = null;
        this.over = null;
    },

    announce(wording) {
        this.announcement = '';
        this.$nextTick(() => {
            this.announcement = wording;
        });
    },
});
