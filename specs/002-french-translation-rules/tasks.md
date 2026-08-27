---

description: "Task list for French Translation Rules"
---

# Tasks: French Translation Rules

**Input**: Design documents from `specs/002-french-translation-rules/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/)

**Tests**: included, and not optional here. Constitution Principle III is non-negotiable, and
`CLAUDE.md` requires every change to be covered by a test.

**Organization**: grouped by user story. Each story owns one family of rules end to end, from the
config that declares them through the check that enforces them to the values in `lang/fr_FR.json` that
break them. A story can therefore ship on its own with `monica:localize --check` green.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel, meaning a different file and no dependency on unfinished work
- **[Story]**: US1, US2, US3, mapping to the user stories in [spec.md](./spec.md)

## A note on parallelism

There is little of it, and pretending otherwise would mislead. Four files carry almost all the work:
`config/monica.php`, `app/Console/Commands/LocalizeCommand.php`,
`tests/Unit/Console/Commands/LocalizeCommandTest.php` and `lang/fr_FR.json`. Tasks touching the same
file are sequential, so `[P]` appears only where the files genuinely differ, which is most of Phase 6.

---

## Phase 1: Setup

**Purpose**: know what the file looks like before anything touches it

- [X] T001 Record the baseline of `lang/fr_FR.json` with the measurement command in [quickstart.md](./quickstart.md), and confirm it reports 259 straight apostrophes and 74 breaking spaces before punctuation. Every later verification is a comparison against these numbers.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: the mechanism every rule hangs on. No rule is evaluated yet.

**CRITICAL**: US1 and US2 cannot begin until this phase is done. US3 can.

- [X] T002 Add the `typography` key to the `fr_FR` entry of `config/monica.php`, in the shape given by [contracts/typography-config.md](./contracts/typography-config.md), writing both space characters as `"\u{202F}"` and `"\u{00A0}"` escapes rather than literally. Leave the other eight locale entries without the key.
- [X] T003 In `app/Console/Commands/LocalizeCommand.php`, read the locale's typography declaration from `config('monica.locales.{locale}.typography')`, defaulting to an empty array. Collect a per-locale `typography` breach list in `synchronize()` beside the existing `incomplete` and `uncounted` lists, return it in the result array, count each breach into `$missing`, and print it from a new `reportTypography()` that names the locale, the rule and each offending value.
- [X] T004 In `app/Console/Commands/LocalizeCommand.php`, add the prose extraction the rules read: split a value on `|` into its wordings, and mask every `:placeholder` so that no rule can see a placeholder's colon. A colon followed by a letter or an underscore opens a placeholder, per [D7](./research.md).
- [X] T005 Confirm nothing regressed with the plumbing in place and no rule declared: `php artisan test --compact tests/Unit/Console/Commands/LocalizeCommandTest.php` and `php artisan monica:localize --check` both pass.

**Checkpoint**: the mechanism exists and is silent. It has no test of its own, because a mechanism with no rules has no behaviour to assert. Its two invariants, that an undeclared locale stays silent and that English stays silent, are tested in T008, the first point at which a rule exists to exercise them.

---

## Phase 3: User Story 1 - French punctuation stays attached to the word it belongs to (Priority: P1) 🎯 MVP

**Goal**: no punctuation mark can be pushed onto the next line on its own. 74 values corrected, and a new breach refused.

**Independent Test**: render the French locale at a phone width across the screens carrying these strings and confirm no punctuation mark begins a line alone. `Supprimer « :name » ?` with a long name is the single best case.

### Tests for User Story 1

- [X] T006 [US1] Add the spacing cases to `tests/Unit/Console/Commands/LocalizeCommandTest.php`, from the table in [contracts/localize-command.md](./contracts/localize-command.md): an ordinary space before `?` reported, U+202F before `?` silent, no space before `?` reported, U+00A0 before `:` silent, U+202F before `:` reported.
- [X] T007 [US1] Add the placeholder case to `tests/Unit/Console/Commands/LocalizeCommandTest.php`: `Supprimer :name ?` with U+202F before the `?` is silent, because the placeholder's colon is not punctuation. Also the guillemet inner-space cases, which this task omitted and T010 implements: `« x »` with ordinary spaces reported, with U+202F silent.
- [X] T008 [US1] Add the exclusion cases to `tests/Unit/Console/Commands/LocalizeCommandTest.php`: the same offending value in `de_DE` is silent because German declares no rules, any value in `en` is silent, and a counted phrase whose wordings are both clean is silent while a breach after the `|` is reported.

### Implementation for User Story 1

- [X] T009 [US1] Implement the `space_before` rule in `app/Console/Commands/LocalizeCommand.php`: for each mark the locale declares, a breach is any occurrence preceded by a space other than the declared one, or by no space at all.
- [X] T010 [US1] Implement the `quotes.inner_space` half of the quotation rule in `app/Console/Commands/LocalizeCommand.php`: a guillemet without the declared space inside it is a breach. The character half of that rule belongs to US2.
- [X] T011 [US1] Correct the 74 spacing breaches in `lang/fr_FR.json` with a one-off script written in the session scratchpad and not committed, applying U+202F before `;`, `!` and `?` and inside guillemets, and U+00A0 before `:`, honouring the placeholder exclusion. Then run `php artisan monica:localize`.
- [X] T012 [US1] Verify: the spacing half of the baseline measurement now reads 0, `php artisan monica:localize --check` passes, and `git diff --stat` shows `lang/fr_FR.json` and nothing else under `lang/`.

**Checkpoint**: User Story 1 is complete and shippable on its own. The visible defect is gone and a new spacing breach is refused.

---

## Phase 4: User Story 2 - One French, not several (Priority: P2)

**Goal**: one apostrophe, one quotation convention, one form of address. 270 values corrected, and a new breach refused.

**Independent Test**: read every French value and confirm one apostrophe character, guillemets everywhere text is quoted, and no tutoiement.

### Tests for User Story 2

- [X] T013 [US2] Add the apostrophe cases to `tests/Unit/Console/Commands/LocalizeCommandTest.php`: `L'image` reported, `L’image` silent.
- [X] T014 [US2] Add the quotation character cases to `tests/Unit/Console/Commands/LocalizeCommandTest.php`: a value quoting with straight double quotes reported, the same value in guillemets with U+202F inside silent.
- [X] T015 [US2] Add the address cases to `tests/Unit/Console/Commands/LocalizeCommandTest.php`: `Tu peux` reported, `Ton coffre` silent because `ton` is also the French noun for a tone and is deliberately not checked, per [D5](./research.md).

