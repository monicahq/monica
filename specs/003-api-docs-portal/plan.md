# Implementation Plan: API foundations and the API docs portal

**Branch**: `2026-08-27-api-doc-portal` | **Date**: 2026-08-27 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/003-api-docs-portal/spec.md`

**Base**: cut from `2026-08-26-restore-former-design` (pull request 93), merged up to `a573c60`. Every fact in
[research.md](research.md) was verified against that commit, including the design tokens in
`resources/css/app.css`, which pull request 93 rewrites. Re-verify R10 and R11 if this branch is
rebased onto a later head of it.

## Summary

Erica gets its first API. Six endpoints (status, register, log in, log out, get profile, update
profile) under `/api/v1`, each one a thin controller over an action that already exists, plus a public
reference at `/docs/api` built from PHP definition files and rendered with Blade.

The API surface is copied from beaver, which ships exactly these endpoints. The portal's look comes
from `API portal.dc.html`, which shows one endpoint at a time rather than stacking every section on
one long page as beaver does, and here that is one endpoint to a page with an address of its own
rather than an anchor into a long one. Erica's `resources/css/app.css` already
defines the same token vocabulary as the design system the file imports, so the portal is Tailwind
utilities over existing tokens and needs no new CSS.

The one piece of real machinery is the reference builder. It reads `resources/docs/api/*.php`,
normalizes each section, and generates the cURL, JavaScript and PHP samples, the highlighted JSON and
a markdown rendering. Beaver puts it in `app/Services`. The constitution fixes the repository layout
and `app/Services` does not exist here, so it lives in `app/ViewModels/Docs` instead, and the markdown
endpoints render through a Blade template so the view model stays Blade-only as Principle I requires.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 13.8, Laravel Sanctum 4.3 (already installed, already minting keys
through `CreateApiKey`), Blade, Tailwind CSS v4, Alpine.js, Alpine Ajax, Turbo

**Storage**: The existing schema. This feature adds no migration. Tokens live in
`personal_access_tokens`, which Sanctum already owns.

**Testing**: PHPUnit. `tests/Feature/Controllers/Api` for the endpoints,
`tests/Feature/Controllers/App/Docs` for the portal, `tests/Unit/ViewModels/Docs` for the builder,
plus a route-to-documentation synchronisation test.

**Target Platform**: Server rendered web application, self hosted and hosted alike

**Project Type**: Web service plus one public server rendered page

**Performance Goals**: A page of the portal is one request that reads a handful of PHP files and
renders. No database query is needed to draw it. Sidebar links carry `data-turbo="true"`, so moving
between endpoints swaps the document rather than reloading the whole page.

**Constraints**: The suite passes on SQLite, MySQL and PostgreSQL. PHPStan level 5 over `app/`,
`routes/` and `database/`. Pint clean. WCAG 2.2 AA on the portal.

**Scale/Scope**: 6 endpoints, 11 documented sections in 4 definition files, 11 public pages plus their
markdown counterparts, roughly 10 new classes and 10 new Blade files.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict | How this plan satisfies it |
|---|---|---|
| I. Actions hold the business logic | Pass | No new business logic. `CreateAccount`, `AttemptLogin`, `CreateApiKey`, `DestroyApiKey` and `UpdateUserProfile` are reused as they stand, apart from one stateless flag on `AttemptLogin` (see research R3). Controllers do transport only. The docs view model reads and never writes, and is consumed only by Blade. |
| II. Simplicity over abstraction | Pass | No `ApiResponses` trait: `app/Traits` does not exist here and three JSON literals in three controllers are cheaper than a trait to house them. The builder is one view model with two small collaborators beside it, which is the documented pattern for a rich page. |
| III. Tested before merged | Pass | Every endpoint, the portal, the markdown output and the builder get tests. The route synchronisation test fails the suite if the reference and `routes/api.php` disagree in either direction. |
| IV. Laravel conventions | Pass | `withRouting(api:)` registers `routes/api.php`. Route names camelCase under an `api.v1.` prefix, public URLs kebab-case, array validation rules, early returns, no `else`, one class per file, no new migration so no `down()` question. |
| V. Localized experience | **Deviates** | The portal ships in English only, by explicit decision. Recorded in Complexity Tracking. |
| VI. Nothing changes under anybody's feet | Pass | Versioning the API from the first endpoint is this principle applied to the API surface. `/api/v1` callers never move because a v2 arrives. |
| VII. Stable core | Pass | Introduces no concept and no second personalisation mechanism. |
| VIII. Nothing disappears silently | Pass | Logging out revokes one token and says so; the caller's other tokens are untouched. Revocation goes through `DestroyApiKey`, so it lands in the activity log like every other revocation. |
| IX. Accessible to everybody | Pass | The portal is a delivery condition: keyboard reachable sidebar and language tabs, `aria-current` on the selected endpoint, method carried by text and not only by colour, visible focus, readable at 200%, and the reduced motion block already in `app.css`. |
| X. Everything goes through the API | Pass, with existing debt | This is the principle's first instalment. Two known gaps stay open and are recorded in Complexity Tracking: token scopes cannot be limited, and the rest of the product has no API yet. |

**Layout gates**: `app/Http/Controllers/Api`, `routes/api.php` and `app/Http/Resources` are named in
the constitution as what gets built when the API is built, so none of them needs an amendment.
`app/Services` and `app/Traits` would each be a new top-level folder under `app/` and are avoided.

**Post-design re-check**: unchanged. Phase 1 added no class outside the folders above.

## Project Structure

### Documentation (this feature)

```text
specs/003-api-docs-portal/
├── plan.md              # This file
├── spec.md
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── openapi.yaml     # Phase 1 output
├── checklists/
│   └── requirements.md
└── tasks.md             # Created by /speckit-tasks, not here
```

### Source Code (repository root)

```text
app/Http/Controllers/Api/
├── StatusController.php              # GET /api/v1/status
├── MeController.php                  # GET and PUT /api/v1/me
└── Auth/
    ├── RegistrationController.php    # POST /api/v1/register
    └── LoginController.php           # POST /api/v1/login, DELETE /api/v1/logout

app/Http/Controllers/App/Docs/
├── ApiDocsController.php             # GET /docs/api and /docs/api/{section}
└── ApiDocsMarkdownController.php     # GET /docs/api.md and /docs/api/{section}.md

app/Http/Resources/
└── UserResource.php                  # the /me envelope

app/ViewModels/Docs/
├── ApiDocsViewModel.php              # loads the definition files, builds navigation and sections
├── ApiDocsSection.php                # one normalized endpoint
└── ApiDocsCodeSamples.php            # cURL, JavaScript and PHP samples for one section

app/Actions/
└── AttemptLogin.php                  # gains a stateless flag, see research R3

routes/
└── api.php                           # new, registered from bootstrap/app.php

resources/docs/api/
├── 01-getting-started.php            # Introduction, Authentication, Rate limits, Pagination, Errors
├── 02-health.php                     # Check status
├── 03-authentication.php             # Register, Log in, Log out
└── 04-profile.php                    # Get your profile, Update your profile

resources/views/app/docs/api/
├── show.blade.php                    # one endpoint of the reference
└── markdown.blade.php                # the text/markdown rendering

resources/views/components/api-docs/
├── subheader.blade.php               # API, v1 badge, search, Get an API key
├── sidebar.blade.php                 # Getting started links, Resources groups
├── endpoint.blade.php                # one endpoint: prose column plus code column
├── params.blade.php
├── code-panel.blade.php              # language tabs, copy
├── response-panel.blade.php
├── method-badge.blade.php
└── rate-limit.blade.php

tests/Feature/Controllers/Api/
├── StatusControllerTest.php
├── MeControllerTest.php
└── Auth/
    ├── RegistrationControllerTest.php
    └── LoginControllerTest.php

tests/Feature/Controllers/App/Docs/
├── ApiDocsControllerTest.php
└── ApiDocsMarkdownControllerTest.php

tests/Feature/Views/Components/ApiDocs/     # one file per component
tests/Unit/ViewModels/Docs/
└── ApiDocsViewModelTest.php          # includes the route synchronisation assertions
```

**Structure Decision**: everything lands in folders the constitution already fixes. The API gets the
`Api` controller namespace and `routes/api.php` that the constitution reserves for it, and its
resource classes go in `app/Http/Resources`, also named there. The portal is a public page, and public
pages in this repository already live under `App` controllers and `resources/views/app` (the
marketplace and the shared contact screens both do), so the docs portal follows them rather than
opening a `Marketing` namespace the constitution says not to write as though it exists.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Principle V: the portal ships in English only, with no `__()` calls | Explicit decision by the maintainer during planning. The reference is thousands of words of technical prose, and translating it into nine locales would roughly triple the feature and add translation debt to every endpoint documented afterwards. API references are conventionally English. | Translating the chrome only was offered and turned down as a half measure that still fails the principle while adding upkeep. Full translation was turned down on cost. This is a standing exception for `resources/docs/api` and the portal views, not a licence to skip `__()` anywhere else. |
| Principle X: tokens are issued with no abilities, so their scopes cannot be limited | Already recorded as follow-up TODO 1 in the constitution's own sync report. `CreateApiKey` mints a token that grants everything. Fixing it means designing an ability vocabulary for a product that has six endpoints, which is a decision better taken when there is a surface worth scoping. | Adding abilities now would invent a vocabulary against six endpoints and force a second pass once the rest of the product is exposed. The debt is unchanged by this feature, not increased. |
| Principle X: only six endpoints exist, so most of the product is still unreachable | The spec bounds this feature to the eight sidebar entries in the supplied design. | Building the whole API in one change is not reviewable. The definition file format and the synchronisation test are built so each later resource is an additive file plus routes. |
