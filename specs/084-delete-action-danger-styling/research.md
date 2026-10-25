# Research: Delete actions drawn as danger

## R1. One component for the deletion section

**Decision**: a new anonymous component `x-delete-section` draws a deletion section: `x-panel`
with `tone="danger"`, its title and icon, a description slot, and a `trigger` slot for the button.

**Rationale**: the same section stands in six files (vault, contact, template, entity type,
relationship type, life event type), which is past the three file bar in
`.claude/rules/components.md`. The drift this feature fixes is exactly what that bar exists to stop.

**Alternatives**: write `x-panel tone="danger"` in each file (six copies of the gutter and the row
layout, the state that produced four looks); fold the section into `x-delete-action` (rows use that
component too, and they must not get a box).

## R2. The trigger stays at each call site

**Decision**: `x-delete-section` takes the trigger as a slot. Entity, relationship and life event
types pass `x-delete-action variant="danger"`; vault and contact keep their `x-modal.warning` with
an `x-button.danger` trigger, because their warning cards carry their own copy.

**Rationale**: a component cannot hand a slot to its parent (`components.md`), and the vault card has
conditional paragraphs and its own "cannot be undone" sentence, which `x-delete-action` would repeat.

## R3. The template, entity type and relationship type sections move out of the form's foot

**Decision**: their delete button leaves the bar it shares with Cancel and Save and becomes an
`x-delete-section` below the form, as the life event type screen already does.

**Rationale**: FR-001 asks for a box, and a red box in a bar of three buttons would push Save aside.

## R4. The refused state

**Decision**: the refused button becomes `x-button.danger` with `aria-disabled="true"` and its
`aria-describedby`, inside the same red section. The button component already dims `aria-disabled`.

**Applies to**: contact, entity type, relationship type, life event type.

## R5. Red text comes from `x-button`, through a `tone` prop

**Decision**: `x-button` takes `tone="danger"`, which swaps the text colour its `secondary`, `bar`
and `ghost` variants name for `text-error`. `x-delete-action` passes it instead of adding a class.

**Rationale**: a `text-error` added from outside names the colour the variant already names, and the
later utility wins: `text-ink` (byte 54800) and `hover:text-ink` (77687) beat `text-error` (54391)
and `hover:text-error` (77525), so the "red" delete buttons were drawn ink. `danger` met `text-white`
the same way and won only by luck. `components.md`: the component grows a prop.

**Dark theme**: white on the dark red `#f87171` measured 2.77:1, under FR-011. A `--danger-text` token
(white, `#18181b` in dark) now labels the solid red button and the red panel's mark: 6.4:1 there.

## R6. Parts of a screen and rows

| Call site | Today | After |
| --- | --- | --- |
| Every `x-delete-action` row (values, relationships, notification setup and number, admin configuration) | Ink, red class lost | `tone="danger"`, through R5 |
| Template section and page "Remove", draft, field (`_section`, `_pages`, `builder`, `_inspector`) | Ink | `tone="danger"` |
| Timeline entry "Delete" (`_timeline`) | Ink | `tone="danger"` |
| Contact photo "Remove the photo" (`contacts/edit`) | Ink | `tone="danger"` |
| Relationship category link (`_category`, a bare `<button>`) | Muted, red on hover | `text-error` in place of `text-muted` |
| Every non-delete solid red button (FR-009) | Solid red | Unchanged |

## R7. Copy

**Decision**: no new string. Each section's title reuses its button's existing label ("Delete this
type", "Delete the template", "Delete this vault", "Delete contact"), as the life event type
section already does. The description reuses the paragraph each screen already prints, or none.

**Rationale**: FR-012 changes no wording, and no new key means `monica:localize --check` stays green.

## R8. Tests

**Decision**: no new test file. `x-delete-section` has no contract beyond `x-panel`'s heading, and
`.claude/rules/testing.md` forbids asserting its look. The existing feature tests that find "Delete
this type" and "Delete the contact", and `DeleteActionTest`, must still pass, and the whole suite
runs because shared components change.

**Alternative**: a `DeleteSectionTest` asserting the heading and slots (proves only what `PanelTest`
already proves).
