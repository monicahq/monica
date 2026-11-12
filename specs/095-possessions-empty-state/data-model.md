# Data Model: Possessions Empty State

## `possession_types` (migration `2026_10_26_000000`, edited, R1)

```
icon    string (30), nullable    one of the glyphs of the section catalogue, or none
```

`PossessionType`: `icon` in `#[Fillable]`, `@property string|null $icon`. Factory: `icon` null.

## Default icons (`PossessionTypeEnum::icon()`, R3)

| Glyph | Defaults |
| --- | --- |
| `book` | Comics, Books |
| `layers` | Trading cards, Furniture |
| `music` | Vinyl records, CD, Musical instruments |
| `file` | DVD |
| `coin` | Coins |
| `mail` | Stamps |
| `people` | Action figures |
| `target` | Video games |
| `star` | Watches |
| `food` | Wine |
| `car` | Cars |
| `activity` | Bicycles |
| `home` | Household appliances, Homes |
| `phone` | Electronics |
| `briefcase` | Tools |
| `profile` | Clothing |
| `gift` | Jewellery |
| `heart` | Art |
| `ball` | Sports |
| `tag` | Other |

## Actions

| Action | Change |
| --- | --- |
| `CreatePossessionType` | `?string $icon = null`; `''` to null; outside `Section::ICONS` refused on `icon` |
| `UpdatePossessionType` | `?string $icon`, required argument (R5); same rules |
| `CreateDefaultPossessionTypes` | writes `$case->icon()` |
| `ImportPossessionType` | writes the icon the reader returns |
| `ExportPossessionType` | writes `icon` (null when none) after `colour` |

## `PossessionsViewModel` (`app/ViewModels/Vaults`, R8)

| Method | Returns |
| --- | --- |
| `isEmpty()` | the vault holds no possession, whatever its status |
| `types()` | eight `{name, colour, tint, icon, fields, newUrl}`; `fields` is two names joined with `, `, or "No field yet" |
| `typesSettingsUrl()`, `tagsSettingsUrl()`, `locationsSettingsUrl()`, `newUrl()` | routes |
| `locationsLine()` | `''` without locations; else "Home, Storage unit and Martine’s house" style names, plus "and :count more", with the total |
| `hasLocations()` | for the block and its link |

`PossessionFormViewModel`: constructor gains `?PossessionType $chosenType = null` (R7).
