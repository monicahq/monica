# Tasks: Locale Aware Sorting

**Input**: Design documents from `/specs/003-locale-aware-sorting/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/internal.md](contracts/internal.md)

**Tests**: Included and not optional. The skill defaults them off, but constitution Principle III
is NON-NEGOTIABLE and `CLAUDE.md` requires every change to be covered, so each new class gets a
test in the same phase that creates it.

**Organization**: Grouped by user story so each can be implemented, tested and delivered on its
own.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an incomplete task)
- **[Story]**: which user story the task serves

## Resolved before starting

The plan left one question here. There are no git tags, no `CHANGELOG.md` and no version field,
so the project is pre-production, and its convention is to edit existing migrations until it is
not. The two columns therefore go into the existing create migrations and there is no backfill
migration. Anyone holding local data runs `php artisan migrate:fresh`.

---

## Phase 1: Setup

**Purpose**: Declare the new dependency so an installation lacking it fails loudly (FR-013).

- [X] T001 Add `"ext-intl": "*"` to the `require` block of `composer.json`, so an installation without it is refused at install or upgrade rather than serving lists in a wrong order
- [X] T002 [P] Note the `intl` requirement in `README.md`, including that the `php:8.4` base images need `docker-php-ext-install intl`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The single ICU helper every story calls, the two derived columns, and the rebuild.
No user story can begin until this phase is done.

**CRITICAL**: T003 to T005 merge the staged `CollectionSorter` into `LocaleCollation` and delete
it. That clears both constitution gates at once, the forbidden `final` and the missing test,
because the offending file stops existing. They come first because the branch cannot merge with
those outstanding.

- [X] T003 Create `app/Helpers/LocaleCollation.php` as the one place that asks ICU anything, per [contracts/internal.md](contracts/internal.md). Move `sortByCollator()` and the per-locale collator cache across from the staged `app/Helpers/CollectionSorter.php` unchanged in behaviour, and add `sortKey()`, `foldForSearch()` and `headingLetter()`. `getCollator()` is the shared cache all of them read from and the only place `Collator::FRENCH_COLLATION` is set. No `final`, which Principle II forbids, and no character list anywhere in the file
- [X] T004 Delete `app/Helpers/CollectionSorter.php` and point the `sortByCollator` macro in `app/Providers/AppServiceProvider.php` at `LocaleCollation::sortByCollator()`. The macro keeps its name, so no caller changes
- [X] T005 Create `tests/Unit/Helpers/LocaleCollationTest.php` covering both halves in one file. The absorbed half: sorting a collection by callable and by string key, the descending path, and a French collection ordering `cote côte coté côté` rather than byte order, which is the coverage the staged helper never had. The new half: `foldForSearch` giving `elodie`, `muller`, `ñuñez` and `ışık` for the four contract cases; `headingLetter('irmak', 'tr_TR')` being `İ` and `headingLetter('ırmak', 'tr_TR')` being `I`; a nonsense locale falling back without throwing; a non-letter start giving `#`
- [X] T006 [P] Confirm no file outside `app/Helpers/LocaleCollation.php` constructs a `Collator` or a `Transliterator`, with `grep -rn 'new Collator\|Transliterator::create' app/`. The contract makes it the only place, and a second collator elsewhere is a second cache and a second chance to forget the French attribute
- [X] T007 Add `sort_key` (binary, 2048) and `search_key` (string, 511) plus the `(vault_id, sort_key)` index to `database/migrations/2026_08_09_000007_create_contacts_table.php`, keeping the existing `(vault_id, sort_name)` index and writing no `down()`
- [X] T008 [P] Add the same two columns and index to `database/migrations/2026_08_11_000000_create_entities_table.php`
- [X] T009 Set both keys in the saving hook of `app/Models/Contact.php` where it already calls `buildSortName()`, built from the owning account's language via `Account::locale()` rather than from `App::getLocale()`, so a queued job writes what a request would. Both kept out of `$fillable`
- [X] T010 [P] Do the same in the saving hook of `app/Models/Entity.php`
- [X] T011 [P] Assert in `tests/Unit/Models/ContactTest.php` and `tests/Unit/Models/EntityTest.php` that both keys are derived on save, rebuilt when the name changes, built for the account's language and not the request's, and not mass assignable
- [X] T012 Create `app/Actions/RebuildOrderingKeys.php` taking promoted `readonly` `Account $account` and `string $locale`, with one public `execute(): void`, rebuilding every `sort_key` and `search_key` on `contacts` and `entities` in the account, soft deleted rows included. Idempotent, and it reads the locale from its input rather than from application state
- [X] T013 [P] Create `tests/Unit/Actions/RebuildOrderingKeysTest.php` asserting every key is rebuilt, that a new language writes a different key, that running twice changes nothing, that another account is untouched, and that a deleted row is rebuilt too
- [X] T014 Call `RebuildOrderingKeys` from `app/Actions/UpdatePreferences.php` inside a transaction wrapping the locale write and the rebuild together, so a failed rebuild leaves both the language and every key as they were (FR-011). `LogUserAction` is dispatched after the commit
- [X] T015 Extend `tests/Unit/Actions/UpdatePreferencesTest.php` to assert the keys are rebuilt for the new language, and that a failure inside the rebuild rolls the locale change back so no list is ordered on a mixture of two languages

