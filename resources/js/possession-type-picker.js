import searchableList from './searchable-list';

export default (options) =>
    searchableList({
        id: 'possession-type',
        types: options.types.map((type) => ({ ...type, value: type.uuid })),
        recent: options.recent,
        labels: options.labels,
        value: options.chosen,

        get chosenType() {
            return this.types.find((type) => type.value === this.value) ?? null;
        },

        get groups() {
            const query = this.query.trim().toLocaleLowerCase();

            if (query !== '') {
                const matching = this.types.filter((type) => type.name.toLocaleLowerCase().includes(query));

                return matching.length > 0 ? [{ key: 'matching', items: matching }] : [];
            }

            const recent = this.recent.map((uuid) => this.types.find((type) => type.value === uuid)).filter(Boolean);

            return [
                { key: 'recent', items: recent },
                { key: 'all', items: this.types },
            ].filter((group) => group.items.length > 0);
        },

        get noMatchLine() {
            return this.labels.noMatch.replace(':query', this.query.trim());
        },

        pick(uuid) {
            const type = this.types.find((candidate) => candidate.value === uuid);

            if (type === undefined) {
                return;
            }

            const changed = this.value !== uuid;

            this.value = uuid;
            this.close();
            this.$dispatch('possession-type-chosen', { name: type.name });

            if (changed) {
                this.$ajax(type.fieldsUrl, { target: 'possession-type-fields', focus: false });
            }
        },
    });
