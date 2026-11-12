# Tasks: Possession Detail

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office.

## Phase 1: Foundational

- [X] T001 [P] Add `public function formatPossessionValue(PossessionFieldValue $value): ?string` to `app/Helpers/FieldValueFormatter.php` (research R4): read with `PossessionFieldValueFormat::read($value->field->kind, $value->value)`; Text and Select return the stored text (null when blank); Number goes through the same `numberFormat()` path as `number()`, keeping its decimals; Date parses `Y-m-d` with `CarbonImmutable::createFromFormat` and uses `write($date, $this->dateFormat())`; Boolean `__('Yes')`/`__('No')`; Rating `trans_choice('[0,*]:count out of five', $rating)` after checking it never reads wrongly at one in English (else write `{1}One out of five|:count out of five` guarded against zero)
- [X] T002 [P] Test each kind in `tests/Unit/Helpers/FieldValueFormatterTest.php`: number `1250.5` with CommaAndDot gives `1,250.5`; a date in the user's format; `1` gives "Yes"; rating 4; a select option as stored; blank text gives null
- [X] T003 [P] Create `app/Actions/UpdatePossessionFeatured.php` (research R2), class docblock of one sentence: constructor `User $user`, `Possession $possession`, `bool $isFeatured`; `execute(): Possession` refuses another account with `ModelNotFoundException`; does nothing but return when the flag is unchanged; else updates `is_featured` and `position` (null when unfeatured) and calls `FeaturedPossessionOrder::arrange(vaultId:, moved: $possession)` when featured, `arrange(vaultId:)` when unfeatured, exactly as `UpdatePossession::updatePossession()` does; dispatches `LogUserAction` `PossessionUpdate` with `['name' => ...]` on `low`
- [X] T004 [P] Test it in `tests/Unit/Actions/UpdatePossessionFeaturedTest.php`: featuring places it last after two featured ones; unfeaturing clears its position and closes the gap; another account throws `ModelNotFoundException`; `LogUserAction` pushed on `low` (`Queue::fake()`)

## Phase 2: User Story 1, open a possession (P1) MVP

Independent test: spec US1. Selecting a possession in the listing opens its page with the Overview shown.

