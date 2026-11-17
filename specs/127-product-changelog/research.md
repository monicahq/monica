# Research: Product changelog

## R1. Translating an entry

- **Decision**: an entry's English `title`, `area` and body are translation keys. `LocalizeCommand::stringsToTranslate()`
  gains a third source next to the translatable enums: every entry in `changelog/`. The screen and the
  API show `__($entry->title)` and `__($entry->body)` in the reader's locale. Sonnet subagents translate,
  as for every other string.
- **Rationale**: one pipeline. `monica:localize --check` already fails CI on a missing locale (FR-010),
  enforces each locale's typography, and `__()` falls back to English. Editing the English text changes
  the key, so the old translations drop out and the check asks for new ones, which is right.
- **Alternatives**: one Markdown file per locale per entry, rejected: 32 files per change and a second
  check to write. Translated keys inside the front matter, rejected: no typography check.

## R2. Reading the front matter

- **Decision**: `ChangelogEntries` splits the file on the two `---` lines and reads each line as
  `key: value`, trimming one pair of quotes. Values are single line scalars by definition (FR-001).
- **Rationale**: `symfony/yaml` is installed only through dev packages (`laravel/sail`,
  `laravel/roster`), so production lacks it, and adding a dependency needs approval. Six flat keys do
  not need a YAML parser.
- **Alternatives**: commonmark's `FrontMatterExtension`, rejected: it requires `symfony/yaml` too.

## R3. Reading the files on every request

- **Decision**: the parsed list is cached forever under a key built from the sorted file names and
  their `filemtime`s. A request stats the folder (one `glob`, one `stat` per file) and parses nothing
  unless a file changed.
- **Rationale**: the top bar badge needs the list on every signed in page. Deployment changes mtimes,
  so no command has to run after a release (SC-001), on hosted or self hosted.

## R4. Seen marks are rows, not a timestamp

- **Decision**: `changelog_seen_entries (user_id, entry_id)`. Opening What's new, or `PUT changelog/seen`,
  inserts a row for every visible unseen entry. Unseen = visible, published after `users.created_at`,
  no row.
- **Rationale**: an agent sets `published_at` when writing; the pull request merges days later. A "seen
  at" timestamp would then hide that entry from anybody who visited in between.

## R5. Where What's new lives

- **Decision**: `App\Http\Controllers\App\ChangelogController` and friends at the top level of `App`,
  beside `NotificationController`, with `App\ViewModels\ChangelogViewModel`. Admin controllers under
  `App\Admin`, view models under `ViewModels\Admin`.
- **Rationale**: it belongs to no vault and no settings screen, like notifications.

## R6. Comments open on demand

- **Decision**: the Comment button loads `GET changelog/{entry}/comments` into the entry with Alpine
  Ajax (`x-target`); posting answers with the same fragment. A fix row has a disclosure button opening
  the same fragment, its votes included (US3 scenario 2).
- **Rationale**: the page shows up to 90 days of entries; rendering every thread would be most of the
  page for threads nobody opens.

## R7. The period is a query string

- **Decision**: `?period=90`, anything else meaning 30. The switch is two links with `aria-current`
  rather than `aria-pressed` buttons, since each is a page.
- **Rationale**: the code-style rule allows a query string for a reason; a filter that survives reload
  and can be shared is one, as in `admin/support`.

## R8. Days, dates and links

- **Decision**: days are UTC (FR-003). "Today" and "Yesterday" compare with the UTC date of now; any
  other day is written with the reader's date format. `link` must match `^/(?![/\\])\S*$`; a failing
  link is dropped at read time and named by the entry check.
- **Rationale**: one day boundary for everybody, as the brief asks; the link rule excludes schemes,
  protocol relative URLs and backslash tricks with no list to maintain.

## R9. No user journal, an administrator log

- **Decision**: votes, seen marks and comments write nothing to the account's journal. The four
  moderation actions write `AdministratorActionEnum` cases through `LogAdministratorAction` (FR-012).
- **Rationale**: the journal records the person's own data; feedback on the product is not that.

## R10. Not in scope

- No MCP tool: principle X asks for the API; no tool exists to mirror. No webhook: no other
  feedback-on-the-product event has one either. Both are one later pull request each if wanted.
