import { startRegistration, startAuthentication, browserSupportsWebAuthn } from '@simplewebauthn/browser';

/*
 * The whole of the browser side. It asks the server for a challenge, hands it to
 * the library, and posts back what comes out. It converts nothing: at version 13
 * the library takes and returns the JSON form of the WebAuthn structures, where
 * every binary field is already a base64url string, so the server's options go
 * straight in and the credential comes straight back out.
 *
 * Every outcome is written into `message`, which the screen renders inside a live
 * region. A cancelled device prompt changes nothing visible on the page, so
 * announcing it is the only way somebody using a screen reader learns that
 * nothing happened.
 */

const post = async (url, body) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body ?? {}),
    });

    return response;
};

const firstError = (payload, fallback) => {
    const errors = payload?.errors ?? {};
    const first = Object.values(errors)[0];

    return (Array.isArray(first) ? first[0] : first) ?? payload?.message ?? fallback;
};

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
        this.supported = browserSupportsWebAuthn();
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
            const options = await post(config.optionsUrl);

            if (!options.ok) {
                this.announce(firstError(await options.json().catch(() => null), config.text.failed), true);

                return;
            }

            const { publicKey } = await options.json();
            const credential = await startRegistration({ optionsJSON: publicKey });

            const stored = await post(config.storeUrl, { ...credential, name: this.chosenName() });

            if (!stored.ok) {
                this.announce(firstError(await stored.json().catch(() => null), config.text.failed), true);

                return;
            }

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
            const options = await post(config.optionsUrl);

            if (!options.ok) {
                this.announce(firstError(await options.json().catch(() => null), config.text.failed), true);

                return;
            }

            const { publicKey } = await options.json();

            /*
             * `useBrowserAutofill` stays false. True is the flow where the browser
             * offers passkeys inside a username field, and this screen has no
             * username field: nothing is typed. The platform's cross-device flow
             * needs nothing here, it runs inside this same call.
             */
            const assertion = await startAuthentication({ optionsJSON: publicKey, useBrowserAutofill: false });

            const signedIn = await post(config.loginUrl, assertion);

            if (!signedIn.ok) {
                this.announce(firstError(await signedIn.json().catch(() => null), config.text.failed), true);

                return;
            }

            window.location = config.redirectUrl;
        } catch (error) {
            this.announce(this.explain(error), true);
        } finally {
            this.busy = false;
        }
    },

    /*
     * The two outcomes worth telling apart from a general failure. Anything else
     * is reported as a failure, because guessing at a cause somebody cannot act
     * on is worse than saying it did not work.
     */
    explain(error) {
        if (error?.name === 'NotAllowedError') {
            return config.text.cancelled;
        }

        if (error?.name === 'InvalidStateError') {
            return config.text.alreadyRegistered;
        }

        return config.text.failed;
    },
});
