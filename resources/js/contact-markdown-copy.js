/**
 * The line of a contact's actions menu that puts the contact on the clipboard as Markdown, and the
 * status that says whether it got there. The text is asked for only when the line is chosen. Handing
 * the clipboard a promise keeps the click as the gesture that allowed the copy, which Safari requires;
 * where that is not supported, the text is awaited and copied the ordinary way.
 */
export default ({ url, copied, failed }) => ({
    state: 'idle',

    get message() {
        if (this.state === 'copied') {
            return copied;
        }

        if (this.state === 'failed') {
            return failed;
        }

        return '';
    },

    copyMarkdown() {
        const text = fetch(url, { credentials: 'same-origin' }).then((response) => {
            if (! response.ok) {
                throw new Error(String(response.status));
            }

            return response.text();
        });

        this.write(text).then((ok) => {
            this.state = ok ? 'copied' : 'failed';

            if (ok) {
                setTimeout(() => {
                    if (this.state === 'copied') {
                        this.state = 'idle';
                    }
                }, 2500);
            }
        });
    },

    async write(text) {
        if (window.isSecureContext && navigator.clipboard?.write && window.ClipboardItem) {
            try {
                await navigator.clipboard.write([
                    new ClipboardItem({ 'text/plain': text.then((value) => new Blob([value], { type: 'text/plain' })) }),
                ]);

                return true;
            } catch {
            }
        }

        try {
            return await window.copyText(await text);
        } catch {
            return false;
        }
    },
});
