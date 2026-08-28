# Implementation Plan: API keys and logs endpoints

**Branch**: `2026-08-28-api` | **Date**: 2026-08-28 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/004-api-keys-logs/spec.md`

## Summary

Six endpoints are added to the version one API: four for API keys (list, read one, create, revoke)
and two for the account journal (list, read one). Nothing new is invented behind them. Key creation
and revocation already exist as `CreateApiKey` and `DestroyApiKey`, which the security screen and
the API login endpoint already call, and the journal already exists as the `Log` model the settings
screen reads. The work is therefore transport and presentation: two controllers under
`app/Http/Controllers/Api`, two Eloquent resources, six routes, two definition files for the public
reference, and the tests for all of it.

Three decisions shape the code and are argued in [research.md](./research.md): both lists follow the
pagination convention the reference already promises, which is page based with a `total`,
`CreateApiKey` starts returning Sanctum's `NewAccessToken` so the create endpoint can describe the key
it just minted without querying for it again, and the authenticated API group starts setting the
locale from the user so that a journal sentence comes back in the language its reader chose.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Laravel Sanctum (personal access tokens), Blade for the
reference portal. No new dependency.

**Storage**: The existing `personal_access_tokens` and `logs` tables. No migration.

**Testing**: PHPUnit, feature tests under `tests/Feature/Controllers/Api`, authenticated with
`Sanctum::actingAs()`. The suite runs on SQLite, MySQL and PostgreSQL, so no driver specific SQL.

**Target Platform**: The JSON API served under `/api/v1`, plus the public reference at `/docs/api`.

**Project Type**: Server rendered Laravel application with a JSON API beside it.

**Performance Goals**: A page of the journal is answered from an indexed read, at the size one
account's journal actually reaches. Deep paging costs a count and an offset, which is the trade
accepted in order to keep the pagination convention the reference already published.

**Constraints**: Sixty requests per minute per token, the same limit the rest of the authenticated
API carries. A secret key value may appear in exactly one response body and nowhere else.

**Scale/Scope**: Two controllers, two resources, six routes, two reference files, two new test
files, and small edits to four existing files.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | How this feature satisfies it |
| --- | --- |
| I. Actions hold the business logic | The two write endpoints call `CreateApiKey` and `DestroyApiKey`, the same actions the security screen calls. Nothing is reimplemented, and the journal entries those actions write keep being written. The read endpoints query the models directly, as `MeController` already does, because view models are for Blade and nothing else. |
| II. Simplicity over abstraction | Two controllers, two resources, no base class, no service layer, no trait. The page size clamp is three lines repeated in two controllers rather than a helper, which is what the rule asks for. |
| III. Tested before merged | One feature test per controller, covering the success case, the validation case, the cross account case and the unauthenticated case. The touched action, middleware and web controller keep their tests, updated for the new return type. |
| IV. Laravel conventions | Kebab-case URLs (`api-keys`), camelCase route names and parameters (`apiKeys.show`, `{apiKey}`), one class per file, strict types, array notation for validation rules, early returns. |
| V. Localized and safe | The journal sentence is translated, and this feature makes the authenticated API set the locale from the user so it comes back in their language rather than the instance default. No new user facing string is expected; `monica:localize` is run to confirm it. |
| VI. Nothing changes under anybody's feet | Nothing published or versioned is touched. The one change to an existing contract, the return type of `CreateApiKey`, is internal and every call site moves with it in the same change. |
| VII. Stable core | No new personalisation mechanism, no new concept. |
| VIII. Nothing disappears silently | Directly served: the journal is append only, so the API offers no way to create, change or delete an entry. Revoking a key is the one destruction here, it is explicit, and it is itself journalled. |
| IX. Accessible to everybody | No new screen. The two reference groups render through the existing `api-docs` components, which are already tested. |
| X. Everything goes through the API | This feature exists to serve it, and it closes the one gap that was awkward: the credential for the API was the only thing the API could not talk about. |

**Result: PASS**, with one pre-existing deviation carried rather than fixed, recorded in Complexity
Tracking below.

**Re-check after Phase 1 design: PASS.** The design added no class the gate did not anticipate. The
two edits that reach outside the feature, the return type of `CreateApiKey` and the session guard in
`SetLocale`, are both narrow, both keep their existing tests passing, and both are argued in
research.md.

## Project Structure

### Documentation (this feature)

```text
specs/004-api-keys-logs/
├── plan.md              # This file
├── spec.md              # The feature specification
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── api-keys-and-logs.md
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 output, written by /speckit-tasks
```

### Source Code (repository root)

New files:

```text
app/Http/Controllers/Api/ApiKeyController.php     # index, show, create, destroy
app/Http/Controllers/Api/LogController.php        # index, show
app/Http/Resources/ApiKeyResource.php             # the api_key envelope
app/Http/Resources/LogResource.php                # the log envelope
resources/docs/api/05-api-keys.php                # the API keys group of the reference
resources/docs/api/06-logs.php                    # the Logs group of the reference
tests/Feature/Controllers/Api/ApiKeyControllerTest.php
tests/Feature/Controllers/Api/LogControllerTest.php
```

Edited files:

```text
routes/api.php                                          # six routes, one import each
config/monica.php                                       # the default and maximum page size
app/Actions/CreateApiKey.php                            # returns NewAccessToken
app/Http/Controllers/App/Settings/Account/Security/ApiKeyController.php   # reads plainTextToken
app/Http/Controllers/Api/Auth/LoginController.php                          # reads plainTextToken
app/Http/Middleware/SetLocale.php                       # session safe, so the API can use it
tests/Unit/Actions/CreateApiKeyTest.php                 # the new return type
tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php               # the two new groups
```

**Structure Decision**: The `Api` namespace is flat, matching what is already there
(`Api/MeController`, `Api/StatusController`, with `Api/Auth` for the credential endpoints). The web
controllers for the same two concepts sit deep under `App/Settings/Account`, and that depth is a
property of where the screens live in the settings tree, not of the resources themselves. A token
reaches its own keys and its own account's journal with no tree to walk, so the API says
`/api/v1/api-keys` and `/api/v1/logs` and the controllers sit directly under `Api`.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Principle X asks for keys whose scopes can be limited. The keys these endpoints list and mint grant everything their holder can do. | The gap is pre-existing: `CreateApiKey` has always minted an ability-less token, and the constitution's own follow-up list records it as debt at item 1. Adding scopes would change what a key is, would touch the security screen, the login endpoint and the middleware, and would need the spec to settle which scopes exist before code depends on them. | Shipping scopes inside this feature was rejected because it makes a six endpoint transport change into a redesign of the credential, and because the constitution's governance section says a question the spec has not settled is not answered by a choice buried in a pull request. The endpoints are shaped so that an `abilities` attribute is additive later: it becomes a new attribute in the resource and a new optional body parameter on create, and no existing caller breaks. |
