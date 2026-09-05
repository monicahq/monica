# 004. Log

**Status:** exists (`app/Models/Log.php`), built as far as the models it depends on allow
**Spec:** 3.2, 3.3, 12, 14.2

## What it is

The journal des modifications of section 12: who changed what, when, from which value, and through which interface. Section 3.2 puts it in the core, alongside reminders, and for the same reason: the product must be able to treat it transversally whatever structure the user invented.

It answers a different question from a snapshot ([RecordSnapshot](023-record-snapshot-done.md)). A snapshot exists to go back. A log exists to explain. They are not alternatives and neither replaces the other.

Today the table records account level actions as a translation key plus parameters. That shape is good and should be kept, because it renders in the language of whoever is looking and survives an action the current version no longer knows about. What it lacks is a target and a before value.

## Fields

```
id                          int                             primary key
account_id                  int                             account the action was performed in
vault_id                    int                             vault the action touched, null for account level actions
user_id                     int                             user who acted, null once that user is deleted and null when the system acted
user_email                  string (255)                    address of the user at the time, so the log still says who acted once they are gone
action                      string (255)                    action performed, also used as the translation key
parameters                  json                            parameters for the translation
loggable_type               string (255)                    class of the object acted on, null when the action targets nothing in particular
loggable_id                 int                             id of the object acted on
changes                     json                            the fields that changed, each with its value before and after
source                      enum (web, api, mcp, import, automation, module, system)  interface the action came from
source_detail               string (255)                    which token, module or import run precisely, null when there is nothing to name
ip_address                  string (45)                     address the action came from, null when the system acted
created_at                  datetime
updated_at                  datetime
```

`loggable_type` and `loggable_id` are what let the log cover every object rather than only records: vaults, structures, private links, modules and settings (12). `changes` holds the before and after values, without which the log says that something changed but not what it changed from, which is half the question section 12 asks. `source` and `source_detail` tell a change made by an automation from one made by hand (12, and 3.6 which makes the API a first class caller). `vault_id` lets the log be filtered and scoped the way everything else in a vault is.

`user_id` is null for actions the application performed on its own, such as disabling a webhook subscription after repeated failures (14.4). `user_email` then holds `system`, and the entry reads as having been performed by the application rather than by anybody.

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [Vault](007-vault-in-progress.md)
- `belongsTo` [User](002-user-done.md)
- `morphTo` loggable

## Rules

- A log row is never updated and never deleted, except under an explicit retention policy (12). No soft delete, because there is nothing to restore.
- The log outlives its subject. Deleting a contact does not delete the entries describing it, which is why `loggable_id` carries no foreign key.
- The log outlives its author, which is why `user_email` is copied at write time.
- An action the running version no longer recognises is shown by its raw name rather than hidden. This is already the behaviour of the `description` accessor and it stays.
- Writing a log entry is never optional and never conditional on a setting. Every action that changes state writes one.

## Not done yet

- `loggable` is filled by most of the actions there are, and `changes` by six of them. Naming what was acted on is a line in the action and every action that writes to a row now writes it; saying what a value was before it changed is a shape each action has to build for itself, and only the ones that rename something (a vault, a value list and its items, an entity type, a contact, an entity) do. Section 12 is only answered once every action that edits rather than creates does the same.
- `vault_id` is filled by the actions performed inside a vault, and the model has the relationship. It still carries no foreign key, for the same reason `loggable_id` does not: the log outlives what it describes.
- `source` is always `web`. The column and `LogSourceEnum` carry every case, and `LogUserAction` defaults to `web` and is never told otherwise, so version one of the API writes its entries as though a browser had made them. That is the first real gap in the column rather than a case waiting on an interface that does not exist: the API is here. The MCP server, imports, automations and modules each set their own as they arrive.
- Nothing writes an entry with no user behind it yet. The model and the job handle one, and the first real case is the webhook subscription of 14.4.
- Nothing reads `changes` back. The log screen lists what was done and by whom; the before and after values sit in the column and no screen unfolds one.

## Decisions taken while building it

- **The job carries the morph rather than the row.** `LogUserAction` takes a model in its constructor and keeps only its type and its key, because half of what is worth writing down is a deletion and the row is gone by the time the queue reaches the job. A job holding the model would go looking for it on the way out of the queue and fail, which would lose the record of every deletion the product performs.

## Open questions

- **How to treat a sensitive field.** Section 12 raises it and does not answer it: a field marked sensitive is masked on screen, but its old value would sit in clear text in `changes`. Three options are on the table: mask sensitive values inside `changes`, encrypt `changes` at rest, or restrict who reads the log. Mono-user removes most of the force of the third, since the only reader is the owner. Until this is decided, `changes` must record the fact that a sensitive field changed and omit its values. That is the safe default and it can be relaxed later. The reverse cannot.
- Retention. The specification allows a policy without defining one. Nothing is pruned until it does.
