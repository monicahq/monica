# Tasks: Contact sort names follow each language

**Input**: Design documents from `specs/145-contact-sort-name-languages/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: Included. Constitution principle III makes tests a merge condition. Follow
`.claude/rules/testing.md`: `declare(strict_types=1);`, `#[Test]`, snake_case `it_` names, named
arguments, no comment. Names from The Office, except where a language needs its own letters.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1 to US4)

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: None needed. No dependency, no schema change, no new folder.

- [X] T001 Read before writing code: `.claude/rules/code-style.md`, `.claude/rules/testing.md`, the skills `models`, `actions`, `migrations`, `config`, `tests`, `changelog`, `api-docs-writer`, `definition-of-done` under `.claude/skills/`, and `specs/145-contact-sort-name-languages/research.md` in full

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The helper every story extends, and the split between what a contact files under and what it is searched by.

- [X] T002 Add `public static function lower(string $text, ?string $locale = null): string` to `app/Helpers/LocaleCollation.php`, returning `(string) self::caser($locale ?? App::getLocale(), 'Lower')->transliterate($text)`, and make `foldForSearch()` call it for its `$lowered` line so the two never differ
- [X] T003 Create `app/Helpers/NameFiling.php` with `php artisan make:class Helpers/NameFiling --no-interaction`: `public static function sortName(string $locale, ?string $family, ?string $given, ?string $additional, string $fallback = ''): string`. For now it joins the non empty parts as family, given, additional with one space, uses `$fallback` when that is empty, then returns `mb_substr(LocaleCollation::lower(mb_trim($text), $locale), 0, 511)`. No class docblock, no inline comment
- [X] T004 In `app/Models/Contact.php`, replace the private `buildSortName()` with two public methods. `sortNameFor(string $locale): string` returns `NameFiling::sortName(locale: $locale, family: $this->last_name, given: $this->first_name, additional: $this->middle_name, fallback: (string) ($this->formatted_name ?? $this->nickname ?? $this->maiden_name))`. `searchKeyFor(string $locale): string` returns `LocaleCollation::foldForSearch(<the exact text buildSortName() returns today, mb_strtolower included>, $locale)`. In `saving`, set `sort_name` from the first, `sort_key` from that sort name and `search_key` from the second. Move the sentence of the old docblock to `sortNameFor()`. `it_builds_the_keys_for_the_language_of_the_account_and_not_the_request` in `tests/Unit/Models/ContactTest.php` (line 317) folds the sort name to get the search key, which stops being true for Irmak: make it assert `sort_name` is `ırmak` and `search_key` equals `searchKeyFor('tr_TR')`
- [X] T005 [P] Create `tests/Unit/Helpers/NameFilingTest.php`: family, given, additional order; the fallback when no part is given; an empty string when nothing is given; a name longer than 511 characters is cut
- [X] T006 [P] Add to `tests/Unit/Models/ContactTest.php`: for an English account, Jim Halpert has sort name `halpert jim` and the search key it has today (`LocaleCollation::foldForSearch('halpert jim', 'en')`)
- [X] T007 Run `php artisan test --compact tests/Unit/Helpers tests/Unit/Models/ContactTest.php tests/Unit/Actions/RebuildOrderingKeysTest.php`; every test that passed before must pass, the one T004 rewrote included

**Checkpoint**: Foundation ready. English accounts file and search exactly as before.

---

## Phase 3: User Story 1: A name files under the part its reader looks for (Priority: P1) 🎯 MVP

**Independent Test**: an Icelandic account holding Jón Stefánsson, Björk Guðmundsdóttir and Anna Jónsdóttir orders them Anna, Björk, Jón by `sort_key`.

### Tests for User Story 1

