# Implementation Plan: Additional feature flags

**Branch**: `2026-10-10-additional-feature-flags` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/073-additional-feature-flags/spec.md`

## Summary

Declare five Pennant flags on specification 068's mechanism. CardDAV, vCard export, SMS
notifications and file upload ship off; the MCP server ships on through a resolver that accepts the
null scope. Each capability is refused where it enters: route middleware for CardDAV, MCP, vCard and
the dedicated upload routes, the notification channel model for SMS, the shared field validation for
uploads inside forms. Decisions in [research.md](research.md), answers per surface in
[contracts/flags.md](contracts/flags.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: `laravel/pennant` (068), `laravel/mcp`, `laravel/passport`, `monicahq/laravel-sabre`; nothing new

**Storage**: Pennant's `features` table; no migration

**Testing**: PHPUnit, `RefreshDatabase`; the suite defines the four withdrawn flags as on (R8)

**Target Platform**: hosted and self hosted alike

**Project Type**: server rendered web application with a JSON API, an MCP server and a CardDAV server

**Performance Goals**: one cached flag read per account per request, as 068

**Constraints**: a CardDAV client still receives the 401 challenge (R2); a flag never marks a channel failed (R4)

**Scale/Scope**: five flag classes, about 14 routes gated, three notices, 78 existing test files kept green by R8

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions | The refusals inside work (`DeliverNotification`, `ImportVCard`) live in their actions |
| II. Simplicity | One middleware reused; one model method for SMS; one rule for fields |
| III. Tested | Each flag class, each gate, each notice (Project Structure) |
| IV. Laravel way | Pennant's nullable resolver (R1); validation for the field refusal (R6) |
| V. Localized, safe | Three sentences and one validation message through `__()` and `monica:localize` |
| VI. Nothing changes under feet | Four capabilities disappear on deployment; justified below |
| VII. Stable core | No concept added |
| VIII. Nothing disappears silently | Data kept and FR-014 explains; a syncing device is the silent case; justified below |
| IX. Accessible | Notices use `x-notice`; withdrawn controls are absent, as 068 settled for flags |
| X. API | Every gate applies to the `v1` API as to the web (contract) |
| XI. Short | spec 119, research 80, contract and quickstart under 60, this plan under 100 |
| Same product everywhere | No resolver reads the plan, the payment state or the deployment |

Gate passes with the justifications below, before research and again after design.

## Project Structure

### Documentation (this feature)

`spec.md`, `plan.md`, `research.md`, `quickstart.md`, `contracts/flags.md`. No `data-model.md`: no
table or column changes.

### Source Code (repository root)

```text
app/Features/{SmsNotifications,CardDav,VCardExport,FileUpload,McpServer}.php   R1
config/laravelsabre.php                  flag:carddav after AuthenticateWithApiKeyOnBasicAuth (R2)
routes/ai.php, app/Providers/AppServiceProvider.php   flag:mcp-server; authorization view refuses (R3)
routes/web.php, routes/api.php           vCard, photo, avatar and docs routes behind their flag
app/Models/NotificationChannel.php       isOffered(), mayCarry(), resolveRouteBinding() (R4)
app/Actions/DeliverNotification.php      abandons before hand() (R4)
app/Http/Controllers/Api/NotificationChannelController.php   index leaves the row out
app/Helpers/FieldValueRules.php          prohibited file rules when off (R6)
app/Actions/ImportVCard.php              writeThePhoto() skipped when off (R6)
app/ViewModels/Settings/Account/{Notifications/NotificationChannelsViewModel,Security/SecurityViewModel,Profile/ProfileViewModel}.php
app/ViewModels/Vaults/{ContactViewModel,ContactEditViewModel,EntityFormViewModel}.php
app/ViewModels/Docs/{DocsViewModel,ApiDocsViewModel}.php, app/Actions/BuildOpenApiDocument.php   R7
resources/docs/api/06-contacts.php       'flag' => 'vcard-export' on contacts-vcard
resources/views/components/{field-input,field-repetitions}.blade.php   uploadsAreOn
resources/views/app/{settings/account/{notifications,security,profile},vaults/contacts,docs}/*
.claude/rules/flags.md                   a permanent switch, and the sentence of FR-014
lang/*.json
tests/TestCase.php                       R8
tests/Unit/Features/*Test.php            the shipped default of each class
tests/Unit/{Models,Actions,Helpers,ViewModels}/...   channel, delivery, vCard photo, field rules, notices
tests/Feature/Controllers/...            one off case per gated route, web and API; DAV and MCP endpoints
```

**Structure Decision**: existing folders only. No new middleware, action, policy or component: the
`flag` middleware and `x-notice` exist, and a flag is not an ability (`.claude/rules/policies.md`).

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| VI: four capabilities withdrawn on deployment | The user's decision: released by the administrator, not by the deploy | Shipping them on was offered and declined |
| VIII: a syncing device is not told why it stops | A DAV client shows no message from the server | Forbidden instead of not found was offered and declined |
| `flags.md`: a permanent MCP flag, FR-014 explains | The user's decision; the rule is amended here, not broken silently | Deleting it at release removes the administrator's switch |
| Portal guide pages link to gated docs (R7) | The portal cannot gate a page | Building that is out of scope; follow up |
