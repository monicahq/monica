# 033. GroupMember

**Status:** in progress (specs/085-groups)
**Spec:** 8.2

## What it is

One contact's membership in one group, with the stretch of time it lasted.

The period is the reason this is a model and not a plain pivot. Section 8.2 says members of a group have their own joining and leaving dates, which may differ from each other and from the group's own timeline. A child born into a household joins it years after it was formed. A flatmate leaves while the flat carries on.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API
account_id                  int                             account the membership belongs to
group_id                    int                             group being joined
contact_id                  int                             contact joining it
role                        string (255)                    what they are in the group, such as parent, child or captain
joined_on                   date                            when they joined, null when unknown
joined_precision            enum (day, month, year, decade, unknown)  how precisely the join is known
left_on                     date                            when they left, null while they are still in
left_precision              enum (day, month, year, decade, unknown)  how precisely the departure is known
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime                        set only for a membership recorded by mistake, or folded by a merge
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [Group](032-group-not-yet.md)
- `belongsTo` [Contact](019-contact-done.md)

## Rules

- A contact may hold several successive memberships of the same group, having left and come back. Only one may be current, which is `left_on` being null.
- A departure before a join is refused. Membership entirely outside the group's own period is warned about, not refused.
- A deleted contact's memberships stay in place and are hidden with the contact, and come back when it is restored (3.3).
- The role is free text.
- Joining and leaving are events on the contact timeline, alongside relationship events and life events (8.3, 9.5).
