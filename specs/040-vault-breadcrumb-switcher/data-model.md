# Data model: Vault switcher in the breadcrumb

No migration, no new column, no new model, no change to any relationship. This file records what the
change reads and where it comes from, so the plan can be checked against the schema rather than
against memory.

## What is read

### Vault (`vaults`)

| Attribute | Origin | Used for |
| --- | --- | --- |
| `id` | column | the route to the vault's dashboard |
| `account_id` | column | scoping the list to the account that owns the vault the reader is in |
| `name` | column | the crumb's label, the entry's label, and the character on the tile |
| `colour` | column, a hex string, one of `VaultColourEnum` | the tile behind the character |
| `contact_count` | column, a running count | the line under each entry in the list |
| `is_default` | column | first sort key, so the default vault leads the list |
| `position` | column | second sort key |

`colour` and `contact_count` are both declared in `2026_08_08_000000_create_vaults_table.php`.
`contact_count` is maintained on write, which is why the list costs one query and not one query per
vault.

### Account

Read only through `Vault::$account_id`. The account itself is never loaded.

## The query

One query per render of a vault screen, replacing the identical query
`App\View\Components\Vault\Sidebar::vaults()` performs today:

- every vault whose `account_id` matches the current vault's,
- ordered by `is_default` descending, then `position` ascending.

The ordering is copied from the block being removed, so the vaults appear in the order readers
already know them in.

## Derived values

Neither is stored, both are computed at render.

- **The tile character**: the first character of `name`, uppercased where the character has an
  uppercase form, left as written where it does not. It repeats the name that sits beside it, so it
  is hidden from assistive technology rather than read out twice.
- **Whether the crumb is a control**: true when the collection holds more than one vault. This is
  `count()` on a collection already in memory, not a second query.

## What is not read

`Vault::$description` and the vault's contacts. The list names vaults and counts people; it opens
nothing else.
