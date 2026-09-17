let made = 0;

const reference = () => `n${Date.now().toString(36)}${made++}`;

/**
 * The editor for what an activity of one nature asks for: its groups, the
 * fields nobody grouped, and the choices a field offers.
 *
 * Every method is handed the element that was acted on and finds the editor
 * from there. Neither $el nor $root can be trusted for that: inside a handler
 * $el is whatever was clicked, and several of the controls are components of
 * their own, which makes $root the control rather than the editor.
 */
export default () => ({
    draggedField: null,
    draggedBlock: null,

    editor(el) {
        return el.closest('[data-field-editor]');
    },

    holder(editor) {
        return editor.querySelector('[data-blocks]');
    },

    blocks(editor) {
        return Array.from(this.holder(editor).children).filter((block) => block.matches('[data-block]'));
    },

    groups(editor) {
        return this.blocks(editor).filter((block) => ! block.matches('[data-loose]'));
    },

    loose(editor) {
        return this.holder(editor).querySelector('[data-block][data-loose]');
    },

    build(editor, template, ref) {
        const source = editor.querySelector(`[${template}]`);
        const holder = document.createElement('div');

        holder.innerHTML = source.innerHTML.split('__ref__').join(ref);

        return holder.firstElementChild;
    },

    addBlock(el) {
        const editor = this.editor(el);
        const block = this.build(editor, 'data-block-template', reference());

        this.holder(editor).insertBefore(block, this.loose(editor));
        this.settle(el);

        block.querySelector('input[type="text"]')?.focus();
    },

    removeBlock(el) {
        const editor = this.editor(el);
        const block = el.closest('[data-block]');
        const landing = this.loose(editor).querySelector('[data-fields]');

        block.querySelectorAll('[data-field]').forEach((field) => landing.append(field));
        block.remove();

        this.settle(editor);
    },

    moveBlock(el, delta) {
        const block = el.closest('[data-block]');
        const groups = this.groups(this.editor(el));
        const target = groups[groups.indexOf(block) + delta];

        if (! target) {
            return;
        }

        delta < 0 ? target.before(block) : target.after(block);
        this.settle(el);
    },

    addField(el) {
        const block = el.closest('[data-block]');
        const field = this.build(this.editor(el), 'data-field-template', reference());

        block.querySelector('[data-fields]').append(field);
        this.settle(el);

        field.querySelector('input[type="text"]')?.focus();
    },

    removeField(el) {
        const editor = this.editor(el);

        el.closest('[data-field]').remove();
        this.settle(editor);
    },

    moveField(el, delta) {
        const field = el.closest('[data-field]');
        const siblings = Array.from(field.parentElement.children);
        const target = siblings[siblings.indexOf(field) + delta];

        if (! target) {
            return;
        }

        delta < 0 ? target.before(field) : target.after(field);
        this.settle(el);
    },

    addChoice(el) {
        const field = el.closest('[data-field]');
        const choice = this.build(this.editor(el), 'data-choice-template', field.dataset.reference);

        field.querySelector('[data-choices]').append(choice);
        choice.querySelector('input')?.focus();
    },

    removeChoice(el) {
        el.closest('[data-choice]').remove();
    },

    startField(event, el) {
        event.stopPropagation();
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', el.dataset.reference);
        this.draggedField = el;
    },

    overField(event, el) {
        if (this.draggedField && this.draggedField !== el) {
            event.stopPropagation();
            event.dataTransfer.dropEffect = 'move';
        }
    },

    dropField(event, el) {
        const dragged = this.draggedField;

        if (! dragged) {
            return;
        }

        // Stop the row's own container from also taking the drop and sending the field to the end.
        event.stopPropagation();
        this.stopDragging();

        if (dragged === el) {
            return;
        }

        el.before(dragged);
        this.settle(el);
    },

    overFields(event) {
        if (this.draggedField) {
            event.dataTransfer.dropEffect = 'move';
        }
    },

    dropFields(event, el) {
        const dragged = this.draggedField;

        this.stopDragging();

        if (! dragged) {
            return;
        }

        el.append(dragged);
        this.settle(el);
    },

    startBlock(event, el) {
        if (this.draggedField) {
            return;
        }

        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', el.dataset.reference);
        this.draggedBlock = el;
    },

    overBlock(event, el) {
        if (this.draggedBlock && this.draggedBlock !== el) {
            event.dataTransfer.dropEffect = 'move';
        }
    },

    dropBlock(event, el) {
        const dragged = this.draggedBlock;

        this.stopDragging();

        if (! dragged || dragged === el || el.matches('[data-loose]')) {
            return;
        }

        el.before(dragged);
        this.settle(el);
    },

    stopDragging() {
        this.draggedField = null;
        this.draggedBlock = null;
    },

    /**
     * Rewrite what every row says about where it sits, which is the only thing
     * the server reads the order from. The block holding the fields nobody
     * grouped always stays last on screen and first in the saved order.
     */
    settle(el) {
        const editor = this.editor(el);

        this.blocks(editor).forEach((block, index) => {
            const isLoose = block.matches('[data-loose]');
            const own = block.querySelector('[data-block-position]');

            if (own) {
                own.value = index + 1;
            }

            const fields = Array.from(block.querySelectorAll('[data-field]'));

            fields.forEach((field, place) => {
                field.querySelector('[data-field-position]').value = place + 1;
                field.querySelector('[data-field-group]').value = isLoose ? '' : block.dataset.reference;
            });

            const count = block.querySelector('[data-count]');

            if (count) {
                count.textContent = String(fields.length);
            }

            block.querySelector('[data-empty]')?.classList.toggle('hidden', fields.length > 0);
        });
    },

    init() {
        this.settle(this.$el);
    },
});
