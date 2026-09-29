/**
 * The row of actions under the title of a documentation page: copying the page
 * as Markdown for an assistant, and saying whether the copy worked.
 */
export default ({ markdown }) => ({
    markdown,
    copied: false,
    failed: false,

    copyPage(text) {
        window.copyText(text).then((ok) => {
            this.copied = ok;
            this.failed = ! ok;
            setTimeout(() => {
                this.copied = false;
            }, 1600);
        });
    },

    get copyLabel() {
        if (this.copied) {
            return 'Copied';
        }

        if (this.failed) {
            return 'Could not copy. Select the text and copy it by hand.';
        }

        return 'Copy for LLM';
    },
});
