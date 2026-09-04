# Research: Confirmation when a contact is marked a favourite

## Decision 1: reverse the recorded decision not to flash

**Decision**: Flash a status message on both branches of `ContactFavouriteController::update()`.

**Rationale**: The note in that controller argues the star is the answer, so a message adds nothing.
That holds only while the user is looking at the star. The star is 26 pixels in the corner of an
avatar, it changes tint and shape but does not move, and with the partial update it changes without
the page doing anything else. Every other save in the product answers in the same corner, so the one
that stays silent reads as the one that did not land. The user asked for the message, which settles
it either way.

**Alternatives considered**: Keep it silent (the status quo the user is overriding). Animate the star
harder instead (adds motion where the constitution asks for less, and says nothing to a screen reader).

## Decision 2: confirm both directions

**Decision**: Marking and removing both flash.

**Rationale**: One control with two directions. A user who saw a message the first time reads its
absence the second time as a failure.

**Alternatives considered**: Marking only, which is the literal request and stays available if the
user wants it narrowed.

## Decision 3: no new mechanism is needed

**Decision**: Ordinary `->with('status', ...)` on the redirects that already exist.

**Rationale**: Three of the requirements fall out of what is already built, rather than needing work.

| Requirement | What already satisfies it |
| --- | --- |
| FR-004, appears with and without scripting | The toaster is `x-sync`, so Alpine Ajax merges `#notifications` out of every response even though the star's form targets `#contact-favourite`. Without scripting the same redirect is an ordinary page load. |
| FR-006, one at a time | The toaster is `x-merge="replace"`, so a second answer rebuilds the region instead of appending to it. |
| FR-005 and FR-009, self-dismissing and not repeated | The component's own timer, and the fact that a flash is read once. |

**Alternatives considered**: A dedicated Alpine event dispatched from the star (a second notification
path for one screen, bypassing the no-scripting case).

## Decision 4: the copy

**Decision**:

| Direction | `status` | `status_description` |
| --- | --- | --- |
| Marked | `Marked as a favourite.` | `They sit at the top of the list.` |
| Removed | `No longer a favourite.` | `They go back in with everybody else.` |

**Rationale**: Each names the resulting state rather than the act, which is what keeps it true when a
stale page confirms a state that was already stored. The two cannot be mistaken for each other, which
is FR-003. The register matches the rest of the product ("Your time zone is saved.", "The value is
offered again."). No `:count`, so plain `__()` is correct and `trans_choice()` is not owed.

**Alternatives considered**: Naming the contact (`:name is a favourite.`), redundant on that person's
own page and one more string to translate around a name whose order varies by culture.

## Decision 5: what the change has to correct on its way through

Two tests assert the behaviour being reversed and are rewritten rather than deleted:
`it_says_nothing_the_star_has_not_already_said` and the `assertSessionMissing('status')` inside
`it_destroys_nothing_when_it_clears_the_mark`. The controller docblock and the comment in
`resources/views/app/vaults/contacts/show.blade.php` both state the old rule and become wrong.

## Open questions

None. The one assumption worth confirming, whether removal is confirmed too, is Decision 2 and is
recorded in the spec.
