# Implementation Plan: Groups

**Branch**: `2026-10-29-groups` | **Date**: 2026-10-29 | **Spec**: [spec.md](spec.md)

## Summary

Two tables, their actions, screens, API, MCP tools and docs, group cards in CardDAV both ways, and
merge, export and share link support, behind one flag until US1 to US4 are complete. See
[research.md](research.md), [data-model.md](data-model.md) and [contracts/api.md](contracts/api.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Alpine Ajax, `laravel/mcp`, `sabre/dav` |
| Storage | Two new tables; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: action, view model, controller, tool and DAV tests |
| Project type | Web application with a JSON API, an MCP server and a CardDAV server |
| Scale | 8 new actions and 6 changed, 2 models, 7 controllers, 11 tools, 1 docs page, 1 flag |

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: web, API, MCP and CardDAV all call the same actions |
| II. Simplicity | Pass: copies life events and relationships; no kind, no shared abstraction (R1) |
| III. Tested | Pass: test scope below |
| IV. Laravel conventions | Pass: inline validation, one named route per line, `foreignIdFor`, `up()` only |
| V. Localized | Pass: new copy and the thirteen renamed strings go through `monica:localize` (R10) |
| VI. Nothing changes underfoot | Pass: no structure version; a device changes only name and current members (FR-021) |
| VII. Stable core | Pass: Group is built as a fixed concept, not as an entity type |
| VIII. Nothing disappears silently | Pass: soft delete and restore; merge undo restores memberships |
| IX. Accessible | Pass: member picker reachable by keyboard; refusals announced (FR-016) |
| X. Through the API | Pass: every operation has a route and a tool (SC-002) |
| XI. Short record | Pass: every artifact under its cap |

Re-checked after design: unchanged.

## Project Structure

```text
database/migrations/2026_10_29_000000_create_groups_table.php           groups, runs first
database/migrations/2026_10_29_000001_create_group_members_table.php    group_members
app/Models/{Group,GroupMember}.php    database/factories/{Group,GroupMember}Factory.php
app/Features/Groups.php               flag, added to tests/TestCase.php
app/Enums/UserActionEnum.php          eight cases (R11)
app/Actions/{Create,Update,Destroy,Restore}Group.php
app/Actions/{Create,Update,Destroy}GroupMembership.php                  leaving is an update
app/Actions/WriteGroupCard.php        a group card from a device (R7)
app/Actions/{PreviewContactMerge,MergeContacts,UndoContactMerge}.php    memberships (R8)
app/Actions/{WriteAccountExport,WritesMonicaItems,ReadMonicaFile}.php  groups in the export
app/Actions/DestroyVault.php          marks groups deleted_with_vault
app/Http/Dav/{AddressBookBackend,CollectionChanges,GroupVCard}.php      group cards (R6, R7)
app/Http/Controllers/App/Vaults/{Group,GroupRestoration,GroupMember}Controller.php
app/Http/Controllers/Api/{Group,GroupRestoration,GroupMember,ContactGroup}Controller.php
app/Http/Resources/{Group,GroupMembership}Resource.php
app/ViewModels/Vaults/{GroupsViewModel,GroupViewModel,GroupFormViewModel,ContactGroupsBlock}.php
app/ViewModels/Vaults/ContactTimelineBlock.php                          join and leave entries (R5)
app/Mcp/Tools/*Group*.php             eleven tools; EricaServer registers them
resources/views/app/vaults/groups/    index, show, form, _member-form; link in components/vault/sidebar
resources/views/app/vaults/contacts/  _group-memberships block; share link toggle
resources/docs/api/27-groups.php      API reference; McpDocsViewModel and CarddavDocsViewModel
life event type editor files          a "group" of fields becomes a "block" (R10)
spec/models/032, 033                  aligned with data-model.md
routes/web.php, routes/api.php        behind flag:groups
```

**Structure Decision**: the life event capability, file for file, plus the CardDAV backend (R1).

## Test scope

| Layer | Proves |
| --- | --- |
| Actions | account and vault checks; FR-005; date order; destroy keeps members; restore; changelog |
| `WriteGroupCard` | creates no contact; diff starts and ends today; unknown `MEMBER` ignored; both forms |
| Merge, undo | move; fold keeps earlier join and kept role; undo restores both |
| View models | current then former; pagination; empty states; group cards skip hidden contacts |
| Controllers | routes answer; inline validation; one refusal as error or 422; wrong vault 404 |
| `AddressBookTest` | group card served; sync token moves on a membership change; deleting the card deletes the group |
| Export | round trip through `WriteAccountExport` and the import reader |
| Flag | off gives 404 and no group card |

## Order

1. Flag, migration, models, factories, changelog cases.
2. Group and membership actions with their tests (US1, US3).
3. Vault list, group page, contact block, timeline entries (US1 to US3).
4. API, resources, MCP tools, docs pages (FR-013).
5. CardDAV out, then in (US4); the device `UID` risk in R7 is tested first.
6. Merge, export and import, share link, vault deletion.
7. Terminology change, `monica:localize`, PHPStan, Pint, full suite.

## Risks

- A contact a device created may not answer to its `UID` (R7); its membership would then be ignored.
- Neither DAVx5 nor iOS group cards have been tried against this server (quickstart 6, 7).

## Complexity Tracking

None.
