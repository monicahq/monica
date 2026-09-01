# Phase 0: Research

**Feature**: Locale resolution modelled on laravel-localizer | **Date**: 2026-08-31

Read from `asbiin/laravel-localizer@master`, from the framework in `vendor/`, and from this
application's own tree, with every checkable claim actually run. Two findings remove work that looked
necessary, and one names the failure this feature exists to avoid.

## R1: What is borrowed

**Decision**: the shape of the answer, not the arrangement of the code and not a line of its text.

**Rationale**: the package's idea is worth having and is three sentences long. A language is resolved
by walking an ordered list of sources. Each source reads one place and returns a language or nothing.
The first value that is a language the product actually ships wins, and is then recorded in several
places at once.

Everything else the package carries exists because it is a package. The `Detector` and `Store`
interfaces, the eight detector classes, the three store classes, the service provider and the config
file are all machinery for letting a host it has never seen choose an order and swap pieces. This
product knows both of its orders, and SC-012 asks that no third be configurable, so that machinery has
nothing to do here.

The precedence itself is also borrowed, in part. Upstream's default order (user, session, cookie,
browser, app) is close to what this product wants, and the two places FR-002 departs from it (session
ahead of user, because that is what the product already does) are departures made knowingly rather than
by accident.

**Alternatives considered**: copying the files, decided against because it brings a licence. Mirroring
the arrangement without the text, which was the previous plan and would have been thirteen classes and
an amendment to the constitution's fixed layout, rejected in clarification.

## R2: The failure this exists to avoid

**Finding**: upstream compares a detected value against the supported locales with
`in_array($locale, $this->locales)`, a strict membership test with no case folding, no separator
handling and no language fallback. Its `CombinedFilter` offers `fr-FR` and then `fr`.

**Why it matters here**: this project's keys are `en`, `fr_FR`, `de_DE`, `es_ES`, `pt_PT`, `pt_BR`,
`nl_NL`, `it_IT`, `tr_TR`. Neither `fr-FR` nor `fr` equals `fr_FR`. Had the package been installed as
it ships, eight of the nine locales would have been unreachable by browser detection and only a browser
announcing a bare `en` would ever have matched. The feature would have looked done and done nothing.

This is invisible from the README, which reads as though `BrowserDetector` matches a browser to a
supported locale. FR-004 exists because of it, and it is recorded here because the same trap is
available to anyone writing the matching from scratch.

**Also not borrowed for a related reason**: upstream's `trusted_detectors`, which makes a source's
value the app locale whether or not it is supported. FR-006 forbids exactly that, and on the API it
would let any `Accept-Language` header set an arbitrary locale.

## R3: The header parsing is already done

**Decision**: `$request->getLanguages()`. No dependency, and no parsing written here.

**Rationale**: verified by running it in this application.

```text
Accept-Language: fr-CH,fr;q=0.9,pt-BR;q=0.8,sv;q=0.7
$request->getLanguages() === ["fr_CH", "fr", "pt_BR", "sv"]

Accept-Language: pt,en-US;q=0.5
$request->getLanguages() === ["pt", "en_US"]
```

Three things come free, and each is a thing the package's own dependency, `codezero/browser-locale`,
exists to provide. The header is split and sorted by its quality weights, in
`Symfony\Component\HttpFoundation\AcceptHeader`. The result is deduplicated. And `formatLocale()`
rebuilds each tag as `language_Script_REGION` joined with underscores, which is this project's own
separator, so `fr-CH` arrives as `fr_CH` rather than needing a translation step.

That last point is why the separator half of R2's problem does not exist for us. It looked like half
the work and the framework had already done it.

**Alternatives considered**: `codezero/browser-locale` as a dependency, which contradicts the clarified
assumption that this feature adds none. Reimplementing it, which is the same work with more bugs.
`$request->getPreferredLanguage()`, which takes a list of acceptable values and returns one, so the
matching problem does not go away and the weight handling becomes opaque.

## R4: The matching rule, which is the only new logic

**Decision**: for each value `$request->getLanguages()` returns, in order:

1. return it if it is a shipped key. `fr_FR`, `pt_BR` and `en` land here;
2. otherwise take its language, everything before the first underscore, and return the one shipped
   locale for that language when the product ships exactly one. `fr_CH` and `fr` both reach `fr_FR`,
   and `en_US` reaches `en`;
3. otherwise, when the product ships more than one for that language, return the one declaring itself
   the answer. Bare `pt` lands here.

Collect every match, in the browser's order, and let the caller take the first. Nothing that is not a
shipped key can come out.

**Rationale**: rule 2 is what makes eight of the nine locales reachable, and it is also what makes a
Swiss French reader land in French rather than in the fallback. Rule 3 is FR-005.

