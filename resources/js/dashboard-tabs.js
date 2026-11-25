export default () => ({
    view: 'timeline',

    selectTab(tab) {
        this.view = tab.dataset.tab;
        tab.focus();
    },

    selectAdjacentTab(tablist, step) {
        const tabs = [...tablist.querySelectorAll('[role="tab"]')];
        const current = tabs.findIndex((tab) => tab.dataset.tab === this.view);
        const direction = getComputedStyle(tablist).direction === 'rtl' ? -step : step;

        this.selectTab(tabs[(current + direction + tabs.length) % tabs.length]);
    },

    selectFirstTab(tablist) {
        this.selectTab(tablist.querySelector('[role="tab"]'));
    },

    selectLastTab(tablist) {
        const tabs = tablist.querySelectorAll('[role="tab"]');

        this.selectTab(tabs[tabs.length - 1]);
    },
});
