# Contracts: Possessions Listing

No schema change. Decisions referenced as R1 to R9 are in [research.md](../research.md).

## Routes (web)

| Method | URI | Name | Controller |
| --- | --- | --- | --- |
| GET | `vaults/{vault}/possessions` | `possessions.index` | `PossessionController@index` (exists) |
| GET | `vaults/{vault}/possessions/locations/{location}` | `possessions.locations.show` | `PossessionLocationController@show` (new) |

`{location}` is `whereUuid`, bound, account scoped. Both build the same view, `app.vaults.possessions.index`.

## `PossessionsViewModel` (extended)

Constructor gains `?Location $location = null`. New public methods, all `once()`:

| Method | Returns |
| --- | --- |
| `summary()` | "15 possessions across 10 locations, 42,050 € declared." |
| `tree()` | `PossessionLocationTreeBlock` |
| `selection()` | `array{name, address, count, value}` |
| `parts()` | `list<array{name, count, colour}>`, empty below two parts (R6) |
| `partsLabel()` | the bar's text alternative |
| `featured()` | `list<Row>` in position order |
| `others()` | `list<Row>` by name |
| `othersTitle()` | "Everything else" or "Kept at Garage" |

`Row` is `array{name, type, icon, tint, place, value, description, isLent}`; `place` is the full path
under "All locations", the path below the selected location otherwise, "No specific spot" for one
kept at the selection itself, or "No location".

## `PossessionLocationTreeBlock` (new, next to the view model)

`nodes(): list<array{name, url, depth, icon, colour, count, isCurrent}>`, "All locations" first,
then `LocationTree::ordered()`; `manageUrl()` to `settings.locations.index`.

## `PossessionValueFormatter` (new helper, R3 and R4)

`format(?string $amount, ?string $currency): string` (a dash when either is null);
`total(iterable<Possession>): string` (one amount per currency, or a dash).

## Screen

- `<nav aria-label="Locations">`; the current node has `aria-current="page"`.
- Bar: `role="img"` with `aria-label` from `partsLabel()`; legend repeats names and counts in text.
- Star: `x-icon` with label "Featured". Lent: `x-badge tone="neutral"` reading "Lent".
- Empty list copy: "Nothing else is kept here. Featured possessions are listed above."

## API and MCP (R8)

- `GET api/vaults/{vault}/possessions?location={uuid}`: possessions at that location or inside it.
  A uuid that is not a location of the account answers 422 on `location`.
- `list-possessions` tool: optional `location`, same meaning, described in its schema.
- `resources/docs/api/27-possessions.php`: the parameter documented.
