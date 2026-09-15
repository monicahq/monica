# Implementation Plan: Generic structure engine

**Branch**: `2026-09-13-structure` | **Date**: 2026-09-13 | **Spec**: [spec.md](spec.md)

## Summary

Split the product concept from the technical one. Template stays what a user applies to a contact or
an entity; the versioned engine underneath becomes Structure, StructureVersion,
StructureVersionPage and StructureVersionSection, owned by whatever needs it. An ActivityType becomes
the second owner, with its structure created alongside it, which proves the engine is shared rather
than borrowed. Decisions and the nine contradictions this resolves are in [research.md](research.md);
the tables are in [data-model.md](data-model.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: none added

**Storage**: MySQL. Create migrations are edited in place and the database rebuilt with
`migrate:fresh`, per the project rule that holds until the end of 2026. No rename migration, no
backfill.

**Testing**: PHPUnit, 5,847 tests. PHPStan level 5 over `app/`, `routes/`, `database/`.

**Project Type**: server rendered Laravel application with a JSON API

**Constraints**: no behaviour change for contacts and entities; no user-visible change to template
vocabulary; the changelog is append only, so no `UserActionEnum` case is renamed or removed.

**Scale/Scope**: 281 files, around 3,350 occurrences, across models, migrations, actions,
controllers, view models, views, API resources, factories and tests.

**Settled**: the author answered Q1 to Q3 on 2026-09-13. The structure points at its owner
polymorphically (D1), the kind of record moves onto the structure (D2), and the marketplace publishes
the owner (D3). Migrations may now be written.

## Constitution Check

| Principle | Gate | Status |
|---|---|---|
| I Actions hold the logic | Renamed actions keep one operation each; the activity type structure is created by an action both the web and the API will call | Pass |
| II Simplicity | One container replaces an assumption. No new abstraction layer and no interface. The morph in D1 is the house pattern, matching `field_values.valuable_type` and `marketplace_installations.installable_type` | Pass |
| III Tested before merged | Every renamed class keeps its test; SC-005 adds the shared-engine proofs | Pass |
| IV Laravel conventions | One class per file, `up()` only, no `constrained()`, existing migrations edited in place | Pass |
| V Localized and safe | Log copy changes wording for activity types, so `monica:localize` runs and every locale is translated | Pass |
| VI Nothing changes underfoot | The freezing rules are carried over unchanged; the principle's own wording needs the amendment in research contradiction 7 | Pass, with amendment |
| VII Stable core | This removes a second field engine before it can exist. Directly served | Pass |
| VIII Nothing disappears | No `UserActionEnum` case renamed or dropped (D4); detached values keep their marking. D1 takes the owner cascade off the database, so deleting an owner must delete its structure in the same action rather than leaving it orphaned | Pass, with a rule moved into code |
| IX Accessible | No screen markup changes for templates; no new screen ships here | Pass |
| X Everything through the API | The template API keeps every path and route name; only the payload keys move (D5). The activity type API is deferred with its screens, so FR-014 and FR-015 are not discharged here | Deferred, declared |
| XI The written record is short | Four artifacts exceed their cap. See Complexity Tracking | Fail, justified |

## Project Structure

### Documentation

```text
specs/035-generic-structure-engine/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── checklists/requirements.md
```

No `contracts/` directory. The API surface does not move (D5), so a contracts file would restate
`routes/api.php` and the docs portal, which principle XI forbids.

### Source code

All work lands in existing folders; no new top-level folder, so no amendment is needed on that count.

```text
app/Models/            Structure (morphTo owner), StructureVersion, StructureVersionPage,
                       StructureVersionSection, ActivityType; Template, Contact,
                       Entity, Section, SectionVersion, VaultDefaultTemplate edited
app/Actions/           26 actions renamed; CreateActivityType added
app/Http/Controllers/  31 controllers, web and API, renamed or edited
app/ViewModels/        13 view models edited
app/Http/Resources/    3 resources renamed, ContactResource keeps its payload key
app/Helpers/           MarketplacePayload, StructureFile, StructureFileReader, ShareLinkSummary
app/Enums/             StructureAppliesToEnum replaces TemplateAppliesToEnum; UserActionEnum grows cases
database/migrations/   7 create migrations edited, 2 added (structures, activity_types)
database/factories/    6 renamed, 2 added
resources/views/       6 templates edited
tests/                 125 files renamed or edited, plus the SC-005 proofs
spec/models/           012, 013, 016, 018, 019, 020, 023, 035, 047, 055, README, plus 056 Structure
spec/spec.md           sections 5.2, 5.3, 6, 9.3
.specify/memory/constitution.md   principle VI wording, MINOR amendment
```

**Structure Decision**: the repository layout is fixed by the constitution and every file above
already has a home. The only genuinely new classes are Structure, ActivityType and their factories.

Two ordering notes D1 forces. A morph takes no foreign key, so `structures` may be created before
`templates` and `activity_types` without the migration order mattering; the one real dependency is
`structures.published_version_id`, whose foreign key is added from the `structure_versions` migration
with `Schema::table()`, exactly as `templates.published_version_id` is added today. And creation runs
owner first: `CreateActivityType` saves the type, then creates the structure naming it, then publishes
the first version.

## Complexity Tracking

| Violation | Why needed | Simpler alternative rejected because |
|---|---|---|
| `spec.md` at 200 lines against a 120 cap | Three user stories with their acceptance scenarios, 19 requirements and the scope split between this feature and the two that follow | Dropping the acceptance scenarios would leave "preserve current behaviour" untestable, which is the whole risk of a 281-file rename |
| `plan.md` at 110 against 100, `research.md` at 112 against 80, `data-model.md` at 77 against 60 | Five decisions with their rationale, nine contradictions one line each, and the table of renames the author asked for before any migration | Splitting any of them across more files would duplicate headings and make the total worse |
| FR-014 and FR-015 stated but not discharged | They describe screens and a record that do not exist yet; stating them here is what keeps the later features honest | Deleting them would lose the requirement that an activity type never shows the word template |
