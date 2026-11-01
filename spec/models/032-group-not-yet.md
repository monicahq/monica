# 032. Group

**Status:** in progress (specs/085-groups)
**Spec:** 3.2, 8.2

## What it is

A set of contacts that exists independently of the links between them: a household, a couple, a family, a team, a circle of friends. It is in the immutable core (3.2).

Section 8.2 is unusually firm about why this is separate from relationships, and the sentence to keep is "le foyer n'est pas le couple". Two partners may live apart. They may have several households. They may stay co-parents after separating. A household is never derived or suggested from a couple in the first version.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the group belongs to
vault_id                    int                             vault the group lives in
name                        string (255)                    name of the group, such as the Halpert household
description                 text                            what it is, in the user's own words
started_on                  date                            when the group came into being, null when unknown
started_precision           enum (day, month, year, decade, unknown)  how precisely the start is known
ended_on                    date                            when it stopped existing, null while it still does
ended_precision             enum (day, month, year, decade, unknown)  how precisely the end is known
dav_uri                     string (255)                    the .vcf name a device chose for its card, null for a group made here
created_at                  datetime
updated_at                  datetime                        moves when a membership changes, so the CardDAV sync token moves
deleted_at                  datetime
deleted_with_vault          boolean                         whether the deletion of its vault took it out
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `hasMany` [GroupMember](033-group-member-not-yet.md)
- `belongsToMany` [Contact](019-contact-done.md) through [GroupMember](033-group-member-not-yet.md)

## Rules

- A group is never created or suggested automatically from a relationship (8.2).
- A group has its own name and its own timeline, distinct from the timelines of its members.
- Every member is a contact in the same vault.
- A contact belongs to as many groups as it needs. Two households is the case the specification names.
- A group has no kind. Its name says what it is, and [GroupType](031-group-type-not-yet.md) was dropped (specs/085-groups).
- A group is soft deleted, and deleting it never touches its members. It has no photo of its own.
- Whether a group exists today is `ended_on` being null, and its headcount is counted, never stored.
- The specification puts groups in v1 (15), and their model is complete from the start even if the interface arrives in pieces. There is no simplified version to break later.

## Open questions

- Whether a group can hold entities as well as contacts. A household that includes the dog is a natural thing to want. Section 8.2 describes groups as sets of contacts, so this file follows that and the question is worth asking out loud.
