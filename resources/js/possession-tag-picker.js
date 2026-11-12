export default (options) => ({
    createUrl: options.createUrl,
    labels: options.labels,
    open: false,
    query: '',
    active: '',
    offset: '0px',

    matches(name) {
        return name.toLocaleLowerCase().includes(this.query.trim().toLocaleLowerCase());
    },

    get canCreate() {
        const query = this.query.trim().toLocaleLowerCase();

        if (query === '') {
            return false;
        }

        return !this.tagRows().some((row) => row.dataset.name.toLocaleLowerCase() === query);
    },

    get createLine() {
        return this.labels.create.replace(':name', this.query.trim());
    },

    tagRows() {
        return Array.from(document.querySelectorAll('#possession-tag-listbox [data-tag-option]'));
    },

    visibleRows() {
        const rows = this.tagRows().filter((row) => this.matches(row.dataset.name));

        if (this.canCreate) {
            rows.push(document.getElementById('possession-tag-create'));
        }

        return rows;
    },

    toggle() {
        if (this.open) {
            this.close();

            return;
        }

        const width = Math.min(320, window.innerWidth - 32);
        const left = this.$refs.add.getBoundingClientRect().left;

        this.offset = left + width > window.innerWidth - 16 ? `${window.innerWidth - 16 - width - left}px` : '0px';
        this.open = true;
        this.query = '';
        this.active = this.visibleRows()[0]?.id ?? '';
        this.$nextTick(() => this.$refs.search.focus());
    },

    close(returnFocus = true) {
        if (!this.open) {
            return;
        }

        this.open = false;
        this.query = '';

        if (returnFocus) {
            this.$nextTick(() => this.$refs.add.focus());
        }
    },

    search() {
        this.$nextTick(() => {
            this.active = this.visibleRows()[0]?.id ?? '';
        });
    },

    move(step) {
        const rows = this.visibleRows();

        if (rows.length === 0) {
            return;
        }

        const index = rows.findIndex((row) => row.id === this.active);
        const next = index === -1 ? 0 : (index + step + rows.length) % rows.length;

        this.active = rows[next].id;
        rows[next].scrollIntoView({ block: 'nearest' });
    },

    chooseActive() {
        document.getElementById(this.active)?.click();
    },

    choose(row) {
        this.active = row.id;

        if (row.dataset.carried === 'true') {
            const remove = document.getElementById(`remove-tag-${row.dataset.id}`);

            this.close(false);
            remove?.focus();
            remove?.click();

            return;
        }

        this.send('put', row.dataset.url);
    },

    create() {
        if (!this.canCreate) {
            return;
        }

        this.$refs.name.value = this.query.trim();
        this.send('post', this.createUrl);
        this.query = '';
        this.active = '';
    },

    closeUnlessRefused() {
        if (document.getElementById('possession-tag-error')) {
            return;
        }

        this.close();
    },

    send(method, url) {
        this.$refs.form.action = url;
        this.$refs.form.querySelector('input[name="_method"]').value = method;
        this.$refs.form.requestSubmit();
    },
});
