# Data model: Change the template of a contact

No table, column or migration. The feature moves existing rows the way a version change already does.

| What | Where | Change |
| --- | --- | --- |
| A contact's template | `contacts.structure_version_id` | Now may point at a version of another structure than before |
| Record count of a version | `structure_versions.record_count` | Decremented on the old version, incremented on the new, as today |
| Answers | `field_values.field_id`, `is_detached`, `detached_at` | Follow their field, are set aside, or come back, as today |
| Repeated rows | `field_value_rows.field_id` | Follow their field, as today |
| Reminders | `reminders.field_id`, `inactive_reason` | Follow, stop with `FieldRemoved`, or restart, as today |
| Version history | `record_snapshots` | One row per actual move with reason `TemplateChange`; none when nothing moves (R4) |
| Account log | `logs` | New action `record_moved_to_template` (R8) |

## New enum case

`UserActionEnum::RecordMovedToTemplate = 'record_moved_to_template'`, worded
"Moved :name onto the template :template", parameters `name` and `template`.

## Transitions of one answer

```text
shown ──(no matching field on the new version)──> set aside
set aside ──(a later version carries its field)──> shown
shown ──(matching field)──> shown, in the matching field
```

A reminder follows its answer: stopped with `FieldRemoved` when the answer is set aside, restarted when it
comes back, and only those stopped for that reason.
