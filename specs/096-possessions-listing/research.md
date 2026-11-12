# Research: Possessions Listing

## R1. The selection is a route segment, not a query string

- Decision: `GET vaults/{vault}/possessions/locations/{location}`, named
  `possessions.locations.show`, on a new `PossessionLocationController@show`. "All locations" stays
  `possessions.index`.
- Rationale: code-style forbids query strings without reason; a bound `Location` resolves through
  `ResolvesModelInAccount`, so another account's location is not found without a check of our own.
- Rejected: `?location=`; Alpine switching in the page (no address, nothing to reload).

## R2. One read of the vault's possessions, filtered in memory

- Decision: the view model loads every possession of the vault once, with `possessionType` and
  `location`, and the tree once through `LocationTree::forAccount()`. Counts per node, the bar and
  both lists are filtered from those two collections by `LocationTree::descendantIds()`.
- Rationale: 500 rows and 50 locations is two queries; a count query per node is fifty.
- Rejected: `withCount` per location (locations are account wide, the count is per vault).

## R3. Money: the locale places the symbol, the user picks the separators

- Decision: a helper `PossessionValueFormatter` builds an intl `NumberFormatter` in currency style
  for the app locale, with no decimals, then sets its grouping and decimal symbols from the user's
  `NumberFormatEnum`, the fallback `FieldValueFormatter::numberFormat()` already uses.
- Rationale: principle V forbids hardcoding a currency format; the user's separator preference
  already governs every other number.
- Rejected: `Number::currency()` (ignores the user's separators); number plus ISO code.

## R4. Totals per currency

- Decision: a total is a list of amounts, one per currency present, ordered by amount, joined with
  `__(', ')`. A possession without value or currency adds nothing.
- Rejected: converting to one currency (no rates, and none wanted).

## R5. Colour of a location

- Decision: top level locations take `VaultColourEnum::cases()` in tree order, cycling; a nested
  location takes its top level colour. Possessions use their type's colour and icon.
- Rationale: locations carry no colour (principle VII); the design colours by top level place.

## R6. Bar parts

- Decision: for a location, one part per direct child holding at least one possession, plus "No
  specific spot" for those at the location itself. For "All locations", one part per top level
  location plus "No location". The bar shows only with two or more parts; its `aria-label` lists
  each part and count, so colour carries nothing alone.

## R7. Sidebar count

- Decision: `App\View\Components\Vault\Sidebar` adds one `count()` beside its existing
  `hasPossessionTypes` query.
- Rejected: a `possession_count` column like `contact_count` (a counter kept by four actions for a
  figure one cheap query gives).

## R8. API and MCP parity (principle X)

- Decision: `GET api/vaults/{vault}/possessions` and `list-possessions` accept `location` (a uuid of
  the account), matching that location and every location inside it. Unknown or foreign uuid: 422.
- Rationale: browsing by location is a capability; the API returns the location per row but could
  not narrow by a subtree.

## R9. What stays of the current screen

- Decision: the 095 empty state stays as is; the `@else` placeholder is replaced. A Lent badge uses
  `x-badge tone="neutral"`; the list uses `x-panel` rows, the tree the nav item markup of the
  settings screens. Nothing extracted: the tree appears in one file.
