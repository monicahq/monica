# Tasks: MCP Server

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[contracts/capabilities.md](contracts/capabilities.md), [quickstart.md](quickstart.md)

**Format**: `- [ ] [ID] [P?] [Story?] description with the file path`. `[P]` means a different file with
nothing incomplete in front of it.

**Tests are not optional here.** Principle III makes a test part of the change rather than a decision,
so every phase carries its own.

**Shape of this file**: Principle XI asks for one line per task, phase headings and the dependency list,
so the template's parallel examples, strategy and notes sections are left out.

**T001 is a merge blocker, not a formality.** `app/Mcp` is a new top-level folder and the constitution
requires an amendment for one. No code in this feature may merge before that amendment does.

**Every tool is built the same way**, so each tool task means: the class extending
`Laravel\Mcp\Server\Tool`, a `#[Description]`, a `schema()`, a `handle()` running the logic named in
[contracts/capabilities.md](contracts/capabilities.md), `protected string $mirrors` holding the route
name, and `shouldRegister(Request $request)` reading the presented key.

## Phase 1: Setup

**Purpose**: the permission to create the folder, and the dependency that fills it.

- [X] T001 Open the constitutional amendment adding `app/Mcp` (`Servers`, `Tools`) to the `app/` list in `.specify/memory/constitution.md` Technology and Architecture Constraints, with the Sync Impact Report and version bump its own Governance section requires, arguing it from [research.md](research.md) section 2
- [X] T002 Add `laravel/mcp` `^1.0` to the `require` block of `composer.json` and install it. Do **not** publish `routes/ai.php`: [research.md](research.md) section 3 shows the provider skips it when absent, and the route belongs in `routes/api.php`
- [X] T003 [P] Register the `mcp` rate limiter in `app/Providers/AppServiceProvider.php` beside the `api` one, returning `Limit::none()` when `config('monica.self_hosted')` is true and a per-key limit otherwise, keyed on the token id rather than the address

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: the endpoint, its authentication and its changelog source. Every story rides on this and none can start before it.

- [X] T004 Create `app/Mcp/Servers/MonicaServer.php` extending `Laravel\Mcp\Server`, with `#[Name]`, `#[Version]` and `#[Instructions]` telling an assistant what Monica is and that every capability is confined to the key's account, and an empty `$tools` array the story phases fill
- [X] T005 [P] Create `app/Http/Middleware/SetMcpLogSource.php` setting `log_source` to `LogSourceEnum::Mcp` and `log_source_detail` to the presented key's name on the request attributes, following `app/Http/Middleware/AuthenticateWithApiKeyOnBasicAuth.php`, which is where `LogUserAction` reads both from
- [X] T006 Register the endpoint in `routes/api.php` with `Mcp::web('mcp', MonicaServer::class)->middleware(['auth:sanctum', 'throttle:mcp', SetMcpLogSource::class])`, outside the `v1` prefix group, so the api group's `SetApiLocale` resolves the locale FR-011 needs
- [X] T007 Create `tests/Feature/Mcp/McpAuthenticationTest.php` covering FR-002: no key, a deleted key and an expired key are each refused and no capability is offered, and a live key reaches the catalogue whatever kind it was minted for
- [X] T008 [P] Create `tests/Feature/Mcp/McpRateLimitTest.php` covering FR-010 and SC-006: a limit applies when `monica.self_hosted` is false, none applies when it is true, and the catalogue is identical either way
- [X] T009 [P] Create `tests/Feature/Mcp/McpTransportTest.php` asserting the route answers 405 to GET and DELETE as the protocol requires, and that `SetApiLocale` has run by the time a tool answers

**Checkpoint**: an assistant can connect and is correctly refused. Nothing is offered yet.

## Phase 3: User Story 1 (P1) - An assistant reads the directory 🎯 MVP

**Goal**: an assistant connects with a key the user already holds and answers questions about the people in their vaults, changing nothing.

**Independent test**: connect with a valid key, ask who is in a named vault, and confirm the answer matches that vault and no other account's.

