# Implementation Plan: Locale resolution modelled on laravel-localizer

**Branch**: `006-import-laravel-localizer` | **Date**: 2026-08-31 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/006-import-laravel-localizer/spec.md`

## Summary

Rewrite `App\Http\Middleware\SetLocale` so that a reader's language is resolved from five sources
instead of three, and give the API a second middleware that reads the same sources in its own order.
Two behaviours the product does not have today come with it: a first time visitor lands in the
language their browser announces, and a signed out reader's choice survives the end of their session.

What comes from `asbiin/laravel-localizer` is the shape of the answer. A language is resolved by
walking an ordered list of sources and taking the first that yields something the product actually
ships, then recording that answer in several places at once. No file is copied. The pluggable
registry the package uses to express this, because a package cannot know its host's order, collapses
here into two named sequences, since this product knows both of its orders and SC-012 asks that no
third be configurable.

Nothing is added to `composer.json`. `$request->getLanguages()` already parses `Accept-Language`,
sorts by quality weight and returns values in this project's own underscore form, so the header work
is done. What is left is mapping a value the product does not ship onto one it does, which is the only
genuinely new logic in the feature.

Three files land, one is rewritten in place, and four are edited. Part of the work is already
committed: `6fa1e09` took `set.locale` off every route and put `SetLocale` in the `web` group.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: none added. Laravel 13.8, already present, provides the `Accept-Language`
parsing, the session, the cookie queue and the request the resolver reads.

**Storage**: no schema change and no migration. `users.locale` (`string(5)`, nullable) already holds
an account preference and already fits `fr_FR`. The session key stays `locale`, so sessions written
before this change keep resolving after it. One new cookie, `locale`.

**Testing**: PHPUnit. One new unit test for the resolver, two new feature tests for the two orders,
two existing controller tests extended.

**Target Platform**: Linux server, the same nine locales, no new browser requirement.

**Project Type**: server rendered Laravel web application with a JSON API beside it.

**Performance Goals**: none worth a number. Resolving a language reads a session key, a column already
loaded with the authenticated user, a cookie and a parsed request header, and compares against a nine
entry array. It replaces work of the same shape.

**Constraints**: no new top level folder, so no amendment to the fixed layout. No third party text in
any file. `config('monica.locales')` stays the only definition of what the product ships. Nothing the
reader can see changes beyond what the spec describes.

**Scale/Scope**: nine locales, five sources, two request surfaces, three files added, one rewritten,
four edited. Roughly 190 lines of application code and 360 of tests.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict | Note |
| --- | --- | --- |
| I. Actions hold the business logic | Pass, not engaged | Nothing here is a user operation on a record. Resolving a request's language is transport, which is what middleware is for, and the one action this touches (`UpdatePreferences`) is unchanged. |
| II. Simplicity over abstraction | Pass, and this is where the design earns it | One class with a method per source, two middlewares naming their order, no interfaces and no registry. The alternative, mirroring the package's arrangement, was thirteen classes for the same observable behaviour and was rejected in clarification. |
| III. Tested before merged | Pass, and improves | `app/Http/Middleware/SetLocale.php` has no test today: nothing under `tests/` names it or `set.locale`. Its replacement arrives with every source, the precedence between them, both orders, and the rejection of a value the product does not ship. |
| IV. Laravel conventions are the default | Pass | Middleware in a group, the request injected rather than reached for, no facade of our own, no service locator. Typed properties and returns, `declare(strict_types=1)`, early returns, no `else`, one class per file. |
| V. Localized and safe user experience | Pass, and is the point | No new user facing string, so `monica:localize` reports nothing, and it is run to prove that. No destructive action. |
| VI. Nothing changes under anybody's feet | Pass | The principle most at risk, so read it carefully. No stored preference is written, moved or reinterpreted. The session key does not move, so a live session keeps its language across the deploy. A first time visitor may now land in their own language, which is a default filling a place where the product previously had no answer, not a change to something somebody chose. |
| VII. Stable core | Pass, not engaged | Adds no concept. |
| VIII. Nothing disappears silently | Pass | One middleware of ours is rewritten in place. |
| IX. Accessible to everybody | Pass | No screen changes. Landing in a language the reader actually reads is an accessibility gain, and the switcher stays where it is for anybody the guess is wrong for. |
| X. Everything goes through the API | Pass, and repairs | `6fa1e09` took `set.locale` off `routes/api.php` without putting anything in its place, so on `main` today the API's translated messages resolve to whatever the previous request left behind. This plan gives the API its own order. |

**The layout gate passes.** `app/Helpers` and `app/Http/Middleware` are both folders the constitution
names, so no new top level folder and no amendment. This is a change from the two earlier plans, which
both required amending the constitution, and it follows directly from dropping the registry.

Development workflow gates: branch `2026-08-31-import-laravel-localizer` off an up to date `main`,
Conventional Commits title reading as one short outcome, no dash punctuation in any prose written
here, Pint clean, PHPStan reporting nothing new, `monica:localize` run, and no mention of the tool that
wrote it in the pull request. `asbiin/laravel-localizer` named in the pull request as the source of the
design, which the spec asks for.

## Project Structure

### Documentation (this feature)

```text
specs/006-import-laravel-localizer/
├── plan.md              # This file
├── spec.md              # The specification, clarified three times
├── research.md          # Phase 0: what is borrowed, what is dropped, and what the framework already does
├── data-model.md        # Phase 1: the five sources and the shipped locale
├── quickstart.md        # Phase 1: how to prove it works
├── contracts/
│   ├── locale-resolution.md      # The two orders, the resolver's surface, what writes back
│   └── api-language.md           # What an API caller may ask for and receive
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2, written by /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Helpers/
│   └── LocaleResolution.php               # NEW. A method per source, a method per order, a method to record.
├── Http/
│   ├── Controllers/App/
│   │   ├── Auth/LocaleController.php      # EDIT. Records the choice in one call instead of one.
│   │   └── Settings/Account/Preferences/
│   │       └── PreferenceController.php   # EDIT. Records the choice in one call instead of two.
│   └── Middleware/
│       ├── SetLocale.php                  # REWRITTEN in place. Runs the web order.
│       └── SetApiLocale.php               # NEW. Runs the API order.
config/
└── monica.php                             # EDIT. One optional key, naming which Portuguese answers `pt`.
bootstrap/
└── app.php                                # EDIT. Register the API middleware. The web registration is already committed.
tests/
├── Unit/Helpers/
│   └── LocaleResolutionTest.php           # NEW. Every source, both orders, the matching table.
├── Feature/Localization/
│   ├── WebLocaleResolutionTest.php        # NEW. The web order through real requests.
│   └── ApiLocaleResolutionTest.php        # NEW. The API order, and that nothing is written back.
└── Feature/Controllers/App/
    ├── Auth/LocaleControllerTest.php      # EDIT. Now also asserts the cookie.
    └── Settings/Account/Preferences/
        └── PreferenceControllerTest.php   # EDIT. Same.
