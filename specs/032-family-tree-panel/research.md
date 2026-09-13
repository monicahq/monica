# Research: Family tree panel

## D1. The drawing is CSS tiers with border connectors, not coordinates and not SVG

**Decision**: three flex tiers stacked in a grid, all centred on one vertical line, with the joins
drawn as `before:`/`after:` pseudo-element borders on each node's cell. The first cell in a tier
draws the right half of the bar, the last the left half, the middle ones the whole width, and a lone
cell draws none. Tailwind's `first:`, `last:` and `only:` variants say this directly.

**Rationale**: FR-015 caps no tier, so the layout has to hold any number of people. A cell that
knows only whether it is first, last or alone needs no arithmetic and no geometry in the view model.

**Alternatives**: absolutely positioned nodes at computed pixels, as the mock draws them, rejected
because the coordinates would be computed per contact, putting geometry in PHP. Inline SVG, rejected
because every node carries an avatar and `foreignObject` costs more than the lines are worth.

## D2. Every child hangs from the contact, never from the couple

**Decision**: the parents' bar drops onto the contact, the children's bar rises to the contact, and
a short horizontal line joins the contact to each current partner. Three joins, one anchor.
**This amends the specification**, which asked for children to hang from the pair when there is
exactly one partner, the way the mock draws it. FR-014 now records the correction.

**Rationale**: the records hold "A is a parent of B" and nothing else. They do not hold that the
contact's current partner is also the child's parent. Hanging children from the pair asserts a
co-parentage nobody wrote down, and reads as wrong on a blended family, which a personal directory
is full of. The specification already said so in its own edge cases, so the two could not both
stand. This is the defect the constitution's Governance asks be fixed rather than left standing.

**Alternatives**: ask, per child, whether the partner is also recorded as their parent, and anchor
each accordingly. Correct, but it costs another query, splits one tier across two anchor points and
doubles the connector rules, for a distinction nobody reads at 384px.

## D3. Anchoring on the contact is what centres the drawing

**Decision**: the middle tier is a three-column grid, `1fr auto 1fr`, the contact in the `auto`
column and the partners in the right one. The two `1fr` columns stay equal, so the contact sits on
the centre line whether or not a partner exists and all three joins are vertical at `left-1/2`. The
mock aims the parent line at Judy rather than at the midpoint of Judy and Jack, which is right, and
making the contact the centre gets that for free.

## D4. Fixed viewport, scrolled in both directions, reachable by keyboard

**Decision**: the panel body is a fixed-height box with `overflow-auto`, the drawing inside sized to
its content. The box takes `tabindex="0"`, `role="group"` and a name, so a scrollable region stays
operable without a pointer even when the only thing in a tier is a name-only node, which is not
focusable.

**Rationale**: FR-016 and FR-017 together. A scroll container holding non-focusable content fails
WCAG 2.1.1 unless the container itself takes focus, which principle IX makes a delivery condition.

## D5. Parentage is selected by a scope on the model, beside the two that exist

**Decision**: add `Contact::scopeParentsOf()` next to `scopeChildrenOf()` and
`scopeCurrentPartnersOf()`, reading membership off `RelationshipBehaviorEnum::GenealogyParentage`
through the same `typesBehavingAs()` subquery, and consulting no status.

**Rationale**: `scopeChildrenOf()`'s own docblock argues the case, that the selection belongs on the
model because the API answers the same question and a rule kept in a view model is one the two
surfaces sooner or later disagree about. Its inverse belongs there too, and it satisfies FR-008.

## D6. Three questions of the records, whatever the family holds

**Decision**: one query per tier, each eager-loading the avatar. Nothing is asked per person, and no
age is read: the mock names people rather than dating them, so the `ContactAges` lookup the other
blocks share is not needed here. FR-020 and SC-004 ask for this because the reader controls how many
people are drawn, so a question per person would degrade quietly on exactly the contacts the feature
is for.

## D7. No API endpoint ships with this, and why

The panel records nothing. It is a second reading of relationships that already exist, so it adds no
capability for principle X to cover. Relationships have no endpoints at all today, a gap inherited
from features 029 to 031 and not one this feature widens. The selection logic sits on the model
rather than in the view model (D5), so the endpoint, when it comes, is transport and nothing more.

## D8. Two further departures from the mock

It draws one parent and one partner; both tiers hold as many as are recorded. Its footer carries an
"Open" control; it is dropped, per the specification's assumptions.
