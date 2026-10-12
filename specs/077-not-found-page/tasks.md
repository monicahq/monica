# Tasks: A Page of Our Own When an Address Leads Nowhere

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)
**Tests**: required by the constitution (III), one controller test file.

## Phase 1: Setup

- [X] T001 Copy `/Users/regis/Downloads/monica-404-illustration.webp` to `public/images/not-found.webp` (1335 by 532) and the night version to `public/images/not-found-dark.webp` (1986 by 792), research R4

## Phase 2: Foundational

- [X] T002 Create `app/Http/Controllers/App/NotFoundController.php` with `show(): never` calling `abort(404)`, following `HomeController.php` for the header and `declare(strict_types=1)`
- [X] T003 Add `Route::fallback([NotFoundController::class, 'show'])->where('fallbackPlaceholder', '^(?!api/|mcp$).*')->name('notFound.show');` as the last statement of `routes/web.php`, after both `require` lines (research R2)
- [X] T004 [P] Add `'notFound.show'` to `$publicRoutes` in `tests/Feature/RouteProtectionTest.php`

## Phase 3: User Story 1, understand the page does not exist and get back (P1) MVP

**Independent test**: `GET /no-such-address` answers 404 with the heading and a "Return home" link to `route('home.index')`.

- [X] T005 [US1] Create `resources/views/errors/404.blade.php` per the outline in plan.md: first line `{{-- What somebody sees when the address they asked for leads nowhere. --}}`, no `@var` block; `<x-guest-layout :title="__('Page not found')">`; header link to `route('home.index')` holding `<x-logo />` and `{{ config('app.name') }}` with `focus-ring`; `<main>` holding the `<img src="{{ asset('images/not-found.webp') }}" alt="" width="1335" height="532">` scaling to its column, the single `<h1>` `__('We’re sorry, but the page you’re looking for doesn’t exist.')`, the line `__('It may have moved, or the link might be broken.')`, and `<x-button :href="route('home.index')">{{ __('Return home') }}</x-button>`; `<footer>` with `{{ config('app.name') }} © {{ now()->format('Y') }}`, `·`, `__('Open source personal CRM')`, then links `__('Docs')` to `route('docs.index')` and `__('Legal')` to `config('monica.terms_url')`, stacking under `sm`. Typographic apostrophes in every key. Spacing with `space-y`, no page scroll sideways at 375 px
- [X] T006 [US1] Create `tests/Feature/Controllers/App/NotFoundControllerTest.php` (`declare(strict_types=1)`, `#[Test]`, snake_case, The Office data) with `it_shows_the_page_for_an_address_that_leads_nowhere` (404; heading; `route('home.index')`; `route('docs.index')`; `config('monica.terms_url')`; "Return home")
- [X] T007 [US1] Add to the same file `it_shows_the_page_for_a_contact_reached_through_another_vault`: one account, two vaults, a contact in the first, `GET route('contacts.show', [secondVault, contact])` answers 404 with the heading
- [X] T008 [US1] Add `it_answers_json_to_an_api_address_that_leads_nowhere`: `getJson('/api/v1/no-such-address')` answers 404 in JSON without the page (`shouldRenderJsonWhen` decides by path, not by `Accept`)

## Phase 4: User Story 2, my language and my tools (P2)

**Independent test**: a signed in person whose `locale` is `fr_FR` sees the French heading.

- [X] T009 [US2] Run `php artisan monica:localize` and translate every reported string in every configured locale, following `.claude/rules/translations.md` (French: `’`, U+202F before `?` `!` `;`, U+00A0 before `:`). Never edit `lang/*.json` keys by hand
- [X] T010 [US2] Add `it_shows_the_page_in_the_language_of_whoever_is_signed_in` to `tests/Feature/Controllers/App/NotFoundControllerTest.php`: user with `locale` `fr_FR`, `actingAs`, `GET /no-such-address`, assert the French heading from `lang/fr_FR.json`
- [X] T011 [US2] Check `resources/views/errors/404.blade.php` against FR-008 and FR-009: one `h1`, `alt=""` on the illustration, `focus-ring` on every link, tab order logo, button, Docs, Legal; nothing animated

## Phase 5: Polish

- [X] T012 Run `php artisan test --compact tests/Feature/Controllers/App/NotFoundControllerTest.php tests/Feature/RouteProtectionTest.php tests/Feature/Mcp/McpTransportTest.php tests/Feature/Controllers/Api/EmailSentControllerTest.php`, then the whole suite, since the fallback route touches every unmatched GET
- [X] T013 Run `php artisan monica:localize --check`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`; fix what they report
- [X] T014 Run the "By eye" section of `quickstart.md` (light and dark, 375 px and 1440 px, 200% zoom) through the take-screenshot skill and report what the dark theme does to the illustration

## Dependencies

- T001 has no dependency. T002 comes before T003, which comes before T006 to T008 and T010. T004 can run in parallel with anything.
- T005 comes before T006 to T011. T009 comes before T010 and T013.
- US2 depends on US1's view. US1 alone is a shippable MVP in English; US2 adds the translations and the accessibility check.
- Parallel: T001, T002 and T004. Inside US1, the tests T006 to T008 share one file, so they run in sequence.
