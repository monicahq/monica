/**
 * The search over the MCP tools: what the reader typed, and which tools it
 * finds by name or by what they do. Every tool link is rendered by the server
 * and carries what it is found by, so the families work without JavaScript.
 */
export default () => ({
    query: '',

    get searching() {
        return this.query.trim() !== '';
    },

    matches(text) {
        return this.searching && text.includes(this.query.trim().toLowerCase());
    },

    get matchCount() {
        return [...this.$root.querySelectorAll('#mcp-tool-search-results [data-search]')]
            .filter((link) => this.matches(link.dataset.search)).length;
    },

    get anyMatch() {
        return this.matchCount > 0;
    },

    get status() {
        if (! this.searching) {
            return '';
        }

        if (this.matchCount === 0) {
            return `No tool matches “${this.query.trim()}”.`;
        }

        return this.matchCount === 1 ? '1 tool found.' : `${this.matchCount} tools found.`;
    },
});
