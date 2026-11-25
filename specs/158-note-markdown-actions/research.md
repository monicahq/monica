# Research: Markdown actions on a note

## R1. Where the Markdown document is built

**Decision**: `KnowledgeMarkdown::document(string $title, ?string $body): string` returns `"# {$title}\n\n{$body}"`,
or `"# {$title}\n"` when the body is blank. The view model and the download controller both call it.

**Rationale**: the helper already owns every Markdown concern of a note (`toHtml`, `headings`,
`taskLine`) and has its test file. One place, so Copy, View as MD and Download cannot disagree (FR-003).

**Alternatives**: an action class like `ExportContactMarkdown` (rejected: one line of string work, no
rule, no journal entry); a model method (rejected: the helper is where note Markdown lives).

## R2. Download: served by a route, not built in the browser

**Decision**: `GET vaults/{vault}/knowledge/notes/{knowledgeNote}/markdown`, named
`knowledge.noteMarkdown.show`, on a new `KnowledgeNoteMarkdownController::show` returning
`response()->streamDownload()` with `text/markdown; charset=utf-8`. The menu item is an
`x-actions-menu.item` with `download data-no-instant`.

**Rationale**: the contact screen does exactly this (`ContactMarkdownController`, its "Export as
Markdown" item). It works without JavaScript, the file name and encoding are set by the server, and
the route is checked by `RouteProtectionTest`. The name follows the sibling `knowledge.noteTasks.update`.

**Alternatives**: a `Blob` and object URL in Alpine, as the design prototype does (rejected: a
second way of downloading in the application, and untestable in PHPUnit).

## R3. File name

**Decision**: `Str::slug($title)`, falling back to `note` when the slug is empty, then `.md`.

**Rationale**: `Str::slug` transliterates to ASCII, which gives `diagnose-wi-fi-and-home-network`.
A title in a script it cannot transliterate yields an empty string, hence the fallback (spec story 3).

## R4. Copy and View as MD stay in the page

**Decision**: the view model exposes `markdown()`. The note's reading view is wrapped in one
`x-data` holding `source` (bool) and `copyState` (`idle|copied|failed`), with the document passed by
`@js()` on that plain `div`. Copy calls `window.copyText()`. View as MD toggles a `<pre>` holding the
document against the drawn `<article>`.

**Rationale**: the text is already on the server when the page renders; fetching it again, as
`contact-markdown-copy.js` does, exists there only because a contact's document is costly to build.
`window.copyText()` is the shared clipboard helper. Reusing "Could not copy. Download the file
instead." matches the contact screen, and the download is one item below.

**Alternatives**: a new JavaScript module (rejected: about fifteen lines of state, `x-copy-button`
keeps the same amount inline); fetching from the download route (rejected, above).

## R5. The trigger button

**Decision**: `x-actions-menu` with `variant="secondary"`, the Markdown mark in its `glyph` slot and,
in its `text` slot, a `span` whose text is "Copied" while `copyState === 'copied'`, else "Markdown",
followed by the `chevron-down` glyph. Item lines follow the contact screen: a `button role="menuitem"`
that closes the menu and returns focus to `$refs.menuTrigger` before acting.

**Rationale**: the menu contract (ARIA, Escape, click outside, focus) lives in `x-actions-menu` and
must not be copied (components rule, contract exception). Its `glyph` and `text` slots already
cover the design. A `role="status"` line under the header carries the copy result, as on the contact
screen, because a changed button label is not announced.

## R6. The Markdown mark

**Decision**: a new anonymous component `x-markdown-mark` with props `size` (height, default 12)
and `label` (nullable), drawn on its own `0 0 208 128` viewBox, filled with `currentColor`, width
`round(size * 208 / 128)`. Decorative (`aria-hidden`) without a label, `role="img"` with
`aria-label` and `<title>` with one, as `x-icon` does.

**Rationale**: `x-icon` is a square 16 unit grid of strokes with one `viewBox` and stroke attributes
for every glyph; the mark is a filled 208 by 128 logo. Forcing it in would add a per glyph viewBox,
fill and aspect ratio to a component used on every screen.

**Alternatives**: a `markdown` key in `x-icon` with special cases (rejected, above).

## R7. Glyphs for the menu lines

**Decision**: add `copy` and `download` to `x-icon`, with the paths from the design (both drawn on the
16 unit grid). `code` already exists for View as MD.
