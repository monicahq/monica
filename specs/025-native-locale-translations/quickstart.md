# Quickstart: adding one locale

The procedure is the `.claude/skills/add-locale` skill, with the framework files and the glossary
added. One locale per branch, one locale per pull request.

## Prerequisites

The groundwork change has merged: the locale columns are `string(12)`, the sentinels have moved off
`sv_SE`, the pickers sort and scroll, `lang/en/` is published, and the glossary exists.

## Steps

1. Add the entry to `config/monica.php`, following
   [contracts/locale-registration.md](contracts/locale-registration.md).
2. `php artisan monica:localize`. It creates `lang/<locale>.json` holding `{}` and reports the count
   it is missing.
3. Translate every key `lang/en.json` holds. Read the glossary first, and
   `.claude/rules/translations.md` for the language's own conventions if it has a section.
4. Copy `lang/en/` to `lang/<locale>/` and translate the 148 framework strings.
5. `php artisan monica:localize` again, then `php artisan monica:localize --check`.
6. Record the locale in the review table of `.claude/rules/translations.md`, as reviewed or awaiting
   a reviewer.

## Verification

```bash
php artisan monica:localize --check
php artisan test --compact tests/Unit/Translation
php artisan test --compact tests/Unit/Console/Commands/LocalizeCommandTest.php
php artisan test --compact tests/Unit/Helpers/LocaleResolutionTest.php tests/Feature/Localization
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
```

Expected: the check reports the locale files are up to date, and the table shows the new locale with
nothing missing.

By hand, with the locale selected:

- Walk the signed in screens, the credentials screens and one email. No English sentence.
- Submit a form with a field left empty. The error reads in the locale, not in English.
- Read the language picker. The locale appears with its own name, its region and its flag, in the
  sorted list, and the popup scrolls rather than running off screen.
- Open a screen showing a date written with a month name, and one showing a relative time.
- Render a counted phrase at 0, 1, 2, 5 and 21.
- Zoom to 200%. Nothing overflows or truncates.

## What failure looks like

- `needs N wording(s) for each phrase that shows a number` means a counted phrase has the wrong
  number of segments for this language. The count is in [research.md](research.md).
- `whose wording shows a number without :count in it` means a segment spells the digit.
- `The locale files are out of date` with a non-zero Missing column means keys are still untranslated.
- A type error in `CountedPhraseTest` means `lang/<locale>.json` does not exist yet.
