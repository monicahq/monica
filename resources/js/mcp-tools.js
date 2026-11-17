/**
 * The tools of one family: which rows are unfolded to show their
 * parameters. A link to a tool, from the contents or from elsewhere, unfolds
 * its row on arrival. Each row copies its tool as Markdown.
 */
export default (names) => ({
    open: [],
    copied: null,
    failed: null,

    init() {
        this.reveal(window.location.hash);
        window.addEventListener('hashchange', () => this.reveal(window.location.hash));
    },

    reveal(hash) {
        const name = hash.replace(/^#tool-/, '');

        if (names.includes(name) && ! this.open.includes(name)) {
            this.open.push(name);
        }
    },

    copyTool(name, markdown) {
        window.copyText(markdown).then((ok) => {
            this.copied = ok ? name : null;
            this.failed = ok ? null : name;
            setTimeout(() => {
                if (this.copied === name) {
                    this.copied = null;
                }
            }, 1600);
        });
    },

    copyLabel(name) {
        if (this.copied === name) {
            return 'Copied';
        }

        if (this.failed === name) {
            return 'Could not copy';
        }

        return 'Copy as Markdown';
    },

    isOpen(name) {
        return this.open.includes(name);
    },

    toggle(name) {
        this.open = this.isOpen(name) ? this.open.filter((each) => each !== name) : [...this.open, name];
    },

    get allOpen() {
        return names.every((name) => this.open.includes(name));
    },

    toggleAll() {
        this.open = this.allOpen ? [] : [...names];
    },
});
