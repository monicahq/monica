# Tasks: Delete actions drawn as danger

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: no new test (research R8, look only); every existing test must still pass, and the whole suite runs because shared components change.

## Phase 1: Setup

- [X] T001 Create branch `2026-10-25-delete-action-danger-styling` from an up to date `main` and carry the four files of specs/084-delete-action-danger-styling/ onto it (if `.git/config.lock` blocks `checkout -b`, build it with plumbing)

## Phase 2: Foundational

- [X] T002 In the `$tint` `match` of resources/views/components/delete-action.blade.php, give `danger` an empty string so the solid red button no longer names `text-error` over `text-white` (research R5)
- [X] T003 Create resources/views/components/delete-section.blade.php (research R1): first line `{{-- A section that deletes what the screen is about: a red panel, what goes with it, and the button. --}}`, a `@var` block, `@props(['title', 'icon' => 'close'])`, an `x-panel tone="danger"` passing title, icon and `$attributes`, holding one `flex flex-wrap items-center gap-x-6 gap-y-3` row with the default slot (when not empty) in a `min-w-60 flex-1 flex flex-col gap-2 text-sm text-pretty text-body` block and the `trigger` slot in a `shrink-0` block
- [X] T004 Add `x-delete-section` to the component table of .claude/rules/components.md: "A red panel that deletes the screen's own record, what goes with it, and the button"

Checkpoint: the component exists and nothing renders differently yet.

## Phase 3: User Story 1, a deletion section reads as dangerous (P1) MVP

Independent test: quickstart rows 1, 2, 4, 5 (first half), 6 and 10.

- [X] T005 [US1] Replace the `x-panel tone="danger"` of the deletable branch with `x-delete-section` (title `__('Delete this type')`, icon `close`, paragraph as default slot, the existing `x-delete-action variant="danger"` as `trigger`) in resources/views/app/settings/account/life-event-types/form.blade.php, and check it renders as before
- [X] T006 [P] [US1] Replace the `x-panel tone="danger"` with `x-delete-section` (title `__('Delete this vault')`, icon `vault`, both paragraphs as default slot, the `x-modal.warning` with its `x-button.danger` trigger as `trigger`, dropping `sm:ml-auto`) in resources/views/app/vaults/settings.blade.php
- [X] T007 [P] [US1] Replace the plain `x-panel :title="__('Delete contact')"` with `x-delete-section` (icon `people`, `#delete-contact-reason` paragraph as default slot) and turn the deletable trigger into `x-button.danger type="button" @click="open = true"` without `text-error` or `sm:ml-auto` in resources/views/app/vaults/contacts/edit.blade.php
- [X] T008 [P] [US1] Move the `x-modal.warning` out of the bar holding Cancel and Save into an `x-delete-section` (title `__('Delete the template')`) placed after that bar, trigger `x-button.danger type="button" @click="open = true"`, in resources/views/app/settings/account/templates/form.blade.php (research R3)
- [X] T009 [P] [US1] Move the `@include('...entity-types._delete')` out of the bar holding Cancel and Save to after it in resources/views/app/settings/account/entity-types/form.blade.php, and in resources/views/app/settings/account/entity-types/_delete.blade.php wrap the deletable branch in `x-delete-section` (title `__('Delete this type')`) with `x-delete-action variant="danger"` as `trigger`
- [X] T010 [P] [US1] Same as T009 for resources/views/app/settings/account/relationship-types/form.blade.php and resources/views/app/settings/account/relationship-types/_delete.blade.php
- [X] T011 [US1] Run tests/Feature/Controllers/App/Vaults/ContactControllerTest.php, tests/Feature/Controllers/App/Settings/Account/EntityTypes/EntityTypeControllerTest.php, tests/Feature/Controllers/App/Settings/Account/RelationshipTypes/RelationshipTypeControllerTest.php and the vault, template and life event type controller tests after `php artisan view:clear`; rewrite any assertion on a class string into one on behaviour (.claude/rules/components.md)

