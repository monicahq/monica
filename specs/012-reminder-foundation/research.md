# Research: Reminder foundation on a date field

Phase 0. Ten decisions, and what each of them rules out. Nothing here was a NEEDS CLARIFICATION in
the plan; the Technical Context has no unknowns, because the stack, the storage and the test runner
are the project's and no dependency is added. What needed research was the shape of the rule, and the
answer in every case came from reading what the repository already does.

| Decision | Rationale | Rejected |
| --- | --- | --- |
| The rule points at the **field value**, not at the pair of record and field. | It is what makes FR-010's "at most one rule per value" a statement about one column, and it makes a date inside a repetition work with no extra machinery: each repetition's answer is its own value row, so each can carry its own rule. | Keying on record plus field, which cannot tell two repetitions apart, and would have needed the repetition id as a third key. |
| `kind` becomes two columns, `source` and `cadence`. | Section 10.2, as amended, says the source provides the record, field and value while the rule separately defines whether it fires once or recurs. One enum holding `one_off`, `recurring` and `field_based` cannot express a field-based rule that fires once, which is half of what the design offers. | Keeping the single enum from `spec/models/040`, which would have made the design's two controls write one column and disagree about it. |
| The moments live in a JSON column on the rule. | A moment has no identity, is never addressed, never ordered on its own and is always read with its rule. One value, one column. | A `reminder_moments` table. It would enforce the no-duplicates rule in the schema, which is the one thing it buys, and cost a second model, a join and a migration for something never queried apart. Duplicates are refused in validation instead. |
| The moment carries a unit, not a count of days. | Three months before 1 March is 1 December, and no number of days says that. `offset_days` in `spec/models/040` cannot express what the design's own unit picker offers. | A signed integer of days, which silently rounds a month to thirty and is wrong twice a year. |
| No occurrence is stored. The next date is computed by `App\Helpers\ReminderSchedule`. | `spec/models/041` asks how far ahead deadlines are generated and the specification does not answer it. Generating on an unspecified window would fix that answer by accident. | Generating a rolling window now, which is model 041's feature and needs the window decided first. |
| The arithmetic goes in a helper, the wording in the view model. | The next date is read by a screen today and by a job when 041 arrives, so it cannot live in a view model, which may not be consumed outside Blade. The summary sentence is copy, and copy is translated in the view model with the rest of the screen's words. | Putting both on the `Reminder` model, which would make the model hold the phrasing of nine locales. |
| The rule rides the block's existing form and save. | The design shows one Save changes button, and the block's promise is that nothing is written until it is saved. `SetFieldValues` already runs in one transaction and already returns the written value, so the rule is attached to a value that exists. | A second form posting on its own, which would write a rule for a date the same page had not saved yet. |
| Unticking soft-deletes; the five causes deactivate; reactivating matches the cause. | Section 3.3 and Principle VIII. A rule taken away has to come back, and a rule silenced for one reason must not be revived by another: unarchiving a contact whose field was also removed leaves it inactive, and says which. | A hard delete on untick, and a single boolean revived by any of the causes, which would make the contact coming back undo a field that has not. |
| `is_reminder_source` goes from the column, the builder and the payload, but the import keeps accepting the key. | `StructureFileReader::object()` refuses a key it does not know, so dropping the key from the allowed list would refuse every structure file exported before this change. The key is read and ignored. | Dropping it outright, which breaks import of files already in circulation and of everything the marketplace holds. |
| Columns for cadences and sources nothing can build yet are not created. | The Field model's own established decision: a setting the type will never read is a setting that lies. An inactivity rule needs `ActivityType`, which is model 035 and does not exist. Migrations are edited until production, so the columns arrive with the screens that write them. | Creating the whole of `spec/models/040`'s field list, which would ship eleven columns nothing writes and an enum with cases nobody can reach. |

## What the repository already said

Three actions carry a comment describing the half this feature builds, and each is where the
deactivation belongs: `ArchiveContact` ("no reminder about them fires while they are archived.
Reminders do not exist yet, so nothing here cancels anything"), `UnarchiveContact` and
`ArchiveEntity`. `UpdateTimezone` already promises that a rule freezes its zone. `FieldTypeEnum`
still answers `canDriveAReminder()` for a recurring date alone, which commit 9d1c039 made wrong in
the documentation and nowhere else.

## Open, and deliberately not answered here

- How far ahead deadlines are generated, and how long past ones are kept. Both belong to model 041.
- Whether push tokens are a column or a model. Model 042's question, and nothing here touches it.
- Whether an inactivity rule can watch a group. Model 040's question, and no source for it exists.
