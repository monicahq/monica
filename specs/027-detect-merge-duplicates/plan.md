# Implementation Plan: Detect and merge duplicate contacts

**Branch**: `2026-09-10-detect-merge-duplicates`

**Input**: [spec.md](spec.md)

## Summary

A vault gains a screen naming the pairs of contacts that look like the same person. Two contacts can
be merged, previewed first, with the user deciding every conflict, and the merge can be undone. Two
signals propose a pair: names that resemble each other, and an exact shared value in a field the
structure marks as identifying. Detection never runs on the save path, and nothing is ever joined
without somebody confirming it.

## Technical Context

| Dimension | This feature |
| --- | --- |
| Language, framework | PHP 8.4, Laravel 13 |
| Dependencies added | none. Blade, Alpine Ajax and Tailwind for the screens, Sanctum for the API |
| Storage | two columns (`fields.is_identifying`, `contacts.match_key`) and two tables (`contact_duplicate_candidates`, `contact_merges`), in [data-model.md](data-model.md) |
| Testing | PHPUnit, `RefreshDatabase`, across SQLite, MySQL and PostgreSQL |
| Platform | server rendered web, responsive, plus the JSON API |
| Project type | web application, existing |
| Performance goal | the duplicates screen opens as fast as the contact list of the same vault, and saving a contact is not slowed at all (SC-004) |
| Constraints | no driver-specific SQL, so name similarity blocks in SQL and compares in PHP. Detection is a queued job. 31 locales to translate |
| Scale, scope | 10,000 contacts per vault. 4 new actions and 2 extended, 1 job, 3 web and 3 API controllers, 3 resources, 2 models, 2 view models, 3 screens, 4 migrations |

## Constitution Check

*GATE: passes before Phase 0 and after Phase 1.*

| Principle | How this feature satisfies it |
| --- | --- |
| I Actions hold the business logic | `PreviewContactMerge`, `MergeContacts`, `UndoContactMerge`, `DismissDuplicateCandidate`, and `CreateField` and `UpdateField` extended. The preview is an action, not a view model, because the API reads it too ([research.md](research.md) 7). `ContactDuplicatesViewModel` and `ContactMergeViewModel` only read. |
| II Simplicity over abstraction | One job taking either a contact or a vault, rather than two jobs. Dismissal on the candidate row rather than a second table. No restore-snapshot machinery: the undo replays the merge's own ledger. |
| III Tested before merged | Every action, the job, both models, every controller on both surfaces, both view models. Each compartment failure tested: another account's contact, and the right account's wrong vault, both 404. The job tested on all three drivers. |
| IV Laravel conventions | `up()` only, no `constrained()`, foreign keys declared at the bottom. Inline `$request->validate()`. camelCase route names, kebab-case URLs, `#[RouteKey]` on `ContactMerge`. |
| V Localized and safe | Every string through `__()` or `trans_choice()`, then `php artisan monica:localize` and all 31 locales translated. The counted phrases (candidate pairs, values moved) declare their plural behaviour. The confirmation warns before absorbing a contact (FR-014). |
| VI Nothing changes under anybody's feet | `is_identifying` sits on `fields`, which hangs off `section_version_id`, so marking a field on a published structure drafts a new version and changes no record already shown. A merge is the owner's explicit act, previewed, snapshotted first and undoable. |
| VII A stable core, unlimited personalisation | No second way to attach data. `is_identifying` is one more property of a field, next to `is_unique` and `is_sensitive`. Contact stays fixed, and `is_me` cannot be absorbed. |
| VIII Nothing disappears silently | The absorbed contact is soft deleted, never destroyed, and its address redirects. Values with nowhere to land on the survivor's structure are detached, not dropped, which is the mechanism `MoveRecordToTemplateVersion` already uses, and they appear on the set-aside screen. |
| IX Accessible to everybody | The preview is a comparison table with real table semantics and a radio group per conflict, so no choice is carried by colour. The candidate list, the dismissal and the confirmation are keyboard operable. Both screens checked at 200% and at phone width. |
| X Everything goes through the API | Five routes on `routes/api.php`, mirroring the web ones and sharing the same actions. `FieldResource` gains the attribute. The reference under `resources/docs/api` is updated. No artificial intelligence: detection is deterministic string comparison. |
| XI The written record is short | `spec.md` 120, `plan.md` 100, `research.md` 80, `data-model.md` 60, one contract file, `quickstart.md` 52. Each fact in one of them. |
| Product invariants | Contacts only, entities out, per the spec's Assumptions. A vault is a compartment: no pair, preview or merge crosses one. Detection proposes and never applies. |

One deviation, recorded rather than justified away: the product specification's model 034 names a
`relationship_resolutions` column, and relationships do not exist yet, so this ships one `resolutions`
column instead. `spec/models/034-contact-merge-done.md` is updated in this pull request, which is
also where its two open questions get their answers ([research.md](research.md) 8).

## Project Structure

### Documentation (this feature)

```text
specs/027-detect-merge-duplicates/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/duplicates-and-merges.md
├── checklists/requirements.md
└── tasks.md                          # /speckit-tasks, not created here
```

### Source Code (repository root)

```text
app/
├── Actions/           PreviewContactMerge, MergeContacts, UndoContactMerge,
│                      DismissDuplicateCandidate, and CreateField + UpdateField extended
├── Jobs/              DetectDuplicateContacts
├── Models/            ContactDuplicateCandidate, ContactMerge, and Contact + Field extended
├── Helpers/           LocaleCollation::matchKey()
├── Enums/             DefaultFieldEnum::isIdentifying(), UserActionEnum + 3 cases
├── Http/Controllers/  App/Vaults/ContactDuplicateController, ContactDuplicateDismissalController,
│                      App/Vaults/ContactMergeController, and the three Api/ counterparts
├── Http/Resources/    ContactDuplicateCandidateResource, ContactMergeResource,
│                      ContactMergePreviewResource, and FieldResource extended
└── ViewModels/        Vaults/ContactDuplicatesViewModel, Vaults/ContactMergeViewModel
database/migrations/   two column additions, two tables
resources/views/app/   vaults/duplicates/index, contacts/merge/{new,show}
resources/docs/api/    the five routes and the field attribute
routes/                web.php, api.php, console.php (the nightly sweep)
spec/models/           034-contact-merge-done.md updated
tests/                 Unit for the actions, job, models, view models. Feature for both surfaces
lang/                  31 locales, through php artisan monica:localize
```

**Structure Decision**: nothing new. Every file lands in a folder the constitution already lists, and
the merge follows the shape of `MoveRecordToTemplateVersion`, which snapshots, moves, detaches what no
longer fits and logs.

## Complexity Tracking

No violation to justify. Two judgement calls are argued in [research.md](research.md): the `match_key`
column, which buys portable name blocking without driver-specific SQL, and the undo replaying a ledger
rather than a snapshot, which is what makes it possible at all without a restore action.
