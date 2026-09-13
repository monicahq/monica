# Research: Add a relationship

## A page, not a dialog

**Decision**: a page at `vaults/{vault}/contacts/{contact}/relationships/new`, in the standard shell.

**Rationale**: the mockup is `role="dialog"`, but every comparable creation form here is a page with a
breadcrumb and a sidebar (`share-links/form.blade.php`, `contacts/form.blade.php`,
`relationship-types/form.blade.php`). The card layout, copy and progression survive the move unchanged.

A dialog was rejected: it needs a host screen this feature is not otherwise touching, and
`components/modal/` holds confirm, warning and a small form, none of them a six-part progressive form.

## The entry point already exists

**Decision**: link the form from the contact's existing relationships tab, and add no `GET .../relationships`.

**Rationale**: `ContactViewModel::unbuilt()` (line 626) already renders that tab through the `contacts.show`
`{page?}` catch-all, saying "Who :name is to the other people in this vault, once relationships exist."
Replacing that sentence with a way in is the whole entry point, the catch-all already owns that URL, and
both new routes therefore sit above `routes/web.php:230`.

## The contact picker searches the server

**Decision**: a `GET` fragment re-rendered through Alpine Ajax `x-target`, querying
`Contact::scopeMatchingSearch()` (`app/Models/Contact.php:509`) scoped to the vault.

**Rationale**: SC-007 and the product spec both refuse loading a contact's whole graph into a screen, and
that reads naturally onto the picker. `matchingSearch` exists, folds accents per locale
(`LocaleCollation::foldForSearch`), and already serves the contacts index and the JSON API. The timezone
picker, which renders every option and filters in Alpine, is right for ~400 fixed zones and wrong for a vault
of thousands; a JSON endpoint was rejected because no controller under `App` returns JSON.

## The preview sentence reuses the settings screen's

**Decision**: the view model supplies a translated template and Alpine fills it, as
`RelationshipTypeFormViewModel::sentenceTemplate()` does with `__(':one is :two’s :role.')`, rendered into an
`aria-live="polite"` region at `relationship-types/form.blade.php:159-166`.

**Rationale**: same sentence, already translated in every locale, already accessible. A second way to say it
is the duplication principle XI exists to stop. A nameless other side has no name to substitute, so one
sibling string reads by role instead.

## The checkbox flips `is_partial`, and nothing else

**Decision**: unchecked creates a `Contact` with `is_partial = true`, checked an ordinary one. Both go through
`new CreateContact(...)->execute()`, which already takes `isPartial`.

**Rationale**: the mockup's wording ("they stay a name on this relationship, with no page of their own")
contradicted `spec/models/026`, which requires two real contacts and no free-text endpoint. The spec was
amended on 2026-09-13 to settle it this way, and `is_partial` already governs exactly this: it keeps a contact
out of the main lists (`Contact::scopeListed()`, `Account::contactCount()`) and out of nothing else.

**Rejected**: nullable contact ids plus a name column, which would put rows in the table that the family tree
and the Immediate family block cannot read.

## Making `relationshipCount()` honest is a behaviour change

**Decision**: replace the `return 0;` stub with a real count, in this feature rather than later.

**Rationale**: its comment says the answer is none only because the record does not exist. Six call sites read
it, and two rules turn on it: a type in use keeps its direction, and a type in use cannot be deleted. Leaving
it at `0` once rows exist lets somebody reverse or delete a type underneath the rows this feature writes,
which is principle VI.

**Consequence**: `tests/Unit/Models/RelationshipTypeTest.php:109` asserts the `0` and must be rewritten.
Deletes and direction changes that pass today start being refused once a type is used. This is the one place
this feature changes an existing screen.

## The parentage cycle check

**Decision**: walk ancestors from the proposed child through rows whose type has
`system_behavior = genealogy_parentage`, refusing if the proposed parent is reached. One query per
generation, never the whole vault.

**Rationale**: `RelationshipBehaviorEnum::GenealogyParentage` is the stable hook the product spec insists on,
so a custom type named "Parent" never enters the walk, and the table's
`(vault_id, contact_b_id, relationship_type_id, deleted_at)` index makes the upward walk cheap. A
transitive-closure table was rejected as a second mechanism for a rule that runs once per save.
