# Research: Error 500 page

## R1. How a server failure reaches the page

- **Decision**: Add `resources/views/errors/500.blade.php` and nothing else on the server side.
- **Rationale**: With debug mode off, the exception handler turns any exception that is not an HTTP
  exception into a 500 and renders `errors/500` when it exists. `abort(500)` takes the same route even
  with debug mode on. `bootstrap/app.php` already sends `api/*`, `mcp` and the passkey endpoints to
  JSON through `shouldRenderJsonWhen`, and requests that ask for JSON get JSON anyway, so FR-011 holds
  without a change.
- **Alternatives considered**: A `render` callback in `withExceptions`. Rejected: it duplicates what the
  view convention already does.

## R2. Rendering with the database unreachable (FR-007)

- **Decision**: The view uses only `x-error-page`, which uses `x-guest-layout`, `partials.meta`,
  `x-logo`, `route()`, `config()`, `asset()`, `now()` and `__()`. None of them reads the database.
  `partials.meta` already guards the CSRF token with `request()->hasSession()`.
- **Rationale**: Read in full on 2026-11-24. The locale is set by `SetLocale` before the controller
  runs; if the failure happens before or inside it, the application's default locale applies, which
  is what the spec asks.
- **Proof**: A feature test points the default database connection at one that is not configured,
  throws from a route, and asserts the heading. If anything on the page touched the database, the
  rendering would fail and Laravel would fall back to its generic screen, which lacks the heading.

## R3. Not leaking the exception (FR-008)

- **Decision**: The view never reads `$exception`. A test throws a `RuntimeException` whose message
  stands in for a query and asserts the message is absent.
- **Rationale**: The 403 page prints the exception message on purpose (a policy's refusal). The 500
  page must not copy that pattern.

## R4. "Try again" without resubmitting a form

- **Decision**: For a `GET` or `HEAD` request, "Try again" links to `request()->fullUrl()`. For any
  other method it links to `url()->previous(route('home.index'))`, the way the 429 page goes back.
- **Rationale**: A link is always a `GET`, so reloading a failed `POST` through it cannot resubmit
  anything; sending the reader to the address of the `POST` would answer 405. `url()->previous()`
  reads the `Referer` header before the session, so it works without a session.
- **Alternatives considered**: `onclick="location.reload()"`. Rejected: it resubmits a failed form
  after a browser prompt, and it does nothing without JavaScript.

## R5. The illustrations

- **Decision**: Crop the scene out of each mockup with ImageMagick, dropping the logo, text, button and
  footer, and save as WebP: `public/images/server-error.webp` (light, about 1370 × 600 from
  1448 × 1086) and `public/images/server-error-dark.webp` (dark, about 1330 × 540 from 1585 × 992). Both
  `alt=""`, `loading="lazy"`, `decoding="async"`, `max-w-5xl`, swapped with `dark:hidden` and
  `hidden dark:block`, exactly as on the 404 page.
- **Rationale**: Same mechanism as the 404 page, which follows the theme script in `partials.meta`
  (stored choice first, then the system scheme).
- **Limit**: The mockups are smaller than the 404 art (1986 px wide in dark), so the crops are softer
  on a large screen. A higher resolution export of the scene alone can replace the two files later
  under the same names, with only `width` and `height` updated.
- **As built**: the light crop is 1448 × 620, levelled so its off-white background reaches pure white, with feathered transparent edges. The dark crop is 1585 × 560, its background removed by flood fill from the four corners, so the scene sits on the page's own dark colour without a halo. Both under 125 KB.
- **Edge of the light crop**: The heading in the light mockup starts at about y 780 and the grass ends
  at about y 745. The crop stops above the heading.

## R6. The "Try again" icon

- **Decision**: Add a `reload` glyph (a circular arrow, same stroke style as the other glyphs) to
  `x-icon`, rendered `aria-hidden` inside the button.
- **Rationale**: The dark mockup shows it, and `x-icon` has no such glyph today. "Return home" keeps no
  icon, as in the light mockup.

## R7. A failure inside an Alpine Ajax request

- **Finding**: Alpine Ajax 0.12.7 dispatches `ajax:error`, then looks for each target id in the answer.
  The error page carries none, so it dispatches `ajax:missing` and throws a `RenderError` for a
  response that is not ok. The screen stays as it was, nothing is merged, and the reader is not shown
  the error page. This is how a 403, 404 or 419 behaves today too.
- **Decision**: Out of scope. The new page changes nothing here and the spec's edge case is worded to
  match: no fragment is merged. Showing the error to the reader after a failed Ajax request is a
  separate change that touches every error status.

## R8. Strings

New keys: `Server error` (the title), `We’re sorry, something went wrong on our end.`,
`Please try again in a moment.`. `Try again` and `Return home` already exist in every locale. The new
keys are added by `php artisan monica:localize` and translated by Sonnet subagents, following the
translations skill and the typography rules in `.claude/rules/translations.md`.