```

**Structure Decision**: everything lands in a folder the constitution already names.
`app/Helpers/LocaleResolution.php` sits beside `LocaleCollation.php`, which is the existing home for
locale machinery that is neither an action nor a model, and which this class most resembles: one
subject, static or near static, reached from a small number of places.
`app/Http/Middleware/SetLocale.php` keeps its name and path, so `bootstrap/app.php` needs no change to
its import and the file's history stays attached to the thing it does. `tests/Unit/Helpers/` mirrors
the application namespace, which the testing rules require. `tests/Feature/Localization/` is new, which
the constitution permits: it fixes the three top level test folders and leaves what sits inside
`Feature` to follow the code, as `Feature/Auth` and `Feature/Translation` already do.

## Complexity Tracking

No constitution violation to justify. Two choices cost something and are recorded so a reviewer can
disagree with them rather than discover them.

| Choice | Why | Simpler alternative rejected because |
|-----------|------------|-------------------------------------|
| A resolver class rather than the logic inside the two middlewares | The five sources are shared between the two orders, and the two controllers need the recording half without the reading half. Putting the sources in one class is what stops the second middleware being a copy of the first. | Two self contained middlewares, each reading what it needs, is fewer files and duplicates four of the five sources. It is worse the moment a source changes and only one of the two follows. |
| A five source chain at all, rather than extending the current three | FR-002 and FR-013 name five between them, and the two new ones are the point of the feature. | Nothing simpler reaches the requirements. The current middleware already does this for three sources with `??`, and the new class is that same idea with two more sources and a second order. |

## Constitution Check, after Phase 1 design

Re-run against the artifacts rather than the intention. No verdict changed, and unlike the two earlier
plans for this feature, no gate is conditional.

- **Layout**: nothing new outside a folder the constitution names. `app/Helpers` and
  `app/Http/Middleware` both exist. No amendment, which is the practical dividend of dropping the
  registry. `tests/Feature/Localization/` is inside `Feature`, which the constitution leaves open.
- **One class per file**: three files, three classes, and the file being replaced keeps its path.
- **Simplicity**: one resolver, two middlewares, no interfaces, no registry, no config file. The
  invariant that every source returns a shipped key or nothing is what keeps the resolver honest without
  a validation layer, and it is worth reading the contract for that one point alone.
- **Tests**: three new files and two extended. The unit test carries the matching table, the shipped
  invariant on all five sources, both orders, every adjacent precedence pair, and `remember()`. The two
  feature tests prove the orders survive a real request and that the API writes nothing back. This is
  strictly more coverage than exists today, where the behaviour has none.
- **Migrations**: none, so the rule about `up()` never comes up.
- **Localization**: no new string. `monica:localize --check` reporting nothing is the assertion.
- **API coverage**: the feature is transport, so the question of where its logic sits does not apply.
  The surface it touches is repaired rather than extended, and research R11 records that the repair is
  of a defect currently on `main`.
- **Provenance**: no third party text and no licence notice. SC-013 is a checked outcome and a reviewer
  item, since no command can assert it.

Two things a reviewer should push on if they disagree, both in Complexity Tracking: whether the resolver
deserves to be its own class rather than living in the two middlewares, and whether `app/Helpers` is the
right home for it. Neither changes behaviour.

**Gate: passed.** Ready for `/speckit-tasks`.