- [X] T008 [P] [US1] Add to `tests/Unit/Helpers/NameFilingTest.php` a `#[DataProvider]` keyed by locale holding five names (family, given, additional) and the order expected, one entry for every code of `AvailableLocales::codes()`, each name sorted by `LocaleCollation::sortKey(NameFiling::sortName(...), $locale)`. Write each expected order from the Files under cell of that locale's row of research R2 before running the code, never from its output, and pick names whose order changes when given and family are swapped, so the entry proves the rule and not the alphabet (which spec 003 already tests). Use no family name opening with a particle and no reading: T016 and T017 prove those two columns, and with them this provider is the record SC-001 refers to; the names of the three acceptance scenarios of spec US1 are the entries for `is_IS`, `vi_VN` and `en`, and the `tr_TR` entry holds a name opening with `I` and one opening with `İ`. Add one test asserting the provider's keys equal `AvailableLocales::codes()`, so a new locale fails until it has an entry. Add one test over the same provider for SC-002: for every locale, a `Collator` of that locale set to `Collator::SECONDARY` compares each part of each name with `LocaleCollation::lower()` of it as equal, so lowering changes the case of a letter and never the letter (`mb_strtolower('Irmak')` fails this for `tr_TR`)
- [X] T009 [P] [US1] Add to `tests/Unit/Models/ContactTest.php`: for an account whose user reads `is_IS`, Björk Guðmundsdóttir has a sort name starting with `björk`; for `vi_VN`, family Nguyễn, additional Văn, given An starts with `an`; the `search_key` of the Icelandic contact equals `LocaleCollation::foldForSearch('guðmundsdóttir björk', 'is_IS')`, the text the old rule folds, written as that literal and not through `searchKeyFor()` (FR-008)

### Implementation for User Story 1

- [X] T010 [US1] In `config/localization.php`, add `'name_filing' => ['files_under' => 'given']` to `is_IS`, `si_LK`, `ta_IN` and `vi_VN`, and add one paragraph to the comment block above `locales` saying what `name_filing` holds (`files_under`, `skipped_particles`, `phonetic_scripts`) and that a locale without it files family name first
- [X] T011 [US1] In `app/Helpers/NameFiling.php`, read `config("localization.locales.{$locale}.name_filing", [])` and, when `files_under` is `given`, join as given, additional, family; anything else keeps family, given, additional
- [X] T012 [US1] Run `php artisan test --compact tests/Unit/Helpers/NameFilingTest.php tests/Unit/Models/ContactTest.php`

**Checkpoint**: New and edited contacts file under the right part. Existing rows wait for US4.

---

## Phase 4: User Story 2: A capital letter files with its small letter (Priority: P1)

**Independent Test**: a Turkish account holding the family names İpek, ırmak and Irmak orders Irmak and ırmak together, before İpek.

- [X] T013 [P] [US2] Add to `tests/Unit/Helpers/LocaleCollationTest.php`: `lower('Irmak', 'tr_TR')` is `ırmak`, `lower('İpek', 'tr_TR')` is `ipek`, `lower('Irmak', 'en')` is `irmak`
- [X] T014 [P] [US2] Add to `tests/Unit/Models/ContactTest.php`: for an account reading `tr_TR`, three contacts with the family names İpek, ırmak and Irmak, read back `orderBy('sort_key')`, give Irmak and ırmak in the first two places and İpek last
- [X] T015 [US2] Run both test files. The implementation is T002 and T003; if T014 fails, the fault is there, not in a new rule

**Checkpoint**: SC-002 holds: T008 proves it for every language, T014 for the Turkish order.

---

## Phase 5: User Story 3: Readings and particles follow the reader's language (Priority: P2)

**Independent Test**: for `nl_NL`, van Gogh and van der Berg have sort names starting `gogh` and `berg`; for `ja_JP`, 鈴木 read すずき sorts before 山田 read やまだ.

### Tests for User Story 3