**Alternatives considered**: exact matching only, which is what upstream does and which R2 shows fails
here. Matching on the language alone and ignoring the region, which would send a Brazilian reader to
whichever Portuguese happened to be listed first.

## R5: Which Portuguese answers a bare `pt`

**Decision**: `pt_BR`, declared as `'answers_bare_language' => true` on that locale's entry in
`config/monica.php`.

**Rationale**: CLDR resolves `pt` to Brazilian Portuguese as its default content, which is also where
the large majority of Portuguese speakers are. FR-005 requires the answer be declared beside the
locales rather than inferred, and one optional key on one of the nine entries is the smallest
declaration that satisfies it. The other eight need nothing, because their language maps to exactly one
of them.

**This is a product decision, not a technical one, and it is one config line to reverse.** It decides
only the case where a browser names Portuguese and no region at all. `pt-PT` still reaches `pt_PT` and
`pt-BR` still reaches `pt_BR`, through rule 1 of R4.

**Alternatives considered**: `pt_PT`, on the reading that the bare code names the origin, which most
tooling no longer follows. Declaring nothing and letting the first shipped match win, rejected because
the winner would then be whichever of the two `config/monica.php` lists first, which is `pt_PT` today
by accident of ordering rather than by decision.

## R6: Why two middlewares and one resolver

**Decision**: `App\Helpers\LocaleResolution` holds a method per source, a method per order and a method
that records an answer. `App\Http\Middleware\SetLocale` runs the web order,
`App\Http\Middleware\SetApiLocale` runs the API order.

**Rationale**: the orders genuinely differ. On the web an announced language is the browser's ambient
setting, which a signed in user never asked for, so their account preference outranks it. On the API it
is an explicit act by the caller on that request, so it outranks the preference stored against the
token's user. The spec records that asymmetry as deliberate rather than as an oversight.

Two orders over five shared sources is what decides the split. Each order is a short sequence and
belongs in the middleware that runs it, where a reader looking for "what wins" will find it. The
sources are shared, so they belong somewhere both can reach, and the two controllers need the recording
half without any of the reading half.

**Alternatives considered**: two self contained middlewares duplicating four of the five sources, which
is fewer files and rots the first time one source changes and only one copy follows. One middleware
whose order depends on a condition inside it, which hides the ordering in a branch. A registry of
source objects, which is upstream's answer and was rejected in clarification.

## R7: What the API must not write, and making that this project's promise

**Decision**: the API order records the resolved locale on the application and nowhere else.

**Rationale**: FR-013 says an API request stores nothing.

Worth recording, because it is the reason this is a decision rather than a formality: on the `api`
group a session write and a queued cookie would both already be inert.
`Illuminate\Foundation\Configuration\Middleware::getMiddlewareGroups()` puts `EncryptCookies`,
`AddQueuedCookiesToResponse` and `StartSession` in the `web` group only, so a queued cookie is never
attached to an API response and a session write is never persisted. The requirement would hold by
accident even if the API middleware recorded everything.

It is stated explicitly anyway, because a promise the specification makes should not rest on the
contents of a framework array this project does not own. Recording one thing makes it this project's
guarantee, and a test asserting no `Set-Cookie` on an API response keeps it one.

## R8: Reading a session that was not started

**Decision**: read the session through the `Session` facade, not through the request, and drop the
guard the current middleware carries.

**Rationale**: the existing `App\Http\Middleware\SetLocale` guards with `$request->hasSession()` and
its docblock says that without the check, reading a session that was never started throws. That is true
of `$request->session()`, which raises `RuntimeException: Session store not set on request`. It is not
true of the `Session` facade, which resolves the manager and its driver independently of the request.
Verified by calling `Session::get('locale')` in this application outside any request: it returns
`NULL`.

So the guard disappears with the middleware it belongs to, and FR-012 is satisfied without one. A test
covers it regardless, because it is the kind of thing that is true until a framework upgrade makes it
false.

## R9: Keeping one definition of the shipped locales

**Decision**: the resolver reads `config('monica.locales')` directly. No copy, no derived list, no
config file of its own.

**Rationale**: FR-007 requires one definition. `config/monica.php` is it, holding each locale's label,
region, formats and typography, and it is what the language switcher, the account preferences screen
and `monica:localize` already read. Both the shipped keys and the Portuguese tie break of R5 come from
the same array, so adding a tenth locale is one entry there and one `lang/*.json` and nothing else,
which is SC-011.

## R10: What the deploy does to somebody mid visit

**Decision**: the session key stays `locale`. The cookie is named `locale` and lives a year. The
account preference stays the existing `users.locale` column.

