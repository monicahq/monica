# Tasks: CardDAV accepts any incoming vCard

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/carddav-save.md](contracts/carddav-save.md), [quickstart.md](quickstart.md)

**Tests**: required. Principle III and `CLAUDE.md` make a change done only when it is covered. Within each story the tests come first and fail before the code changes. Follow `.claude/rules/testing.md`: data from The Office, `#[Test]`, snake_case names, no comments in tests.

## Phase 1: Foundational

- [X] T001 Add a public `validate(string $data, ?string $prefer = null): string` to tests/Fixtures/Http/Dav/ExposedCardDavPlugin.php that gives the plugin a `Sabre\DAV\Server` whose request carries `Prefer: $prefer` when set, calls `validateVCard($data, $modified)` and returns `$data`

## Phase 2: User Story 1, a card the device considers valid is saved (P1)

**Independent test**: each "After" row of the contract for vCard 2.1, an unreadable line, a `_` in a name, two `FN`, a non card and strict handling answers as written.

- [X] T002 [P] [US1] In tests/Unit/Http/Dav/CardDavPluginTest.php, through `validate()`, prove: vCard 2.1 (with `N;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE`) comes out readable; `X_CUSTOM:` is kept; two `FN` without `ALTID` are kept; a missing `UID` and `VERSION` are repaired; a body `this is not a card` and a `VCALENDAR` throw `UnsupportedMediaType`; with `$prefer = 'handling=strict'` a 2.1 card still throws `UnsupportedMediaType`
- [X] T003 [P] [US1] In tests/Unit/Http/Dav/CardDavPluginTest.php prove that a line with no colon, a line `X CUSTOM:value` and a folded unreadable line with its continuation each come out as one `X-MONICA-UNREADABLE-LINE` holding the raw text, the rest of the card intact; and pin Sabre's message by asserting `Reader::read` of a no colon line throws a `ParseException` matching `/Line starting at \d+/`
- [X] T004 [P] [US1] In tests/Unit/Http/Dav/CardDavPluginTest.php prove through `convert()` that a card holding `X-MONICA-UNREADABLE-LINE` comes back with the raw line in its place for `vcard4` and `vcard3`, and keeps the property for `jcard`
- [X] T005 [US1] In app/Http/Dav/CardDavPlugin.php make `validateVCard` follow research R2: with `getHTTPPrefer()['handling'] === 'strict'` keep today's code path (jCard, `withOneOfItsAlternativeNames`, parent); otherwise convert jCard as today, set the unreadable lines aside (T006), `Reader::read(..., OPTION_FORGIVING)`, throw `UnsupportedMediaType` unless the result is a `VCard`, run `validate(Node::PROFILE_CARDDAV | Node::REPAIR)` ignoring the messages, add one `X-MONICA-UNREADABLE-LINE` per line set aside, assign the serialisation to `$data` and set `$modified = true`
- [X] T006 [US1] In app/Http/Dav/CardDavPlugin.php add the private method setting unreadable lines aside per R3: on `ParseException` read N from `Line starting at N`, remove physical line N and the following lines starting with a space or a tab, keep their raw text joined by CRLF, retry, at most 50 times, then let the last `ParseException` become `UnsupportedMediaType`
- [X] T007 [US1] In app/Http/Dav/CardDavPlugin.php make `convertVCard` call the parent first, then for `vcard3` and `vcard4` replace each serialised `X-MONICA-UNREADABLE-LINE` line (after unfolding) by its unescaped value; leave `jcard` alone
- [X] T008 [US1] In tests/Feature/Dav/AddressBookTest.php prove over HTTP: `PUT` of a 2.1 card answers 201 and creates the contact; `PUT` of a card holding `this line has no colon` answers 201 and a `GET` returns that line unchanged; `PUT` of a 2.1 card with header `Prefer: handling=strict` answers 415; `it_refuses_a_body_that_is_not_a_card` still answers 415

## Phase 3: User Story 2, every email address, phone number and address reaches the contact (P1)

**Independent test**: on the default template, a card with two `EMAIL`, two `TEL` and two `ADR` leaves six values on the contact.

