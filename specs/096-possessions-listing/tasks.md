# Tasks: Possessions Listing

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/screen-and-api.md](contracts/screen-and-api.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office.

## Phase 1: Foundational

- [X] T001 Create `app/Helpers/PossessionValueFormatter.php` (research R3, R4): constructor `User $user`; `format(?string $amount, ?string $currency): string` returns `—` when either is null, else an intl `NumberFormatter` for `app()->getLocale()` in `CURRENCY` style, 0 fraction digits, grouping and decimal symbols set from `$user->number_format`, falling back as `FieldValueFormatter::numberFormat()` does (NoSeparator sets an empty grouping symbol), formatting with `formatCurrency($amount, strtoupper($currency))`; `total(iterable<Possession>): string` sums `declared_value` per currency (skipping a null value or currency), orders by amount descending, joins with `__(', ')`, `—` when nothing was summed
- [X] T002 Test it in `tests/Unit/Helpers/PossessionValueFormatterTest.php`: `en` with CommaAndDot gives `€18,400`; `fr_FR` with SpaceAndComma puts the symbol after and a non breaking space between thousands; a null amount or currency gives `—`; two EUR and one USD give two amounts; nothing valued gives `—`

## Phase 2: User Story 1, see every possession of the vault (P1) MVP

Independent test: spec US1. A vault with featured and other possessions shows the summary and both lists, nothing from another vault.

- [X] T003 [US1] Extend `app/ViewModels/Vaults/PossessionsViewModel.php` (keep every 095 method): constructor gains `private readonly ?Location $location = null`; private `possessions()` (`once`) loads the vault's possessions with `possessionType` and `location`; private `tree()` (`once`) is `LocationTree::forAccount($this->user->account_id)`; private `selected()` is every possession when no location, else those whose `current_location_id` is in `[$location->id, ...$tree->descendantIds($location)]`; update the class docblock to say it also lists the vault's possessions by location
- [X] T004 [US1] Add `summary()`: `trans_choice(':count possession across :locations, :value declared.|:count possessions across :locations, :value declared.', $count, ['locations' => trans_choice(':count location|:count locations', $locationCount), 'value' => $formatter->total(...)])`, or `trans_choice(':count possession, :value declared.|:count possessions, :value declared.', ...)` when the account has no location
- [X] T005 [US1] Add `featured()` (selected and `is_featured`, by `position`) and `others()` (selected and not featured, by name with `LocaleCollation::sortByCollator`), each a `list<array{name, type, icon, colour, tint, place, value, description, isLent}>`: `icon` `$type->icon ?? Section::DEFAULT_ICON`, `tint` `VaultColourEnum::tintFor($type->colour)`, `place` the location's `LocationTree::path()`, or `__('No location')`, `value` from `PossessionValueFormatter::format()`, `isLent` `status === PossessionStatusEnum::Lent`; add `othersTitle()` returning `__('Everything else')` with no location
- [X] T006 [US1] Add to `tests/Unit/ViewModels/Vaults/PossessionsViewModelTest.php`: summary at one and many possessions, with and without locations; featured in position order; others by name; a possession without location reads "No location"; Lent flagged, Sold not; no value gives `—`; another vault's possession neither listed nor counted
- [X] T007 [US1] Replace the `@else` placeholder in `resources/views/app/vaults/possessions/index.blade.php` (update its first line comment): the page header description becomes `summary()`; "Featured here" (`h2`, absent when `featured()` is empty) as a grid of cards: tinted 32px tile with `x-icon` in the type colour `aria-hidden`, name, place · value, description when present, `star-filled` icon labelled "Featured"; then `h2` `othersTitle()` and an `x-panel` of rows: tinted tile, name, "type · place", `x-badge tone="neutral"` "Lent" when `isLent`, value right aligned; rows are not links (spec Assumptions). Read `x-panel`, `x-badge`, `x-icon`, `x-page-header` first
- [X] T008 [US1] In `tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php` replace the "Possessions are coming" assertion: a vault with possessions shows "Featured here", a featured name, "Everything else", an other name and "Add a possession", and not "Possessions are coming"

## Phase 3: User Story 2, browse by location (P2)

Independent test: spec US2. Selecting a location narrows counts, bar and lists to it and what it contains.

- [X] T009 [US2] Add `Route::get('vaults/{vault}/possessions/locations/{location}', [PossessionLocationController::class, 'show'])->whereUuid('location')->name('possessions.locations.show');` after `possessions.index` in `routes/web.php` (research R1)
- [X] T010 [US2] Create `app/Http/Controllers/App/Vaults/PossessionLocationController.php` with `show(Request $request, Vault $vault, Location $location): View` returning `app.vaults.possessions.index` with `new PossessionsViewModel(user:, vault:, location:)`; if the vault holds no possession, the view shows the empty state as for the index
- [X] T011 [US2] Create `app/ViewModels/Vaults/PossessionLocationTreeBlock.php` (constructor: `Vault $vault`, `LocationTree $tree`, `Collection $possessions`, `?Location $current`): `nodes()` returns `list<array{name, url, depth, icon, colour, count, isCurrent}>`, first "All locations" (`layers` icon, `possessions.index`, every possession), then `$tree->ordered()` with depth from the parent chain, `place` icon at the top level and `chevron-right` below, colour `VaultColourEnum::cases()[$index % count]` by top level order inherited by descendants (research R5), count of possessions at the node or inside it, `url` `possessions.locations.show`; `manageUrl()` to `settings.locations.index`. Expose it as `tree()` on the view model
- [X] T012 [US2] Add `selection()` to the view model: `name` (location name or `__('All locations')`), `address` (top level with an address: it; nested: `__(':path, at the address of :holder', ...)` using `addressHolder()`; no holder: the path; all: `trans_choice(':count address|:count addresses', $n)` counting locations with an address), `count`, `value` (`total()`); `othersTitle()` becomes `__('Kept at :name', ...)` with a location
- [X] T013 [US2] Add `parts()` and `partsLabel()` (research R6): with a location, one part per direct child holding a possession plus `__('No specific spot')` for those at the location itself; with none, one per top level location plus `__('No location')`; each `{name, count, colour}`, the catch-all part in `muted`; `[]` below two parts; `partsLabel()` joins "name count" with `__(', ')`
- [X] T014 [US2] Tests: `tests/Unit/ViewModels/Vaults/PossessionLocationTreeBlockTest.php` (order and depth, counts include nested locations, zero for an unused location, colour inherited, current node, another account's locations absent); add to `PossessionsViewModelTest.php` the narrowing by location, inherited address, address count, parts with "No specific spot" and "No location", no parts below two, "Kept at" title
- [X] T015 [US2] Add the two column layout to `index.blade.php`: `<nav aria-label="{{ __('Locations') }}">` with `x-caption` "Locations", "Manage" link, one link per node (`aria-current="page"` when current, left padding from depth, coloured icon `aria-hidden`, name, count); the selection panel (`h2` name, address, `dl` "Possessions" and "Declared value"); when `parts()` is not empty, a bar `role="img" aria-label="{{ $viewModel->partsLabel() }}"` of flex segments and a legend in text; when `others()` is empty, "Nothing else is kept here. Featured possessions are listed above."; stacked at phone width
- [X] T016 [US2] Create `tests/Feature/Controllers/App/Vaults/PossessionLocationControllerTest.php`: a location renders its name, "Kept at" and `aria-current="page"`; a location reached through the wrong vault of the same account still renders (locations are account wide, the possessions are this vault's); run `RouteProtectionTest`

## Phase 4: User Story 3, around the listing (P3)

Independent test: spec US3. "Manage" opens the locations settings; the sidebar count matches the vault.

- [X] T017 [US3] In `app/View/Components/Vault/Sidebar.php` pass `possessionCount` (`Possession::query()->where('vault_id', $this->vault->id)->count()`, only when `hasPossessionTypes`, else 0; research R7); in `resources/views/components/vault/sidebar.blade.php` add `@var int $possessionCount` and set the possessions entry `badge` to `$possessionCount > 0 ? (string) $possessionCount : null`
- [X] T018 [US3] Add to `tests/Feature/Views/Components/SidebarTest.php`: the count shows beside "All possessions" with two possessions, not with none, and another vault's possessions are not counted; assert in `PossessionControllerTest.php` the "Manage" link to `settings.locations.index`

## Phase 5: API and assistant parity (FR-015)

- [X] T019 [P] In `app/Http/Controllers/Api/Possessions/PossessionController.php::index` accept `'location' => ['nullable', 'uuid']`; resolve it among the account's locations or answer 422 on `location` "This location does not exist."; narrow with `whereIn('current_location_id', [$id, ...LocationTree::forAccount(...)->descendantIds($location)])`; tests in `tests/Feature/Controllers/Api/Possessions/PossessionControllerTest.php` (nested included, sibling excluded, another account's uuid 422)
- [X] T020 [P] Same argument in `app/Mcp/Tools/ListPossessions.php` (rule, filter, schema "Only possessions at this location or inside it, by the identifier list-locations gives.", tool description mentions location); tests in `tests/Unit/Mcp/Tools/ListPossessionsTest.php`
- [X] T021 [P] Document `location` in `resources/docs/api/27-possessions.php`; run `ApiDocsReturnFieldsTest`

## Phase 6: Polish

- [X] T022 [P] Update `docs/portal/en/9-templates-and-customisation/16-possessions.md` (the listing, browsing by location) with the writer skill
- [X] T023 Run `php artisan monica:localize`, translate every reported string in every locale following `.claude/rules/translations.md` and its glossary, then `php artisan monica:localize --check`
- [X] T024 Run `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `bun run build` and the whole suite
- [ ] T025 Look at the listing with the take-screenshot skill in light, dark, at phone width and in `fr_FR`; walk quickstart "By hand" with the keyboard alone
- [X] T026 Record the feature in `docs/FEATURES.md` with the feature-documentator skill

## Dependencies

- T001 before T004, T005, T012. US1 before US2 (it fills the same view model and view).
- US3 and Phase 5 depend only on Phase 1 and may run beside US1 and US2.
- Parallel: T019, T020, T021 with each other; T017 with T003 to T008; T022 with T023.

## Implementation strategy

MVP is Phases 1 and 2: the vault's possessions listed under "All locations". Then the tree and
selection (US2), the sidebar count (US3), API parity, and polish.
