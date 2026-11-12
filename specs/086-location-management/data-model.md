# Data Model: Location Management

## `locations` (new table)

| Column | Type | Rule |
| --- | --- | --- |
| `id` | id | |
| `uuid` | uuid, unique | route key, through `HasUuids` |
| `account_id` | `foreignIdFor(Account::class)->constrained()->cascadeOnDelete()` | shared by every vault of the account |
| `parent_id` | `foreignIdFor(Location::class, 'parent_id')->nullable()->constrained('locations')->cascadeOnDelete()` | same account; never itself or a descendant |
| `name` | string(255) | required, plain text |
| `emoji` | string(32), nullable | one emoji (research R6) |
| `address` | text, nullable | free text, several lines |
| timestamps | | |

Index on `(account_id, parent_id)`. The cascade on `parent_id` is only reached when an account is deleted:
`DestroyLocation` refuses a location with children.

## `Location` model

- `#[RouteKey('uuid')]`, `#[Fillable([...])]`, `HasUuids`, `ResolvesModelInAccount`, `HasFactory`.
- `account()`: `BelongsTo`. `parent()`: `BelongsTo<Location>`. `children()`: `HasMany<Location>`.
- `Account::locations()`: `HasMany<Location>`.

## Actions

| Action | Inputs | Refuses |
| --- | --- | --- |
| `CreateLocation` | user, name, ?parent, ?emoji, ?address | parent of another account |
| `UpdateLocation` | user, location, name, ?parent, ?emoji, ?address | location or parent of another account; parent that is the location or inside it |
| `DestroyLocation` | user, location | location of another account; `LocationPolicy::delete` denial |

Each sanitises `name` and `address` with `TextSanitizer`, trims an empty emoji or address to null, and
dispatches `LogUserAction` with `LocationCreation`, `LocationUpdate` or `LocationDeletion` and the
name as parameter.

## `LocationPolicy`

`delete(User, Location)`: denies while `children()` exists, with the counted message of research R4.

## State

None. A location has no status; it exists until deleted.
