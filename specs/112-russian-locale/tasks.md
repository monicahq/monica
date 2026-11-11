---
description: "Task list for the Russian locale"
---

# Tasks: Russian locale

**Input**: `specs/112-russian-locale/` ([plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md))

**Tests**: the project rules require them; only the two the plan names are added.

## Phase 1: Setup

- [X] T001 Update `main`, cut branch `2026-10-12-russian-locale` from it, and carry the untracked `specs/112-russian-locale/` across

## Phase 2: Foundational (blocks every story)

- [X] T002 Add `'ru' => 'ru_RU'` to the `aliases` array of `config/localization.php`, keeping the array sorted
- [X] T003 Run `php artisan lang:add ru_RU --no-interaction` and confirm it wrote `lang/ru_RU.json` and the six files in `lang/ru_RU/`
- [X] T004 Add the `ru_RU` entry to the `locales` array of `config/localization.php` exactly as research.md "Config entry" gives it (no `currency`, no `answers_bare_language`, no `typography`), in alphabetical position
- [X] T005 Add `'ru_RU' => 3` to `WORDINGS_PER_LOCALE` in `tests/Unit/Translation/CountedPhraseTest.php`, in alphabetical position
- [X] T006 Run `php artisan monica:localize` so `lang/ru_RU.json` holds every key with an empty value; never add or reorder keys by hand
- [X] T007 [P] Add the `ru_RU` column to the glossary in `.claude/rules/translations.md` with the 17 words from research.md "Glossary column"
- [X] T008 [P] Add the `ru_RU` row to the native reader table in `.claude/rules/translations.md`: `No`, three wordings for every number, declares no typography

## Phase 3: User Story 1, read the product in Russian (P1) MVP

**Independent test**: quickstart.md "By hand", rows 1 to 3 and the email row.

- [X] T009 [US1] Check the six files in `lang/ru_RU/` that laravel-lang supplied against `lang/en/` for key parity and fix any value still in English
- [X] T010 [US1] Translate entries 1 to 600 of `lang/ru_RU.json`: glossary words only, *вы* in lower case, `«…»` for quotation, every `:placeholder` unchanged, counted phrases left to T016
- [X] T011 [US1] Translate entries 601 to 1200 of `lang/ru_RU.json` under the rules of T010
- [X] T012 [US1] Translate entries 1201 to 1800 of `lang/ru_RU.json` under the rules of T010
- [X] T013 [US1] Translate entries 1801 to 2400 of `lang/ru_RU.json` under the rules of T010
- [X] T014 [US1] Translate entries 2401 to 3000 of `lang/ru_RU.json` under the rules of T010
- [X] T015 [US1] Translate entries 3001 to the end of `lang/ru_RU.json` under the rules of T010

## Phase 4: User Story 2, counts, dates and numbers (P1)

**Independent test**: every counted phrase reads correctly at 0, 1, 2, 5, 11, 21 and 22; quickstart.md date, number and contact count rows.

- [X] T016 [US2] Give each of the 108 counted phrases in `lang/ru_RU.json` three wordings (one, few, many), each interpolating `:count`, keeping any `{1}` pin, and the four `[0,*]` phrases one wording as research.md "Plural wordings" says
- [X] T017 [US2] Run `php artisan test --compact tests/Unit/Translation` and fix every Russian value it reports

## Phase 5: User Story 3, Russian alphabetical order (P2)

**Independent test**: `php artisan test --compact tests/Unit/Helpers/LocaleCollationTest.php`.

- [X] T018 [P] [US3] Add `it_folds_yo_into_ye_in_russian` to `tests/Unit/Helpers/LocaleCollationTest.php`: `foldForSearch('Ёлкин', 'ru_RU')` is `елкин`, `headingLetter('Ёлкин', 'ru_RU')` is `Е`, `foldForSearch('Йорк', 'ru_RU')` is `йорк` and `headingLetter('Йорк', 'ru_RU')` is `Й`
- [X] T019 [P] [US3] Add `it_sorts_names_in_russian_alphabetical_order` to `tests/Unit/Helpers/LocaleCollationTest.php`: Яна, Жанна, Ёлкин, Евгений, Анна, Иван, Йорк sort to Анна, Евгений, Ёлкин, Жанна, Иван, Йорк, Яна under `ru_RU`

## Phase 6: Polish

- [X] T020 Run `php artisan monica:localize --check` and resolve every report for every locale
- [X] T021 Search `lang/ru_RU.json` for учётная запись, сейф, отношение, активность, каталог and `"Вы`, and replace each with its glossary or register form
- [X] T022 Run `php artisan test --compact`, then `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [X] T023 Walk quickstart.md "By hand" and record any string cut at 200% zoom as a finding, without changing markup

## Dependencies

- T001 before everything. T002, T003, T004 in order; T005 and T006 after T004; T007 and T008 after T001.
- US1 (T009 to T015) needs T006 and T007. Its batches run in order, since they share one file.
- US2 (T016, T017) needs T006 and T007, and runs after T015 so it is the only writer of the file.
- US3 (T018, T019) needs only T004 and can run alongside US1.
- Polish (T020 to T023) after every story.

## Parallel example

T007 with T008; T018 with T019, both alongside any batch of US1.

## Implementation strategy

US1 and US2 ship together: a Russian file without three wordings fails `CountedPhraseTest`, and
`monica:localize --check` fails on any empty value, so neither is mergeable alone. US3 adds tests
only and can land in the same pull request.