- [X] T016 [P] [US3] Add to `tests/Unit/Helpers/NameFilingTest.php`: for `nl_NL`, `van Gogh`, `Vincent` gives `gogh vincent van` and `van der Berg`, `Jan` gives `berg jan van der`; `Vandenberg` and a family name that is only `Van` are left whole; `Van Der Berg` in capitals is treated the same; for `en` nothing is skipped; for `es_ES`, `de la Cruz` gives `cruz` first and `La Fuente` keeps `la fuente`; for `fr_FR`, `de Gaulle` gives `gaulle` first, `Le Pen` keeps `le pen`, `d'Aubigné` and `d’Aubigné` give `aubigné` first and `Dupont` is left whole; for `de_DE`, `von der Leyen` gives `leyen` first and `Zur Linde` keeps `zur linde`; for `pt_PT` and `pt_BR`, `dos Santos` gives `santos` first; for `en`, `D'Arcy` is left whole. With T008, these cases are the particle evidence of SC-001
- [X] T017 [P] [US3] Add to `tests/Unit/Models/ContactTest.php`: for `ja_JP`, a contact displayed 山田 太郎 holding a phonetic representation with script `Hira`, family やまだ, given たろう has sort name `やまだ たろう`; the same contact with a phonetic representation in `Latn` only, or with none, files under 山田; the same contact in an `en` account files under 山田 whatever it holds; of two `Hira` readings the lower `position` decides; a `Hira` reading holding only the formatted name `やまだ たろう`, and no part, files the contact under `やまだ たろう` too. This test is the Japanese evidence of SC-001, since the provider of T008 holds no reading
- [X] T018 [P] [US3] Add to `tests/Unit/Models/ContactNameRepresentationTest.php`: in a `ja_JP` account, saving a `Hira` phonetic representation that is not the displayed one changes the contact's `sort_name` and `sort_key`, and deleting it restores them; neither changes `search_key`; in an `en` account neither writes the contact
- [X] T019 [P] [US3] Add to `tests/Unit/Actions/UpdateContactNameRepresentationsTest.php`: in a `ja_JP` account, adding a `Hira` phonetic representation through the action, on a contact whose `nameRepresentations` relation was loaded beforehand, files the contact under the reading, and removing it through the action files it under 山田 again. If this fails with T022 and T023 done, the loaded relation is stale: make `sortNameFor()` query the reading and keep the eager load for `RebuildOrderingKeys` only

### Implementation for User Story 3

- [X] T020 [US3] In `config/localization.php`, add `skipped_particles` to `nl_NL`, `de_DE`, `fr_FR`, `es_ES`, `pt_PT` and `pt_BR`, and `'phonetic_scripts' => ['Hira', 'Kana']` to `ja_JP`, with exactly the lists of `specs/145-contact-sort-name-languages/research.md` R2 (the Dutch list holds `'t` and `'s`, and the French and Portuguese lists hold `d'`, each with both the straight and the typographic apostrophe, written as `"\u{2019}t"` and `"d\u{2019}"`)
- [X] T021 [US3] In `app/Helpers/NameFiling.php`, before joining: while the lowercased family name starts with a listed phrase followed by a space, or with a listed phrase ending in an apostrophe (the elided `d'`, which no space follows), remove the longest such phrase and keep it; stop when none matches or nothing would remain. Join the removed phrases after the last part, in the order removed. Add `public static function phoneticScripts(string $locale): array` with `@return list<string>`, returning the rule's `phonetic_scripts` or `[]`
- [X] T022 [US3] In `app/Models/Contact.php`, make `sortNameFor()` use a reading when there is one: when `NameFiling::phoneticScripts($locale)` is empty or the contact does not exist yet, none, with no query; otherwise the first of `$this->nameRepresentations` whose `kind` is `ContactNameKindEnum::Phonetic` and whose `script_code` is listed. With a reading, pass its `family_name`, `given_name` and `additional_name` in place of the contact's, and its `formatted_name` as the fallback in place of the contact's, since a representation may hold a formatted name and no part
- [X] T023 [US3] In `app/Models/ContactNameRepresentation.php`, make `keepTheContactInStep()` call `$contact->unsetRelation('nameRepresentations')` first, for every representation, then `$contact->save()` for one that is not the displayed one when `NameFiling::phoneticScripts($contact->account->locale())` is not empty, before it resends the search; do the same in the `deleted` hook. Accounts whose language lists no reading script get no extra write. Update the class hook docblock sentence to say the contact is refiled
- [X] T024 [US3] Run the four test files of T016 to T019

