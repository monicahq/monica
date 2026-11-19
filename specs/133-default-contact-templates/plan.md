# Implementation Plan: Four supplied contact templates

**Branch**: `2026-11-18-default-templates` | **Date**: 2026-11-18 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/133-default-contact-templates/spec.md`

## Summary

Every account receives four more supplied contact templates beside Basic contact, which stays supplied and stays the vault default everywhere. The templates are declared in the existing `Default*Enum` classes and written by the existing `CreateDefaultTemplate` job: queued at sign up, and through one backfill migration for existing accounts. A shared key dictionary, plus a fallback in `MoveRecordToStructureVersion`, keeps values visible when a contact changes template. Decisions are in [research.md](research.md), keys and slugs in [data-model.md](data-model.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (latest)

**Primary Dependencies**: none added

**Storage**: existing tables only; no schema change

**Testing**: PHPUnit, `RefreshDatabase`

**Target Platform**: hosted and self hosted Monica

**Project Type**: web application (Blade, JSON API, MCP, CardDAV)

**Performance Goals**: sign up no slower than today (templates queued); the backfill migration only dispatches jobs

**Constraints**: no existing migration edited; nothing a person already sees changes; Basic contact untouched

**Scale/Scope**: about 200 rows per account (4 templates, 28 sections, about 120 fields, 4 value lists)

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: seeding stays in the job and `CreateDefaultValueLists`; the fallback lives in `MoveRecordToStructureVersion` |
| II. Simplicity | Pass: no new mechanism, four enums extended, one new job for the backfill |
| III. Tested | Pass: see Tests below |
| V. Localized | Pass: every new label is a translation key, `monica:localize` run, French wording from the spec |
| VI. Nothing changes under anybody's feet | Pass: templates are added, vault defaults and records untouched; a move stays explicit and snapshotted |
| VII. Stable core | Pass: built only from Template, Page, Section, Field |
| VIII. Nothing disappears silently | Pass: an unmatched value is set aside as today, never dropped |
| X. API | Pass: no new capability; templates, sections, fields and value lists are already exposed by the API and MCP |
| XI. Short record | Exceeded: `spec.md` is about 300 lines because the four template layouts are the requirement. The pull request says so |

## Changes

| File | Change |
| --- | --- |
| `app/Enums/DefaultTemplateEnum.php` | four cases, `isVaultDefault()`, pages, translation keys |
| `app/Enums/DefaultPageEnum.php` | eight page cases with their keys and sections |
| `app/Enums/DefaultSectionEnum.php` | 28 section cases, `fieldLabel()` override, icons, colours |
| `app/Enums/DefaultFieldEnum.php` | new cases, types, children, value lists, repetition order, relation target, `isKeptOffTheCard()` |
| `app/Enums/ValueListEnum.php`, new `HobbyEnum`, `CommunicationChannelEnum`, `IndustryEnum` | four lists |
| `app/Jobs/CreateDefaultTemplate.php` | label through `fieldLabel()`; vault defaults only when `isVaultDefault()` |
| `app/Jobs/SupplyContactTemplates.php` (new) | four value lists, then the four templates, for one account; skips a template whose slug the account's own template or section holds |
| `app/Actions/CreateAccount.php` | the new job joins the queued chain |
| `app/Actions/CreateVault.php`, `app/Actions/DestroyTemplate.php`, `app/ViewModels/Vaults/VaultFormViewModel.php` | pick the vault default by slug, not "first system template" |
| `app/Actions/MoveRecordToStructureVersion.php` | key and type fallback (R5) |
| `app/Helpers/OwnedFields.php` | skip `isKeptOffTheCard()` keys (R6) |
| `database/migrations/2026_11_18_000004_backfill_supplied_contact_templates.php` (new) | dispatch `SupplyContactTemplates` per account |
| `lang/*.json` | new strings, French from the spec |
| `docs/portal/en/9-templates-and-customisation/1-introduction.md`, changelog | R11 |

## Tests

| Test | Proves |
| --- | --- |
| `tests/Unit/Jobs/CreateDefaultTemplateTest.php` | each new template's pages, sections and field keys in order; no vault default written for them |
| `tests/Unit/Jobs/SupplyContactTemplatesTest.php` (new) | lists and templates added; second run adds nothing; deleted template not restored; held slug skips |
| `tests/Unit/Actions/CreateAccountTest.php` | chain carries the job; Basic contact stays default and holds the user's contact |
| `tests/Unit/Actions/CreateVaultTest.php`, `DestroyTemplateTest.php` | default is Basic contact with five system contact templates present |
| `tests/Unit/Actions/MoveRecordToStructureVersionTest.php` | value follows key across sections; children follow their group; ambiguous match is set aside; same-section match unchanged |
| `tests/Unit/Helpers/OwnedFieldsTest.php` (new) | gift and outing links are not vCard URLs; website still is |
| `tests/Unit/Enums/TranslatableEnumsTest.php`, `DefaultFieldEnumTest.php` | new labels reach every locale; a key names one concept |

## Project Structure

```text
specs/133-default-contact-templates/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── checklists/requirements.md
```

No `contracts/`: no endpoint, command or file format changes. The move fallback is a behaviour of an existing action, described in R5.

**Structure Decision**: the existing Laravel tree; files listed under Changes.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| `spec.md` over 120 lines | the four layouts are the requirement | a link to the request would leave the layouts nowhere in the repository |
