---
description: "Task list for notes in a repeater"
---

# Tasks: Notes in a repeater

**Input**: Design documents from `specs/049-repeater-notes/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, The Office data, no comments, no class string or markup order assertion. Rules in the view model test, copy and ARIA in the screen test.

**Organization**: the view model key is the foundation. US1 draws the note, US2 adds Read more, US3 the empty state. US2 and US3 each need the foundation and the partial from US1.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, configuration or data change.

## Phase 2: Foundational

- [X] T001 In `app/ViewModels/Vaults/ContactViewModel.php`, add a private `readsAsNotes(Field $field): bool`: true when `$field->type->isRepeater()` and the children with `type->storesValue()` are exactly one, of type `FieldTypeEnum::Textarea` (research R1)
- [X] T002 In `app/ViewModels/Vaults/ContactViewModel.php`, change `fieldsIn(StructureVersionSection $row)` to add `'notes' => $this->notesOf($field, $row)`, and add a private `notesOf()` returning `null` unless `readsAsNotes()`, else `['showsLabel' => $field->label !== $this->nameOf($row), 'entries' => [...]]`; each entry from the field’s rows in `repetitions()` order is `['id' => $row->id, 'shown' => first two paragraphs, 'hidden' => the rest, 'addedLine' => __('Added :date', ['date' => ...])]`; paragraphs are `value_text` split with `preg_split('/\R\s*\R/', trim(...))`, and a row whose text is null or blank is left out (research R2, R3). Update the `@return` array shape of `fieldsIn()`
- [X] T003 In `app/ViewModels/Vaults/ContactViewModel.php`, write the date: the row’s `created_at->setTimezone($this->user->timezone)` through `DateFormatEnum::write()` with the user’s `date_format->value`, falling back to `AvailableLocales::details(app()->getLocale())['date_format']`, as `ContactTimelineBlock::dateFormat()` does (research R7)
- [X] T004 In `tests/Unit/ViewModels/Vaults/ContactViewModelTest.php`, add tests building a section "Notes" holding a `Repeater` field "Notes" with one `Textarea` child (`parent_field_id`), rows with `FieldValueRow::factory()` and `value_text` values: `it_reads_a_repeater_of_one_long_text_as_notes` (entries, `showsLabel` false); `it_names_a_note_repeater_whose_label_is_not_the_section_name` ("Medical history" in "Health", `showsLabel` true); `it_keeps_the_first_two_paragraphs_of_a_note_in_sight` (five paragraphs: two shown, three hidden, single line breaks kept); `it_leaves_out_a_note_row_with_nothing_written`; `it_dates_a_note_in_the_date_format_of_the_reader` (a user with a date format and a time zone, `created_at` fixed); `it_does_not_read_a_standalone_long_text_or_a_mixed_repeater_as_notes` (`notes` null)

**Checkpoint**: T004 passes.

## Phase 3: User Story 1 - Read a note (Priority: P1)

**Goal**: a written note reads as full width prose with its added date.

**Independent Test**: spec US1.

- [X] T005 [US1] Create `resources/views/app/vaults/contacts/_notes.blade.php` (research R5, R8): first line `{{-- A field of notes, read as text across its section. --}}`, then a `@var` block for `array<string, mixed> $field` and `array<string, mixed> $section`; one `<div>` with `border-b border-hairline px-6 py-5 last:border-b-0 flex flex-col gap-2`; a `<dt>` holding `$field['label']`, `text-sm font-semibold text-ink-soft` when `showsLabel`, else `sr-only`; a `<dd class="m-0">` looping `entries`, each a block with its `shown` paragraphs as `<p class="whitespace-pre-line">` inside `flex flex-col gap-3 text-[14.5px]/[1.7] text-pretty text-ink max-w-[68ch] break-words`, then `addedLine` in `text-xs text-muted`; entries separated by `border-t border-hairline-soft` with padding; Tailwind utilities only
- [X] T006 [US1] In `resources/views/app/vaults/contacts/_section.blade.php`, inside the `@forelse` of the reading state, `@include('app.vaults.contacts._notes', ['field' => $field, 'section' => $section])` when `$field['notes'] !== null`, else the existing `x-detail-list.row`
- [X] T007 [US1] In `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php`, add `it_shows_a_note_across_its_section`: a contact with a note row renders its text and its "Added" line on `contacts.show` (build the section as T004 does)

**Checkpoint**: quickstart rows 1 and 7.

## Phase 4: User Story 2 - Read a long note (Priority: P2)

**Goal**: a note past two paragraphs collapses behind Read more.

**Independent Test**: spec US2.

- [X] T008 [US2] In `resources/views/app/vaults/contacts/_notes.blade.php`, when an entry has `hidden` paragraphs (research R4): wrap the entry in `x-data="{ expanded: false }"`; render the hidden paragraphs in a block with `id="note-{{ $entry['id'] }}-rest"`, the same paragraph classes and `x-show="expanded"`, without `x-cloak`; add a `<button type="button" x-cloak>` with `@click="expanded = ! expanded"`, `:aria-expanded="expanded.toString()"`, `aria-controls` on that id, and `x-text="expanded ? @js(__('Show less')) : @js(__('Read more'))"` over the `Read more` wording; a text button with `focus-ring` beside `addedLine` from `xs` up, and under `max-xs:` a full width `min-h-11` bordered button above it (frame 1f). Put `@js()` on this plain `<button>`, never on a component tag
- [X] T009 [US2] In `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php`, add `it_folds_a_long_note_behind_read_more`: a five paragraph note renders "Read more", `aria-controls="note-{id}-rest"` and the `id` it names, and the text of the fifth paragraph (readable without scripts)

**Checkpoint**: quickstart rows 2, 4 and 6.

## Phase 5: User Story 3 - An empty note repeater (Priority: P3)

**Goal**: an empty note repeater says so and leads to the editor.

**Independent Test**: spec US3.

- [X] T010 [US3] In `resources/views/app/vaults/contacts/_notes.blade.php`, when `entries` is empty: a `flex flex-wrap items-center justify-between gap-3` line with "No note yet" in `text-base text-muted`, and, when `$field['isEditable']` and `$section['isEditable']`, `<x-button.secondary size="small" :href="$section['editUrl']" :x-target="$section['regionId']">` with `<x-icon name="plus" :size="15" />` and "Add a note"; `max-xs:` full width and `min-h-11` (read `x-button.secondary` first)
- [X] T011 [US3] In `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php`, add `it_offers_to_add_a_note_when_there_is_none`: a note repeater with no row renders "No note yet", "Add a note" and the section’s `contacts.sections.edit` address

**Checkpoint**: quickstart row 3.

## Phase 6: Polish

- [X] T012 Run `php artisan monica:localize`, translate `Added :date`, `No note yet`, `Add a note`, `Read more`, `Show less` in every configured locale following `.claude/rules/translations.md` and its glossary ("note"), then `php artisan monica:localize --check`
- [X] T013 Run the whole suite, since `_section.blade.php` draws every section: `php artisan test --compact`; fix any assertion that described the old repeater markup by asserting the behaviour instead
- [X] T014 Run `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [ ] T015 Walk `quickstart.md` by hand, desktop and 390px, keyboard only

## Dependencies

- T001 → T002 → T003 → T004. Phase 2 blocks every story.
- T005 → T006 → T007. US2 (T008, T009) and US3 (T010, T011) both edit `_notes.blade.php` after T005, so they run one after the other, in either order.
- Polish after all stories.

## Parallel Opportunities

Few: most tasks touch `ContactViewModel.php` or `_notes.blade.php`. T012 translations may run beside T013 once the strings exist.

## Implementation Strategy

MVP is Phase 2 and US1: every note reads as prose, long ones in full. US2 then folds long notes, US3 adds the empty state. Commit after each phase.
