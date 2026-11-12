# Data Model: Possession Tags

## Table `possession_tags`

Migration `database/migrations/2026_10_26_000003_create_possession_tags_table.php`, `up()` only,
every column commented, as `2026_10_26_000000_create_possession_types_table.php`.

| Column | Type | Rule |
| --- | --- | --- |
| id | id | primary key |
| uuid | uuid, unique | identifier used in addresses and by the API |
| account_id | `foreignIdFor(Account::class)->constrained()->cascadeOnDelete()` | the account the tag belongs to, shared by every vault |
| name | string(50) | 1 to 50 characters, unique in the account ignoring case (R3) |
| colour | string(7) | one of the vault colours, as a hexadecimal value (R2) |
| timestamps | | |

Index: `(account_id, name)` for the list. No unique index (R3). No soft deletes: a deleted tag
is gone, and nothing points at it yet.

## Model `App\Models\PossessionTag`

`HasFactory`, `HasUuids`, `ResolvesModelInAccount`, `#[RouteKey('uuid')]`,
`#[Fillable(['account_id', 'name', 'colour'])]`. Relation `account()`. `Account` gains
`possessionTags()`. Full model docblock, as `PossessionType`. Factory
`PossessionTagFactory`, with a name from The Office ("Dundie award winner", "Insured", "To sell").

## Actions

| Action | Takes | Does | Logs |
| --- | --- | --- | --- |
| `CreatePossessionTag` | `user`, `name`, `colour = VaultColourEnum::Blue` | sanitise, refuse empty or taken (R3, R4), create | `PossessionTagCreation` `{name}` |
| `UpdatePossessionTag` | `user`, `possessionTag`, `name`, `colour` | account check, sanitise, refuse empty or taken by another tag, save | `PossessionTagUpdate` `{name}` |
| `DestroyPossessionTag` | `user`, `possessionTag` | account check, delete | `PossessionTagDeletion` `{name}` |

Every action refuses a tag of another account with `ModelNotFoundException`, and dispatches
`LogUserAction` on the `low` queue.

Journal wordings: "Added the possession tag :name", "Changed the possession tag :name", "Deleted
the possession tag :name".

## View model `App\ViewModels\Settings\Account\PossessionTags\PossessionTagsViewModel`

| Method | Returns |
| --- | --- |
| `user()`, `title()`, `description()` | the shell and the header |
| `createAction()` | `settings.possessionTags.create` |
| `colours()` | `list<{value, label, isDefault}>` over `VaultColourEnum::cases()` |
| `tags()` | `list<{uuid, name, colour, colourName, updateAction, destroyAction}>`, sorted with `LocaleCollation::sortByCollator` |
| `countLine()` | `trans_choice(':count tag|:count tags', n)`; empty string at zero (the empty state speaks) |
| `isEmpty()` | whether the account has no tag |
