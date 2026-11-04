# Research: Banner offering full access to free accounts

## R1. Who is offered full access

**Decision**: no recorded dismissal, `$account->isMetered()`, and `! $account->hasFullAccess()`.

**Rationale**: both methods exist on `Account` and the caps read nothing else. `isMetered()` is
false on a self hosted instance and for a hosted account with no ceiling, which settles both edge
cases without a deployment check of our own. `hasFullAccess()` covers subscription, lifetime and
granted access in one call.

**Alternatives considered**: `$account->subscribed()` alone (rejected: it offers a subscription to
a lifetime holder); reading `config('monica.self_hosted')` again (rejected: `isMetered()` does).

## R2. Where the dismissal is stored

**Decision**: a nullable `full_access_offer_dismissed_at` timestamp on `users`, added to the create
migration, set by a `MarkFullAccessOfferAsDismissed` action that keeps the first moment.

**Rationale**: the shape of `welcomed_at` and `MarkUserAsWelcomed`, the one other "seen once, never
again" fact in the application. An account holds one user, so the user row is the account's. The
migrations skill says to edit the create migration until production, so a local database needs
`migrate:fresh`.

**Alternatives considered**: a column on `accounts` (rejected: the precedent and the API's `me`
resource are the user); browser storage (rejected: FR-006 requires every device); a table of
dismissed notices (rejected: one notice does not earn a table).

## R3. Where the banner is drawn

**Decision**: an anonymous component `x-full-access-offer`, rendered by `layouts/shell` and
`layouts/account` above the screen's content. `Shell` and `AccountLayout` each expose one boolean,
`offersFullAccess`, read from `User::isOfferedFullAccess()`. `Shell` sets it to false on the billing
routes: `settings.billing*`, `settings.checkout*`, `settings.subscription*`, `settings.invoices.*`.

**Rationale**: the vault list, the first screen after sign in, uses `x-account-layout`; every other
signed in screen uses `x-shell`, directly or through `x-settings.page`. The admin space, sign in and
shared screens use other layouts, so FR-002's exclusions hold without code, except Billing. `Shell`
already reads the current route for the search box.

**Alternatives considered**: a view composer (rejected: the project has none); the check written in
Blade (rejected: three conditions and a route list are logic, not markup); drawing it in
`x-marketplace-layout` too (rejected: that layout also serves visitors, and the marketplace is not
where the reader's limits bite).

## R4. How dismissing works

**Decision**: a plain `<x-form method="put">` to `fullAccessOffer.update`, whose controller calls
the action and answers `redirect()->back()`.

**Rationale**: the welcome screen and duplicate pairs are dismissed the same way. The reader lands
on the same screen without the banner, focus starts at the top of the page, and an error takes the
ordinary path. No JavaScript.

**Alternatives considered**: Alpine Ajax replacing the banner in place (rejected: it needs an empty
target in every response and its own focus handling, for an action taken once).

## R5. API coverage

**Decision**: `PUT api/me/full-access-offer`, answering `UserResource`, which gains
`full_access_offer_dismissed_at`. Documented beside `me/welcome` in
`resources/docs/api/03-profile.php`.

**Rationale**: principle X, and `MeWelcomeController` is the exact precedent.

## R6. Journal

**Decision**: no `LogUserAction`. `MarkUserAsWelcomed` writes none either: hiding a banner changes
nothing the account owns.

## R7. Cost per screen

**Decision**: check the dismissal first, then `isMetered()` (no query), then `hasFullAccess()`.
Only an undismissed free metered account pays for the order and subscription queries.
