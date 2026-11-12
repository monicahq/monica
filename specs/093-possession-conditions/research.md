# Research: Possession Conditions

Every decision below copies a sibling already in the codebase, named in each entry. Nothing here
needed an outside source.

## R1. A new fixed concept, so principle VII is amended

- **Decision**: Possession condition joins the fixed concepts of principle VII, which go from
  thirteen to fourteen. Constitution 2.9.0 becomes 2.10.0 (MINOR, a new fixed concept), with a Sync
  Impact Report. `spec/spec.md` names it, and `spec/models/065-possession-condition-in-progress.md`
  describes it.
- **Rationale**: A condition is a record of the account with a fixed shape (name, colour, icon,
  position), not user defined data attached through a template. 092 admitted Possession tag the
  same way.
- **Alternatives considered**: A value list. Rejected: a value list is the option set of a template
  field, and possessions do not use templates.

## R2. The defaults are an enum, written by an action, seeded by a job, backfilled by a migration

- **Decision**: `App\Enums\PossessionConditionEnum` (`New`, `LikeNew`, `Used`, `Worn`, `Damaged`,
  `Broken`), implementing `Translatable`, with `label()` and `colour()`. The action
  `CreateDefaultPossessionConditions` writes the six in the user's locale, positions 1 to 6, inside
  a transaction, returns early when the account holds any condition, and logs
  `PossessionConditionsSeeded` once. The job `SeedPossessionConditions` runs it, appended to the
  chain in `CreateAccount::seedTheAccount()` after `SeedPossessionTypes`. The migration
  `2026_10_26_000005_backfill_possession_conditions.php` runs the action for every user whose
  account holds no condition.
- **Rationale**: This is exactly how 088 seeded and backfilled possession types
  (`PossessionTypeEnum`, `CreateDefaultPossessionTypes`, `SeedPossessionTypes`,
  `2026_10_26_000001_backfill_possession_types.php`). Six rows would fit in the request, but every
  other seed of the account lives in that chain, and one place for them is easier to read than a
  rule about which seeds are cheap enough.
- **Colours**: New Green, Like new Teal, Used Blue, Worn Amber, Damaged Rust, Broken Slate.
- **Alternatives considered**: Writing them synchronously in `CreateAccount`, as
  `CreateDefaultValueLists` is. Rejected for consistency with the possession siblings.

## R3. "New" already has a translation, and it must mean the condition

- **Finding**: `lang/en.json` already holds the key `New`, merged from the framework's language
  package by `monica:localize`. No screen of the application shows it today. In French it reads
  "Nouveau", where a condition reads "Neuf".
- **Decision**: Keep the English name "New", as the request says. While translating, confirm again
  that no screen shows the key, then set every locale's value for `New` to the word for the
  condition (French "Neuf", German "Neu", Spanish "Nuevo"…). One key, one meaning: the condition.
- **Rationale**: Keys are the English strings, so the two senses cannot hold two translations. The
  application never shows the other sense.
- **Alternatives considered**: "Brand new". Rejected: it changes the name the request gave.

## R4. Name, colour, icon

- **Name**: 1 to 50 characters, `max:50` inline in each controller and tool; sanitised with
  `TextSanitizer::plainText()`; empty refused with "Write a name for this condition."; a duplicate
  ignoring case refused with "You already have a condition with this name.", compared with
  `mb_strtolower()` and excluding the condition itself on update. As 092 R3 and R4.
- **Colour**: `VaultColourEnum`, stored as the 7 character hexadecimal value, Blue when left out
  (API and tools only; the screen always sends one). As 092 R2.
- **Icon**: nullable, one of `Section::ICONS`, the catalogue template sections and life event types
  offer (`Rule::in(Section::ICONS)`, as `LifeEventTypeController` does). An empty string from the
  screen means no icon and is stored as null.

## R5. The icon and the colour are picked with `x-mark-picker`

