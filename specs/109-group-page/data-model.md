# Data model: Group page

Production holds data, so each change is a new migration (`up()` only). Spec 085 owns the group and
membership tables; only what changes is here.

## life_events (new column)

```
group_id    int, nullable    foreignIdFor(Group::class)->nullable()->constrained()->nullOnDelete()
                             the group the activity was recorded on; null for every other life event
```

Set once, by `CreateLifeEvent`. Never changed by `UpdateLifeEvent`. A soft deleted group keeps the
link, so restoring the group brings its history back. Indexed with `happened_on` for the history.

## group_notes (new)

```
id                   int          primary key
uuid                 string (36)  route key
account_id           int          constrained
vault_id             int          constrained
group_id             int          constrained, cascade on delete
body                 text         plain text, sanitized, required, at most 65,535 characters
created_by_user_id   int, null    constrained, null on delete
created_at           datetime     the date the history shows
updated_at           datetime
deleted_at           datetime     soft delete
```

Index `(group_id, created_at)`. Uses `ResolvesModelInVault`, which `RouteProtectionTest` checks; the
controller also refuses a note whose `group_id` is not the group in the URL.

## group_files (new)

```
id                   int          primary key
uuid                 string (36)  route key
account_id           int          constrained
group_id             int          constrained, cascade on delete
file_id              int          constrained
added_by_user_id     int, null    constrained, null on delete
created_at           datetime     "added on" in the list
updated_at           datetime
```

Unique `(group_id, file_id)`. Each row holds one reference on `files.reference_count`. Uses
`ResolvesModelInAccount`; the controller checks the group as for notes.

## reminders (no schema change)

`remindable_type` may now be `Group`'s morph class. Rules: source is `manual`; a group has no archive state;
`DestroyGroup` deactivates with `record_deleted`, `RestoreGroup` reactivates the same reason.
