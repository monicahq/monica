# Research: Readable vault and contact addresses

Nine decisions. Everything else follows what the repository already does.

## Where a readable name is written

**Decision**: in the `saving` hook of `Contact::booted()`, beside `sort_name`, and in a `Vault::booted()` that does not exist yet.

**Rationale**: that hook exists because "every path that writes a name meets here", which is its own docblock, and it already builds for the account's language rather than the request's so a queued job writes what a request would. A contact's name is written by `CreateContact`, `UpdateContactName`, `MergeContacts`, the importer and the synchronisation.

**Rejected**: assigning in each action, which is the same code in five places and silently absent in the sixth.

## Keeping a released name away from another record

**Decision**: two tables, `vault_slug_reservations` and `contact_slug_reservations`, each holding every name its record has carried. The collision test reads the live column and the table.

**Rationale**: FR-006 is the requirement the feature turns on. A name released by a rename has to stay unavailable, and the live column alone forgets it the moment it changes.

**Rejected**: one polymorphic table, whose scope column is null for a vault and whose unique index would not compare those nulls; a short discriminator on every address, which satisfies FR-006 by construction and makes every address carry three characters nobody reads; a JSON column of past names, which no supported database indexes for this lookup.

## Building a name that keeps its script

**Decision**: a `SlugBuilder::readable()` beside `build()`, sharing its collision loop. It normalises to NFC, drops every control and format character, lowercases with the root locale, turns every run of anything that is not a letter, mark or number into one separator, trims, limits to 128 characters, and falls back to a fixed word when nothing survives.

**Rationale**: `Str::slug()` folds to ASCII, which is the one thing FR-007 forbids. Dropping format characters is FR-013: they are invisible and they steer the direction an address reads in.

**Rejected**: lowercasing with the account's locale, which gives a Turkish account a different name for the same contact, since Turkish lowercases `I` to `ı`.

## How the preference reaches the addresses the product builds

**Decision**: `Vault` and `Contact` override `getRouteKey()`, returning the readable name or the uuid according to `Account::usesReadableAddresses()`, which mirrors `Account::locale()` and is memoised per request by account.

**Rationale**: `route()` calls `getRouteKey()`, so one override covers all 123 call sites, the mail and the API resources at once. Reading it from the record's own account rather than from the session is what makes a queued notification build the same address a request would.

**Rejected**: reading `Auth::user()` in the model, which is null in a queued job and would send every notification to an unreadable address; passing the chosen form at each `route()` call.

## How both forms resolve

**Decision**: `ResolvesModelInAccount` and `ResolvesModelInVault` gain a `routeKeyColumns()` returning `[$this->getRouteKeyName()]`, which `Vault` and `Contact` override with `['slug', 'uuid']`. The trait matches any of them in one query, inside the same account and vault clauses it already applies.

**Rationale**: FR-003 requires both forms to answer whatever the preference says, and the traits are the one place every binding passes. The default keeps every other model on exactly today's behaviour.

**Rejected**: a second route per form, which doubles 60 route definitions and their names.

## Which words a name may not take

**Decision**: a named list of the segments the routes already fix, and a test walking `Route::getRoutes()` to assert the list still covers them.

**Rationale**: `vaults/new`, `contacts/new` and the `contacts/{filter?}` values `favourites` and `archived` are registered before the record routes and win. A hardcoded list rots the first time a route is added; the test is what stops that.

## Where the preference is stored

**Decision**: `users.uses_readable_addresses`, beside `locale`, `time_format` and `name_order`.

**Rationale**: the spec calls it a preference on the account, and every preference in the product is already a column on the one user the account has. `UpdatePreferences` is extended rather than joined by a second action.

## What a merged away address does

**Decision**: `AbsorbedContactController` stops redirecting and renders a screen naming the contact that absorbed this one, with a link through to it.

**Rationale**: this reverses shipped behaviour, which the clarification asked for. Its `->missing()` hook and its merge lookup are already right; only the response and the lookup column change.

## Giving a name to the records that exist

**Decision**: the three columns go into the create migrations, edited in place, which the migrations skill requires until the end of 2026. Nothing backfills anything: a database that already holds rows is rebuilt with `migrate:fresh`, and the `saving` hook gives every record a name as it is created.

**Rationale**: editing a create migration leaves a database that already exists without the column, so there is no column for a command or a backfill migration to fill. FR-009 holds because seeding creates the records after the column exists.

**Rejected**: `add_slug_to_…` paired with a `backfill_…` migration, which is what `add_match_key_to_contacts_table` and `backfill_contact_match_keys` did on 2026-09-10 and what a database in production would need. The skill's rule wins while the project is not in production, and the exception those two files represent is now written into the skill.
