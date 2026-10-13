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

                await passkeys.register({ name: '_' });

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

            return error?.message || config.text.failed;
        },
    };
};
