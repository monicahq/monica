# Implementation Plan: Support requests inside the application

**Branch**: `2026-11-11-support-tickets` | **Date**: 2026-11-11 | **Spec**: [spec.md](spec.md)

## Summary

Users open support requests from a Support area, administrators answer them from a Support tab in
the admin space, and both sides read one thread. Four tables, six actions, two notifications, user
and admin screens from the two Claude Design files, user and admin API endpoints, ten MCP tools and
their docs. Decisions are in [research.md](research.md), tables in [data-model.md](data-model.md),
surfaces in [contracts/http.md](contracts/http.md) and [contracts/mcp.md](contracts/mcp.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.4, Laravel 13, Blade, Tailwind 4, Alpine.js and Alpine Ajax |
| Packages | `laravel/sanctum` 4 (API keys), `laravel/mcp` 1 (MCP server). No new dependency |
| Storage | SQL tables of data-model.md; attachments on the default filesystem disk |
| Testing | PHPUnit, `RefreshDatabase`, `Notification::fake()`, `Storage::fake()` |
| Scale | Tens of open requests at a time on hosted; the queue paginates at 50 |

## Constitution Check

| Principle | How the plan meets it |
| --- | --- |
| I. Actions | `OpenSupportRequest`, `AddSupportMessage`, `SolveSupportRequest`, `SendSupportReply`, `AddSupportNote`, `ChangeSupportRequestStatus`. Web, API and MCP call them; view models only read |
| II. Simplicity | No policy (R7), no flag (R16), no service layer; one shell component, justified by four screens (R17) |
| III. Tested | Every action, view model, controller, notification, resource and tool has a test; the note leak is tested per surface |
| IV. Conventions | One named route per line, inline validation, `up()` only, `foreignIdFor()->constrained()`, enums with `Enum` suffix |
| V. Localized | All copy through `__()`/`trans_choice()`, category copy on a `Translatable` enum, `monica:localize` for every locale; user written text never translated |
| VI. Nothing changes under feet | Not touched: no structure, version or setting involved |
| VII. Stable core | Support is not personal data and adds no second way to attach user data |
| VIII. Nothing disappears silently | Author and status names are snapshotted (R2); history is append only |
| IX. Accessible | Category radio group, keyboard rows, status in words beside tone, `aria-current` on the open row, focus kept after sending; reviewed by the `accessibility-reviewer` agent |
| X. API | Every user and admin capability in the API and MCP (FR-013, FR-015); webhooks absent from the product (R19) |
| XI. Short record | All artifacts within their caps |
| Mono-user invariant | Support is between a person and the instance's administrators; no vault content is shown to them (FR-011) |
| Same product everywhere | Identical hosted and self hosted; the intro names the instance's own administrators |

Gate: passes. Re-checked after Phase 1: passes, no violation to track.

## Project Structure

```text
database/migrations/2026_11_11_00000{0..3}_create_support_*_table.php
database/factories/Support{Request,Message,Attachment,StatusChange}Factory.php
database/seeders/                       the five requests of the design, Office characters
app/Enums/                              SupportCategoryEnum, SupportRequestStatusEnum, SupportMessageKindEnum;
                                        new cases in UserActionEnum, AdministratorActionEnum, EmailType, ApiKeyScopeEnum
app/Models/                             SupportRequest, SupportMessage, SupportAttachment, SupportStatusChange; relations on Account, User
app/Actions/                            the six actions above
app/Notifications/                      SupportRequestReceived, SupportReplySent
app/ViewModels/Support/                 SupportRequestFormViewModel, SupportRequestsViewModel,
                                        SupportRequestViewModel, SupportConfirmationViewModel
app/ViewModels/Admin/                   SupportQueueViewModel + SupportRequestContext (side panel)
app/Http/Controllers/App/Support/       SupportRequestController, SupportRequestConfirmationController,
                                        SupportMessageController, SupportSolutionController, SupportAttachmentController
app/Http/Controllers/App/Admin/         SupportController, SupportReplyController, SupportNoteController,
                                        SupportStatusController, SupportAttachmentController
app/Http/Controllers/Api/               SupportRequestController, SupportMessageController,
                                        SupportSolutionController, SupportAttachmentContentController
app/Http/Controllers/Api/Admin/         SupportRequestController, SupportReplyController, SupportNoteController, SupportStatusController
app/Http/Resources/                     SupportRequestResource, SupportMessageResource, Admin\SupportRequestResource
app/Mcp/Tools/                          the ten tools of contracts/mcp.md
resources/views/app/support/            index, new, confirmation, show (+ partials)
resources/views/app/admin/support/      index (+ partials: row, thread, composer, context, history)
resources/views/components/support/     page.blade.php (the shell of R17)
resources/views/layouts/admin.blade.php Support entry with the To handle count
resources/views/components/account-menu.blade.php  Help and support entry
resources/docs/api/                     32-support-requests.php, 33-support-administration.php
docs/portal/en/10-your-account-and-security/  contact-support guide; docs/FEATURES.md entry
lang/*.json                             via monica:localize
tests/Unit/{Actions,Models,ViewModels/Support,ViewModels/Admin,Notifications,Enums}
tests/Feature/Controllers/{App/Support,App/Admin,Api,Api/Admin}, tests/Feature/Mcp
```

**Structure Decision**: the existing Laravel layout. User screens get their own `Support` domain
folder beside `Vaults` and `Settings`; admin screens join `Admin`.

## Implementation order

1. Enums, migrations, models, factories, seeder.
2. Actions and notifications, with their tests: the rules of data-model.md live here.
3. User web screens, then admin web screens, from the two design files.
4. User API, admin API with the `administration` scope, then MCP tools.
5. API docs, MCP docs, portal guide, FEATURES.md, translations.
6. Full suite, PHPStan, Pint, `monica:localize --check`, accessibility and rules reviews.

Account deletion must also delete the attachment files from the disk: find the action that deletes
an account and extend it, with a test.

## Complexity Tracking

None.
