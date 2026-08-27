# Research: French Translation Rules

**Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md) | **Phase**: 0

Everything below was settled by reading the codebase and by measuring the shipped locale files, not
by recalling how Laravel projects usually do this. Each measurement is repeatable.

## D1. Where the check hangs

**Decision**: extend `App\Console\Commands\LocalizeCommand`. Collect breaches per locale inside
`synchronize()`, return them in the result array, report them from a new `reportX()` method, and count
each into `$missing` so `--check` fails.

**Rationale**: the command already does exactly this, twice. `synchronize()` reads each translated
value and checks its wording count and its `:count` placeholders, returning `incomplete` and
`uncounted` lists that `reportIncomplete()` and `reportUncounted()` print. A third list follows the
same path with no new machinery. The failure route is already wired: `missing > 0` sets `$outOfDate`,
which `--check` turns into a non-zero exit.

**This corrects the spec.** FR-015 first said the routine does not inspect translated values. It does.
What it does not do is judge the prose inside one. The correction is recorded in the spec.

**Alternatives rejected**:

- A separate command. Two commands to run and two ways to be out of date, when the project's checklist
  names one.
- A test that reads `lang/fr_FR.json` directly and asserts on it. It would catch the shipped file but
  say nothing while somebody is writing a new string, and SC-008 asks for the breach to be caught
  before it reaches the file.

## D2. Where a locale declares its rules

**Decision**: a `typography` key inside that locale's entry in `config/monica.php`, beside `label`,
`region`, `date_format`, `number_format` and `flag`. Absent or empty means the locale has no rules and
passes.

**Rationale**: FR-017 asks for the rules to come from the locale's own section, and this is literally
that. `locales()` in the command already reads `config('monica.locales')`, so the check gains its
input without gaining a dependency. The `add-locale` skill already sends anybody adding a language to
this exact array, so a tenth locale is a matter of writing its rules where it is already writing its
date format.

**Alternatives rejected**:

- A class per locale implementing a contract. More expressive than the four kinds of rule need, and
  Principle II asks for the simpler of two readable options. Worth revisiting only if a locale ever
  needs a rule that data cannot state.
- A new `config/typography.php`. Splits one locale across two files, which is how the two halves start
  disagreeing.
- Parsing the rules out of the Markdown document. The document is prose written for people. Making it
  machine-read would freeze its wording.

## D3. The document, and how it avoids drifting from the config

**Decision**: `.claude/rules/translations.md`, one section per configured locale, registered in
`.claude/rules/index.md` and pointed at from the `translations` and `add-locale` skills. French is
filled in. The other eight say that nobody has written their rules yet. One test asserts that the
`fr_FR` typography config holds exactly the characters the document names.

**Rationale**: FR-001 asks for the rules where the project's standing rules live, which is
`.claude/rules/`. That leaves two statements of the same four facts, the prose and the config, and the
constitution is explicit that two descriptions of one thing is how the first goes stale. A single
small test pinning the four characters costs almost nothing and removes the drift. It is a regression
test on a settled decision, not machinery.

**Alternatives rejected**:

- Document only, config derived by a human reading it. This is the current situation for the plural
  rules and it works, but those rules live in `code-style.md`, which every agent loads. A per-locale
  typography document will not be loaded on every task.
- Config only, no document. Fails FR-001 and FR-005, and a config array does not explain why French
  needs a narrow space.

## D4. Which non-breaking space, and what that costs in the file

**Decision**: U+202F before `;`, `!` and `?` and inside `«` and `»`; U+00A0 before `:`. Settled in
clarification.

**What the research adds**: `synchronize()` encodes with `JSON_UNESCAPED_UNICODE`, so both characters
are written into `lang/fr_FR.json` literally rather than as ` `. They are invisible in an editor
and indistinguishable from an ordinary space by eye.

Dropping the flag is not an option: without it every accented character in French, German, Portuguese
and Turkish becomes an escape sequence, and eight locale files stop being readable. The invisibility is
therefore accepted, and the check is what makes it safe. This is the reason FR-005 requires the
document to name both characters by code point rather than by showing them.