- [X] T010 [US1] Create `app/Http/Controllers/Api/ContactMeController.php` with `show`, resolving the vault the way `ContactController` does and answering the vault's `is_me` contact as a `ContactResource`, `firstOrFail` giving the 404. This is FR-013, the capability the API was missing
- [X] T011 [US1] Register `GET vaults/{vault}/contacts/me` in `routes/api.php` named `api.v1.contacts.me`, above the `contacts.show` route so the literal wins over the parameter
- [X] T012 [P] [US1] Create `tests/Feature/Controllers/Api/ContactMeControllerTest.php` covering the shape, the vault's Me contact and no other, and 404 for another account's vault and for a vault holding none
- [X] T013 [US1] Add the Me contact section to `resources/docs/api/06-contacts.php`, in the same commit as T011
- [X] T014 [US1] Update the section count in `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php` and add an arm to `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php` for the new endpoint
- [X] T015 [P] [US1] Create `app/Mcp/Tools/ListVaults.php` mirroring `api.v1.vaults`
- [X] T016 [P] [US1] Create `app/Mcp/Tools/ListContacts.php` and `app/Mcp/Tools/GetContact.php` mirroring `api.v1.contacts` and `api.v1.contacts.show`, the first taking the `filter`, `query`, `sort` and `direction` arguments the controller validates and running the same `inGrouping`, `matchingSearch` and `sortedBy` scopes
- [X] T017 [P] [US1] Create `app/Mcp/Tools/GetMeContact.php` mirroring `api.v1.contacts.me`
- [X] T018 [P] [US1] Create `app/Mcp/Tools/ListTemplates.php` and `app/Mcp/Tools/ListTemplateVersions.php` mirroring `api.v1.templates` and `api.v1.templates.versions`
- [X] T019 [P] [US1] Create `app/Mcp/Tools/ListEntityTypes.php`, `app/Mcp/Tools/ListRelationshipTypes.php`, `app/Mcp/Tools/ListRelationshipCategories.php` and `app/Mcp/Tools/ListActivityCategories.php`, four account scoped lists mirroring the routes of the same names
- [X] T020 [US1] Register the ten reading tools in `$tools` on `app/Mcp/Servers/MonicaServer.php`
- [X] T021 [US1] Create `tests/Feature/Mcp/McpCatalogueTest.php` covering SC-003: every tool the server registers names a `$mirrors` route, `Route::has()` knows each one, and the test fails on a tool that names none. This is the only guard that the correspondence is real
- [X] T022 [P] [US1] Create `tests/Feature/Mcp/McpCompartmentTest.php` covering SC-004: every reading capability answers not found, never forbidden, for another account's vault and for a contact of a vault the request did not name
- [X] T023 [P] [US1] Create `tests/Feature/Mcp/McpRefusalLanguageTest.php` covering FR-011: with the account in a non-English locale, a refusal comes back in that locale rather than in English
- [X] T024 [P] [US1] Create the ten classes under `tests/Unit/Mcp/Tools/`, one per tool, each covering the schema it declares, the payload matching the API resource, and an empty result reading as empty rather than as an error
- [X] T025 [US1] Create `resources/docs/api/19-mcp.php` as a guide page (`'guide' => true`, following `resources/docs/api/01-getting-started.php`) saying how to connect an assistant, what the endpoint address is, and what each reading capability does. This is FR-012
- [X] T026 [US1] Run `php artisan monica:localize` and translate the refusal strings and the tool descriptions in all 31 locales, following `.claude/rules/translations.md` for the typography and the glossary of each

**Checkpoint**: an assistant answers questions about a vault and changes nothing. This is the MVP, and it ships alone.

## Phase 4: User Story 2 (P2) - An assistant records a person

**Goal**: an assistant creates a contact and corrects its name, and the changelog says the change arrived from an assistant rather than from a browser.

**Independent test**: ask a connected assistant to add a person to a named vault, then confirm the contact exists there and the changelog attributes it to MCP and to that key.

- [X] T027 [P] [US2] Create `app/Mcp/Tools/CreateContact.php` mirroring `api.v1.contacts.create` and running `App\Actions\CreateContact`, validating through its schema what `ContactController::create` validates inline
- [X] T028 [P] [US2] Create `app/Mcp/Tools/UpdateContactName.php` mirroring `api.v1.contacts.name.update` and running `App\Actions\UpdateContactName`
- [X] T029 [P] [US2] Create `app/Mcp/Tools/SetContactFavourite.php` mirroring `api.v1.contacts.favourite.update`, taking the state wanted rather than a toggle and running `App\Actions\FavouriteContact` or `App\Actions\UnfavouriteContact`
- [X] T030 [US2] Register the three writing tools in `$tools` on `app/Mcp/Servers/MonicaServer.php`
- [X] T031 [US2] Create `tests/Feature/Mcp/McpChangelogTest.php` covering FR-008 and SC-005: each of the three writes lands a `Log` row with source `mcp` and `source_detail` naming the key, and a contact created through MCP is indistinguishable from one typed in a browser apart from that row
- [X] T032 [US2] Extend `tests/Feature/Mcp/McpCompartmentTest.php` with the three writing capabilities: a vault of another account answers not found and nothing is written
- [X] T033 [P] [US2] Create the three classes under `tests/Unit/Mcp/Tools/` covering the schema, the action being run with the validated arguments, and a refusal leaving nothing written
- [X] T034 [US2] Add the writing capabilities to `resources/docs/api/19-mcp.php`, saying plainly that a change made here appears in the account's changelog attributed to the key
- [X] T035 [US2] Run `php artisan monica:localize` and translate the new refusal strings and tool descriptions in all 31 locales

**Checkpoint**: an assistant changes data, and the account's changelog can always say it was an assistant.

## Phase 5: User Story 3 (P3) - The user sees and cuts off what is connected

**Goal**: the user tells an assistant's key from a personal script's on the key list, and revoking it stops the assistant.

**Independent test**: mint a key for an assistant, confirm the key list says so, revoke it, and confirm the assistant can no longer connect.

