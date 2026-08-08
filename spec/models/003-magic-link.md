# 003. MagicLink

**Status:** exists (`app/Models/MagicLink.php`), no change
**Spec:** none directly

## What it is

A single use link that signs somebody in without a password. Only the hash of the token is stored, so the link that was emailed cannot be rebuilt from a database dump.

The specification does not discuss authentication, so this model is untouched by it. It is listed here because it exists, because it sits between the user and everything else, and so that nobody later assumes an undocumented table is dead weight.

## Fields

```
id                          int                             primary key
user_id                     int                             user the link signs in
token                       string (64)                     hash of the token in the link, so the token itself is never stored
expires_at                  datetime                        when the link stops working
used_at                     datetime                        when the link was used, which is also what stops a second use
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [User](002-user.md)

## Rules

- A link is usable only while `used_at` is null and `expires_at` is in the future.
- Deleting the user deletes the links, which is correct: an unused link to a deleted account must stop working immediately.
- Expired and used links are pruned on a schedule. They carry nothing worth keeping.

## Open questions

None. This model is settled.
