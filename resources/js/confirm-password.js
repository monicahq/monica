export default (config) => ({
    open: false,
    busy: false,
    message: '',
    failed: false,
    password: '',
    form: null,

    async stillConfirmed() {
        try {
            const response = await fetch(config.statusUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (! response.ok) {
                return false;
            }

            const status = await response.json();

            return status.confirmed === true;
        } catch {
            return false;
        }
    },

    async start(form) {
        if (await this.stillConfirmed()) {
            form.requestSubmit();

            return;
        }

        this.form = form;
        this.password = '';
        this.announce('');
        this.open = true;
    },

    close() {
        this.open = false;
        this.form = null;
    },

    announce(message, failed = false) {
        this.message = message;
        this.failed = failed;
    },

    submit() {
        const form = this.form;

        this.close();

        form?.requestSubmit();
    },

    async withPassword() {
        this.busy = true;
        this.announce('');

        try {
            const response = await fetch(config.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ password: this.password }),
            });

            if (response.ok) {
                this.submit();

                return;
            }

            this.announce(response.status === 429 ? config.text.tooMany : config.text.wrong, true);
        } catch {
            this.announce(config.text.failed, true);
        } finally {
            this.busy = false;
        }
    },
});