- [X] T036 [US3] Add the `client` column to `database/migrations/2026_08_01_000000_create_personal_access_tokens_table.php`, commented, defaulting to `script` so an existing key reads as a personal script. **Altered rather than added**: `.claude/skills/migrations/SKILL.md` requires editing the existing migration until the product is in production, which overrides this task as first written. The column is described in [spec/models/006](../../spec/models/006-personal-access-token-in-progress.md) and deliberately not restated here
- [X] T037 [P] [US3] Create `app/Enums/ApiKeyClientEnum.php` with `Script` and `Assistant`, implementing `Translatable` with a label and a description each, following `app/Enums/ApiKeyScopeEnum.php`. A mobile kind arrives with mobile and is not invented here
- [X] T038 [US3] Add the promoted `ApiKeyClientEnum $client` parameter to `app/Actions/CreateApiKey.php`, defaulting to `Script`, writing it onto the token and naming it in the `LogUserAction` parameters beside the name and scopes
- [X] T039 [US3] Accept and validate `client` in `app/Http/Controllers/App/Settings/Account/Security/ApiKeyController.php` with `Rule::enum(ApiKeyClientEnum::class)`, and in `app/Http/Controllers/Api/ApiKeyController.php` so the choice is reachable through the API as Principle X requires
- [X] T040 [US3] Add the choice control to `resources/views/app/settings/account/security/_api-keys.blade.php` using the components already in `resources/views/components`, labelled and described from the enum, the kind never carried by colour alone, operable with the keyboard and announced by a screen reader
- [X] T041 [P] [US3] Expose the kind in `app/Http/Resources/ApiKeyResource.php` and in `app/ViewModels/Settings/Account/Security/SecurityViewModel.php`, so the key list row reads as an assistant's or a script's
- [X] T042 [P] [US3] Extend `tests/Unit/Actions/CreateApiKeyTest.php` with the kind written, the default when none is given, and the kind reaching the log parameters
- [X] T043 [P] [US3] Extend `tests/Feature/Controllers/Api/ApiKeyControllerTest.php` and create `tests/Feature/Views/App/Settings/Security/ApiKeyClientChoiceTest.php` covering the field name and values, the rejection of an unknown kind, and the row announcing which kind a key is
- [X] T044 [US3] Add the kind to `resources/docs/api/04-api-keys.php` and to the connecting section of `resources/docs/api/19-mcp.php`, saying it describes the key and does not restrict it, then update the section count and the `ApiDocsReturnFieldsTest` arm
- [X] T045 [US3] Run `php artisan monica:localize` and translate the two labels, the two descriptions and the screen's copy in all 31 locales

**Checkpoint**: the key list tells an assistant's key from a script's, and revoking one stops the assistant.

## Phase 6: Polish

- [X] T046 Run `php artisan monica:localize --check` and fix what it reports
- [X] T047 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T048 [P] Run `vendor/bin/phpstan analyse` and fix rather than ignore
- [X] T049 Walk [quickstart.md](quickstart.md) end to end against a running instance, including the revocation in step 7, and state in the pull request the five caps Principle XI is over and the two divergences [plan.md](plan.md) names

## Dependencies

- T001 blocks merge, not work. T002 blocks everything: no tool class compiles without the package. T003 is independent of both and can be written first.
- T004, T005 and T006 are serial: the server must exist before the route names it, and the middleware before the route lists it. T007 to T009 need all three.
- Phase 2 blocks all three stories. Nothing in Phase 3, 4 or 5 can start before T006 answers a request.
- Within US1, T010 blocks T011 blocks T013, and T017 needs T011, because a tool cannot mirror a route that does not exist. The five tool tasks T015 to T019 are otherwise genuinely parallel: different files, no shared state.
- T020 is serial against T015 to T019 and against T030, because all six edit `$tools` on `MonicaServer`. T021 needs T020, since it reads the registered list.
- T022 is created in US1 and extended by T032, so those two are serial across the stories. So is `resources/docs/api/19-mcp.php`, created by T025 and edited by T034 and T044.
- US2 needs only Phase 2 and the writing actions, which already exist, so it could be built beside US1. It is second because a write is only safe once the reads prove the compartment holds.
- US3 touches no tool and no capability. It needs only Phase 2 for the changelog detail to have a key name to write, so it is independent of US1 and US2 and could be built first if the key list mattered more than the connection.
- T026, T035 and T045 each depend on the strings of their own phase, and each is what keeps that phase shippable alone: a phase that ships an untranslated string breaks Principle V.
- Phase 6 depends on whichever stories ship. T046 reports nothing if each phase ran its own `monica:localize`.

## Suggested MVP

Phases 1, 2 and 3. An assistant then connects with a key the user already holds and answers questions
about their vaults without anything being exported, which is spec.md's SC-001 and SC-002 and the whole
feature at its smallest.

## Known gap, to state in the pull request

User Story 2's second acceptance scenario reads "a key permitted to read only". No such key can be
minted: `ApiKeyScopeEnum` holds one case and has no read and write split. `shouldRegister()` on every
tool is where that scenario becomes live, with no other change. This is the inheritance clarification
1 chose, reaching one scenario the clarification did not rewrite.
