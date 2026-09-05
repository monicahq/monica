import { Passkeys, UserCancelledError, PasskeyExistsError, InvalidDomainError, NotSupportedError } from '@laravel/passkeys';

/*
 * The whole of the browser side. `@laravel/passkeys` runs the ceremony and talks
 * to the endpoints its own server package registers, so there are no URLs here:
 * it knows them, and it carries the CSRF token itself.
 *
 * Every outcome is written into `message`, which the screen renders inside a live
 * region. A cancelled device prompt changes nothing visible on the page, so
 * announcing it is the only way somebody using a screen reader learns that
 * nothing happened.
 */

export default (config) => ({
    busy: false,
    message: '',
    failed: false,

    /*
     * `supported` is asked once, on init, because the answer cannot change while
     * the page is open. The server has already decided whether to render this
     * component at all; this is the half only the browser can answer.
     */
    supported: false,

    init() {
        this.supported = Passkeys.isSupported();
    },

    /*
     * The name the person typed, or the default. Read from the ref here rather
     * than passed in as a closure, because a closure cannot survive being
     * serialised into an HTML attribute.
     */
    chosenName() {
        return this.$refs.name?.value?.trim() || config.text.defaultName;
    },

    announce(message, failed = false) {
        this.message = message;
        this.failed = failed;
    },

    async register() {
        this.busy = true;
        this.announce('');

        try {
            await Passkeys.register({ name: this.chosenName() });

            window.location = config.redirectUrl;
        } catch (error) {
            this.announce(this.explain(error), true);
        } finally {
            this.busy = false;
        }
    },

    async signIn() {
        this.busy = true;
        this.announce('');

        try {
            /*
             * Autofill is deliberately not used. That is the flow where the
             * browser offers passkeys inside a username field, and this screen
             * has no username field: nothing is typed. The platform's
             * cross-device flow needs nothing here, it runs inside this call.
             */
            const { redirect } = await Passkeys.verify();

            window.location = redirect ?? config.redirectUrl;
        } catch (error) {
            this.announce(this.explain(error), true);
        } finally {
            this.busy = false;
        }
    },

    /*
     * The outcomes worth telling apart from a general failure. Anything else is
     * reported as a failure, because guessing at a cause somebody cannot act on
     * is worse than saying it did not work.
     *
     * The library raises its own error types, so this reads a class rather than
     * a `name` string. A server-side refusal arrives as the base PasskeyError
     * carrying the message the server chose, which is more useful than anything
     * that could be written here, so it is passed straight through.
     */
    explain(error) {
        if (error instanceof UserCancelledError) {
            return config.text.cancelled;
        }

        if (error instanceof PasskeyExistsError) {
            return config.text.alreadyRegistered;
        }

        if (error instanceof InvalidDomainError || error instanceof NotSupportedError) {
            return config.text.failed;
        }

        return error?.message || config.text.failed;
    },
});
