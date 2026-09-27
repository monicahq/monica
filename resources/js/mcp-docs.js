/**
 * The MCP documentation page: the address the reader types, the client tab
 * they chose, the section they are reading, and what the last copy did.
 *
 * Every address on the page is rendered with the instance's own, so the page
 * reads right without JavaScript. Typing another one swaps it in the shown
 * text and in what the copy buttons put on the clipboard.
 */
export default ({ instance, server, tabs, sections }) => ({
    address: instance,
    tab: tabs[0],
    current: sections[0],
    copied: null,
    failed: null,

    init() {
        const observer = new IntersectionObserver((entries) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
                this.current = entry.target.id;
            });
        }, { rootMargin: '-80px 0px -70% 0px' });

        sections.forEach((id) => {
            const section = document.getElementById(id);

            if (section) {
                observer.observe(section);
            }
        });
    },

    get serverAddress() {
        const typed = this.address.trim().replace(/\/+$/, '');

        return `${typed === '' ? instance : typed}/mcp`;
    },

    swap(text) {
        return text.replaceAll(server, this.serverAddress);
    },

    copy(key, text) {
        window.copyText(this.swap(text)).then((ok) => {
            this.copied = ok ? key : null;
            this.failed = ok ? null : key;
            setTimeout(() => {
                this.copied = null;
            }, 1600);
        });
    },

    label(key, idle) {
        if (this.copied === key) {
            return 'Copied';
        }

        if (this.failed === key) {
            return 'Could not copy. Select the text and copy it by hand.';
        }

        return idle;
    },

    select(key) {
        this.tab = key;
        this.$nextTick(() => document.getElementById(`client-tab-${key}`)?.focus());
    },

    move(event) {
        const index = tabs.indexOf(this.tab);
        const targets = {
            ArrowRight: (index + 1) % tabs.length,
            ArrowLeft: (index - 1 + tabs.length) % tabs.length,
            Home: 0,
            End: tabs.length - 1,
        };

        if (! (event.key in targets)) {
            return;
        }

        event.preventDefault();
        this.select(tabs[targets[event.key]]);
    },
});
