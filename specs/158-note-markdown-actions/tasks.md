# Tasks: Markdown actions on a note

**Input**: `specs/158-note-markdown-actions/` (spec.md, plan.md, research.md)

**Tests**: required by constitution principle III; one layer each, as listed in plan.md.

## Phase 1: Setup

- [X] T001 Read `.claude/skills/views/SKILL.md`, `.claude/skills/controllers/SKILL.md`, `.claude/skills/routes/SKILL.md`, `.claude/skills/view-models/SKILL.md`, `.claude/skills/components/SKILL.md` and `.claude/rules/components.md` before writing code

## Phase 2: Foundational (blocks every story)

- [X] T002 [P] Add `public static function document(string $title, ?string $body): string` to `app/Helpers/KnowledgeMarkdown.php`, returning `"# {$title}\n\n{$body}"`, or `"# {$title}\n"` when `blank($body)` (research R1)
- [X] T003 [P] Test `document()` in `tests/Unit/Helpers/KnowledgeMarkdownTest.php`: title as level one heading, one blank line, body byte for byte (with a list, a table and a task); a blank body gives the heading alone
- [X] T004 [P] Create `resources/views/components/markdown-mark.blade.php`: props `size` (height, default 12) and `label` (nullable); `viewBox="0 0 208 128"`, `fill="currentColor"`, width `(int) round($size * 208 / 128)`, the two paths from the user's SVG (outer frame with `fill-rule="evenodd" clip-rule="evenodd"`, then the M and arrow); `aria-hidden="true"` without a label, `role="img"`, `aria-label` and `<title>` with one, as `resources/views/components/icon.blade.php` does; classes `block shrink-0` merged with `$attributes`; first line comment and `@var` block per `.claude/rules/code-style.md` (research R6)
- [X] T005 [P] Add the `copy` (`<rect x="5.4" y="5.4" width="8.2" height="8.2" rx="1.5"></rect><path d="M10.6 5.4V3.9a1.5 1.5 0 0 0-1.5-1.5H3.9a1.5 1.5 0 0 0-1.5 1.5v5.2a1.5 1.5 0 0 0 1.5 1.5h1.5"></path>`) and `download` (`<line x1="8" y1="2.6" x2="8" y2="9.8"></line><path d="M5 7 8 10l3-3"></path><path d="M2.8 11.2v1.2a1.2 1.2 0 0 0 1.2 1.2h8a1.2 1.2 0 0 0 1.2-1.2v-1.2"></path>`) glyphs to `$paths` in `resources/views/components/icon.blade.php` (research R7)
- [X] T006 Add `markdown(): string` (calls `KnowledgeMarkdown::document($this->note->title, $this->note->body)`) and `markdownUrl(): string` (route `knowledge.noteMarkdown.show`) to `app/ViewModels/Vaults/KnowledgeNoteViewModel.php` (depends on T002, T010)
- [X] T007 In `resources/views/app/vaults/knowledge/note.blade.php`, reading branch only, wrap the header and the note body in one `<div class="contents" x-data="{ source: false, copyState: 'idle', markdown: @js($viewModel->markdown()) }">`; when `! $viewModel->isEmpty()`, put before Edit an `<x-actions-menu :label="__('Markdown')" variant="secondary" width="wide">` whose `glyph` slot holds `<x-markdown-mark :size="12" />` and whose `text` slot holds `<span x-text="copyState === 'copied' ? @js(__('Copied')) : @js(__('Markdown'))">{{ __('Markdown') }}</span><x-icon name="chevron-down" :size="14" class="text-muted" />`; under the header a `<p role="status" class="text-sm text-muted" x-text="...">` showing nothing, or "Could not copy. Download the file instead." when `copyState === 'failed'` (research R5; depends on T004, T005, T006)

## Phase 3: User Story 1, copy (P1) MVP

**Goal**: Copy puts the note's Markdown on the clipboard. **Test**: choose Copy, paste, compare with the source.

- [X] T008 [US1] In the menu of T007, add a `<button type="button" role="menuitem">` laid out like the contact screen's "Copy as Markdown" line in `resources/views/app/vaults/contacts/show.blade.php`, with `<x-icon name="copy" :size="16" />` and `{{ __('Copy') }}`; `@click` closes the menu, focuses `$refs.menuTrigger`, then `window.copyText(markdown).then((ok) => { copyState = ok ? 'copied' : 'failed'; if (ok) setTimeout(() => { if (copyState === 'copied') copyState = 'idle' }, 2000) })`
- [X] T009 [US1] Extend `it_shows_a_note_with_its_markdown_drawn` in `tests/Feature/Controllers/App/Vaults/KnowledgeNoteControllerTest.php` to assert the reading view offers "Copy", "View as MD" and "Download as MD"; add a test that an empty note offers none of them

