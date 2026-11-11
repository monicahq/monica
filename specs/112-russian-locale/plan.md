# Implementation Plan: Russian locale

**Branch**: `2026-10-12-russian-locale` | **Date**: 2026-10-12 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/112-russian-locale/spec.md`

## Summary

Register `ru_RU` through the add-locale skill and translate all 3,588 strings plus the framework
files. Sorting, headings, search folding, slugs, dates and browser detection already handle Russian;
the plan adds tests that pin that and changes no application code. Decisions are in
[research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major)

**Primary Dependencies**: laravel-lang 15.37 (ships `ru`), Carbon (ships `ru_RU`), ICU via `intl`

**Storage**: `lang/ru_RU.json`, `lang/ru_RU/*.php`, the `ru_RU` entry of `config/localization.php`

**Testing**: PHPUnit, `php artisan monica:localize --check`

**Target Platform**: the existing web application, API and emails

**Project Type**: web application (Laravel monolith)

**Performance Goals**: none beyond the shipped locales

**Constraints**: every string translated before release; glossary fixed in research.md

**Scale/Scope**: 3,588 JSON strings, 108 counted phrases, six framework files

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. No action is added or changed |
| II. Simplicity | Pass. Configuration and translation files only |
| III. Tested before merged | Pass. `CountedPhraseTest` entry, two `LocaleCollationTest` cases, the localization check |
| IV. Laravel conventions | Pass. `lang:add` and the JSON translation files |
| V. Localized UX | Pass. The point of the feature; no left to right assumption is added |
| VI. Nothing changes under anybody's feet | Pass. No existing user's locale changes |
| VII. Stable core | Not affected |
| VIII. Nothing disappears silently | Not affected |
| IX. Accessible | Pass. `<html lang>` follows the active locale; longer strings are checked at 200% zoom |
| X. Through the API | Pass. No capability is added; the API answers in Russian through `LocaleResolution::forApi()` |
| XI. Short record | Pass. No data model or contract file: research.md carries the config entry |

Post design re-check: unchanged, no violation.

## Project Structure

### Documentation (this feature)

```text
specs/112-russian-locale/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── tasks.md        (from /speckit-tasks)
```

### Source Code (repository root)

```text
config/localization.php                       # 'ru' alias, 'ru_RU' locale entry
lang/ru_RU.json                               # written by monica:localize, then translated
lang/ru_RU/{actions,auth,http-statuses,pagination,passwords,validation}.php   # from lang:add
.claude/rules/translations.md                 # glossary column, native reader row
tests/Unit/Translation/CountedPhraseTest.php  # 'ru_RU' => 3
tests/Unit/Helpers/LocaleCollationTest.php    # Ё folds, Й stays, Cyrillic order
```

**Structure Decision**: no new directory and no application class. The language pickers and the
validation rules read `App\Helpers\AvailableLocales` and are not touched (skill step 7).

## Order of work

1. Alias, `lang:add ru_RU`, config entry. The suite stays green with an empty JSON file only after
   step 2, so steps 1 to 3 land together.
2. `monica:localize`, then translate the missing keys in batches by key, glossary first.
3. Three wordings on every counted phrase; the four `[0,*]` phrases keep one.
4. `CountedPhraseTest` entry, the two collation tests, `translations.md` updates.
5. The gates in `CLAUDE.md`: tests, `monica:localize --check`, PHPStan, Pint.

## Complexity Tracking

No violation to justify.
