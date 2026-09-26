# Research: MCP tools for templates, sections and fields

## R1. How a tool mirrors an endpoint

**Decision**: each tool validates its input with the endpoint's rules, reaches records through
`MirrorsAnApiCapability`, calls the same action as the endpoint, and returns the same resource,
resolved. It names the route in `$mirrors`, which `McpCatalogueTest` checks against `Route::has()`.

**Rationale**: this is what the 13 existing tools do (`CreateContact`, `UpdateContactName`). Calling
the controller from the tool would bring the HTTP layer into MCP, and `firstOrFail()` there reaches
the assistant as a generic server error once debug is off (see the trait's docblock).

**Alternatives**: calling the API over HTTP from the tool (a second request, a second rate limit hit,
no gain); sharing validation arrays between controller and tool (a new abstraction for rules that are
five lines each, against principle II).

## R2. Reaching a version, a section and a field

**Decision**: add three lookups to the trait, each scoped through the one above it, each refusing with
a translated "does not exist" sentence:

- `version(Template, int)`: by `version_number` inside the template. "That version does not exist."
- `section(StructureVersion, string)`: the `structure_version_sections` row by id through
  `assembly()`, as `TemplateScopedController::findRow()` does. "That section does not exist."
- `field(SectionVersion, string)`: by id, then carried by `key` across `MakeSectionVersionEditable`,
  as `FieldController::keyOf()` and `fieldIn()` do. "That field does not exist."

A page named when adding a section is looked up in the version: "That page does not exist."

**Rationale**: same scoping as the API; the trait already does this for vault, contact and template.

## R3. Refusals from actions

**Decision**: catch exactly the exceptions each controller catches and return `Response::error()` with
the exception's message, as `CreateContact` does. The messages stay the actions' English sentences,
which is what the API returns today.

| Tool | Caught |
| --- | --- |
| delete-template | `CannotDeleteATemplateInUse` |
| publish-template-version | `InvalidArgumentException`, `PublishedVersionIsFrozen`, `CannotPublishAnEmptyPage` |
| delete-template-draft | `PublishedVersionIsFrozen` |
| add-template-section | `SectionIsAlreadyInTheStructureVersion`, `InvalidArgumentException` |
| create-template-field | `CannotNestARepeater`, `InvalidArgumentException` |
| update-template-field | `InvalidArgumentException`, `AcceptedFileKindIsNotValid` |

**Not translated**: the action messages are English in the API too. Translating them is a separate
change to the actions, out of scope here.

## R4. A published version is not refused by the section and field endpoints (confirmed)

**Finding**: `StructureVersionSection` and `SectionVersion` throw `PublishedVersionIsFrozen` from model
events when a published structure version is changed. The API section, section position and field
controllers do not catch it, and `bootstrap/app.php` does not render it, so the API most likely answers
500 there. No API test covers it (`StructureVersionSectionControllerTest`, `FieldControllerTest`,
`FieldPositionControllerTest` were read).

**Confirmed 2026-09-25**: `PUT .../sections/{section}` on a published version answered 500 with
`PublishedVersionIsFrozen` from `StructureVersionSection::refuseWhenTheStructureIsPublished()`.

**Decision**: the four section endpoints and four field endpoints catch `PublishedVersionIsFrozen` and
answer 422 on `version`, and the tools return the same message through `Response::error()`. This is a
correctness defect in the API, fixed because the spec (US1 scenario 8) depends on it.

## R5. Creating a new section through the API

**Decision**: extend `POST templates/{template}/versions/{version}/sections`. It takes either
`section_id` (an existing section, as today) or `name` with optional `icon` and `colour` (a new one),
never both. For a new section the controller refuses a version that is not a draft **before**
`CreateSection` runs, so a refused call leaves no orphan section, then calls `CreateSection` and
`AddSectionToStructureVersion` exactly as `TemplateSectionController::create` does on the web.

**Rationale**: one endpoint, one route name, one tool. The web builder does both in one request.

**Alternatives**: a separate `POST .../sections/new` route (a second route and tool for the same
gesture); a top level `POST sections` (sections exist to sit in templates; the web has no such screen).

## R6. Partial updates

**Decision**: `update-template`, `update-template-section` and `update-template-field` keep what is
there for every argument left out, as `update-contact-name` does. The endpoints require the full
object (`present` rules, booleans defaulting to false), which an assistant asked to "make the field
required" would otherwise use to clear every other flag.

**Rationale**: with full input the result is identical to the endpoint's; only omission differs, and
it differs in the safe direction. The docs page says so once.

## R7. Tool annotations

**Decision**: `#[IsReadOnly]` on the two new read tools and the ten existing read tools;
`#[IsDestructive]` on delete-template, delete-template-draft, remove-template-section and
delete-template-field. `laravel/mcp` v1.0.0 ships both (`Server/Tools/Annotations`).

**Rationale**: clients use these to decide whether to ask the person first; the spec asks for it on
deletes, and a catalogue where half the read tools say so is misleading.

## R8. Opening a draft when one exists

**Finding**: `StructureVersionController::create` answers 200 with the existing draft instead of 201
with a new one (`CreateStructureVersionDraft` returns the open draft). The tool returns the draft
either way; the difference is only the status code, which MCP does not carry.

## R9. Journal source

**Finding**: `LogUserAction` reads `log_source` from the request attributes that `SetMcpLogSource`
sets, and every action used here dispatches it except `DeactivateTemplate` and
`MakeSectionVersionEditable`, which the API does not log either. One changelog test on a template tool
proves the path; the mechanism is shared.
