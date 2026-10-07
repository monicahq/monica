const PART_KEYS = ['prefix', 'given_name', 'additional_name', 'family_name', 'suffix'];

const TEXT_KEYS = ['formatted_name', ...PART_KEYS];

const POSTED_KEYS = ['id', 'kind', 'language', 'script', 'phonetic_system', 'name_order', ...TEXT_KEYS];

const blank = (kind = 'written') => ({
    id: null,
    kind,
    language: null,
    script: null,
    phonetic_system: null,
    formatted_name: null,
    prefix: null,
    given_name: null,
    additional_name: null,
    family_name: null,
    suffix: null,
    name_order: null,
});

const filled = (value) => typeof value === 'string' && value.trim() !== '';

/**
 * The name editor of the create and edit screens. Nothing it does is written until the form is
 * sent: the rows are posted as hidden inputs, and a row being edited or a removal waiting for its
 * answer holds the form back.
 *
 * One name is edited in place, as its parts. Two or more are a list with one chosen as the display
 * name, each opened in an editor of its own. A pronunciation is written in its parts only and is
 * never the display name.
 *
 * The screen's submit button sits outside this component, so whether the form is held back is
 * announced with the `name-editor-busy` window event.
 */
export default (config) => ({
    rows: config.rows.length > 0 ? config.rows : [blank()],
    display: config.display ?? 0,
    hadName: config.hadName,
    labels: config.labels,
    editing: null,
    draft: null,
    confirming: null,
    replacement: null,
    showParts: false,
    warnsOfNoName: false,
    sendsWithoutName: false,
    editor: null,

    init() {
        this.editor = this.$el;
        this.editor.closest('form')?.addEventListener('submit', (event) => this.beforeSending(event));
        this.$watch('busy', (busy) => this.$dispatch('name-editor-busy', busy));
    },

    get single() {
        return this.rows.length <= 1 && this.isPlain(this.rows[0]) && this.editing === null;
    },

    get busy() {
        return this.editing !== null || this.confirming !== null;
    },

    get posted() {
        return this.rows.filter((row) => this.holdsText(row));
    },

    get postedDisplay() {
        const shown = this.rows[this.display];
        const index = this.posted.indexOf(shown);

        if (this.posted.length === 0) {
            return '';
        }

        return index === -1 ? 0 : index;
    },

    get draftComposed() {
        return this.draft ? this.compose(this.draft) : '';
    },

    get draftWouldBeDisplayed() {
        if (this.editing === 'new') {
            return this.rows.length === 1 && ! this.holdsText(this.rows[0]);
        }

        return this.editing === this.display;
    },

    get draftProblem() {
        if (this.draft === null) {
            return '';
        }

        if (! this.holdsText(this.draft)) {
            return this.draft.kind === 'phonetic' ? this.labels.missingParts : this.labels.missingText;
        }

        if (this.draft.kind === 'phonetic' && ! filled(this.draft.phonetic_system)) {
            return this.labels.missingSystem;
        }

        if (this.draft.kind === 'phonetic' && this.draftWouldBeDisplayed) {
            return this.labels.phoneticDisplay;
        }

        return '';
    },

    get draftHint() {
        if (this.draftProblem !== '') {
            return this.draftProblem;
        }

        return this.labels.showsAs.replace(':name', () => this.nameOf(this.draft));
    },

    isPlain(row) {
        if (! row) {
            return true;
        }

        return row.kind === 'written' && ! filled(row.formatted_name) && ! filled(row.language) && ! filled(row.script);
    },

    holdsText(row) {
        const keys = row.kind === 'phonetic' ? PART_KEYS : TEXT_KEYS;

        return keys.some((key) => filled(row[key]));
    },

    isPhonetic(row) {
        return row?.kind === 'phonetic';
    },

    chooseDisplay(index, event) {
        if (this.isPhonetic(this.rows[index])) {
            event.preventDefault();

            return;
        }

        this.display = index;
    },

    rowLabel(template, row) {
        return template.replace(':name', () => this.nameOf(row));
    },

    removalPrompt(row) {
        return this.rowLabel(this.replacement === null ? this.labels.removalBlocked : this.labels.removal, row);
    },

    compose(row) {
        const given = [row.given_name, row.additional_name].filter(filled).join(' ');
        const parts = row.name_order === 'family_first'
            ? [row.prefix, row.family_name, given, row.suffix]
            : [row.prefix, given, row.family_name, row.suffix];

        return parts.filter(filled).join(' ');
    },

    nameOf(row) {
        if (! row) {
            return this.labels.unnamed;
        }

        const written = filled(row.formatted_name) && ! this.isPhonetic(row) ? row.formatted_name.trim() : this.compose(row);

        return written || this.labels.unnamed;
    },

    metaOf(row) {
        const kind = row.kind === 'phonetic' && filled(row.phonetic_system)
            ? `${this.labels.kinds.phonetic} (${this.labels.systems[row.phonetic_system] ?? row.phonetic_system})`
            : this.labels.kinds[row.kind];

        return [this.labels.languages[row.language], this.labels.scripts[row.script], kind].filter(Boolean).join(' · ');
    },

    postedValue(row, key) {
        return row[key] ?? '';
    },

    startAdding() {
        this.editing = 'new';
        this.draft = blank('transliteration');
        this.showParts = false;
        this.confirming = null;
        this.focusEditor();
    },

    startEditing(index) {
        const row = this.rows[index];

        this.editing = index;
        this.draft = { ...row };
        this.showParts = row.kind === 'phonetic' || PART_KEYS.some((key) => filled(row[key]));
        this.confirming = null;
        this.focusEditor();
    },

    changeDraftKind() {
        if (this.draft.kind === 'phonetic') {
            this.showParts = true;
        }
    },

    cancelEditing() {
        const index = this.editing;

        this.editing = null;
        this.draft = null;

        if (index === 'new') {
            this.focusAddButton();

            return;
        }

        this.focusRow(index);
    },

    commitOnEnter(event) {
        if (event.target.tagName !== 'INPUT') {
            return;
        }

        event.preventDefault();
        this.commitDraft();
    },

    commitDraft() {
        if (this.draftProblem !== '') {
            return;
        }

        const row = { ...this.draft };

        if (row.kind === 'phonetic') {
            row.formatted_name = null;
        } else {
            row.phonetic_system = null;
        }

        if (this.editing === 'new' && this.draftWouldBeDisplayed) {
            this.rows = [{ ...row, id: this.rows[0].id }];
            this.display = 0;
            this.editing = null;
            this.draft = null;
            this.focusRow(0);

            return;
        }

        if (this.editing === 'new') {
            this.rows.push(row);
            this.editing = null;
            this.draft = null;
            this.focusRow(this.rows.length - 1);

            return;
        }

        const index = this.editing;

        this.rows[index] = row;
        this.editing = null;
        this.draft = null;
        this.focusRow(index);
    },

    remove(index) {
        if (this.rows.length === 1) {
            this.rows = [{ ...blank(), id: this.rows[0].id }];
            this.display = 0;
            this.focusRow(0);

            return;
        }

        if (index !== this.display) {
            this.rows.splice(index, 1);

            if (index < this.display) {
                this.display -= 1;
            }

            this.focusRow(Math.min(index, this.rows.length - 1));

            return;
        }

        const replacement = this.rows.findIndex((row, otherIndex) => otherIndex !== index && ! this.isPhonetic(row));

        this.confirming = index;
        this.replacement = replacement === -1 ? null : replacement;
        this.editing = null;
        this.draft = null;
        this.focusConfirmation(index);
    },

    confirmRemoval() {
        if (this.replacement === null) {
            return;
        }

        const removed = this.confirming;
        const replacement = this.rows[this.replacement];

        this.rows.splice(removed, 1);
        this.display = this.rows.indexOf(replacement);
        this.confirming = null;
        this.focusRow(this.display);
    },

    cancelRemoval() {
        const index = this.confirming;

        this.confirming = null;
        this.focusRow(index);
    },

    sendWithoutName() {
        this.sendsWithoutName = true;
        this.editor.closest('form')?.requestSubmit();
    },

    beforeSending(event) {
        if (this.busy) {
            event.preventDefault();

            return;
        }

        if (this.posted.length === 0 && this.hadName && ! this.sendsWithoutName) {
            event.preventDefault();
            this.warnsOfNoName = true;
        }
    },

    postedName(index, key) {
        return `names[${index}][${key}]`;
    },

    postedKeys() {
        return POSTED_KEYS;
    },

    focusEditor() {
        this.$nextTick(() => this.editor.querySelector('[data-name-draft] select, [data-name-draft] input')?.focus());
    },

    focusRow(index) {
        this.$nextTick(() => {
            if (this.single) {
                this.editor.querySelector('#name-given')?.focus();

                return;
            }

            this.editor.querySelectorAll('[data-name-row] input[type=radio]')[index]?.focus();
        });
    },

    focusAddButton() {
        this.$nextTick(() => this.editor.querySelector('[data-name-add]')?.focus());
    },

    focusConfirmation(index) {
        this.$nextTick(() => {
            const row = this.editor.querySelectorAll('[data-name-row]')[index];
            const target = this.replacement === null ? '[data-name-keep]' : 'select';

            row?.querySelector(`[data-name-removal] ${target}`)?.focus();
        });
    },
});
