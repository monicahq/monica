# Research: Document head metadata through Laravel Head

Source: the package documentation at https://laravel.com/framework/docs/head and its `composer.json`,
read on 2026-11-04. The package documents no test helper, no single switch for noindex, and nothing
on whether `canonical()` keeps the query string.

## R1. The package installs as it ships

- Decision: `composer require laravel/head`. It asks for PHP `^8.3` and `illuminate/*` `^13.17.0`;
  13.35 is installed. Auto discovery registers `HeadServiceProvider` and the `Head` facade.
- Rationale: the spec's request is the approval a new dependency needs (CLAUDE.md). Alternative:
  keep writing tags by hand in `partials/meta`, which is what left every page indexable.

## R2. `partials/meta` stays the one place, and `@head` replaces two tags in it

- Decision: the partial keeps the charset, viewport, csrf token, Turbo setting, icons, manifest and
  theme script. Its `<title>` and description become `Head::title($title, exact: true)` and
  `Head::description(...)` in an `@php` block, followed by `@head`.
- Rationale: every layout and docs page already passes `title` and `description` to the partial, so
  no screen changes (FR-006) and a new screen still sets its title once (SC-004).
- Alternatives: move icons and manifest into `Head::defaults()` (moves working markup for nothing);
  set the head in every controller (six places for one rule).

## R3. Noindex is the default, declared once

- Decision: `AppServiceProvider::configureHead()` declares `Head::defaults()` with the application
  name as title, `robots('noindex')`, and `og(siteName:, type: OgType::Website)`. `Head::errors()`
  declares `robots('noindex')` for every status.
- Rationale: precedence puts error metadata last, so a 404 under `/docs` stays noindex. Plain
  `noindex` lets a crawler that lands on a signed in page still follow its links.
- Alternatives: `hiddenFromRobots()` renders `none`, which adds nofollow everywhere for no gain.

## R4. Public pages opt in through one middleware

- Decision: a middleware aliased `searchable` is put on the docs group and the marketplace group. When
  `config('monica.search_engine_indexing')` is true, it calls `Head::searchableByRobots()` and marks
  the request; `partials/meta` then calls `Head::canonical(forceHttps: false)` unless the page is an
  error page. The middleware runs before an error is known, and the package cannot remove a tag.
- Rationale: route metadata is fixed when the routes are cached, so it cannot read a setting. A
  middleware reads it at request time, and one class is the one place the rule lives.
- The package builds the canonical from `$request->url()`, which drops the query string.
- `forceHttps: false`: the package rewrites canonical addresses to https by default, which would
  point a self hosted instance served over http at an address that does not answer.
- Alternatives: `Route::withHead()` with the config read inline (stale under `route:cache`); the
  same call in six controllers.

## R5. Private links are nofollow through route metadata

- Decision: `share.show` and `share.password.create` take `->withHead(robots: 'noindex, nofollow')`.
- Rationale: a fixed string, so route caching is safe, and it sits beside the route it protects.

## R6. The switch is `MONICA_SEARCH_ENGINE_INDEXING`, on by default

- Decision: `config/monica.php` key `search_engine_indexing`, `(bool) env(..., true)`, documented
  the way `registration_enabled` is, and added to `.env.example`.
- Rationale: the clarification asks for a variable that turns indexing off, so the default is on.
  It reads no plan and no deployment kind, so both deployments run the same product.

## R7. Descriptions come from what each page already has

| Page | Description |
| --- | --- |
| API section | The section's existing `description` |
| Guide page | A new required `description` key in its front matter, 73 files in `docs/portal/en` |
| Docs index, CardDAV, MCP, marketplace | The lead sentence each page already shows; no new string |

- Alternative for the guide: the body's first paragraph, which drifts with whatever the page opens on.

## R8. No alternate languages yet

- Decision: none are written. Only `en` exists, so the code would never run, and no test could reach
  its pairing branch. It lands with the second locale, matching pages by front matter `id`.

## R9. Tests read the rendered head

- Decision: feature tests assert the `<meta name="robots">`, canonical and `og:` tags in the
  response. One data provider lists a route per kind (vault screen, settings, admin, sign in,
  private link, password form, 404, docs, guide, marketplace) for SC-001.
- Rationale: no fake exists, and the rendered head is the contract the crawler reads.
