/**
 * The line "Every [n] weeks" around the interval input. The wordings arrive already translated, one
 * per plural category, with `:n` where the number goes, so the words before and after the input
 * follow the reader's language rather than English order.
 */
export default (config) => ({
    interval: config.interval,

    parts(cadence) {
        const wordings = config.wordings[cadence];

        if (!wordings) {
            return { before: '', after: '' };
        }

        const number = Math.max(1, Number(this.interval) || 1);
        const category = new Intl.PluralRules(config.locale).select(number);
        const wording = wordings[category] ?? wordings.other ?? Object.values(wordings)[0];
        const [before, after = ''] = wording.split(':n');

        return { before: before.trim(), after: after.trim() };
    },
});
