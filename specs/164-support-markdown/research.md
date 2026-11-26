# Research: Markdown in support requests

The first plan built a dedicated renderer, a Content Security Policy, a formatted field in the API and
a single rendering for the emails. On review the user asked for Laravel's own Markdown support without
the rest. This version keeps what the safety rests on and drops what only backed it up.

## R1. Store as written

**Decision**: The four actions that write a message (`OpenSupportRequest`, `AddSupportMessage`,
`SendSupportReply`, `AddSupportNote`) stop passing the body through `TextSanitizer::plainText()`. The
body is stored exactly as typed. The subject keeps `plainText()`.

**Rationale**: `plainText()` runs Purify with no tag allowed, which deletes `<anything>`, so
`a <b> c`, an HTML error pasted into a code block and `<https://…>` autolinks are lost before
Markdown sees them (FR-003). Safety belongs to the drawing (R2), which escapes. Knowledge notes already
store their body raw and escape when drawing.

**Consequence**: the API `body` may now contain `<` and `>`. A client that inserted `body` into a page
as HTML was unsafe before and becomes exploitable now. The API changelog says so (R6).

## R2. `Str::markdown()` with safe settings, in one place

**Decision**: A small helper, `App\Helpers\SupportMarkdown`, holds the one call:

```php
Str::markdown($body, [
    'html_input' => 'escape',
    'allow_unsafe_links' => false,
    'max_nesting_level' => 20,
    'max_delimiters_per_line' => 100,
    'renderer' => ['soft_break' => "<br />\n"],
    'external_link' => [... same as KnowledgeMarkdown, plus 'nofollow' => 'external'],
], [new KnowledgeImageLinkExtension, new ExternalLinkExtension, new SupportHeadingLevelExtension]);
```

It exposes `toHtml(string): string` and `plainText(string): string`.

**Rationale**:

- `Str::markdown()` builds a `GithubFlavoredMarkdownConverter`, which covers FR-007 (tables,
  strikethrough, autolinks, task lists drawn `disabled`). Amended 2026-11-26: the converter is now built by hand from the core, autolink, strikethrough and table extensions, leaving task lists out so no form control is drawn, and `SupportLinkSchemeExtension` keeps a link active only for `http`, `https`, `mailto` or a path on the instance (FR-016).
- Its defaults are unsafe: in `vendor/league/commonmark/src/Environment/Environment.php`,
  `html_input` defaults to `allow` and `allow_unsafe_links` to `true`. The two options are the whole
  defence against FR-014 to FR-016, so they must live in exactly one place rather than at every call.
- `max_nesting_level` and `max_delimiters_per_line` bound the known slow inputs (FR-021).
- `soft_break` keeps a single Enter as a new line.
- The GFM converter adds no heading permalinks, footnotes or attribute syntax, so the output carries
  no `id` and no author-chosen attribute (FR-019).

**Alternatives considered**: A dedicated `Environment` like `KnowledgeMarkdown` (the first plan).
Rejected as more than the need: `Str::markdown()` accepts options and extensions, which is all this
uses.

## R3. Headings sit below the screen's own

**Decision**: `app/Helpers/SupportHeadingLevelExtension.php`, a `DocumentParsedEvent` listener of a
dozen lines, adds three to every heading level and caps it at six, so `#` becomes `h4` under the
message's `h3` (FR-009).

**Rationale**: No CommonMark option does this. Sizing alone with CSS would leave an `h1` in the middle
of the thread, which breaks the outline a screen reader user navigates by (FR-013).

## R4. Images are links

**Decision**: Reuse `KnowledgeImageLinkExtension` as is. An image becomes a link to its address, and
the link goes through `allow_unsafe_links` like any other. Nothing is fetched when a message opens
(FR-018). The class keeps its name; renaming it touches Knowledge for no behaviour.

## R5. One component draws a message

**Decision**: `resources/views/components/support/message-body.blade.php` takes the source, calls
`SupportMarkdown::toHtml()`, and prints it inside an element with prose classes: lists, quotes,
tables, headings sized for h4 to h6, and `pre` with `whitespace-pre-wrap wrap-anywhere`, so a long
line wraps rather than scrolls (FR-008) and no scrolling region needs a keyboard stop. Both
`_message` partials call it.

**Rationale**: it stands in two files, below the three file bar of `.claude/rules/components.md`. It
is extracted anyway because it holds the only `{!! !!}` printing a message, and it never receives
HTML, so a later partial cannot print unescaped text by mistake. Same reasoning as that rule's
exception for a contract.

## R6. Emails, API and MCP keep what they do

**Emails**: unchanged code. The four notifications keep `->line($this->body)`. Secured encoding is on
(`Markdown::withSecuredEncoding()` in `AppServiceProvider`), so a line goes through CommonMark with
`html_input` set to `escape`, and `[` and `<` are escaped first, which makes links and images
inactive. Raw HTML therefore stays text, and nothing is fetched (FR-010). One notification test
proves it with a `<script>` and an image now that the body is no longer stripped.

**API and MCP**: `body` is returned as written, as today. No formatted field. The API docs
(`resources/docs/api/32-support-requests.php`, `33-support-administration.php`) describe `body` as
Markdown, to be formatted safely by the client, never inserted as HTML. An API changelog entry
records the change of what `body` may hold. The four MCP tools that describe `body` as plain text say
Markdown.

**Alternatives considered**: a `body_html` field. Rejected by the user for now: a client can format
the Markdown itself, and the field can be added later without breaking anything.

## R7. The last line in the administration list

**Decision**: `SupportMarkdown::plainText()` strips the tags of `toHtml()`, decodes entities and
collapses whitespace. `SupportQueueViewModel::rows()` uses it for `lastLine` (FR-011).

## R8. Where somebody learns the syntax

**Decision**: A section "Format your message" in
`docs/portal/en/10-your-account-and-security/19-ask-for-help.md`, with a table of marks shaped like
the Knowledge notes page. A component `x-support.markdown-hint` prints one line, "You can format your
message with Markdown.", linking to that section through
`PortalCatalogue::pageForReader('account.support')`, as `x-help` resolves its guide. It goes under the
four boxes, which stand in three files, so it meets the three file bar.

## R9. Tests

`tests/Unit/Helpers/SupportMarkdownTest.php` runs a `#[DataProvider]` of at least ten hostile inputs
through `toHtml()` and asserts on the parsed output (DOMDocument) rather than on strings: no `script`,
`img`, `iframe` or `style` element, no `on*` or `style` attribute, every `href` starting `http://`,
`https://`, `mailto:` or `/`. Inputs: `<script>`, `<img onerror>`, `<svg onload>`, `<iframe>`,
`[x](javascript:…)`, `[x](JaVaScRiPt:…)`, `[x](java&#x09;script:…)`, `[x](data:text/html,…)`,
`![x](https://…)`, `![x](javascript:…)`, a fence whose info string holds a quote. Plus the shape
rules (heading levels, soft break, task list drawn as text, external link attributes) and a timing bound on
5,000 nested `>` and 10,000 `*` (SC-003).

The surfaces each get one assertion that they use the helper, in tests that already exist.

## R10. Messages already stored

Nothing migrated. They went through `plainText()`, so they hold no tag. A message that used `*` or a
leading `#` may now read formatted. Accepted in the spec.
