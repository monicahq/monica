# Implementation Plan: Contact sort names follow each language

**Branch**: `claude/contact-sort-name-languages-3b809f` | **Date**: 2026-11-21 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/145-contact-sort-name-languages/spec.md`

## Summary

A contact's sort name is built by a new `NameFiling` helper from a rule its reader's language declares
in `config/localization.php`: which part comes first, which particles are skipped, and whether a kana
reading decides. The name is lowercased with that language's own rules. A language change and the
upgrade both refile every contact through `RebuildOrderingKeys`, one account at a time, all or
nothing. Search is untouched, no column is added, and no screen changes. The decisions and the table
of languages are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (latest)
**Primary Dependencies**: `ext-intl` (`Collator`, `Transliterator`), already a hard dependency since 003
**Storage**: existing `contacts.sort_name`, `sort_key` and `search_key`; one data migration, no schema change
**Testing**: PHPUnit, `PAO_DISABLE=1 php artisan test --parallel`
**Target Platform**: web application and its JSON API, MCP through the API
**Project Type**: web application (single Laravel project)
**Performance Goals**: no read path changes; one more query per contact save for Japanese accounts only
**Constraints**: all or nothing per account (FR-009, FR-010); search byte for byte as today (FR-008)
**Scale/Scope**: one helper, a config key on 12 locales, two model hooks, one action, six `orderBy`, one migration

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. The rule is a helper read by the model hook and by `RebuildOrderingKeys`, as `LocaleCollation` is today. Nothing new is written outside them |
| II. Simplicity | Pass. One helper, one config key. No class per language |
| III. Tested before merged | Pass. Tests listed below |
| IV. Laravel conventions | Pass, with a note. Config in `config/`, data migration with `up()` only. The migration calls an action, against the migrations skill (Complexity Tracking) |
| V. Localized and safe | Pass. This implements its sentence on the order of given and family name. The only new strings are the What's new entry, translated in every locale |
| VI. Nothing changes under anybody's feet | Pass, with a note. No record changes. The order of the people list changes at upgrade for accounts reading in is, si, ta, th and vi (part), nl, de, fr, es and pt (particles), tr (case) and ja (readings). 003 reordered every list the same way. Announced in What's new |
| VII. Stable core | Pass. Contact gains nothing personalisable |
| VIII. Nothing disappears silently | Pass. No name is rewritten |
| IX. Accessible | Pass. No markup changes |
| X. Through the API | Pass. The API list reads the same key and follows. No new capability to expose |
| XI. Short written record | Pass. `data-model.md` and `contracts/` are omitted: no schema or interface changes, and R1 holds the config shape |

Post-design re-check: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/145-contact-sort-name-languages/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
config/localization.php                                # + name_filing on 12 locales (research R2)
app/
├── Helpers/NameFiling.php                             # new: sortName() from the parts, a reading and a locale
├── Helpers/LocaleCollation.php                        # + lower(), public, over the existing transliterator
├── Models/Contact.php                                 # saving: sort_name from NameFiling, search_key from the text it has today, each a method
├── Models/ContactNameRepresentation.php               # saved and deleted: re-save the contact when not the displayed one
├── Actions/RebuildOrderingKeys.php                    # contacts: sort_name and both keys through those two methods, readings eager loaded
├── ViewModels/Vaults/                                 # orderBy sort_key: RelationshipFormViewModel, FieldChoicesViewModel (contacts),
│                                                      #   GroupMemberCandidatesViewModel, PossessionLoanContactCandidatesViewModel
└── Http/Controllers/App/Vaults/                       # orderBy sort_key: ContactLifeEventCandidateController, VaultLifeEventCandidateController
database/migrations/…_backfill_contact_sort_names.php  # RebuildOrderingKeys per account, one transaction each
tests/
├── Unit/Helpers/NameFilingTest.php                    # new: five names for every locale of lang/, so a new one fails until it has a row
├── Unit/Helpers/LocaleCollationTest.php               # lower() keeps the Turkish letters apart (SC-002)
├── Unit/Models/ContactTest.php                        # sort name per rule; search key unchanged by the rule (FR-008)
├── Unit/Models/ContactNameRepresentationTest.php      # a kana reading saved or deleted refiles the contact
├── Unit/Actions/RebuildOrderingKeysTest.php           # a language change refiles; a rebuilt search key equals a saved one
├── Unit/Actions/UpdatePreferencesTest.php             # a failed rebuild leaves language, sort names and keys as they were
└── Unit/Migrations/BackfillContactSortNamesTest.php   # new: every account refiled, an untouched one unchanged
```

**Structure Decision**: existing single Laravel project. `NameFiling` sits beside `LocaleCollation`,
which it calls, and every other file exists already.

## Delivery checklist (from `definition-of-done`)

- No screen gains a string. The What's new entry is translation keys: `monica:localize`, then every locale, before `--check`.
- API: no new field. Correct any sentence of the API docs saying the list is ordered by family name,
  and add an API changelog entry for the order.
- Changelog entry (What's new) naming the languages whose list order changes.
- Product docs: say where the people list is described that the order follows the interface language.
- `vendor/bin/pint --dirty --format agent`, PHPStan, the whole suite (pgsql is verified by CI).

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| The migration calls `RebuildOrderingKeys`, where the migrations skill asks for the query builder | The filing rule must exist once, and an account is refiled all or nothing (research R7) | Re-saving each contact, as `backfill_contact_match_keys` does, leaves an account half refiled when it stops |
