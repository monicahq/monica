# Implementation Plan: Native quality translations for twenty two new locales

**Branch**: `2026-09-07-native-locale-translations` | **Date**: 2026-09-07 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/025-native-locale-translations/spec.md`

## Summary

Register twenty two new locales and translate the 2,206 product strings plus the 148 the framework
supplies into each, then review the nine already shipped to the same standard. One groundwork change
clears three blockers, then one change per locale, then a closing guard.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13.30.1

**Primary Dependencies**: none added. Carbon ships locale data for all twenty two codes, verified
against the installed version. `Illuminate\Translation\MessageSelector` decides how many wordings a
counted phrase needs per language.

**Storage**: `config/monica.php` for the registration, `lang/<locale>.json` for the product strings,
`lang/<locale>/{validation,auth,passwords,pagination}.php` for the framework strings.

**Testing**: PHPUnit. `tests/Unit/Translation`, `tests/Unit/Console/Commands/LocalizeCommandTest.php`,
`tests/Unit/Helpers/LocaleResolutionTest.php`, `tests/Feature/Localization`.

**Target Platform**: the responsive web application, plus the JSON API, which resolves its language
from `Accept-Language`.

**Project Type**: web application, server rendered Blade.

**Constraints**: `monica:localize --check` fails when any registered locale is less than fully
translated, so registration and translation are one atomic change. There is no partially translated
state the suite tolerates.

**Scale/Scope**: 2,206 product strings and 148 framework strings per locale, 22 new locales and 9
reviewed, about 74,000 translated values in total. 81 counted phrases, 451 strings with placeholders,
zero strings carrying markup.

## Constitution Check

*GATE: passes before Phase 0 and after Phase 1.*

| Principle | How this feature satisfies it |
| --- | --- |
| I Actions hold the business logic | No business logic. Configuration, translation files and view model reads. |
| II Simplicity over abstraction | No new abstraction. The per-locale change is a config entry and some files. |
| III Tested before merged | The groundwork change carries the schema, sentinel and picker tests. Each locale is covered by the existing suite, which iterates `config('monica.locales')`. |
| IV Laravel conventions | `lang:publish` for the framework files, migrations edited before production with `up()` only. |
| V Localized and safe | This is the principle the feature serves. It also removes the standing breach: the framework strings read as English in all nine shipped locales today. |
| IX Accessible to everybody | The picker gains a scroll container and keyboard reachable ordering at 31 entries. |
| XI The written record is short | `data-model.md` omitted, see below. `plan.md` is at its cap. |

One conflict to resolve rather than justify: `.specify/memory/constitution.md:110` says translations
are produced in "every configured locale, of which there are nine today". Governance requires the
pull request that finds it to fix it. Dropping the count is a PATCH amendment in its own pull request.

## Project Structure

### Documentation (this feature)

```text
specs/025-native-locale-translations/
├── plan.md
├── research.md
├── contracts/locale-registration.md
├── quickstart.md
└── checklists/requirements.md
```

`data-model.md` is omitted. There is no data model beyond a configuration array and some files, and
the constitution requires an artifact whose content another one carries to be left out.

### Source Code (repository root)

```text
config/monica.php                    # one entry per locale
lang/<locale>.json                   # 2,206 product strings, written by monica:localize
lang/<locale>/*.php                  # 148 framework strings, hand maintained
app/Helpers/StructureFileReader.php  # locale regex and length cap
app/ViewModels/Auth/GuestViewModel.php
app/ViewModels/Settings/Account/Preferences/PreferencesViewModel.php
app/Console/Commands/LocalizeCommand.php
resources/views/components/language-picker.blade.php
resources/docs/api/03-profile.php
database/migrations/*                # five locale columns widened
.claude/rules/translations.md        # glossary and the review record
```

**Structure Decision**: nothing new is created. Every path above exists, and the feature adds files
only under `lang/`.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Editing five shipped migrations | `fil_PH` is six characters against a `string(5)` column | A new migration widening five columns is more code for a product that has not reached production, where the house rule is to edit |
| Hand maintained PHP language files | `monica:localize` manages JSON only, and the framework's strings are PHP | Moving them into JSON would fork the framework's own keys |
