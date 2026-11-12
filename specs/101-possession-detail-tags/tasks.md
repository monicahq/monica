# Tasks: Tags on the Possession Page

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/web.md](contracts/web.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office. Read the matching skill (`actions`, `models`, `view-models`, `views`, `controllers`, `routes`, `tests`) before each kind of file, and `.claude/rules/components.md` before any Blade.

## Phase 1: Setup

- [X] T001 [P] Update `spec/models/062-possession-in-progress.md` and `spec/models/064-possession-tag-in-progress.md`: a possession's tags are also added, created and removed one at a time from its page; a tag created there takes the colour after the account's latest tag (research R3)

## Phase 2: Foundational

- [X] T002 Create `app/Actions/AttachPossessionTag.php` (read `UpdatePossessionFeatured.php` first): constructor `User $user`, `Possession $possession`, `PossessionTag $possessionTag`; class docblock of one sentence; `ModelNotFoundException` when either record's `account_id` differs from the user's; when the possession already carries the tag return without logging; else `$possession->tags()->attach()`, `unsetRelation('tags')`, dispatch `LogUserAction` `PossessionUpdate` with `['name' => $possession->name]`, loggable the possession, on `low`; returns the possession
- [X] T003 Test in `tests/Unit/Actions/AttachPossessionTagTest.php`: link written (`assertDatabaseHas('possession_possession_tag', …)`) and journal pushed (`Queue::assertPushedOn('low', LogUserAction::class)`); already carried leaves one row and pushes nothing; a possession of another account and a tag of another account each throw `ModelNotFoundException`

## Phase 3: User Story 1, add an existing tag (P1) MVP

Independent test: tick "Fragile" and "Heirloom" on the Dundie awards; both chips stay after a reload.

- [X] T004 [US1] In `app/ViewModels/Vaults/PossessionViewModel.php`: `header()['tags']` becomes `list<array{id: string, name: string, colour: string, tint: string, removeUrl: string}>` (colour from the tag, `VaultColourEnum::tintFor()`); add `tagOptions(): list<array{id: string, name: string, colour: string, carried: bool, others: string, url: string}>` over `$account->possessionTags()->withCount('possessions')->orderBy('name')`, `others` being `__('Unused')` at zero else `trans_choice(':count other|:count others', $n)` where `$n` excludes this possession (research R7), `url` the `possessions.tags.update` route; `tagCount(): string` ("No tags on this possession yet" at zero, else `trans_choice(':count tag on this possession|:count tags on this possession', …)`); `manageTagsUrl()` to `settings.possessionTags.index`; update the `@return` shapes
- [X] T005 [US1] Test in `tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php`: chips sorted with their colour; options sorted by name, carried flag right, "Unused", "1 other" and "2 others" counted without this possession and across vaults; `tagCount()` at zero, one and two
- [X] T006 [US1] Add to `routes/web.php` after `possessions.featured.update`: `PUT vaults/{vault}/possessions/{possession}/tags/{possessionTag}` `[PossessionTagAttachmentController::class, 'update']` `whereUuid(['possession', 'possessionTag'])` named `possessions.tags.update`; import as `App\Http\Controllers\App\Vaults\PossessionTagAttachmentController` aliased if it clashes with the settings controller import
- [X] T007 [US1] Create `app/Http/Controllers/App/Vaults/PossessionTagAttachmentController.php` `update(Request, Vault, Possession, PossessionTag)`: `abort_unless($possession->vault_id === $vault->id, 404)`, call `AttachPossessionTag`, `back()->with('status', __('Added “:name”.', ['name' => $possessionTag->name]))`
- [X] T008 [US1] Create `resources/js/possession-tag-picker.js` and register it as `possessionTagPicker` in `resources/js/app.js` (research R5): state `open`, `query`, `active`; `toggle()`, `close(refocus)`, outside click and Tab close, Escape closes and focuses `$refs.add`; `visible()` reads `[data-tag-option]` rows whose `data-name` contains the query ignoring case and hides the others; ArrowUp and ArrowDown move `active` over visible rows plus the create row, setting `aria-activedescendant` on the input; Enter clicks the active row; `@ajax:error` reloads the page
- [X] T009 [US1] Create `resources/views/app/vaults/possessions/_header-tags.blade.php` (first line comment and `@var` block) and include it from `_header.blade.php` in place of the current tag list: wrapper `x-data="possessionTagPicker()"`; `<ul id="possession-tag-chips" aria-label="{{ __('Tags on this possession') }}">` of chips (colour dot `aria-hidden`, name); "Add a tag" button (`x-ref="add"`, `aria-haspopup="listbox"`, `:aria-expanded`, label "Add a tag to sort this possession" when there is none) opening a `role="dialog"` panel with label "Find or create a tag", `role="combobox"` input `aria-controls="possession-tag-options"`, `<ul id="possession-tag-options" role="listbox" aria-multiselectable="true">` of `role="option"` rows with `aria-selected`, `data-tag-option`, `data-name`, each a `PUT` through `$ajax` to its `url` with `x-target="possession-tag-chips possession-tag-options possession-tag-count"`; foot `<span id="possession-tag-count">` and the "Manage tags" link; panel stays inside the viewport on narrow screens
- [X] T010 [US1] Add to `tests/Feature/Controllers/App/Vaults/PossessionTagAttachmentControllerTest.php`: PUT attaches and redirects back with "Added “Fragile”."; a possession of another vault of the same account answers 404; the possession page renders "Add a tag" and the chip name

## Phase 4: User Story 2, create a tag from the page (P1)

Independent test: type "Swiss made", choose the create row; the chip appears and the tag is in Possession tags.

- [X] T011 [P] [US2] Add `nextPossessionTagColour(): VaultColourEnum` to `app/Models/Account.php` (docblock in full): Blue when the account has no tag, else the case after the colour of its tag with the highest `id`, Slate wrapping to Blue; test in `tests/Unit/Models/AccountTest.php` for none, after Green is Amber, after Slate is Blue
- [X] T012 [US2] Add `nextTagColour(): array{colour: string, name: string}` to `PossessionViewModel` (name from the enum's translated label) and `createTagUrl()`; extend `PossessionViewModelTest`
- [X] T013 [US2] Add `POST vaults/{vault}/possessions/{possession}/tags` `possessions.tags.create` to `routes/web.php` and `create(Request, Vault, Possession)` to `PossessionTagAttachmentController`: `abort_unless` as T007, validate `name` `['required', 'string', 'max:50']`, `CreatePossessionTag(user, name, colour: $request->user()->account->nextPossessionTagColour())`, then `AttachPossessionTag`, `back()` with "Created “:name” and added it."
- [X] T014 [US2] In `_header-tags.blade.php` add the create row (`id="possession-tag-create"`, `x-show` when the trimmed query equals no `data-name` ignoring case): "Create “…” and add it" with the colour dot and "Gets the colour :colour. You can change it in Possession tags."; it posts `name` through `$ajax` with the same `x-target`, then empties the query; "No tag matches “…”." when nothing is visible and nothing can be created; `<x-error :messages="$errors->get('name')" />` placed inside `possession-tag-options` so the Ajax swap carries it, and the input `aria-describedby` it
- [X] T015 [US2] Extend `PossessionTagAttachmentControllerTest`: POST creates the tag with the next colour, attaches it, flashes; an empty name and a 51 character name return an error on `name`; a name the account already uses ignoring case returns an error

## Phase 5: User Story 3, take a tag off after confirming (P1)

Independent test: press × on "To sell", confirm; the chip is gone and "To sell" is still in Possession tags.

- [X] T016 [P] [US3] Create `app/Actions/DetachPossessionTag.php` mirroring T002 (no change, no log); test in `tests/Unit/Actions/DetachPossessionTagTest.php`: link removed and journal pushed, tag still exists (`assertModelExists`), not carried pushes nothing, other account refused
- [X] T017 [US3] Add `DELETE vaults/{vault}/possessions/{possession}/tags/{possessionTag}` `possessions.tags.destroy` and `destroy()` to `PossessionTagAttachmentController` (same shape as `update`, flash "Removed “:name” from this possession.")
- [X] T018 [US3] In `_header-tags.blade.php` give each chip an `x-modal.warning` (method `delete`, action `removeUrl`, `x-target` as T009) with title "Remove “:name” from this possession?", body "The tag stays in your account for your other possessions.", confirm "Remove"; its trigger is the × button with `id="remove-tag-{id}"` and `aria-label` "Remove “:name” from this possession"; a carried option in the panel, when chosen, closes the panel and clicks that button (research R6)
- [X] T019 [US3] Extend `PossessionTagAttachmentControllerTest`: DELETE detaches and flashes; the page renders the confirmation copy for a carried tag

## Phase 6: Polish

- [X] T020 [P] Update `docs/portal/en/9-templates-and-customisation/16-possessions.md` (tags managed from the page, removal keeps the tag) with the `writer` skill, and add an entry to `docs/FEATURES.md` with the `feature-documentator` skill
- [X] T021 Run `php artisan monica:localize`, translate every missing string in every locale following `.claude/rules/translations.md` and the glossary, then `php artisan monica:localize --check`
- [ ] T022 Run `bun run build`, then quickstart's by hand steps on the Overview and the Transactions section, keyboard only and with a screen reader
- [X] T023 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, and the whole suite with `php artisan test --compact`

## Dependencies

T002 → T003 → US1 (T004 → T006 → T007 → T008 → T009 → T010) → US2 (T011 ∥ T012 → T013 → T014 → T015) → US3 (T016 → T017 → T018 → T019) → T020 ∥ T021 → T022 → T023. T001 any time. US2 and US3 each need US1's partial; they do not need each other. Parallel: T001, T011 and T016 alongside US1.
