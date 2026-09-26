# Research: Documentation portal

## R1. Two routes, two controllers under `App\Http\Controllers\App\Docs`

**Decision**: `GET docs` → `DocsController@index`, named `docs.index`; `GET llms.txt` →
`LlmsTextController@index`, named `llmsText.index`. Both public, next to the `docs.api.*` routes.
**Rationale**: matches `ApiDocsController` and `ApiDocsMarkdownController`, one controller per document.
**Rejected**: `Route::view('docs')` (the page needs a view model); `llms.txt` under `docs/` (the
format fixes the file at the root).

## R2. One view model, `App\ViewModels\Docs\DocsViewModel`

**Decision**: `areas()` returns the four cards as `{icon, kind, title, description, url}`, `url`
null where no page exists; `host()` gives the instance host for the illustration; `llmsText()` builds
the `/llms.txt` body. **Rationale**: `ApiDocsViewModel` already serves both its screen and its
Markdown file, so one view model per documentation area is the precedent.
**Rejected**: a static array in Blade (the view models skill); a separate `LlmsText` class (one method).

## R3. `/llms.txt` is English, the portal is translated

**Decision**: the portal's strings go through `__()` (FR-006). `llmsText()` writes English literals
and does not reuse `areas()`, so its wording does not follow the visitor's locale.
**Rationale**: `/llms.txt` indexes the API reference, which is written in English only
(`resources/docs/api`); a file an assistant fetches should not change language with a cookie.
**Rejected**: reusing `areas()` titles in the file (it would come out in French for a French visitor).

## R4. A standalone page, header written inline

**Decision**: `resources/views/app/docs/index.blade.php` is a full HTML document, like
`app/docs/api/show.blade.php`, with the same header: `x-logo`, `x-theme-toggle`, Sign in under `@guest`.
**Rationale**: the two documentation pages must read as one place. The header now stands in two
files, below the three file bar in `.claude/rules/components.md`, and carries no contract of its own
(the theme toggle is already a component). **Rejected**: `x-guest-layout` (it frames sign-in screens).

## R5. Cards and illustration

**Decision**: the cards are a `<ul role="list">`; a card is an `<a>` when it has a `url`, a `<div>`
without the arrow otherwise. The illustration is `aria-hidden="true"`, shows `{host}/dav` and
`{host}/api/mcp` (both real routes) and two made up key strings without a prefix, since keys created
by `CreateApiKey` have none. The design's tokens (`page`, `canvas`, `card`, `hairline`, `ink-soft`,
`shadow-panel`, `shadow-pop`) and icons (`journal`, `key`, `layers`, `device`, `arrow-right`,
`chevron-right`) all exist in `resources/css/app.css` and `x-icon`, so nothing is added to the theme.

## R6. Links

| Element | Target |
| --- | --- |
| Logo | `home.index` |
| Get started with Monica | `auth.register.new` |
| Get an API key | `settings.security.index` (redirects to sign in when signed out) |
| API card | `docs.api.index` |
| `/llms.txt`, OpenAPI spec | `llmsText.index`, `docs.api.openapi` |
| Source code | `https://github.com/monicahq/monica`, written in the view: no config key exists |
