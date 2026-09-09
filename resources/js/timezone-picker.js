/**
 * Choosing where you are: by typing a city, by asking the browser, or by
 * clicking the map.
 *
 * `bounds` carries the two latitudes the map is cut off at, handed over by
 * `TimezoneCatalogue` so that the click below reads the same projection the
 * marker was drawn with. Everything else the form needs is in the markup.
 */
export default (bounds) => ({
    query: '',
    shown: 0,
    deviceSilent: false,

    get needle() {
        return this.query.trim().toLowerCase();
    },

    /**
     * Show the cities the search reaches, and never more than a screenful:
     * the list holds every zone the machine knows and drawing all of them
     * would bury the few that were asked for.
     */
    filter() {
        const limit = 8;
        let shown = 0;

        this.$refs.results.querySelectorAll('[data-search]').forEach((row) => {
            const hit = this.needle !== '' && row.dataset.search.includes(this.needle) && shown < limit;

            row.hidden = ! hit;

            if (hit) {
                shown++;
            }
        });

        this.shown = shown;
    },

    /** Walk the results with the arrow keys, stopping at either end. */
    step(from, by) {
        const rows = [...this.$refs.results.querySelectorAll('[data-search]:not([hidden]) button')];

        if (rows.length === 0) {
            return;
        }

        const at = rows.indexOf(from);

        rows[Math.min(rows.length - 1, Math.max(0, at + by))].focus();
    },

    reset() {
        this.query = '';
        this.filter();
    },

    clear() {
        this.reset();
        this.$refs.field.focus();
    },

    /**
     * Ask the browser where it is. It may refuse, or not know, and then it
     * says so rather than submitting an empty zone.
     */
    device(event) {
        let zone = '';

        try {
            zone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        } catch {
            zone = '';
        }

        if (zone === '') {
            this.deviceSilent = true;

            return;
        }

        event.currentTarget.value = zone;
        this.$el.requestSubmit(event.currentTarget);
    },

    /**
     * Turn a click on the map into the place that was clicked, then let the
     * server name the nearest city to it.
     *
     * The map is an equirectangular projection: longitude runs straight across
     * the full turn, and latitude straight down from the northern edge to the
     * southern one. Both edges come from the server, because the same two
     * numbers place the marker and draw the equator, and a copy of them here
     * would be a copy nobody could check.
     */
    point(event) {
        const box = event.currentTarget.getBoundingClientRect();

        if (box.width === 0 || box.height === 0) {
            return;
        }

        const across = (event.clientX - box.left) / box.width;
        const down = (event.clientY - box.top) / box.height;

        const longitude = across * 360 - 180;
        const latitude = bounds.north - down * (bounds.north - bounds.south);

        this.$refs.longitude.value = longitude.toFixed(4);
        this.$refs.latitude.value = latitude.toFixed(4);

        this.$el.requestSubmit();
    },
});
