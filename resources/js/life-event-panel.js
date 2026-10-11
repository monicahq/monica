export default (projection) => ({
    view: 'timeline',
    open: projection.open,
    step: projection.step,
    category: projection.category,
    type: projection.type,
    subject: projection.subject,
    people: projection.people,
    openPieces: projection.openPieces,
    date: projection.date,
    types: projection.types,
    resultHeadings: projection.resultHeadings,
    candidatesUrl: projection.candidatesUrl,
    copy: projection.copy,
    cancelUrl: projection.cancelUrl,
    newUrl: projection.newUrl,

    init() {
        if (this.open && this.step === 'pick') {
            this.focusOn('life-event-type-search');
        }

        if (this.cancelUrl && this.open) {
            this.$nextTick(() => {
                document.getElementById('life-event-panel')?.scrollIntoView({ block: 'start' });
                document.getElementById('life-event-date')?.focus({ preventScroll: true });
            });
        }
    },

    query: '',
    personQuery: '',
    subjectQuery: '',
    adding: false,
    changing: false,
    dropNote: '',

    get searching() {
        return this.query.trim() !== '';
    },

    get resultCount() {
        return this.types.filter((type) => this.matches(type.name, type.categoryName)).length;
    },

    get resultHeading() {
        return this.resultHeadings[this.resultCount] ?? '';
    },

    get noMatchLine() {
        return this.copy.noMatch.replace(':query', this.query.trim());
    },

    matches(name, categoryName) {
        const query = this.query.trim().toLocaleLowerCase();

        return name.toLocaleLowerCase().includes(query) || categoryName.toLocaleLowerCase().includes(query);
    },

    focusOn(id) {
        this.$nextTick(() => document.getElementById(id)?.focus());
    },

    show() {
        if (this.newUrl) {
            this.$ajax(this.newUrl, { target: 'contact-timeline' });

            return;
        }

        this.open = true;
        this.focusOn('life-event-type-search');
    },

    pick(id, name, categoryName, fieldsUrl) {
        const sameType = this.type?.id === id;

        this.type = { id, name, categoryName };
        this.step = 'details';
        this.changing = false;
        this.query = '';
        this.focusOn('life-event-date');

        if (sameType) {
            return;
        }

        this.openPieces = this.openPieces.filter((piece) => !piece.startsWith('field-'));
        this.$ajax(fieldsUrl, { target: 'life-event-custom-fields' });
    },

    changeType(el) {
        const panel = el.closest('[data-life-event-panel]');
        const written = Array.from(panel.querySelectorAll('[data-custom-field]'))
            .filter((field) => this.isOpen(field.dataset.piece) && this.holdsSomething(field))
            .map((field) => field.dataset.label);

        this.dropNote = written.length === 0 ? '' : this.copy.drop.replace(':fields', written.join(this.copy.or));
        this.changing = true;
        this.step = 'pick';
        this.focusOn('life-event-type-search');
    },

    holdsSomething(field) {
        return Array.from(field.querySelectorAll('input, textarea, select')).some((input) => {
            if (input.type === 'hidden') {
                return false;
            }

            if (input.type === 'checkbox' || input.type === 'radio') {
                return input.checked;
            }

            return input.value !== '';
        });
    },

    isOpen(piece) {
        return this.openPieces.includes(piece);
    },

    openPiece(piece) {
        if (!this.isOpen(piece)) {
            this.openPieces.push(piece);
        }
    },

    closePiece(piece) {
        this.openPieces = this.openPieces.filter((open) => open !== piece);
    },

    isChosen(id) {
        return this.people.some((person) => person.id === id);
    },

    changeSubject() {
        this.step = 'who';
        this.subjectQuery = '';
        this.searchSubjects();
        this.focusOn('life-event-subject-search');
    },

    searchSubjects() {
        const parameters = new URLSearchParams({ query: this.subjectQuery, list: 'life-event-subjects' });

        this.$ajax(`${this.candidatesUrl}?${parameters}`, { target: 'life-event-subjects' });
    },

    choose(id, name) {
        if (this.step === 'who') {
            this.subject = { id, name };
            this.remove(id);
            this.step = 'pick';
            this.focusOn('life-event-type-search');

            return;
        }

        if (!this.isChosen(id)) {
            this.people.push({ id, name });
        }

        this.personQuery = '';
        this.searchPeople();
    },

    remove(id) {
        this.people = this.people.filter((person) => person.id !== id);
    },

    startAdding() {
        this.adding = true;
        this.searchPeople();
    },

    searchPeople() {
        const parameters = new URLSearchParams({ query: this.personQuery });

        parameters.append('except[]', this.subject.id);
        this.people.forEach((person) => parameters.append('except[]', person.id));

        this.$ajax(`${this.candidatesUrl}?${parameters}`, { target: 'life-event-candidates' });
    },

    cancel(el) {
        if (this.cancelUrl) {
            this.$ajax(this.cancelUrl, { target: 'contact-timeline' });

            return;
        }

        el.closest('form').reset();

        this.open = false;
        this.step = 'pick';
        this.type = null;
        this.subject = projection.subject;
        this.people = [];
        this.openPieces = [];
        this.date = projection.today;
        this.query = '';
        this.personQuery = '';
        this.subjectQuery = '';
        this.adding = false;
        this.changing = false;
        this.dropNote = '';
        this.$nextTick(() => this.$refs.addButton?.focus());
    },
});
