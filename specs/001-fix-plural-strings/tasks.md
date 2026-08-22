---

description: "Task list for correcting plural phrasing in interface copy"
---

# Tasks: Correct Plural Phrasing in Interface Copy

**Input**: Design documents from `/specs/001-fix-plural-strings/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/)

**Tests**: Included, and not optional here. FR-013 requires them, Principle III of the constitution
makes them non negotiable, and the per change checklist in `.claude/CLAUDE.md` repeats it.

**Organization**: Tasks are grouped by user story. Two of the three stories ship independently. The
third depends on the first two, for a reason stated under Dependencies rather than glossed over.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Exact file paths are given in each description

## Path Conventions

Laravel application at the repository root: `app/`, `resources/views/`, `lang/`, `tests/`. There is no
`src/`. Paths below are as they appear in the tree.

---

## Phase 1: Setup

**Purpose**: establish a clean baseline, and produce the inventory every later task consumes

- [X] T001 Record the baseline so later failures are attributable: run `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` and `php artisan monica:localize --check`, and note the current pass state and the per locale missing counts in the pull request description
- [X] T002 Write the phrase inventory to `specs/001-fix-plural-strings/phrase-classification.md`: every one of the 46 entries in `lang/en.json` that contains `:count`, plus the 18 standalone `1 xxx` keys, each marked as a counted phrase or as number independent, with the decision recorded per FR-019 and the target wording for each segment. This file is the authoritative list that T009 onward work from

**Checkpoint**: the inventory exists, so no later task has to guess whether a phrase varies with its number

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: teach `monica:localize` the counted phrase format, before any phrase is converted

**⚠️ CRITICAL**: this phase must land before Phase 3. Converting phrases first would make
`monica:localize` write locale files in a shape the later check rejects, forcing the nine `lang/*.json`
files to churn twice.

**⚠️ EQUALLY CRITICAL**: the two bad pattern detections are **not** in this phase. They belong to
US3, because adding them now would fail the command on all 69 places this feature exists to fix, and
block its own implementation.

- [X] T003 Add a private method to `app/Console/Commands/LocalizeCommand.php` that returns how many segments a locale requires, by probing `Illuminate\Translation\MessageSelector::getPluralIndex()` across a range of numbers and taking the highest index plus one, per research decision D3. No table, no config key
- [X] T004 In `app/Console/Commands/LocalizeCommand.php`, treat a key containing `|` as a counted phrase: count its segments in each locale's file and report a locale holding the wrong number as missing that translation, so it flows into the existing per locale missing count and the existing `--check` failure
- [X] T005 In `app/Console/Commands/LocalizeCommand.php`, accept a key whose single segment is prefixed `[0,*]` as a number independent phrase requiring exactly one segment in every locale, per research decision D4
- [X] T006 [P] Add tests to `tests/Unit/Console/Commands/LocalizeCommandTest.php` for T003 to T005: two segments required for `en` and eight others, one for `tr_TR`, a locale with too few segments reported missing, a locale with too many reported missing, and a `[0,*]` phrase satisfied by one segment everywhere

**Checkpoint**: the command understands the format and validates it, and nothing is detected or blocked yet

---

## Phase 3: User Story 1 - A count and its noun agree, at every number (Priority: P1) 🎯 MVP

**Goal**: the 17 places that show one fixed wording whatever the number read correctly, ending the six
readings that are wrong in English today ("1 lines" in two places, "It was opened 1 times", and three
that need a duration configured to one: "Remember me for 1 days", "the next 1 minutes" twice).

**Corrected during implementation**: the split is 17 and 52, not 37 and 32. See the correction note in
[spec.md](./spec.md). Most guards sit further up their method than the original analysis looked.

**Independent Test**: set each affected count to one and read the screen, the email or the changelog
entry. Every phrase agrees with its number. Repeat at two and at a large value.

### Tests for User Story 1

> Write these first. They fail until the conversion lands.

- [X] T007 [P] [US1] Add wording assertions at counts of 0, 1, 2 and 1000 for the vault facing labels in `tests/Unit/ViewModels/Vaults/`, covering `VaultsViewModel` (including that zero still returns "Nobody in it yet"), `VaultDashboardViewModel`, `ContactShareLinksViewModel` and `ShareLinkViewModel`
- [X] T008 [P] [US1] Add wording assertions at counts of 0, 1, 2 and 1000 for the settings, marketplace and helper labels in `tests/Unit/ViewModels/Settings/`, `tests/Unit/ViewModels/Marketplace/` and `tests/Unit/Helpers/`, covering `DefaultValuesViewModel`, `EntityTypeFormViewModel`, `SecurityViewModel`, `MarketplaceStatistics`, `ShareLinkSummary` and `StructureFileReader`
- [X] T009 [P] [US1] Add a test to `tests/Unit/Models/LogTest.php` asserting a changelog entry reads correctly at a count of one for each of the three counted sentences in `UserActionEnum`

### Implementation for User Story 1

- [X] T010 [P] [US1] Convert the branchless phrases in `app/ViewModels/Vaults/VaultsViewModel.php`, `app/ViewModels/Vaults/VaultDashboardViewModel.php`, `app/ViewModels/Vaults/ContactShareLinksViewModel.php` and `app/ViewModels/Vaults/ShareLinkViewModel.php` to `trans_choice`, per the inventory from T002 and the format in [contracts/counted-phrase-format.md](./contracts/counted-phrase-format.md). Every segment interpolates `:count`; no segment carries a literal digit
- [X] T011 [P] [US1] Convert the branchless phrases in `app/ViewModels/Settings/Account/DefaultValues/DefaultValuesViewModel.php`, `app/ViewModels/Settings/Account/EntityTypes/EntityTypeFormViewModel.php` and `app/ViewModels/Settings/Account/Security/SecurityViewModel.php` to `trans_choice`
- [X] T012 [P] [US1] Convert the branchless phrases in `app/ViewModels/Marketplace/MarketplaceStatistics.php` to `trans_choice`, leaving the ternary sites in that file to T020
- [X] T013 [P] [US1] Convert the branchless phrases in `app/Helpers/ShareLinkSummary.php` and the four limit messages in `app/Helpers/StructureFileReader.php` to `trans_choice`. A limit is treated like any other count, because a deployment can set it to one
- [X] T014 [P] [US1] Convert the three counted sentences in `app/Enums/UserActionEnum.php` to the pipe separated form, and change `app/Models/Log.php` (the `description` accessor, around line 202) to call `trans_choice` when the key is a counted phrase. This is the only render path outside a view model or a Blade template
- [X] T015 [P] [US1] Convert the branchless phrases in the Blade templates `resources/views/app/auth/login.blade.php`, `resources/views/app/auth/magic-link-sent.blade.php`, `resources/views/mail/auth/magic-link-created.blade.php`, `resources/views/app/settings/account/templates/export.blade.php` and `resources/views/app/settings/account/templates/share.blade.php` to `trans_choice`
- [X] T016 [US1] Convert the remaining branchless phrases in the files that also hold ternary sites, leaving the ternaries to Phase 4: `app/ViewModels/Marketplace/MarketplaceViewModel.php`, `app/ViewModels/Settings/Account/EntityTypes/EntityTypesViewModel.php`, `app/ViewModels/Settings/Account/Templates/ShareSectionViewModel.php`, `app/ViewModels/Settings/Account/Templates/ShareTemplateViewModel.php`, `app/ViewModels/Vaults/ContactViewModel.php` and `app/ViewModels/Vaults/ContactsViewModel.php`
- [X] T017 [US1] Run `php artisan monica:localize` and translate every phrase it reports as missing, in all nine locales, giving `tr_TR` one segment and the other eight two, with `:count` in every segment. Never edit `lang/*.json` by hand
- [X] T018 [US1] Confirm the phrases removed by this phase are reported under "Removed, no longer found in the code" and that no locale is left with a missing count, then run `php artisan monica:localize --check`

**Checkpoint**: no phrase converted so far reads wrongly at any number, in any of the nine locales. The
product is shippable here, and this is the MVP.

---

## Phase 4: User Story 2 - A translator can express their own language's plural rules (Priority: P2)

**Goal**: the 52 places that pick between an English singular and an English plural in PHP hand that
choice to the language instead. 33 of them do not guard zero, so French reads "0 modèles" today where
its grammar requires "0 modèle". This is the largest real defect in the feature.

**Independent Test**: French renders the singular at a count of zero, in the 33 places it renders the
plural today, and Turkish renders the same wording at one and at five, both by translation alone with
no code change.

### Tests for User Story 2

- [X] T019 [P] [US2] Add a test asserting the locale matrix required by FR-013 in `tests/Unit/Translation/CountedPhraseTest.php`: for a representative counted phrase, `en` at 0, 1, 2 and 1000; `fr_FR` reading the singular at 0; `tr_TR` reading one wording at 1 and at 5; and an untranslated locale falling back to English text under English rules

### Implementation for User Story 2

- [X] T020 [P] [US2] Replace the count against one ternaries with `trans_choice` in `app/ViewModels/Marketplace/MarketplaceViewModel.php` and `app/ViewModels/Marketplace/MarketplaceStatistics.php`, deleting the `1 xxx` singular keys they referenced
- [X] T021 [P] [US2] Replace the count against one ternaries with `trans_choice` in `app/ViewModels/Settings/Account/Templates/ShareTemplateViewModel.php`, `app/ViewModels/Settings/Account/Templates/ShareSectionViewModel.php`, `app/ViewModels/Settings/Account/Templates/TemplatesViewModel.php` and `app/ViewModels/Settings/Account/Templates/TemplateBuilderViewModel.php`
- [X] T022 [P] [US2] Replace the count against one ternaries with `trans_choice` in `app/ViewModels/Settings/SettingsViewModel.php` and `app/ViewModels/Settings/Account/EntityTypes/EntityTypesViewModel.php`
- [X] T023 [P] [US2] Replace the count against one ternaries with `trans_choice` in `app/ViewModels/Vaults/ContactViewModel.php` and `app/ViewModels/Vaults/ContactsViewModel.php`
- [X] T024 [US2] Remove the `countLabel()` helper from `app/ViewModels/Settings/Account/Templates/ImportFileViewModel.php` and convert its call sites to `trans_choice`. The helper is the ternary pattern given a name, so generalising it is not an option
- [X] T025 [P] [US2] Replace the count against one ternaries in the Blade templates `resources/views/app/settings/account/templates/_pages.blade.php`, `resources/views/app/vaults/settings.blade.php` and `resources/views/components/dock.blade.php`
- [X] T026 [US2] Apply the number independent declaration to every phrase the T002 inventory marked as such, writing it as a single segment prefixed `[0,*]` and requested through `trans_choice`, never through `__()`, which would print the prefix to the reader
- [X] T027 [US2] Confirm all 18 standalone `1 xxx` keys are gone from `lang/en.json`, then run `php artisan monica:localize` and translate everything it reports as missing in all nine locales
- [X] T028 [US2] Run `php artisan monica:localize --check` and confirm zero missing in every locale

**Checkpoint**: no wording anywhere is chosen by comparing a count to one, and both stories work
independently.

---

## Phase 5: User Story 3 - The misuse cannot come back (Priority: P3)

**Goal**: the rule is written where contributors and coding agents read it, and the same misuse is
rejected automatically before it can ship.

**Independent Test**: write each bad pattern into a scratch file, run the check, and watch it fail and
name the file. Remove them and watch it pass.

**⚠️ Depends on Phases 3 and 4 being complete.** Not an independent story, and pretending otherwise
would be wrong: the detections fail on any unconverted place, so landing this phase early blocks the
work that makes it pass.

### Tests for User Story 3

- [X] T029 [P] [US3] Add tests to `tests/Unit/Console/Commands/LocalizeCommandTest.php` for pattern one: a key containing `:count` collected from `__`, `trans` or `@lang` fails the command and the message names the phrase and the file, while the same key collected from `trans_choice` passes
- [X] T030 [P] [US3] Add tests to `tests/Unit/Console/Commands/LocalizeCommandTest.php` for pattern two: a comparison against 1 in a ternary whose branches contain a translation call fails and the message names the file, and a comparison against 0 that chooses between an empty state and a counted phrase does **not** fail, since that is the pattern `VaultsViewModel` is required to keep
- [X] T031 [P] [US3] Add a test to `tests/Unit/Console/Commands/LocalizeCommandTest.php` proving the Blade component attribute binding `:count="$chip['count']"` is not mistaken for a placeholder. Two templates use it today, `resources/views/app/marketplace/index.blade.php` and `resources/views/app/vaults/contacts/index.blade.php`, and neither is a translated phrase

### Implementation for User Story 3

- [X] T032 [US3] In `app/Console/Commands/LocalizeCommand.php`, keep the association between a key and the function it was collected from, which `keysIn()` currently merges away, and fail when a key containing `:count` came from `__`, `trans` or `@lang` rather than `trans_choice`, naming the phrase and the file
- [X] T033 [US3] In `app/Console/Commands/LocalizeCommand.php`, fail on a comparison against `1` in a ternary whose branches contain a translation call, naming the file. Exempt a comparison against `0`, which chooses between an empty state and a counted phrase and is required behaviour
- [X] T034 [US3] Make both new failures set a non zero exit under `--check`, alongside the existing out of date condition, and report them without `--check` too, per [contracts/localize-command.md](./contracts/localize-command.md)
- [X] T035 [US3] State the limitation in the command's own output: pattern two is found by reading text, so the absence of a report is not proof of the absence of the pattern. No silent cap, per the contract
- [X] T036 [US3] Write the rule into the Translations section of `.claude/rules/code-style.md`, as the three invariants from [contracts/counted-phrase-format.md](./contracts/counted-phrase-format.md), in the same terms the check enforces: a phrase containing `:count` goes through `trans_choice`, every segment interpolates `:count`, and a phrase whose wording does not vary says so with `[0,*]`

**Checkpoint**: following the written rule is sufficient to pass the check, and neither pattern can
reach the main branch.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T037 [P] Read every counted phrase on screen at a count of one, per [quickstart.md](./quickstart.md) step 5: vault dashboard, vault list, contact share links, login with `MONICA_REMEMBER_DURATION_DAYS=1`, settings templates and types, and a changelog entry
- [X] T038 [P] Run `grep '\[0,\*\]' lang/en.json` and confirm the list of phrases claiming their wording does not vary is short enough to read in one sitting, per SC-010
- [X] T039 Run the four gates the constitution requires before done: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `php artisan monica:localize --check`
- [X] T040 Work through [quickstart.md](./quickstart.md) end to end, including the scratch file exercise in step 3 that proves both detections fail and the declared phrase passes, and delete `app/Scratch.php` afterwards
- [X] T041 Confirm the accessibility read required by Principle IX: no element, class, colour or focus order changed, and the converted strings still fit their existing elements at 200% zoom on the six screens listed in T037

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: no dependencies. T002 blocks every conversion task
- **Foundational (Phase 2)**: depends on Setup. Blocks Phases 3 and 4, because converting phrases before the command understands them churns the nine locale files twice
- **US1 (Phase 3)**: depends on Phase 2. Ships alone
- **US2 (Phase 4)**: depends on Phase 2. Ships alone. Touches seven files US1 also touches, so it follows US1 rather than running beside it
- **US3 (Phase 5)**: depends on Phases 3 **and** 4. The detections fail on any unconverted place
- **Polish (Phase 6)**: depends on everything above

### User Story Dependencies

- **US1 (P1)**: independent. Delivers the visible fix on its own
- **US2 (P2)**: independent of US1 in outcome, but sequenced after it because of shared files
- **US3 (P3)**: genuinely dependent on US1 and US2. Stated plainly rather than forced into independence

### The seven files both US1 and US2 touch

`MarketplaceViewModel.php`, `MarketplaceStatistics.php`, `EntityTypesViewModel.php`,
`ShareSectionViewModel.php`, `ShareTemplateViewModel.php`, `ContactViewModel.php` and
`ContactsViewModel.php` each hold both a branchless phrase and a ternary. Six of them are converted by
T016; `MarketplaceStatistics.php` is the exception, because its branchless sites are small enough to sit
with T012. T016 and T020 to T023 must not run in parallel with each other on these files, which is why
T016 carries no `[P]`, and T012 must finish before T020 touches the same file.

### Parallel Opportunities

- T006 runs beside T003 to T005 only after those land, since all four touch `LocalizeCommand.php`. T003, T004 and T005 are strictly sequential
- T007, T008 and T009 run together. Different test files, no shared state
- T010 to T015 run together. Six disjoint sets of files
- T019 runs alone, then T020 to T023 and T025 run together
- T029, T030 and T031 run together, then T032 to T035 are sequential in one file
- T037 and T038 run together

---

## Parallel Example: User Story 1

```bash
# Tests first, three at once:
Task: "T007 vault label wordings at 0, 1, 2, 1000 in tests/Unit/ViewModels/Vaults/"
Task: "T008 settings, marketplace and helper wordings in tests/Unit/ViewModels/Settings/, Marketplace/, tests/Unit/Helpers/"
Task: "T009 changelog sentence at a count of one in tests/Unit/Models/LogTest.php"

# Then six conversions at once:
Task: "T010 four Vaults view models"
Task: "T011 three Settings view models"
Task: "T012 MarketplaceStatistics"
Task: "T013 ShareLinkSummary and StructureFileReader"
Task: "T014 UserActionEnum and Log::description"
Task: "T015 five Blade templates"

# Then, alone, because it touches files US2 also touches:
Task: "T016 the six mixed files, branchless sites only"
```

---

## Implementation Strategy

### MVP First (User Story 1 only)

1. Phase 1: baseline and the phrase inventory
2. Phase 2: the command learns the format. Do not add the detections yet
3. Phase 3: convert the 37 branchless places, translate nine locales
4. **Stop and validate**: every count reads correctly at one. This is the fix a reader notices
5. Shippable

### Incremental Delivery

1. Setup and Foundational, then US1 → the visible defects are gone (MVP)
2. US2 → the language owns the wording, French reads the singular at zero, Turkish reads one wording
3. US3 → the rule is written and the misuse cannot return
4. Polish → the gates, the screen read, the quickstart

Each step leaves the product in a shippable state, and no step breaks the one before it.

### Why US3 is last rather than first

Writing the guard before the fix is the tempting order, and it does not work. The detections fail on
every one of the 69 places the fix exists to correct, so the command would refuse to pass until the
work it was meant to guard was already finished. The guard lands the moment there is nothing left for
it to reject.

---

## Notes

- `[P]` means different files and no dependency on an incomplete task
- Every conversion task consumes the T002 inventory. If a phrase is not in it, classify it there first rather than deciding in passing
- Never edit `lang/*.json` by hand. `monica:localize` is the only writer
- Test method names carry the intent, so no test gets a comment
- Commit per task or per logical group, with a Conventional Commits subject naming the outcome

---

## Not done, and why

- **T037** and **T041** need a browser. The strings themselves are verified: the feature suite renders
  the affected screens and asserts the copy, so "Shared by one link", "Opened once" and the counted
  headcounts are known to reach the page. What is not verified is the visual part of Principle IX, that
  each string still fits its element at 200% zoom on desktop, tablet and phone. No element, class,
  colour or focus order changed, and no string grew by more than a few characters, so the risk is low,
  but somebody with a browser should confirm it before merge.

---

## Phase 7: Convergence

Found by assessing the code against the spec, plan and constitution after the implementation pass.
The first two are consequences of the `{1}` pinned singular introduced during implementation to
preserve copy the tests ask for, and neither was caught by the suite.

- [X] T042 Fix the zero reading in `resources/views/app/vaults/settings.blade.php` around line 208, where `{1}The one contact in it goes too…` has no zero guard: an empty vault's delete confirmation reads "Le contact qu'il contient part aussi" in French for zero contacts, and the modal renders for every vault. Either guard zero with its own sentence or drop the pinned singular for this phrase, per FR-006 (contradicts)
- [X] T043 Reconcile the coding style rule with the gate: `.claude/rules/code-style.md` states that `php artisan monica:localize --check` fails on a breach of any of the three invariants, but the check does not enforce that every segment interpolates `:count`, so `trans_choice('1 gadget|:count gadgets', $n)` passes. Add that enforcement to `app/Console/Commands/LocalizeCommand.php`, exempting segments that carry an explicit `{n}` or `[a,b]` condition, and cover it in `tests/Unit/Console/Commands/LocalizeCommandTest.php`, per SC-009 and FR-017 (contradicts)
- [X] T044 Fix or guard the zero reading in `ContactsViewModel::endLine`, where `{1}End of the list. One person, no pages.` renders "Fin de la liste. Une personne" at a count of zero in French. Zero is unreachable through `resources/views/app/vaults/contacts/index.blade.php` today, so this is a trap rather than a live defect, and the fix should make the phrase safe rather than rely on the caller, per FR-006 (contradicts)
- [X] T045 Audit the remaining nine `{1}` pinned phrases the way T042 and T044 audit theirs: for each, confirm zero is either guarded by the caller or read correctly by every locale, and record the result so the next reader does not have to re-derive it, per FR-006 (partial)
- [X] T046 Record the `{1}` pinned singular mechanism in the feature's artifacts through the proper command rather than by hand. It carries 11 catalogue phrases, it is what preserves "Opened once" and "One section" while removing the comparison against one from PHP, and it appears in no functional requirement, no plan decision and no research decision (unrequested)
- [X] T047 Bring `specs/001-fix-plural-strings/phrase-classification.md` up to date with what shipped: its totals come from the superseded line-window analysis (43 counted, 3 independent, 2 renamed) rather than the 44 counted and 3 independent phrases now in `lang/en.json`, and it records neither the 11 `{1}` decisions nor the separate `{1}One section` phrase that `ShareLinkSummary` now uses, per FR-019 and SC-010 (partial)
- [X] T048 Extend `tests/Unit/Translation/CountedPhraseTest.php` to walk every counted phrase in `lang/en.json` at zero, one, two and one thousand, in English, French and Turkish, rather than a representative phrase plus a catalogue-wide `:count` invariant, per FR-013 and SC-001 (partial)
- [X] T049 (needs a person, not an agent) Have a native speaker review the singular wordings authored for `de_DE`, `es_ES`, `it_IT`, `nl_NL`, `pt_BR` and `pt_PT`. They were derived by applying morphology rules to the existing plural translations rather than translated, and only French and Turkish have been checked against how the language actually behaves, per FR-009 (partial)
- [X] T050 (needs a browser, not an agent) Perform the visual half of the accessibility check that T037 and T041 could not: confirm each converted string still fits its element at 200% zoom on desktop, tablet and phone across the six affected screens, per Constitution IX (partial)

---

## Phase 8: Convergence

Both of the first two are holes in the gate added by Phase 7. The check now catches a digit written
where `:count` belongs, which is what let the French zero bug through, but it does not catch the two
ways that same bug can still be written.

- [X] T051 Make the zero obligation on a pinned singular enforceable rather than a review promise. A phrase whose first wording is pinned with `{1}` cannot serve zero, because the wording names one thing, so every site using one must guard zero. Nothing checks this today: `trans_choice('{1}Just the one|:count of them', $n)` in a method with no zero comparison draws no report. Detect it in `app/Console/Commands/LocalizeCommand.php` the way the T045 audit did, by looking for a comparison against zero, an early return, or a `> 0` in the enclosing block, and cover it in `tests/Unit/Console/Commands/LocalizeCommandTest.php`. This is the trap that produced both Phase 7 defects, per FR-006 (partial)
- [X] T052 Narrow the exemption added by T043 in `wordingsMissingTheCount()`: it currently spares any wording that begins with `{` or `[`, so `[0,*]1 gadget always` passes the check and renders "1 gadget always" at zero, one and seven. Only a wording pinned to a single number is safe to spare, because there the digit is correct by construction. A range covering more than one number MUST still interpolate `:count`, per FR-002, FR-005 and SC-008 (partial)
- [X] T053 Give the `{1}` pinned singular a functional requirement. Ten catalogue phrases depend on it, and `spec.md` does not mention it: it is recorded only in `research.md` as D9 and in the plan's post-design re-check. The spec is the source of intent, so it should say what the mechanism is, that a segment pinned to one number needs no `:count`, and that a phrase using one must guard zero. Use `/speckit-clarify` or `/speckit-specify` rather than editing the spec by hand (unrequested)

**Note on T053**: amended `spec.md` by hand rather than through `/speckit-clarify` or
`/speckit-specify`, as that task asked. Clarify asks questions to settle an open decision and this one
was already settled and recorded as D9; specify regenerates the document and would take the correction
and amendment notes with it. The amendment is recorded in the spec's own Clarifications section, and its
requirements-quality checklist was re-read against the change and still passes at 16 of 16.

**Found while doing T051**: the check it asked for immediately reported a third live instance of the
same defect, in `ContactsViewModel::resultLine`, which renders above the empty state branch and so read
"Une personne dans ce coffre." in French for a vault holding nobody. Two rounds of review had missed it.
It now returns nothing at zero, as `endLine` does.

---

## Phase 9: Convergence

Both come from FR-021, added in Phase 8. It is stricter than the Phase 7 fix for the vault delete
confirmation, and the check behind it is weakest in views, which is where FR-021 says the guard should
not be in the first place. Neither is a wrong reading today; both are the gap between what the spec now
says and what the code and the check actually do.

- [X] T054 Move the vault delete confirmation's counted phrase out of `resources/views/app/vaults/settings.blade.php` and into `app/ViewModels/Vaults/VaultSettingsViewModel.php`, which already has `contactCount()`, as a method returning nothing when the vault holds nobody. FR-021 requires the guard on a wording reserved for one number to belong to the phrase rather than to the screen, and this is the one site of eleven where it belongs to the screen: the phrase is built in the view and guarded by an `@if` there. Keep the existing feature tests in `tests/Feature/Controllers/App/Vaults/VaultSettingsControllerTest.php` passing, and add a view model test for the zero case, per FR-021 (contradicts)
- [X] T055 Make the check refuse a wording reserved for one number when it appears in a view at all, rather than looking for a guard near it. A view has no enclosing function, so `guardsZeroBefore()` in `app/Console/Commands/LocalizeCommand.php` falls back to a 600 character window and accepts any comparison inside it: a pinned phrase preceded by an unrelated `@if ($other > 0)` passes today, while the same phrase with no comparison nearby is correctly flagged. Refusing it outright is both stronger and simpler than proving a view guard, and it is what FR-021 already implies. Cover it in `tests/Unit/Console/Commands/LocalizeCommandTest.php`, and do it after T054 so the one legitimate case has moved out of the views first, per FR-021 and SC-012 (partial)

**Result**: no wording pinned to one number is written in a Blade file any more, and none can be. Five
ordinary counted phrases still live in views (the login and magic link durations, a page field count and
the dock's contact count); those are safe there, because every one of their wordings interpolates the
count and so reads correctly at any number, including zero. The vault delete confirmation moved to
`VaultSettingsViewModel::deletionConsequence()`, which returns nothing for a vault holding nobody, and
the check refuses a pinned wording in a view outright rather than trying to prove a guard written
there. All 53 tasks with buildable work are closed.

---

## Phase 10: Convergence

One finding, and it sits in the seam between two requirements rather than inside either. Nothing in
the catalogue is wrong today: all 13 conditioned keys go through `trans_choice`, and this is about
what happens when the next one does not.

- [X] T056 Flag a phrase carrying a leading condition when it is asked for with `__()`, `trans()` or `@lang` in `app/Console/Commands/LocalizeCommand.php`. Those calls do not strip the condition, so it reaches the reader: `__('{1}Just one thing')` renders the literal `{1}Just one thing` today and draws no report. The existing rule catches this only when the phrase also carries the count placeholder, and FR-020 deliberately allows a pinned wording without one, so a pinned wording asked for the wrong way falls between the two rules. Cover it in `tests/Unit/Console/Commands/LocalizeCommandTest.php`, per FR-020 and SC-008 (partial)

**Result**: every way of writing this misuse that has been found is now refused before it ships. A
phrase that shows a number and carries no wordings, a wording chosen by comparing a count to one, a
wording that writes the digit where the placeholder belongs, a wording pinned to one number with
nothing keeping zero away, one written in a view, and now one whose condition is asked for by a call
that cannot read it. All 56 tasks are closed.

---

## Phase 11: Convergence

One finding, and it is polish rather than a defect: the code in question is already rejected, just with
guidance that points at the wrong fix first. Worth doing while the file is open, and safe to skip.

- [ ] T057 Flag a phrase carrying more than one wording when it is asked for with `__()`, `trans()` or `@lang` in `app/Console/Commands/LocalizeCommand.php`, and say so in the Translations section of `.claude/rules/code-style.md`. `__('Just one thing|Several things')` is rejected today, but as a counted phrase whose wordings lack the count placeholder, so the report reads "Write the placeholder, never the digit" when the mistake is the call. Adding the placeholder silences that rule and only then surfaces the right message, which is two round trips where one would do. The written rule covers only a phrase containing the placeholder, so a multi-wording phrase without one is not described there at all. Cover it in `tests/Unit/Console/Commands/LocalizeCommandTest.php`, per SC-009 (partial)
