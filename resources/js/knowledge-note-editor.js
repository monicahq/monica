/**
 * The editor of a Knowledge note. "Add a link", or Control K (Command K on an Apple device), opens a
 * small form taking a text and a web address, then writes `[text](address)` where the caret was,
 * around the words selected if there were any. The shortcut stops at the text, so inside a note it
 * adds a link rather than opening the vault search.
 */
export default () => ({
    linking: false,
    start: 0,
    end: 0,
    linkText: '',
    linkAddress: '',

    askLink() {
        const body = this.$refs.body;

        this.start = body.selectionStart;
        this.end = body.selectionEnd;
        this.linkText = body.value.slice(this.start, this.end);
        this.linkAddress = '';
        this.linking = true;
        this.$nextTick(() => this.$refs.address.focus());
    },

    closeLink() {
        this.linking = false;
        this.$nextTick(() => this.$refs.body.focus());
    },

    applyLink() {
        let address = this.linkAddress.trim();

        if (address === '') {
            return;
        }

        if (!/^https?:\/\//i.test(address)) {
            address = `https://${address}`;
        }

        const text = this.linkText.trim() || address.replace(/^https?:\/\/(www\.)?/i, '');
        const markdown = `[${text}](${address})`;
        const body = this.$refs.body;
        const caret = this.start + markdown.length;

        body.value = body.value.slice(0, this.start) + markdown + body.value.slice(this.end);
        this.linking = false;
        this.$nextTick(() => {
            body.focus();
            body.setSelectionRange(caret, caret);
        });
    },

    shortcut(event) {
        if (!(event.ctrlKey || event.metaKey) || event.altKey || event.shiftKey || event.key.toLowerCase() !== 'k') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        this.askLink();
    },
});