### Implementation for User Story 2

- [X] T016 [US2] Implement the `forbidden_apostrophes` rule in `app/Console/Commands/LocalizeCommand.php`.
- [X] T017 [US2] Implement the `forbidden_quotes` rule in `app/Console/Commands/LocalizeCommand.php`.
- [X] T018 [US2] Implement the `forbidden_words` rule in `app/Console/Commands/LocalizeCommand.php`, matching on word boundaries and case insensitively.
- [X] T019 [US2] Correct the 259 straight apostrophes in `lang/fr_FR.json` with the one-off script. Safe as a textual replacement because no value in any locale file contains an HTML tag, a Markdown link, a URL, a backtick or a `**`, verified across all 1708 values in [D6](./research.md).
- [X] T020 [US2] Correct the one value quoting with straight double quotes in `lang/fr_FR.json` by hand, not by script. It held a JSON sample that no correction could make satisfy the rules, so on the user's decision the French was reworded to drop the literal sample. Recorded as a correction in [spec.md](./spec.md). Choosing what the guillemets enclose is a reading of the sentence, which is why FR-019 keeps the check out of it. Then run `php artisan monica:localize`.
- [X] T021 [US2] Verify: the apostrophe half of the baseline measurement reads 0, `php artisan monica:localize --check` passes, and no key changed.

**Checkpoint**: User Stories 1 and 2 both complete. The file satisfies every rule and the check ships green, which is SC-010.

---

## Phase 5: User Story 3 - A contributor knows the rules before writing, not after (Priority: P3)

**Goal**: the rules are readable by a person before they write a string, not inferable only from the config or from a neighbouring translation.

**Independent Test**: hand somebody unfamiliar with the codebase a new English string and the document, and confirm the French they produce needs no punctuation correction in review.

