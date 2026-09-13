# Data model: Readable vault and contact addresses

Three columns and two tables. Decisions are in [research.md](research.md).

## Columns added to tables that exist

| Table | Column | Shape | Comment it carries |
| --- | --- | --- | --- |
| `vaults` | `slug` | `string(128)` | readable identifier used in URLs and by the API, unique within the account |
| `contacts` | `slug` | `string(128)` | readable identifier used in URLs and by the API, unique within the vault |
| `users` | `uses_readable_addresses` | `boolean` default `true` | whether the product builds addresses that name a vault and a contact |

`vaults` gains `unique(['account_id', 'slug'])` and `contacts` gains `unique(['vault_id', 'slug'])`. Each migration is edited in place rather than added to, which the migrations skill allows until the end of 2026.

## Tables added

`vault_slug_reservations`

| Column | Shape | Comment it carries |
| --- | --- | --- |
| `id` | `id` | primary key |
| `account_id` | `unsignedBigInteger` | account the reservation is scoped to |
| `vault_id` | `unsignedBigInteger` | vault that carried the name, kept so a merge or a restore can be reasoned about |
| `slug` | `string(128)` | name this vault has carried, never given to another |

`contact_slug_reservations` is the same with `vault_id` as the scope and `contact_id` as the holder.

Each carries `timestamps()`, a `unique` across scope and `slug`, and its foreign keys declared at the bottom of the migration with `cascadeOnDelete`, never through `constrained()`.

A row is written when a record is given a name and is never deleted, including when the record is. That is what FR-006 rests on: the collision test reads the live column and the reservation table, so a name a record has released is unavailable to every other record in the scope for good.

## Rules the columns carry

- A vault's name is built from `vaults.name`. A contact's is built from the whole displayed name, which is `prefix`, `first_name`, `middle_name`, `last_name`, `suffix` and `maiden_name`, in the order `name_order` gives, falling back to `nickname` the way `nameInOrder()` already does.
- The name is rebuilt on every save where the parts it is built from changed, in the model's `saving` hook. Nothing asks the user for one and nothing lets them edit one.
- A collision inside the scope, live or reserved, appends `-2`, then `-3`, shortening the stem to stay inside 128 characters. `SlugBuilder::build()` already does exactly this and its loop is shared.
- A name reducing to nothing after normalisation falls back to a fixed word, which then collides and takes a number, so every record has an address of its own.

## What is not touched

`contacts.uuid` and `vaults.uuid` keep their columns, their unique indexes and their comments. They stay the permanent address, the one the API reports as `id`, and the one the product builds when the preference is off.

`share_links`, `files` and the synchronisation columns address their records by identifiers of their own and are unchanged.