- [X] T005 [US1] Create `app/ViewModels/Vaults/PossessionViewModel.php` (read `.claude/skills/view-models/SKILL.md` first; class docblock says what the screen shows): constructor `User $user`, `Possession $possession`; loads once `possessionType.groups.fields`, `possessionType.standaloneFields`, `values.field`, `tags`, `acquiredFrom`, and `LocationTree::forAccount()` (research R6)
- [X] T006 [US1] Add `header(): array{name, isFeatured, type, icon, tint, colour, place: ?array{name, holder: ?string}, tags: list<string>}`: `icon` `$type->icon ?? Section::DEFAULT_ICON`, `tint` `VaultColourEnum::tintFor($type->colour)`; `place` null without location, else the location name and the name of its top level ancestor when nested (walk `parentOf()`), null holder at the top level
- [X] T007 [US1] Add `description(): ?string`, and `groups(): list<array{title: ?string, fields: list<array{label, value}>}>`: each group of the type in position order with its fields in position order, then the standalone fields as a group with a null title, every value through `formatPossessionValue()`, fields without a value left out, groups left empty dropped
- [X] T008 [US1] Add `value(): ?string` (`PossessionValueFormatter::format()`, null when `declared_value` is null) and `facts(): list<array{label, value}>` (research R7): Type; Kept in (location path or `__('No location')`); Status (`status->label()`); Acquired (`acquisition->label()`, then `acquired_on` in the user's date format, joined with `__(', ')`) only when set; From (`acquiredFrom->name`) only when the contact is loaded and not deleted; Quantity only above one. Add `addedLine()` `__('Added on :date', ...)` and `editedLine()` with `updated_at->diffForHumans()` (`__('Last edited :when', ...)`)
- [X] T009 [US1] Add `breadcrumb()` (`[['label' => __('All possessions'), 'url' => route('possessions.index', $vault)], ['label' => name]]`), `listingUrl()`, `featuredUrl()` (`possessions.featured.update`), `destroyUrl()` (`possessions.destroy`)
- [X] T010 [US1] Test in `tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php`: place nested gives holder "Dunder Mifflin", top level no holder, none null; groups in type order, empty field and empty group left out, standalone fields last; value null without declared value; facts with and without acquisition, quantity at one absent and at three present, deleted giver absent
- [X] T011 [US1] Add to `routes/web.php` after `possessions.contactCandidates.index`: `Route::get('vaults/{vault}/possessions/{possession}', [PossessionController::class, 'show'])->whereUuid('possession')->name('possessions.show');` (research R1)
- [X] T012 [US1] Add `show(Request $request, Vault $vault, Possession $possession): View` to `app/Http/Controllers/App/Vaults/PossessionController.php` returning `app.vaults.possessions.show` with `new PossessionViewModel(user:, possession:)`
- [X] T013 [US1] Create `resources/views/app/vaults/possessions/show.blade.php` (first line comment, then `@var` view model; read `x-shell`, `x-breadcrumb.vault`, `x-panel`, `x-chip`, `x-detail-list`, `x-icon`, `_photos.blade.php` first): `x-shell` titled with the name, breadcrumb from `breadcrumb()`, sidebar `current="possessions"`; header: tinted type pill with icon `aria-hidden`, "kept in" location "at" holder or "No location", `h1` name, `star-filled` labelled "Featured" when featured, tags as `x-chip icon="tag"`; two columns stacking at phone width: the sections nav (Phase 3 fills it) and the Overview: the photos placeholder sentence "Photos come in a later version.", "Description" when present, "Details" with "Fields from the :type type." and one `dl` per group with `x-caption` titles, or "Nothing is filled in yet." when no group; aside: value panel "Current estimated value" with the amount, or "No value recorded yet."; facts `dl`; added and edited lines
- [X] T014 [US1] In `resources/views/app/vaults/possessions/index.blade.php` make each featured card and each row an `<a href>` to `possessions.show` (add `url` to `PossessionsViewModel::row()`), with `focus-ring`; research R8
- [X] T015 [US1] Tests in `tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php`: show renders the name and "Current estimated value"; the index links to a possession's page

## Phase 3: User Story 2, the sections list (P2)

Independent test: spec US2. Ten sections, Overview current, nine announced unavailable.

- [X] T016 [US2] Add `sections(): list<array{label, icon, isAvailable}>` to the view model, in the design's order and icons: Overview `info`, Timeline `timeline`, Transactions `coin`, Valuations `activity`, Provenance `family`, Insurance `lock`, Maintenance `settings`, Loans `relationship`, Locations `place`, Documents `file`; only Overview available. Check each icon name exists in `x-icon`, else pick the closest and say so. Test the order and that only Overview is available
- [X] T017 [US2] In `show.blade.php` render `<nav aria-label="{{ __('Sections of this possession') }}">`: Overview an `<a href>` to the page with `aria-current="page"`; the nine others a `<span role="link" aria-disabled="true" aria-describedby="sections-later">` with no href; one `<p id="sections-later" class="sr-only">{{ __('Comes in a later version.') }}</p>` (research R5)

## Phase 4: User Story 3, act on the possession (P3)

Independent test: spec US3. The menu features, unfeatures and deletes; the main Edit part is unavailable.

- [X] T018 [US3] Add an `unavailable` prop (`?string`, the reason, default null) to `resources/views/components/button/split.blade.php` and its `@var` block: when set, the main part renders without `href`, `aria-disabled="true"` and `aria-describedby` an id holding the reason in `sr-only`; the chevron and menu unchanged. Test in `tests/Feature/Views/Components/ButtonSplitTest.php`: the main part carries `aria-disabled` and the reason, the trigger still has `aria-haspopup="menu"`; then run the whole suite (shared component)
- [X] T019 [US3] Add to `routes/web.php`: `Route::put('vaults/{vault}/possessions/{possession}/featured', [PossessionFeaturedController::class, 'update'])->whereUuid('possession')->name('possessions.featured.update');` and `Route::delete('vaults/{vault}/possessions/{possession}', [PossessionController::class, 'destroy'])->whereUuid('possession')->name('possessions.destroy');`
- [X] T020 [US3] Create `app/Http/Controllers/App/Vaults/PossessionFeaturedController.php` `update(Request, Vault, Possession): RedirectResponse`: validate `'is_featured' => ['required', 'boolean']`, run `UpdatePossessionFeatured`, redirect to `possessions.show` with `:name is featured.` or `:name is no longer featured.`
- [X] T021 [US3] Add `destroy(Request, Vault, Possession): RedirectResponse` to `PossessionController`: run `DestroyPossession`, redirect to `possessions.index` with `:name is deleted.`
- [X] T022 [US3] In `show.blade.php` add `x-button.split label="Edit" :unavailable="__('Editing comes in a later version.')"` with a menu (read `x-actions-menu.item` and `x-modal.warning`): a `PUT` form button "Add to featured" or "Remove from featured" (`star`) to `featuredUrl()`; a separator; "Delete this possession" in danger tone opening `x-modal.warning` (title `Delete :name?`, confirm "Delete", `action` `destroyUrl()`, body "This cannot be undone.")
- [X] T023 [US3] Create `tests/Feature/Controllers/App/Vaults/PossessionFeaturedControllerTest.php` (features and unfeatures with the flash, missing `is_featured` refused) and add to `PossessionControllerTest.php` destroy redirecting with the message and `assertModelMissing()`; run `RouteProtectionTest`

## Phase 5: Polish

- [X] T024 [P] Update `docs/portal/en/9-templates-and-customisation/16-possessions.md` (the possession page, featuring, deleting) with the writer skill
- [X] T025 Run `php artisan monica:localize`, translate every reported string in every locale following `.claude/rules/translations.md` and its glossary, then `php artisan monica:localize --check`
- [X] T026 Run `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `bun run build` and the whole suite
- [ ] T027 Look at the page with the take-screenshot skill in light, dark, at 375 px and in `fr_FR`; walk quickstart "By hand" with the keyboard alone (skipped on 2026-10-27: the author checks the page by hand)
- [X] T028 Record the feature in `docs/FEATURES.md` with the feature-documentator skill

## Dependencies

- T001 before T007. T003 before T020. T005 to T009 before T013. T013 before T017 and T022. T018 before T022.
- US2 and US3 extend the same view model and view as US1, so they follow it.
- Parallel: T001 to T004 with each other; T018 with Phase 2; T024 with T025.

## Implementation strategy

MVP is Phases 1 and 2: every possession opens its page with its Overview. Then the sections list
(US2), the Edit menu (US3), and polish.
