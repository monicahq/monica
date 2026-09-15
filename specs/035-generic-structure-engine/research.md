# Research: Generic structure engine

## Decisions

Three invariants were put to the author as Q1, Q2 and Q3 and were not answered before planning
began. The recommended answer is adopted below so the design can be written. Each is one line to
flip, and nothing downstream is built until they are confirmed.

### D1: the structure points at its owner

`structures.owner_type` and `structures.owner_id`, both not null, unique together. The owner carries
no structure column. `Structure` declares `morphTo owner`; `Template` and `ActivityType` each declare
`morphOne Structure`.

**Rationale**: a version reaches its owner in one query, which the record screen and
`ContactResource` both need to name the template a contact is on. The pattern is already the house
one: `field_values.valuable_type` and `marketplace_installations.installable_type` both store a fully
qualified class name with no morph map, so this adds no new idiom.

**What it costs, and what pays for it**: a polymorphic column takes no foreign key, so the database
cannot cascade an owner's deletion to its structure and cannot refuse a dangling `owner_id`. The
unique index on `owner_type` and `owner_id` still carries half of FR-007, one structure per owner,
and the not-null columns carry the other half, one owner per structure.

The cascade turned out to cost less than expected, and the first version of this decision got it
wrong. Templates are soft deleted and nothing force deletes one, so a structure must **not** be
deleted with its owner: a restored template has to come back with its fields. The only final deletion
is the account, and `structures.account_id` cascades. What remains in code is narrower than
"deleting an owner deletes its structure": anything that force deletes a single owner has to take the
structure with it, and `Structure::owner()` is guarded against a row that is gone.

**Rejected**: the owner pointing at its structure, with `templates.structure_id` and
`activity_types.structure_id` not null and unique, which keeps real foreign keys and cascades but
answers "who owns this" in two queries. A shared free-standing structure, which breaks FR-007
outright.

### D2: the kind of record lives on the structure

`applies_to` and `entity_type_id` move from `templates` to `structures`. `TemplateAppliesToEnum`
becomes `StructureAppliesToEnum` and gains an `Activity` case.

**Rationale**: a version can then answer "what kind of record do I describe" without knowing its
owner, which is what moving a record to another version and resolving a vault default both need.
Template reads them through its structure, so the API is unchanged.

**Rejected**: leaving them on `templates`, which makes every compatibility check walk to the owner and
branch on which kind of owner it is, reintroducing the assumption being removed.

### D3: the marketplace publishes the owner

`marketplace_installations.installable_type` gains a third kind, ActivityType, beside Template and
Section. The payload carries the owner's own attributes plus its structure.

**Rationale**: somebody browsing installs "a restaurant log", not "a structure", and the owner's name,
icon, colour and category have nowhere to live in a structure-only payload.

**Rejected**: publishing the structure and building the owner around it at install time.

### D4: log actions are per owner, not per engine

`UserActionEnum` keeps its `template_version_*` cases with their current copy, because they describe
a template and a template is still what the user did something to. Activity type structures get their
own cases, worded as custom fields. The changelog is append only (principle VIII), so no case is
renamed or removed.

### D5: the API surface does not move, with one correction

Paths, route names and resource `type` strings keep their template wording, per FR-009. A contact is
still reached at `/vaults/{vault}/contacts/{contact}/template-version` under the route name
`contacts.templateVersion.update`, and a version is still `/templates/{template}/versions/{version}`.

D5 first said `ContactResource` would keep exposing `template_version_id`. That was wrong and is
reversed: the key becomes `structure_version_id`, on the request input as well as the response. The
reason the first version gave, that the contact payload speaks the product's vocabulary, does not
hold up. `template_name` is product vocabulary and stays. `template_version_id` is engine vocabulary
that had leaked into the payload, and a contact points at a structure version rather than at a
template version, so keeping the key would have been the one name FR-018 forbids, preserved on
purpose and forgotten later. The product is pre-production and the docs portal is ours to edit.

The three resource `type` discriminators go the same way, `template_version`, `template_version_page`
and `template_version_section` becoming the structure spellings. They are machine fields rather than
words on a screen, they name the engine, and the same versions will be reached from an activity type
path where the template spelling would simply be false.

## Naming collisions found in the audit

- `app/Helpers/StructureFile.php` and `StructureFileReader.php` already mean "a file holding an
  exported structure". They keep their names, which become more accurate, not less.
- `app/Http/Controllers/Api/TemplateStructureController.php` is a base class that resolves a template
  and a version. Its name would now read as a controller for the new model. Rename it
  `TemplateVersionScopedController`.

## Contradictions with the specifications as written

Each is resolved by this feature and each needs an edit, listed as a task.

| # | Where | The contradiction | Resolution |
|---|---|---|---|
| 1 | `spec/models/035` | ActivityType has a nullable `template_version_id`, "null when it asks for none" | Non-null `structure_id`, always created. An empty published version is already legal: publishing needs one page, which may hold nothing |
| 2 | `spec/models/035` | Activity type fields "use the template and template version API" | Contradicts FR-014. The activity type reaches the engine on its own path, designed with its screens |
| 3 | `app/Enums/TemplateAppliesToEnum` | Only Contact and Entity, while spec 5.2 and the `field_values` comments already say "a contact, an entity or an activity" | D2 adds the third case |
| 4 | `spec/models/012`, `templates` table | `published_version_id` sits on the template | Moves to the structure; models 012 and 018 change |
| 5 | `spec/models/013`, `016`, `055` | The engine is described as a property of templates | Rewritten as StructureVersion, StructureVersionSection, StructureVersionPage |
| 6 | `spec/models/README.md` | Numbering is a dependency order and refuses renumbering | Structure takes number 056 and says why it sorts out of order, as model 055 already does |
| 7 | Constitution, principle VI | "a published version of a template or a section is frozen" | The frozen thing is a structure version. Wording, no change of obligation, which the file's own versioning rule calls a PATCH: 2.3.0 to 2.3.1 |
| 8 | `marketplace_installations` | `installable_type` commented "a template or a section" | D3 adds the third kind; model 047 changes |
| 9 | `spec/spec.md` 5.3 and 9.3 | An entity type may have several templates, with a vault picking one. An activity type gets exactly one structure, account wide, with no per-vault variation | Deliberate asymmetry, currently unwritten. Section 9.3 says it out loud |

## Scale

281 files name a template version: 102 under `app/`, 125 test files, around 3,350 occurrences.
Mechanical in most, structural in the model, migration, marketplace and activity type layers.
