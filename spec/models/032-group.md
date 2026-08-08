# 032. Group

**Status:** to create
**Spec:** 3.2, 8.2

## What it is

A set of contacts that exists independently of the links between them: a household, a couple, a family, a team, a circle of friends. It is in the immutable core (3.2).

Section 8.2 is unusually firm about why this is separate from relationships, and the sentence to keep is "le foyer n'est pas le couple". Two partners may live apart. They may have several households. They may stay co-parents after separating. A household is never derived from a couple, and the product must never infer one from the other. It may suggest creating a group. It may not create one on its own.

## Fields

```
id                          int                             primary key
account_id                  int                             account the group belongs to
vault_id                    int                             vault the group lives in
group_type_id               int                             kind of group it is
uuid                        string (36)                     unguessable identifier used in URLs and by the API
name                        string (255)                    name of the group, such as the Halpert household
description                 text                            what it is, in the user's own words
started_on                  date                            when the group came into being, null when unknown
started_precision           enum (day, month, year, decade, unknown)  how precisely the start is known
ended_on                    date                            when it stopped existing, null while it still does
ended_precision             enum (day, month, year, decade, unknown)  how precisely the end is known
is_active                   boolean                         whether the group exists today
member_count                int                             running count of current members
avatar_file_id              int                             photo of the group, null when a generated avatar is used
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md), [GroupType](031-group-type.md)
- `belongsTo` [File](008-file.md) as avatar
- `hasMany` [GroupMember](033-group-member.md)
- `belongsToMany` [Contact](019-contact.md) through [GroupMember](033-group-member.md)

## Rules

- A group is never created automatically from a relationship (8.2). Suggesting is allowed, creating is not.
- A group has its own name and its own timeline, distinct from the timelines of its members.
- Every member is a contact in the same vault.
- A contact belongs to as many groups as it needs, including several of the same kind. Two households is the case the specification names.
- A group is soft deleted, and deleting it never touches its members.
- The specification puts groups in v1 (15), and their model is complete from the start even if the interface arrives in pieces. There is no simplified version to break later.

## Open questions

- Whether a group can hold entities as well as contacts. A household that includes the dog is a natural thing to want. Section 8.2 describes groups as sets of contacts, so this file follows that and the question is worth asking out loud.
- Whether a group can contain another group. Not asked for, and the recursion cost is not obviously worth it.
