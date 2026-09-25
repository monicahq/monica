export default (projection) => ({
    url: projection.url,
    listId: projection.listId,
    chosen: projection.chosen,
    chosenName: projection.chosenName,
    query: '',
    searched: false,

    get hasChosen() {
        return this.chosen !== '';
    },

    search() {
        this.searched = true;

        this.$ajax(`${this.url}?query=${encodeURIComponent(this.query)}`, {
            target: `${this.listId}:record-choices`,
            focus: false,
        });
    },

    open() {
        if (!this.searched) {
            this.search();
        }
    },

    choose(uuid, name) {
        this.chosen = uuid;
        this.chosenName = name;
    },

    forget() {
        this.chosen = '';
        this.chosenName = '';
    },
});
