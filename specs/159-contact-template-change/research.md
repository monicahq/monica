# Research: Change the template of a contact

Each decision names what was chosen, why, and what was rejected. Line numbers are those of `main` on
2026-11-25.

## R1. The API: two new endpoints, the version endpoint untouched

**Decision**: `PUT /vaults/{vault}/contacts/{contact}/template` changes the template, with `template`
(the template's id) and an optional `structure_version_id`. `GET /vaults/{vault}/contacts/{contact}/template-preview?template={id}`
previews it. `PUT .../template-version` keeps its exact behaviour and its documentation.

**Rationale**: the version endpoint is documented as "deliberately narrow" and answers 404 for a version
of another template, because there it is an address for something not on offer. Widening it would change
a documented contract (FR-011) and mix two decisions under one name. A template is what the reader picks,
so the new endpoint takes a template, not a version. The preview takes the template as a query parameter
because the web screen asks it the same way, from the links of its menu (R2), and the API mirrors that shape.

**Alternatives rejected**: widening `template-version` (breaks a documented 404); a `dry_run` flag on the
`PUT` (the MCP preview tool could not be declared as only reading); a path parameter for the preview (two shapes
for one question).

## R2. The screen, as the design `Edit Contact.dc.html` draws it

**Decision**: Edit contact gains a third panel, "Template", after "Change the name" and before the delete
section, with the `layers` icon and the subtitle "Decides which sections and fields :name’s page shows."
It holds:

- the label "Template used for :name", followed by the help bubble (title "What changing the template
  means", the design's two sentences, `guide="contacts.change-template"`);
- a picker shaped like a control, showing the current template and, when it has several, its version. It
  opens a menu grouped by template. A template with one published version is one line; a template with
  several shows its name and "N versions", then one line per published version, the latest marked
  "Latest" and the others by their publication month. The current version is checked
  (`menuitemradio`, `aria-checked`);
- under it, "Changing a template will have some consequences." and a "Read more" disclosure
  (`aria-expanded`, `aria-controls`) unfolding the five consequences agreed on 2026-11-25, with their
  icons (`arrow-right`, `layers`, `reminder`, `profile`, `clock`);
- once a line is picked, a confirmation (second version of the design, 2026-11-25): a `section` titled
  "Switch :name to the :template template?" (with "(version :number)" when the template has several), a
  sentence, "No data will be lost." with a check mark, the list of fields moving to Unused answers (field
  on the left, its section with a folder mark on the right) and its note, then a footer with "You can
  switch back to your previous template at any time.", "Cancel" and "Change template". While a change is
  pending, the "Read more" line is not shown.

Each menu line is a link to `contacts.edit?template={id}&version={id}`, carrying `x-target` of the
confirmation block. With Alpine Ajax only that block is updated (`x-merge="update"`) and its title takes the focus (`x-autofocus`), which is what a screen reader announces;
without JavaScript the link reloads the screen with the block filled. "Use this template" submits a `PUT`
to `contacts.template.update`; Cancel links back to `contacts.edit`. Picking the current version shows no
block, as in the design.

The page header loses its "Current template" link to the builder: the panel now names the template, and
the design draws the header with its title and description only.

**Rationale**: the menu is the design's. Links make it work without JavaScript and keep the preview
computed only for the line picked (FR-004). The menu's trigger, panel, Escape and outside click are the
contract of `x-actions-menu`; the picker reuses that component, adding a `control` look for its trigger if
the existing variants cannot draw it, rather than writing a second menu by hand
(`.claude/rules/components.md`).

**Deviations from the design**:

- The design names the place answers go "Other information". No such section exists: the product, its
  help centre and its 31 translations call it Unused answers. The screen keeps Unused answers, one word
  for one meaning.
- The design lists only the fields moving away. FR-004 also requires the answers coming back and the
  reminders that stop or run again, so they are listed below it in the same row style, each list shown
  only when it has something in it.

**Versions in the picker**: the design lists versions, so the picker also moves a contact between
versions of its own template. That goes through the same `contacts.template.update` with the same
template, which `ChangeContactTemplate` accepts. The notice on the contact page and the version history
keep their own route, unchanged.

**Alternatives rejected**: a native `select` with a submit button (not the design, and it cannot show the
version groups); a preview for every line computed when the screen opens (cost grows with the number of
versions).

## R3. The action: `ChangeContactTemplate` in front of `MoveRecordToStructureVersion`

**Decision**: a new action `App\Actions\ChangeContactTemplate(user, contact, template, ?structureVersion)`
checks what is specific to choosing a template, then delegates the move to `MoveRecordToStructureVersion`:

- the contact and the template belong to the user's account (`ModelNotFoundException` otherwise);
- the template describes contacts, is active, and has a published version;
- a named version belongs to that template and is published (any published version, not only the
  latest, since the picker and the version history offer older ones).

A refusal is an `InvalidArgumentException` carrying the translated sentence; both controllers turn it into
a form error or a 422 with that sentence, the same in the three surfaces (SC-005). The web and API
controllers validate shape only (`template` required, a string; `structure_version_id` an integer).

**Rationale**: principle I. `MoveRecordToStructureVersion` stays the one place a record moves, for
contacts and entities alike. It already refuses a draft, another account and another kind of record.

**Alternatives rejected**: putting the template checks in `MoveRecordToStructureVersion` (entities move
through it and have their own offer rules); a policy (the rule is written once, in the action; the screen
only lists offered templates with a query, which is not a second statement of a refusal, per
`.claude/rules/policies.md`).

## R4. Moving onto the current version does nothing, before the snapshot

**Decision**: `MoveRecordToStructureVersion::execute()` returns the record unchanged, before the
transaction, when the record is already on the target version. No snapshot, no log, no reindexing.

**Rationale**: FR-008 and SC-009. Today the snapshot is taken first and `move()` then finds nothing to do,
leaving an empty version history entry. A retried bulk job depends on this (FR-021).

## R5. One matching rule, split by target version

**Decision**: the matching in `followTheFieldsIntoTheNewVersion()` (lines 130 to 220) moves into
`App\Helpers\FieldCorrespondence`. It is built once for a target version (the two indexes: same section,
key and type; same parent, key and type) and resolves a contact's held fields against it with
`counterparts(Collection $heldFields, array $filledFieldIds): array<int, Field>`, a pure computation.
The action uses the result to write; the preview (R6) uses it to list.

**Rationale**: FR-004a and FR-019. The indexes depend only on the target version, so many contacts moving
to the same version share them; only the resolution is per contact. The existing 22 tests of
`MoveRecordToStructureVersionTest` prove the extraction changed nothing.

**Alternatives rejected**: previewing by running the move in a transaction and rolling back. The move
calls `DetachFile`, `DeactivateReminders` and `ReactivateReminders`, which dispatch `LogUserAction` and
touch files, and a rollback takes none of that back.

## R6. The preview: `App\Helpers\TemplateChangePreview`

**Decision**: built from a contact, a target version and whether the reader reaches sensitive fields. It
returns:

- `isChange`: false when the contact is already on that version;
- `setAside`: answers that would leave, each `{field_id, label, section}`;
- `broughtBack`: set aside answers that would come back, same shape;
- `remindersStopped`, `remindersRestarted`: `{id, label}`;
- `hiddenCount`: answers counted but not named (R10).

The web view model and the API controller both read it. Its lists are keyed by field id, so a later bulk
preview sums them per field (FR-022) without another rule.

**Rationale**: a view model may not hold the rule and the API controller may not depend on a view model.
`app/Helpers` already holds classes of this kind (`OwnedFields`, `NameFiling`).

## R7. Writing to the log is the caller's choice

**Decision**: `MoveRecordToStructureVersion`, `CreateRecordSnapshot`, `DeactivateReminders` and
`ReactivateReminders` gain `bool $writesToLog = true`, passed down by the move. Every current caller keeps
the default, so nothing a reader sees changes.

**Rationale**: FR-020. A move writes at least two log entries plus one per reminder touched; a change of
thousands of contacts must write one. The parameter has no caller passing `false` until the bulk feature,
which is the cost of preparing now; each of the four actions gets one test proving it writes nothing when
told not to.

**Alternatives rejected**: deferring FR-020 to the bulk feature (agreed against on 2026-11-25); a global
switch on `LogUserAction` (hidden state, would silence unrelated entries in the same request).

## R8. A log entry that says "template", not "version"

**Decision**: a new `UserActionEnum::RecordMovedToTemplate`, worded "Moved :name onto the template
:template". `MoveRecordToStructureVersion` writes it when the structure changes and keeps
`RecordMovedToTemplateVersion` otherwise.

**Rationale**: "Moved Dwight onto version 1 of its template" is false when the template changed.

## R9. Reverting across templates from the version history

**Decision**: `ContactViewModel::canRevertTo()` accepts a version of another structure when it is
published and its template is active. Such an entry posts to `contacts.template.update` with the template
and `structure_version_id`; an entry of the same template keeps posting to `contacts.templateVersion.update`.
`whyNoRevertTo()` gains "This template is turned off, so nothing can be put back on it." and keeps the
retired version sentence. The sentence "This version belongs to another template, so it cannot be reverted
to here." disappears.

**Rationale**: principle VI and story 3. The move brings set aside answers back by itself
(`bringBackWhatItCarriesAgain()`), so the round trip restores the page (SC-002).

## R10. Sensitive fields in the preview

**Decision**: on the web the preview names every field: a signed in reader can reveal sensitive values,
and only labels are shown. Through the API and the MCP server, an answer in a sensitive field is named
only for a key holding the `sensitive-fields` scope; otherwise it is counted in `hidden_count`.

**Rationale**: FR-004b. A label alone tells that this contact holds an answer in that field, which is the
information the scope protects. The check copies the private `reachesSensitiveFields()` of the API
controllers, as they all do.

## R11. Documentation

**Decision**:

- Help centre: a new page `contacts.change-template`, `docs/portal/en/3-people/7-change-the-template.md`,
  written with the `writer` skill. Corrected: `3-change-what-you-know.md` (link from "Answers to questions
  no longer asked"), `9-templates-and-customisation/4-change-a-template.md` ("Move those contacts to
  another template first" links to the new page), and the version history page if it says reverting to
  another template is impossible.
- The help bubble on Edit contact: `guide="contacts.change-template"`.
- API: two sections in `resources/docs/api/06-contacts.php` (`contacts-template-preview`,
  `contacts-template-update`), a release in `resources/docs/api-changelog/2026-11-25.php`,
  `ApiDocsViewModel::LAST_GENERATED` moved, the section count and `answer()` arms in the docs tests.
- MCP: `preview-contact-template-change` and `change-contact-template` added to the `Contacts` family in
  `McpDocsViewModel::FAMILIES`.
- What's new: one entry, with the `changelog` skill.

## R12. Out of scope, stated so nobody builds it

Entities (animals and other kinds of record), any bulk screen, queue, progress or bulk log entry, and
changing the matching rule itself.
