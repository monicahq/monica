# Implementation Plan: Change the template of a contact

**Branch**: `2026-11-25-contact-template-change` | **Date**: 2026-11-25 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/159-contact-template-change/spec.md`

## Summary

A contact can move to another template from Edit contact, the API and the MCP server, after a preview of
what moves, what is set aside and which reminders stop. The move itself already exists
(`MoveRecordToStructureVersion`); a new action `ChangeContactTemplate` checks the chosen template and
delegates to it. The field matching is extracted into `FieldCorrespondence`, shared by the move and by
`TemplateChangePreview`, so the preview cannot disagree with the change. The version history reverts
across templates. Logging becomes the caller's choice, ready for moving many contacts at once. The screen
follows the design `Edit Contact.dc.html`: a Template panel whose menu lists templates and their versions,
a "Read more" disclosure of the consequences, and a confirmation block that also carries the preview
lists. Decisions and their alternatives are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: Blade, Alpine.js, Alpine Ajax, Tailwind CSS, Laravel MCP, Sanctum; no new dependency

**Storage**: MySQL/SQLite, no migration ([data-model.md](data-model.md))

**Testing**: PHPUnit (`tests/Unit/Actions`, `tests/Unit/Helpers`, `tests/Unit/ViewModels`, `tests/Feature/Controllers`, `tests/Unit/Mcp`)

**Target Platform**: web application, the API v1 and the MCP server

**Project Type**: web application (Laravel monolith)

**Performance Goals**: the Edit contact screen computes a preview only when a template is asked for; the
matching indexes are built once per target version (R5)

**Constraints**: WCAG 2.2 AA; works without JavaScript; the three surfaces give the same sentences (SC-005)

**Scale/Scope**: one action created, four changed, two helpers, three controllers, three routes, two MCP
tools, one screen, one help page and three corrected

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. `ChangeContactTemplate` holds the template checks; the matching rule lives in one helper used by the action, never by a controller or view model alone (R3, R5, R6) |
| II. Simplicity | Pass, with one cost: `writesToLog` on four actions has no caller passing `false` until the bulk feature (R7), accepted by the owner on 2026-11-25 |
| III. Tested before merged | Pass. Tests below |
| IV. Laravel conventions | Pass. Named routes, camelCase segments, inline validation, `x-target` |
| V. Localized and safe | Pass. Every wording through `__()`, translated in every locale by Sonnet subagents |
| VI. Nothing changes under anybody's feet | Pass. Explicit confirmation after a preview; snapshot before the move; revert across templates (R9) |
| VII. Stable core | Pass. Template, Page, Section, Field unchanged; no second mechanism |
| VIII. Nothing disappears silently | Pass. Unmatched answers set aside and listed; stopped reminders say why and restart |
| IX. Accessible | Pass. The menu contract of `x-actions-menu` with radio lines in named groups, `x-help`, focus moved to the confirmation and back to the trigger, a refused control kept with `aria-disabled` and its reason |
| X. Everything through the API | Pass. Two endpoints, two MCP tools through `answerThroughApi()`; no outbound webhooks exist yet |
| XI. Short record | Pass. Contracts and quickstart kept to what tasks need |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/159-contact-template-change/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── api.md
│   ├── mcp.md
│   └── web.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/Helpers/FieldCorrespondence.php                       # new, the matching rule (R5)
app/Helpers/TemplateChangePreview.php                     # new (R6)
app/Actions/ChangeContactTemplate.php                     # new (R3)
app/Actions/MoveRecordToStructureVersion.php              # uses FieldCorrespondence; no-op first (R4); writesToLog (R7); new log case (R8)
app/Actions/CreateRecordSnapshot.php                      # + writesToLog
app/Actions/DeactivateReminders.php                       # + writesToLog
app/Actions/ReactivateReminders.php                       # + writesToLog
app/Enums/UserActionEnum.php                              # + RecordMovedToTemplate
app/Http/Controllers/App/Vaults/ContactTemplateController.php      # new, update()
app/Http/Controllers/Api/ContactTemplateController.php             # new, update()
app/Http/Controllers/Api/ContactTemplatePreviewController.php      # new, show()
app/ViewModels/Vaults/ContactEditViewModel.php            # templates offered, preview block
app/ViewModels/Vaults/ContactViewModel.php                # canRevertTo(), revertTo(), whyNoRevertTo() (R9)
app/Mcp/Tools/PreviewContactTemplateChange.php            # new
app/Mcp/Tools/ChangeContactTemplate.php                   # new
app/Mcp/Servers/MonicaServer.php                          # register both
app/ViewModels/Docs/McpDocsViewModel.php                  # Contacts family
routes/web.php, routes/api.php                            # three routes
resources/views/app/vaults/contacts/edit.blade.php        # Template panel per the design; header link removed
resources/views/components/actions-menu/index.blade.php   # a control look for the picker's trigger, if needed (R2)
resources/views/app/vaults/contacts/_version-history.blade.php   # revert form target
resources/views/app/vaults/contacts/form.blade.php        # sentence checked, now true
resources/docs/api/06-contacts.php                        # two sections
resources/docs/api-changelog/2026-11-25.php               # Added entry
docs/portal/en/3-people/7-change-the-template.md          # new help page
docs/portal/en/3-people/3-change-what-you-know.md         # link
docs/portal/en/9-templates-and-customisation/4-change-a-template.md   # corrected sentence
lang/*.json                                               # every locale
```

**Structure Decision**: existing Laravel layout. The helpers sit in `app/Helpers` beside `OwnedFields`.

### Tests, one layer each

- `FieldCorrespondence`: same section match, elsewhere match, two candidates match nothing, a field already filled is not taken, a repeater child follows its parent only. Most cases are already proved through the move; these prove the helper alone writes nothing.
- `TemplateChangePreview`: lists set aside, brought back, stopped and restarted reminders; `isChange` false on the current version; a sensitive field counted, not named, without the scope; its lists equal what the move then does (SC-004).
- `MoveRecordToStructureVersion`: the 22 existing tests unchanged; no snapshot and no log on the current version; the template log case; `writesToLog: false` writes no log.
- `CreateRecordSnapshot`, `DeactivateReminders`, `ReactivateReminders`: one test each for `writesToLog: false`.
- `ChangeContactTemplate`: each refusal (another account, another kind, turned off, no published version, a version of another template); the move and its log; a round trip restores answers and reminders (SC-002).
- `ContactEditViewModel`: the menu groups templates and their published versions, latest first, the current one checked; the panel is absent when nothing else is offered; the preview block only for another version than the current one.
- `ContactViewModel`: revert offered across templates, refused with its sentence for a turned off template.
- Web controller: redirect and flash, the action's refusal on `template`, wrong vault 404.
- API controllers: JSON shape, the 422 refusal, validation of `template`, another account 404.
- MCP: none of their own (`.claude/rules/mcp.md`); the catalogue and walk tests cover them.
- Docs: section count, `answer()` arms, `HelpGuideCoverageTest` for the bubble.

### Delivery

The screen task works from the design `Edit Contact.dc.html`, read on 2026-11-25 (project
`d4c00f28-7f8a-415b-91bc-cf0ae3b5a25b`). A change to `x-actions-menu` is a change to every screen using
it, so the whole suite runs after it. Changelog entry (changelog skill),
translations by Sonnet subagents, the `definition-of-done` skill before the commit.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| `writesToLog` with no caller passing `false` yet | FR-020: the next feature changes thousands of contacts and must write one log entry | Deferring means reopening four actions next month; agreed against on 2026-11-25 |
