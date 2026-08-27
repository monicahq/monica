# Contract: what `monica:localize` reports and when it fails

**Spec**: [spec.md](../spec.md) | **Requirements**: FR-015, FR-016, FR-018, FR-019

Extends the contract the plural feature established in
[001-fix-plural-strings/contracts/localize-command.md](../../001-fix-plural-strings/contracts/localize-command.md).
Nothing already in that contract changes.

## What is added

A third per-locale collection alongside `incomplete` and `uncounted`. It follows the same path:
collected in `synchronize()`, returned in the result array, printed by its own report method, counted
into `missing` so `$outOfDate` is set.

## Reporting

A breach is named, never counted (FR-015). The report identifies the locale, the rule broken, and each
value that broke it, in the manner of the two reports already there:

```text
fr_FR has 3 value(s) written with a character French does not use:
  Supprimer l'image de :name ?
  ...
```

Each rule reports separately, so a value breaching two rules is named under both. Reporting the value
by its key, which is the English sentence, matches what `reportIncomplete()` and `reportUncounted()`
already do and is what makes the breach findable.

## Failure

- Under `monica:localize`, a breach is reported and the file is still written. The value is kept, so
  nobody's work is thrown away between finding the breach and fixing it. This is what the command
  already does for a phrase translated with the wrong number of wordings, and the reasoning is
  recorded in the comment there.
- Under `monica:localize --check`, a breach makes the command exit non-zero (FR-018). The suite runs
  `--check`, so a breach fails the build.

## What the check must never do

- **Rewrite a value.** FR-019. Not the apostrophe, not the space, not the quotation. It reports and a
  person decides.
- **Raise a breach against a placeholder.** A colon immediately followed by a letter or an underscore
  opens a placeholder. FR-016, and see [D7](../research.md) for why this is stated exactly rather than
  approximately.
- **Raise a breach against a counted-wording separator.** The `|` is structure. Each wording either
  side of it is judged as prose.
- **Raise a breach against a locale with no rules.** FR-017. Eight of the nine locales are in this
  state and all of them must stay green.
- **Judge the English source.** The keys are English sentences and English declares no typography.
  They pass because `en` has no `typography` key, by the same route as the other seven.

## What it must catch

Each of these is a test in `LocalizeCommandTest`, written the way the 28 already there are written:
a fixture lang path, a value written into it, and an assertion on the output.

| Case | Expected |
| --- | --- |
| `L'image` in `fr_FR` | Reported. Straight apostrophe. |
| `L’image` in `fr_FR` | Silent. |
| `Supprimer ?` with an ordinary space in `fr_FR` | Reported. |
| `Supprimer ?` with U+202F in `fr_FR` | Silent. |
| `Supprimer?` with no space in `fr_FR` | Reported. |
| `Sections :` with U+00A0 in `fr_FR` | Silent. |
| `Sections :` with U+202F in `fr_FR` | Reported. The narrow space is wrong before a colon. |
| `Supprimer :name ?` with U+202F before the `?` in `fr_FR` | Silent. The placeholder's colon is not punctuation. |
| `"promeneuse"` in `fr_FR` | Reported. Straight double quotes. |
| `« promeneuse »` with ordinary spaces in `fr_FR` | Reported. |
| `Ton coffre` in `fr_FR` | Silent. `ton` is not checked, and [D5](../research.md) says why. |
| `Tu peux` in `fr_FR` | Reported. |
| `:count personne\|:count personnes` in `fr_FR`, both wordings clean | Silent. |
| A wording after the `\|` breaching a rule | Reported. |
| The same offending value in `de_DE` | Silent. German declares no rules. |
| Any value in `en` | Silent. |
