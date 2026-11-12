# Research: Possession Locations

## R1 Storage
**Decision**: a `possession_location_stays` table carrying `account_id`, `vault_id`, `possession_id`
and `location_id`, model `PossessionLocationStay`, `ResolvesModelInVault`. Shapes in
[data-model.md](data-model.md).
**Rationale**: as 116 R1. "Stay" is the design's word ("Every stay").

## R2 The current location stays a column
**Decision**: `possessions.current_location_id` stays, and every action that opens, closes, corrects
or deletes the open stay writes it in the same transaction (FR-003).
**Rationale**: the location tree, the list filter, `PossessionResource` and `ListPossessions` read it.
**Rejected**: dropping the column and joining on the open stay, which rewrites all four.

## R3 Actions
**Decision**: `MovePossession` (location, moved on, reason, note), `UpdatePossessionLocationStay`
and `DestroyPossessionLocationStay`. Each locks the possession row first, so two moves cannot both
see "no open stay".
- `MovePossession` refuses the open stay's location on `location`, and a day before the open stay's
  arrival on `moved_on`, with `ValidationException`; closes the open stay on that day; opens the new
  one; writes `current_location_id`.
- `UpdatePossessionLocationStay` refuses a missing `left_on` on a past stay and a `left_on` before
  `arrived_on`; never sets `left_on` on the open stay; writes `current_location_id` when the open
  stay's location changes. Neighbours are not touched (spec US3).
- `DestroyPossessionLocationStay` clears `current_location_id` when it deletes the open stay.

## R4 The possession forms
**Decision**: `CreatePossession` with a location writes one open stay, arriving today, without a
second journal line. `UpdatePossession` calls `MovePossession` dated today on a change and closes
the open stay today when cleared, as `ResetPassword` calls another action. Today is the user's.

## R5 Existing possessions
**Decision**: the new migration inserts one open stay, `arrived_on` null, for each possession with a
`current_location_id` (spec Edge Cases).

## R6 Order
**Decision**: `Possession::locationStays()` is a `HasMany` ordered by `arrived_on is null` descending
(unknown first), then `arrived_on`, then `id`. The screen reverses it for "newest first"; the API and
the assistant read it oldest first, as 116 R5. Valid on SQLite, MySQL and PostgreSQL.

## R7 Deleting a location
**Decision**: `LocationPolicy::delete()` also counts the stays at the location and denies with
`trans_choice(':count stay of a possession is here. Correct or delete it first.|:count stays of
possessions are here. Correct or delete them first.', $count)` (FR-010). The settings screen, the
API and `DeleteLocation` already ask this policy, so all three refuse with no change of their own.
`location_id` cascades on delete, as `parent_id` does, so deleting the account still works; the
policy is what refuses. **Rejected**: `restrictOnDelete()`, which can break the account cascade.

## R8 Derived figures
**Decision**: computed by `PossessionLocationsViewModel`: the current stay, lengths, gap lines, the
"stays in locations" line and the move preview. A length is days under 31, then years and months, as
the design's `spanOf`. Every counted phrase goes through `trans_choice()`. Paths and addresses come
from `LocationTree::path()` and `addressHolder()`.

## R9 Screen behaviour
**Decision**: as 116 R9. One GET renders the section; the move form, the help and the delete warning
are Alpine state; "Correct" is its own GET rendering the page with the filled form. The move preview
is computed in Alpine from the open stay the view model passes, since it changes as the fields change.
Writes redirect back with a flash. "Manage locations" leads to `settings.locations.index`.

## R10 Sections list
**Decision**: Locations keeps its place in `PossessionViewModel::sections()`, gains URL and count.

## R11 API and assistant
**Decision**: as 116 R11, under `possessions/{possession}/location-stays`. See [contracts/api.md](contracts/api.md).

## R12 Journal
**Decision**: `UserActionEnum` gains `PossessionMove` ("Moved the possession :name to :location"),
`PossessionLocationStayUpdate` and `PossessionLocationStayDeletion`, naming the possession.

## R13 Constitution and models
**Decision**: MINOR amendment (2.19.0): Possession location stay joins principle VII (twenty five
concepts), and the Possession sentence says it holds its stays, the open one being where it sits.
`spec/models/062` follows; `spec/models/076-possession-location-stay-in-progress.md` is new.

## R14 Documentation
**Decision**: portal page `22-possession-locations.md` (the help mark points at it), a line in
`16-possessions.md` and in the locations page saying why a location may refuse deletion, API
reference `38-possession-location-stays.php`, an entry in `docs/FEATURES.md`.