**Checkpoint**: both gates clear, the helper is tested against all nine languages, the keys exist
and stay fresh, and a language change is all or nothing. User stories can start.

---

## Phase 3: User Story 1 - A list reads in my alphabet (Priority: P1) 🎯 MVP

**Goal**: The vault contact list orders by the reader's alphabet, pages without losing anybody,
and reverses exactly.

**Independent Test**: Set the interface language, load a vault holding that language's
characteristic letters, order by name, and read the order off the screen.

- [X] T016 [P] [US1] Extend `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php` with the two ordering cases from the spec: French gives Bernard, Hannon, Schrute; German gives Martin, Nilsson, Scott
- [X] T017 [P] [US1] Add a paging test to `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php`: a vault of more than 50 people, including several with identical names, paged to the end, yields every person exactly once
- [X] T018 [P] [US1] Add a reversal test to `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php` asserting the descending order is the exact reverse of the ascending one
- [X] T019 [US1] Point `SORT_COLUMNS` at `sort_key` in `app/ViewModels/Vaults/ContactsViewModel.php`, so `ordered()` orders on the key then `id` instead of on `sort_name`, keeping the existing null-ranking handling for the sorts that need it
- [X] T020 [US1] Confirm the list built by `ordered()` in `app/ViewModels/Vaults/ContactsViewModel.php` still pages off an index, by checking the query plan against the new `(vault_id, sort_key)` index. Verified: `SEARCH contacts USING INDEX contacts_vault_id_sort_key_index`, with no temporary sort for the ordering

**Checkpoint**: US1 is functional and testable on its own. This is the MVP.

---

## Phase 4: User Story 2 - The headings agree with what is under them (Priority: P2)

**Goal**: Every letter heading is the letter the reader's language files the names under it by.

**Independent Test**: Turkish interface, a vault holding Işık and Irmak, and check which heading
each sits under.

- [X] T021 [P] [US2] Add heading tests to `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php`: French files Élodie and Erin under one E; Turkish files Işık under `I` and Irmak under `İ`; a name starting with a digit, punctuation or an emoji lands under `#`
- [X] T022 [US2] Replace the `mb_strtoupper(mb_substr(...))` in `letterOf()` in `app/ViewModels/Vaults/ContactsViewModel.php` with `LocaleCollation::headingLetter()`. `mb_strtoupper` maps both Turkish `i` and `ı` to `I` and must not be used here
- [X] T023 [US2] Add a test to `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php` asserting no heading disagrees with its contents: for every group in a rendered list, every entry returns that group's letter (SC-004)

**Checkpoint**: US1 and US2 both work independently.

---

## Phase 5: User Story 3 - I find a name typed without its accents (Priority: P2)

**Goal**: Searching folds what the reader's language calls a minor difference, and only that.

**Independent Test**: Type the unaccented spelling in each language and read what comes back.

- [X] T024 [P] [US3] Add the fold matrix to `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php`: French `elodie` finds Élodie; German `muller` finds Müller; Spanish `nunez` does **not** find Ñuñez; Turkish `isik` does **not** find Işık. Name the language in each test method and state in the method name that the last two are expected, because they will otherwise be read as bugs
- [X] T025 [US3] Change `matching()` in `app/ViewModels/Vaults/ContactsViewModel.php` to match the folded term against `search_key`, keeping the existing `sort_name` and `nickname` matches so a reader who did type the accents still finds the entry, and keeping the match anywhere in the text rather than only at its start (FR-019)
- [X] T026 [US3] Fold the reader's search term through `LocaleCollation::foldForSearch()` in `searchTerm()` in `app/ViewModels/Vaults/ContactsViewModel.php`, replacing the bare `mb_strtolower`
- [X] T027 [P] [US3] Add the empty result copy through `__()` in `resources/views/app/vaults/contacts/index.blade.php` so a term matching nothing says so rather than rendering a blank region — **already present**: the view has a `noResults()` branch rendering "Nothing matches" through `__()`. No change needed, covered by a test instead
- [X] T028 [P] [US3] Add a feature test to `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` asserting a search matching nothing renders the empty message and returns 200

**Checkpoint**: US1, US2 and US3 all work independently.

---

## Phase 6: User Story 4 - The other alphabetical lists follow the same rule (Priority: P3)

**Goal**: The marketplace catalogue and the administrator filter order by the reader's alphabet
too. Both are held whole, so neither needs a stored key.

**Independent Test**: Open each with a French interface and check accented names sit with their
unaccented letter.

- [X] T029 [P] [US4] Add an ordering test to `tests/Unit/ViewModels/Marketplace/MarketplaceViewModelTest.php` asserting an accented listing name sits with its unaccented letter under a French interface
- [X] T030 [US4] Order the catalogue by name in PHP in `app/ViewModels/Marketplace/MarketplaceViewModel.php` via `pageOrderedByName()`, which reorders the query away, orders the names through the macro and pages the result. It gets no stored key because it is one catalogue read in nine languages; the other three sorts stay in SQL, being dates and counts

