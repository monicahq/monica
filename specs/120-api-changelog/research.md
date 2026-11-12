# Research: API changelog

## R1. Where an entry is written

- **Decision**: one PHP file per release under `resources/docs/api-changelog/`, named after its date
  (`2026-11-04.php`), returning the release and its entries. The folder ships holding only
  `.gitkeep`. Format in [data-model.md](data-model.md).
- **Rationale**: the reference already lives as PHP arrays in `resources/docs/api/`, read by
  `ApiDocsViewModel::groups()`. Same shape, same review, and an entry ships in the pull request of the
  change it records (FR-009). One file per date keeps a release's entries together and the file name
  orders them.
- **Alternatives**: a database table, rejected: the reference is content in the repository and a
  self hosted instance has to carry it on deployment. One file per entry, rejected: a release note
  would then have no home.

## R2. Hiding an entry

- **Decision**: an entry is visible when its `group` names a group that `ApiDocsViewModel::groups()`
  returns. A release with no visible entry is dropped.
- **Rationale**: `groups()` already applies `offeredToEveryAccount()` (flags and support), so the
  changelog hides exactly what the reference hides, with no second rule (edge case, FR-008).

## R3. Linking an endpoint

- **Decision**: `ApiDocsViewModel::sectionFor(string $method, string $path): ?ApiDocsSection` scans the
  non guide sections for the same method and path. An entry writes paths relative to `/api/v1`, as
  `resources/docs/api` does. `null` renders the endpoint as text.
- **Rationale**: a removed endpoint has no section, which is precisely the plain text case.

## R4. Checking entries

- **Decision**: `ApiChangelogViewModel::problems(): list<string>` reports every malformed entry
  (missing field, unknown kind or group, breaking without impact, shared id, file name differing from
  its date, a release with no entry). A unit test asserts the shipped folder reports none, and other
  tests feed it fixture folders under `tests/Fixtures/Docs/ApiChangelog/`.
- **Rationale**: FR-009 asks the suite to refuse a bad entry. The view model takes its folder in its
  constructor (`?string $directory = null`, defaulting to `resource_path('docs/api-changelog')`), so
  fixtures prove rendering and the check even though production ships empty.
- **Alternatives**: an Artisan check command, rejected: CI runs the suite already.

## R5. Swapping the folder in a controller test

- **Decision**: controllers take the view model by method injection. A feature test binds
  `new ApiChangelogViewModel(directory: <fixture>)` with `$this->app->instance()`.
- **Rationale**: Laravel's container, no config key added for tests only. `DocsViewModel` resolves it
  with `app()` for the same reason, so `llms.txt` and the sitemap are tested with fixture entries.

## R6. Filtering

- **Decision**: an Alpine component `apiChangelog` in `resources/js/api-changelog.js`, registered in
  `app.js`. Rows carry `data-kind`, `data-breaking` and `data-text`; the component shows or hides rows,
  groups and releases, and keeps the count. The Before/After example is local `x-data` state.
- **Rationale**: FR-005 says no reload. The same approach as `apiDocsSidebarScroll`.
- **Count wording**: "1 change" / ":count changes" is chosen in JavaScript by comparing to one. The
  counted phrase rule is about translated copy; this page is English only like the whole API
  reference (`resources/views/components/api-docs` holds no `__()`), so the rule does not apply.

## R7. Shared controls

- **Decision**: the "Copy for LLM" and "View as Markdown" pair moves out of `x-api-docs.endpoint`
  into `x-api-docs.markdown-actions` (props `markdown`, `url`), used by the endpoint, the list and the
  entry.
- **Rationale**: three files carry it, and it has a behavioural contract (the copy state announced
  through `aria-live`), so `.claude/rules/components.md` asks for a component.
- The example's Copy button follows `x-api-docs.code-panel` rather than `x-copy-button`: the sibling
  panel's pattern wins over the plan (CLAUDE.md precedence), and `x-copy-button` draws a full button
  with a status line that the panel header has no room for.
- Status badges reuse `x-badge` (`success` for Latest, `danger` for Breaking); methods reuse
  `x-api-docs.method-badge`.

## R8. Sidebar, sitemap, llms.txt

- **Decision**: `ApiDocsViewModel::navigation()` gives each guide a `url` and appends
  `['id' => 'changelog', 'label' => 'Changelog', 'url' => route('docs.api.changelog.index')]`; the
  sidebar reads `url`. `DocsViewModel::llmsText()` and `sitemap()` add the list and each visible entry.
- **Rationale**: confirmed that both read `ApiDocsViewModel::sections()` (`DocsViewModel` lines 87
  and 115), so the changelog has to be added beside them, not inherited.
