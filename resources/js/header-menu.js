/**
 * One menu of the documentation header, a native <details> that already opens and closes on a
 * click, a tap and the keyboard. This adds what a menu is expected to do beyond that: Escape and a
 * click elsewhere close it, and a mouse resting on it opens it, closing a moment after the mouse
 * leaves so the pointer can cross the gap to the panel. Only one menu stays open at a time.
 */
export default () => ({
    menu: null,
    hovered: false,
    timer: null,

    init() {
        this.menu = this.$el;

        this.closeOnEscape = (event) => {
            if (event.key !== 'Escape' || !this.menu.open) {
                return;
            }

            const focusWasInside = this.menu.contains(document.activeElement);

            this.close();

            if (focusWasInside) {
                this.menu.querySelector('summary')?.focus();
            }
        };

        this.closeOnClickElsewhere = (event) => {
            if (!this.menu.open) {
                return;
            }

            if (!this.menu.contains(event.target) || event.target.closest('a')) {
                this.close();
            }
        };

        this.closeBeforeSnapshot = () => this.close();

        document.addEventListener('keydown', this.closeOnEscape);
        document.addEventListener('click', this.closeOnClickElsewhere);
        document.addEventListener('turbo:before-cache', this.closeBeforeSnapshot);

        this.menu.addEventListener('pointerover', (event) => this.openOnRest(event));
        this.menu.addEventListener('pointerout', (event) => this.closeAfterLeaving(event));
        this.menu.querySelector('summary')?.addEventListener('click', (event) => this.keepOpenOnClick(event));
        this.menu.addEventListener('focusout', (event) => this.closeWhenFocusLeaves(event));
    },

    destroy() {
        document.removeEventListener('keydown', this.closeOnEscape);
        document.removeEventListener('click', this.closeOnClickElsewhere);
        document.removeEventListener('turbo:before-cache', this.closeBeforeSnapshot);
    },

    openOnRest(event) {
        if (event.pointerType !== 'mouse') {
            return;
        }

        clearTimeout(this.timer);

        if (this.menu.open || this.menu.contains(event.relatedTarget)) {
            return;
        }

        document.querySelectorAll('details[data-menu][open]').forEach((other) => {
            if (other !== this.menu) {
                other.open = false;
            }
        });

        this.menu.open = true;
        this.hovered = true;
    },

    closeAfterLeaving(event) {
        if (event.pointerType !== 'mouse' || !this.hovered || this.menu.contains(event.relatedTarget)) {
            return;
        }

        this.timer = setTimeout(() => {
            if (!this.menu.contains(document.activeElement)) {
                this.close();
            }
        }, 250);
    },

    closeWhenFocusLeaves(event) {
        if (this.menu.open && event.relatedTarget && !this.menu.contains(event.relatedTarget)) {
            this.close();
        }
    },

    keepOpenOnClick(event) {
        if (!this.hovered) {
            return;
        }

        event.preventDefault();
        clearTimeout(this.timer);
        this.hovered = false;
    },

    close() {
        clearTimeout(this.timer);
        this.menu.open = false;
        this.hovered = false;
    },
});
