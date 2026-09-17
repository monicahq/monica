# Research: Activity type management

## D1. The builder for a type's custom fields is written again, not generalised

**Decision**: new thin controllers and views under `ActivityTypes`, calling the engine actions
unchanged (`AddSectionToStructureVersion`, `CreateSection`, `MakeSectionVersionEditable`,
`CreateField`, `UpdateField`, `DestroyField`, `PublishStructureVersion`). Only
`StructureVersionSection::isReachableThrough()` is widened, from `Template $template` to any owner
model, because both callers ask it the same question.

**Rationale**: the engine is already generic where it matters. `PublishStructureVersion` names the
activity type in its log and its change note, `CreateActivityType` already builds the structure, its
first version and its page. What is template-shaped is the controller layer and the views, and a
type's surface is deliberately smaller: one page, no page management, no version history, no copy,
export, import or share. Writing the smaller screen costs less than making
`TemplateBuilderViewModel` (403 lines) serve two owners, and principle II prefers the repetition.

**Alternatives**: one owner-agnostic builder on `custom-fields/{structure}`, rejected because it
rewrites a shipped screen for a caller needing less than half of it and puts the word structure in
the address bar; parameterising the section and field partials by route name, rejected because a
partial taking its routes as arguments reads worse than two partials.

## D2. The selected category is a path segment, not a query string

**Decision**: `settings/account/activity-types` lists every type, and
`settings/account/activity-types/categories/{activityCategory}` lists one category. Each rail entry
is a link carrying `x-target`, so the panel swaps in place and the URL stays shareable.

**Rationale**: `.claude/rules/code-style.md` asks for no query string without a good reason. A link
per category is also what makes the rail reachable with a keyboard and announceable, which
`aria-current` then marks (principle IX).

**Alternatives**: `?category=`, rejected by the rule; client-side selection as the design file does
it, rejected because the selection would not survive a reload and a screen reader would be told
nothing changed.

## D3. Asking for fields on the add screen saves the type first

**Decision**: the add screen's fields panel submits the form with a named value, and the controller
creates the type and redirects to its custom fields instead of the list.

**Rationale**: a type owns its structure, so there is nothing to put a field on until it exists.
`spec/models/035` settles the wording: "Saving the type offers the field builder". One form and one
submit keeps the half-filled name and category rather than discarding them.

**Alternatives**: creating the type the moment the button is pressed, rejected because a name typed
but not yet reviewed would be saved behind the user's back; disabling the panel until the type is
saved, rejected because the design shows it on both screens.

## D4. Two policies, for the two refusals a screen and an action both state

**Decision**: `ActivityTypePolicy::delete()` and `ActivityCategoryPolicy::delete()`, each returning
`Response::deny()` with the wording the reader already sees. The screens ask with `inspect()` and
render the refused control disabled with its reason; the actions ask with `authorize()`.

**Rationale**: `.claude/rules/policies.md` says a policy exists to stop a rule being written twice,
and each of these is written twice here. `RelationshipTypePolicy` is the same shape. The
`CannotDeleteASuppliedActivityType`, `CannotDeleteASuppliedActivityCategory` and
`CannotDeleteAnActivityCategoryInUse` exceptions are deleted, as 033 deleted the ones it replaced.

**Alternatives**: keeping the exceptions and letting the screens duplicate the conditions, rejected
by the rule; a policy for activation or for reordering, rejected because neither is ever refused.

## D5. No migration

**Decision**: nothing in the schema changes. Every column the screens need already exists on
`activity_categories` and `activity_types`, the structure is created with the type, and
`activity_types.colour` is a 7 character hex, which is exactly what `VaultColourEnum` holds.

**Rationale**: the tables were written against `spec/models/028` and `035`, the same specifications
these screens are built from.

## D6. Icons and colours come from the catalogue sections already use

**Decision**: the type form reuses `x-section-mark-picker`, `Section::ICONS` and the colour list the
mark picker already offers, rather than the sixteen icons and eight hex values the design file names.

