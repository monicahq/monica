import { Passkeys, UserCancelledError, PasskeyExistsError, InvalidDomainError, NotSupportedError } from '@laravel/passkeys';

export default (config) => ({
    busy: false,
    message: '',
    failed: false,

    supported: false,

    init() {
        this.supported = Passkeys.isSupported();
    },

    announce(message, failed = false) {
        this.message = message;
        this.failed = failed;
    },

    async register() {
        this.busy = true;
        this.announce('');

        try {
            await Passkeys.register({ name: '_' });

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
            const { redirect } = await Passkeys.verify();

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
            const { redirect } = await Passkeys.verify({ routes: config.routes });

            this.$dispatch('passkey-confirmed', { redirect });
        } catch (error) {
            this.announce(this.explain(error), true);
        } finally {
            this.busy = false;
        }
    },

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
