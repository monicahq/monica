# 033. GroupMember

**Status:** to create
**Spec:** 8.2

## What it is

One contact's membership in one group, with the stretch of time it lasted.

The period is the reason this is a model and not a plain pivot. Section 8.2 says members of a group have their own joining and leaving dates, which may differ from each other and from the group's own timeline. A child born into a household joins it years after it was formed. A flatmate leaves while the flat carries on.

## Fields

```
id                          int                             primary key
account_id                  int                             account the membership belongs to
group_id                    int                             group being joined
contact_id                  int                             contact joining it
role                        string (255)                    what they are in the group, such as parent, child or captain
joined_on                   date                            when they joined, null when unknown
joined_precision            enum (day, month, year, decade, unknown)  how precisely the join is known
left_on                     date                            when they left, null while they are still in
left_precision              enum (day, month, year, decade, unknown)  how precisely the departure is known
is_current                  boolean                         whether they are a member today
notes                       text                            anything worth noting about this membership
position                    int                             order members are listed in
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md)
- `belongsTo` [Group](032-group.md)
- `belongsTo` [Contact](019-contact.md)

## Rules

- A contact may hold several successive memberships of the same group, having left and come back. Only one may be current.
- A departure before a join is refused. Membership entirely outside the group's own period is warned about, not refused.
- `is_current` maintains the group's `member_count`.
- Deleting a contact soft deletes its memberships and restores them if the contact comes back (3.3).
- Joining and leaving are events on the contact timeline, alongside relationship events and activities (8.4, 9.5).

## Open questions

- Whether `role` should come from a value list scoped to the group type rather than being free text. A list is filterable and needs seeding per group type, which is more machinery than the specification asks for.
