# Research: Welcome screen

## R1. Where the detour goes

**Decision**: `HomeController::index` sends a user whose `welcomed_at` is empty to `welcome.index`,
before its vault check.

**Rationale**: every sign-in path redirects to `home.index` (`LoginController`, `MagicLinkController`,
`SsoController`, `TwoFactorChallengeController`, `EmailVerificationController`, and
`redirectUsersTo` in `bootstrap/app.php`). One line there covers them all.

**Alternatives**: a middleware on the whole signed-in group (intercepts bookmarks and email links, and
every request pays a check); a redirect in each sign-in controller (six places to keep in step).

## R2. What the flag is

**Decision**: a nullable `welcomed_at` datetime on `users`, added to the `Schema::create` of
`0001_01_01_000000_create_users_table.php`.

**Rationale**: a moment says as much as a boolean and more (when). The migrations skill says to edit
the create migration until production; a database that predates it is rebuilt with `migrate:fresh`.

**Alternatives**: a boolean `is_welcomed` (loses the moment); a new migration (the skill forbids it
before production).

## R3. Which users start welcomed

**Decision**: `UserFactory` sets `welcomed_at` to now, with a `notWelcomed()` state. `CreateAccount`
writes nothing, so a real account starts empty.

**Rationale**: the factory stands for users who already exist. Without it, every test reaching
`home.index` would land on `welcome` and `HomeControllerTest` would describe the detour instead of
the vault decision.

## R4. One action, idempotent

**Decision**: `MarkUserAsWelcomed(user)` sets `welcomed_at` only when it is empty and returns the
user. The web and API controllers both call it. It dispatches no `LogUserAction`.

**Rationale**: FR-005 keeps the first moment. Dismissing a letter is not something the account's
journal should record, the same as `UpdateContactListView`.

## R5. Routes

**Decision**: web `GET welcome` → `WelcomeController@index` (`welcome.index`) and `PUT welcome` →
`WelcomeController@update` (`welcome.update`), in the `auth`, `active`, `verified` group beside
`gettingStarted.index`. API `PUT me/welcome` → `MeWelcomeController@update` (`api.v1.me.welcome.update`),
answering `UserResource`, which gains `welcomed_at` as a timestamp or null.

**Rationale**: marking the user changes the user, so `update`, as `me/contact-list-view` does.

## R6. The screen

**Decision**: `x-guest-layout` (the bare page ground, already used by the signed-in
`confirm-password` screen), then `x-logo`, `x-caption`, an `h1`, `x-panel`, two `x-image` portraits (`public/images/regis.webp`, `alexis.webp`),
and an `x-button` submitting the `PUT` form. `App\ViewModels\Welcome\WelcomeViewModel` gives the copy.

**Rationale**: 1a draws no navigation column, so `x-account-layout` does not fit. Every piece exists
under `resources/views/components`.

## R7. Copy

**Decision**: new strings for the caption (`Welcome to :app`), the greeting (`Hi :name, we’re glad
you’re here.`, first word of `first_name`, as getting started does), the line, the three paragraphs,
and `The people behind :app`. The signature reuses the existing `:first and :second` key, so it reads
"Regis and Alexis" rather than the design's "Regis & Alexis".

**Rationale**: one translation of the founders' names already exists; a second key for an ampersand
is two words for one meaning.

## Left standing

- Getting started (`GettingStartedViewModel::letter()`) already carries a letter from the same two
  people, with their portraits. A new account without a vault now reads both, one after the other.
  This plan does not touch getting started; whether its letter goes is the owner's decision.
- 1a draws initials; the founders asked for their portraits instead, with an empty `alt`, since their names are printed beside them.
- Getting started points at `images/regis.jpg` and `images/alexis.jpg`, which do not exist, so its portraits are broken today.
