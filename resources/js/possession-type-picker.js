export default (options) => ({
    types: options.types,
    recent: options.recent,
    labels: options.labels,
    chosen: options.chosen,
    open: false,
    query: '',
    active: '',

    get chosenType() {
        return this.types.find((type) => type.uuid === this.chosen) ?? null;
    },

    get groups() {
        const query = this.query.trim().toLocaleLowerCase();

        if (query !== '') {
            const matching = this.types.filter((type) => type.name.toLocaleLowerCase().includes(query));

            return matching.length > 0 ? [{ key: 'matching', items: matching }] : [];
        }

        const recent = this.recent.map((uuid) => this.types.find((type) => type.uuid === uuid)).filter(Boolean);

        return [
            { key: 'recent', items: recent },
            { key: 'all', items: this.types },
        ].filter((group) => group.items.length > 0);
    },

    get options() {
        return this.groups.flatMap((group) => group.items.map((type) => ({ id: this.optionId(group.key, type.uuid), uuid: type.uuid })));
    },

    get activeId() {
        return this.active === '' ? null : this.active;
    },

    get noMatchLine() {
        return this.labels.noMatch.replace(':query', this.query.trim());
    },

    optionId(groupKey, uuid) {
        return `possession-type-option-${groupKey}-${uuid}`;
    },

    toggle() {
        if (this.open) {
            this.close();

            return;
        }

        this.open = true;
        this.query = '';
        this.active = this.options.find((option) => option.uuid === this.chosen)?.id ?? this.options[0]?.id ?? '';
        this.$nextTick(() => this.$refs.search.focus());
    },

    close(returnFocus = true) {
        if (!this.open) {
            return;
        }

        this.open = false;
        this.query = '';

        if (returnFocus) {
            this.$nextTick(() => this.$refs.trigger.focus());
        }
    },

    search() {
        this.active = this.options[0]?.id ?? '';
    },

    move(step) {
        const options = this.options;

        if (options.length === 0) {
            return;
        }

        const index = options.findIndex((option) => option.id === this.active);
        const next = index === -1 ? 0 : (index + step + options.length) % options.length;

        this.active = options[next].id;
        document.getElementById(this.active)?.scrollIntoView({ block: 'nearest' });
    },

    moveTo(position) {
        const options = this.options;

        if (options.length === 0) {
            return;
        }

        this.active = (position === 'first' ? options[0] : options[options.length - 1]).id;
        document.getElementById(this.active)?.scrollIntoView({ block: 'nearest' });
    },

    pickActive() {
        const option = this.options.find((candidate) => candidate.id === this.active);

        if (option !== undefined) {
            this.pick(option.uuid);
        }
    },

    pick(uuid) {
        const type = this.types.find((candidate) => candidate.uuid === uuid);

        if (type === undefined) {
            return;
        }

        const changed = this.chosen !== uuid;

        this.chosen = uuid;
        this.close();
        this.$dispatch('possession-type-chosen', { name: type.name });

        if (changed) {
            this.$ajax(type.fieldsUrl, { target: 'possession-type-fields', focus: false });
        }
    },
});
