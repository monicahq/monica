# Implementation Plan: Export a contact as a vCard

**Branch**: `039-export-contact-vcard` | **Date**: 2026-09-17 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/039-export-contact-vcard/spec.md`

## Summary

Hand the account holder the card the product already builds for synchronising devices. `VCardMerger` produces it today for CardDAV, so most of the work is transport and journalling. One correction rides along: a card returning from a device under a filing address the product has never seen is matched by the identifier it carries and updates its contact, instead of becoming a second one.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 12

**Primary Dependencies**: `sabre/vobject`, which builds the card today, `sabre/dav` for the CardDAV surface, Laravel Sanctum for the API key scopes

**Storage**: MySQL, PostgreSQL or SQLite. No migration: the feature adds no column and no table

**Testing**: PHPUnit, in `tests/Unit/Actions`, `tests/Feature/Controllers/App/Vaults`, `tests/Feature/Controllers/Api` and `tests/Feature/Dav`

**Target Platform**: Server rendered web application, plus the JSON API and CardDAV

**Project Type**: Web application (Blade, Tailwind, Alpine)

**Performance Goals**: SC-004, a contact of 100 field values exported in under 2 seconds. The card is built in memory from records the screen already loads

**Constraints**: `spec.md` stands at 115 lines against its 120 cap, so this plan links rather than restates. The suite must pass on all three database connections, and PHPStan level 5 must report nothing new

**Scale/Scope**: One contact per request. Four new files, five edited

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Only the principles this change actually touches are listed. The rest are untouched: no structure version moves (VI), no second way to attach data appears (VII), and nothing is destructive, so no warning is owed (V).

| Principle | How this change satisfies it |
| --- | --- |
| I. Actions hold the business logic | One `ExportContactVCard` action. Both controllers carry transport and nothing else |
| II. Simplicity over abstraction | `VCardMerger` is reused unchanged. No second card shape, no writer of our own |
| III. Tested before merged | Unit test for the action, feature tests for both controllers, a CardDAV test for the return path |
| VIII. Nothing disappears silently | The export is journalled, and FR-011 replaces a silent duplicate with an update |
| IX. Accessible to everybody | The control joins the existing header button row, so it inherits a reachable, labelled pattern |
| X. Everything goes through the API | `GET /vaults/{vault}/contacts/{contact}/vcard`, in the portal and the OpenAPI document |
| XI. The written record is short | No `data-model.md`. The feature adds no entity beyond what the spec's Key Entities already names |

**Gate result**: pass, with one thing stated rather than hidden. No MCP tool is added. The server exposes 14 selected capabilities rather than one per endpoint, so leaving this one out follows the existing line instead of opening a hole.

**Post-design re-check**: still passing. The design added no folder, no dependency and no entity. Every artifact is inside its cap: this plan 95 of 100, `research.md` 59 of 80, `contracts/contact-vcard.md` 52 of 60, `quickstart.md` 49 of 60.

## Project Structure

### Documentation (this feature)

```text
specs/039-export-contact-vcard/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── contact-vcard.md # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit-tasks, not created here)
```

`data-model.md` is deliberately absent. The constitution requires it omitted when another artifact carries its content, and this feature introduces no entity, no column and no state transition.

### Source Code (repository root)

```text
app/
├── Actions/
│   ├── ExportContactVCard.php              # new: builds the card, journals, refuses across accounts
│   └── BuildOpenApiDocument.php            # edited: honour a per-endpoint response media type
├── Enums/UserActionEnum.php                # edited: one case, ContactExported
├── Http/
│   ├── Controllers/
│   │   ├── Api/ContactVCardController.php  # new: show(), streams text/vcard
│   │   └── App/Vaults/ContactVCardController.php  # new: show(), streams text/vcard
│   └── Dav/AddressBookBackend.php          # edited: createCard() matches an unknown URI by UID
└── ViewModels/Vaults/ContactViewModel.php  # edited: vCardUrl()

resources/
├── docs/api/06-contacts.php                # edited: the endpoint, with a non-JSON response
└── views/app/vaults/contacts/show.blade.php # edited: one button in the header row

routes/
├── api.php                                 # edited: one route
└── web.php                                 # edited: one route, placed above the catch-all
```

**Structure Decision**: the layout is fixed by the constitution and every file lands in a folder that exists. The two controllers are separate classes rather than a method on `ContactController`, following the project rule that additional actions get their own controller. The web route MUST be declared above `vaults/{vault}/contacts/{contact}/{page?}` in `routes/web.php:255`, which would otherwise capture `vcard` as a page name and route the request to `ContactController::show`.

## Complexity Tracking

No constitution violations, so this table is empty. The one judgement worth recording is that `BuildOpenApiDocument` gains a media type option rather than a special case for this endpoint, because a hardcoded `application/json` is the thing actually blocking the contract, and `research.md` records why.
