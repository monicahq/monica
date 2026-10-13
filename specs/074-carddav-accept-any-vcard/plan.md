# Implementation Plan: CardDAV accepts any incoming vCard

**Branch**: `2026-10-11-carddav-accept-any-vcard` | **Date**: 2026-10-12 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/074-carddav-accept-any-vcard/spec.md`

## Summary

A save into an address book stops being refused for what a contacts application actually writes, and
stops failing with a server error. Four changes, each decided in [research.md](research.md):

1. `CardDavPlugin::validateVCard` reads every save forgiving, repairs it and ignores Sabre's level 3
   verdict, unless the client asked for strict handling (R2).
2. A line the parser cannot read travels as `X-MONICA-UNREADABLE-LINE` and goes back to devices as
   the raw text it was (R3).
3. `ImportVCard` borrows a name when the card has none (R4), hands the repeater rather than its
   child to `CreateFieldValueRow`, removes an emptied repetition, and leaves on the card any value its
   field refuses (R5).
4. The default template holds addresses in a repeater, for accounts created from now on (R6).

The answers, card by card, are in [contracts/carddav-save.md](contracts/carddav-save.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13

**Primary Dependencies**: sabre/dav 4.7.1, sabre/vobject 4.6.1

**Storage**: no schema change. The unreadable lines live in the existing `contacts.vcard` column,
inside the kept card. `data-model.md` is therefore omitted.

**Testing**: PHPUnit, `php artisan test --compact`

**Target Platform**: the CardDAV endpoint under `/dav`

**Project Type**: web application, server side only

**Performance Goals**: a save stays one parse more than today, plus one parse per unreadable line,
capped at 50

**Constraints**: the answers of [026](../026-dav-synchronisation/spec.md) for conflicts, deletions,
moves and the person's own contact are unchanged (SC-004)

**Scale/Scope**: one Sabre plugin, one action, two enums, one seeder, one docs view model

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the business logic | Pass. The name and the fields stay in `ImportVCard`; the plugin only repairs the text |
| II. Simplicity | Pass. No new class; the line handling is two private methods of the plugin that uses them |
| III. Tested before merged | Pass. Every row of the contract gets a test, and the default template is finally under test |
| IV. Laravel conventions | Pass |
| V. Localized | Pass. The docs copy changes in English; `monica:localize` reports what else moves |
| VI. Nothing changes under anybody's feet | Pass. The default template change reaches new accounts only; no published structure is touched |
| VII. One personalisation mechanism | Pass. The unreadable line rides on the kept card 026 already keeps; it is never read, searched or shown |
| VIII. Nothing disappears silently | Pass. An unreadable line and a refused value both stay on the card |
| IX. Accessible | Not affected: no screen changes beyond a repeater the template screens already draw |
| X. Through the API | Pass. Every write still goes through the actions the API calls |
| XI. Short record | Pass. All artifacts under their caps |

Re-checked after design: no change.

## Project Structure

### Documentation (this feature)

```text
specs/074-carddav-accept-any-vcard/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
├── contracts/carddav-save.md
└── tasks.md            (next: /speckit-tasks)
```

### Source Code (repository root)

```text
app/Http/Dav/CardDavPlugin.php            lenient validation, unreadable lines in and out
app/Actions/ImportVCard.php               borrowed name, repeater, emptied repetition, refused value
app/Enums/DefaultFieldEnum.php            Addresses repeater holding Address
app/Enums/DefaultSectionEnum.php          the Addresses section holds the repeater
database/seeders/ExampleAccountSeeder.php the sample address written in a repetition
app/ViewModels/Docs/CarddavDocsViewModel.php  "A change is refused" no longer says every contact needs a name
specs/026-dav-synchronisation/            FR-009 and the contract's PUT rows point at 074

tests/Unit/Http/Dav/CardDavPluginTest.php
tests/Unit/Actions/ImportVCardTest.php
tests/Feature/Dav/AddressBookTest.php     the two refusal tests now expect acceptance or 400
tests/Unit/Jobs/CreateDefaultTemplateTest.php
```

**Structure Decision**: the existing layout. Nothing new at the top level.

## Complexity Tracking

No violation to justify.
