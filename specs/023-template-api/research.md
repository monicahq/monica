# Research: Template endpoints

## Decision 1: the reference implementation is the entity type endpoints, not `collections`

**Decision**: mirror `EntityTypeController`, `EntityTypeResource`, `EntityTypeControllerTest` and `resources/docs/api/07-entity-types.php`.

**Rationale**: the `api-writer` skill names a `collections` API as the reference. It does not exist, and neither does `App\Services\ApiDocumentation`, `Tests\Unit\Services\ApiDocumentationTest` or the `Marketing\Docs` namespace the `api-docs-writer` skill names. The real portal is built by `App\ViewModels\Docs\ApiDocsViewModel`, served by `App\Http\Controllers\App\Docs\ApiDocsController`, and the two way route sync is asserted by `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` through `documentedRoutes()`. Checked in code outranks a skill.

**Where the skills are stale and the code wins**: `index` returns a `JsonResponse` carrying a hand built `data` and `meta` envelope rather than an `AnonymousResourceCollection`; `id` is the uuid rather than a stringified row id; page size comes from `monica.api_default_items_per_page` and `monica.api_maximum_items_per_page`, not `app.maximum_items_per_page`; lookups go through a private `find()` scoped to the account rather than `$request->route()->parameter()`; route names carry the `api.v1.` prefix. The skills also describe owner, editor and viewer roles. The application has none, so every permission case is a cross account case, as `.claude/rules/testing.md` says.

**Alternatives rejected**: writing to the skill as literally specified would produce a controller unlike every sibling and a docs file the portal cannot load.

## Decision 2: how each thing is addressed

**Decision**: template by uuid, version by its number within its template, page by uuid, section placement and field by id.

**Rationale**: `Template` and `TemplateVersionPage` carry `HasUuids`. `TemplateVersion`, `TemplateVersionSection` and `Field` carry no uuid column, so there is nothing else to address them by. A version number is unique within its template, is what a change note refers to and is what the version history screen shows, so `/templates/{template}/versions/3` reads correctly and survives a copy between accounts; the browser binds the row id instead, which is an implementation detail no caller should inherit.

**Consequence**: the version endpoints resolve `version_number` within the template rather than using implicit binding, and the numeric ids are constrained with `->where(..., '[1-9][0-9]*')` as the sibling routes are.

## Decision 3: a template must have at most one draft, and today nothing enforces it

**Finding**: `CreateTemplateVersionDraft` always creates a version numbered `max + 1`. Nothing stops a second draft. Both `TemplateBuilderViewModel` and `TemplatesViewModel` then read the draft with `firstWhere('status', Draft)`, so a second draft would be silently invisible and unreachable in the browser.

**Decision**: add the guard to `CreateTemplateVersionDraft` so it returns the draft that is already open instead of creating another, and cover it with a test.

**Rationale**: the invariant is already assumed in two view models, so this is a defect in the code rather than a new capability. The API makes it trivially reachable, since a script retrying a failed POST would open a second draft. Fixing it in the action fixes the browser path at the same time, which is what Principle I asks for. The constitution requires a pull request that finds code and intent disagreeing to resolve it rather than record it.

**Alternative rejected**: refusing the second request with an error. Returning the open draft is idempotent, which is what a retrying client needs, and it is what the builder screen already behaves as though were true.

## Decision 4: publishing the sections a version carries belongs in an action

**Finding**: `TemplateVersionPublicationController::publishTheSectionsItCarries()` walks the assembly and publishes every section version still in draft before publishing the template version. That is business logic in a controller, which Principle I forbids, and `PublishTemplateVersion` refuses outright with `CannotPublishWhileASectionIsStillADraft` when it is skipped.

**Decision**: move that walk into `PublishTemplateVersion`, and have both the web controller and the API controller call the action alone.

**Rationale**: without it the API endpoint either duplicates the loop or cannot publish anything the builder can. Duplicating it would leave two copies of a rule about freezing, which is the rule the whole versioning design rests on.

## Decision 5: what publication and field configuration actually enforce

- A change note is required only when the version has a predecessor, so the first version of a template publishes without one. `PublishTemplateVersion` throws `InvalidArgumentException` otherwise.
- Publication refuses a version with no page, and a page with no section, through `CannotPublishAnEmptyPage`. The spec's edge case is existing behavior, not new work.
- `MakeSectionVersionEditable(user, row)` already returns the editable section version, opening a draft of the section when the placed one is published. Phase five reuses it exactly as `FieldController` does.
- `CreateField` settles `is_repeatable` from the type and `relation_target` plus `relation_entity_type_id` only when the type is a relation. `UpdateField` exposes neither. Repeatability and relation targeting are therefore settled when a field is added and are not editable afterwards, which is what the spec now says.
- Reaching a placement is guarded by `TemplateVersionSection::isReachableThrough($user, $template)`, and pages by the same method on `TemplateVersionPage`. The API uses them rather than inventing a second scoping rule.

## Decision 6: no new tables, and no data model artifact

**Decision**: omit `data-model.md`.

**Rationale**: every model, column, cast and relationship this feature needs exists and is documented in the model docblocks, which `.claude/rules/code-style.md` designates as the model's documentation. A data model artifact would copy them, and Principle XI requires a fact to live in exactly one artifact. The only schema question, whether placements need a uuid, is answered in Decision 2: they do not.
