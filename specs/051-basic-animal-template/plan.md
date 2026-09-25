# Implementation Plan: Basic animal template

**Branch**: `2026-09-25-basic-animal-template` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

`CreateDefaultTemplate` learns to seed any `DefaultTemplateEnum` (R1), and `BasicAnimal` brings its
entity type (R3), page, four sections and twelve fields ([data-model.md](data-model.md)). It becomes
retry safe and never replaces a vault default (R4). `CreateVault` sets the Animal default next to
the contact one. Caregivers refuse the same contact twice through a field option (R7), and a row now
refuses an empty required child. A command backfills existing accounts
([contract](contracts/seed-animal-template-command.md)). No schema change.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)

**Primary Dependencies**: none added

**Storage**: rows only, no migration (data-model.md)

**Testing**: PHPUnit: the job, `CreateVault`, `SetFieldValue`, `SetFieldValues`,
`CreateDefaultValueLists`, the command

**Target Platform**: account creation (queued chain), vault creation, Artisan

**Project Type**: server rendered web application

**Performance Goals**: about 40 rows per account, inside the existing seeding chain

**Constraints**: one transaction per account; stable keys (R2, R6); every supplied string translated

**Scale/Scope**: 3 enums edited, 1 enum added, 1 job, 4 actions, 1 command, about 25 strings

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | Seeding stays in its job, as Basic contact's does; the rules sit in `SetFieldValue(s)` |
| II. Simplicity over abstraction | Pass | One job parameter instead of a copy (R1) |
| III. Tested before merged | Pass | Every behaviour in the Tests table |
| IV. Laravel conventions | Pass | Command with attributes like `MakeAdministratorCommand`; no migration |
| V. Localized and safe | Pass | Translation keys for the template; the type's names in the user's locale (R3); nothing destructive |
| VI. Nothing changes under anybody’s feet | Pass | Version 1 published once; a chosen vault default is kept (R4); edited templates are never touched |
| VII. Stable core | Pass | Built from Template, Page, Section, Field; animals stay entities |
| VIII. Nothing disappears silently | Pass | Nothing removed; the backfill reports every skip |
| IX. Accessible to everybody | Pass | No screen changes |
| X. Everything goes through the API | Pass | Types, templates and field options are already in the API; the command is administration |
| XI. The written record is short | Pass | spec 120, plan under 100, research under 80 |

Re-check after Phase 1: unchanged.

## Project Structure

```text
specs/051-basic-animal-template/  spec  plan  research  data-model  contracts/  checklists/
```

```text
app/Enums/DefaultTemplateEnum.php     # BasicAnimal, entity type details
app/Enums/DefaultPageEnum.php         # AnimalGeneral
app/Enums/DefaultSectionEnum.php      # four animal sections
app/Enums/DefaultFieldEnum.php        # animal fields, children, value list, required, options
app/Enums/ValueListEnum.php           # AnimalSexes
app/Enums/AnimalSexEnum.php           # new
app/Models/EntityType.php             # 'pet' in ICONS
app/Jobs/CreateDefaultTemplate.php    # supplied parameter, type, guard, firstOrCreate
app/Actions/CreateAccount.php         # chain BasicAnimal after BasicContact
app/Actions/CreateVault.php           # seed the Animal default
app/Actions/CreateDefaultValueLists.php  # lists parameter
app/Actions/SetFieldValue.php         # distinct_in_list
app/Actions/SetFieldValues.php        # required child in a row
app/Console/Commands/SeedAnimalTemplateCommand.php  # new
tests/…                                # mirrors each file above
lang/*.json                            # through monica:localize
```

## Tests

| Layer | Proves |
| --- | --- |
| Job | type then template; the published contents of data-model.md; restricted to the type; vault defaults; a second run writes nothing; a chosen default kept; another account untouched; Basic contact unchanged |
| `CreateVault` | a new vault gets both defaults; none when the account has no Basic animal |
| `SetFieldValue` | a second value naming the same contact is refused with the option, allowed without |
| `SetFieldValues` | a caregiver row with a role and no person is refused; an empty row is dropped |
| `CreateDefaultValueLists` | `lists:` adds only what is missing |
| Contact form view model | Basic animal is not offered |
| Command | seeds, repeats without duplicates, skips on a held slug, keeps an edited template, unknown account fails |

## Complexity Tracking

None.