## D5. Whether the *vous* rule can be checked mechanically

**Decision**: check the unambiguous tutoiement pronouns only, `tu`, `toi` and `tes`, on word
boundaries. Do not check `ton` and `ta`.

**Rationale**: measured against the shipped file, all six of `tu`, `ton`, `ta`, `tes`, `toi` and `te`
appear zero times, so any of them would pass today. The question is the false positive tomorrow. `ton`
is also the French noun for a tone or a shade, and `ta` appears inside musical and abbreviation
contexts; either could turn up in a legitimate string. `tu`, `toi` and `tes` have no such reading.

FR-018 makes a breach fail and FR-021 forbids a list of tolerated values, so there is no escape hatch:
a false positive would block a correct string with no way to accept it except rewording. Between
missing a rare slip and blocking a correct string, missing the slip is the failure worth choosing.

**Alternatives rejected**:

- Check all six. Buys a little coverage at the price of an unfixable false positive.
- Check `ton` and `ta` only when the value contains no `vous`. Clever, and Principle II rules it out.
- Skip the *vous* rule entirely. It is the one convention the file already keeps perfectly, in 272
  values, and the point of writing it down is that nothing currently protects it.

## D6. How the existing 340 breaches get corrected

**Decision**: a one-off script run in the working tree, not committed. It rewrites values in
`lang/fr_FR.json` only. `php artisan monica:localize` then normalises the file, and the new check
verifies the result.

**Rationale**: the rule that `lang/*.json` is never edited by hand is about **keys**. The constitution
says "Keys in `lang/*.json` MUST NOT be edited by hand", and the translations skill says not to add,
remove or reorder a key. Values are what a translator writes, so correcting them is the ordinary
translation workflow, and 340 of them is a job for a script rather than for 340 careful edits.

The script is a one-off and stays out of the repository. A committed Artisan command would be a
permanent tool for a job done once, and FR-019 says the check must never rewrite a value.

**Measured risk, and why it is low**: the correction is textual, so anything resembling markup would
be at risk. Neither `lang/fr_FR.json` nor `lang/en.json` contains a single HTML tag, Markdown link,
URL, backtick or `**`. Verified across all 1708 values in both. The only structural characters in play
are the `:name` placeholders and the `|` separating counted wordings, and both are covered by FR-014
and FR-016.

**Alternatives rejected**:

- Correct by hand. 340 values, four rules, two invisible characters.
- A committed Artisan command that fixes the file. Contradicts FR-019 and outlives its purpose.

## D7. Distinguishing a placeholder's colon from French punctuation

**Decision**: a colon that is immediately followed by a letter or an underscore belongs to a
placeholder and is left alone. Every other colon is French punctuation and takes U+00A0 before it.

**Rationale**: this is the mistake worth naming, because it was made during this feature. The first
measurement of the file reported 326 values with a space before a colon. The regular expression was
matching `Supprimer :name ?`, where the colon opens a placeholder. The real figure is 40. Every count
in the spec was re-measured after the rule above was applied.

Laravel's placeholders are `:` followed by word characters, so the rule is exact rather than a
heuristic, and no French value spells a colon immediately before a letter for any other reason.
Measured: 0 values contain a time, a ratio or a URL scheme, which are the usual counter-examples.

## D8. What the check must not do

**Decision**: report and refuse. Never rewrite.

**Rationale**: FR-019. Beyond the spec's reasoning, a rewriting check would be the only thing in the
codebase that edits translated prose without a person reading it, and the guillemet rule in particular
cannot be applied mechanically. Turning `"promeneuse de chiens"` into guillemets means deciding what
was being quoted, which is a reading of the sentence.

## Open questions carried into implementation

None. Every NEEDS CLARIFICATION in the spec was answered before this phase, and the two facts this
phase discovered, that the command already inspects values and that the file carries no markup, were
folded back into the spec and into the decisions above.
