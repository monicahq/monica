# Quickstart: proving locale resolution works

**Feature**: Locale resolution modelled on laravel-localizer | **Date**: 2026-08-31

How to see the feature working and how to know it is right. Setup, then the automated proof, then the
manual checks the suite cannot make.

## Prerequisites

The branch checked out, and a database the suite can refresh. That is all: this feature adds no
dependency, so there is nothing to install and no config to publish.

```bash
composer install && php artisan config:clear
```

If `composer.lock` shows a change on this branch, something went wrong. Nothing in this feature touches
it.

## The automated proof

Run the tests this feature owns. They are the definition of done, not a smoke test.

```bash
php artisan test --compact tests/Unit/Helpers/LocaleResolutionTest.php tests/Feature/Localization
```

Then the two controller tests it changes.

```bash
php artisan test --compact tests/Feature/Controllers/App/Auth/LocaleControllerTest.php tests/Feature/Controllers/App/Settings/Account/Preferences/PreferenceControllerTest.php
```

Then the whole suite, before opening anything.

```bash
php artisan test --compact
```

### What each test file has to establish

`tests/Unit/Helpers/LocaleResolutionTest.php` carries the logic, so it carries most of the cases.

The matching rule of FR-004 and FR-005, one case per row of the table in
[data-model.md](data-model.md): a shipped key arriving as is, a region the product does not ship
falling back to its language, a language with no region, both Portuguese regions, bare Portuguese
reaching the locale that declares itself its answer, a language the product does not ship yielding
nothing, several languages resolved in the browser's weight order, and a malformed header.

Then the invariant every source shares, which is FR-006: a session, a cookie or a user column holding a
locale the product does not ship yields nothing rather than that value. Worth a case each, because it is
what makes a forged value harmless and it is easy to implement in four places and forget the fifth.

Then the two orders, FR-002 and FR-013, exercised by feeding sources and asserting which wins: each
source winning when those above it are empty, and each adjacent pair. The pair that matters most is the
one the two orders disagree on, an account preference against an announced language, which must go one
way under `forWeb()` and the other under `forApi()`.

Then `remember()`, which must touch the session, the cookie and the active locale in one call, FR-016.

`tests/Feature/Localization/WebLocaleResolutionTest.php` proves the web order survives a real request:
that FR-008 holds, with a guest screen, a signed in screen and `docs/api` all resolved without any of
them naming a middleware, and that the precedence the unit test asserts in isolation is the precedence
a reader actually gets.

`tests/Feature/Localization/ApiLocaleResolutionTest.php`, driven through `$this->json()` with
`Sanctum::actingAs()`. The rows of the table in
[contracts/api-language.md](contracts/api-language.md), including the header disagreeing with the token
user's preference. Plus the two negative assertions that make FR-013 this project's guarantee rather
than a framework accident, that an API response carries no `Set-Cookie` for the locale and that the
session is untouched, and FR-012, that a request with no session resolves without raising.

The controller tests gain the cookie. `LocaleControllerTest::it_changes_the_language_of_the_interface`
asserts the session today; it also asserts the queued cookie now.
`PreferenceControllerTest` gains the same, and keeps its existing assertion that the account preference
is what `UpdatePreferences` wrote.

## The manual checks

Three things the suite cannot see. Use a browser with its language set to French, and no session.

```bash
composer run dev
```

1. **A first visit lands in French.** Open the sign in screen in a private window. It is in French with
   nothing clicked. This is User Story 2, the one behaviour a reader will notice. If it comes out in
   English, the matching is returning something the shipped test rejects, which is the failure research
   R2 exists to prevent.

2. **A choice outlives the session.** Pick Italian from the switcher. Confirm the interface changes on
   the screen you are returned to, not the one after. Then delete the session cookie only, leaving the
   `locale` cookie, and reload. Still Italian. That is User Stories 1 and 3 in one pass.

3. **An account preference beats the device.** With that Italian cookie still set, sign in to an account
   whose preference is German. German wins. This is the precedence rule the API inverts and the web does
   not, so it is worth seeing once by hand.

For the API, one call shows the inversion:

```bash
curl -s -i -H 'Authorization: Bearer <token>' -H 'Accept-Language: fr-FR' https://localhost/api/me | head -20
```

The body comes back in French even for a token whose user prefers German, and the response headers carry
no `Set-Cookie` for the locale.

## The gates before this is done

```bash
vendor/bin/pint --dirty --format agent
```

```bash
php artisan monica:localize --check
```

`monica:localize` should report nothing missing, because this feature adds no user facing string. If it
reports something, a string was added that the specification did not ask for.

Then PHPStan, and the suite across the three database connections in CI.

Two things a reviewer should check by eye, because no command does:

- No file added by this feature reproduces text from `asbiin/laravel-localizer`, and no licence notice
  from it appears anywhere. That is SC-013. What was taken is the shape, described in research R1.
- That project is named in the pull request as the source of the design, which the spec asks for as a
  courtesy.
