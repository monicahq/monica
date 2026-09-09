# Implementation Plan: Magic link sign in behind one instance setting

**Branch**: `2026-09-09-login-link-setting` | **Date**: 2026-09-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/026-login-link-setting/spec.md`

## Summary

Remove the direct sign in shortcut: the `spatie/laravel-login-link` package, its config file, its block
on the sign in screen and the two view model methods that feed it. Put the three magic link routes
behind one middleware that answers not found while `monica.magic_link_enabled` is false, and hide the
offer on the sign in screen through the same setting. It is on by default, so an instance that never
sets it changes in nothing but the loss of the shortcut. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13.30.1

**Primary Dependencies**: one removed, `spatie/laravel-login-link` 1.6.7, which takes
`spatie/laravel-package-tools` with it since nothing else requires it. None added.

**Storage**: nothing touched. `config/monica.php` gains one key, read from `MONICA_MAGIC_LINK_ENABLED`.

**Testing**: PHPUnit. `tests/Feature/Controllers/App/Auth/MagicLinkControllerTest.php` for the gate,
`LoginControllerTest.php` beside it for the screen, `tests/Unit/ViewModels/Auth/LoginViewModelTest.php`
for the read. Middlewares have no unit tests in this codebase; the routes they guard test them.

**Target Platform**: the responsive web application. The magic link has no API surface.

**Project Type**: web application, server rendered Blade.

**Constraints**: the package's service provider registers `POST laravel-login-link-login`
unconditionally, so that route only disappears with the package. `MagicLinkCreated` builds a signed
URL to `auth.magicLink.show` from the queue, so the routes stay registered while the setting is off
and refuse at request time instead of not existing at boot.

**Scale/Scope**: three routes gated, one route removed, one setting, one middleware, one config file
deleted, six test files touched.

## Constitution Check

*GATE: passes before Phase 0 and after Phase 1.*

| Principle | How this feature satisfies it |
| --- | --- |
| I Actions hold the business logic | Nothing added. `CreateMagicLink` and `ConsumeMagicLink` are untouched. The gate is a middleware, and the screen reads the setting through `LoginViewModel`. |
| II Simplicity over abstraction | One middleware, one config key, and deletions. |
| III Tested before merged | Every gated route answers not found while off, an emailed link is not spent, the screen hides the offer, the view model reads the setting, the shortcut's request is gone. |
| IV Laravel conventions | The middleware is aliased `magic.link` in `bootstrap/app.php` and applied to a group. `env()` appears in `config/monica.php` only. |
| V Localized and safe | No new string. The removed block carried the one untranslated string on the screen. |
| VI Nothing changes under anybody's feet | Default on, so an upgrade changes nothing for a user. Turning it off is the operator's act, and a link emailed before it is refused, not spent. |
| IX Accessible to everybody | The sign in screen loses one box conditionally. Nothing new to audit. |
| X Everything goes through the API | The magic link is a credentials screen with no API counterpart, and the setting is instance configuration, not something done in the interface. |
| XI The written record is short | `data-model.md` omitted. The routes and the setting are one contract file. |
| Product invariants | A feature must not exist on one deployment only. The shortcut existed on local instances alone, and this removes it. |

## Project Structure

### Documentation (this feature)

```text
specs/026-login-link-setting/
├── plan.md
├── research.md
├── contracts/magic-link-setting.md
├── quickstart.md
└── checklists/requirements.md
```

`data-model.md` is omitted: no table changes, and the spec's Key Entities already name the three things involved.

### Source Code (repository root)

```text
config/monica.php                                    # magic_link_enabled, three lines of comment
.env.example                                         # MONICA_MAGIC_LINK_ENABLED=true, documented
app/Http/Middleware/EnsureMagicLinkIsEnabled.php     # new, answers 404 while the setting is off
bootstrap/app.php                                    # alias magic.link
routes/auth.php                                      # the three magic link routes grouped under it
app/ViewModels/Auth/LoginViewModel.php               # magicLinkIsEnabled(), example account methods gone
resources/views/app/auth/login.blade.php             # offer behind @if, @env block removed
composer.json, composer.lock                         # spatie/laravel-login-link removed
config/login-link.php                                # deleted
tests/Feature/Controllers/App/Auth/MagicLinkControllerTest.php
tests/Feature/Controllers/App/Auth/LoginControllerTest.php
tests/Feature/Controllers/App/Auth/LoginLinkTest.php # deleted
tests/Unit/ViewModels/Auth/LoginViewModelTest.php
.claude/skills/middlewares/SKILL.md                  # four middlewares, not three
.claude/CLAUDE.md                                    # same sentence
```

**Structure Decision**: nothing new outside `app/Http/Middleware`, which exists. Everything else edits or deletes a file named above.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Removing a dependency, which needs approval | The package registers its route unconditionally, and SC-004 wants that route gone everywhere | `dont-discover` in `composer.json` keeps a dead package and a config file naming a controller nobody reaches |
| A fourth middleware where the skill counts three | Three routes share one gate, and a magic link route added later must not escape it | `abort_unless` in the three controller methods, the screenshot link's pattern for its one route, is three copies here |
