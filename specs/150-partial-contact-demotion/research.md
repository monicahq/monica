# Research: making a full contact partial

Decisions for [plan.md](plan.md). Code references are in [analysis.md](analysis.md).

## R1. One route per surface, carrying the wanted state

**Decision**: the change of state is one `PUT vaults/{vault}/contacts/{contact}/partial` with a
required boolean `partial`, on the web (`contacts.partial.update`) and on the API
(`api.v1.contacts.partial.update`). It replaces the `POST .../promotion` route
(`contacts.promotion.create`) built earlier on this branch, which is not merged, so nothing outside
the branch depends on it. `ContactPromotionController` becomes `ContactPartialController`.

**Rationale**: the favourite mark already works this way on both surfaces
(`ContactFavouriteController::update`, field `favourite`), and the MCP tool `SetContactFavourite`
mirrors it with one boolean. A state with two values is set, not created and destroyed.

**Alternatives considered**: a second controller for the demotion beside the promotion one (two
routes, two tests, two MCP tools for one flag); `PATCH` on the contact itself (the API has no general
update of a contact, each property has its own route).

## R2. Two actions, not one

**Decision**: keep `PromotePartialContact` and add `DemoteFullContact`. The controllers pick one by
the boolean, as the favourite controller picks `FavouriteContact` or `UnfavouriteContact`.

**Rationale**: the two directions have different rules. Promotion asks the plan's ceiling under the
account lock; demotion asks the new policy ability and never the ceiling. One action branching on a
flag would hold two sets of rules behind one name, which `.claude/skills/actions` forbids (one user
action per class).

## R3. The rule about the account holder's contact is a policy ability

**Decision**: `ContactPolicy::demote(User $user, Contact $contact)` denies the contact representing
the account holder, with a sentence of its own. The edit screen asks `inspect('demote', ...)` to draw
the panel refused with its reason; the action asks `authorize('demote', ...)`.

**Rationale**: `.claude/rules/policies.md`: a policy exists when a rule is stated twice, once where the
screen decides whether to offer a control and once where the action refuses. That is the case here,
and `delete` on the same policy already refuses the same contact.

**Not in the policy**: "already partial" and "archived" are states found while the work runs, not
standing abilities; they are refusals of the action (`InvalidArgumentException`), as "already a full
contact" already is in `PromotePartialContact`.

## R4. The ceiling on every way back to full

**Decision**: `MergeContacts` asks `authorize('create', Contact::class)` under a lock on the account
row when both the kept and the absorbed contact are partial, since only then does the merge add a
counted contact (analysis, D1). The web and API merge controllers catch `AuthorizationException`
and answer as their other refusals do (back with the error on the form; 422).

`UndoContactMerge` can raise the count too, by restoring an absorbed full contact, but that is the
undo of something the person did, inside the plan at the time, and refusing an undo would trap data.
Left as is and recorded in analysis.

**Alternatives considered**: refusing to merge two partial contacts at all (needlessly strict: the
person may be below the ceiling).

## R5. The lock

**Decision**: `DemoteFullContact` does not lock. It only lowers the count, and the promotion and the
merge, which raise it, lock the account row before asking the policy. Two raises are therefore
ordered, and a lowering in between can only help them.

**Rationale**: the race that matters is two raises at 29 of 30 (analysis, S5), and both raising paths
lock. Locking the lowering path too would add contention for no case it protects.

## R6. Where the warning about reachability comes from

**Decision**: the edit page's view model reports whether any relationship reaches the contact
(`Relationship` where `contact_a_id` or `contact_b_id` is the contact), and the confirmation adds one
sentence when none does. The action does not check it: the answer was to allow and warn.

## R7. The panel and the confirmation

**Decision**: the panel is an `x-panel` above `x-delete-section` on
`resources/views/app/vaults/contacts/edit.blade.php`, holding the three lines of design 2a and a
secondary button. The confirmation is `x-modal.form` (the same one the promotion band uses), with
`method="put"`, a hidden `partial=0`, and the consequences in its body. Refused for the account
holder's contact, the button is drawn with `aria-disabled="true"` and `aria-describedby` on the reason,
as the delete section beside it does.

**Copy** (English source, final wording may move in review, but only towards what is true):

- Panel title: "Keep :name as a partial contact"
- Panel lead: "For somebody you only need to know through another person, like a colleague's partner or a friend's child."
- Lines: ":name leaves your list of people and no longer counts towards your plan." / "Everything written about :name stays, and so do their relationships." / "You can make :name a full contact again at any time."
- Button and confirm: "Make a partial contact"
- Dialog title: "Make :name a partial contact?"
- Dialog body: ":name leaves the list of people and the address books synchronised to your phones, and their birthday leaves your birthday calendar. Everything written about them stays, and they can still be found in search."
- Extra sentence when no relationship reaches them: ":name has no relationship, so you will find them only under Names only in the list of people, or in search."
- Flash: ":name is a partial contact." / "They are under Names only in the list of people."
- Refusal for the account holder: "This is the contact that represents you, and it stays a full contact."
- Refusal when already partial: ":name is already a partial contact."
- Journal: "Made :name a partial contact"

Design 2a says "his relationships" and "make him"; the copy uses the name and "their", since the
product does not know the contact's pronouns.

## R8. What the promotion band says, corrected

**Decision**: the band's copy written earlier on this branch stays; it makes no false claim. The
older sentences "until they are more than a first name" (`ContactController.php:188`,
`ContactsViewModel.php:268`) and the MCP description of `is_partial` (`app/Mcp/Tools/CreateContact.php:52`)
are corrected in this feature, since they describe the partial state wrongly and the feature makes
the state something a person sets by hand.

## R9. CardDAV needs no change

**Decision**: nothing changes in `app/Http/Dav`. The address book and calendar already leave partial
contacts out and report them in the removed set of the sync changes when `updated_at` moves
(`CollectionChanges.php:163`). Saving the flag moves `updated_at`. One regression test proves a
demoted contact lands in the removed set.

UNCONFIRMED: client behaviour when a removed card carries unsent local edits. Not testable here; noted
in analysis S1.
