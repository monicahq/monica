---

description: "Task list for 025-native-locale-translations"
---

# Tasks: Native quality translations for twenty two new locales

**Input**: Design documents from `/specs/025-native-locale-translations/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/](contracts/), [quickstart.md](quickstart.md)

**Tests**: included. Principle III makes a test a condition of merge, so the test is part of the task rather than an optional phase.

**Organization**: by user story. The unit of delivery is one locale per pull request.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: different files, no dependency on an unfinished task
- Every locale task edits `config/monica.php`, so no locale task is ever `[P]`

---

## Phase 1: Setup

**Purpose**: remove the one statement the codebase already contradicts.

- [X] T001 Drop the locale count from the Principle V paragraph in `.specify/memory/constitution.md:110`, as its own PATCH amendment pull request

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: three blockers plus the shared groundwork. No locale can be registered until this phase is complete.

**⚠️ CRITICAL**: `monica:localize --check` fails on any registered locale that is not fully translated, so nothing here may register a locale.

- [X] T002 Widen `locale` to `string(12)` in `database/migrations/0001_01_01_000000_create_users_table.php`, `database/migrations/2026_08_09_000000_create_templates_table.php`, `database/migrations/2026_08_09_000002_create_sections_table.php`, `database/migrations/2026_08_12_000001_create_marketplace_listings_table.php` and `database/migrations/2026_09_02_000003_create_notifications_table.php`
- [X] T003 Widen `LOCALE` to `/^[a-z]{2,3}(_[A-Z]{2})?$/` and raise the length cap from 5 to 12 in `app/Helpers/StructureFileReader.php:47,809`
- [X] T004 Cover a `fil_PH` locale reading in `tests/Unit/Helpers/StructureFileReaderTest.php`
- [X] T005 [P] Replace the `sv_SE` sentinel with `kl_GL` in `tests/Feature/Controllers/App/Auth/LocaleControllerTest.php:44`
- [X] T006 [P] Replace the `sv_SE` and `zh-Hans-TW` expectations with `kl_GL` in `tests/Unit/Helpers/LocaleResolutionTest.php:84,105,129,132,135`
- [X] T007 Sort the locale list by label through `sortByCollator` in `app/ViewModels/Auth/GuestViewModel.php:18`
- [X] T008 Sort the locale list by label through `sortByCollator` in `app/ViewModels/Settings/Account/Preferences/PreferencesViewModel.php:20`
- [X] T009 Update the first element assertion to follow the sorted order in `tests/Unit/ViewModels/Auth/RegisterViewModelTest.php:33`
- [X] T010 Give the popup a scroll container and a maximum height in `resources/views/components/language-picker.blade.php`
- [X] T011 Cover the picker at thirty one locales in `tests/Feature/Views/Components/LanguagePickerTest.php`
- [X] T012 Replace the comparator that ignores its second argument in `app/Console/Commands/LocalizeCommand.php:766`
- [X] T013 Cover the locale ordering of the report in `tests/Unit/Console/Commands/LocalizeCommandTest.php`
- [X] T014 Run `php artisan lang:publish` and commit `lang/en/validation.php`, `lang/en/auth.php`, `lang/en/passwords.php` and `lang/en/pagination.php`
- [X] T015 [P] Replace the hardcoded wording list with a locale to count map in `tests/Unit/Translation/CountedPhraseTest.php:109`
- [X] T016 [P] Guard the unguarded `file_get_contents` against a missing file in `tests/Unit/Translation/CountedPhraseTest.php:142`
- [X] T017 Read the locale list from `config('monica.locales')` instead of spelling it out in `resources/docs/api/03-profile.php:37`
- [X] T018 Cover the generated locale list in `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php`
- [X] T019 Write the glossary table, one row per product noun and one column per locale, in `.claude/rules/translations.md`
- [X] T020 Write the review record table naming which locales a native speaker has read, in `.claude/rules/translations.md`
- [X] T021 [P] Drop the stale locale counts in `.claude/rules/code-style.md:290` and `.claude/skills/add-locale/SKILL.md:32`

**Checkpoint**: the schema takes a six character code, the sentinels have moved, the picker holds thirty one entries, and `lang/en/` exists.

---

## Phase 3: User Story 1 - Read the product in my own language (Priority: P1) 🎯 MVP

**Goal**: twenty two new locales, each complete on the day it is registered.

**Independent Test**: sign in with the locale selected and walk the signed in screens, the credentials screens and one email without seeing an English sentence.

Each task registers the locale in `config/monica.php`, writes `lang/<locale>.json` through `php artisan monica:localize`, translates the four files under `lang/<locale>/`, and adds the locale to the review record. The procedure is [quickstart.md](quickstart.md), the config shape is [contracts/locale-registration.md](contracts/locale-registration.md), the wording count is in [research.md](research.md).

- [X] T022 [US1] Add Japanese in `config/monica.php`, `lang/ja_JP.json` and `lang/ja_JP/`
- [X] T023 [US1] Add Simplified Chinese in `config/monica.php`, `lang/zh_CN.json` and `lang/zh_CN/`
- [X] T024 [US1] Add Korean in `config/monica.php`, `lang/ko_KR.json` and `lang/ko_KR/`
- [X] T025 [US1] Add Polish, three wordings, in `config/monica.php`, `lang/pl_PL.json` and `lang/pl_PL/`
- [X] T026 [US1] Add Swedish in `config/monica.php`, `lang/sv_SE.json` and `lang/sv_SE/`
- [X] T027 [US1] Add Danish in `config/monica.php`, `lang/da_DK.json` and `lang/da_DK/`
- [X] T028 [US1] Add Norwegian Bokmål in `config/monica.php`, `lang/nb_NO.json` and `lang/nb_NO/`
- [ ] T029 [US1] Add Finnish in `config/monica.php`, `lang/fi_FI.json` and `lang/fi_FI/`
- [ ] T030 [US1] Add Icelandic in `config/monica.php`, `lang/is_IS.json` and `lang/is_IS/`
- [ ] T031 [US1] Add Hungarian in `config/monica.php`, `lang/hu_HU.json` and `lang/hu_HU/`
- [ ] T032 [US1] Add Greek in `config/monica.php`, `lang/el_GR.json` and `lang/el_GR/`
- [ ] T033 [US1] Add Catalan in `config/monica.php`, `lang/ca_ES.json` and `lang/ca_ES/`
- [ ] T034 [US1] Add Lithuanian, three wordings, in `config/monica.php`, `lang/lt_LT.json` and `lang/lt_LT/`
- [ ] T035 [US1] Add Irish, three wordings, in `config/monica.php`, `lang/ga_IE.json` and `lang/ga_IE/`
- [ ] T036 [US1] Add Vietnamese in `config/monica.php`, `lang/vi_VN.json` and `lang/vi_VN/`
- [ ] T037 [US1] Add Thai in `config/monica.php`, `lang/th_TH.json` and `lang/th_TH/`
- [ ] T038 [US1] Add Filipino in `config/monica.php`, `lang/fil_PH.json` and `lang/fil_PH/`
- [ ] T039 [US1] Add Hindi in `config/monica.php`, `lang/hi_IN.json` and `lang/hi_IN/`
- [ ] T040 [US1] Add Tamil in `config/monica.php`, `lang/ta_IN.json` and `lang/ta_IN/`
- [ ] T041 [US1] Add Bengali in `config/monica.php`, `lang/bn_BD.json` and `lang/bn_BD/`
- [ ] T042 [US1] Add Sinhala in `config/monica.php`, `lang/si_LK.json` and `lang/si_LK/`
- [ ] T043 [US1] Add Uzbek in `config/monica.php`, `lang/uz_UZ.json` and `lang/uz_UZ/`

**Checkpoint**: every registered locale reports nothing missing, and each one shipped on the day it was written.

---

## Phase 4: User Story 3 - Numbers, dates and counts read correctly (Priority: P2)

**Goal**: a count, a date and a number read the way the language writes them, at every number.

**Independent Test**: render every counted phrase in one locale at 0, 1, 2, 5 and 21 and confirm each reads correctly.

- [ ] T044 [US3] Extend the counted phrase sweep from 0, 1, 2, 1000 to 0, 1, 2, 5, 21 in `tests/Unit/Translation/CountedPhraseTest.php:115`

The wording count, `date_format` and `number_format` are part of each registration in Phase 3, so this story adds only the sweep that proves it.

---

## Phase 5: User Story 2 - Recognise my own language (Priority: P1)

**Goal**: the nine shipped locales read as a native speaker would write them, and gain the framework strings they never had.

**Independent Test**: a native reviewer reads a sample of a hundred strings for one locale and records how many they would change.

- [ ] T045 [US2] Review `lang/fr_FR.json` and add `lang/fr_FR/`
- [ ] T046 [US2] Review `lang/de_DE.json` and add `lang/de_DE/`
- [ ] T047 [US2] Review `lang/es_ES.json` and add `lang/es_ES/`
- [ ] T048 [US2] Review `lang/it_IT.json` and add `lang/it_IT/`
- [ ] T049 [US2] Review `lang/nl_NL.json` and add `lang/nl_NL/`
- [ ] T050 [US2] Review `lang/pt_PT.json` and add `lang/pt_PT/`
- [ ] T051 [US2] Review `lang/pt_BR.json` and add `lang/pt_BR/`
- [ ] T052 [US2] Review `lang/tr_TR.json` and add `lang/tr_TR/`
- [ ] T053 [US2] Check the British spelling of the source strings in `lang/en.json`, which are the keys in the code rather than a translation

---

## Phase 6: User Story 4 - The language's own punctuation is enforced (Priority: P3)

**Goal**: a language with written punctuation rules has them declared, so the check refuses a value that breaks them.

**Independent Test**: declare the rules for one locale, break one string on purpose, and confirm the check names the value and the rule.

- [ ] T054 [US4] Declare `typography` for each locale a native reviewer has confirmed, in `config/monica.php`
- [ ] T055 [US4] Add those locales to the quotation map in `tests/Unit/Translation/FrenchTypographyTest.php:59` and write their sections in `.claude/rules/translations.md`

Both wait on a reviewer rather than on a task. A locale that declares nothing is not checked, which the spec accepts.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [ ] T056 Assert every configured locale holds the four framework files with the keys `lang/en/` holds, in `tests/Unit/Translation/FrameworkStringsTest.php`
- [ ] T057 Run the suite on SQLite, MySQL and PostgreSQL
- [ ] T058 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse`
- [ ] T059 Walk [quickstart.md](quickstart.md) against three locales, one per wording count

