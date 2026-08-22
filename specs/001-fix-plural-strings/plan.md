# Implementation Plan: Correct Plural Phrasing in Interface Copy

**Branch**: `001-fix-plural-strings` (git branch `2026-08-21-fix-plural-strings`) | **Date**: 2026-08-21 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-fix-plural-strings/spec.md`

## Summary

Interface copy that shows a number is asked for with `__()`, which cannot vary a wording by number.
The result is 37 places with one fixed wording whatever the count, at least fifteen of which read
wrongly at one ("1 people", "Opened 1 times", "Remember me for 1 days"), and 32 places that pick
between an English singular and an English plural in PHP, which no translator can change.

The fix is the mechanism Laravel documents for exactly this: `trans_choice()`, with the plural forms
carried in the phrase and separated by `|`. The project's existing convention that a translation key
*is* the English sentence survives intact, because the key simply becomes the pipe separated English
sentence and the source locale keeps writing every value as its own key.

Three findings from Phase 0 shape the work, all verified by running the framework rather than reading
it:

1. **Every segment must interpolate `:count`.** Writing the singular as the literal `1 personne` makes
   French render "1 personne" at a count of zero, because French groups zero with the singular. With
   `:count personne` it renders "0 personne". English is unaffected, since `:count person` at one
   renders "1 person" too. This kills the 18 standalone `1 xxx` keys rather than promoting them.
2. **Turkish needs exactly one segment.** `getPluralIndex` returns 0 for `tr_TR` at every number, so a
   second segment is text no reader can reach. The number of segments a locale needs is therefore a
   property of the locale, and the check derives it by probing the framework instead of keeping a
   table that can drift.
3. **`trans_choice` strips an explicit `[0,*]` range and `__()` does not.** That gives the number
   independent declaration the spec asks for (FR-018) a Laravel native form that lives at the phrase
   and is visible in review, and it makes the rule uniform: any phrase whose text contains `:count`
   goes through `trans_choice`, full stop.

## Technical Context

**Language/Version**: PHP 8.4.24

**Primary Dependencies**: Laravel 13.23.0. No new dependency. The work uses
`Illuminate\Translation\Translator::choice()` and `MessageSelector`, both already present.

**Storage**: N/A. Nothing persists. The nine `lang/*.json` files and the phrases in code are the whole
surface.

**Testing**: PHPUnit, per `.claude/rules/testing.md`.

**Target Platform**: Laravel web application, Blade with Tailwind and Alpine.

**Project Type**: Web application, single codebase.

**Performance Goals**: N/A. `trans_choice()` costs the same lookup as `__()` plus one `explode`.

**Constraints**: The nine configured locales stay at zero missing translations. `monica:localize`
remains the only writer of `lang/*.json`. Pint and PHPStan stay clean.

**Scale/Scope**: 46 phrases in the English catalogue that show a number, referenced from 69 places
across 31 files, plus 18 standalone singular keys to remove. 21 view models and helpers, 10 Blade
templates, one model accessor, one enum, one Artisan command, and `.claude/rules/code-style.md`.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Gate | Verdict |
|---|---|---|
| I. Actions hold the business logic | No business logic may move into a view model, and view models stay read only | PASS. Choosing a wording for a number is presentation. No action changes. View models keep reading and returning strings. |
| II. Simplicity over abstraction | No new abstraction where a framework feature exists | PASS, and it is the reason for two decisions. The required segment count is probed from `MessageSelector` rather than duplicated in a config table, and the number independent declaration reuses Laravel's `[0,*]` range rather than inventing a marker. No helper is introduced to wrap `trans_choice`. |
| III. Tested before merged | Every touched class has a test, and the suite passes | PASS by construction. `LocalizeCommandTest` grows the new failures, each touched view model gains wording assertions at zero, one, two and a large number, and `Log::description` gains a counted phrase test. |
| IV. Laravel conventions are the default | Deviations justified | PASS, strongly. `trans_choice()` is the documented mechanism, and this change replaces a hand rolled substitute with it. One class per file holds. |
| V. Localized and safe user experience | Translatable through the documented path, `monica:localize` the only writer, all nine locales, no hand edited keys | PASS. This is the principle the change exists to satisfy. No destructive UI action is involved. |
| VI. Nothing changes under anybody's feet | No change alters what somebody already sees without accepting it | PASS. Interface copy is not user data. No record, template version or preference is touched. |
| VIII. No silent deletion | Nothing disappears quietly | PASS with a note. Reshaping the keys drops the old ones, and each drop takes its eight translations with it. The deletion is not silent: `monica:localize` prints "Removed, no longer found in the code" for every key that leaves, and FR-014 requires the replacements to be translated in the same change. See Complexity Tracking. |
| IX. Accessible to everybody | Every screen a change touches is audited | PASS. No markup, structure, colour or focus behaviour changes. Only the text inside existing elements changes, and a plural wording is never longer than the fixed plural it replaces by more than a few characters. |
| X. Everything goes through the API | A new capability states where its API coverage is | N/A. This adds no capability. The phrases live in view models and Blade, which is where interface copy belongs, and there is no `Api` namespace to mirror. |

**Gate result: PASS. No violations to justify, one note recorded below.**

### Re-check after Phase 1 design

The design added three things worth re-testing against the constitution, and none moved a verdict.

- **`trans_choice` at every call site instead of a project helper.** Re-tested against Principles II
  and IV. Still PASS, and better than the alternative: the rule in the coding style names the call you
  write, so guidance and code use one vocabulary. A `countLabel()` style helper already exists in
  `ImportFileViewModel` and is removed by this change rather than generalised.
- **Probing `MessageSelector` for the required segment count.** Re-tested against Principle II. PASS.
  It replaces a config table nobody would remember to update, and it reads from the same function that
  chooses the segment at render time, so it cannot disagree with what a reader sees.
- **`[0,*]` as the number independent declaration.** Re-tested against Principles II and IV. PASS. It
  is a documented framework feature, not an invention, and it needs no new syntax, no registry and no
  helper.

A fourth mechanism arrived during implementation and is recorded as research decision D9: a singular
pinned to exactly one number with `{1}`, used by ten phrases to keep copy such as "Opened once" that
the tests for those screens ask for. Re-tested against Principles II and IV: PASS, since it is a
documented framework condition rather than an invention, though it carries a trap that convergence
had to find. It is **not** described by any functional requirement, so the spec is behind the
implementation on this point and needs a `/speckit-clarify` or `/speckit-specify` pass to catch up.

One item was checked and deliberately left alone. Principle IX requires every screen a change touches
to be audited, and this change touches text inside 10 Blade templates. No element, class, colour or
focus order changes, so the audit is a read of the rendered strings, listed in
[quickstart.md](./quickstart.md) step 5, rather than a full screen audit.

## Project Structure

### Documentation (this feature)

```text
specs/001-fix-plural-strings/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   ├── counted-phrase-format.md   # What a counted phrase is and how it is written
│   └── localize-command.md        # What the command reports and when it fails
├── checklists/
│   └── requirements.md  # Written by /speckit-specify, revalidated by /speckit-clarify
└── tasks.md             # Written by /speckit-tasks, not by this command
```

### Source Code (repository root)

```text
app/
├── Console/Commands/
│   └── LocalizeCommand.php          # Collects counted phrases, and fails on the two bad patterns
├── Enums/
│   └── UserActionEnum.php           # Three changelog sentences become counted phrases
├── Helpers/
│   ├── ShareLinkSummary.php
│   └── StructureFileReader.php      # Four refusal messages naming a limit
├── Models/
│   └── Log.php                      # The one accessor that renders an enum sentence
└── ViewModels/                      # 19 files, the bulk of the work
    ├── Marketplace/                 # MarketplaceViewModel, MarketplaceStatistics
    ├── Settings/                    # SettingsViewModel, and 7 under Settings/Account
    └── Vaults/                      # 6 files

resources/views/                     # 10 Blade templates
├── app/auth/                        # login, magic-link-sent
├── app/marketplace/                 # index
├── app/settings/account/templates/  # export, share, _pages
├── app/vaults/                      # settings, contacts/index
├── components/                      # dock
└── mail/auth/                       # magic-link-created

lang/                                # 9 files, written only by monica:localize
├── en.json                          # 18 singular keys leave, 46 phrases change shape
└── de_DE, es_ES, fr_FR, it_IT, nl_NL, pt_BR, pt_PT, tr_TR

tests/
├── Unit/
│   ├── Console/Commands/LocalizeCommandTest.php   # Grows the new failure cases
│   ├── Models/LogTest.php
│   └── ViewModels/                                 # Wording assertions per view model
└── Feature/                                        # Unchanged, screens keep rendering

.claude/rules/code-style.md          # The written rule, in the Translations section
```

**Structure Decision**: No new directory and no new class. The change is concentrated in the view
models that already build these labels, in the one Artisan command that already reads every file for
translatable strings, and in the coding style rules. Adding a helper to wrap `trans_choice()` was
rejected under Principle II: it would hide the framework call the rule is about, and the rule is
easier to follow when the call it names is the call you write.

## Complexity Tracking

> No constitution violations. One consequence is recorded here because it is the riskiest part of the
> change and a reviewer should see it stated rather than discover it.

| Consequence | Why it is accepted | Alternative rejected because |
|---|---|---|
| Reshaping 46 phrases and removing 18 drops those keys from all eight translated locales, discarding existing translated values | Principle VIII is satisfied because the loss is reported, not silent, and FR-014 requires the replacements translated in the same change. The alternative leaves the product shipping wrong grammar in nine languages. | Keeping the old keys beside the new ones would leave two spellings of the same sentence in the catalogue, and the dead one would be translated forever by whoever did not know which was live. |
| The two bad patterns are detected by reading files as text, not by parsing them | The command already reads files as text on purpose, because a Blade file is not PHP until compiled, and it already finds every translation call this way. | Building an AST pass would mean two ways of finding translatable strings in one command, and would still not cover Blade. A false positive here is loud and fixed in one line; the current silence is what shipped the bug. |
