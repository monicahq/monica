# 037. ActivityParticipant

**Status:** to create
**Spec:** 9.2, 9.5, 10.2

## What it is

Who was there. A participant is a contact or an entity of the same vault (9.2).

It is polymorphic because the specification says participants are "contacts ou entités du vault", and because an activity involving the dog is exactly as legitimate as one involving a person.

## Fields

```
id                          int                             primary key
account_id                  int                             account the participation belongs to
activity_id                 int                             activity taken part in
participant_type            string (255)                    class of the participant, a contact or an entity
participant_id              int                             id of that record
role                        string (255)                    what they were there as, such as host, guest or practitioner
counts_as_interaction       boolean                         copied from the type at write time, so recomputing inactivity never joins
notes                       string (255)                    anything specific to this participant's presence
position                    int                             order participants are listed in
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [Activity](036-activity-not-yet.md)
- `morphTo` participant, a [Contact](019-contact-done.md) or an [Entity](020-entity-done.md)

## Rules

- A participant appears once per activity.
- The participant lives in the same vault as the activity. This is the compartment boundary and it holds here too.
- `counts_as_interaction` is copied from the activity type at write time. It is denormalised on purpose: recomputing a contact's `last_activity_at` is a hot path and it should read one indexed table, not join through the activity to its type.
- Adding a participant to an activity that counts refreshes `last_activity_at` on that contact. Removing one recomputes it.
- Deleting a participation never deletes the activity. An activity with nobody left is still a valid activity (9.2).
- `Moi` may be a participant like anybody else (4.3). "We lived in Montreal" is exactly the sentence section 4.3 wants to be expressible.

## Open questions

- Whether roles come from a value list. Free text is fine to start and cannot be filtered on, which will be wanted the moment somebody tries to count how often they hosted.
