export default (projection) => ({
    types: projection.types,
    copy: projection.copy,
    contactName: projection.contactName,
    searchUrl: projection.searchUrl,
    query: projection.query,

    typeId: projection.typeId,
    mode: projection.mode,
    first: projection.firstName,
    last: projection.lastName,
    picked: projection.otherContactId,
    pickedName: projection.otherContactName,
    swapped: projection.swapped,

    get type() {
        return this.types[this.typeId] ?? null;
    },

    get hasType() {
        return this.type !== null;
    },

    get symmetric() {
        return this.hasType && this.type.isSymmetric;
    },

    get roleA() {
        if (!this.hasType) {
            return '';
        }

        return this.swapped ? this.type.roleB : this.type.roleA;
    },

    get roleB() {
        if (!this.hasType) {
            return '';
        }

        return this.swapped ? this.type.roleA : this.type.roleB;
    },

    get other() {
        if (this.mode === 'unknown') {
            return this.copy.nameless.replace(':role', this.roleB.toLowerCase());
        }

        if (this.mode === 'known') {
            return [this.first.trim(), this.last.trim()].filter(Boolean).join(' ');
        }

        if (this.mode === 'existing') {
            return this.pickedName;
        }

        return '';
    },

    get complete() {
        if (!this.hasType) {
            return false;
        }

        if (this.mode === 'unknown') {
            return true;
        }

        if (this.mode === 'known') {
            return this.first.trim() !== '';
        }

        return this.mode === 'existing' && this.picked !== '';
    },

    get asksForAContact() {
        return this.mode === 'unknown' || this.mode === 'known';
    },

    get previewOne() {
        return this.complete ? this.sentence(this.contactName, this.other, this.roleA) : '';
    },

    get previewTwo() {
        if (!this.complete) {
            return '';
        }

        const capitalised = this.other.charAt(0).toUpperCase() + this.other.slice(1);

        return this.copy.inverse.replace(
            ':sentence',
            this.sentence(capitalised, this.contactName, this.roleB),
        );
    },

    get hint() {
        if (this.complete) {
            return this.copy.ready;
        }

        return this.hasType ? this.copy.needOther : this.copy.needType;
    },

    sentence(one, two, role) {
        return this.copy.sentence
            .replace(':one', one)
            .replace(':two', two)
            .replace(':role', role.toLowerCase());
    },

    chooseType() {
        this.swapped = false;
    },

    swap() {
        if (!this.symmetric) {
            this.swapped = !this.swapped;
        }
    },

    choose(id, name) {
        this.picked = String(id);
        this.pickedName = name;
    },

    forget() {
        this.picked = '';
        this.pickedName = '';
    },

    search() {
        this.$ajax(`${this.searchUrl}?query=${encodeURIComponent(this.query)}`, {
            target: 'relationship-candidates',
        });
    },
});
