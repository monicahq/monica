# Phase 0 research: API foundations and the API docs portal

Every unknown the Technical Context raised is resolved here. Three were settled by the maintainer
during planning and are marked as such. The rest were settled by reading the constitution, the erica
codebase and the beaver reference implementation.

## R1. How the API is versioned

**Decision**: every route sits under `/api/v1`. `routes/api.php` is registered from
`bootstrap/app.php` through `withRouting(api: ...)`, which supplies the `/api` prefix and the `api`
middleware group, and the file opens one group: `Route::prefix('v1')->name('api.v1.')`.

**Rationale**: Principle X requires the API to be versioned, and the design file already writes
`/api/v1/status`, `/api/v1/login` and the rest into its own code samples. Versioning from the first
endpoint costs nothing today. Retrofitting it later breaks every caller or needs a compatibility
layer, which Principle VI exists to prevent.

**Alternatives considered**: beaver's unprefixed `/api/health` with a decorative `v1` badge in the
subheader was rejected. It is not versioning, it is the appearance of it. Chosen by the maintainer.

## R2. Where the reference builder lives

**Decision**: `app/ViewModels/Docs/ApiDocsViewModel.php`, with `ApiDocsSection` and
`ApiDocsCodeSamples` beside it. The markdown endpoints render through
`resources/views/app/docs/api/markdown.blade.php` returned with a `text/markdown` content type.

**Rationale**: the constitution fixes the repository layout and says a new top-level folder under
`app/` needs an amendment. `app/Services`, where beaver keeps `ApiDocumentation`, does not exist here.
A view model is the right fit on its merits, not only as a workaround: it assembles what one screen
shows, it reads from configuration and copy and never writes, and `.claude/CLAUDE.md` already
describes a rich page keeping one main view model with small objects beside it, exactly as
`MarketplaceStatistics` does. Rendering the markdown through Blade keeps the view model Blade-only,
which Principle I demands, and it is genuinely simpler than assembling markdown by string
concatenation in PHP.

**Alternatives considered**: amending the constitution to add `app/Services` was offered and turned
down. `app/Helpers` was rejected because helpers here are stateless utilities like `TextSanitizer`,
not builders that load files and cache. Chosen by the maintainer.

## R3. How the API logs somebody in without duplicating the login rules

**Decision**: `AttemptLogin` gains one constructor flag, `stateless`, defaulting to `false`. When it
is true the action validates the credentials without starting a session, and everything else it does
stays shared: the rate limit keyed on address and IP, the warning email to the address owner, the
refusal of deactivated and single sign-on users, the deliberately identical failure message, the
`last_login_at` stamp, the `CheckLastLogin` dispatch and the activity log entry. `LoginController`
calls it, catches the `ValidationException` it throws on refusal, and answers 401.

**Rationale**: Principle I says API controllers reuse the actions rather than re-implementing the
behaviour, and login is the worst place in the product to hold two copies of the rules. A second
`AttemptApiLogin` action would duplicate the throttle key, the opaque failure and the warning email,
and the two would drift.

Mapping the exception to 401 is transport work and belongs in the controller. The alternative, letting
the framework render `ValidationException` as a 422 with an `errors.email` key, is what a browser form
wants and not what an API client expects for bad credentials.

**Alternatives considered**: a separate action mirroring beaver's `LoginController`, rejected on
Principle I. Widening `AttemptLogin` to mint tokens itself, rejected because minting is
`CreateApiKey`'s job and the web flow must not start doing it.

## R4. How the other five endpoints reuse existing actions

**Decision**: no new action is written.

| Endpoint | Action reused | Note |
|---|---|---|
| `POST /register` | `CreateAccount` then `CreateApiKey` | Produces the same account, first vault, value lists and default template as the sign-up screen, then mints a key named after `device_name`. |
| `POST /login` | `AttemptLogin` (stateless) then `CreateApiKey` | See R3. |
| `DELETE /logout` | `DestroyApiKey` | Called with `apiKeyId: $request->user()->currentAccessToken()->id`, so revocation is logged exactly as it is from the security screen. |
| `GET /me` | none | A read. `UserResource` over `$request->user()`. |
| `PUT /me` | `UpdateUserProfile` | Same rules and the same consequence: changing the address clears `email_verified_at`. |
| `GET /status` | none | A read. |

