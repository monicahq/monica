# Implementation Plan: MCP Server

**Branch**: `035-mcp-server` | **Date**: 2026-09-15 | **Spec**: [spec.md](spec.md)

## Summary

One dependency, one server class, thirteen tools, one middleware, one column and one missing API
endpoint. The capability list and what each mirrors is
[contracts/capabilities.md](contracts/capabilities.md); the eight questions that shaped the design
are [research.md](research.md).

The design is mostly subtraction. Writes run the actions the `Api` controllers already run, refusals
are translated because `SetApiLocale` is already on the api group, and the changelog learns it was
MCP because `LogUserAction` already reads `log_source` off the request and CardDAV already shows how
a key sets it. Thirteen tools land without one action changing.

Two things it cannot subtract: `app/Mcp` is a new top-level folder, which the constitution says
requires an amendment, and FR-006 asked for a Me contact the API does not carry, so FR-013 adds it.

`data-model.md` is omitted on Principle XI's own terms. The one column this adds, `client` on
`personal_access_tokens`, is already described in
[spec/models/006](../../spec/models/006-personal-access-token-in-progress.md).

## Technical Context

| | |
| --- | --- |
| Language, dependencies | PHP 8.4, Laravel 13, Sanctum. One new required dependency, `laravel/mcp` v1.0.0, approved 2026-09-14. |
| Storage | One column, `client` on `personal_access_tokens`, one migration, `up()` only. No table is created. |
| Testing | PHPUnit, `php artisan test --compact`. The package ships `EricaServer::tools()` and `EricaServer::actingAs($user)->tool(...)`, so a tool is testable without HTTP. |
| Target, type | Linux server, hosted and self hosted alike. `POST /api/mcp`, registered in `routes/api.php`. |
| Performance | A list is one query per tool, the same one the API runs. Nothing is added to the request path of any existing screen or endpoint. |
| Constraints | `throttle:mcp`, per key, `Limit::none()` when `monica.self_hosted`. The number is configuration, which `spec/models/006` fixes deliberately. |
| Scope | 13 tools, 1 server, 1 middleware, 1 enum, 1 migration, 1 API controller, 1 route group, 1 docs page, 6 test classes. Roughly 30 files, 24 of them new. |

## Constitution Check

*GATE: must pass before Phase 0. Re-checked after Phase 1 below.*

| Principle | Verdict | How this feature satisfies it |
| --- | --- | --- |
| I. Actions hold the business logic | **Attention** | The three writes run `CreateContact`, `UpdateContactName` and `FavouriteContact`, untouched. The ten reads have no action to run, since this project puts list queries in controllers, so each reading tool repeats the model scopes and the API resource. Shared logic in the model, not a rule written twice, but it is FR-003's weakest point and why `McpCatalogueTest` exists. |
| II. Simplicity over abstraction | **Pass** | Thirteen short tools, no base class of our own beyond the package's `Tool`. The vault lookup is three lines repeated per tool, exactly as the `Api` controllers already repeat it. |
| III. Tested before merged | **Pass** | Six feature classes named in [quickstart.md](quickstart.md), plus a test per tool. No existing behaviour changes, so existing tests move only where the key gains a column. |
| IV. Laravel conventions | **Pass** | `up()` only, no `constrained()`, kebab-case tool names, camelCase route names, inline validation through the tool schema, early returns. |
| V. Localized and safe | **Attention** | Refusal strings, the mint screen's new choice and the docs page. Every one through `__()` and `monica:localize`, in all 31 locales. FR-011 is the reason refusals cannot be English constants. |
| VI. Nothing changes under anybody's feet | **Pass** | Purely additive. No published version, record or installed item is touched, and an existing key keeps working with `client` defaulting to the script kind. |
| VII. A stable core | **Pass** | No new way to attach user defined data. The tools reach templates and fields through the one mechanism that already exists. |
| VIII. Nothing disappears silently | **Pass** | Revoking a key deactivates the connection and says so; nothing is deleted. The changelog is append only and gains rows, never loses them. |
| IX. Accessible to everybody | **Attention** | One screen changes: the key mint form gains a choice of caller kind. Keyboard, screen reader, contrast and 200% zoom apply to it like any control, and the choice must not be carried by colour. |
| X. Everything goes through the API | **Pass, and it closes a gap** | FR-013 adds the Me contact endpoint the API was missing. The server holds no authorisation rule and reaches nothing the API cannot. |
| XI. The written record is short | **Over cap, declared** | Three are over and the pull request must say so: spec 127 against 120, research 84 against 80, this plan 142 against 100. Contract 55 and quickstart 60 are inside. `data-model.md` is omitted rather than written thin. |

**Gate result: conditional.** Ten principles pass or pass with attention. One thing is not the
plan's to decide: `app/Mcp/{Servers,Tools}` is a new top-level folder under `app/`, and the
constitution says in terms that one requires an amendment. **This feature cannot merge until that
amendment merges.** Complexity Tracking carries it and the two other judgement calls.

## Project Structure

