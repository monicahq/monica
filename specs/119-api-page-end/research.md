# Research: End of an API reference page

## R1. Order of the neighbours

- **Decision**: `ApiDocsViewModel::sections()` gives the order. Previous and next are the entries on
  either side of the current one in that list.
- **Rationale**: `sections()` already applies `offeredToEveryAccount()`, so a hidden page is skipped
  for free (FR-002). Only `01-getting-started.php` is a guide group and it sorts first, so the flat
  list matches the sidebar, which puts guides before resources.
- **Alternatives**: flattening `navigation()`, rejected: it returns arrays without titles, and would
  repeat the guide/resource split for no gain.

## R2. Where the "last generated" date lives

- **Decision**: a `LAST_GENERATED` constant on `ApiDocsViewModel`, read by `lastGenerated()`, the
  pattern of `McpDocsViewModel::LAST_UPDATED`. The `api-docs-writer` skill gains one line telling it
  to move the date whenever it changes the reference.
- **Rationale**: the date has to survive a deployment. File modification times are reset by every
  checkout, and the application has no record of when a reference file was last changed.
- **Alternatives**: newest `filemtime()` under `resources/docs/api`, rejected for that reason; reading
  git history at runtime, rejected: production has no `.git`.

## R3. Support link

- **Decision**: `ApiDocsViewModel::supportUrl(): ?string` returns `route('support.requests.new')` while
  `SupportAvailability::isEnabled()`, and `null` otherwise.
- **Rationale**: the view model decides what the screen shows (testing rules), so the support
  condition is proven once there. The route sits behind sign in, which covers the visitor edge case.

## R4. Copy and translation

- **Decision**: the copy is written in English in the markup, with no `__()`.
- **Rationale**: the API reference is English throughout and its strings are already written in the
  markup (`endpoint.blade.php`); `.claude/rules/components.md` names the API docs portal as its own
  English-only design system. No `lang/*.json` change follows.

## R5. Component

- **Decision**: one anonymous component, `x-api-docs.page-end`, rendered by
  `resources/views/app/docs/api/show.blade.php` after `x-api-docs.endpoint`, inside `<main>`.
- **Rationale**: it sits beside the other `api-docs` components and is used on one screen; the three
  file bar for a shared component is not reached. Icons come from `x-icon` (`arrow-right`, `mail`,
  `file`), all present; every token of the design (`hairline`, `shadow-panel`, `hover`, `ink-soft`,
  `muted`) exists in `resources/css/app.css`.
- **Changelog**: the footer is a `<ul>`, so the changelog sentence is added later as one more `<li>`.

## R6. Markdown

- **Decision**: nothing changes. `ApiDocsMarkdownController` renders `ApiDocsMarkdown`, which never
  reads the Blade view, so FR-010 holds without any work.