**Rationale**: `App\Http\Middleware\SetLocale`, `LocaleController::update()` and
`PreferenceController::update()` all use the session key `locale` today, so keeping it means a session
written before the deploy is read correctly after it and nobody is dropped back into English mid visit.
That is Principle VI in the smallest possible way, and it is free.

`users.locale` is `string(5)` and nullable, wide enough for `fr_FR`, so no migration.

## R11: What is already committed, and the gap it leaves open

**Finding**: `6fa1e09 feat: import laravel-localizer` has already landed part of this. It took
`set.locale` off `routes/web.php`, `routes/auth.php` and `routes/api.php`, removed the alias, and put
`SetLocale` in the `web` group between `StartSession` and `SubstituteBindings` by removing
`SubstituteBindings` and appending both in order.

**Consequence for the plan**: the web registration is done and `bootstrap/app.php` needs only the API
one. FR-008 is already satisfied for the web surface.

**Consequence worth flagging**: on `main` as it stands, `routes/api.php` no longer runs any locale
middleware, and no replacement exists yet. An API request therefore resolves to whatever
`app()->getLocale()` happens to be, which in a queue worker or a long lived process is whatever the
previous request left behind. That is a live defect, not a hypothetical, and `SetApiLocale` closes it.

## R12: Where the existing behaviour is covered

**Finding**: it is not. Nothing under `tests/` names `SetLocale` or `set.locale`.

**Consequence**: there is no regression suite to lean on, so the tests are written from the
specification rather than from what passes today, and FR-002's precedence rules get explicit coverage
they have never had. `LocaleControllerTest` covers the switcher writing the session and refusing an
unshipped value, which is the one piece of the current behaviour that is tested, and it is extended
rather than replaced.

## R13: Two things the implementation corrected in this plan

Recorded here rather than quietly fixed, because both were wrong in an earlier draft of the contract
and a reader comparing the documents will notice.

**The web middleware must not record what it resolves.** The contract had it call `remember()` on
every request, so that a detected language would stick. That contradicts the specification's own
assumption that nothing is kept on the device of a reader who never chose a language: it would have
put a year long cookie on the first visit of somebody who merely has a browser configured in French.
The middleware now sets the language and records nothing. A choice is recorded by the screen it is
made on, and a detection is redone next request, which costs nothing.

**Reading the account on the api surface has to name the guard.** `Auth::user()` reads the default
guard, which is the session one. The api middleware runs on the group, and `auth:sanctum` is named by
the route, so group middleware runs first and the default guard is still empty when the language is
resolved. `forApi()` therefore calls `fromUser(guard: 'sanctum')`, which reads the token directly.
Without this the API silently ignored every stored preference and fell through to the fallback.

The second was caught by a test rather than by reading, which is the argument for the tests being
written from the specification rather than from the code.

## R14: Two things the test environment hides

Both cost time during implementation and both are worth knowing before writing another test here.

**The test client announces a language of its own.** `Request::create()` defaults
`HTTP_ACCEPT_LANGUAGE` to `en-us,en;q=0.5`, and English is one of the nine, so a test that says
nothing about the header is testing a caller who asked for English rather than one who asked for
nothing. Every test meaning "nothing announced" has to send an empty `Accept-Language` explicitly. Two
API tests passed for the wrong reason until this was found.

**`withUnencryptedCookie()` does not do what it sounds like on a web route.** It puts the raw value in
the request, `EncryptCookies` then fails to decrypt it and nulls it, so the cookie arrives absent. On
the web group the helper to use is `withCookie()`, which the framework encrypts on the way in so the
middleware decrypts it back. Three cookie tests passed vacuously until this was found. The unit tests
build a bare request with no middleware, so they use plain cookies and are unaffected.

## R15: The inversion has a cost on the API, and it showed up in an existing test

**Finding**: `LogControllerTest::it_writes_the_sentence_in_the_language_of_the_account` failed once the
API started reading `Accept-Language` ahead of the account. It signs in a user whose account is French
and asserts a French sentence, and the test client announces English, which the product ships, so
English now wins.

**This is the specification working, not breaking.** FR-013 says a caller naming a language gets it
whoever the token belongs to. The test was written when nothing could outrank the account, and it now
has to say that it announces nothing, which is one line and a comment. A second test was added beside
it for the other half, a caller asking for French against an English account.

**What it tells you about the change, though, is worth stating plainly.** Many HTTP clients send an
`Accept-Language` by default. A browser always does, so a single page application calling this API from
a French speaker's browser sends French whether or not anybody chose it, and that now outranks the
preference stored on the account. `curl` and most server side clients send nothing and are unaffected.

That is the trade the ordering makes, and it was made deliberately when the ordering was inverted
during clarification. It is recorded here because a test failing on a real repository is the first time
it becomes concrete, and because anyone reconsidering FR-013 later should find the argument rather than
rediscover it.
