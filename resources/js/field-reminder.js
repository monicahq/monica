export default (config) => ({
    on: config.on,
    shown: 0,
    held: config.held,
    rows: config.rows,
    cadence: config.cadence,
    recurs: config.recurs,
    interval: config.interval,
    time: config.time,
    zone: config.zone,

    plural: null,

    init() {
        try {
            this.plural = new Intl.PluralRules(config.locale);
        } catch {
            this.plural = new Intl.PluralRules('en');
        }
    },

    maximumFor(index) {
        return config.maximums[this.rows[index].unit] ?? 1;
    },

    isDrawn(index) {
        return index < this.held + this.shown;
    },

    counts(index) {
        return this.isDrawn(index) && ! this.rows[index].removed;
    },

    get active() {
        return this.rows.filter((row, index) => this.counts(index)).length;
    },

    /**
     * Say a phrase at a number, in the wording that number takes in this
     * language. The server sent one wording per plural category, so the
     * category is asked for rather than worked out from the number.
     */
    counted(forms, count) {
        const category = this.plural === null ? 'other' : this.plural.select(count);
        const wording = forms[category] ?? forms.other ?? Object.values(forms)[0] ?? '';

        return wording.replaceAll(':n', String(count));
    },

    phrase(row) {
        if (row.direction === 'on') {
            return config.words.onTheDay;
        }

        const count = Number(row.amount) || 1;
        const forms = config.words.units[row.unit] ?? config.words.units.days;
        const span = this.counted(forms, count);

        return config.words[row.direction === 'before' ? 'before' : 'after'].replace(':span', span);
    },

    get cadenceWords() {
        if (! this.recurs) {
            return config.words.once;
        }

        const count = Number(this.interval) || 1;
        const forms = config.words.intervals[this.cadence] ?? config.words.intervals.yearly;

        return this.counted(forms, count);
    },

    /**
     * Join what the moments read as into one list, with the separator and the
     * pattern this language joins a list by, both of them translated.
     */
    listed(said) {
        if (said.length === 1) {
            return said[0];
        }

        return config.words.listPattern
            .replace(':list', said.slice(0, -1).join(config.words.listSeparator))
            .replace(':last', said[said.length - 1]);
    },

    get summary() {
        const said = this.rows.filter((row, index) => this.counts(index)).map((row) => this.phrase(row));

        if (said.length === 0) {
            return config.words.nothing;
        }

        return config.words.summary
            .replace(':moments', this.listed(said))
            .replace(':cadence', this.cadenceWords)
            .replace(':time', this.time)
            .replace(':zone', this.zone);
    },
});
