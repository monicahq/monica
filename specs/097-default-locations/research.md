# Research: Default Locations

The pattern is `CreateDefaultPossessionConditions` (093): an enum, an action, a job in the setup
chain, a backfill migration. Every decision below follows it unless it says otherwise.

## R1. The catalogue is `DefaultLocationEnum`

Cases `House`, `LivingRoom`, `Kitchen`, `Bedroom`, `Bathroom`, `StorageSpace`, backed by snake_case strings.
`label()` gives the English name, `emoji()` the emoji, `isInsideHouse()` is true for every case except
`House`. It implements `Translatable`, so `monica:localize` finds the six names without a `__()` call
written out in a view.

The label of `House` is "My house", not "House": the key "House" already belongs to the possession
type categories, where it means a detached house as opposed to a flat, and one key carries one
meaning.

- Rejected: an array in the action. `monica:localize` discovers `Translatable` enums, not arrays.
- Rejected: a `parent(): ?self` method. One level of nesting does not need a general shape.

## R2. The action writes the rows itself

`CreateDefaultLocations` creates `House` first, then the five rooms with `parent_id` set to it, through
`Location::query()->create()`, in one `DB::transaction()` (FR-003).

- Rejected: calling `CreateLocation` six times. It logs one journal entry per location (FR-006 wants
  one), and its sanitising and account check guard input the catalogue does not take.

## R3. The guard

`$this->user->account->locations()->exists()` returns `[]` before anything is written (FR-004). This
also makes a retried job and a second migration run add nothing (SC-002).

## R4. The language

`__($case->label(), [], $this->user->locale ?? config('app.locale'))`, as 093 does (FR-005).

## R5. The journal

`UserActionEnum::LocationsSeeded = 'locations_seeded'`, worded "Set up the locations the account
starts with", placed with the other location cases, dispatched on the `low` queue.

## R6. The job

`SeedLocations(user:)` calls the action, and goes last in `CreateAccount::seedTheAccount()`'s chain,
after `SeedPossessionConditions`.

## R7. The migration

`2026_10_26_000007_backfill_locations.php`, the shape of `backfill_possession_conditions`:
`User::query()->whereDoesntHave('account.locations')->chunkById(200, ...)` running the action.
A new file, never an edit of an existing one. It sorts after `create_locations_table` (2026_10_25).

## R8. The emojis pass `SingleEmoji`

🛋️ and 🛏️ are a pictograph followed by U+FE0F. `SingleEmoji` counts graphemes (`\X`), so each is one
emoji. The seeding skips the rule, so the enum test validates every `emoji()` against it, which keeps
a seeded location editable without its emoji being refused on save.

## R9. What else changes

- `specs/086-location-management/spec.md` line 119: "no location is created automatically, for an
  account" becomes a pointer to 097, as 089 did for 088.
- `docs/portal/en/9-templates-and-customisation/13-locations.md`: one sentence saying an account
  starts with a house and five rooms, which can be changed or deleted (FR-010).
- `docs/FEATURES.md`: one line.
- No API or MCP change: seeded locations are ordinary rows, reached by the endpoints and tools of 087.
- No screen change: the empty state of 086 stays for an account that deletes everything.
