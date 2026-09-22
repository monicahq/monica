# Research: Contact list view preference

Phase 0. Every decision the plan rests on, with what was rejected. Facts checked against the
repository are marked as such.

## Where the preference is stored

**Decision**: three nullable columns on `users`, added by editing
`database/migrations/0001_01_01_000000_create_users_table.php` in place.

**Rationale**: the table already carries `locale`, `timezone`, `time_format`, `date_format`,
`name_order`, `number_format` and `uses_readable_addresses`, which is the same kind of thing. Null
means "never chosen", which is how `date_format` and `number_format` already say "follow the locale".
The migrations skill requires editing the create migration rather than adding one until the product is
in production, and says a database that predates the edit is rebuilt with `migrate:fresh` rather than
patched.

**Alternatives considered**:

- **A `user_view_preferences` table keyed by user and screen.** Rejected under principle II. One screen
  is in scope and remembering any other list is out of scope, so the table would be a generalisation of
  a single row.
- **A JSON `preferences` column.** Rejected: nothing in the codebase stores preferences that way,
  it cannot be cast to an enum, and it cannot be validated by the same `Rule::enum` the API uses.
- **A new migration beside the existing one.** Rejected: the migrations skill reserves that for a
  column that must reach a database already in use, and names the three migrations that took it.

## Whether the preference is per user or per vault

**Decision**: one per user, applied in every vault.

**Rationale**: the request says "at user level", and the follow-up it names is a single default in
settings, which is one value. It is recorded in the spec's Assumptions as a decision open to revision.

**Alternative considered**: a row per user and vault. It answers the family-vault-and-work-vault case,
but it multiplies the storage, needs a table, and settles a question the request already answered.
Moving to it later is a table and a lookup, and nothing in this design forecloses it.

## How the bare address behaves

**Decision**: `GET vaults/{vault}/contacts` redirects to the address of the remembered view, with the
grouping in the path and the sort and direction in the query string. Every grouping, Everybody
included, has a path segment. `ContactGroupingEnum::Everyone` is already spelled `all`.

**Rationale**: FR-005 wants the address to state what is on screen, which a redirect gives for free,
and which makes the view shareable. Everybody needs a segment of its own because the bare address now
means "open my remembered view": without one, the Everybody chip would point at the bare address and
bounce back to whatever the reader was trying to leave.

**What it costs**: one extra hop for entries through the sidebar, the breadcrumb and the redirect after
a contact is deleted. The filter chips and both forms point at explicit addresses and never redirect,
so the Alpine Ajax path is unaffected.

**Alternative considered**: render the remembered view at the bare address without redirecting. One
fewer round trip, but the address then says nothing about what is on screen, and copying it hands
somebody else their own remembered view rather than the one being shared.

## What an address that states only a grouping does to the sort

**Decision**: a choice the address does not state falls back to the remembered value, and the
remembered value falls back to the default. Only the choices the address states are recorded.

**Rationale**: `RelationshipsBlock` links to the Names only grouping with no query string, and
`app/ViewModels/Vaults/RelationshipsBlock.php:101` is checked. Defaulting the unstated sort to Name
would silently discard a remembered "Last spoke, newest first" every time somebody followed that link.
This refines FR-009 without contradicting it: the stated grouping still wins and is still recorded.

**Consequence**: an address that states a grouping is rendered as it is, with no second redirect to
append the sort. `/vaults/x/contacts/names-only` stays a working permalink.

## Prefetching must not record

**Decision**: skip the recording when `$request->prefetch()` is true.

**Rationale, with the mechanism checked**: `resources/js/app.js:1` imports instant.page, which is
installed at 5.2 and preloads with `<link rel=prefetch>` on hover. Its `isPreloadable()`
(`node_modules/instant.page/instantpage.js:255`) skips a link carrying a query string unless the body
declares `data-instant-allow-query-string`, and no layout declares it, so today's chip addresses, which
always carry `sort` and `direction`, are not prefetched. The Names only link in the relationships panel
carries no query string and is prefetched, so hovering it would otherwise record Names only without
anybody having clicked anything, and the next visit would open on it. That is exactly the surprise
principle VI forbids.

