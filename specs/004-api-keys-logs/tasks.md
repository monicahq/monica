---

description: "Task list for the API keys and logs endpoints"
---

# Tasks: API keys and logs endpoints

**Input**: Design documents from `/specs/004-api-keys-logs/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md),
[data-model.md](./data-model.md), [contracts/api-keys-and-logs.md](./contracts/api-keys-and-logs.md)

**Tests**: Required, and not optional here. Constitution Principle III is non-negotiable, and SC-002
of the spec asks for a success case, a refusal case and a cross account case per operation.

**Organization**: Grouped by user story, so either can ship on its own.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: US1 is API keys, US2 is logs
- Paths are from the repository root

---

## Phase 1: Setup

**Purpose**: The one setting both stories read.

- [X] T001 Add `api_default_items_per_page` (default 10, from `MONICA_API_DEFAULT_ITEMS_PER_PAGE`) and `api_maximum_items_per_page` (default 100, from `MONICA_API_MAXIMUM_ITEMS_PER_PAGE`) to `config/monica.php`, each with the explanatory comment block every other value in that file carries

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The middleware stack of the authenticated API group, which both stories run inside.

**⚠️ CRITICAL**: T002 and T003 land together. Registering the middleware without the guard makes
every API request throw, because a token request carries no session.

- [X] T002 Guard the session read in `app/Http/Middleware/SetLocale.php` so `preferred()` asks `$request->hasSession()` before `$request->session()->get('locale')`, leaving the order of preference (session, then the user's locale, then the fallback) unchanged
- [X] T003 Add `set.locale` to the `['auth:sanctum', 'throttle:60,1']` middleware group in `routes/api.php`, so an authenticated caller is answered in the language on their account

**Checkpoint**: The API group sets the locale and still answers. Every existing API test proves the
guard works, since without it they would all fail with "Session store not set on request".

---

## Phase 3: User Story 1 - Manage API keys without opening the app (Priority: P1) 🎯 MVP

**Goal**: List, read, mint and revoke API keys through the API, so a key can be rotated from a script.

**Independent Test**: Sign in for a token through `POST /api/v1/login`, then use only that token to
list keys, read one, create a second, and revoke it. Nothing in the browser is needed, and the
security screen afterwards shows exactly what the calls did.

### The action and its existing callers

- [X] T004 [US1] Change `CreateApiKey::execute()` in `app/Actions/CreateApiKey.php` to return Sanctum's `NewAccessToken` instead of the plain text string, keeping the journal entry it writes untouched, and update the class docblock, which currently says nothing accepts these keys yet
- [X] T005 [P] [US1] Update `app/Http/Controllers/App/Settings/Account/Security/ApiKeyController.php` to read `->plainTextToken` off what `CreateApiKey` returns before putting it on the session
- [X] T006 [P] [US1] Update `app/Http/Controllers/Api/Auth/LoginController.php` to read `->plainTextToken` off what `CreateApiKey` returns before answering with it
- [X] T007 [US1] Update `tests/Unit/Actions/CreateApiKeyTest.php` for the new return type: assert `NewAccessToken` comes back, that `->plainTextToken` is a non-empty string, that `->accessToken` is the row now in `personal_access_tokens`, and keep the existing assertion that `LogUserAction` is queued

### The endpoints

- [X] T008 [P] [US1] Create `app/Http/Resources/ApiKeyResource.php`, a `JsonResource` with a `/** @mixin PersonalAccessToken */` docblock, rendering `type` `api_key`, the id as a string, and the attributes `name`, `last_used_at`, `expires_at`, `created_at` and `updated_at` as Unix integers (nullable ones with `?->timestamp`), plus `links.self` pointing at `route('api.v1.apiKeys.show', $this->id)`. The hash and the plain text value never appear
- [X] T009 [US1] Create `app/Http/Controllers/Api/ApiKeyController.php` with `index`, `show`, `create` and `destroy`, following [contracts/api-keys-and-logs.md](./contracts/api-keys-and-logs.md): scope everything through `$request->user()->tokens()`, cursor paginate `index` by descending id with `per_page` clamped between one and `config('monica.api_maximum_items_per_page')`, validate `name` on `create` with `['required', 'string', 'min:3', 'max:255']`, call `CreateApiKey` and `DestroyApiKey` with named arguments, answer `create` with 201 carrying the resource under `data` and the plain text under a sibling `token` key, and answer `destroy` with `response()->noContent(204)`
- [X] T010 [US1] Register the four routes in `routes/api.php` inside the authenticated group, named `apiKeys`, `apiKeys.show`, `apiKeys.create` and `apiKeys.destroy`, at `api-keys` and `api-keys/{apiKey}`, with `->where('apiKey', '[1-9][0-9]*')` on the two that take one, and add the controller import in alphabetical order

### Tests for User Story 1

- [X] T011 [US1] Create `tests/Feature/Controllers/Api/ApiKeyControllerTest.php` with `declare(strict_types=1)`, `use RefreshDatabase`, `Queue::fake()` where an action runs, `Sanctum::actingAs($user)`, a shared `$jsonStructure`, and these cases: it lists the keys of the caller newest first; it does not list the keys of another account; it clamps an absurd `per_page` to the maximum and a zero to one; it shows one key; it returns not found for a key of another account; it creates a key and answers 201 with the plain text token beside the data; it validates the name when creating; it deletes a key and answers 204 with the row gone; it returns not found when deleting a key of another account, which still authenticates; no response other than the creation carries the plain text value; and each of the four answers 401 without a token
- [X] T012 [US1] Run `php artisan test --compact tests/Feature/Controllers/Api/ApiKeyControllerTest.php tests/Unit/Actions/CreateApiKeyTest.php tests/Unit/Actions/DestroyApiKeyTest.php tests/Feature/Controllers/Api/Auth/LoginControllerTest.php tests/Feature/Controllers/App/Settings/Account/Security/ApiKeyControllerTest.php` and fix what it reports

### Documentation for User Story 1

- [X] T013 [P] [US1] Create `resources/docs/api/05-api-keys.php` returning the group name `API keys` and four sections in order, with the ids `api-keys-index`, `api-keys-show`, `api-keys-create` and `api-keys-destroy`, the labels `List keys`, `Get a key`, `Create a key` and `Delete a key`, paths written exactly as the routes register them (`/api-keys`, `/api-keys/{apiKey}`), a `rateLimit` on every section since it has no default, sample responses matching `ApiKeyResource` exactly (string id, Unix integers, the cursor paginated envelope on the list), `responseStatus` 201 on the create and 204 with no `response` on the delete, and no dashes used as punctuation in the copy
- [X] T014 [US1] Add an `assertSee` for one of the API keys titles to `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`, then run `php artisan test --compact tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php tests/Feature/Controllers/App/Docs/ApiDocsMarkdownControllerTest.php`, which is what proves the four routes and the four documented endpoints match in both directions

**Checkpoint**: The four key endpoints work, are tested and are documented. This is a shippable
increment on its own.

---

## Phase 4: User Story 2 - Read the account journal from a script (Priority: P2)

**Goal**: List and read the account journal through the API, in the caller's language.

**Independent Test**: Create some activity in an account, then read the journal through the API and
confirm the same entries the settings screen shows come back, in the same order, each with a readable
sentence.

### The endpoints

- [X] T015 [P] [US2] Create `app/Http/Resources/LogResource.php`, a `JsonResource` with a `/** @mixin Log */` docblock, rendering `type` `log`, the id as a string, and the attributes `author`, `action`, `parameters`, `description`, `source` (its enum value), `ip_address` and `created_at` as a Unix integer, plus `links.self` pointing at `route('api.v1.logs.show', $this->id)`. The `changes` column is deliberately withheld, and the docblock says why, pointing at the unsettled question about values somebody marked sensitive
- [X] T016 [US2] Create `app/Http/Controllers/Api/LogController.php` with `index` and `show` only, scoping both through `$request->user()->account->logs()`, eager loading `user` so the `author` accessor costs no query per row, cursor paginating `index` by descending id with the same `per_page` clamp as the keys, and letting `findOrFail` produce the 404
- [X] T017 [US2] Register the two routes in `routes/api.php` inside the authenticated group, named `logs` and `logs.show`, at `logs` and `logs/{log}`, with `->where('log', '[1-9][0-9]*')`, and add the controller import in alphabetical order

### Tests for User Story 2

- [X] T018 [US2] Create `tests/Feature/Controllers/Api/LogControllerTest.php` following the same conventions as the keys test, with these cases: it lists the journal of the account newest first; it does not list the journal of another account; it includes an entry the application wrote on its own, whose author reads as the system and whose address is null; it clamps `per_page`; it shows one entry; it returns not found for an entry of another account; it returns the description in the language on the caller's account, which is what covers T002 and T003; it does not expose the `changes` column; and both endpoints answer 401 without a token
- [X] T019 [US2] Run `php artisan test --compact tests/Feature/Controllers/Api/LogControllerTest.php` and fix what it reports

### Documentation for User Story 2

- [X] T020 [P] [US2] Create `resources/docs/api/06-logs.php` returning the group name `Logs` and two sections in order, with the ids `logs-index` and `logs-show`, the labels `List logs` and `Get a log`, the paths `/logs` and `/logs/{log}`, a `rateLimit` on both, sample responses matching `LogResource` exactly, and a sentence in the body saying the journal is written by the application and can only be read, which is why there is nothing to create, change or delete here
- [X] T021 [US2] Add an `assertSee` for one of the Logs titles to `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`, then run `php artisan test --compact tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php tests/Feature/Controllers/App/Docs/ApiDocsMarkdownControllerTest.php`

**Checkpoint**: All six endpoints work, are tested and are documented.

---

## Phase 5: Polish and Cross-Cutting Concerns

- [X] T022 Run `php artisan monica:localize --check` and translate anything it reports in every configured locale by running `php artisan monica:localize`, never by editing `lang/*.json` by hand. Nothing new is expected, since the reference is English copy in PHP files and the journal sentences already exist
- [X] T023 Run `vendor/bin/pint --dirty --format agent` and commit what it changes
- [X] T024 Run `vendor/bin/phpstan analyse` and fix anything new at level 5 over `app/`, `routes/` and `database/`
- [X] T025 Run the whole suite with `php artisan test --compact`, because `CreateApiKey` now has three callers
- [ ] T026 Walk section 3 and section 4 of [quickstart.md](./quickstart.md) against a live instance, including the two checks no test covers: revoking the key you are calling with, and copying a sample out of `/docs/api` and running it unchanged

---

## Dependencies and Execution Order

### Phase dependencies

- **Setup (T001)**: nothing depends on it being first except T009 and T016, which read the value.
- **Foundational (T002, T003)**: blocks both stories. T003 without T002 breaks every API endpoint.
- **User Story 1 (T004 to T014)**: needs Phase 2 done. Independent of User Story 2.
- **User Story 2 (T015 to T021)**: needs Phase 2 done. Independent of User Story 1.
- **Polish (T022 to T026)**: after whichever stories are being shipped.

### Within User Story 1

T004 comes before T005, T006 and T007, which all read its new return type, and before T009, which
needs the token row it returns. T008 comes before T009, which renders it. T010 comes before T011,
since the test calls the routes. T013 has to land in the same change as T010: the moment a route
exists with no documentation, `ApiDocsViewModelTest` fails, which is the point of it.

### Within User Story 2

T015 before T016, T016 before T017, T017 before T018. T020 has to land in the same change as T017,
for the same reason as above.

### The one file both stories touch

`routes/api.php` is edited by T003, T010 and T017. Those three are never parallel with each other.
Everything else the two stories touch is in separate files.

### Parallel opportunities

- T005, T006 and T007 are three different files reading the same new return type, so they go together
  once T004 lands.
- T008 and T013 can be written while the controller is being written, since neither depends on it.
- T015 and T020 can be written before the log controller exists.
- With two people, User Story 1 and User Story 2 run side by side once Phase 2 is done, as long as the
  edits to `routes/api.php` are sequenced.

---

## Parallel Example: User Story 1

```bash
# Once T004 has landed, these three are independent files:
Task: "Update the web ApiKeyController to read plainTextToken"
Task: "Update the API LoginController to read plainTextToken"
Task: "Update tests/Unit/Actions/CreateApiKeyTest.php for the new return type"

# And these two need nothing from the controller:
Task: "Create app/Http/Resources/ApiKeyResource.php"
Task: "Create resources/docs/api/05-api-keys.php"
```

---

## Implementation Strategy

### MVP first (User Story 1 only)

1. Phase 1, then Phase 2.
2. Phase 3, the four key endpoints.
3. **Stop and validate**: mint, list, read and revoke a key with nothing but a token.
4. Shippable. The journal endpoints can follow in a second pull request.

### Incremental delivery

Phase 1 and 2 together, then User Story 1, then User Story 2, then Polish. Each story is a pull
request whose title reads as one short sentence naming the outcome, per the constitution:
`feat: manage api keys through the api`, then `feat: read the account journal through the api`.

---

## Notes

- Every new PHP file starts with `declare(strict_types=1);` and holds exactly one class.
- Tests carry no comments. The method name is the description.
- Test data uses The Office, as the testing rules ask: Michael Scott, Dunder Mifflin, a key called
  the Dundie awards bot.
- Nothing here writes a migration, and nothing here adds a screen.
