# Research: Template named on the edit screen of a record

## Where the block sits

- **Decision**: The `actions` slot of `x-page-header`.
- **Rationale**: The slot already places content to the right of the title and wraps it under the
  title when the row does not fit, which is design 1C. A new component would duplicate the header.
- **Alternatives considered**: Hand written header markup matching the design's `items-end`
  alignment. Rejected: `.claude/rules/components.md` says a call site accepts the base layout rather
  than setting its own. The slot aligns to the top and wraps by available width, not at exactly
  1024px; both are visually close to 1C.

## How the template is reached

- **Decision**: `contact->structureVersion->structure->owner`, the path `ContactResource` already
  uses for `template_name`.
- **Rationale**: One path for one fact. The `name` accessor on `Template` already translates a
  supplied template's name, so the screen and the template list agree (FR-004).
- **Alternatives considered**: A `template()` relation on `Contact`. Rejected: one caller, no
  query to share.

## When the owner is null

- **Decision**: `template()` returns `?Template` and the view leaves the block out when it is null.
- **Rationale**: In production a template cannot be deleted while a record uses it, so this never
  shows. Factory contacts in tests have a structure with no owner, and the owner of a structure may
  also be a life event type, which a contact never uses. One `@if` costs less than a 500.
- **Alternatives considered**: An empty state. Rejected by the spec: no state to describe.

## Link target

- **Decision**: `route('settings.templateBuilder.index', $template)`, same tab.
- **Rationale**: The builder, where the tabs, sections and fields the contact shows are changed. Templates belong to the account, so nobody who
  can edit a contact is refused there.

## Accessible name of the link

- **Decision**: Visible text is the template name. The caption "Current template" is tied to the link
  with `aria-describedby`, and the arrow icon is decorative.
- **Rationale**: The name alone tells a screen reader user which template; the caption says what it
  is. No extra hidden text.
