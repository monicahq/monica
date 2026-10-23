export default (config) => {
    // The client library is fetched only on screens that offer a passkey. It is requested as soon as the
    // component starts rather than on click, because Safari refuses a WebAuthn call made after the click
    // has waited on the network.
    let library = Promise.resolve(null);

    return {
        busy: false,
        message: '',
        failed: false,

        supported: false,

        init() {
            this.supported = typeof globalThis.PublicKeyCredential === 'function';

            if (this.supported) {
                library = import('@laravel/passkeys').catch(() => null);
            }

            if (this.supported && config.openOnLoad) {
                this.secondFactor(true);
            }
        },

        async passkeys() {
            const loaded = await library;

            if (! loaded) {
                throw new Error(config.text.failed);
            }

            return loaded.Passkeys;
        },

        announce(message, failed = false) {
            this.message = message;
            this.failed = failed;
        },

        async register() {
            this.busy = true;
            this.announce('');

            try {
                const passkeys = await this.passkeys();

                const added = await passkeys.register({ name: '_', routes: config.routes });

                window.location = added?.redirect ?? config.redirectUrl;
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
                const passkeys = await this.passkeys();
                const { redirect } = await passkeys.verify({ remember: true });

                window.location = redirect ?? config.redirectUrl;
            } catch (error) {
                this.announce(this.explain(error), true);
            } finally {
                this.busy = false;
            }
        },

        async confirm() {
            this.busy = true;
            this.announce('');

            try {
                const passkeys = await this.passkeys();
                const { redirect } = await passkeys.verify({ routes: config.routes });

                this.$dispatch('passkey-confirmed', { redirect });
            } catch (error) {
                this.announce(this.explain(error), true);
            } finally {
                this.busy = false;
            }
        },

        // The prompt opened as the screen loaded, before anybody pressed anything, is refused by browsers
        // that only answer a press (Safari). That refusal looks exactly like a dismissed prompt, so it asks
        // for the press instead of reporting a failure.
        async secondFactor(openedOnLoad = false) {
            this.busy = true;
            this.announce(config.text.opening);

            try {
                const passkeys = await this.passkeys();
                const { redirect } = await passkeys.verify({ routes: config.routes });

                window.location = redirect ?? config.redirectUrl;
            } catch (error) {
                if (openedOnLoad && error?.name === 'UserCancelledError') {
                    this.announce(config.text.waiting);

                    return;
                }

                this.announce(this.explain(error), true);
            } finally {
                this.busy = false;
            }
        },

        explain(error) {
            if (error?.name === 'UserCancelledError') {
                return config.text.cancelled;
            }

            if (error?.name === 'PasskeyExistsError') {
                return config.text.alreadyRegistered;
            }

            if (error?.name === 'InvalidDomainError' || error?.name === 'NotSupportedError') {
                return config.text.failed;
            }

            // The client turns an answer that is not JSON, a throttled request or an error page, into an
            // English sentence naming the status. That sentence is never the reader's.
            if (/status 429/.test(error?.message ?? '')) {
                return config.text.tooMany ?? config.text.failed;
            }

            if (/^Request failed with status/.test(error?.message ?? '')) {
                return config.text.failed;
            }

            return error?.message || config.text.failed;
        },
    };
};
