# Data model: Last in touch

## `life_event_types` (new column)

| Column | Type | Rule |
| --- | --- | --- |
| `updates_last_in_touch` | boolean, default `true` | Whether a life event of this type moves `last_interaction_at` on its participants. Comment: "whether its life events set the last in touch date of the people they are recorded with". |

Added in its own `add_updates_last_in_touch_to_life_event_types_table` migration; `LifeEventType`
gains it in `#[Fillable]`, its cast and its docblock.

## `contacts.last_interaction_at` (existing, now written)

The day, at midnight, of the latest life event that:

1. has the contact among its `life_event_participants` (participant type Contact), and
2. is not soft deleted, and
3. has `happened_on` not after today, and
4. has a type whose `updates_last_in_touch` is true.

Null when none qualifies. Written only by `RecountsLifeEvents` (research R2) and the backfill (R8).
The column comment, which still names the Me contact, is left alone: a shipped migration is never
edited, and the model docblock carries the new meaning.

## When it is recomputed

| Event | Who is recounted |
| --- | --- |
| Life event recorded, edited, deleted, restored | its participants before and after |
| Contacts merged, merge undone, import rolled back | the contacts the action already recounts |
| Type setting changed | every participant of a life event of that type (queued) |
| A day passes | participants of qualifying life events dated yesterday or today (daily job) |
