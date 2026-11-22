import searchableList from './searchable-list';

const fold = (text) =>
    text
        .toLocaleLowerCase()
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '');

const typedOption = 'typed-name';

/**
 * An option may say what the form receives in place of its value (`submits`) and which name goes with
 * it (`typed`), which is how a favourite written by hand keeps both its name and its country.
 */
export default (options) =>
    searchableList({
        id: options.id,
        extras: options.extras,
        favourites: options.favourites.map((favourite) => ({ ...favourite, folded: fold(favourite.label) })),
        countries: options.countries.map((country) => ({ ...country, folded: fold(country.label) })),
        labels: options.labels,
        acceptsATypedName: options.acceptsATypedName,
        valueOfATypedName: options.valueOfATypedName,
        value: options.value,
        typed: '',

        get chosenOption() {
            return [...this.extras, ...this.favourites, ...this.countries].find((option) => option.value === this.value);
        },

        get chosenLabel() {
            if (this.value === typedOption) {
                return this.typed;
            }

            return this.chosenOption?.label ?? this.value;
        },

        get submittedValue() {
            if (this.value === typedOption) {
                return this.valueOfATypedName;
            }

            return this.chosenOption?.submits ?? this.value;
        },

        get submittedName() {
            if (this.value === typedOption) {
                return this.typed;
            }

            return this.chosenOption?.typed ?? '';
        },

        get groups() {
            const query = fold(this.query.trim());
            const matches = (option) => query === '' || option.folded.includes(query);
            const favourites = this.favourites.filter(matches);
            const countries = this.countries.filter(matches);
            const namesACountry = [...favourites, ...countries].some((option) => option.folded === query);
            const offersTheTypedName = this.acceptsATypedName && query !== '' && !namesACountry;

            return [
                { key: 'extras', items: query === '' ? this.extras : [] },
                { key: 'favourites', items: favourites },
                { key: 'all', items: countries },
                { key: 'typed', items: offersTheTypedName ? [{ value: typedOption, label: this.labels.add.replace(':name', this.query.trim()) }] : [] },
            ].filter((group) => group.items.length > 0);
        },

        get countLine() {
            if (!this.open || this.query.trim() === '') {
                return '';
            }

            const found = new Set(this.groups.filter((group) => group.key === 'favourites' || group.key === 'all').flatMap((group) => group.items.map((option) => option.label))).size;

            return found === 0 ? this.labels.noMatch : this.labels.count.replace(':number', found);
        },

        pick(value) {
            if (value === typedOption) {
                this.typed = this.query.trim();
            }

            this.value = value;
            this.close();
        },
    });
