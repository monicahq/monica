const KEY = 'api-docs-sidebar-scroll';

export default () => ({
    init() {
        this.$el.scrollTop = this.remembered();
    },

    remember() {
        try {
            sessionStorage.setItem(KEY, String(this.$el.scrollTop));
        } catch {
        }
    },

    remembered() {
        try {
            return Number(sessionStorage.getItem(KEY)) || 0;
        } catch {
            return 0;
        }
    },
});
