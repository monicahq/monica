# Research: Not Found Page

## R1. How the page is rendered

- **Decision**: `resources/views/errors/404.blade.php`. Laravel renders it for every HTML answer whose status is 404, and keeps its JSON answer for what `shouldRenderJsonWhen` in `bootstrap/app.php` lists (`api/*`, `mcp`, the passkey endpoints). That closure replaces Laravel's `expectsJson()` test, so a JSON request to a web address already gets HTML today, and keeps getting HTML.
- **Rationale**: one file covers an unmatched address, `abort(404)`, a `ModelNotFoundException` from route binding, and `Response::denyAsNotFound()` (an `AuthorizationException` that `prepareException()` turns into a 404 `HttpException`). FR-005 holds with no code.
- **Alternatives**: a render callback in `withExceptions()` handing over a view model. Rejected: it has to repeat the JSON rule and match three exception types to get what the convention already gives.

## R2. Why an unmatched address needs a fallback route

- **Decision**: `Route::fallback([NotFoundController::class, 'show'])->where('fallbackPlaceholder', '^(?!api/|mcp$).*')->name('notFound.show')` at the end of `routes/web.php`; `show()` calls `abort(404)`.
- **Rationale**: an address matching no route runs no route middleware, so no session, no signed in user and no `SetLocale`. The page would be in the application default for everybody, against FR-007 and User Story 2. The fallback route runs the `web` group, so `LocaleResolution::forWeb()` answers as on every other screen. Aborting rather than rendering keeps one rendering path (R1).
- **Consequences, accepted**:
  - `api/*` and `mcp` are kept out of the pattern. `GET /mcp` must keep answering 405 with `Allow: POST` (`McpTransportTest`), and a GET on a POST only API address must keep its 405 (FR-005). Neither needs the reader's language: both answer JSON.
  - `dav/*` is caught by the Sabre route before the fallback.
  - `Route::fallback` registers GET only. A GET on a web address that accepts only other methods answers this page with 404 instead of 405, which suits a person who followed a link.
  - Another method on an unknown address matches no route and gets the same page, in the application default language. Only a script sends one.
  - A visitor hitting an unknown address now starts a session.
- **Alternatives**: no fallback, with the view resolving the locale itself. Rejected: it duplicates `SetLocale`, and the encrypted locale cookie cannot be read without the `web` group.

## R3. No view model

- **Decision**: the view reads `config('app.name')`, `config('monica.terms_url')`, `route('docs.index')`, `route('home.index')` and the year directly.
- **Rationale**: Laravel renders an error view without a controller, so nothing builds a view model. `components/auth-page.blade.php` already prints `config('app.name')` and `now()->format('Y')` in its footer. Nothing here is computed. This is a deviation from the views skill ("the controller builds a view model"), stated in the pull request.

## R4. Layout and illustration

- **Decision**: wrap the page in `x-guest-layout`, which provides the head, the theme script and `bg-page`. Ship the illustration as `public/images/not-found.webp` (1335 by 532, 385 KB), with `alt=""`, `width` and `height` set, and `max-w-full h-auto`.
- **Rationale**: the guest shell is what every signed out screen uses. With the dimensions declared, the page reserves the image's space before it loads and does not shift.
- **Dark theme**: the day illustration is mostly transparent where it is light (sky about 14% opaque), so on a dark page its sky and the pandas' fur turn dark. A night version, `public/images/not-found-dark.webp` (1986 by 792, the same ratio), is shown instead under `.dark` (`dark:hidden` and `hidden dark:block`). The class rather than `<picture media>`, because the theme toggle sets the class and not the media query. Both images are `loading="lazy"`, so the hidden one is not fetched. In the dark theme the page sits on `#142433`, the night sky of the illustration, instead of the near black `bg-page` (decided 2026-10-12); footer text `#a1a1aa` on it is about 6:1.

## R5. CardDAV

When the `carddav` flag is off, `flag:carddav` aborts with 404, and that client now receives our HTML instead of the framework's HTML. The status is unchanged and a CardDAV client reads only the status. FR-005 is read as "same status" for CardDAV.