## Phase 4: User Story 2, a refused deletion still reads as a deletion (P2)

Independent test: quickstart rows 3 and 5 (second half). Depends on the files of Phase 3.

- [X] T012 [US2] In resources/views/app/vaults/contacts/edit.blade.php turn the refused `x-button.secondary aria-disabled` into `x-button.danger` keeping `type="button"`, `aria-disabled="true"` and `aria-describedby="delete-contact-reason"` (research R4)
- [X] T013 [P] [US2] In resources/views/app/settings/account/entity-types/_delete.blade.php wrap the refused branch in the same `x-delete-section`, the `#entity-type-delete-reason` paragraph as default slot without its own size and colour classes, the button as `x-button.danger` with its `aria-disabled` and `aria-describedby`
- [X] T014 [P] [US2] Same as T013 for `#relationship-type-delete-reason` in resources/views/app/settings/account/relationship-types/_delete.blade.php
- [X] T015 [P] [US2] In resources/views/app/settings/account/life-event-types/form.blade.php replace the refused plain `x-panel` with `x-delete-section` (title `__('Delete this type')`), keeping the `.claude/rules/policies.md` comment, `#life-event-type-delete-reason` as default slot and the button as `x-button.danger` with its `aria-disabled` and `aria-describedby`

## Phase 5: User Story 3, delete buttons inside lists (P3)

Independent test: quickstart rows 7, 8 and 9. Needs only Phase 2.

- [X] T016 [US3] In the `$tint` `match` of resources/views/components/delete-action.blade.php give `ghost` `text-error` instead of `hover:text-error` (research R5), and run tests/Feature/Views/Components/DeleteActionTest.php
- [X] T017 [P] [US3] Replace `hover:text-error` with `text-error` on the "Remove" trigger in resources/views/app/settings/account/templates/_section.blade.php
- [X] T018 [P] [US3] Same as T017 in resources/views/app/settings/account/templates/_pages.blade.php
- [X] T019 [P] [US3] Same as T017 on the "Delete the draft" trigger in resources/views/app/settings/account/templates/builder.blade.php
- [X] T020 [P] [US3] Same as T017 on the category deletion link in resources/views/app/settings/account/relationship-types/_category.blade.php
- [X] T021 [US3] Add `text-error` to the "Remove the photo" `x-button.ghost` trigger in resources/views/app/vaults/contacts/edit.blade.php (after T012, same file)

## Phase 6: Polish

- [X] T022 Search resources/views for `hover:text-error`, `x-button.secondary` with `text-error`, and `tone="danger"` panels, and confirm every remaining one is a row, a part of a screen or a non-delete action left alone by FR-009 (research R6)
- [X] T023 Run `php artisan view:clear`, then `php artisan test --compact`, `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent` and `php artisan monica:localize --check` (no new key expected, research R7)
- [X] T024 Walk through quickstart.md in the light and the dark theme, with screenshots through the `take-screenshot` skill, and check contrast and focus (FR-011)
- [X] T025 Record the change with the `feature-documentator` skill in docs/FEATURES.md

## Dependencies

- Phase 1, then Phase 2, block everything.
- US1 (Phase 3) is the MVP. US2 edits the same files and follows it.
- US3 (Phase 5) needs only Phase 2 and can run beside Phases 3 and 4, except T021, which waits for T012.
- Phase 6 comes last. Tasks touching the same file run in order; `[P]` tasks touch files nobody else in the phase touches.

## Parallel examples

- Phase 3: T006 to T010 together, after T005 proves the component.
- Phase 4: T013, T014 and T015 beside T012.
- Phase 5: T017 to T020 together, beside Phase 3.

## Implementation strategy

Ship Phases 1 to 3 first: every deletion section red, the same on six screens. Add the refused states
(Phase 4), then the quieter red of rows and parts of screens (Phase 5). Phase 6 closes every increment.