```text
app/Mcp/Servers/EricaServer.php                     thirteen tools             new
app/Mcp/Tools/List{Vaults,Contacts,Templates}.php   and seven more reads       new
app/Mcp/Tools/Get{Contact,MeContact}.php                                       new
app/Mcp/Tools/{CreateContact,UpdateContactName,SetContactFavourite}.php        new
app/Http/Middleware/SetMcpLogSource.php             log_source, source_detail  new
app/Enums/ApiKeyClientEnum.php                      Script, Assistant          new
app/Http/Controllers/Api/ContactMeController.php    FR-013                     new
database/migrations/..._add_client_to_personal_access_tokens_table.php         new
resources/docs/api/19-mcp.php                       a guide page, FR-012       new
tests/Feature/Mcp/Mcp*Test.php                      six classes                new
tests/Unit/Mcp/Tools/*Test.php                      one per tool               new
routes/api.php                                      Mcp::web, one route        edit
app/Providers/AppServiceProvider.php                the mcp limiter            edit
app/Actions/CreateApiKey.php                        the client parameter       edit
app/Http/Controllers/App/Settings/.../ApiKeyController.php  the choice         edit
app/Http/Resources/ApiKeyResource.php               one field                  edit
app/ViewModels/Settings/Account/Security/SecurityViewModel.php  one field      edit
resources/views/app/settings/.../api-keys              the choice control      edit
composer.json                                       laravel/mcp                edit
lang/*.json                                         new keys, 31 files         edit
tests/**/Docs/*                                     counts, sidebar            edit
```

**Structure Decision**: the shipped layout, plus `app/Mcp`, which is the amendment. `routes/ai.php`
is deliberately **not** created: the package loads it only when it exists, so `Mcp::web()` is called
from `routes/api.php`, which the constitution already names, and the api group's `SetApiLocale` and
Sanctum come with it.

## Order of work

1. The dependency, the migration, `ApiKeyClientEnum`, and `CreateApiKey` taking the kind. Then the
   mint screen, the resource and the view model, then `monica:localize`.
2. `ContactMeController` and its route, with its test and its docs section. FR-013 before anything
   depends on it.
3. `EricaServer`, `SetMcpLogSource` and the route in `routes/api.php`, with `McpAuthenticationTest`
   and `McpChangelogTest`. Nothing offered yet; the transport proven first.
4. The ten reading tools, then the three writing tools, each with its test.
5. `McpCatalogueTest`, `McpCompartmentTest`, `McpRateLimitTest`, `McpRefusalLanguageTest`.
6. `resources/docs/api/19-mcp.php` and the docs counts in the same commit, then the quickstart run.

## The risk worth naming

Nothing existing changes behaviour, so the harm is narrow and lands in one place: a tool that reaches
further than the endpoint it claims to mirror. Four guards:

- `McpCatalogueTest` fails when a tool names no route, or names one `Route::has()` does not know.
- `McpCompartmentTest` asks every capability for another account's vault and contact and demands not
  found, never forbidden, which is the constitution's data access rule stated per capability.
- `McpChangelogTest` proves a write lands with source `mcp` and the key's name, so an assistant's
  change can never be mistaken for one typed in a browser.
- `McpRefusalLanguageTest` sets a non-English locale and demands the refusal come back in it.

One acceptance scenario cannot be proven yet and the pull request should say so. User Story 2's
second scenario reads "a key permitted to read only", and no such key can be minted: `ApiKeyScopeEnum`
holds one case and has no read and write split. The answer sits in `shouldRegister()` and needs no
other change when scopes arrive. That is the inheritance clarification 1 chose, reaching one scenario
the clarification did not rewrite.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| `app/Mcp`, a new top-level folder | The package generates `App\Mcp\Servers` and `App\Mcp\Tools`, and a tool is not a controller, a model or an action, so no listed folder holds it. | `app/Http/Mcp` was rejected: the constitution enumerates `Http`'s five children as explicitly as `app`'s, so it trades a named amendment for an unnamed one. |
| Reading tools repeat the API's query | The ten reads have no action to call, because this project puts list queries in the `Api` controllers and view models, not in `app/Actions`. | Extracting ten read actions was rejected as a refactor of the API surface hiding inside an MCP feature. Dispatching an internal HTTP request per tool was rejected in [research.md](research.md) section 4. |
| One new required dependency | Approved by the user on 2026-09-14. The suggest-and-develop rule governs outbound channels reaching a third-party service; this is an inbound endpoint the product serves, and the application cannot boot the feature without it. | Writing the protocol by hand was rejected: it is a moving specification and a permanent maintenance cost. |

## Constitution Re-check (post Phase 1)

Re-run after the design artifacts were written. **Still conditional on the amendment**, with two
things settled and one found.

- **Principle X got stronger, not weaker.** FR-013 means this feature leaves the API more complete
  than it found it. An MCP capability with no API behind it was the shortcut available here, and the
  clarification refused it.
- **Principle I is where to look in review.** Writes are clean. Reads repeat a query, and the honest
  statement is that `McpCatalogueTest` proves the correspondence by name and not by construction. A
  reviewer should read one reading tool against its controller side by side.
- **Found while writing [research.md](research.md), and not this feature's to fix**: the constitution
  says every account has exactly one Me contact, while `app/Models/Contact.php` documents one per
  vault and `CreateContact` writes it that way. Governance requires the pull request that discovers a
  conflict to say which of the two is wrong. This plan follows the code and states the divergence.
