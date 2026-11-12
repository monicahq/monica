---

description: "Task list for the API foundations and the API docs portal"
---

# Tasks: API foundations and the API docs portal

**Input**: Design documents from `/specs/003-api-docs-portal/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/openapi.yaml](contracts/openapi.yaml),
[quickstart.md](quickstart.md)

**Tests**: mandatory, not optional. Principle III of the constitution is non-negotiable: every model,
controller, action, helper, job, mailable and view model has a corresponding test, and no change ships
without one. Tests here ship alongside the code they cover rather than strictly before it, which is
what the rest of this repository does.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: which user story the task serves (US1, US2, US3)
- Every task names the exact file it touches

## Path conventions

Paths are repository-relative, from `/Users/regis/code/erica`. The layout is fixed by the
constitution and set out in [plan.md](plan.md) under Project Structure. Nothing here creates a
top-level folder that is not already named there.

## Reference material

`/Users/regis/code/beaver` is available in this session and ships this exact API surface and portal.
Read the file named beside each task before writing it. It is a reference to adapt, never to copy
wholesale: the response shapes, the paths, the version prefix and the builder's home all differ here,
and [research.md](research.md) records why for each one.

---

## Phase 1: Setup (shared infrastructure)

**Purpose**: the API needs somewhere to live before an endpoint can be written.

- [X] T001 Add `version` and `instance` to `config/monica.php`, each reading an env-backed value, following the file's existing comment style. `version` is the release the instance runs and `instance` is its host. The status endpoint reports both, and `env()` outside `config/` is forbidden.
- [X] T002 Create `routes/api.php` with `declare(strict_types=1);` and a single `Route::prefix('v1')->name('api.v1.')->group(...)` skeleton holding no route yet. Follow the route conventions in `.claude/rules/code-style.md`: class array notation, camelCase route names, no leading slash.
- [X] T003 Register `routes/api.php` from `bootstrap/app.php` by adding `api: __DIR__.'/../routes/api.php'` to the existing `withRouting()` call. The `shouldRenderJsonWhen` rule for `api/*` is already there and needs no change. Confirm with `php artisan route:list --path=api`.

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: the two pieces both the endpoints and their documentation depend on. US2 documents the
exact shapes these produce, so they are settled once, here.

**⚠️ CRITICAL**: no user story work begins until this phase is complete.

- [X] T004 [P] Create `app/Http/Resources/UserResource.php`, the first class in a folder the constitution reserves for exactly this. Add a `/** @mixin User */` docblock, extend `JsonResource`, and return the `type` / `id` / `attributes` / `links` envelope set out in [data-model.md](data-model.md). `id` is cast to string, timestamps are Unix integers via `->timestamp` (nullable ones via `?->timestamp`), enums are rendered as their value, and `links.self` points at `route('api.v1.me')`. No password, token hash, two-factor secret, recovery code or `last_login_ip` may appear.
- [X] T005 Add a `stateless` flag to `app/Actions/AttemptLogin.php`, promoted, `readonly`, defaulting to `false`, per [research.md](research.md) R3. When true the action validates the credentials without starting a session and without touching the remember duration. Everything else stays shared and unchanged: the rate limit keyed on address and IP, the `LoginFailed` email, the refusal of deactivated and single sign-on users, the identical failure message, the `last_login_at` stamp, the `CheckLastLogin` dispatch and the activity log entry. Do not let this action mint tokens; that stays `CreateApiKey`'s job.
- [X] T006 Extend `tests/Unit/Actions/AttemptLoginTest.php` for the stateless path: it returns the user without starting a session, it still refuses a deactivated user, a single sign-on user and a wrong password with the same message, it still rate limits, and it still stamps and logs. Do not weaken any existing assertion on the session path.

**Checkpoint**: `php artisan test --compact tests/Unit/Actions/AttemptLoginTest.php` is green and the route file is registered.

---

## Phase 3: User Story 1 - Reach the account from outside the browser (Priority: P1) 🎯 MVP

**Goal**: six working endpoints under `/api/v1`, each a thin controller over an action that already
exists.

**Independent Test**: run every call in section 3 of [quickstart.md](quickstart.md) against a fresh
database, with no interface involved. Check status, register, read the profile, update it, log in
again, log out, and confirm the revoked token stops working while the other one keeps working.

### Implementation for User Story 1

- [X] T007 [P] [US1] Create `app/Http/Controllers/Api/StatusController.php` with a `show` method returning `JsonResponse`. Open a database connection inside a `try`, answer 200 with `status`, `version` and `instance` from T001 on success, and 503 with `status` of `error` on `Throwable`. Adapt `beaver/app/Http/Controllers/Api/HealthController.php`, whose shape differs: see [research.md](research.md) R6.
- [X] T008 [P] [US1] Create `app/Http/Controllers/Api/Auth/RegistrationController.php` with a `store` method. Validate with array notation per [contracts/openapi.yaml](contracts/openapi.yaml): `first_name` and `last_name` required strings to 100 characters, `email` required, lowercase, unique on `User::class` and refused by the disposable address rule, `password` required and `confirmed` with `Password::min(8)->uncompromised()`, `device_name` nullable to 255. Call `CreateAccount`, then `event(new Registered($user))`, then `CreateApiKey` with a name built from `device_name`. Answer 201 with `token` and a null `expires_at`. Read `app/Http/Controllers/App/Auth/RegistrationController.php` first so the API applies the same rules as the sign-up screen.
- [X] T009 [P] [US1] Create `app/Http/Controllers/Api/Auth/LoginController.php` with `store` and `destroy`. `store` validates `email`, `password`, nullable `code` and nullable `device_name`; calls `AttemptLogin` with `stateless: true`; catches `ValidationException` and answers 401 with the action's own message, so the API never reveals which of a wrong password, a deactivated account or an unknown address it was. When the user has `two_factor_confirmed_at` set, require `code` and check it with `VerifyTwoFactorCode` before any token exists, answering 401 when it is missing or wrong. On success call `CreateApiKey` and answer 200 with `token` and a null `expires_at`. `destroy` calls `DestroyApiKey` with `apiKeyId: $request->user()->currentAccessToken()->id` and answers `response()->noContent(204)`.
- [X] T010 [P] [US1] Create `app/Http/Controllers/Api/MeController.php` with `show` and `update`. `show` returns `new UserResource($request->user())`. `update` validates `first_name`, `last_name` and `email` exactly as `app/Http/Controllers/App/Settings/Account/Profile/ProfileController.php` does, including the unique rule ignoring the current user and the disposable address rule, calls `UpdateUserProfile` with named arguments, and returns `new UserResource($request->user()->refresh())`. Preferences are not updated here.
- [X] T011 [US1] Wire all six routes in `routes/api.php`: `status` outside the auth group with `throttle:60,1`; `register` and `login` outside it with `throttle:6,1`; `logout`, `me` and `me` update inside `Route::middleware(['auth:sanctum', 'throttle:60,1'])`. Names are `api.v1.status`, `api.v1.register`, `api.v1.login`, `api.v1.logout`, `api.v1.me` and `api.v1.me.update`. Import the controllers in alphabetical order. Verify with `php artisan route:list --path=api`.
- [X] T012 [P] [US1] Create `tests/Feature/Controllers/Api/StatusControllerTest.php`: it answers 200 with the three keys and needs no token.
- [X] T013 [P] [US1] Create `tests/Feature/Controllers/Api/Auth/RegistrationControllerTest.php`: it creates the account and returns a token with 201; the account really has its first vault; it refuses a duplicate address, a disposable address, a weak password and a mismatched confirmation with 422 and `assertJsonValidationErrors`; and the response body contains no password. Fake the queue with `Queue::fake()`.
- [X] T014 [P] [US1] Create `tests/Feature/Controllers/Api/Auth/LoginControllerTest.php`: it returns a token for good credentials; it answers 401 with the same message for a wrong password, an unknown address, a deactivated user and a single sign-on user; it answers 401 when two-factor is confirmed and `code` is absent or wrong, and issues no token in that case; it returns a token when the code is right; and `destroy` revokes only the calling token, leaving the user's other tokens working. Use `Sanctum::actingAs($user)` for `destroy` and `Queue::fake()` throughout.
- [X] T015 [P] [US1] Create `tests/Feature/Controllers/Api/MeControllerTest.php` with a shared `$jsonStructure` property: `show` returns the caller and nobody else; a request with no token and a request with a revoked token both answer 401; `update` saves the change, returns the updated resource, and clears `email_verified_at` when the address changes; validation failures answer 422 with `assertJsonValidationErrors`; and no response contains a password, token hash, two-factor secret or recovery code.

**Checkpoint**: User Story 1 is complete and testable on its own. `php artisan test --compact tests/Feature/Controllers/Api` is green, and section 3 of the quickstart runs end to end. This is the MVP.

---

## Phase 4: User Story 2 - Find out what the API can do (Priority: P2)

**Goal**: the reference at `/docs/api`, matching `API portal.dc.html`.

**Independent Test**: open `/docs/api` signed out and check that every endpoint of User Story 1
appears with its verb, path, parameters, sample request and sample response, that the sidebar carries
the two groups from the supplied image, and that selecting an entry, switching sample language and
copying all work.

**Depends on User Story 1**: the synchronisation test in T020 compares the reference against
`routes/api.php` in both directions, so the routes have to exist for this story's suite to pass.

### Definition files for User Story 2

- [X] T016 [P] [US2] Create `resources/docs/api/01-getting-started.php` returning a group named `Getting started` with `'guide' => true` and five sections: `introduction`, `authentication-guide`, `rate-limits`, `pagination` and `errors`, in that order, matching the supplied image. Adapt beaver's file of the same name, correcting the paths to `/api/v1`, the rate limits to sixty per minute and six per minute for the credential endpoints, and the pagination example to the convention list endpoints will follow, per [research.md](research.md) R13. Guide sections are excluded from the synchronisation test, so their illustrative paths are allowed not to exist.
- [X] T017 [P] [US2] Create `resources/docs/api/02-health.php`: one group named `Health`, one section `status-show` labelled `Check status`, `GET /status`, `auth` false, with the rate limit sentence and the response body from [contracts/openapi.yaml](contracts/openapi.yaml).
- [X] T018 [P] [US2] Create `resources/docs/api/03-authentication.php`: one group named `Authentication` with `register-store`, `login-store` and `logout-destroy` in that order, each with its body parameters, its rate limit sentence, and the exact response shapes from the contract. `logout-destroy` has `'responseStatus' => 204` and no `response` key. Say plainly on `login-store` that a second factor is required when the user has one, and on `logout-destroy` that other tokens keep working.
- [X] T019 [P] [US2] Create `resources/docs/api/04-profile.php`: one group named `Your profile` with `me-show` and `me-update`, the six body parameters of the update reduced to the three this API accepts, and the note that changing the address clears its confirmation. State that preferences are not updated here.

### The builder for User Story 2

- [X] T020 [US2] Create `app/ViewModels/Docs/ApiDocsSection.php`, one normalized section: it takes the raw array from a definition file, applies the defaults set out in [data-model.md](data-model.md) (`label` falls back to `title`, `examplePath` to `path`, `auth` to true, `responseStatus` to 200, `paramsTitle` to `Parameters`), and merges `pathParams`, `queryParams` and `bodyParams` into one list. It reads and never writes.
- [X] T021 [US2] Create `app/ViewModels/Docs/ApiDocsCodeSamples.php`: for one section, build the cURL, JavaScript and PHP samples, each as a list of lines split into coloured parts the way the design file's `highlight` does, quoting strings in `--brand` and leaving the rest in `--ink`. Build the request body from the parameters carrying an `example`, unless the section overrides it with `exampleBody`. Drop the Authorization header when `auth` is false. Adapt the three sample builders in `beaver/app/Services/ApiDocumentation.php`, correcting the base URL to include `/v1`.
- [X] T022 [US2] Create `app/ViewModels/Docs/ApiDocsViewModel.php`: load `resources/docs/api/*.php` in filename order, cache the result on the instance, and expose `groups()`, `navigation()` (guide links and resource groups, as the sidebar needs them), `sections()`, `section(string $id)` and `documentedRoutes()`, which excludes guide groups. Add the static `baseUrl()` and `paginated()` helpers the definition files call. It queries no database and is consumed only by Blade.
- [X] T023 [US2] Create `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php`. Cover the defaults, the navigation split between guides and resources, and `section()` returning null for an unknown id. Then the two gates that make FR-019 real, each a separately named test so a failure says which direction broke: `it_documents_every_registered_route` and `it_registers_every_documented_route`, comparing verb and path against `routes/api.php` and excluding guide groups.

### The page for User Story 2

- [X] T024 [P] [US2] Create `resources/views/components/api-docs/method-badge.blade.php`: an anonymous component taking `method`, rendering the verb as text in the design's colours (GET `brand`, POST `success`, PUT and PATCH `warning`, DELETE `error`) with the matching tint behind it. The verb is always text, never colour alone.
- [X] T025 [P] [US2] Create `resources/views/components/api-docs/params.blade.php`: the parameter list from the design, each row carrying the name in mono, the type, and the words `required` or `optional` in text as well as colour, then the description. Renders nothing when the list is empty.
- [X] T026 [P] [US2] Create `resources/views/components/api-docs/code-panel.blade.php`: the design's code card, with the method badge, the three language tabs as real buttons in a labelled group, a copy button, and the highlighted lines. Alpine holds the selected language; the copy button announces its result in text rather than only changing colour.
- [X] T027 [P] [US2] Create `resources/views/components/api-docs/response-panel.blade.php`: the response card with its status code, a copy button, and the highlighted body. For a 204 it shows `No content.` in place of a body.
- [X] T028 [P] [US2] Create `resources/views/components/api-docs/rate-limit.blade.php`: the bordered box from the design carrying the section's rate limit sentence and the note that every response reports the remaining budget in `X-RateLimit-Remaining`.
- [X] T029 [US2] Create `resources/views/components/api-docs/endpoint.blade.php`, composing T024 to T028: the design's two column body, a prose column capped at 640px (title, method and path pill, description, extra paragraphs, parameters, rate limit box) and a sticky code column capped at 540px (code panel, response panel, the note about keys acting as you). It wraps to one column on a narrow screen and carries the section's `id` as its anchor.
- [X] T030 [P] [US2] Create `resources/views/components/api-docs/sidebar.blade.php`: Getting started as five plain links with no verb, then the resource groups with a verb column and the left rail from the design. Entries are real links to real anchors, so the page works with JavaScript off; the selected one carries `aria-current="page"`. Filtering is by the search text held in Alpine.
- [X] T031 [P] [US2] Create `resources/views/components/api-docs/subheader.blade.php`: the sticky bar with the API label, the `v1` badge, the labelled search input with its `⌘K` hint and shortcut, and the **Get an API key** button pointing at the existing security settings route. The label is a real `<label>`, not a placeholder standing in for one. Drop the design's Changelog and Guides links, which have nowhere to point.
- [X] T032 [US2] Create `resources/views/app/docs/api/show.blade.php`: the public page. A minimal shell (wordmark via `x-logo`, `x-theme-toggle`, a sign in link), then the subheader, the sidebar and every section rendered into the page with Alpine showing one at a time, per [research.md](research.md) R9. Use Tailwind utilities over the tokens already in `resources/css/app.css` and add no CSS, mapping `--error` to the `error` utility and `--duration-normal` to `--duration-standard` as R10 sets out.
- [X] T033 [US2] Create `app/Http/Controllers/App/Docs/ApiDocsController.php` with `index`, which leads to the page the reference opens with, and `show`, which renders one endpoint and answers 404 for an id nothing matches. Register `docs/api` and `docs/api/{section}` in `routes/web.php` as public routes named `docs.api.index` and `docs.api.show`, beside the other public routes and outside every auth group.
- [X] T034 [US2] Create `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`: the page answers 200 to a visitor who is not signed in, and shows the two sidebar headings, every group name and one title from each group.
- [X] T035 [P] [US2] Create the component tests under `tests/Feature/Views/Components/ApiDocs/`, one file per component, rendering with `$this->blade(...)` and asserting the classes that carry the meaning and the copy, per `.claude/rules/testing.md`.
- [X] T036 [US2] Walk the accessibility and responsiveness conditions in section 1 of [quickstart.md](quickstart.md), which Principle IX makes delivery conditions rather than a later pass: tab the whole page with focus visible throughout, check the sidebar and language tabs with a screen reader, confirm no meaning rests on colour alone, and check both themes at 375 pixels wide and at 200% zoom with no sideways scrolling of the page.

**Checkpoint**: User Stories 1 and 2 both work. The reference renders, and it cannot drift from the routes without the suite going red.

---

## Phase 5: User Story 3 - Hand the reference to a machine (Priority: P3)

**Goal**: the reference as plain text, whole or one endpoint at a time.

**Independent Test**: `curl /docs/api.md` and `curl /docs/api/me-show.md` both return markdown
describing the same endpoints the page shows.

- [X] T037 [US3] Create `resources/views/app/docs/api/markdown.blade.php`, rendering a section or the whole reference as markdown: heading, verb and path, description, parameters as a list, the cURL sample in a fenced block, and the response in another. Rendering through Blade rather than concatenating strings in PHP is what keeps the view model Blade-only, per [research.md](research.md) R2.
- [X] T038 [US3] Add a `markdown()` accessor to `app/ViewModels/Docs/ApiDocsViewModel.php` that renders T037 for one section and for the whole reference, and expose the per-section string to the page so the Copy for LLM button has something to put on the clipboard.
- [X] T039 [US3] Create `app/Http/Controllers/App/Docs/ApiDocsMarkdownController.php` with `index` and `show`, each returning the rendered markdown with a `text/markdown` content type, and register `Route::get('docs/api.md', ...)` and `Route::get('docs/api/{section}.md', ...)` in `routes/web.php`, constraining `section` to `[a-z0-9\-]+` and naming them `docs.api.markdown.index` and `docs.api.markdown.show`. An unknown section answers 404.
- [X] T040 [US3] Add the **Copy for LLM** and **View as Markdown** buttons to `resources/views/components/api-docs/endpoint.blade.php`, positioned as the design places them above the title. Copy for LLM writes the section's markdown to the clipboard and reports it in text; View as Markdown links to the section's `.md` route.
- [X] T041 [US3] Create `tests/Feature/Controllers/App/Docs/ApiDocsMarkdownControllerTest.php`: both routes answer 200 with a `text/markdown` content type to a visitor who is not signed in, the whole reference contains every documented endpoint, one section returns that section alone, and an unknown section answers 404.

**Checkpoint**: all three user stories work independently.

---

## Phase 6: Polish and cross-cutting concerns

- [X] T042 Run `vendor/bin/pint --format agent` and fix everything it reports.
- [X] T043 Run `vendor/bin/phpstan analyse` and fix every new finding rather than ignoring it. Expect the generics on `UserResource` and the view model's array shapes to need docblocks.
- [X] T044 Run `php artisan monica:localize --check`. It should report nothing new: the portal ships in English by decision, recorded in the plan's Complexity Tracking, and no other screen changed. Anything it does report is a string that leaked into a screen that is not the portal, and must be translated in all nine locales.
- [X] T045 Run the full suite on SQLite, MySQL and PostgreSQL, which is what CI gates on. No database-specific SQL was written, so a failure on one driver alone is a real defect.
- [X] T046 Walk [quickstart.md](quickstart.md) end to end from a clean `migrate:fresh`, including the refusal table in section 4.
- [X] T047 Re-read the six controllers against Principle I and remove anything that is not transport. Validation, a call to an action, and a response is the whole of what belongs there.

---

## Dependencies and execution order

### Phase dependencies

- **Setup (Phase 1)**: no dependencies.
- **Foundational (Phase 2)**: needs Setup. Blocks every user story.
- **User Story 1 (Phase 3)**: needs Foundational. Independent of US2 and US3.
- **User Story 2 (Phase 4)**: needs Foundational, and needs US1's routes to exist for T023 to pass.
- **User Story 3 (Phase 5)**: needs US2, since it renders the same sections through a second template.
- **Polish (Phase 6)**: needs every story you intend to ship.

### Story dependencies

Unlike the usual shape, these three stories are a chain rather than three independent slices, and the
plan says so rather than pretending otherwise. US2 documents US1 and is verified against it. US3
re-renders US2. Each is still independently demonstrable once the one before it is in place, and each
is a sensible place to stop.

### Within each story

- Definition files and controllers before the routes that point at them.
- The builder before the components that render it.
- Leaf components before the component that composes them.
- Tests with the code they cover, and green before the next task starts.

### Parallel opportunities

- T004 runs alone in Phase 2; T005 and T006 are one piece of work on one action.
- Phase 3: T007 to T010 are four separate controller files and run together. T011 wires them and must
  follow all four. T012 to T015 are four separate test files and run together once T011 is in.
- Phase 4: T016 to T019 are four separate definition files and run together. T024 to T028 are five
  separate leaf components and run together. T030 and T031 run together. T035 splits by component.
- Phase 5 is a single thread. Every task touches something the previous one produced.

---

## Parallel example: User Story 1

```bash
# The four controllers, four separate files:
Task: "Create app/Http/Controllers/Api/StatusController.php"
Task: "Create app/Http/Controllers/Api/Auth/RegistrationController.php"
Task: "Create app/Http/Controllers/Api/Auth/LoginController.php"
Task: "Create app/Http/Controllers/Api/MeController.php"

# Then T011 wires the routes, then the four test files together:
Task: "Create tests/Feature/Controllers/Api/StatusControllerTest.php"
Task: "Create tests/Feature/Controllers/Api/Auth/RegistrationControllerTest.php"
Task: "Create tests/Feature/Controllers/Api/Auth/LoginControllerTest.php"
Task: "Create tests/Feature/Controllers/Api/MeControllerTest.php"
```

---

## Implementation strategy

### MVP first (User Story 1 only)

1. Phase 1, Setup.
2. Phase 2, Foundational.
3. Phase 3, User Story 1.
4. **Stop and validate**: run section 3 of the quickstart against a fresh database.
5. This is shippable on its own. Monica has an API for the first time, six endpoints of it, and the
   security screen's keys finally have something to open. The reference is still missing, so it is
   shippable rather than announceable.

### Incremental delivery

1. Setup and Foundational, then US1, then stop and validate. MVP.
2. US2, then stop and validate. The API is now discoverable, and the synchronisation test starts
   protecting it from drift on every later change.
3. US3, then stop and validate. The reference is machine readable.

### Notes

- `[P]` means different files and no dependency on unfinished work.
- Commit after each task or each logical group, with a Conventional Commits subject naming the
  outcome and not the mechanics.
- Every checkpoint is a real stopping place. None of the three stories leaves the tree broken.
- Beaver is a reference to adapt, not to copy. The paths carry `/v1`, the response shapes follow the
  design file, the builder is a view model rather than a service, and there is no `ApiResponses`
  trait, because `app/Traits` does not exist here and three JSON literals do not need one.
