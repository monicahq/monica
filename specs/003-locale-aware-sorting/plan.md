# Implementation Plan: Locale Aware Sorting

**Branch**: `2026-08-27-locale-aware-sorting` | **Date**: 2026-08-27 | **Spec**: [spec.md](spec.md)

## Summary

Lists read in the alphabet of whoever is looking, and searching folds what that language
calls a minor difference. Ordering runs on a stored binary collation key, so paging stays
index backed and reads the same on all three supported databases. Searching runs on a stored
folded text column. Both keys are derived and both are rebuilt in one transaction when the
reader changes language. ICU is the only authority on what a language does: no alphabet,
accent or letter is written down in the codebase.

The one exception is the marketplace catalogue, shared across accounts and so with no single
language to precompute for. Its name order is worked out when it is read.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 13.8. `ext-intl` becomes a hard dependency, verified at
ICU 74.2. It is used nowhere in the application today, so this feature introduces it.

**Storage**: SQLite, MySQL, PostgreSQL. Two derived columns on `contacts` and `entities`,
which already carry a `sort_name`.

**Testing**: PHPUnit.

**Target Platform**: Linux server. Self hosted and hosted run the same product, so the
dependency applies to both.

**Project Type**: Server rendered web application (Blade, Tailwind, Alpine, Alpine Ajax).

**Performance Goals**: Paging stays index backed and does not get more expensive as a vault
grows. A list page renders within a tenth of its current time.

**Constraints**: Ordering must be byte comparable to be identical across the three databases.
Measured worst case key for a 511 character name is 1546 bytes, inside MySQL's 3072 byte
index limit, so no prefix index is needed. A rebuild is all or nothing.

**Scale/Scope**: Nine locales. One user per account, so one language and one stored key per
entry, except in the shared catalogue. Two models carry sortable names. Three alphabetical
surfaces: the vault contact list, the marketplace catalogue, the administrator filter.

## Constitution Check

*GATE: must pass before Phase 0. Re-checked after Phase 1 design, unchanged.*

| Principle | Verdict |
|---|---|
| I. Actions hold the business logic | Pass. The rebuild is a write, so it is an action. No view model may build or refresh a key. |
| II. Simplicity over abstraction | **Fails today, resolved by the merge.** `app/Helpers/CollectionSorter.php` is declared `final`, which the constitution forbids. The file is absorbed into `LocaleCollation` and deleted, so the `final` goes with it. |
| III. Tested before merged (NON-NEGOTIABLE) | **Fails today, resolved by the merge.** The staged `CollectionSorter` has no test, while `tests/Unit/Helpers/` holds nine. Its behaviour is covered by `LocaleCollationTest` once absorbed. |
| IV. Laravel conventions | Pass. Migrations write `up()` only, declare no `constrained()`. One class per file. |
| V. Localized and safe | Pass. New copy goes through `__()` and `monica:localize` in all nine locales. |
| VI. Nothing changes under anybody's feet | Pass. A rebuild changes derived order, never content, and only on the reader's own explicit action. |
| VII. Stable core | Pass. No new concept, no second personalisation mechanism. |
| VIII. Nothing disappears silently | Pass. A failed rebuild rolls back; a missing dependency is refused loudly at install. |
| IX. Accessible (NON-NEGOTIABLE) | Pass. Headings stay text, no meaning in colour, the empty search state is copy rather than a blank region. |
| X. Everything goes through the API | Pass. All logic in helpers and actions, so the API adds only transport. Nothing written as though `Api` exists. |
| Repository layout is fixed | Pass. Four files across existing folders. No new top level folder. |

Both failures are on code already staged, not on anything this plan proposes. Both are cheap
and both are the first tasks. The gate is not blocked, but it is not clean either.

## Project Structure

```text
app/Actions/RebuildOrderingKeys.php     # new: rebuilds both keys for an account
app/Actions/UpdatePreferences.php       # existing: calls it inside its transaction
app/Helpers/LocaleCollation.php         # new: the single ICU helper. Absorbs the staged
                                        #   CollectionSorter, adds the collation key, the
                                        #   search fold and the heading letter, and owns
                                        #   the collator cache
app/Helpers/CollectionSorter.php        # deleted, absorbed into LocaleCollation
app/Providers/AppServiceProvider.php    # the sortByCollator macro, the only way in
app/Models/Contact.php                  # sets both keys where it sets sort_name
app/Models/Entity.php                   # same
app/ViewModels/Vaults/ContactsViewModel.php          # orders and pages on the key
app/ViewModels/Marketplace/MarketplaceViewModel.php  # name order in PHP, no key to store
app/ViewModels/Admin/AdministratorLogsViewModel.php  # ordered through the macro
database/migrations/                    # two columns on contacts, two on entities
tests/Unit/Helpers/LocaleCollationTest.php
tests/Unit/Providers/AppServiceProviderTest.php
tests/Unit/Actions/RebuildOrderingKeysTest.php
tests/Feature/Controllers/              # order, headings, search, per locale
```

**Structure Decision**: The existing Laravel layout, unchanged. One helper rather than two:
`LocaleCollation` absorbs the staged `CollectionSorter`, which is then deleted.

Both classes need the same thing, a `Collator` configured for a locale and cached, and two
classes would mean two caches of the same objects and two places to remember that French needs
its attribute set. Merging leaves one file that owns everything ICU, which is what the contract
already asked for when it said no other file may construct a `Collator` or a `Transliterator`.
Principle II, simplicity over abstraction, points the same way.

This reverses the earlier recommendation in this plan, which argued for two files on the grounds
that "order this collection I am holding" and "what does this language say about this string" are
different questions. They are, but they are answered from the same cached object, and splitting
them bought a second cache rather than a clearer boundary.

## Decisions settled during implementation

The two columns went into the existing create migrations rather than a new one. There are no
tags, no `CHANGELOG.md` and no version field, so the project is pre-production and its
convention is to edit existing migrations until it is not. Anyone holding local data runs
`php artisan migrate:fresh`.

## Complexity Tracking

| Violation | Why needed | Simpler alternative rejected because |
|---|---|---|
| Two derived columns per model, not one | Ordering needs a binary key that byte compares; searching needs foldable text `like` can match inside. | A folded text column cannot express French accent from the end and loses the Turkish and Spanish letter distinctions. A binary key cannot be substring matched. |
| The marketplace orders when read, unlike everything else | It is one catalogue read in nine languages, so there is no single language to build a key for. | A stored key would have to pick one reader's language and be wrong for the other eight. Leaving it byte ordered was the alternative, and it filed accented names past Z. |
| A new hard dependency on `ext-intl` | Nine alphabets cannot be written down correctly, and ICU already knows them. | Hand written rules were rejected after the probes proved the author's own expectations wrong twice. |

## Constitution Check, re-evaluated after Phase 1 design

Nothing in the design moved a verdict. Confirmations and one addition:

- **Principle I holds.** The only write is `RebuildOrderingKeys`, an action. No view model
  builds, refreshes or repairs a key, which is the rule most likely to be broken by somebody
  fixing a bug in a hurry.
- **Principle II.** The two mechanisms, a stored key inside an account and a read-time order
  for the shared catalogue, exist because a shared list has no one language to store for.
  That is two mechanisms for a real reason rather than for symmetry.
- **Principle VI.** A rebuild changes derived order, never content, and only on the reader's
  own explicit action.
- **Principle VIII.** A failed rebuild rolls the language back with it, and a missing
  dependency is refused loudly at install.
- **Principle X.** Every decision about a language sits in a helper reached through a macro,
  so an API controller adds only transport.
- **Repository layout unchanged.** No new top level folder.