**Rationale**: `.claude/rules/components.md` forbids a second of anything, the stored shape is the
same (an icon name and a colour), and the design's palette is the same eight hues drawn from the
same tokens. A second catalogue would drift the first time either is extended.

## D7. The field editor lives on the type form, and one save writes the lot

**Decision**: the second screen (`custom-fields`) and the third (the field type catalogue, for an
activity type) are gone. The type form carries the whole editor: groups, the fields in no group, each
field's kind, its settings, its choices and its help text. One submit carries name, category, mark,
the whole layout and availability, and `ReplaceActivityTypeFields` writes them into one draft and
publishes it. This supersedes D1 and D3.

**Rationale**: the design file is one screen and the reader was reaching a type's fields through two
more. Three screens for one idea is what made the old flow invent a group called "Details" the moment
somebody added their first field.

**Alternatives**: saving each row as it is edited, rejected because the design shows no per-row save
and the bottom Save would then mean something different for each half of the screen; keeping the
publish step as its own button, rejected because nothing on the design offers it and a save that does
not take effect is a trap.

## D8. A field may stand outside a group, which is a placement that hides its name

**Decision**: `structure_version_sections.shows_title`. The fields nobody grouped are one section
placed first with its name hidden.

**Rationale**: a field belongs to a section version and to nothing else, so "no group" cannot be the
absence of a section. It has to be a section the reader is not shown, and that is a property of the
placement, beside `title_override` and `is_collapsed_by_default`.

**Alternatives**: an empty `title_override` as a sentinel, rejected because an empty string and "no
name given" would then mean two different things in one column.

## D9. Inline choices are kept in a value list nobody shares

**Decision**: `value_lists.is_shared`. A choice field's choices are written into a list of its own
with `is_shared` false, which the value lists screen and every picker filter out.

**Rationale**: the design writes choices under the field, and the application already holds a set of
choices one way. Two ways to hold the same thing is what the naming rule exists to stop. Without the
column, a list named after one field would stand on the value lists screen beside Countries.

**Alternatives**: choices in the field's `options`, rejected as a second way to say the same thing;
a value list picker in the row, rejected because it asks somebody to leave the screen and make a list
before they can finish a field.

## D10. Templates keep the catalogue screen

**Decision**: only activity types pick a field kind from a row. `FieldController` and
`resources/views/app/settings/account/field-type.blade.php` are untouched.

**Rationale**: the design covers the activity type screen. The template builder has pages, versions
and section reuse that the design never addressed, and rewriting it here would be a second feature.

**Follow-up**: the two builders now pick a field kind two different ways. Settle which one wins before
either grows again.

## D11. The revised design: availability in the header, icon and colour as two pickers

**Decision**: the form's top third follows the second revision of `Activity types.dc.html`.
Availability is a switch beside the title rather than a section of its own; "How it looks" is gone and
its icon and colour sit inside "Name and category" as two popovers; "Extra fields" is "Fields", its
heading shares a row with "Add a group", and the sentence under it is deleted. This supersedes the
mark picker half of D6 and the expanded layout D7 introduced.

**Rationale**: the design asked for it, and each move reduces the screen: one fewer section, one
fewer band of thirty-odd radio buttons, one fewer paragraph. The switch reuses the markup
`app/settings/account/notifications/_channel.blade.php` already ships, so there is one switch in the
product rather than two.

**Alternatives**: keeping the expanded radiogroups, rejected because the design replaced them and
nothing else called that layout; a third `x-mark-picker` layout, rejected because `expanded` had one
caller and this replaces it, leaving the component with two.

**Kept against the design**: the "No icon" button and the "Neutral" colour stay in their popovers.
Both columns are nullable here, and dropping them would leave a mark that cannot be taken off.

**Kept against the design, second**: the availability sentence reaches a screen reader through
`aria-describedby` as well as the `title` the design uses. A native tooltip is the only explanation
left of what retiring a type does, and it reaches neither a screen reader nor a keyboard on its own.
