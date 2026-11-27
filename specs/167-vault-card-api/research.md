# Research: Vault card values in the API

## R1. Where the shared rules live

- **Decision**: on the models. `Vault::faces()` is a relation (contacts, `listed()`, newest first by
  `created_at` then `id`, `limit(4)`). `Vault::scopeWithCardValues(User $user)` eager loads
  `faces.avatar` and counts `upcoming_reminder_count`. `Vault::scopeOrderedByRecentVisit()` holds the
  web order. `VaultsViewModel` and `Api\VaultController` both call them.
- **Rationale**: the constitution (principle I) forbids consuming a view model outside Blade, so the
  view model cannot be the source. Reading is not a user operation, so an action does not fit. A
  relation and two scopes are the Laravel way and are each testable once, on the model.
- **Alternatives**: a shared read class under `app/Helpers` (an extra concept for three queries);
  copying the rules into the controller (forbidden by FR-006).

## R2. Four faces per vault without N+1

- **Decision**: the `limit(4)` inside the `faces()` relation. Laravel 13 eager loads a limited relation
  with a `ROW_NUMBER()` window per parent, in one query for the whole page.
- **Rationale**: native, works on SQLite, MySQL 8 and PostgreSQL, which the suite runs on.
- **Alternatives**: a hand-written window query (driver-specific SQL, which the constitution forbids
  unguarded); loading every contact and slicing in PHP (unbounded memory on large vaults).

## R3. The reminder count without N+1

- **Decision**: split `ReminderOccurrence::scopeAwaitingAnswerInVault()` into a new
  `scopeAwaitingAnswerUntil(CarbonImmutable $untilDay)` (active, not after the day, rule switched on)
  and the vault and ordering it already adds. Count with an `addSelect` subquery built on
  `ReminderOccurrence::query()->awaitingAnswerUntil()`, correlated on `vault_id`.
- **Rationale**: one correlated subquery for the page. The new scope carries no `orderBy`, because
  PostgreSQL refuses an ORDER BY inside a `count(*)` subquery. The reminders screen keeps calling
  `awaitingAnswerInVault()`, which now calls the new scope, so the rule is written once.
- **Alternatives**: a grouped count merged in PHP (a second code path for the web); `withCount()` with
  a closure, which Larastan cannot type without an inline override.

## R4. The end of the month

- **Decision**: `CarbonImmutable::now($user->timezone)->endOfMonth()`, computed inside
  `scopeWithCardValues()`, which is why the scope takes the user.
- **Rationale**: the web uses the signed in person's zone; the API uses the token holder's, who is the
  same kind of person.

## R5. Every vault response carries the values

- **Decision**: `VaultController::find()` applies `withCardValues()`, and `create` and `update` return
  the vault re-read through `find()` instead of `$vault->refresh()`. `VaultResource` reads
  `$this->faces` and `$this->upcoming_reminder_count` directly.
- **Rationale**: `VaultResource` is used by `VaultController` alone, and both MCP tools returning a vault
  (`ListVaults`, `UpdateVault`) answer through it, so one loading point covers every surface.
- **Alternatives**: `whenLoaded()` in the resource (an attribute that comes and goes breaks clients).

## R6. The avatar address

- **Decision**: `GET|QUERY vaults/{vault}/contacts/{contact}/avatar`, named `contacts.avatar.show`, a new
  `show()` on `Api\ContactAvatarController`. It looks the vault and contact up as `update()` does,
  answers 404 when the contact has no avatar or the file is missing on disk, and streams
  `variantPath(Contact::AVATAR_THUMBNAIL_PIXELS) ?? path`. Cached privately for an hour, not `immutable`
  as `FileVariantController` is: that address names the file, this one names the contact.
- **Rationale**: it sits next to the avatar routes that already exist, and it is not behind
  `flag:file-upload`, matching the web route, which serves stored files whatever the flag says.
- **Alternatives**: a generic vault file endpoint (wider than the need, and its own spec); a signed URL
  (expires, so a cached list breaks).

## R7. The `sort` parameter

- **Decision**: `'sort' => ['nullable', 'string', Rule::in(['recent'])]`, inline, as
  `PossessionController` validates its own `sort`. `recent` applies `orderedByRecentVisit()`, then `id`.
- **Rationale**: a single value needs no enum. `ListVaults` adds `sort` to its schema.

## R8. Proving the query count

- **Decision**: the `queriesOf()` pattern of `PossessionTypeControllerTest`: the same request with 2
  and with 5 vaults, each holding contacts with avatars and reminders, must take the same count.
