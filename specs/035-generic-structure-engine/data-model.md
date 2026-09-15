# Data model: Generic structure engine

Decisions D1, D2 and D3 in [research.md](research.md) settle the three shapes marked below. Every
create migration is edited in place; the database is rebuilt with `migrate:fresh`.

## New tables

```
structures
  id, uuid, account_id
  owner_type            App\Models\Template or App\Models\ActivityType    (D1)
  owner_id              id of that owner, no foreign key on a morph        (D1)
  applies_to            contact, entity or activity                        (D2)
  entity_type_id        nullable, set when applies_to is entity            (D2)
  published_version_id  nullable, version a new record is given
  timestamps
  unique (owner_type, owner_id)

activity_types
  id, uuid, account_id, activity_category_id
  key, name, name_translation_key, description, icon, colour
  is_system, is_active, position
  timestamps, softDeletes
```

`activity_types` otherwise follows `spec/models/035`, with `template_version_id` dropped: the
structure names the type, not the reverse (D1).

## Renamed tables

| Today | Becomes | Other change |
|---|---|---|
| `template_versions` | `structure_versions` | `template_id` becomes `structure_id` |
| `template_version_pages` | `structure_version_pages` | `template_version_id` becomes `structure_version_id` |
| `template_version_sections` | `structure_version_sections` | both keys renamed to match |

## Renamed columns

`template_version_id` becomes `structure_version_id` on `contacts`, `entities`, `record_snapshots`
and `vault_default_templates`. The `activities` table, when it is created, uses the new name.

## Changed table

`templates` loses `published_version_id`, `applies_to` and `entity_type_id`, all three moving to
`structures`. It gains no column: the structure names its owner (D1).

## Relationships

```
Structure          belongsTo Account; belongsTo StructureVersion as publishedVersion (nullable)
                   morphTo owner                          (Template or ActivityType)
                   hasMany StructureVersion
StructureVersion   belongsTo Structure; belongsTo StructureVersion as previousVersion (nullable)
                   hasMany StructureVersionPage, StructureVersionSection
                   belongsToMany SectionVersion through structure_version_sections
                   hasMany Contact, Entity, Activity
StructureVersionPage     belongsTo StructureVersion; hasMany StructureVersionSection
StructureVersionSection  belongsTo StructureVersion, StructureVersionPage, SectionVersion
Template                 morphOne Structure
ActivityType             belongsTo ActivityCategory; morphOne Structure
Contact / Entity         belongsTo StructureVersion; morphMany FieldValue, FieldValueRow
```

Section, SectionVersion, Field, FieldValue, FieldValueRow, ValueList and ValueListItem keep every
relationship they have, with `SectionVersion::templateVersions()` renamed `structureVersions()` and
`section_versions.usage_count` recommented to count structure versions.

## Invariants the tables carry

- One structure per owner: the unique index on `owner_type` and `owner_id`. One owner per structure:
  both columns are not null.
- A morph carries no foreign key, so two rules move into code. Deleting a Template or an ActivityType
  is soft and leaves its structure in place, so a restored owner comes back with its fields, and the
  only final deletion is the account's, which the database cascades. `Structure::owner()` is null once
  the owner is soft deleted, so the deleting actions refuse while a record points at one of its
  versions and remove the vault defaults that name one.
- A record never follows the latest version: `structure_version_id` is written once at creation and
  changed only by the move action, which snapshots first.
- `structures.published_version_id` is the only place "which version is current" is written.
