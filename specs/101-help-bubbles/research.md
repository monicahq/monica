# Research: Help bubbles

Nothing in the Technical Context was left open. Each decision below was settled by the code.

## R1. Rewrite `x-help` in place, do not add a second component

- **Decision**: `resources/views/components/help.blade.php` becomes the 3c bubble. Its one call site (`_api-keys.blade.php:92`) gains a title and a guide.
- **Rationale**: spec FR-009; `components.md` forbids two of one thing. The current file already carries the hover versus pinned state, the outside click, Escape and the narrow screen sheet, so the work is its content and its accessibility contract, not its mechanics.
- **Rejected**: a new `x-help-bubble` beside it (two components, one call site left on the old look).

## R2. A guide is named by its portal page id and resolved per reader

- **Decision**: a static `PortalCatalogue::pageForReader(string $id): ?PortalPage` tries `app()->getLocale()`, then `en`. The bubble takes a `guide` id and calls it.
- **Rationale**: every portal page carries a frontmatter `id` that the writer skill declares permanent, and the portal already links by id (`@doc(integrations.overview)`). `PortalPage::url()` builds the address. Today only `en` exists, so every reader lands on English until a translation ships, with no change here.
- **Rejected**: a route name or a written out URL in each call site (breaks on a rename, which the id survives).
- **Cost**: one file scan of the catalogue per bubble rendered (74 small files). One bubble per screen today; memoise per request only if a screen ever carries many.

## R3. A missing guide fails the suite, never the page

- **Decision**: a coverage test globs `resources/views/**/*.blade.php`, reads every `<x-help` tag, fails when one has no `guide`, and fails naming the id when `guide` is not an `en` page, or when its `#section` is not among the `##` anchors `PortalMarkdown::render()` gives that page (it lists them under `contents`). Anchors come from the heading text, so they are the English ones; a translated portal page opens at its top instead, which is harmless. At runtime a guide that resolves to nothing drops the footer link; the screen still renders.
- **Rationale**: FR-007 and SC-001. Same shape as `tests/Unit/Policies/PolicyCoverageTest.php`. A view not rendered by any test is still covered, which a render time exception would miss, and a 500 in production over a help link is worse than a missing link.

## R4. Disclosure semantics, not a tooltip

- **Decision**: the mark is a `<button type="button">` with `aria-expanded` and `aria-controls` (Alpine `$id('help')`), and an `aria-label` the caller writes in full (`label` prop, "Help about the email address"). The bubble is `role="group"` labelled by its title (`aria-labelledby`), rendered immediately after the button so it follows it in reading and Tab order.
- **Rationale**: FR-A01 to A03. ARIA forbids focusable content inside `role="tooltip"`, and the bubble holds two links. `group` rather than `region` keeps the bubble out of the landmark list. The label is a whole sentence so translators never assemble "Help about" with a fragment.
- **Rejected**: `role="dialog"` as today (implies a focus move and a modal reading mode the bubble does not have).

## R5. Opening, closing, focus

- **Decision**: hover opens on a fine pointer only (current `canHover` check), click or tap toggles a pinned state, focus alone opens nothing. Escape closes and, when focus was inside, moves it to the button. `@focusout` leaving the whole component closes an unpinned bubble. Opening one dispatches `help-opened` on `window` with its `$id`; the others close.
- **Rationale**: FR-005, FR-006, FR-A04, edge case "one at a time". A bubble pinned by a click stays until Escape, an outside click or a second click, which is what a touch reader needs.

## R6. Target size without changing the drawing

- **Decision**: the button keeps `size-4` and gains `after:absolute after:-inset-1 after:content-['']` on a `relative` button, giving a 24 × 24 hit area.
- **Rationale**: FR-A05 (2.5.8).

## R7. Contrast

| Pair | Light | Dark |
| --- | --- | --- |
| Glyph `--muted` on `--canvas` | 4.83 | 7.76 |
| Glyph `--muted-soft` on `--canvas` (today) | **2.56** | 4.12 |
| Ring `--hairline-strong` on `--canvas` | 1.48 | 1.91 |
| Body text `--muted` on `--card` (footer) | 4.63 | 6.91 |

- **Decision**: the glyph moves to `text-muted`. The ring stays decorative; the glyph is what identifies the control (1.4.11). The focus ring is the shared `focus-ring` recipe.

## R8. Reduced motion

- **Decision**: transitions use `duration-(--duration-fast)` classes, as `x-dropdown` does, never bare `x-transition`, whose inline 150 ms ignores the `prefers-reduced-motion` block at `app.css:330` that zeroes the duration tokens.

## R9. Where the mark sits on the profile screen

- **Decision**: `x-page-section` gains a `help` slot rendered right after the `<h2>`, outside it, so the heading's accessible name stays "Email address".
- **Rejected**: the mark inside the `<h2>` (the heading would read "Email address Help about the email address"); on the input's label (the label repeats the section title, so the mark would sit on the second of two identical words).
