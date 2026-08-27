# Implementation Plan: French Translation Rules

**Branch**: `002-french-translation-rules` (git branch `2026-08-26-french-translation-rules`) | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/002-french-translation-rules/spec.md`

## Summary

Write down how French copy is punctuated and addressed, put those rules where a locale already
declares everything else about itself, teach the routine that keeps translations in step to refuse a
value that breaks them, and correct the roughly 340 values in the shipped French file that do.

The technical shape comes from [research.md](./research.md), and the short version is that almost none
of it is new. `LocalizeCommand::synchronize()` already reads every translated value and already
collects per-locale breaches of the counted-phrase rules, which are already reported by name and
already turn `--check` red. A third kind of breach follows the identical path. What is genuinely new
is a `typography` key on each locale in `config/monica.php`, a document under `.claude/rules/`, and
one throwaway script that corrects the file once.

## Technical Context

**Language/Version**: PHP 8.4.24

**Primary Dependencies**: Laravel 13.8. No new dependency.

**Storage**: `lang/*.json`, one file per locale, written by `php artisan monica:localize`.
`config/monica.php` for the per-locale rules.

**Testing**: PHPUnit. `tests/Unit/Console/Commands/LocalizeCommandTest.php` (28 tests, fixture lang
path via `useLangPath`) and `tests/Unit/Translation/` for language behavior.

**Target Platform**: Linux, CLI for the check, every screen for the result.

**Project Type**: Laravel monolith, Blade and Alpine.

**Performance Goals**: none of its own. The check runs inside the existing pass over 9 locales and
1708 keys, reads no additional file, and must not make `monica:localize` noticeably slower.

**Constraints**: the check reports and refuses, never rewrites (FR-019). No list of tolerated values
anywhere (FR-021). A locale with no rules written passes (FR-017). Keys and English strings are not
touched.

**Scale/Scope**: 9 locales, 1 of them given rules. 1708 keys. About 340 French values corrected:
259 apostrophes, 74 spacings, 11 quotations, with overlap.

## Constitution Check

*GATE: passed before Phase 0. Re-checked after Phase 1, below.*

| Principle | Gate | Verdict |
| --- | --- | --- |
| I. Actions Hold the Business Logic | Does this belong in an Action? | **N/A.** Nothing here is a user action. The work extends a console command, which is where the sibling plural rules already live. |
| II. Simplicity Over Abstraction | Is anything extracted that need not be? | **Pass.** Rules are config data, not a class hierarchy. D2 and D5 both record a cleverer option rejected under this principle. |
| III. Tested Before Merged | Is every behavior covered? | **Pass.** Four rules, each with an accepting and a refusing test, plus the placeholder and separator exclusions, plus a locale with no rules. Extends an existing test class. |
| IV. Laravel Conventions Are the Default | Anything fighting the framework? | **Pass.** Config through `config()`, command through Artisan, tests through `artisan()`. |
| V. Localized and Safe User Experience | Does it serve localization? | **Pass, and this is the principle the feature exists to serve.** It adds no string, changes no key, and leaves user-created content alone. |
| VI. Nothing Changes Under Anybody's Feet | Does anything change under a user? | **Pass with a note.** 340 French strings change appearance: a different apostrophe, a narrower space. No meaning changes (FR-014). The principle governs published templates and records, not the product's own interface copy, which is expected to improve between versions. |
| VII. A Stable Core, Unlimited Personalisation | Does it touch the core's shape? | **N/A.** |
| VIII. Nothing Disappears Silently | Is anything deleted? | **Pass.** No key is removed. The correction rewrites characters inside values and deletes nothing. |
| IX. Accessible to Everybody | Any screen affected? | **Pass, favorably.** No markup or contrast change. Story 1 removes a punctuation mark stranded at the start of a line, which is a reading defect that worsens at 200% zoom, exactly where WCAG 2.2 AA asks for the text to stay readable. |
| X. Everything Goes Through the API | Any capability the API lacks? | **N/A.** A development-time check, not a product capability. |

No violation. The Complexity Tracking table below stays empty.

## Project Structure

### Documentation (this feature)

```text
specs/002-french-translation-rules/
├── plan.md              # This file
├── research.md          # Phase 0: eight decisions, two spec corrections
├── data-model.md        # Phase 1: the typography config shape and what a breach is
├── quickstart.md        # Phase 1: how to run and verify the whole thing
├── contracts/
│   ├── typography-config.md   # The per-locale config contract
│   └── localize-command.md    # What the command reports and when it fails
└── tasks.md             # Phase 2, written by /speckit-tasks
```

### Source Code (repository root)

```text
config/
└── monica.php                              # `typography` key added to the fr_FR locale entry;
                                            # the other eight locales left as they are

app/Console/Commands/
└── LocalizeCommand.php                     # collects typography breaches in synchronize(),
                                            # reports them in a new reportTypography(),
                                            # counts them into $missing so --check fails

lang/
└── fr_FR.json                              # ~340 values corrected, once, by a script that is
                                            # not committed

.claude/rules/
├── index.md                                # gains a link to translations.md
└── translations.md                         # NEW: one section per locale, French filled in,
                                            # the other eight stating that nobody has written theirs

.claude/skills/
├── translations/SKILL.md                   # points at the new rules document
└── add-locale/SKILL.md                     # step for declaring the new locale's typography

tests/Unit/
├── Console/Commands/LocalizeCommandTest.php    # the refusing and accepting cases per rule
└── Translation/FrenchTypographyTest.php        # NEW: pins the four fr_FR characters, so the
                                                # document and the config cannot drift apart
```

**Structure Decision**: no new directory anywhere, and no new class. Every file above already exists
except `.claude/rules/translations.md` and `tests/Unit/Translation/FrenchTypographyTest.php`, and the
second of those sits beside `CountedPhraseTest.php` in a directory the plural feature already created
for exactly this kind of test. `app/` is untouched apart from the one command. That is deliberate:
[D1](./research.md) found the hook point already built and tested, and [D2](./research.md) found the
place a locale declares itself already in use by the `add-locale` skill.

## Phase 1 output

- [data-model.md](./data-model.md): the `typography` array, the four rule kinds, and what a breach is.
- [contracts/typography-config.md](./contracts/typography-config.md): the config shape, what an absent
  or empty declaration means, and what the check may assume.
- [contracts/localize-command.md](./contracts/localize-command.md): the report wording, the exit
  behavior, and the exclusions the check must honor.
- [quickstart.md](./quickstart.md): running the correction, running the check, and the measurements
  that prove the file is clean.

## Constitution re-check, after Phase 1

Unchanged. Phase 1 added no class, no directory, no dependency and no route. The one thing it added
beyond the plan above is the drift test named in [D3](./research.md), which is four assertions on
config values and does not move the verdict on Principle II.

## Complexity Tracking

No constitutional violation to justify.
