# Data model: Contact timeline and adding a life event

Columns and meanings are those of `spec/models/036` and `037`; this file records only what the
implementation decides beyond them. Migrations `2026_09_23_000000_create_life_events_table` and
`2026_09_23_000001_create_life_event_participants_table`, `up()` only, foreign keys at the bottom.

## LifeEvent (`life_events`)

| Column | Type | Rule here |
| --- | --- | --- |
| `happened_on` | date | required |
| `date_precision` | `DatePrecisionEnum` (day, month, year, decade, unknown) | `day` |
| `created_from` | `LifeEventSourceEnum` (day, contact, api, mcp, import, module) | `contact` |
| `title`, `location` | string 255, nullable | trimmed, empty becomes null |
| `narrative` | text, nullable | |
| `duration_in_minutes` | int, nullable | 1 to 100000 |
| `is_shareable`, `narrative_is_shareable` | boolean | false |
| `participant_count` | int | set by `CreateLifeEvent` |

- Foreign keys: `account_id`, `vault_id`, `life_event_type_id`, `structure_version_id`,
  `created_by_user_id` (null on delete).
- Index: `(vault_id, happened_on)`.
- Traits: `HasUuids`, `ResolvesModelInVault`, `SoftDeletes`, `HasFactory`.
- Relations: `account`, `vault`, `lifeEventType` (with trashed), `structureVersion`, `participants`
  (ordered by `position`), `contacts` (morphedByMany through participants), `fieldValues`,
  `fieldValueRows` (morphMany `valuable`).
- Accessor `name`: `title`, else the type’s translated name.

## LifeEventParticipant (`life_event_participants`)

| Column | Rule here |
| --- | --- |
| `participant_type`, `participant_id` | `Contact` only in this feature |
| `role`, `notes` | null |
| `position` | 0 for the page’s contact, then the order chosen |

- Unique `(life_event_id, participant_type, participant_id)`.
- Index `(participant_type, participant_id, life_event_id)` for the timeline query.
- No soft delete: a participation goes with its life event.

## Contact

- `lifeEvents()`: morphToMany through `life_event_participants`, for the timeline.
- `life_event_count` and `last_life_event_at` are written by `CreateLifeEvent`. `last_interaction_at`
  is not touched.

## Enums

- `DatePrecisionEnum` and `LifeEventSourceEnum` in `app/Enums`, string backed.
- `UserActionEnum::LifeEventCreation = 'life_event_created'`, described as "Recorded the life event :type on
  the timeline of :name".
