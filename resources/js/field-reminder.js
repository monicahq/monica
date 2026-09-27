export default (config) => ({
    on: config.on,
    repeat: config.repeat,
    time: config.time,
    date: '',
    yearUnknown: false,
    touched: false,

    init() {
        const date = document.getElementById(config.dateId);
        const year = config.yearId ? document.getElementById(config.yearId) : null;

        this.date = date?.value ?? '';
        this.yearUnknown = year?.checked ?? false;

        date?.addEventListener('input', () => {
            this.date = date.value;
            this.touched = true;
        });

        year?.addEventListener('change', () => {
            this.yearUnknown = year.checked;
            this.touched = true;
        });

        this.$watch('repeat', () => { this.touched = true; });
        this.$watch('time', () => { this.touched = true; });
    },

    escape(text) {
        const span = document.createElement('span');
        span.textContent = text;

        return span.innerHTML;
    },

    /**
     * The next day the reminder falls on, worked out the way the server's schedule does for the
     * choices this list offers: once, or every so many years counted from the date. A rhythm the
     * list only keeps for an older reminder is left to the server's own sentence.
     */
    nextDate() {
        const [year, month, day] = this.date.split('-').map(Number);

        if (! month || ! day) {
            return undefined;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const on = (y) => new Date(y, month - 1, Math.min(day, new Date(y, month, 0).getDate()));
        const anchor = this.yearUnknown || ! year ? today.getFullYear() : year;

        if (this.repeat === 'once') {
            const date = on(anchor);

            return date < today ? null : date;
        }

        const [cadence, interval] = this.repeat.split(':');

        if (cadence !== 'yearly') {
            return undefined;
        }

        const step = Math.max(1, Number(interval) || 1);
        let candidate = anchor;

        while (on(candidate) < today) {
            candidate += step;
        }

        return on(candidate);
    },

    get nextLine() {
        if (! this.touched && config.next !== null) {
            return config.next;
        }

        const date = this.nextDate();

        if (date === undefined) {
            return this.date === '' ? this.escape(config.words.noDate) : (config.next ?? '');
        }

        if (date === null) {
            return this.escape(config.words.nothing);
        }

        const written = new Intl.DateTimeFormat(config.locale, { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
        const when = config.words.when.replace(':date', written).replace(':time', this.time || '09:00');

        return this.escape(config.words.next).replace(':when', `<strong class="font-semibold text-ink">${this.escape(when)}</strong>`);
    },
});
