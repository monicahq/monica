# Implementation Plan: Vault trash

**Branch**: `163-vault-trash` | **Date**: 2026-11-25 | **Spec**: [spec.md](spec.md)

## Summary

A Trash tab in vault settings lists what was deleted from the vault for 30 days, with restore, delete for good and empty, mirrored in the API and MCP. Deleting a person or a group now takes its dependents with it, each remembering its owner ([research R1, R2](research.md)); animals gain deletion and group files are set aside rather than erased. A daily job deletes for good what has waited 30 days.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)
**Primary Dependencies**: Laravel MCP, Scout (search follows soft delete and restore on its own), Pennant (`groups`, `file-upload` flags)
**Storage**: SQLite and MySQL; six migrations adding `deleted_with`, one adding soft deletes to `group_files`, one data migration ([data-model.md](data-model.md))
**Testing**: PHPUnit, `composer test:parallel`
**Target Platform**: web, JSON API, MCP
**Project Type**: Laravel web application
**Performance Goals**: the tab and the listing endpoint answer with one paginated union query and one load per kind on the page
**Constraints**: WCAG 2.2 AA; every phrase translated in every locale; specs under constitution XI caps
**Scale/Scope**: nine kinds, ~10 new actions, 1 job, 1 helper pair, 5 web routes, 5 API routes, 5 MCP tools

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Every operation is an action; controllers, MCP and the job call them. `VaultTrash` only reads. |
| II. Simplicity | Pass. One union query, one obstacle method; per-kind restore actions because each kind's side effects differ. |
| III. Tested | Pass. Actions, helper, job, controllers, MCP catalogue and view model each tested. |
| IV. Laravel conventions | Pass. `up()` only, new migrations, camelCase route names, one route per line. |
| V. Localized and safe | Pass. Every destructive step confirmed; counted phrases through `trans_choice()`. |
| VI. Nothing changes under feet | Pass. Restoring returns records to the versions they pointed at. |
| VIII. Nothing disappears silently | Pass, and the reason for the feature: the 30 days are shown per row with their date. |
| IX. Accessible | Pass. Show menu uses `x-actions-menu`; countdown text, not colour alone; confirmations use `x-delete-action`'s card. |
| X. Through the API | Pass. Every tab operation has an endpoint and a tool ([contracts/api.md](contracts/api.md)). |
| XI. Short record | Pass. |

No violation; Complexity Tracking omitted.

## Project Structure

```text
app/Enums/TrashItemKindEnum.php                       new
app/Enums/UserActionEnum.php                          + restorations of 5 kinds, TrashItemDestruction, TrashEmptied
app/Helpers/VaultTrash.php, TrashItem.php             new (R3, R4)
app/Actions/
  RestoreTrashItem, DestroyTrashItem, EmptyVaultTrash, DestroyEntity      new
  RestoreContact, RestoreEntity, RestoreRelationship, RestoreReminder,
  RestoreGroupMembership, RestoreGroupNote, RestoreGroupFile               new
  RestoreGroup, RestoreLifeEvent                                           + dependents
  DestroyContact, DestroyGroup                                             + dependents (R2)
  RemoveGroupFile                                                          soft delete (R6)
  MergeContacts                                                            + owner on dropped rows (R7)
app/Jobs/PurgeVaultTrash.php                          new, scheduled in routes/console.php (R8)
app/Http/Controllers/App/Vaults/
  VaultTrashController, VaultTrashItemController, VaultTrashRestorationController   new
  EntityController                                    + destroy
app/Http/Controllers/Api/
  TrashController, TrashItemController, TrashRestorationController                  new
  EntityController                                    + destroy
app/Http/Resources/TrashItemResource.php              new
app/Mcp/Tools/ ListTrash, RestoreTrashItem, DeleteTrashItem, EmptyTrash, DeleteEntity   new, registered in MonicaServer
app/ViewModels/Vaults/VaultTrashViewModel.php         new
app/Models/Concerns/GoesToTheTrashWithItsOwner.php    new; six models use it, GroupFile also gains SoftDeletes
resources/views/app/vaults/settings.blade.php         + tabs; settings/_tabs, settings/trash.blade.php new
resources/views/... delete confirmations of the nine kinds   copy names the trash (FR-014)
resources/docs/api/43-trash.php                       new; entity deletion added to its page
config/monica.php                                     trash_retention_in_days
database/migrations/                                  2026_11_25_000004 to 000010, one table per migration (data-model.md)
tests/ mirroring each of the above
```

**Structure Decision**: the existing Laravel layout; no new base folder.

## Delivery notes

- Product documentation (writer skill), the changelog entry and a help bubble on the Trash tab are part of the definition of done.
- Translations by Sonnet subagents after `monica:localize`.
