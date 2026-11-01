---
name: definition-of-done
description: Run the four checks CLAUDE.md requires before a change is done (affected tests, monica:localize --check, PHPStan, Pint) and report the first one that fails. Use before committing, before opening a pull request, or when asked whether a change is finished.
disable-model-invocation: true
---

# Definition of done

CLAUDE.md says a change is done when four checks hold. CI runs the tests and PHPStan, so a change
that passes its tests alone still lands a red pull request. Run the checks in this order and stop at
the first one that fails: fix it, then start again from that check.

## 1. Find what changed

```bash
git status --porcelain --untracked-files=all
```

The changed files are the modified, added and untracked ones. Leave out what you did not write in
this task, and say which files you left out.

## 2. The affected tests

Find the tests that cover each changed file. A test mirrors the class it tests (`app/Actions/X.php`
is `tests/Unit/Actions/XTest.php`, a controller is under `tests/Feature/Controllers`), and
`grep -rl ClassName tests` finds the others. Run them together:

```bash
php artisan test --compact <test files>
```

Run the whole suite instead (`php artisan test --compact --parallel`) when the change touches a
shared component under `resources/views/components`, a layout, a trait under `app/Models/Concerns`,
`routes/*.php`, a middleware, a migration or `tests/TestCase.php`. `.claude/rules/components.md`
says why.

A changed class with no test is a failure of this step, unless `.claude/rules/testing.md` says the
class has nothing of its own to prove.

## 3. Translations

```bash
php artisan monica:localize --check
```

When it reports a missing string, run `php artisan monica:localize` and translate what it reports in
every configured locale, following the `translations` skill and `.claude/rules/translations.md`.
Never edit a key in `lang/*.json` by hand.

## 4. PHPStan

```bash
vendor/bin/phpstan analyse --no-progress
```

It must report nothing. Fix the code, never the baseline, unless the user asks.

## 5. Pint

```bash
vendor/bin/pint --dirty --format agent
```

It must leave nothing to fix. If it changed a file, run step 2 again for that file.

## Report

One line per check: passed, failed (with the first error), or skipped (with the reason). A check
you did not run is never reported as passed.