- [X] T022 [US3] Create `.claude/rules/translations.md` with one section per configured locale. Fill in French: the apostrophe, both space characters named by code point because neither is visible, the guillemets, and *vous*. State what the rules do not cover, naming number and date formats as deliberately out of scope. Give the other eight locales a section saying their rules are unwritten, so a contributor can tell that from rules that do not exist.
- [X] T023 [P] [US3] Add `.claude/rules/translations.md` to the list in `.claude/rules/index.md`.
- [X] T024 [P] [US3] Point `.claude/skills/translations/SKILL.md` at the new document from its "Writing a translation" section.
- [X] T025 [P] [US3] Add a step to `.claude/skills/add-locale/SKILL.md` covering the optional `typography` declaration, after the existing step 1 that registers the locale in `config/monica.php`. The file already has an uncommitted step 7 staged; keep it.
- [X] T026 [US3] Create `tests/Unit/Translation/FrenchTypographyTest.php` pinning the four characters the document names against `config('monica.locales.fr_FR.typography')`, so the prose and the config cannot drift apart. One class, `declare(strict_types=1)`, `#[Test]` attributes, snake_case names, no comments, per `.claude/rules/testing.md`.

**Checkpoint**: all three stories complete.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T027 Run `vendor/bin/pint --dirty --format agent` and commit what it changes.
- [X] T028 Run the full suite with `php artisan test --compact`. Not a filtered run: the correction changed roughly 340 strings, and a feature test asserting on French copy would notice. This is the run that catches it.
- [X] T029 [P] Follow [quickstart.md](./quickstart.md) end to end on a clean checkout of the branch and confirm every step reports what it says it will. Steps 1 to 6 done and reporting as written. Step 7, looking at a screen at phone width, was NOT done: it needs the application running against a database and the best case sits behind a login. The characters are proven by measurement instead.
- [X] T030 [P] Confirm `git diff --name-only main` lists only the files named in [plan.md](./plan.md), and that the one-off correction script is not among them.

---

## Dependencies & Execution Order

### Phase dependencies

- **Setup (T001)**: no dependencies.
- **Foundational (T002 to T005)**: needs T001 only for the numbers to compare against. Blocks US1 and US2.
- **US1 (T006 to T012)**: needs Foundational.
- **US2 (T013 to T021)**: needs Foundational. Independent of US1, though both edit the same four files, so in practice they are done one after the other by one person.
- **US3 (T022 to T026)**: needs T002 for the config the document describes and the drift test asserts on. Independent of US1 and US2 otherwise, and could be written first if you wanted the rules on paper before enforcing them.
- **Polish (T027 to T030)**: needs everything.

### Within each story

- Tests before implementation. Write the case, watch it fail, make it pass.
- The check before the correction. T009 and T010 come before T011, and T016 to T018 before T019, so that the script's output is verified by something rather than inspected by eye.
- The correction before the verification.

### Parallel opportunities

- T023, T024 and T025 are three different files with no shared content.
- T029 and T030 are independent verifications.
- Nothing else. Every other task lands in `config/monica.php`, `LocalizeCommand.php`, `LocalizeCommandTest.php` or `lang/fr_FR.json`, and two people in one of those files is a merge conflict rather than parallelism.

---

## Implementation Strategy

### MVP

Phase 1, Phase 2, Phase 3. That is T001 to T012, and it delivers the only defect a reader can
actually see: a question mark stranded at the start of a line. The apostrophe split and the missing
document are real, but nobody reading the product notices either one.

Stop there and validate before going further. `monica:localize --check` passes, the spacing
measurement reads 0, and a screen at phone width shows the punctuation where it belongs.

### Incremental delivery

1. Setup and Foundational, and the mechanism exists.
2. US1, and French punctuation is correct. Shippable.
3. US2, and the file is internally consistent. Shippable.
4. US3, and the rules are written down so it stays that way. Shippable.

### If you would rather write the rules first

US3 depends only on T002. Doing Phase 5 before Phase 3 gives you the document to review before any
code enforces it, which is the better order if you expect to argue about the conventions themselves.
The drift test in T026 will pass either way, since it reads the config that T002 writes.

---

## Notes

- The correction script is a one-off. It lives in the session scratchpad, runs twice, and is never
  committed. FR-019 keeps the committed check out of the rewriting business, and a permanent Artisan
  command for a job done once would outlive its purpose.
- No task adds a user-facing string, so no task requires a `monica:localize` translation pass beyond
  normalising the file after a correction. The command's own console output is not wrapped in `__()`.
- Both space characters are invisible in an editor. Verify by measurement, never by looking.
