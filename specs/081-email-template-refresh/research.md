# Research: Refreshed Email Layout

## R1. Where the look lives

- **Decision**: Publish Laravel's html mail components (`layout`, `header`, `footer`, `button`,
  `panel`, `subcopy`) next to the existing `message.blade.php` in `resources/views/vendor/mail/html`,
  and publish `themes/default.css` there too. `Markdown::render()` picks a theme file in that folder
  over the framework's, so no configuration changes.
- **Rationale**: Every notification already goes through `vendor/notifications/email.blade.php` and
  these components (FR-001). Overriding the published files is the documented way to restyle them.
- **Rejected**: a custom view per notification (21 edits, FR-001); a named `monica` theme plus a new
  `mail.markdown` config key (one more moving part for the same result).

## R2. Light colours are inlined, dark colours are not

- **Decision**: Light colours go in `default.css`, which `CssToInlineStyles` writes onto each element.
  Dark colours go in a `<style>` block in the layout's head, inside
  `@media (prefers-color-scheme: dark)`, every declaration `!important`, aimed at classes the
  components carry (`.email-card`, `.email-strip`, `.email-content p` and the rest).
- **Rationale**: The inliner drops media queries from the theme it inlines but leaves a `<style>`
  already in the document alone, which is how the framework's own responsive rules survive. An inline
  style beats a stylesheet rule unless the rule is `!important`. Light stays inline because Gmail and
  Outlook on Windows ignore or strip much of `<style>` (FR-008).
- **Rejected**: dark colours in `default.css` (the inliner removes them).

## R3. Telling clients the email handles dark itself

- **Decision**: `<meta name="color-scheme" content="light dark">`,
  `<meta name="supported-color-schemes" content="light dark">` and `:root { color-scheme: light dark; }`.
  Add `[data-ogsc]` and `[data-ogsb]` rules for Outlook on the web and apps, which recolour with those
  attributes on the body.
- **Rationale**: Apple Mail honours the declaration and the media query (FR-009, FR-010). Outlook's
  attributes let the email correct its partial inversion of text and grounds (FR-011).
- **Gmail apps** recolour on their own and expose no hook. Accepted: every pairing is solid colour on
  solid colour, so the inversion stays legible, and the mark keeps its own white face (R4).

## R4. The mark

- **Decision**: Two PNG files at three times the 28 by 25 header size, `mark-light.png` (dark mark)
  and `mark-dark.png` (light mark), rendered once from `public/images/logo.svg` with ImageMagick and
  committed under `public/images/email/`. The light one is shown; the dark one is hidden with
  `display:none; mso-hide:all` and swapped in by the dark media query. The footer uses the same two
  files at 16 by 15. Absolute URLs from `asset()`, so each instance serves its own (FR-017).
- **Rationale**: Gmail and Outlook do not render SVG in email. `filter: invert(1)` from the design is
  ignored by most clients. A client that ignores the media query keeps the dark mark, whose white
  face still reads on a ground that client darkened.
- **Rejected**: SVG (blocked), base64 inline images (blocked by Gmail, heavier messages),
  a CSS filter (ignored).

## R5. Structure for Outlook on Windows

- **Decision**: Tables with `role="presentation"` for every box, the button as a padded table cell
  around a link, widths set as attributes and styles. Corners and the shadow are left to clients that
  draw them.
- **Rejected**: VML rounded buttons (a block of conditional comments for corners the spec accepts as
  square).

## R6. Measured contrast

Every pairing of design 1a and 1b, plus the derived panel and the danger and success buttons, meets
FR-015. Lowest: muted text on the strip and the footer in light, `#71717a` on `#fafafa`, 4.63:1.
Success button label in light, `#fafafa` on `#15803d`, 4.81:1. Everything else is above 6:1.

## R7. Tests

- **Decision**: One feature test of the rendered layout, under `tests/Feature/Views/Layouts`, building
  `VerifyEmail` and a panel bearing notification and casting `render()` to a string. It asserts what
  `.claude/rules/testing.md` allows: the colour scheme declaration, the language, the alternative texts,
  the heading appearing once, the fallback address present only with an action, and every image URL on
  the application's own host. No colour, class or size is asserted.
- **Rationale**: Existing notification tests already assert each email's copy through `render()` and
  keep passing; the layout is the only new behaviour.
