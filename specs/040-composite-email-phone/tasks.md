---

description: "Task list for making email and phone composite field types"
---

# Tasks: Composite Email and Phone Fields

**Input**: Design documents from `/specs/040-composite-email-phone/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/vcard-mapping.md](contracts/vcard-mapping.md), [quickstart.md](quickstart.md)

**Tests**: not optional here. Constitution III makes a test a delivery condition, so every phase carries its own and the story is not done until they pass.

**Format**: `[ID] [P?] [Story] Description`. `[P]` means a different file with no incomplete dependency.

## Phase 1: Setup

- [X] T001 Cut the working branch `2026-09-18-composite-email-phone` from an up to date `main`, as the constitution's workflow requires

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: the question a type answers, the list a field names, and the column the label lands in. Both stories read all three.

**CRITICAL**: no user story work begins until this phase is complete.

- [X] T002 Extend `tests/Unit/Enums/FieldTypeEnumTest.php`: `usesALabelList()` is true for `Email` and `Phone` and false for every other case, `usesValueList()` stays false for both, and `valueColumn()` still answers `value_string` for both
- [X] T003 Add `usesALabelList(): bool` to `app/Enums/FieldTypeEnum.php` returning `in_array($this, [self::Email, self::Phone], true)`, leaving `valueColumn()`, `hasAnInput()` and `usesValueList()` exactly as they are
- [X] T004 [P] Extend `tests/Unit/Enums/DefaultFieldEnumTest.php`: `Email` names `ValueListEnum::EmailLabels` and `Phone` names `ValueListEnum::PhoneLabels`
- [X] T005 [P] Add those two arms to `DefaultFieldEnum::valueList()` in `app/Enums/DefaultFieldEnum.php`, so `CreateDefaultTemplate` supplies the list without a change of its own
- [X] T006 Extend `tests/Unit/Actions/SetFieldValueTest.php`: a phone value writes the number to `value_string` and the label to `value_list_item_id` in one call, writing the number again without a label leaves the label standing, a label alone writes nothing, and `is_unique` still refuses a repeated number whatever labels the two carry
- [X] T007 Reuse the existing `?ValueListItem $valueListItem` parameter of `app/Actions/SetFieldValue.php` for the label rather than adding a second one, write it to `value_list_item_id` when `$this->field->type->usesALabelList()`, and drop that column from `emptyColumns()` for those types so writing the value never clears the label. Reusing it gets `moveTheChoiceCounts()` and the list membership check for free, which a new parameter would have had to repeat
- [X] T008 Confirm by test rather than by reading that `SetFieldValue::refuseADuplicate()` needs no change, since it compares `$this->column`, which stays `value_string`

**Checkpoint**: a labelled value can be written and read back. No screen offers it yet.

---

## Phase 3: User Story 1 (P1) 🎯 MVP

**Goal**: somebody records a number or an address together with what it is for, and the record reads it back.

**Independent test**: fill the supplied Phone field on a contact, pick Mobile, save, and confirm the record shows the number with Mobile beside it and that clearing the label leaves the number accepted and alone.

- [X] T009 [P] [US1] Extend `tests/Unit/Helpers/FieldValueRulesTest.php`: a `labels.{id}` rule exists for an email or phone field naming a list, it refuses an item of another list and a deactivated item, and no such rule exists for a field naming none
- [X] T010 [US1] In `app/Helpers/FieldValueRules.php` add `"labels.{$field->id}"` for a field whose type `usesALabelList()` and whose `value_list_id` is set, reusing `choiceRule($field)` unchanged with a leading `nullable`, and the same inside `forRepetitionsOf()` as `fields.{id}.*.labels.{child}`
- [X] T011 [US1] Add `'labels' => ['array']` to the rules in `app/Http/Requests/SaveFieldValuesRequest.php` and name the label in `attributes()` so a refusal reads as the field's own wording
- [X] T012 [US1] Extend `tests/Unit/Actions/SetFieldValuesTest.php`: a label given with no value leaves the field unfilled, a required field is satisfied by the value alone with no label, and a label inside a repetition row is saved against the right repetition
- [X] T013 [US1] Add a `private readonly array $labels = []` parameter to `app/Actions/SetFieldValues.php`, read the item in a `labelFor(Field $field, mixed $given)` private method returning null unless the type `usesALabelList()`, pass it to `SetFieldValue` as `labelItem`, and carry `labels` beside `values` through `saveTheRepetitions()`
- [X] T014 [US1] Pass `labels: $request->input('labels', [])` from `app/Http/Controllers/App/Vaults/ContactSectionController.php` to the action, adding no rule of its own
- [X] T015 [P] [US1] Extend `tests/Unit/Helpers/FieldValueFormatterTest.php`: a labelled phone reads as the number and its label, an unlabelled one reads as the number alone, and a label whose item was deactivated still reads
- [X] T016 [US1] In `app/Helpers/FieldValueFormatter.php` give `Email` and `Phone` their own arm returning the value with the label appended through `trans_choice` free copy, keeping `Text` and `Url` on the plain string arm
- [X] T017 [US1] In `resources/views/components/field-input.blade.php` give email and phone a case before `hasAnInput()`: the existing `x-input` typed `email` or `tel`, and beside it a label select named `labels[{id}]` rendered only when the field names a list, the two inside one `<fieldset>` whose `<legend>` is the field's label so a screen reader announces them as one thing
- [X] T018 [US1] In `resources/views/components/field-value.blade.php` add a case drawing the value with its label as a badge beside it, and the value alone when there is none
- [X] T019 [P] [US1] Write `tests/Feature/Views/Components/FieldInputTest.php` coverage for the pair: the select carries `name="labels[{id}]"`, the fieldset groups the two, no select is rendered for a field naming no list, and a deactivated item is not offered
- [X] T020 [P] [US1] Move `email` and `phone` from the Simple family to the Composite family in `app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php`, rewriting both descriptions and both previews to the `rows` kind the other composite types use
- [X] T021 [P] [US1] Add the label list to `FieldTypeEnum::Email` and `FieldTypeEnum::Phone` in `app/Helpers/FieldSettingsCatalogue.php` beside the placeholder they already offer, so the inspector can point a field at another list or at none
- [X] T022 [P] [US1] Extend `tests/Unit/Jobs/DetectDuplicateContactsTest.php` with two contacts sharing an email address under different labels, proving FR-010 holds with no change to the job
- [X] T023 [US1] Extend `tests/Unit/Actions/CreateFieldTest.php` and `tests/Unit/Actions/UpdateFieldTest.php`: an email or phone field keeps its `value_list_id`, and one created without a list is accepted rather than refused
- [X] T024 [US1] In `app/Actions/CreateField.php` and `app/Actions/UpdateField.php` widen the `value_list_id` write to `usesValueList() || usesALabelList()`, leaving the guard that refuses a choice field with no list limited to `usesValueList()`

**Checkpoint**: the feature is usable end to end on the web. Nothing reaches a card yet.

---

## Phase 4: User Story 2 (P2)

**Goal**: the label survives the round trip to an address book and back.

**Independent test**: export a contact whose phone is labelled Work, confirm the card carries `TEL;TYPE=work`, import it back, and confirm the label is the one it left with.

- [X] T025 [P] [US2] Extend `tests/Unit/Http/Dav/VCardMergerTest.php` against [contracts/vcard-mapping.md](contracts/vcard-mapping.md): a standard label writes `TYPE`, an invented one writes `X-ABLabel`, no label writes the field's name as it does today, and a sensitive field still writes nothing
- [X] T026 [US2] In `app/Http/Dav/VCardMerger.php` map the value's label item key to the standard type through a private `standardType(string $key, string $property): ?string`, matching on the item key and never on its translated wording, and fall back to `X-ABLabel` for anything else
- [X] T027 [US2] In the same file make `append()` write the field's name only when the value carries no label, so the two never compete for one property
- [X] T028 [P] [US2] Extend `tests/Unit/Actions/ImportVCardTest.php`: `X-ABLabel` matching a list item wins, `TYPE` is read next with `cell` reading as `mobile`, an unmatched label records the value with none, and nothing is ever added to the account's list
- [X] T029 [US2] In `app/Actions/ImportVCard.php` resolve the label in that order and pass it to `SetFieldValue` as `labelItem`
- [X] T030 [US2] Write the round trip test in `tests/Feature/Controllers/Api/ContactVCardControllerTest.php`: exporting then importing a labelled value returns the same label, which is the only test that catches the two sides disagreeing
- [X] T031 [P] [US2] Cover `value_list_id` for an email or phone field in `tests/Feature/Controllers/Api/FieldControllerTest.php`. No controller change was needed: `app/Http/Controllers/Api/FieldController.php` already validates the column as nullable for every type and hands it to the actions widened in T024

**Checkpoint**: a label written here reaches a phone, and one arriving from a phone is kept.

---

## Phase 5: Polish and cross-cutting

- [X] T032 Move `email` and `phone` to the Composite family in the table in `spec/models/017-field-done.md` and in the families list in `spec/spec.md` section 5.5, since `spec/` is the product's source of truth and the constitution forbids the two disagreeing silently
- [X] T033 Record in `spec/models/017-field-done.md` that a composite type may write a second column, which is the first time the product does it
- [X] T034 Run `php artisan monica:localize` and translate everything it reports in every configured locale, never editing `lang/*.json` by hand
- [X] T035 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T036 [P] Run `vendor/bin/phpstan analyse` and fix what is new
- [X] T037 Run the full suite, not only this feature's files, because `x-field-input` and `x-field-value` are reached by every record screen and several view tests assert on their markup
- [ ] T038 Cover [quickstart.md](quickstart.md) by hand, including step 5, the deactivated label that must still read on a record that already carries it. **Not done.** No port can be bound from this environment, so the application could not be opened. Every step that is a behaviour rather than a look is covered by a test instead: the labelled write and the unlabelled one in `SetFieldValuesTest`, the deactivated label in `FieldValueFormatterTest` and `FieldInputTest`, the export and the round trip in `ContactVCardControllerTest`, and duplicate detection in `DetectDuplicateContactsTest`. What is left is the look
- [ ] T039 Confirm the value and its label are operable with the keyboard alone, announced as one thing by a screen reader, and readable zoomed to 200 percent on desktop, tablet and phone. **Partly done.** The markup contract is asserted in `tests/Feature/Views/Components/FieldInputTest.php`: the pair sits in a `<fieldset>` under a `<legend>` carrying the field's name, each control has its own label, and no unlabelled control is rendered. The screen reader pass, the 200 percent zoom and the phone and tablet widths need somebody to open the screen and were not done here
- [X] T040 State in the pull request that FR-013 is met for reading and unmet for writing, and why, as [plan.md](plan.md) Deviations requires

---

## Dependencies

- **Phase 1** blocks nothing but should come first.
- **Phase 2** blocks Phases 3 and 4. Both stories read `usesALabelList()` and both write through `SetFieldValue`.
- **Phase 3** and **Phase 4** are independent of each other once Phase 2 is done.
- **Phase 5** depends on every story that is being shipped.
- Within a phase: T002 before T003, T004 before T005, T006 before T007, T009 before T010, T012 before T013, T015 before T016, T023 before T024, T025 before T026, T026 before T027, T028 before T029, T017 and T018 before T019, T026 and T029 before T030.

## Implementation strategy

Phase 1, Phase 2, Phase 3, then stop and validate. That is the MVP: labels recorded, shown, validated and proven not to disturb duplicate detection or uniqueness, on the surface everybody uses. Phase 4 is what stops the label being a local secret, and it is the phase least safe to defer, because a value that round trips through a device today would come back stripped of a part it did not have yesterday. Phase 5 is not optional under the constitution: T034 and T037 are delivery conditions, not tidying.