**Checkpoint**: The three parts of a filing rule all work for new and edited contacts.

---

## Phase 6: User Story 4: Nothing is filed on a mixture of two rules (Priority: P2)

**Independent Test**: changing an account from `en` to `is_IS` changes the `sort_name` of every contact in that one save; running the migration on rows holding the old sort name gives the new one.

### Tests for User Story 4

- [X] T025 [P] [US4] Add to `tests/Unit/Actions/RebuildOrderingKeysTest.php`: rebuilding an `en` account for `is_IS` gives each contact the `sort_name` that `sortNameFor('is_IS')` returns, the `sort_key` of that sort name and the `search_key` that `searchKeyFor('is_IS')` returns, so a rebuilt contact equals a saved one; a `ja_JP` rebuild files a contact under its `Hira` reading; an entity keeps its `sort_name`
- [X] T026 [P] [US4] Extend `it_leaves_the_language_and_the_keys_alone_when_the_rebuild_cannot_finish` in `tests/Unit/Actions/UpdatePreferencesTest.php` to assert the `sort_name` of the contact is unchanged too
- [X] T027 [P] [US4] Create `tests/Unit/Migrations/BackfillContactSortNamesTest.php`, shaped like `BackfillContactNameRepresentationsTest.php`: an `is_IS` account whose contact row is forced back to `sort_name` `guðmundsdóttir björk` with `DB::table('contacts')->update()` is refiled to start with `björk`; an `en` contact is left with the same `sort_name`, `sort_key` and `search_key`; no name column of either changes; a soft deleted contact is refiled too

### Implementation for User Story 4

- [X] T028 [US4] In `app/Actions/RebuildOrderingKeys.php`, give contacts their own read: select `id`, `last_name`, `first_name`, `middle_name`, `formatted_name`, `nickname`, `maiden_name`, eager load `nameRepresentations` only when `NameFiling::phoneticScripts($this->locale)` is not empty, and write `sort_name` from `sortNameFor($this->locale)`, `sort_key` from that sort name and `search_key` from `searchKeyFor($this->locale)` in the same batched `update`, adding a third `case id` for `sort_name`. Entities keep reading their stored `sort_name`. In `tests/Unit/Actions/RebuildOrderingKeysTest.php`, the assertions at lines 31, 45 and 110 fold the sort name to get the search key: make them compare with `searchKeyFor()` of the locale rebuilt for. Update the class docblock to say the sort name of a contact is rebuilt with its keys
- [X] T029 [US4] Generate `php artisan make:migration backfill_contact_sort_names --no-interaction`, rename it to `database/migrations/2026_11_21_000000_backfill_contact_sort_names.php`, and write `up()` only: `Account::query()->chunkById(100, ...)`, and for each account `DB::transaction(fn () => (new RebuildOrderingKeys(account: $account, locale: $account->locale()))->execute())`
- [X] T030 [US4] Run the three test files of T025 to T027