**Rationale**: this is what Principle I is for, and it is the strongest argument that the actions were
factored correctly in the first place. Every one of them took the API without modification except
`AttemptLogin`, which needed a session it should never have assumed.

## R5. Two-factor authentication on the API

**Decision**: `LoginController` reads `two_factor_confirmed_at`. When it is set, a `code` is required
in the request body and is checked with the existing `VerifyTwoFactorCode` action, which already
accepts a TOTP or a recovery code. No token is issued until it passes.

**Rationale**: without this the API is a complete bypass of the user's second factor, which is worse
than not shipping the endpoint. Beaver reaches the same conclusion and comments it in the same place.
The check sits in the controller because the web flow challenges for the second factor in its own
controller too, after `AttemptLogin` has run.

## R6. What the status endpoint answers

**Decision**: `GET /api/v1/status`, no token, answering `{"status", "version", "instance"}` with 200,
or `{"status": "error"}` with 503 when the database cannot be reached.

**Rationale**: this reconciles two sources that disagree. Beaver serves `/health` answering
`{"message": "ok", "services": {"database": "up"}}`; the design file serves `/api/v1/status` answering
`{"status", "version", "instance"}`, and describes it as telling you whether the instance is up and
which version it runs. The design is the artifact supplied for this feature and it is the more useful
answer, so its path and its shape win. Beaver's substance, actually opening a database connection
rather than answering blindly, is what decides `status`.

The sidebar group stays **Health** with the label **Check status**, which the design and the supplied
image agree on. 503 rather than beaver's 500, because the condition is temporary unavailability and
monitors treat the two differently.

**Alternatives considered**: keeping beaver's `/health` path, rejected because the design is explicit
and more recent. Laravel's built-in `/up` is unrelated and stays where it is.

## R7. Response shapes, and why they are not uniform

**Decision**: authentication endpoints answer a flat body. Resource endpoints answer the JSON:API
style envelope.

```
POST /login     200  { "token": "...", "expires_at": null }
POST /register  201  { "token": "...", "expires_at": null }
DELETE /logout  204  no body
GET  /me        200  { "data": { "type": "user", "id": "1", "attributes": {...}, "links": {...} } }
PUT  /me        200  { "data": { ... } }
GET  /status    200  { "status": "ok", "version": "...", "instance": "..." }
```

**Rationale**: the design file specifies the login and logout bodies exactly, including the 204 on
logout, so those are the documented contract. `/me` returns a resource, and every resource endpoint
added after this one will return the same envelope through `app/Http/Resources`, so it starts there
now rather than being migrated later. Auth endpoints handing back a credential rather than a resource
is a normal and widely understood difference, and beaver makes the same split.

`expires_at` is always `null` today, because tokens here do not expire. It is in the body so a client
that reads it keeps working the day expiry arrives.

**Alternatives considered**: wrapping everything in beaver's `{message, status, data}` envelope,
rejected because it contradicts the design and buries the resource one level deeper for no gain.

## R8. Rate limits

**Decision**: `throttle:60,1` on the status endpoint and on the authenticated group,
`throttle:6,1` on register and login. Laravel's throttle middleware already emits
`X-RateLimit-Limit` and `X-RateLimit-Remaining`, and `Retry-After` on a 429, which is what the design
promises the reader in its per endpoint rate limit box.

**Rationale**: matches beaver, matches Principle X's requirement that the hosted version is rate
limited, and needs no code. The tighter limit on the two credential endpoints is what stops the API
being a password guessing oracle, on top of the per address and per IP limiter already inside
`AttemptLogin`.

## R9. How the portal is drawn

**Decision**: one endpoint to a page. `/docs/api/{section}` renders a single endpoint, `/docs/api`
redirects to the one the reference opens with, and the sidebar is a set of ordinary links between
them. Alpine holds only the selected sample language and the search text. Which entry is being read is
decided on the server, not in the browser.

**Rationale**: the design shows one endpoint at a time, and an address is the honest way to express
that. An anchor into a long page is a worse version of the same thing: the URL names a position
rather than a document, the back button scrolls instead of navigating, one page carries eleven `h1`
elements, and a link somebody pastes depends on the receiving browser restoring a fragment. A page per
endpoint gives each one a real title, a real history entry, and a 404 when it does not exist.

