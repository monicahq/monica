---

description: "Task list for giving the address field its control"
---

# Tasks: Editable Address Field

**Input**: Design documents from `/specs/043-fix-address-edit/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/section-form.md](contracts/section-form.md), [contracts/vcard-mapping.md](contracts/vcard-mapping.md), [quickstart.md](quickstart.md)

**Tests**: not optional here. Constitution III makes a test a delivery condition, so every phase carries its own and the story is not done until they pass.

**Format**: `[ID] [P?] [Story] Description`. `[P]` means a different file with no incomplete dependency.

## Phase 1: Setup

- [X] T001 Cut the working branch `2026-09-22-fix-address-edit` from an up to date `main`, done on 2026-09-22 during `/speckit-specify` with `git branch` and `git symbolic-ref`, since `checkout -b` half fails in this checkout

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: the two enum answers that switch the type on, the supplied list it names, the shape the value is written in, and what counts as empty. Every story reads all four.

**CRITICAL**: no user story work begins until this phase is complete.

- [X] T002 Extend `tests/Unit/Enums/FieldTypeEnumTest.php`: `hasAnInput()` is now true for `Address` (the assertion at line 77 flips), `usesALabelList()` is true for `Address` and unchanged for every other case, `usesValueList()` stays false for it, `valueColumn()` still answers `value_json`, and a new `suppliedLabelList()` answers `EmailLabels`, `PhoneLabels` and `AddressLabels` for those three types and null for every other
- [X] T003 In `app/Enums/FieldTypeEnum.php` add `self::Address` to `hasAnInput()` and to `usesALabelList()`, and add `suppliedLabelList(): ?ValueListEnum` as a `match` over the three types with `default => null`
- [X] T004 [P] Extend `tests/Unit/Enums/DefaultFieldEnumTest.php`: `Address` names `ValueListEnum::AddressLabels`
- [X] T005 [P] Add `self::Address => ValueListEnum::AddressLabels` to `DefaultFieldEnum::valueList()` in `app/Enums/DefaultFieldEnum.php`, so `CreateDefaultTemplate` supplies the list without a change of its own
- [X] T006 [P] Extend `tests/Unit/Jobs/CreateDefaultTemplateTest.php`: the supplied Address field of a new account carries the `value_list_id` of that account's Address labels list
- [X] T007 Extend `tests/Unit/Actions/SetFieldValueTest.php`: an address given as an array is written with all seven keys `po_box`, `extended`, `street`, `city`, `region`, `postal_code`, `country` in that order, an empty string for a part not given, each part sanitised as plain text, the label written to `value_list_item_id` beside it, a label of another list refused, and `is_unique` on an address field refusing nothing, since `refuseADuplicate()` skips an array
- [X] T008 In `app/Actions/SetFieldValue.php` `readTheColumn()`, when the column is `value_json` and the type is `Address`, build the seven keys from the given array through `TextSanitizer::nullablePlainText()` and `mb_trim()`, writing `''` for a missing or null part, leaving `value_json => $this->value` for any other type; `theLabel()` and `emptyColumns()` need no change because they read `usesALabelList()`
- [X] T009 Extend `tests/Unit/Actions/SetFieldValuesTest.php`: seven empty boxes clear a stored address, seven empty boxes with a label picked leave the field unfilled and keep no label, a required address is refused with `FieldIsRequired` when every box is empty and satisfied when one box is filled, and a partly filled address stores the filled parts with `''` for the rest
- [X] T010 In `app/Actions/SetFieldValues.php` make `isBlank()` treat an array as blank when every value in it is blank after trimming, keeping `[]` blank and leaving an array of ids untouched, so `saveOne()`, `refuseAnEmptyRequiredField()` and `nothingWasFilledIn()` all follow

**Checkpoint**: an address can be written and read back by the actions. No screen offers it yet.

---

## Phase 3: User Story 1 (P1) 🎯 MVP

**Goal**: somebody fills in, changes and clears an address on a record, with its label, from the block that edits the record.

**Independent test**: open the Addresses block of a contact, fill in the street, the city and the postcode, pick a country and Home, save, and confirm the record reads the line with Home beside it; change the postcode alone and confirm nothing else moved; empty every box and confirm the record says not recorded.

- [X] T011 [P] [US1] Extend `tests/Unit/Helpers/FieldValueRulesTest.php`: an address field gets `fields.{id}` as `nullable|array`, each of the seven keys as `nullable|string|max:255`, and `fields.{id}.country` accepting an active item id of the account's Countries list and the word `keep` while refusing an inactive item, an item of another list and free text; `labels.{id}` exists when the field names a list; the same keys exist under `fields.{repeater}.*.values.{child}.{part}` for an address inside a repeater
- [X] T012 [US1] In `app/Helpers/FieldValueRules.php` give `Address` the arm `['array']` in `forOne()`, add a private `forAddressParts(Field $field, string $prefix): array` returning the seven part rules and the country rule, the country rule being `nullable` then a closure rule `function (string $attribute, mixed $value, Closure $fail): void` that returns when `$value === 'keep'` and otherwise calls `$fail()` unless `ValueListItem::query()->whereKey($value)->where('value_list_id', $countries->id)->where('is_active', true)->exists()`, with `$countries` read by `ValueList::query()->where('account_id', $field->account_id)->where('key', ValueListEnum::Countries->value)->first()`, and call it from `for()` and from `forRepetitionsOf()`
- [X] T013 [US1] In `app/Http/Requests/SaveFieldValuesRequest.php` `attributes()` name each part `fields.{id}.{part}` as the part's translated name joined to the field's lowercase label, such as `street of address`, and the same under `fields.{id}.*.values.{child}.{part}`, so a refusal names the box rather than a key
- [X] T014 [US1] Extend `tests/Unit/Actions/SetFieldValuesTest.php`: a country given as an item id is stored as that item's label text in `value_json.country`, `keep` writes back the stored country unchanged, `keep` with nothing stored writes `''`, an empty country writes `''`, an item id of another account is refused with `ModelNotFoundException`, and an item of the account's Phone labels list is refused with `InvalidArgumentException`, so the action holds the rule whatever surface reaches it
- [X] T015 [US1] In `app/Actions/SetFieldValues.php` add a private `addressFor(Field $field, array $given, ?FieldValueRow $row): array` called from `saveOne()` when the type is `Address`, resolving `country` from an id by reading the item through `ValueListItem::query()->whereKey($id)->where('account_id', $this->user->account_id)->first()`, throwing `ModelNotFoundException` when nothing comes back and `InvalidArgumentException` when its `value_list_id` is not the account's Countries list, then storing its `label`; from `keep` to the `country` key of the value already stored on this field and row; and otherwise to `''`, leaving the other six parts as given
- [X] T016 [P] [US1] Extend `tests/Unit/Helpers/FieldValueFormatterTest.php`: a labelled address reads as the joined line with the label in parentheses through the same copy as an email, an unlabelled one as the line alone, a typed wording beside the open label reads instead of the item, a deactivated item still reads, and `addressLine()` returns the filled parts joined by a comma in card order and null when every part is empty
- [X] T017 [US1] In `app/Helpers/FieldValueFormatter.php` add `public static function addressLine(?array $parts): ?string` holding the join that `address()` does today, and make `address()` call it and append the label exactly as `labelled()` does, wording first then item
- [X] T018 [P] [US1] Extend `tests/Feature/Views/Components/FieldInputTest.php` for an address field: seven inputs named `fields[{id}][street]`, `[extended]`, `[po_box]`, `[city]`, `[region]`, `[postal_code]`, `[country]` appear in that order, each under a visible label carrying the part's name; the whole sits in a `<fieldset>` whose `<legend>` carries the field's label; the combo box is named `labels[{id}]` when the field names a list and is absent otherwise; the country `<select>` offers the active items of the account's Countries list with their ids as values, selects the item whose label matches the stored country ignoring case, offers a selected option valued `keep` worded as the stored country when it matches no active item, and offers no such option otherwise; a stored `po_box` is prefilled; a sensitive address field renders each text box as a password input; the line saying the kind cannot be filled in here is absent for an address and still present for a relation
- [X] T019 [US1] In `resources/views/components/field-input.blade.php` add `@case($type === FieldTypeEnum::Address)` before the `usesALabelList()` case: one `<fieldset>` with the field's label as `<legend>` and the optional marker, the help text, the `x-combo-box` for the label when `$items` is not empty, seven `x-input` boxes named `{$name}[{part}]` with ids `{$id}-{part}`, visible labels `__('Street')`, `__('Extended street line')`, `__('Post office box')`, `__('City')`, `__('Region')`, `__('Postcode')`, values from `$value?->value_json[$part] ?? ''`, `type="password"` when the field is sensitive as the text case does, a per part error read from `$messages?->get("fields.{$field->id}.{$part}")`, and an `x-select` named `{$name}[country]` labelled `__('Country')` offering an empty option, the active items of the account's Countries list read by the same query as T012, and the `keep` option described in [contracts/section-form.md](contracts/section-form.md)
- [X] T020 [P] [US1] Extend `tests/Feature/Views/Components/FieldValueTest.php`: an address reads as its line with the label as a badge beside it, as the line alone when there is no label, as not recorded when the value is null, and the line saying the kind cannot be filled in here no longer appears for an address
- [X] T021 [US1] In `resources/views/components/field-value.blade.php` compute `$line` as `FieldValueFormatter::addressLine($value?->value_json)` for an address and `$value?->value_string` otherwise, and widen the `usesALabelList()` case to `$line !== null`, drawing `$line` and the existing `$kind` badge
- [X] T022 [P] [US1] Extend `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php`: posting an address with a label stores the seven keys and the label, posting seven empty boxes clears a stored address, a required address posted empty redirects back with an error naming the field, a street over 255 characters is refused under `fields.{id}.street`, a country id outside the account's Countries list is refused, and the edit screen renders the seven boxes and no line saying the kind cannot be filled in here
- [X] T023 [P] [US1] Extend `tests/Unit/Actions/CreateFieldTest.php` and `tests/Unit/Actions/UpdateFieldTest.php`: an address field keeps its `value_list_id`, and one created without a list is accepted, proving by test that the `usesALabelList()` widening from 040 covers it with no change of its own
- [X] T024 [P] [US1] Extend `tests/Unit/ViewModels/Settings/Account/Templates/FieldCatalogueViewModelTest.php`: the address entry says what the address is for, its preview carries a `label` and the rows Street, City, Postcode and Country, and `typesOfferingALabelList()` contains `address`
- [X] T025 [US1] In `app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php` rewrite the `address` entry: description `Street, city, postcode and country in one block, and what the address is for.`, and preview `['kind' => 'rows', 'label' => __('Home'), 'rows' => [[__('Street'), 'full'], [__('City'), 'medium'], [__('Postcode'), 'narrow'], [__('Country'), 'medium']]]`
- [X] T026 [US1] In `resources/views/app/settings/account/templates/_field-type-preview.blade.php` draw `$preview['label']`, when set, above the rows of the `rows` kind as the closed list box the `labelled` kind already draws
- [X] T027 [US1] Preselect the supplied list on the field type screen: in `app/Http/Controllers/App/Settings/Account/Templates/FieldController.php` hand the `field-type` view a map of type key to the id of the account's supplied list for every type whose `suppliedLabelList()` is not null, in `resources/views/app/settings/account/field-type.blade.php` select that list in the `value_list_id` select when the picked type is in the map and `old('value_list_id')` is empty, and assert in `tests/Feature/Controllers/App/Settings/Account/Templates/FieldControllerTest.php` that the screen carries the Address labels list id for `address`

**Checkpoint**: the feature is usable end to end on the web for a single address. Nothing new reaches a card yet.

---

## Phase 4: User Story 2 (P2)

**Goal**: every part a device wrote survives an edit on the screen, and an address typed here reaches the device with each part in its place and its label as the type.

**Independent test**: import a card carrying `ADR;TYPE=work:PO Box 12;Suite 3;1725 Slough Avenue;Scranton;PA;18505;USA`, change the city through the section form with `country=keep`, export the card, and confirm the post office box, the suite, `USA` and `TYPE=work` are all still there.

- [X] T028 [P] [US2] Extend `tests/Unit/Http/Dav/VCardMergerTest.php` against [contracts/vcard-mapping.md](contracts/vcard-mapping.md): an address labelled Home writes `ADR;TYPE=home` with seven positional parts, Work writes `TYPE=work`, Holiday home and Other writes `X-ABLabel` carrying its wording, no label writes the field's name as today, and the country travels as the stored text
- [X] T029 [US2] In `app/Http/Dav/VCardMerger.php` `standardType()` add an `'ADR'` arm mapping `AddressLabelEnum::Home->value` to `home` and `AddressLabelEnum::Work->value` to `work`, `default => null`, matching on the key and never on the wording
- [X] T030 [P] [US2] Extend `tests/Unit/Actions/ImportVCardTest.php`: `ADR;TYPE=work` records the Work label on an address field naming the Address labels list, an `X-ABLabel` matching an item wins over the type, the seven parts land in the seven keys as today, and a country matching no item is stored as written, proving by test that `labelOf()` needs no change
- [X] T031 [P] [US2] Extend `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php`: for a stored address a device wrote with `po_box`, `extended` and a country matching no item, the edit screen prefills the two boxes and offers the country as the selected `keep` option, and posting a changed city with `country=keep` keeps `po_box`, `extended` and that country unchanged
- [X] T032 [US2] Write the round trip in `tests/Feature/Controllers/Api/ContactVCardControllerTest.php`: import the card of the independent test, save the block through `contacts.sections.update` changing only the city, export through the vCard endpoint, and assert all seven parts and `TYPE=work` come back

**Checkpoint**: an address written here reaches a phone, and one arriving from a phone survives an edit.

---

## Phase 5: User Story 3 (P3)

**Goal**: an address field inside a repeater records several addresses for one person.

**Independent test**: fill two repetitions of a repeater holding an address field, labelled Home, Holiday Home and Work, save, remove the first, save again, and confirm the second is intact.

- [X] T033 [P] [US3] Extend `tests/Unit/Actions/SetFieldValuesTest.php`: two repetitions each holding an address with a label are saved in order with their labels, omitting the first repetition's id on the next save destroys it and keeps the second intact, a new repetition whose address has seven empty boxes is not created, and `keep` inside a repetition resolves to that repetition's stored country
- [X] T034 [P] [US3] Extend `tests/Feature/Views/Components/FieldInputTest.php`: an address rendered with the names a repetition passes carries `fields[{repeater}][{n}][values][{child}][street]` and its label `fields[{repeater}][{n}][labels][{child}]`
- [X] T035 [US3] Extend `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php`: posting two repetitions of a repeater whose child is an address stores two rows, each with seven keys and its own label, in position order; `saveTheRepetitions()`, `childrenOf()` and `x-field-repetitions` need no change because they read `hasAnInput()` and `isBlank()`

**Checkpoint**: all three stories hold, on the screen, on a card and inside a repeater.

---

## Phase 6: Polish and cross-cutting

- [X] T036 In `spec/models/022-field-value-done.md` reduce "Three types have no control yet" to `relation` and `avatar` and rewrite the decision on the stored shape of an address to say the control now reads and writes it; in `spec/models/017-field-done.md` add the address to the composite types that write a second column; in `spec/spec.md` section 5.5 write the full address as carrying what it is for; and correct `spec/models/017-field-done.md` where it says `is_sensitive` masks on screen, since the record masks no type today and only hides the box while typing and excludes the field from share links, because `spec/` is the product's source of truth and the constitution forbids the two disagreeing silently
- [X] T037 Run `php artisan monica:localize` and translate everything it reports in every configured locale, never editing `lang/*.json` by hand
- [X] T038 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T039 [P] Run `vendor/bin/phpstan analyse` and fix what is new
- [X] T040 Run the full suite, not only this feature's files, because `x-field-input` and `x-field-value` are reached by every record screen
- [ ] T041 Cover [quickstart.md](quickstart.md) by hand, including step 7, the device address whose country is kept as it arrived, and step 11, the preselected list
- [ ] T042 Confirm the control is operable with the keyboard alone, announced once by the field's name and then part by part by a screen reader, readable zoomed to 200 percent, and usable on desktop, tablet and phone
- [ ] T043 State in the pull request that principle X is unmet because no endpoint writes field values of any type, as [plan.md](plan.md) Deviations requires, and that the supplied Countries list holds ten countries, as [research.md](research.md) records

---

## Dependencies

- **Phase 1** blocks nothing but should come first.
- **Phase 2** blocks every story. All three read `hasAnInput()`, `usesALabelList()` and `isBlank()`, and all three write through `SetFieldValue`.
- **Phase 3** is the control, and Phases 4 and 5 both edit through it, so they follow it rather than run beside it. Phase 4 and Phase 5 are independent of each other.
- **Phase 6** depends on every story that is being shipped.
- Within a phase: T002 before T003, T004 before T005, T007 before T008, T009 before T010, T011 before T012, T012 before T013, T014 before T015, T016 before T017, T018 before T019, T020 before T021, T017 before T021, T024 before T025, T025 before T026, T028 before T029, T030 and T031 before T032, T033 before T035.

## Implementation strategy

Phase 1, Phase 2, Phase 3, then stop and validate. That is the MVP: an address filled in, changed, cleared, labelled and validated on the surface everybody uses, and the line saying it cannot be filled in here gone. Phase 4 is the phase least safe to defer, because until T031 is proven a save from the screen could drop a part a device wrote yesterday. Phase 5 is small once Phase 3 stands. Phase 6 is not optional under the constitution: T037 and T040 are delivery conditions, not tidying.
