# Quickstart: French Translation Rules

**Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md) | **Phase**: 1

How to run this feature and prove it works. Written to be followed in order, top to bottom, on a clean
working tree.

## Before you start

Measure the file, so you know what the correction is supposed to change:

```bash
python3 -c "import json,re;fr=json.load(open('lang/fr_FR.json'));print('straight apostrophe:',sum(1 for v in fr.values() if chr(39) in v));print('breaking space before punctuation:',sum(1 for v in fr.values() if re.search(r'(?<=[^\s  ]) [;!?]|(?<=[^\s  ]) :(?![A-Za-z_])',v)))"
```

On the shipped file this prints 259 and 74. Both must read 0 when you are done.

## 1. Declare the rules

Add the `typography` array to the `fr_FR` entry of `config/monica.php`, in the shape given by
[contracts/typography-config.md](./contracts/typography-config.md). Leave the other eight locales
alone. Confirm the config loads and the other locales stay empty:

```bash
php artisan config:show monica.locales.fr_FR.typography
```

## 2. Write the document

Create `.claude/rules/translations.md` with one section per configured locale, French filled in and the
other eight saying their rules are unwritten. Link it from `.claude/rules/index.md`, and point the
`translations` and `add-locale` skills at it.

The document names both space characters by code point, because neither is visible. See
[D4](./research.md).

## 3. Teach the command to refuse

Extend `LocalizeCommand` as described in [contracts/localize-command.md](./contracts/localize-command.md).
The check runs against every locale that declares rules, and the eight that do not must stay silent.

Run the command's own tests first, since they are fast and they cover every rule:

```bash
php artisan test --compact tests/Unit/Console/Commands/LocalizeCommandTest.php
```

The command will now fail against the shipped French file, which is correct. It has roughly 340
breaches and nothing has corrected them yet:

```bash
php artisan monica:localize --check
```

## 4. Correct the file

A one-off script, run once, not committed. It rewrites values in `lang/fr_FR.json` only, applying the
four rules in [data-model.md](./data-model.md) and honoring the placeholder exclusion from
[D7](./research.md).

The quotation rule cannot be done blindly: turning straight double quotes into guillemets means
deciding what was being quoted. There is one such value. Do that one by hand.

Then let the command normalise the file:

```bash
php artisan monica:localize
```

Re-run the measurement from the top of this page. Both numbers must be 0.

## 5. Prove nothing changed meaning

The correction touches characters, never words. Confirm that the key set is untouched and that every
value differs from its original only in the characters the rules govern:

```bash
git diff --stat lang/fr_FR.json
```

One file, and the changed-line count should be close to 340. If `en.json` or any other locale appears
in the diff, something has gone wrong: FR-014 and the last Assumption both say this feature touches
only the French side.

## 6. Run the checks

```bash
php artisan monica:localize --check
```

Passes, with no value exempted and nothing recorded as tolerated. That is SC-010.

```bash
vendor/bin/pint --dirty --format agent
```

```bash
php artisan test --compact
```

The whole suite, because the correction changed 340 strings and a feature test that asserts on French
copy would notice. This is the run that catches it.

## 7. Look at it

The defect Story 1 exists to remove is only visible on a narrow screen. Open a screen carrying one of
the corrected strings in French, at a phone width, and confirm no punctuation mark begins a line on
its own:

```bash
composer run dev
```

`Supprimer « :name » ?` with a long name substituted is the best single case, because it exercises the
guillemets and the question mark at once.

## What done looks like

- `php artisan monica:localize --check` passes.
- The two measurements read 0.
- The full suite passes.
- A value written with a straight apostrophe fails the check, and a reviewer can point at the written
  rule it breaks.
- `de_DE` and the six other unruled locales are untouched and still pass.
