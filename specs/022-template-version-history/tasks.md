---

description: "Task list for template version history"
---

# Tasks: Template version history

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Format**: `[ID] [P?] [Story] Description` — `[P]` means a different file with no incomplete dependency.

**Tests**: required. `CLAUDE.md` makes a test part of every change, so each story carries its own.

No Setup phase: the feature adds no dependency, no migration and no factory. Everything it reads shipped in `2026_08_09_*`.

## Phase 1: Foundational (blocks every story)

- [X] T001 Add `GET settings/account/templates/{template}/versions` named `settings.templateVersions.index` to `routes/web.php`, in the authenticated settings group beside the existing `POST` on that URL
- [X] T002 Create `app/Http/Controllers/App/Settings/Account/Templates/TemplateVersionHistoryController.php` with an `index(Request $request, Template $template): View` that builds the view model and returns `app.settings.account.templates.versions`

**Checkpoint**: the URL resolves and is compartmented by `ResolvesModelInAccount`.

---

## Phase 2: User Story 1 — Read what each version contains (P1) 🎯 MVP

- [X] T003 [P] [US1] Create `app/ViewModels/Settings/Account/Templates/TemplateVersionEntry.php`: one version's label, status label and badge tone (draft warning, published success, retired neutral), `isDraft()` for the hollow timeline dot, date line (`isoFormat('LL')` for a published version, `diffForHumans()` for a draft), the version it is based on, its change note with the draft fallback, its page/section/field counts and its "added over its predecessor" summary, all counted phrases through `trans_choice()`
- [X] T004 [US1] Add `pages()` to `TemplateVersionEntry`, returning each page with its label, position label, section/field meta, and its sections read from `assembly` grouped by `template_version_page_id`, each carrying the section name (`title_override` first), `x-section-mark` icon and colour, section version number, field count, `isNewInThisVersion()` by set difference of `section_version_id` against the predecessor's assembly, and its fields with `FieldCatalogueViewModel::labelFor()`
- [X] T005 [US1] Create `app/ViewModels/Settings/Account/Templates/TemplateVersionHistoryViewModel.php` with `template()`, `user()`, `backUrl()` to the builder, `hasVersions()`, and `entries()` mapping `$template->versions` to `TemplateVersionEntry` after one nested eager load of `versions.pages`, `versions.assembly.sectionVersion.section` and `versions.assembly.sectionVersion.fields`
- [X] T006 [US1] Create `resources/views/app/settings/account/templates/versions.blade.php`: `x-shell` with the settings sidebar, a breadcrumb `x-button.bar` back to the builder, the title, the intro paragraph explaining that publishing freezes a version and that records stay on theirs, the timeline list, and an `x-empty-state` when the template has no version
- [X] T007 [US1] Create `resources/views/app/settings/account/templates/_version.blade.php`: the dot and connector column, the entry header (label, `x-badge`, date line, based-on), change note, counts and summary, and the expander copied from `resources/views/app/settings/account/logs/_email-sent-row.blade.php` — a real `<button>` with `:aria-expanded`, `x-cloak`, `x-show`, `x-transition` and the rotating chevron
- [X] T008 [US1] Add the nested disclosure inside `_version.blade.php`: pages in order, each section row with `x-section-mark`, its version, a "New in this version" `x-badge`, its field count and its own expander revealing the fields with their type
- [X] T009 [P] [US1] Create `tests/Unit/ViewModels/Settings/Account/Templates/TemplateVersionEntryTest.php` covering the labels, tones, dates, change note fallback, counts, the added summary, the first version having none, and the new-in-this-version mark
- [X] T010 [P] [US1] Create `tests/Unit/ViewModels/Settings/Account/Templates/TemplateVersionHistoryViewModelTest.php` covering the ordering newest first, that a retired version is listed, an empty template, and that the entries read the placed section version rather than the section
- [X] T011 [US1] Create `tests/Feature/Controllers/App/Settings/Account/Templates/TemplateVersionHistoryControllerTest.php` covering a 200 showing every version with its pages, sections and fields, the 404 for another account's template, the empty state, and that nothing is written

**Checkpoint**: the screen is complete and testable on its own at `/settings/account/templates/{slug}/versions`.

---

## Phase 3: User Story 2 — Find the history from the builder (P1)

- [X] T012 [US2] Add `hasHistory(): bool` to `app/ViewModels/Settings/Account/Templates/TemplateBuilderViewModel.php`, true when `$this->template->versions->count() > 1`
- [X] T013 [US2] Add the `x-link turbo` to `resources/views/app/settings/account/templates/builder.blade.php`, after the existing **Edit** link inside the same paragraph, separated by a muted middot and wrapped in `@if ($viewModel->hasHistory())`
- [X] T014 [P] [US2] Extend `tests/Unit/ViewModels/Settings/Account/Templates/TemplateBuilderViewModelTest.php` with `hasHistory()` true for two versions and false for one
- [X] T015 [US2] Extend `tests/Feature/Controllers/App/Settings/Account/Templates/TemplateBuilderControllerTest.php` asserting the link is present with two versions, absent with one, and points at the history route

**Checkpoint**: the screen is reachable, and the round trip back to the builder works.

---

## Phase 4: Polish & cross-cutting

- [X] T016 Run `php artisan monica:localize` and translate every reported string in the nine configured locales, following `.claude/rules/translations.md` for each one's punctuation and quotation
- [X] T017 Run `php artisan monica:localize --check` and correct every counted phrase and typography breach it reports
- [X] T018 Walk the accessibility pass in [quickstart.md](quickstart.md): keyboard only, `aria-expanded` on every expander, visible focus, 200% zoom
- [X] T019 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --memory-limit=2G`
- [X] T020 Walk [quickstart.md](quickstart.md) end to end against `http://erica.test`, then run `php artisan test --compact --filter=TemplateVersionHistory` and the templates view model and controller suites

---

## Dependencies & Execution Order

- T001 → T002 → everything.
- Phase 2 order: T003 → T004 (same file), T005 needs T003 and T004, T006 → T007 → T008 (T007 and T008 are the same file), T009 and T010 need T005, T011 needs T008.
- Phase 3 depends on T001 only, for the route the link points at, so it can run beside Phase 2.
- Phase 4 depends on every string and file above existing.

### Parallel opportunities

- T009 and T010 together, once T005 lands.
- T014 beside T009 and T010.
- Phase 2 and Phase 3 by two people, once T002 is in.

## Implementation Strategy

US1 and US2 are both P1 and together are the smallest thing worth shipping: US1 without US2 is a screen with no door, US2 without US1 is a link to nothing. Build T001–T011, validate the screen by URL, then add T012–T015 and validate the round trip. Phase 4 is a delivery condition, not a follow-up: `monica:localize --check` and PHPStan level 5 both run in CI.
