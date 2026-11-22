/**
 * The state and keyboard route of x-searchable-list. The picker adds `id`, `value`, `labels` keyed by
 * group, a `groups` getter returning [{ key, items }] whose items carry a `value`, and `pick(value)`.
 */
export default (picker) =>
    Object.defineProperties(
        {
            open: false,
            query: '',
            active: '',

            get options() {
                return this.groups.flatMap((group) => group.items.map((option) => ({ id: this.optionId(group.key, option.value), value: option.value })));
            },

            optionId(groupKey, value) {
                return `${this.id}-option-${groupKey}-${value === '' ? 'none' : value}`;
            },

            toggle() {
                if (this.open) {
                    this.close();

                    return;
                }

                this.open = true;
                this.query = '';
                this.active = this.options.find((option) => option.value === this.value)?.id ?? this.options[0]?.id ?? '';
                this.$nextTick(() => {
                    this.$refs.search.focus();
                    document.getElementById(this.active)?.scrollIntoView({ block: 'nearest' });
                });
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
                    this.pick(option.value);
                }
            },
        },
        Object.getOwnPropertyDescriptors(picker),
    );