---

## Dependencies & Execution Order

### Phase Dependencies

- Phase 1 has no dependency and is its own pull request.
- Phase 2 blocks every user story. Nothing in Phases 3 to 7 may start before it merges.
- Phases 3, 4 and 5 are independent of each other once Phase 2 is done.
- Phase 6 depends on a native reviewer existing, not on a task.
- Phase 7 depends on Phases 3 and 5 being complete.

### Task Dependencies

- T002 and T003 block T038, because `fil_PH` does not fit the schema until they land.
- T005 blocks T026, because `sv_SE` is the sentinel for an unshipped locale until it moves.
- T006 blocks T023, because `zh-Hans-TW` is expected to resolve to nothing until it moves.
- T007 and T008 block T009. T012 blocks T013. T017 blocks T018.
- T014 blocks every task in Phases 3 and 5, which each add a `lang/<locale>/` directory against it.
- T056 depends on every task in Phases 3 and 5.

### Parallel Opportunities

- Within Phase 2: T005 with T006, and T015 with T016, and T021 with either pair.
- Phases 3 and 5 are one pull request per task and can be worked in any order, but they all edit
  `config/monica.php` or a shared review table, so they merge one at a time rather than in parallel.

---

## Implementation Strategy

### MVP

Phase 1, then Phase 2, then T022 alone. Japanese proves the whole pipeline: a registration, a
complete JSON file, four framework files, a one wording plural system and a flag drawn with
gradients. Every task after it is the same work with different words.

### Incremental delivery

Each locale ships the day it is finished, marked in the review record as reviewed or as awaiting a
native reader. No locale waits for another, and a locale nobody reviews still reaches the people who
read it.

### Order of Phase 3

Japanese, Chinese and Korean first, because one wording and no punctuation rules to declare make them
the cheapest. Then Polish, Lithuanian and Irish, the three wording languages, once the pattern is
established. The Indic scripts and Sinhala last, where the script and the plural rules are least
familiar.
