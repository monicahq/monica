# Data Model: Restore the Former Design

**Feature**: [../spec.md](./spec.md) | **Date**: 2026-08-26

## No schema change

This feature adds no table, no column, no index and no migration. It drops none either. Nothing in
`database/` is touched.

That is worth stating rather than leaving implied, because a design change of this size looks like
one that might reach the database, and there is one place where it could have: the colour columns.

## The colour columns, and why they are safe

Five columns hold a colour. Every one of them holds a hex string seven characters long, never a token
name, so removing a token from the configuration file cannot orphan a stored value.

| Table | Column | Holds | Chosen by |
|---|---|---|---|
| `vaults` | `colour` | Hex, 7 | The user, from a fixed list |
| `entity_types` | `colour` | Hex, 7 | The user |
| `value_list_items` | `colour` | Hex, 7, nullable | The user |
| `contacts` | `avatar_colour` | Hex, 7 | Generated when the contact is created |
| `entities` | `avatar_colour` | Hex, 7 | Generated |

`App\Enums\VaultColourEnum` is the fixed list behind the first of them. It has not been edited since
pull request 50, and its eight values are the former direction's own palette: `#3f5fd0` is what
`--brand` used to be, and `#4f8a49`, `#9a6a24` and `#9a3f2b` are the former success, warning and
error exactly. So the colours a user has already chosen for their vaults are already the restored
palette's colours, and the enum is left alone.

`field_values.value_string` also stores a colour when the field is of the colour type. That is a
value somebody typed into a record and has nothing to do with the interface.

## What does change shape: the presentation arrays

Four view models hand Blade a Tailwind class string. Those strings are the palette hard coded in
PHP, and they are what makes this feature reach past Blade. Each array shape below changes.

### `Settings\SettingsViewModel`

Rows describing each settings destination.

Today: `array{title: string, description: string, icon: string, iconClass: string, meta: string|null, url: string|null}`

Afterwards: the same, with `iconClass` removed. The glyph is drawn in the restored neutral treatment
by the component, so the view model stops choosing a colour. `icon`, which names the glyph, stays:
that is meaning, not decoration.

### `Marketplace\MarketplaceViewModel`

A catalogue card.

Today: carries `typeIconClass` and `typeTextClass`, each a pair of hue classes chosen by whether the
item is a template or a section.

Afterwards: one key, `type`, holding `template` or `section`. The card decides how to draw it, and
the word is already on the card, so the distinction survives without the hue. This is the shape a
test can assert without asserting a palette, which is why `MarketplaceViewModelTest` gets shorter.

### `Vaults\VaultDashboardViewModel`

Dashboard blocks.

Today: each block carries `titleClass` and `iconClass`.

Afterwards: both removed. A block is told apart by its heading and its position on the dashboard.

### `Vaults\ContactViewModel`

Today: `FACT_HUES` (three) and `SECTION_HUES` (seven), both assigned by position in a rotation.

Afterwards: both constants removed, and the keys that carried them removed from the section and fact
shapes. The reasoning, including the argument against, is
[research decision 19](./research.md).

## The tone vocabulary that replaces the hues

Where a tone genuinely means something, it travels as a word rather than as a class string. Four
words, and no more:

| Tone | What it means | Where it is used |
|---|---|---|
| `neutral` | No state worth marking | The default badge, an ordinary chip |
| `success` | It worked, it is active, it was delivered | A delivered email, a published version, an active entity type |
| `warning` | It needs attention but nothing is broken | A draft version, a pending submission, an expiring share link |
| `danger` | It failed, it is gone, it is retired | A bounced email, a rejected submission, a retired value, a revoked link |

A component receiving one of these renders a tint, a hairline and a word. The word is not optional:
FR-006 means the tone alone never carries the meaning.

This vocabulary already exists in `badge` and `notice`. What changes is that the four view models
above stop bypassing it with raw classes.

## State transitions

None. This feature changes no state machine. Every status enum in the product
(`MarketplaceListingStatusEnum`, `MarketplaceVersionStatusEnum`, `VersionStatusEnum`,
`ShareLinkExpiryEnum` and the rest) keeps its cases and its transitions untouched. What changes is
only which tone a screen draws for a case.
