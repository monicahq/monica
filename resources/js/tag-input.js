export default (options) => ({
    tags: options.chosen,
    suggestions: options.suggestions,
    draft: '',

    get matches() {
        const query = this.draft.trim().toLocaleLowerCase();

        if (query === '') {
            return [];
        }

        return this.suggestions.filter((name) => name.toLocaleLowerCase().includes(query) && !this.has(name)).slice(0, 6);
    },

    has(name) {
        return this.tags.some((tag) => tag.toLocaleLowerCase() === name.toLocaleLowerCase());
    },

    add(name = null) {
        const wanted = (name ?? this.draft).trim();

        this.draft = '';

        if (wanted === '' || this.has(wanted)) {
            return;
        }

        const existing = this.suggestions.find((candidate) => candidate.toLocaleLowerCase() === wanted.toLocaleLowerCase());

        this.tags.push(existing ?? wanted);
        this.$nextTick(() => this.$refs.draft.focus());
    },

    remove(name) {
        this.tags = this.tags.filter((tag) => tag !== name);
        this.$nextTick(() => this.$refs.draft.focus());
    },

    keydown(event) {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            this.add();

            return;
        }

        if (event.key === 'Backspace' && this.draft === '' && this.tags.length > 0) {
            this.tags.pop();
        }
    },
});
