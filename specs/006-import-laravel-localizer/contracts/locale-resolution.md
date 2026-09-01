# Contract: locale resolution

**Feature**: Locale resolution modelled on laravel-localizer | **Date**: 2026-08-31

The internal contract. One resolver holding the five sources, two middlewares each naming an order, and
no configuration file: the orders are fixed by the specification, so each lives in the class that runs
it. Research R6 has the reasoning.

## The resolver

`App\Helpers\LocaleResolution`, beside `LocaleCollation`.

```text
Sources, each returning a shipped locale key or null
    fromSession(): ?string
    fromUser(?string $guard = null): ?string
    fromCookie(): ?string
    fromAnnouncedLanguage(Request): ?string
    fromApplication(): string

Orders
    forWeb(Request): string
    forApi(Request): string

Recording
    remember(string $locale): void
```

All static, matching `LocaleCollation` beside it, so nothing has to be resolved from the container.

`fromUser()` takes a guard because the api surface needs one named. That surface authenticates with a
token rather than a session, and its middleware runs on the whole group, which is before the route
says a token is required, so the default guard is still the session one and still empty. `forApi()`
passes `sanctum`; `forWeb()` passes nothing and gets the default.

Each source returns a key of `config('monica.locales')` or null, never anything else. That is the
invariant the whole contract rests on, and it is why the shipped test lives inside each source rather
than being applied once at the end: a source that cannot return an unshipped value cannot be wired into
the wrong place later and quietly break FR-006.

`fromApplication()` returns `config('app.locale')` and is not nullable, which is why neither order can
fail to produce an answer.

`remember()` writes the session, the cookie and the active locale together. It is separate from the
orders because the two controllers need it without any reading, which is FR-016.

## The web order

`App\Http\Middleware\SetLocale`, rewritten in place. Already registered by commit `6fa1e09`.

| # | Source | Reads | FR |
| --- | --- | --- | --- |
| 1 | `fromSession()` | session key `locale` | FR-002 |
| 2 | `fromUser()` | `users.locale` of the authenticated user | FR-002 |
| 3 | `fromCookie()` | cookie `locale` | FR-002, FR-015 |
| 4 | `fromAnnouncedLanguage()` | `Accept-Language`, mapped | FR-004, FR-005 |
| 5 | `fromApplication()` | `config('app.locale')` | FR-002 |

The middleware calls `forWeb()` and sets the language, and **records nothing**. This is a correction to
an earlier draft of this contract, which had it call `remember()` on every request. That would keep a
language on the device of somebody who never chose one, which the specification's assumptions rule
out. A language somebody picked is recorded by the screen they picked it on, and a language merely
worked out from their browser is worked out again next time, which costs nothing and leaves nothing
behind. FR-015 is about a chosen language and is satisfied by the two controllers below.

## The API order

`App\Http\Middleware\SetApiLocale`, new.

| # | Source | Reads | FR |
| --- | --- | --- | --- |
| 1 | `fromAnnouncedLanguage()` | `Accept-Language`, mapped | FR-013 |
| 2 | `fromUser()` | `users.locale` of the token's user | FR-013, FR-014 |
| 3 | `fromApplication()` | `config('app.locale')` | FR-014 |

The middleware calls `forApi()` and sets the active locale, and calls nothing else. **It must not call
`remember()`**, which would write a session and queue a cookie. Research R7 explains why this is stated
rather than left to the framework's group definitions, which would make it inert anyway. The two
middlewares are therefore the same three lines apart from which order they call, which is as it should
be: the difference between the surfaces is the order, and nothing else.

## The values that were configuration upstream

Typed constants on the resolver rather than a config file. The first is load bearing for the deploy: it
is the key the product already writes today, so a live session survives the change.

| Value | Value |
| --- | --- |
| session key | `locale` |
| cookie name | `locale` |
| cookie lifetime | one year, in minutes |
| user attribute | `locale`, the existing `users.locale` column |

## The matching rule

`fromAnnouncedLanguage()` is the only new logic in the feature. It reads `$request->getLanguages()`,
which is already parsed, already sorted by quality weight and already written with underscores, and for
each value in turn: returns it if it is a shipped key; otherwise returns the one shipped locale for its
language when the product ships exactly one; otherwise returns the one whose `config/monica.php` entry
declares `answers_bare_language`. The full table is in [data-model.md](../data-model.md).

## Where each order runs

`bootstrap/app.php`.

```text
web group:  ... StartSession ... → App\Http\Middleware\SetLocale → SubstituteBindings   (committed)
api group:  App\Http\Middleware\SetApiLocale → ... throttle ... → SubstituteBindings    (to add)
```

The web position is fixed by FR-009: after `StartSession`, because source 1 needs the session, and
before `SubstituteBindings`, so a resolved record is looked up with the language already settled.
Commit `6fa1e09` already arranged this, by removing `SubstituteBindings` from the group and appending
both in order. Because `SetLocale` keeps its name and path, that registration needs no change and only
the API one is added.

Every route is covered by its group, so no route names a middleware for this. That is FR-008, and
`6fa1e09` already took `set.locale` off all three route files.

## Recording a choice

`LocaleController::update()` and `PreferenceController::update()` both stop writing the session by hand.

```text
LocaleResolution::remember($locale)
```

For `LocaleController` that replaces one write and adds the cookie. For `PreferenceController` it
replaces two, the `session()->put('locale', ...)` and the `app()->setLocale(...)` that follows it, whose
comment already explains that the message would otherwise come out in the language being left behind.
One call now does both and the cookie.

The account preference stays where it is, written only by `UpdatePreferences`, so FR-011 holds.

## Provenance

No file here reproduces text from `asbiin/laravel-localizer`. What is taken is the shape described in
research R1: an ordered walk over sources, first shipped value wins, recorded in several places at
once. That project is named in the pull request as the source of the design. No licence notice enters
the repository, which is SC-013.
