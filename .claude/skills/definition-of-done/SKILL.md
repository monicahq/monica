---
name: definition-of-done
description: The checklist a change passes before it is committed or put in a pull request. Tests, translations, PHPStan, Pint, the changelog entry, and when the change reaches something a person can do, its API endpoint, API documentation, MCP tool and product documentation. Use before every commit, before every pull request, and when asked whether a change is finished.
---

# Definition of done

A change is done when every item below is either done or skipped for a stated reason. CI runs the
tests and PHPStan, so a change that passes its own tests alone still lands a red pull request.

Run this before every commit and before every pull request. Work through the items in order. When a
check fails, fix it, then start again from that check.

## 1. Find what changed

```bash
git status --porcelain --untracked-files=all
git diff --stat main...HEAD
```

The changed files are the modified, added and untracked ones, plus what the branch already carries.
Leave out what you did not write in this task, and say which files you left out.

## 2. The affected tests

Every changed class with something of its own to prove has a test, following
`.claude/rules/testing.md`. A test mirrors the class it tests (`app/Actions/X.php` is
`tests/Unit/Actions/XTest.php`, a controller is under `tests/Feature/Controllers`), and
`grep -rl ClassName tests` finds the others. Run them together:

```bash
php artisan test --compact <test files>
```

A changed class with no test fails this step, unless `.claude/rules/testing.md` says the class has
nothing of its own to prove.

## 3. The whole suite

```bash
composer test:parallel
```

Never `php artisan test --parallel` directly: under an agent, `laravel/pao` crashes the parallel
runner and the suite falls back to a serial run of 5 minutes instead of under a minute. The composer
script removes the variables that switch it on.

Skip this step only when the change touches no PHP, no Blade, no route and no configuration (a
change to Markdown or to `.claude/` alone).

## 4. Translations

```bash
php artisan monica:localize --check
```

When it reports a missing string, run `php artisan monica:localize` and translate what it reports in
every configured locale, following the `translations` skill and `.claude/rules/translations.md`.
Never edit a key in `lang/*.json` by hand.

## 5. PHPStan

```bash
vendor/bin/phpstan analyse --no-progress
```

It must report nothing. Fix the code, never the baseline, unless the user asks.

## 6. Pint

```bash
vendor/bin/pint --dirty --format agent
```

It must leave nothing to fix. If it changed a file, run step 2 again for that file.

## 7. The changelog entry

A change somebody using Monica would notice carries its entry in `changelog/`, written as the
`changelog` skill says. A change nobody using Monica would notice (a refactoring, a test, a rule for
agents) carries none, and the report says so.

## 8. The API endpoint

Constitution principle X: everything that can be done in the interface must be doable through the
API, and a capability is not complete until its API coverage exists and is tested.

When the change adds or modifies something a person can do in the interface (create, change,
delete, reorder, move, a new field on a record), the matching endpoint in `routes/api.php` exists and
answers the same way, with its controller test. Build it with the `api-writer` skill.

Skip it when the change does not alter what a person can do: a visual change, copy, a fix that
restores the behaviour the API already has, an internal refactoring.

## 9. The API documentation

When step 8 added or changed an endpoint or a field, the reference at `/docs/api` describes it and
`resources/docs/api-changelog` carries its entry, both written as the `api-docs-writer` skill says.

Skip it when step 8 was skipped.

## 10. The MCP tool

When step 8 added an endpoint, the tool that mirrors it exists under `app/Mcp/Tools` and answers
through the API controller, as `.claude/rules/mcp.md` says. When step 8 changed an endpoint, the
tool's schema and description still match it.

Skip it when step 8 was skipped.

## 11. The product documentation and the help bubble

When a screen, a form, a setting or a concept a reader has to understand is new or behaves
differently, the product guide says so, written as the `writer` skill says, and the screen carries
the help bubble the `help-bubbles` skill asks for.

Skip it when what the reader sees and understands did not change.

## Report

One line per item, numbered as above. Each line says one of three things:

- **done**, with what was run or written;
- **failed**, with the first error;
- **skipped**, with the reason.

An item you did not run or check is never reported as done.
