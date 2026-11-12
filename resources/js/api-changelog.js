/**
 * The filters of the API changelog: a search, a kind and breaking changes only. Rows, groups and
 * releases that hold nothing matching are hidden, without asking the server again.
 */
export default () => ({
    query: '',
    kind: 'All',
    breakingOnly: false,
    rows: [],

    init() {
        this.rows = [...this.$el.querySelectorAll('[data-kind]')];
    },

    matches(row) {
        if (this.kind !== 'All' && row.dataset.kind !== this.kind) {
            return false;
        }

        if (this.breakingOnly && row.dataset.breaking !== 'true') {
            return false;
        }

        const query = this.query.trim().toLowerCase();

        return query === '' || row.dataset.text.includes(query);
    },

    holdsMatch(element) {
        return [...element.querySelectorAll('[data-kind]')].some((row) => this.matches(row));
    },

    get count() {
        return this.rows.filter((row) => this.matches(row)).length;
    },

    get countLabel() {
        return this.count === 1 ? '1 change' : `${this.count} changes`;
    },

    clear() {
        this.query = '';
        this.kind = 'All';
        this.breakingOnly = false;
        this.$nextTick(() => this.$refs.search.focus());
    },
});
