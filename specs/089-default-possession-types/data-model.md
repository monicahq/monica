# Data Model: Default Possession Types

No table or column. The action writes rows into the three 088 tables, for new accounts through the job and for existing ones through `2026_10_26_000001_backfill_possession_types`.

## Rows written per account

| Table | Rows | Values |
| --- | --- | --- |
| `possession_types` | 25 | `name` from `label()` in the user's language (R3), `colour` from the table below |
| `possession_field_groups` | one per group | `name` translated, `position` from 1 in catalogue order |
| `possession_fields` | one per field | `name` translated, `kind`, `possession_field_group_id` or null when standalone, `position` from 1 within its group or among the standalone fields, `options` a list for a select, never empty, null otherwise |

## `PossessionTypeEnum`

| Member | Returns |
| --- | --- |
| `label(): string` | English name of the type, the translation key |
| `colour(): VaultColourEnum` | Colour of the type |
| `blocks(): array` | `list<{name: ?string, fields: list<field>}>`, the unnamed block holding the standalone fields and coming first, a field being `{name: string, kind: PossessionFieldKindEnum, options: list<string>}` |
| `codes(): list<string>` (static) | The options written untranslated, codes and publisher names (R2) |
| `translationKeys(): list<string>` | Type, group, field and option names, codes excluded |

## Colours

| Colour | Types |
| --- | --- |
| Blue | Comics, Books, Electronics and computers |
| Violet | Vinyl Records, CD, DVD, Video Games, Musical instruments |
| Amber | Trading Cards, Coins, Stamps, Watches, Jewellery |
| Plum | Wine, Action Figures / Toys, Art and decoration |
| Rust | Cars, Bicycles, Tools and equipment |
| Teal | Household appliances, Furniture, Homes |
| Green | Clothing and accessories, Sports and outdoors |
| Slate | Other |

## Actions and jobs

| Class | Does |
| --- | --- |
| `CreateDefaultPossessionTypes(user)` | Returns `[]` when the account holds a type; otherwise writes the catalogue in one transaction, logs `PossessionTypesSeeded` once, returns the 25 types |
| `SeedPossessionTypes(user)` | Queued job calling the action |
| `CreateAccount` | Appends `SeedPossessionTypes` to its seeding chain |