- [X] T031 [P] [US4] Add an ordering test to `tests/Unit/ViewModels/Admin/AdministratorLogsViewModelTest.php` for the same property
- [X] T032 [US4] Change `administrators()` in `app/ViewModels/Admin/AdministratorLogsViewModel.php` to order through `sortByCollator` instead of `orderBy('first_name')`
- [X] T033 [P] [US4] Add a test to `tests/Unit/ViewModels/Settings/Account/Templates/TemplatesViewModelTest.php` asserting a position ordered list is untouched, so nothing alphabetises a list the reader arranged (FR-010)

**Checkpoint**: every alphabetical list in the application follows the reader's language.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T034 Run `php artisan monica:localize` and translate everything it reports as missing in all nine `lang/*.json` files, never editing keys by hand
- [X] T035 [P] Run `vendor/bin/pint --dirty --format agent` over the changed PHP files
- [X] T036 Work through [quickstart.md](quickstart.md) scenarios 1 to 7 by hand, including forcing a failure inside the rebuild to confirm the rollback — **covered by automated tests, not by hand**: scenarios 1 to 5 by `ContactsViewModelTest` and `ContactControllerTest`, 6 by the rollback test in `UpdatePreferencesTest`, 7 by `LocaleCollationTest`. No manual browser pass was done
- [!] T037 Run the feature tests against each database connection the project supports and compare the resulting order for one fixture, since the ordering is bytewise and must be identical on all three (FR-020, SC-009) — **not run as written**: MySQL denies access and PostgreSQL has no driver on this machine, so only SQLite was exercised. The invariant the claim rests on is tested instead (`it_orders_the_list_exactly_as_the_collator_would`): the database's ordering of `sort_key` equals the collator's own ordering. Since the comparison is bytewise, any database comparing those bytes gives the same order, but that has not been observed on the other two
- [X] T038 Split the changes unrelated to sorting out of `app/Providers/AppServiceProvider.php` into their own branch: the rate limiters, `Model::shouldBeStrict`, `CarbonImmutable`, `DB::prohibitDestructiveCommands` and the password defaults. Only the `sortByCollator` macro belongs to this feature, and after T004 it delegates to `LocaleCollation`. `shouldBeStrict` can break existing code by throwing on lazy loading — **done early**: the staged provider imported `Illuminate\Cache\RateLimiter` instead of the facade, so nothing booted and no test could run. Removed changes saved to the session scratchpad
- [X] T039 Run the full suite with `php artisan test --compact` and confirm nothing else regressed

---

## Dependencies & Execution Order

- **Phase 1 (Setup)**: no dependencies.
- **Phase 2 (Foundational)**: depends on Phase 1. Blocks every user story. T003 and T004 are the
  gate failures and come before the rest.
- **Phase 3 to 6 (User stories)**: all depend on Phase 2. US1, US2 and US3 all touch
  `ContactsViewModel`, so they are sequential in practice despite being independently testable.
  US4 touches different files and can run in parallel with any of them.
- **Phase 7 (Polish)**: depends on the stories being done.

### Within Phase 2

- T003 before T004, T005, T006, T009, T010, T012.
- T004 after T003, since the macro cannot point at a class that does not exist yet.
- T007 and T008 before T009, T010, T011.
- T012 before T013, T014.
- T014 before T015.

### Parallel opportunities

- T002 alongside T001.
- T003, T004 and T005 are sequential: one file is created, the old one removed, then both halves
  tested. Only T006 in that group is parallel, being a read-only check.
- T008, T010, T011, T013 each parallel with their siblings.
- T016, T017 and T018 write to one file, so they parallelise as authoring work but must be
  committed together.
- The whole of Phase 6 runs parallel to Phases 3 to 5, being different files entirely.

## Parallel Example: Phase 2

```bash
# Different files, no shared dependency:
Task: "Add columns to database/migrations/2026_08_09_000007_create_contacts_table.php"
Task: "Add columns to database/migrations/2026_08_11_000000_create_entities_table.php"
```

## Implementation Strategy

### MVP

Phases 1, 2 and 3. That delivers the vault contact list reading in the reader's alphabet, which
is the whole of the P1 story, and it clears both constitution gates on the way. Stop there and
validate before going on.

### Incremental delivery

1. Phases 1 and 2, foundation ready.
2. Phase 3, the MVP. The contact list reads correctly.
3. Phase 4, the headings agree with it.
4. Phase 5, searching folds accents.
5. Phase 6, the remaining lists.
6. Phase 7, polish.

### The part most likely to go wrong

T009 and T010. The key must be built from the owning account's language, not from
`App::getLocale()`. Those agree during a normal request and diverge in a queued job or a console
command, and the symptom is a single contact ordered as though the reader spoke a different
language, which is invisible until somebody scrolls past a name.

### The part most likely to be reported as a bug

T024. In Spanish, typing `nunez` will not find Ñuñez, and in Turkish, typing `isik` will not
find Işık, because those languages treat them as different letters. This is FR-018 working. The
test names must say so, and the reasoning is in [research.md](research.md).