**Checkpoint**: Existing data follows the rule. All four stories hold.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T031 Replace `->orderBy('sort_name')` with `->orderBy('sort_key')` on the six contact queries (FR-007): `app/ViewModels/Vaults/RelationshipFormViewModel.php:112`, `FieldChoicesViewModel.php:42` (the `Contact` query only, line 54 is entities), `GroupMemberCandidatesViewModel.php:34`, `PossessionLoanContactCandidatesViewModel.php:31`, `app/Http/Controllers/App/Vaults/ContactLifeEventCandidateController.php:29` and `VaultLifeEventCandidateController.php:31`
- [X] T032 For each of the six, add one test to its test file (found with `grep -rl <ClassName> tests`; where none exists create `tests/Unit/ViewModels/Vaults/<ClassName>Test.php` holding that test): in a French account, Dwight Schrute and Erin Élan come back Élan, Schrute, which the database's own order of `sort_name` gets wrong
- [X] T033 [P] In `resources/docs/api/06-contacts.php`, add to the description of the `sort` parameter (line 175) that `name` follows the filing rule of the account's language; correct the `name` attribute (line 226), which says the name shown is what the lists sort on, to say they sort on a text built from the parts of the name by that rule; make the sentence on the rebuild (line 507) name the filing rule; and add an entry to `resources/docs/api-changelog/2026-11-21.php`, following the `api-docs-writer` skill
- [X] T034 [P] Write the What's new entry with the `changelog` skill: names file the way each language files them, naming given name first, particles, Turkish capitals and Japanese readings, and that the list reorders once at upgrade. Its title, area and body are translation keys: run `php artisan monica:localize` and have what it reports missing translated in every locale by Sonnet subagents, as step 4 of that skill and the `translations` skill say, before T037
- [X] T035 [P] Search the product documentation (`grep -rn -i "alphabet\|ordered by\|sort" docs/portal/en`, starting with `3-people/4-find-people.md` and `10-your-account-and-security/15-preferences.md`) for a page describing the people list order and add one sentence, following the `writer` skill, saying the order follows the interface language; add nothing where no page describes it. The pages exist in English only, so nothing is translated
- [X] T036 [P] Amend `spec/models/019-contact-done.md`, which this change would otherwise contradict: lines 32 and 98 say the sort name reads family name first whatever the contact, so say it follows the filing rule of the account's language (the part filed first, the particles skipped, a reading where the language orders by one) and is lowercased with that language; line 34 says `search_key` is the sort name folded, so say it is folded from the family first text, as before. Add one sentence to `spec/models/002-user-done.md` line 44 saying the name order it speaks of is the order a name is displayed in, and that filing follows the interface language (FR-003)
- [X] T037 Run `vendor/bin/pint --dirty --format agent`, PHPStan and `php artisan monica:localize --check`
- [X] T038 Run the `rules-reviewer` agent over the change and check its evidence before acting on it
- [X] T039 Run `PAO_DISABLE=1 php artisan test --parallel`; a `McpOAuthTest` failure on the key file mode is known and unrelated
- [ ] T040 Walk `specs/145-contact-sort-name-languages/quickstart.md` by hand, including the upgrade on a copy
- [X] T041 Run the `definition-of-done` skill. The pull request description must carry what the plan and the research leave to it: the migration calls an action where the migrations skill asks for the query builder (research R7)
- [X] T042 Record the feature in `docs/FEATURES.md` with the `feature-documentator` skill (always the last task)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: none
- **Foundational (Phase 2)**: after Setup; blocks every story
- **US1 (Phase 3)**: after Phase 2
- **US2 (Phase 4)**: after US1, since T008 carries its proof for every language and T014 edits the test file of T009; it adds tests, the implementation is T002 and T003
- **US3 (Phase 5)**: after US1, since T021 edits the join T011 wrote
- **US4 (Phase 6)**: after US3, since T028 and T029 must carry every part of the rule to existing rows
- **Polish (Phase 7)**: after the stories; T031 and T032 depend on nothing but Phase 2

### Parallel Opportunities

- T005 and T006; T008 and T009; T013 and T014; T016 to T019; T025, T026 and T027 (different test files)
- T033 to T036 in parallel

---

## Notes

- One commit and one pull request for the whole feature, after `definition-of-done`: the stories share `NameFiling`, `Contact` and the config file, and the migration must ship with the rule it applies
- No schema change. One data migration, safe to run twice
- `contact_name_representations.sort_name` is left as it is (research R6)
- Thai (`th_TH`) follows CLDR and files family name first, by the owner's decision
- After the rules review (T038): the Vietnamese case of T009, the model test of T014 and the deleted contact case of T027 were removed, since NameFilingTest, LocaleCollationTest and RebuildOrderingKeysTest own those rules; the case test of T008 moved to LocaleCollationTest