## Phase 4: User Story 3, download (P3, built before US2 since T006 needs its route)

**Goal**: Download as MD saves the document as a `.md` file. **Test**: download a note titled "Diagnose Wi-Fi and home network".

- [X] T010 [P] [US3] Add `Route::get('vaults/{vault}/knowledge/notes/{knowledgeNote}/markdown', [KnowledgeNoteMarkdownController::class, 'show'])->whereUuid('knowledgeNote')->name('knowledge.noteMarkdown.show');` after `knowledge.noteTasks.update` in `routes/web.php`, with its `use`
- [X] T011 [US3] Create `app/Http/Controllers/App/Vaults/KnowledgeNoteMarkdownController.php`, `show(Request $request, Vault $vault, KnowledgeNote $knowledgeNote): StreamedResponse`, mirroring `ContactMarkdownController`: `response()->streamDownload()` of `KnowledgeMarkdown::document(...)`, file name `(Str::slug($knowledgeNote->title) ?: 'note').'.md'` written with interpolation, header `Content-Type: text/markdown; charset=utf-8` (research R2, R3)
- [X] T012 [US3] Add the item `<x-actions-menu.item :href="$viewModel->markdownUrl()" download data-no-instant>` with `<x-slot:icon><x-icon name="download" :size="16" /></x-slot:icon>` and `{{ __('Download as MD') }}`, last in the menu of `resources/views/app/vaults/knowledge/note.blade.php`
- [X] T013 [US3] Create `tests/Feature/Controllers/App/Vaults/KnowledgeNoteMarkdownControllerTest.php` (The Office data): the file holds the document, is named `<slug>.md` with the `text/markdown; charset=utf-8` type; a title like `???` gives `note.md`; a note reached through another vault of the same account is not found

## Phase 5: User Story 2, view the source (P2)

**Goal**: View as MD swaps the drawn note for its source and back. **Test**: toggle on a note with headings and tasks.

- [X] T014 [US2] In the menu, between Copy and Download, add a `button role="menuitem"` with `<x-icon name="code" :size="16" />` and `<span x-text="source ? @js(__('View formatted')) : @js(__('View as MD'))">{{ __('View as MD') }}</span>`; `@click` closes the menu, focuses the trigger, toggles `source`
- [X] T015 [US2] In `resources/views/app/vaults/knowledge/note.blade.php`, give the existing drawn-note block `x-show="! source"` and add after it `<pre x-cloak x-show="source" class="max-w-[68ch] overflow-x-auto whitespace-pre-wrap rounded-lg border border-hairline bg-card p-4 font-mono text-xs leading-[1.65] text-body">{{ $viewModel->markdown() }}</pre>`

## Phase 6: User Story 4, the Markdown mark (P3)

**Goal**: the mark is reusable anywhere. **Test**: render with and without a label.

- [X] T016 [P] [US4] Create `tests/Feature/Views/Components/MarkdownMarkTest.php`: without a label it is `aria-hidden="true"`; with `label="Markdown"` it is `role="img"` named "Markdown" and carries the `<title>`; no class or path assertions

## Phase 7: Polish

- [X] T017 Run `php artisan monica:localize`, translate "Markdown", "View as MD", "View formatted", "Download as MD" into every locale through Sonnet subagents (translations skill), then `php artisan monica:localize --check`
- [X] T018 [P] Write the changelog entry with the `changelog` skill
- [X] T019 Run `vendor/bin/pint --dirty --format agent`, `bun run build`, then the full suite with `composer test:parallel`
- [X] T020 Look at the screen in light and dark, at desktop and phone width, with the `take-screenshot` skill; check keyboard use of the menu
- [X] T021 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff, then the `definition-of-done` skill

## Dependencies

- Phase 2 before every story. T006 needs T010 (route name), so T010 runs with Phase 2.
- US1, US2 and US3 all edit `note.blade.php`: run them one after another (US1, US3, US2).
- US4's test (T016) only needs T004. Polish comes last.

## Parallel examples

- T002, T003, T004, T005, T010 touch different files and can start together.
- T013 and T016 can be written at the same time.

## Implementation strategy

MVP is Phase 2 plus US1: the menu with Copy. Then download, then source view, then polish.