- **Decision**: The add form and each row's edit form use `x-mark-picker` with `layout="split"`:
  an Icon trigger and a Colour trigger, each opening its popover. The colours passed are the eight
  palette entries with no neutral option, so a colour is always chosen; the glyph popover starts
  with "No icon". The surrounding `x-data` holds `icon` and `colour`, which the picker and the
  preview share.
- **Rationale**: It is the component that already picks an icon and a colour together, on the life
  event type form and the template builder. The tags screen used a row of native radios for the
  colour alone; adding 38 glyphs as radios to every row would make each edit form a wall of
  swatches.
- **Consequence**: The list panel is `x-panel :clip="false"`, so a popover opened in the last rows
  is not cut off by the panel's edge.
- **Known limit, not fixed here**: the picker labels each glyph with its catalogue key (`ball`,
  `layers`) rather than a translated name. That is the component's behaviour on every screen that
  uses it, and is reported rather than changed in this feature.

## R6. Order: position, appended on creation, closed on deletion

- **Decision**: An unsigned `position` column. `CreatePossessionCondition` places the new one at
  `max(position) + 1`. `UpdatePossessionConditionPosition` removes the condition from the ordered
  list, splices it back at `max(0, position - 1)` and renumbers 1..n in a transaction, so a position
  past the end puts it last, as `UpdateEntityTypePosition` does. `DestroyPossessionCondition`
  deletes and renumbers what remains.
- **Screen**: each row is an `x-sortable-row` inside `x-data="sortableRows"`, posting to the
  position route through its `x-position-form`, as the entity type list does. Each row also has
  "Move :name up" and "Move :name down" icon buttons (absent on the first and last row), posting the
  neighbouring position, and the moved row's button takes focus afterwards through the `moved` and
  `direction` flash, as `possession-types/_group.blade.php` does. The flash status "“:name” is moved
  to position :position." is announced by the status region.
- **API and tools**: `PUT possession-conditions/{possessionCondition}/position` and the tool
  `set-possession-condition-position`, as `SetEntityTypePosition`.

## R7. One screen, edited in place, deleted through `x-modal.warning`

- **Decision**: As 092 R5 and R7. `settings/account/possession-conditions`, one `index` view, a
  `_row.blade.php` that turns into its form (`x-data="{ editing: … }"`, reopened on an error in its
  bag), Alpine Ajax targeting `possession-conditions`. Delete is a ghost danger button labelled
  "Delete :name", opening `x-modal.warning` titled "Delete the condition “:name”?".

## R8. The navigation entry

- **Decision**: `possession-conditions` in the Possessions group of `x-settings.sidebar`, after
  Possession tags, labelled "Possession conditions", with the `star` icon. `place`, `tag` and `hash`
  are taken by the siblings; a star reads as a grade.

## R9. Journal, API, tools and documentation copy the possession tag ones

- **Journal**: `PossessionConditionCreation`, `PossessionConditionUpdate`,
  `PossessionConditionPositionUpdate`, `PossessionConditionDeletion`, `PossessionConditionsSeeded`.
- **API**: under `api/v1/possession-conditions`, ordered by position, paginated in memory like the
  tags. A `PossessionConditionResource`.
- **Tools**: six, with a `possessionCondition()` lookup in `MirrorsAnApiCapability`, groups
  "Possession conditions" and "Possession conditions, changing" in `McpDocsViewModel`.
- **Docs**: `resources/docs/api/29-possession-conditions.php`, a guide page
  `docs/portal/en/9-templates-and-customisation/15-possession-conditions.md`, a line in
  `docs/FEATURES.md`.

## R10. The "Condition" groups of the default possession types stay

- **Finding**: Several default possession types already carry a field group named "Condition"
  (Stamps: Perforated, Cancelled/mint; Watches: Box & papers; Video games: Complete in box). Those
  are details of one kind of thing, not its general state.
- **Decision**: Leave them. The glossary word for "condition" is the one those groups already use
  (French "État"), so the two read alike and mean the same family of thing. Whether the groups are
  renamed is for the feature that gives a possession its condition.

## R11. No policy, no flag

- As 092 R10: every rule is stated once, in the action; possession siblings ship without a flag.
