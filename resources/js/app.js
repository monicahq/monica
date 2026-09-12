import 'instant.page';

import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';
import morph from '@alpinejs/morph';
import focus from '@alpinejs/focus';
import anchor from '@alpinejs/anchor';
import ajax from '@imacrayon/alpine-ajax';

import confirmPassword from './confirm-password';
import fieldReminder from './field-reminder';
import passkey from './passkey';
import timezonePicker from './timezone-picker';

window.Turbo = Turbo;

Turbo.session.drive = false;

Alpine.data('confirmPassword', confirmPassword);
Alpine.data('fieldReminder', fieldReminder);
Alpine.data('passkey', passkey);
Alpine.data('timezonePicker', timezonePicker);

window.Alpine = Alpine;

Alpine.plugin(morph);
Alpine.plugin(ajax);

Alpine.plugin(focus);

Alpine.plugin(anchor);

ajax.configure({ mergeStrategy: 'morph' });

window.oversizedFiles = (fileList, maxKilobytes) =>
    Array.from(fileList ?? []).filter((file) => file.size > maxKilobytes * 1024);

window.slugify = (value) =>
    (value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 64);

window.copyText = async (text) => {
    if (window.isSecureContext && navigator.clipboard) {
        try {
            await navigator.clipboard.writeText(text);

            return true;
        } catch {
        }
    }

    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.top = '0';
    area.style.opacity = '0';

    document.body.appendChild(area);
    area.select();

    let copied = false;

    try {
        copied = document.execCommand('copy');
    } catch {
        copied = false;
    }

    document.body.removeChild(area);

    return copied;
};

Alpine.data('sortableRows', () => ({
    draggingId: null,
    hoveringId: null,

    start(event, id) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(id));
        this.draggingId = id;
    },

    over(event, id) {
        event.dataTransfer.dropEffect = 'move';
        this.hoveringId = id;
    },

    stop() {
        this.draggingId = null;
        this.hoveringId = null;
    },

    drop(event, targetId) {
        const draggedId = this.draggingId;
        const rows = event.currentTarget.closest('[data-sortable]');
        this.stop();

        if (! draggedId || draggedId === targetId || ! rows) {
            return;
        }

        const ids = Array.from(rows.querySelectorAll('[data-row-id]')).map((row) => Number(row.dataset.rowId));
        const from = ids.indexOf(draggedId);
        const to = ids.indexOf(targetId);

        if (from < 0 || to < 0) {
            return;
        }

        const form = rows.querySelector(`[data-position-form="${draggedId}"]`);
        form.querySelector('input[name="position"]').value = to > from ? to : to + 1;
        form.requestSubmit();
    },
}));

Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),

    set(dark) {
        this.dark = dark;
        document.documentElement.classList.toggle('dark', dark);

        try {
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        } catch (e) {
        }
    },

    toggle() {
        this.set(! this.dark);
    },
});

document.addEventListener('turbo:load', () => {
    if (! document.documentElement.dataset.alpine) {
        document.documentElement.dataset.alpine = 'started';
        Alpine.start();
    }

    Alpine.initTree(document.body);
});
