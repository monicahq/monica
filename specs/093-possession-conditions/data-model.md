# Data Model: Possession Conditions

## Table `possession_conditions`

Migration `database/migrations/2026_10_26_000004_create_possession_conditions_table.php`, `up()`
only, every column commented.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | big increments | |
| `uuid` | uuid, unique | route key and API id |
| `account_id` | `foreignIdFor(Account::class)->constrained()->cascadeOnDelete()` | |
| `name` | string(50) | unique in the account ignoring case, checked in the action |
| `colour` | string(7) | a `VaultColourEnum` value |
| `icon` | string(30), nullable | one of `Section::ICONS`, or null |
| `position` | unsigned integer | 1..n within the account, no gaps |
| `created_at`, `updated_at` | timestamps | |

Index: `(account_id, position)`.

Backfill: `2026_10_26_000005_backfill_possession_conditions.php` (R2).

## Model `App\Models\PossessionCondition`

- `HasFactory`, `HasUuids`, `ResolvesModelInAccount`; `#[RouteKey('uuid')]`;
  `#[Fillable(['account_id', 'name', 'colour', 'icon', 'position'])]`.
- `account(): BelongsTo`. `Account::possessionConditions(): HasMany`, ordered by `position`.
- Docblock in full: what a condition is, that it belongs to the account, that deleting it takes
  nothing else with it while possessions cannot carry one.
- Factory: an Office name (`Mint`, `Like new`, `Well loved`, `Needs repair`, `Water damaged`), a
  random palette colour, no icon, `position` 1. No `unique()`.

## Enum `App\Enums\PossessionConditionEnum`

Cases `New`, `LikeNew`, `Used`, `Worn`, `Damaged`, `Broken`; `label()` gives "New", "Like new",
"Used", "Worn", "Damaged", "Broken"; `colour()` gives Green, Teal, Blue, Amber, Rust, Slate;
`translationKeys()` gives the label. Implements `Translatable`.

## Actions

| Action | Inputs | Does | Refuses | Journal |
| --- | --- | --- | --- | --- |
| `CreatePossessionCondition` | user, name, colour = Blue, icon = null | sanitises, appends at `max + 1` | empty name; duplicate ignoring case; icon outside the catalogue | `PossessionConditionCreation` (:name) |
| `UpdatePossessionCondition` | user, condition, name, colour, icon | sanitises, saves; keeps position | another account (`ModelNotFoundException`); empty; duplicate excluding itself; icon outside the catalogue | `PossessionConditionUpdate` (:name) |
| `UpdatePossessionConditionPosition` | user, condition, position | splices and renumbers 1..n | another account | `PossessionConditionPositionUpdate` (:name) |
| `DestroyPossessionCondition` | user, condition | deletes, renumbers the rest | another account | `PossessionConditionDeletion` (:name) |
| `CreateDefaultPossessionConditions` | user | the six of the enum, in the user's locale, positions 1..6, in a transaction | nothing; returns `[]` when the account holds any condition | `PossessionConditionsSeeded` |

The icon check lives in the action as well as in the controllers, since the action is what the tools
and any later caller reach: a `ValidationException` on `icon`, "Pick an icon from the list.".

Journal descriptions: "Added the possession condition :name", "Changed the possession condition
:name", "Moved the possession condition :name", "Deleted the possession condition :name", "Set up the
possession conditions the account starts with" (the wording `PossessionTypesSeeded` uses).

## Job `App\Jobs\SeedPossessionConditions`

Runs `CreateDefaultPossessionConditions`; appended to the chain in `CreateAccount::seedTheAccount()`.

## View model `App\ViewModels\Settings\Account\PossessionConditions\PossessionConditionsViewModel`

- `title()` "Possession conditions", `description()`, `createAction()`.
- `colours()`: the palette as `{value, label, tint, swatch}` for `x-mark-picker`, no neutral entry.
- `glyphs()`: `Section::ICONS`.
- `conditions()`: in position order, each `{uuid, name, colour, colourName, icon, position,
  isFirst, isLast, updateAction, positionAction, destroyAction}`.
- `countLine()`: `trans_choice(':count condition, from best to worst.|:count conditions, from best to
  worst.')`, `''` at zero.
- `isEmpty()`.
