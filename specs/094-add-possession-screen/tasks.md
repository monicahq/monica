# Tasks: Add a Possession Screen

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/screen-api-and-mcp.md](contracts/screen-api-and-mcp.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office.

## Phase 1: Setup

- [X] T001 Amend principle VII in `.specify/memory/constitution.md`: the Possession sentence gains "records how it was acquired, from whom, when and for what declared value, and carries any of the account's tags"; Sync Impact Report; version 2.11.0, amended 2026-10-26 (research R2)
- [X] T002 [P] Update `spec/models/062-possession-in-progress.md` with the five acquisition attributes and the tags, and `spec/models/064-possession-tag-in-progress.md` with "carried by possessions; deleting a tag takes it off them", from data-model.md

## Phase 2: Foundational

- [X] T003 Create `app/Enums/PossessionAcquisitionEnum.php` implementing `Translatable`: `Bought = 'bought'`, `Gift = 'gift'`, `Inherited = 'inherited'`, `Made = 'made'`; `label()` "Bought", "A gift", "Inherited", "Made it"; `dateLabel()` "Bought on", "Received on", "Inherited on", "Made on"; `takesPerson()` true for Gift and Inherited; `personLabel()` "Given by", "Inherited from", null otherwise; `translationKeys()` every wording plus "Acquired on"; test in `tests/Unit/Enums/PossessionAcquisitionEnumTest.php`
- [X] T004 Edit `database/migrations/2026_10_26_000002_create_possessions_table.php`, after `is_featured`, each with `->comment()`: `acquisition` string nullable "bought, gift, inherited or made"; `foreignIdFor(Contact::class, 'acquired_from_contact_id')->nullable()->comment(...)->constrained('contacts')->nullOnDelete()` "the person it came from, for a gift or an inheritance"; `acquired_on` date nullable "not after the day it was recorded"; `declared_value` decimal(12, 2) nullable "0 to 9999999999.99"; `declared_value_currency` string(3) nullable "a CurrencyEnum value, set only with a declared value"
- [X] T005 [P] Create `database/migrations/2026_10_26_000006_create_possession_possession_tag_table.php`, `up()` only: `id`; `foreignIdFor(Possession::class)->constrained()->cascadeOnDelete()`; `foreignIdFor(PossessionTag::class)->constrained()->cascadeOnDelete()`; unique `[possession_id, possession_tag_id]`
- [X] T006 Edit `app/Models/Possession.php`: the five columns in `#[Fillable]` and the docblock; casts `acquisition` => `PossessionAcquisitionEnum::class`, `acquired_on` => `immutable_date`, `declared_value` => `decimal:2`; `acquiredFrom(): BelongsTo<Contact, $this>` on `acquired_from_contact_id`; `tags(): BelongsToMany<PossessionTag, $this>` ordered by name. Edit `app/Models/PossessionTag.php`: `possessions(): BelongsToMany<Possession, $this>`, docblock no longer says it cannot be carried
- [X] T007 Add to `tests/Unit/Models/PossessionTest.php`: `acquiredFrom` is null once the contact is soft deleted and returns after `restore()`; `tags` ordered by name
- [X] T008 [P] Add states `bought()` and `giftFrom(Contact $contact)` to `database/factories/PossessionFactory.php`
- [X] T009 Run `php artisan migrate:fresh` and the existing possession suite (`--filter Possession`) to prove nothing broke

## Phase 3: User Story 1, add with a name and a type (P1) MVP

Independent test: spec US1. Save "World’s Best Boss mug" with Kitchenware; it is in the vault, owned, one, not featured.

- [X] T010 [US1] Create `app/ViewModels/Vaults/PossessionFormViewModel.php`: `vault()`, `name()` and `description()` from `old()`, `chosenType()` from `old('type')`, `types()` as `list<{uuid, name, colour, colourName, fieldCount, fieldsUrl}>` ordered by name, `typeCount()` line `trans_choice(':count type in your account|:count types in your account', n)`, `typesSettingsUrl()`, `cancelUrl()`, `createAction()`
- [X] T011 [US1] Test it in `tests/Unit/ViewModels/Vaults/PossessionFormViewModelTest.php`: types by name with field counts, another account's left out, old input read back
- [X] T012 [US1] Add `new` and `create` to `app/Http/Controllers/App/Vaults/PossessionController.php`: inline rules from the contract (`name` `['required', 'string', 'max:255']`, `description` `['nullable', 'string', 'max:2000']`, `type` `['required', 'uuid']`, `is_featured` `['nullable', 'boolean']`); look the type up in the account, else `back()->withInput()->withErrors(['type' => __('Choose a type.')])`; call `CreatePossession` with named arguments; redirect to `possessions.index` with `status` `__(':name is written down.', ['name' => ...])`; action `ValidationException` returns `back()->withInput()->withErrors()`
- [X] T013 [US1] Add `possessions.new` (GET `vaults/{vault}/possessions/new`) and `possessions.create` (POST `vaults/{vault}/possessions`) to `routes/web.php` after `possessions.index`, one per line
- [X] T014 [US1] Create `resources/js/possession-type-picker.js` (research R7): trigger `aria-haspopup="listbox"` `aria-expanded`; options `role="option"` `aria-selected`; arrows, Home, End, Enter, Escape and outside click; focus back on the trigger; writes the hidden `type`; register `Alpine.data('possessionTypePicker', ...)` in `resources/js/app.js`
- [X] T015 [US1] Create `resources/views/app/vaults/possessions/form.blade.php`: `x-shell` with `x-breadcrumb.vault` (Possessions linked, "New possession"), `x-vault.sidebar current="possessions"`; `x-page-header` "Add a possession" with "Something you own: what it is, where it is kept and what it is worth. Only the name and the type are needed now."; `<x-form id="possession-form">` with `x-input` name (`requiredMark`, placeholder "Peugeot 308 SW, grandmother’s watch, the good camera") and `x-textarea` description ("Anything worth remembering about it"); `@include('app.vaults.possessions._type-picker', [...])`; footer slot with the summary line, `x-button.secondary` Cancel and `x-button form="possession-form"` "Add possession". Read each component first (`.claude/rules/components.md`)
- [X] T016 [US1] Create `resources/views/app/vaults/possessions/_type-picker.blade.php`: label "Type", trigger showing the chosen colour dot, name and field count or "Choose a type, out of :count", the listbox of every type (colour dot `aria-hidden`, colour name `sr-only`), the panel foot with the count line and "Manage types"; the error under it
- [X] T017 [US1] Add an `actions` slot to `x-page-header` in `resources/views/app/vaults/possessions/index.blade.php` with `x-button :href="route('possessions.new', $vault)"` "Add a possession"
- [X] T018 [US1] Write `tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php`: `new` answers with "Add a possession", `x-data="possessionTypePicker("`, `aria-haspopup="listbox"`, `role="listbox"`; `create` redirects with its flash and records the possession; missing name and missing type refused with input kept; a type of another account refused on `type`; the index links to `possessions.new`

## Phase 4: User Story 2, pick a type and fill its fields (P1)

Independent test: spec US2. Search "veh", pick Vehicle, fill two fields, save; the values are recorded.

- [X] T019 [US2] Extend `app/Actions/CreatePossession.php` with `array $values = []` keyed by field uuid (research R5): before writing, load the type's fields by uuid, refuse an unknown uuid on `values.{uuid}` "This field is not a field of the type of this possession.", skip `PossessionFieldValueFormat::isEmpty()`, refuse `PossessionFieldValueFormat::refusal()`; then one `DB::transaction` creating the possession and each `PossessionFieldValue` with `normalise()`; one `PossessionCreation` log
- [X] T020 [US2] Add to `tests/Unit/Actions/CreatePossessionTest.php`: values written; empty values skipped; a misfit refused with no possession saved; a field of another type refused
- [X] T021 [US2] Create `app/ViewModels/Vaults/PossessionTypeFieldsBlock.php`: for one type, `groups()` as `list<{name, fields}>` and `standaloneFields()`, each field `{uuid, name, kind, options, inputName 'values[uuid]', old}` in the type's order; test in `tests/Unit/ViewModels/Vaults/PossessionTypeFieldsBlockTest.php`
- [X] T022 [US2] Give `PossessionFormViewModel` `recentTypes()`: distinct `possession_type_id` of this vault's possessions by `created_at` desc, at most four, as the `types()` shape; `fieldsBlock()` for `old('type')` or null; add to its test: distinct, four, newest first, other vaults left out
- [X] T023 [US2] Create `app/Http/Controllers/App/Vaults/PossessionTypeFieldController.php` `index(Request, Vault, PossessionType)` (`abort_unless` same account, 404) rendering `_type-fields`; add `possessions.typeFields.index` (GET `vaults/{vault}/possessions/types/{possessionType}/fields`, `whereUuid('possessionType')`) to `routes/web.php`; test in `tests/Feature/Controllers/App/Vaults/PossessionTypeFieldControllerTest.php` (answers with the field names and `values[` inputs, another account's type 404)
- [X] T024 [US2] Create `resources/views/app/vaults/possessions/_type-fields.blade.php` (root `<div id="possession-type-fields" x-merge="replace">`): "Fields from the :type type" with "All optional. Every :type in your account has them.", group headings, `@include('app.vaults.possessions._field')` per field; without a type "Choose a type above and its fields appear here."
- [X] T025 [US2] Create `resources/views/app/vaults/possessions/_field.blade.php` switching on `PossessionFieldKindEnum`: Text `x-input`, Number `x-input type="number" step="any"`, Date `x-input type="date"`, Boolean `x-checkbox`, Select `x-select` with the options and an empty first one, Rating a `fieldset` with a `legend` and radios 1 to `PossessionFieldKindEnum::MAX_RATING`; error from `values.{uuid}`
- [X] T026 [US2] Extend `possession-type-picker.js` and `_type-picker`: search input `role="combobox"` `aria-controls` `aria-activedescendant` with "Search :count types" and "Esc to close"; groups "Recently used" and "All types", one "Matching types" group while searching; no match "No type called “:query”." and "You can create it in the possession types settings, then come back."; quick choice buttons `aria-pressed` under the picker; choosing calls `$ajax(fieldsUrl, { target: 'possession-type-fields' })`; pass `values` from the controller to `CreatePossession`; add the controller test for a refused field value reaching `values.{uuid}`

## Phase 5: User Story 3, say where it is kept (P2)

Independent test: spec US3. Choose "Home › Living room"; Home's address shows; the location is saved.

- [X] T027 [US3] Give `PossessionFormViewModel` `locations()` from `LocationTree::forAccount()->ordered()`: `list<{uuid, path, address, addressSource}>` with `path()` and `addressHolder()`, `addressSource` "The address of :place." or "Taken from :place, which :location is part of."; `newLocationUrl()` to `settings.locations.new`; add tests (paths, nearest address, none without an address)
- [X] T028 [US3] Add the section "Where it is kept" ("A spot inside a place takes the address of that place.") to `form.blade.php`: `x-select name="location"` with "Not recorded" first, the address card driven by an inline `x-data` over `locations()`, "Missing a place? Add a location"; controller rule `location` `['nullable', 'uuid']` looked up in the account, else an error on `location`; add the controller test

## Phase 6: User Story 4, record how it was acquired (P2)

Independent test: spec US4. A gift from a contact of the vault, received on a date, valued; all read back.

- [X] T029 [US4] Extend `CreatePossession` and `UpdatePossession` with `?PossessionAcquisitionEnum $acquisition`, `?Contact $acquiredFrom`, `?CarbonImmutable $acquiredOn`, `?string $declaredValue`, `?CurrencyEnum $declaredValueCurrency`: drop the contact unless `takesPerson()`; refuse a contact of another vault or trashed on `acquired_from` "This person is not in this vault."; refuse a date after today on `acquired_on` "This date is in the future."; refuse a value below 0 or above 9999999999.99 on `declared_value` "Write a value of zero or more."; refuse a value without currency on `declared_value_currency` "Choose the currency of this value."; drop the currency without a value
- [X] T030 [US4] Add each rule above to `tests/Unit/Actions/CreatePossessionTest.php` and `UpdatePossessionTest.php`
- [X] T031 [US4] Create `app/ViewModels/Vaults/PossessionContactCandidatesViewModel.php` in the shape of `FieldChoicesViewModel` (contacts of the vault matching the query, at most ten, `{uuid, name}`), `app/Http/Controllers/App/Vaults/PossessionContactCandidateController.php` `index` rendering `x-record-picker.candidates` in `resources/views/app/vaults/possessions/contact-candidates.blade.php`, route `possessions.contactCandidates.index` (GET `vaults/{vault}/possessions/contact-candidates`); tests for both (this vault only, trashed left out)
- [X] T032 [US4] Give `PossessionFormViewModel` `acquisitions()`, `currencies()` from `CurrencyEnum`, `defaultCurrency()` from `AvailableLocales::details($locale)['currency']`, `chosenGiver()` from `old('acquired_from')`; tests for the default per locale
- [X] T033 [US4] Create `resources/views/app/vaults/possessions/_acquisition.blade.php`: "How you got it" with "Useful for insurance, and for remembering who it came from."; a `role="radiogroup"` of `x-option-row :chip="true" type="radio"` ("Not recorded" plus the four); `x-record-picker` labelled by `personLabel()` shown only for a person; date `x-input type="date"` labelled by `dateLabel()` or "Acquired on"; value `x-input type="number" min="0" step="0.01"` "Declared value" with help "What it would cost to replace today." beside `x-select name="declared_value_currency"`; controller rules from the contract, contact looked up in the vault; controller test for one refusal

## Phase 7: User Story 5, put tags on it (P2)

Independent test: spec US5. "insured" reused, "office party" created; the possession carries both.

- [X] T034 [US5] Create `app/Helpers/PossessionTagNames.php` `sync(User $user, Possession $possession, array $names): void` (research R6): `TextSanitizer::plainText()`, drop empties and duplicates by `mb_strtolower()`, reuse account tags by `mb_strtolower()` name, create the rest with `CreatePossessionTag` (Blue), `$possession->tags()->sync()`; test in `tests/Unit/Helpers/PossessionTagNamesTest.php`
- [X] T035 [US5] Give `CreatePossession` and `UpdatePossession` `?array $tagNames = null`: refuse a name over 50 characters on `tags` "A tag has at most 50 characters." before the transaction; inside it call `PossessionTagNames::sync()`; `null` on update leaves tags; add tests to both action tests
- [X] T036 [US5] Create `resources/js/tag-input.js` (research R8): chips with a button labelled "Remove the tag :name", hidden `tags[]`, suggestions from the account's tags matching the draft, Enter and comma add, Backspace in an empty draft removes the last; register `Alpine.data('tagInput', ...)` in `resources/js/app.js`
- [X] T037 [US5] Create `resources/views/app/vaults/possessions/_tags.blade.php`: "Tags" with "Free words to find things across types, like “insured” or “for the kids”."; input labelled "Add a tag" with placeholder "Add a tag, then press Enter"; `PossessionFormViewModel::tags()` and old `tags`; controller rules `tags` `['nullable', 'array']`, `tags.*` `['string']`; controller test that tags are saved
- [X] T038 [US5] Give `PossessionTagsViewModel::tags()` a `possessionCount` (`withCount('possessions')`) and a `deleteConsequence` `trans_choice('{1}One possession carries it and loses it.|:count possessions carry it and lose it.', n)` guarded at zero; print it in the confirmation of `resources/views/app/settings/account/possession-tags/_row.blade.php`; update `DestroyPossessionTag` docblock; add to `DestroyPossessionTagTest` that the possession survives without the tag, and to the view model test the count line

## Phase 8: User Story 6, feature it (P3)

- [X] T039 [US6] Give `PossessionFormViewModel` `featuredLine()`: "Featured possessions sit at the top of All possessions." plus `trans_choice('[0,*]You currently feature :count.', n)` only above zero; add the checkbox row "Feature this possession" (`x-checkbox name="is_featured"`) to `form.blade.php`; tests for the line at 0 and 3 and the controller saving `is_featured`

## Phase 9: User Story 7, see where photos will go (P3)

- [X] T040 [US7] Create `resources/views/app/vaults/possessions/_photos.blade.php`: "Photos" with "optional", a static box (no input, no button) with the `memory` icon and "Photos come in a later version."; include it first in the form; assert the sentence in the controller test

## Phase 10: API and assistant (FR-015)

- [X] T041 Extend `app/Http/Controllers/Api/Possessions/PossessionController.php` `rules()`: `acquisition` `['nullable', Rule::enum(PossessionAcquisitionEnum::class)]`, `acquired_from_id` `['bail', 'nullable', 'uuid']` looked up in the vault, `acquired_on` `['nullable', 'date']`, `declared_value` `['nullable', 'numeric']`, `declared_value_currency` `['nullable', 'required_with:declared_value', Rule::enum(CurrencyEnum::class)]`, `tags` `['nullable', 'array']`, `tags.*` `['string', 'max:50']`; pass them to both actions (update passes `tagNames: $request->input('tags', [])`, a full replace)
- [X] T042 Extend `app/Http/Resources/PossessionResource.php` with `acquisition` `{way, acquired_from {id, name}|null, acquired_on, declared_value {amount, currency}|null}` and `tags` `[{id, name, colour}]`, loading `acquiredFrom` and `tags`; tests in `tests/Feature/Controllers/Api/Possessions/PossessionControllerTest.php` (shape, create with all, update clears tags left out, contact of another vault 422)
- [X] T043 [P] Extend `app/Mcp/Tools/CreatePossession.php` and `UpdatePossession.php` with the six inputs, update keeping what is left out through `array_key_exists`; add to their tests and to `GetPossessionTest`
- [X] T044 [P] Document the new request and resource fields in `resources/docs/api/27-possessions.php`; run `ApiDocsReturnFieldsTest`

## Phase 11: Polish

- [X] T045 [P] Write `docs/portal/en/9-templates-and-customisation/16-possessions.md` with the writer skill (adding a possession, its type fields, location, acquisition, tags, featuring); add an entry to `docs/FEATURES.md`
- [X] T046 Run `php artisan monica:localize`, translate every reported string in every locale following `.claude/rules/translations.md`, then `php artisan monica:localize --check`
- [X] T047 Run `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `bun run build` and the whole suite
- [ ] T048 Look at the form with the take-screenshot skill in light and dark, at phone width and 200% zoom; walk quickstart "By hand" with the keyboard alone

## Dependencies

- Phase 1 and 2 before everything. US1 before US2 to US7 (they add sections to its form and parameters to its action).
- US3, US4, US5, US6, US7 are independent of each other once US1 is done; US4 and US5 both edit the two actions, so run them one after the other.
- Phase 10 after US4 and US5. Phase 11 last.
- Parallel: T002 with T003; T005 with T004; T008 with T006; T043 with T044; T045 with T046.

## Implementation strategy

MVP is Phases 1 to 3: a possession added from the screen with a name and a type. Then US2 (fields), then the P2 stories, the API, and polish.