Sidebar links carry `data-turbo="true"`, so moving between endpoints replaces the document without a
full reload while remaining ordinary links. Turbo is opt in in this application, which is why it has
to be asked for.

**Alternatives considered**: beaver stacks every section on one long scrolling page, and the first
implementation here rendered every section and toggled them with Alpine behind a `#fragment`. Both
were rejected for the reasons above. Chosen by the maintainer.

**Accessibility consequences**, which Principle IX makes delivery conditions: the sidebar is a list of
real links, so it works with JavaScript off and with a screen reader; the entry being read carries
`aria-current="page"`, decided server side so it is correct before any script runs; each page is one
document with one `h1`; the verb is text, never colour alone; the language tabs are buttons in a
labelled group; the search input is labelled rather than placeholder only; and copy buttons report
what actually happened rather than assuming.

## R10. Design tokens

**Decision**: no new CSS. The portal is Tailwind utilities over the tokens already in
`resources/css/app.css`.

**Rationale**: the design file imports `monica-design-system-v2`, and erica's `app.css` already
defines the same vocabulary, in both light and dark: `--page`, `--canvas`, `--sunken`, `--ink`,
`--ink-soft`, `--body`, `--muted`, `--muted-soft`, `--hairline`, `--hairline-strong`,
`--hairline-soft`, `--hover`, `--primary`, `--on-primary`, `--brand`, `--success`, `--warning`, and
the radius and duration scales. Each is exposed to Tailwind as a colour utility, so `bg-page`,
`text-ink`, `border-hairline` and the rest are already available. This is also what the project's
standing preference asks for: the design system is utility strings in Blade, and `app.css` holds only
tokens and the palette.

Three names in the design map onto different names here, and the mapping is the whole of the work:

| Design file | erica |
|---|---|
| `--error` | `--danger`, already exposed as the `error` colour utility |
| `--duration-normal` | `--duration-standard` |
| `--topbar`, `--topbar-hairline` | no equivalent; the portal header uses `--canvas` over `--hairline` |

Method colours follow the design: GET `brand`, POST `success`, PUT and PATCH `warning`, DELETE
`error`.

**Alternatives considered**: importing the design system's `styles.css` wholesale, rejected because it
would duplicate a palette erica already has and put two sources of truth in the tree.

## R11. The page around the portal

**Decision**: the portal renders its own minimal public shell: the wordmark, a theme toggle, a sign in
link, and the API subheader from the design. The design's marketing navigation (Features, Pricing,
Docs, Self hosting, GitHub, Get started) is not built.

**Rationale**: erica has no marketing site and the constitution says not to write as though the
`Marketing` namespace exists before it is built. Those links have nowhere to point. The design file is
a template that assumes the marketing site around it; the parts of it specific to the API reference
are what this feature implements. `x-logo` and `x-theme-toggle` already exist as components.

The subheader's Changelog link is dropped for the same reason. The **Get an API key** button points at
the existing `settings.security.show` screen, where keys are already issued.

## R12. Keeping the reference and the routes in step

**Decision**: `ApiDocsViewModelTest` compares the endpoints named in `resources/docs/api/*.php`
against the routes registered in `routes/api.php`, in both directions, and fails on any difference. A
group marked `'guide' => true` is excluded, since its sections are illustrative rather than real
endpoints.

**Rationale**: this is the mechanism that makes FR-019 true rather than aspirational, and it is why
the Pagination guide page can describe a convention that no endpoint implements yet without breaking
the build. It is also what makes the reference cheap to trust as the API grows: an endpoint added
without documentation fails the suite on the pull request that adds it.

## R13. What the Pagination page documents

**Decision**: it stays, and describes the convention that list endpoints will follow, using an
illustrative request against a collection that does not exist yet.

**Rationale**: the supplied image lists Pagination under Getting started, so it is in scope. No
endpoint in this feature returns a list. Documenting the convention now fixes it before the first list
endpoint is written, which is cheaper than discovering three list endpoints disagree. Guide sections
are excluded from R12's synchronisation test precisely so this is possible.
