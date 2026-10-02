/**
 * The keyboard shortcut to search, Command K on Apple devices and Control K elsewhere. The server
 * decides which from the browser it is talking to, so the key the sidebar shows is the key that
 * works. On the search screen it puts the cursor in the field; anywhere else it opens that screen.
 */
export default (config) => ({
    open(event) {
        const modifier = config.usesCommandKey ? event.metaKey : event.ctrlKey;

        if (!modifier || event.altKey || event.shiftKey || event.key.toLowerCase() !== 'k') {
            return;
        }

        event.preventDefault();

        const field = document.getElementById(config.fieldId);

        if (field) {
            field.focus();
            field.select();

            return;
        }

        window.location.assign(config.url);
    },
});
