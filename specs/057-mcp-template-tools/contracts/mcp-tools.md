# Contract: the new MCP tools

Every tool: requires a signed in key, refuses records outside the account with a translated "does not
exist" sentence, returns the mirrored endpoint's resource. "Keeps" means an argument left out keeps
the current value (research R6). Identifiers: `template` is the template uuid, `version` the version
number, `section` the id of the section's placement in the version, `field` the field id.

## Templates

| Tool | Mirrors | Arguments | Annotation |
| --- | --- | --- | --- |
| get-template | `api.v1.templates.show` | template | read only |
| create-template | `api.v1.templates.create` | name, applies_to, description?, entity_type? | |
| update-template | `api.v1.templates.update` | template, name? (keeps), description? (keeps) | |
| delete-template | `api.v1.templates.destroy` | template | destructive |
| set-template-activation | `api.v1.templates.activation.update` | template, is_active | |
| set-template-position | `api.v1.templates.position.update` | template, position | |
| duplicate-template | `api.v1.templates.duplicate.create` | template, name?, slug? | |

## Template versions

| Tool | Mirrors | Arguments | Annotation |
| --- | --- | --- | --- |
| get-template-version | `api.v1.templates.versions.show` | template, version | read only |
| create-template-draft | `api.v1.templates.versions.create` | template | |
| publish-template-version | `api.v1.templates.versions.publication.update` | template, version, change_note? | |
| delete-template-draft | `api.v1.templates.versions.destroy` | template, version | destructive |

`get-template-version` returns the version with its pages, their sections and each section's fields
(`StructureVersionResource::withStructure()`), which is where every `section` and `field` id comes from.

## Sections in a version

| Tool | Mirrors | Arguments | Annotation |
| --- | --- | --- | --- |
| add-template-section | `api.v1.templates.versions.sections.create` | template, version, name? or section_id?, icon?, colour?, page?, section_version_number?, title_override?, is_collapsed_by_default? | |
| update-template-section | `api.v1.templates.versions.sections.update` | template, version, section, title_override? (keeps), is_collapsed_by_default? (keeps) | |
| remove-template-section | `api.v1.templates.versions.sections.destroy` | template, version, section | destructive |
| set-template-section-position | `api.v1.templates.versions.sections.position.update` | template, version, section, position | |

`add-template-section` takes exactly one of `name` (a new section) and `section_id` (one the account
has). `icon` and `colour` apply only with `name`; `section_version_number` only with `section_id`.

## Fields in a section

| Tool | Mirrors | Arguments | Annotation |
| --- | --- | --- | --- |
| create-template-field | `api.v1.templates.versions.sections.fields.create` | template, version, section, type, label, key?, value_list_id?, relation_target?, relation_entity_type_id?, parent_id? | |
| update-template-field | `api.v1.templates.versions.sections.fields.update` | template, version, section, field, then every endpoint input, each keeps | |
| delete-template-field | `api.v1.templates.versions.sections.fields.destroy` | template, version, section, field | destructive |
| set-template-field-position | `api.v1.templates.versions.sections.fields.position.update` | template, version, section, field, position | |

Changing a field of a published section drafts the section first, which gives its fields new ids
(`FieldController::keyOf()`). The field tools return the field as it now is, with its new id, and the
tool descriptions say to use the returned id afterwards.

The schema descriptions list enum values from the enums themselves (`FieldTypeEnum`,
`RelationTargetEnum`, `RepetitionOrderEnum`, `StructureAppliesToEnum`, `Section::ICONS`,
`VaultColourEnum`), as `CreateContact` does for `NameOrderEnum`.

## Server instructions

`MonicaServer`'s instructions gain one paragraph: a template's sections and fields change only on a
draft version; open one with create-template-draft, read ids from get-template-version, and nothing
reaches records until publish-template-version.