`Illuminate\Http\Request::prefetch()` already reads `X-Moz`, `Purpose` and `Sec-Purpose`
(`vendor/laravel/framework/src/Illuminate/Http/Request.php:319`), which covers the browsers that
implement `rel=prefetch`. It is a framework method, so principle IV is satisfied, and it is testable by
sending the header.

**Alternatives considered**:

- **`data-no-instant` on every link that states a grouping.** It works, and instant.page reads the
  attribute at `instantpage.js:288`, but it has to be remembered on every such link written from now
  on, and forgetting it is invisible.
- **Setting `data-instant-intensity="mousedown"` on the body.** It fixes this screen by changing
  prefetching for the whole product, which is a larger decision than this feature should take.

**Left alone deliberately**: prefetching the bare address is harmless. It redirects to the remembered
view and records the value already held, so nothing changes.

## A direction becomes an enum

**Decision**: add `App\Enums\SortDirectionEnum`, cases `Ascending = 'asc'` and `Descending = 'desc'`,
with `fromValueOrAscending()` mirroring `ContactGroupingEnum::fromValueOrEveryone()` and
`ContactSortEnum::fromValueOrName()`.

**Rationale**: the literal already stands in five places, all checked.
`ContactsViewModel::DIRECTION_ASC` and `DIRECTION_DESC`, `Rule::in(['asc', 'desc'])` in
`app/Http/Controllers/Api/ContactController.php:34`, the same rule again in
`app/Mcp/Tools/ListContacts.php:32`, the `string $direction` parameter of
`Contact::scopeSortedBy()` and its defensive `$direction === 'desc' ? 'desc' : 'asc'`
(`app/Models/Contact.php:674`). A column needs a cast and the API needs `Rule::enum`, and
both want a type. Principle II allows extraction when the thing is reused in several places or is a
concept worth naming; this is both, and it makes the fallback for the third choice work the same way as
the other two.

It carries no `label()`. The wording of a direction depends on the sort it belongs to, "Newest first"
against "Z to A", and that pairing is presentation, so it stays in the view model.

**Alternative considered**: keep the string and validate it at each site. Rejected: the column still
needs a cast, and the fallback would be spelled differently in each of the four places.

## Reaching the preference through the API

**Decision**: the three attributes are read on `GET me` through `UserResource`, and written with
`PUT me/contact-list-view` by a new `MeContactListViewController`.

**Rationale**: `MeTimezoneController` is the precedent, at `PUT me/timezone`
(`routes/api.php:83`): one narrow endpoint, one action, returning `UserResource`. `PUT me` cannot take
the work, because it validates `first_name`, `last_name` and `email` as required, so a client wanting
to change only the view would have to resend the person's name.

**Alternative considered**: a `GET`/`PUT contact-list-view` pair with its own `shape()` method, the way
`NotificationPreferenceController` does it. Rejected: that shape exists because a notification
preference is its own model with its own identifier. This lives on the user, so the user's own resource
is where it is read.

## The route parameter keeps the word `filter`

**Decision**: the web route parameter stays `{filter}`, and the query parameter the JSON API already
publishes stays `filter`.

**Rationale**: the enum, the scope and the model docblock all say "grouping", so one word for one
meaning argues for renaming. Against it: `filter` is the word the API publishes and documents, renaming
it there is a breaking change nobody asked for, and renaming only the web side would leave the two
surfaces disagreeing. Keeping `filter` is the spelling that is consistent across both. The churn is
also outside what the request implies.

## What the changelog records

**Decision**: nothing. `UpdateContactListView` dispatches no `LogUserAction`.

**Rationale**: `UpdatePreferences` logs because somebody went to a screen and made a decision. This
preference is written as a side effect of looking at a list, so logging it would put one entry in the
account's journal per visit to the People list, which is noise in the record that answers who changed
what. The action skips the write entirely when nothing differs, so the same reasoning keeps the
database quiet as well.

**Consequence to accept**: writing the preference through the API is a deliberate act and is not logged
either. One action, one behaviour, rather than a log that depends on the caller.
