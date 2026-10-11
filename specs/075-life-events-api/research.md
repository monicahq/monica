# Research: Life events through the API and the MCP server

## R1. One resource under the vault, not under a contact

- **Decision**: `vaults/{vault}/life-events`, narrowed with `?contact=`. Participants travel in the body, the first being the person the life event is recorded for.
- **Rationale**: a life event belongs to the vault and to several people at once; the web nests it under a contact only because the form opens from a page.
- **Rejected**: `vaults/{vault}/contacts/{contact}/life-events` (one life event would answer at as many addresses as it has participants).

## R2. The actions take a contact; the API supplies the first participant

- **Decision**: create passes the first participant sent. Update, delete and restore pass the life event's current first contact participant. On update, the first participant sent must be that contact, else 422 on `participants`.
- **Rationale**: `UpdateLifeEvent`, `DestroyLifeEvent` and `RestoreLifeEvent` refuse a contact who does not take part, and `syncParticipants()` always keeps the contact. Mirroring the web keeps the actions unchanged on that point.
- **Rejected**: making the contact optional in the actions (changes the web's scoping check for an API convenience).
- **Known limit**: a life event with no contact left among its participants answers 422 on delete and restore. It is recorded in the spec's edge cases.

## R3. Custom field values are keyed by field id, in the web form's shape

- **Decision**: write `fields.{id}` (and `labels.{id}`, `label_wordings.{id}`, repeater rows) validated by `FieldValueRules::for()`, exactly as `ContactLifeEventController::valuesFrom()` does. Ids come from `GET life-event-types/{type}/fields` for a new life event, and from the life event itself for an existing one.
- **Rationale**: `SetFieldValues` takes values keyed by id, and a field key is only unique inside its section, so it cannot address a field across the version.
- **Rejected**: keying by `key` (ambiguous across sections).

## R4. File fields are refused on write, read as a file name

- **Decision**: the rule of a field that holds a file becomes `prohibited` in the API and MCP rule set. Update passes `removedFieldIds: []`, so stored files are never touched. Read uses `FieldValueFormatter`, which gives the original file name.
- **Rejected**: uploads over JSON (clarified 2026-10-11).

## R5. Omitted `fields` keeps the values; sent `fields` replaces the set

- **Decision**: `UpdateLifeEvent` takes `?array $values`; `null` skips `SetFieldValues` entirely. A type change with `null` values passes `[]`, so a required field of the new type is refused.
- **Rationale**: `SetFieldValues` treats a missing key as cleared. Without this, a client correcting a date would have to resend every value, in a write shape it cannot rebuild from the read (R6).
- **Top level attributes**: the API's `PUT` replaces them, like `PUT contacts/{contact}/reminders/{reminder}`. The MCP update tool keeps what is left out, as the MCP docs promise for every changing tool, by filling gaps from the stored life event.

## R6. Values are read formatted, as the contact page shows them

- **Decision**: each value is `{field_id, key, label, type, row, value}`, `value` being `FieldValueFormatter::format()`, the same strings the contact list API returns for its columns.
- **Rejected**: a raw per type read shape (a new serializer for fifteen field types, with no precedent in the API). Revisit when contacts gain field values in the API.

## R7. Sensitive values

- **Decision**: the API returns a sensitive field's value only to a key with `ApiKeyScopeEnum::SensitiveFields`, using the check `SearchController::reachesSensitiveFields()` already makes. MCP never returns one, as `SearchRecords` passes `withSensitive: false`. The field is listed either way, its `value` null and `is_hidden` true.

## R8. Where it was written from

- **Decision**: `CreateLifeEvent` gains `LifeEventSourceEnum $createdFrom = LifeEventSourceEnum::Contact`, identical to the parameter branch `2026-10-11-dashboard-life-events` adds. Whichever merges second resolves a one line conflict.
