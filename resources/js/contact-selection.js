/**
 * The people ticked on one page of the contact list, and the toolbar that acts on them. The count is
 * worded in the reader's language from wordings the server translated, one per plural category, so
 * nothing here compares a number to one.
 */
export default (config) => ({
    selected: [],
    category: null,
    list: null,
    touched: false,

    init() {
        this.list = this.$el;
    },

    get count() {
        return this.selected.length;
    },

    get uuids() {
        return [...this.list.querySelectorAll('[data-contact-uuid]')].map((row) => row.dataset.contactUuid);
    },

    get allChecked() {
        return this.uuids.length > 0 && this.uuids.every((uuid) => this.selected.includes(uuid));
    },

    get someChecked() {
        return this.count > 0 && !this.allChecked;
    },

    get headingState() {
        if (this.allChecked) {
            return 'true';
        }

        return this.someChecked ? 'mixed' : 'false';
    },

    get announcement() {
        if (this.count > 0) {
            return this.label;
        }

        return this.touched ? config.nothingSelected : '';
    },

    get label() {
        const category = new Intl.PluralRules(config.locale).select(this.count);
        const wording = config.wordings[category] ?? config.wordings.other ?? Object.values(config.wordings)[0];

        return wording.replace(':n', this.count);
    },

    isSelected(uuid) {
        return this.selected.includes(uuid);
    },

    toggle(uuid) {
        this.touched = true;
        this.selected = this.isSelected(uuid)
            ? this.selected.filter((selected) => selected !== uuid)
            : [...this.selected, uuid];
    },

    toggleAll() {
        this.touched = true;
        this.selected = this.allChecked ? [] : this.uuids;
    },

    clear() {
        this.touched = true;
        this.selected = [];
        this.$nextTick(() => this.list.querySelector('[data-select-all]')?.focus());
    },

    escape() {
        if (this.count === 0 || this.list.querySelector('[aria-haspopup="menu"][aria-expanded="true"]')) {
            return;
        }

        this.clear();
    },

    rowClick(event, uuid) {
        const fromKeyboard = event.detail === 0;
        const opensElsewhere = event.metaKey || event.ctrlKey || event.shiftKey;

        if (this.count === 0 || fromKeyboard || opensElsewhere || !event.target.closest('a')) {
            return;
        }

        event.preventDefault();
        this.toggle(uuid);
    },

    changeCategory(category, form) {
        this.category = category;
        this.$nextTick(() => form.requestSubmit());
    },
});