- [X] T009 [P] [US2] In tests/Unit/Actions/ImportVCardTest.php, on a contact built from `new CreateDefaultTemplate(user: $user)->handle()`, prove: one `EMAIL` and one `TEL` are written; two of each land in two repetitions in the card's order with their labels; a contact holding three `TEL` saved with two keeps exactly two repetitions and no empty one; a repeater whose `repeat_max` is 1 writes the first `EMAIL`, leaves the second on `$contact->vcard`, and the save succeeds
- [X] T010 [US2] In app/Actions/ImportVCard.php `writeSlot`, pass `$field->parent` to `CreateFieldValueRow`, and when a growable slot is emptied destroy its repetition with `DestroyFieldValueRow` if the repetition then holds no other value, `DestroyFieldValue` otherwise
- [X] T011 [US2] In app/Actions/ImportVCard.php `writeSlot`, catch `InvalidArgumentException` and `App\Exceptions\TooManyRepetitions` around one slot's `CreateFieldValueRow` and `SetFieldValue` and return, so the value stays on the kept card (FR-004, FR-008)
- [X] T012 [P] [US2] In app/Enums/DefaultFieldEnum.php add `case Addresses = 'addresses'`, with `type()` `FieldTypeEnum::Repeater`, `label()` `Addresses`, `children()` `[self::Address]` and `repetitionOrder()` `NewestLast`; in app/Enums/DefaultSectionEnum.php make the `Addresses` section hold `DefaultFieldEnum::Addresses` instead of `Address`
- [X] T013 [US2] In database/seeders/ExampleAccountSeeder.php add `DefaultFieldEnum::Addresses->value => DefaultFieldEnum::Address` to the repeater map beside `Emails` and `Phones` (line 300), and write the sample address (line 293) through a repetition, the way emails and phones are written (line 366)
- [X] T014 [P] [US2] In tests/Unit/Jobs/CreateDefaultTemplateTest.php (around line 285) assert the `address` field sits inside the `addresses` repeater, which is repeatable
- [X] T015 [US2] In tests/Feature/Dav/AddressBookTest.php prove `PUT` of a card with two `EMAIL`, two `TEL` and two `ADR` into a vault whose default template comes from `CreateDefaultTemplate` answers 201 and leaves six values on the contact

## Phase 4: User Story 3, a card with no name still becomes a contact (P2)

**Independent test**: cards holding only `ORG`, only `NICKNAME`, only `EMAIL`, only `TEL` are accepted and named after it; a card with none of them answers 400.

- [X] T016 [P] [US3] In tests/Unit/Actions/ImportVCardTest.php prove the contact is named after `ORG` (first component), then `NICKNAME`, then `EMAIL`, then `TEL`, each when the earlier ones are absent; an empty `FN` and `N:;;;;` count as no name; a card with none of them throws `BadRequest`; and in tests/Unit/Http/Dav/VCardMergerTest.php that the merged card of a borrowed name carries `N:;;;;`
- [X] T017 [US3] In app/Actions/ImportVCard.php `writeTheName`, when no row of kind other than phonetic is left, prepend one written row (`formatted_name` the borrowed text cut to 255, every part null) from the first of `ORG`, `NICKNAME`, `EMAIL`, `TEL` on the card, and throw the existing `BadRequest` only when none is present
- [X] T018 [US3] In tests/Feature/Dav/AddressBookTest.php make `it_refuses_a_card_with_no_name_at_all` expect 400 instead of 415, keep `it_refuses_a_card_whose_name_is_empty` at 400, and prove `PUT` of `TEL:+15705550100` alone answers 201 with the contact named `+15705550100`
- [X] T019 [US3] In app/ViewModels/Docs/CarddavDocsViewModel.php line 88, replace "A contact must have a name" with copy saying a card with no name, organisation, nickname, email address or phone number is refused

## Phase 5: Polish

- [X] T020 [P] In specs/026-dav-synchronisation/spec.md FR-009 and specs/026-dav-synchronisation/contracts/dav.md lines 43 and 44, add one line pointing at specs/074-carddav-accept-any-vcard for what a save now accepts
- [X] T021 Run `php artisan monica:localize`, translate what it reports in every configured locale, then `php artisan monica:localize --check`
- [X] T022 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, and the whole suite with `php artisan test --compact`, since the default template change reaches every screen built on it
- [ ] T023 Walk through quickstart.md steps 2 to 4 against a local instance and record the result in the pull request
- [X] T024 Record the change with the feature-documentator skill

## Dependencies

- T001 blocks T002, T003 and T004. Within each story the tests come before the code: T002 to T004 before T005 to T007, T009 before T010 and T011, T016 before T017.
- T005 needs T006; T008 needs T005 to T007. T013 and T015 need T012. T018 needs T017.
- After T001, US1 (CardDavPlugin.php) can run beside US2 and US3. US2 and US3 both edit ImportVCard.php, so run them one after the other.
- Phase 5 comes last. MVP: US2 alone removes the server error that loses most real cards; US1 follows.
