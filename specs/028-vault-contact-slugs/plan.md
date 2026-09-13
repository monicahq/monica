# Implementation Plan: Readable vault and contact addresses

**Branch**: `2026-09-12-vault-contact-slugs` | **Date**: 2026-09-12 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/028-vault-contact-slugs/spec.md`

## Summary

`vaults` and `contacts` each gain a `slug` built from the name their owner gave them, written in the model `saving` hook that already builds `sort_name`, because that hook exists precisely because every path writing a name meets there. `SlugBuilder` gains a `readable()` beside `build()` that keeps the name's own script instead of folding it to ASCII. The `#[RouteKey]` attribute moves to `slug`, which is the one point where all 123 `route()` call sites, the mail and the API resources start building readable addresses at once; `getRouteKey()` is then overridden only to carry the preference, read from the record's own account so a queued notification builds what a request would. The two binding traits gain `routeKeyColumns()` so a record answers to its name and its uuid alike, which is what keeps every saved link working. Two reservation tables hold every name a record has carried, which is the whole of FR-006. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel. No dependency added: `ext-intl` is already required and `LocaleCollation` already drives ICU.

**Primary Dependencies**: none new.

**Storage**: `vaults.slug`, `contacts.slug`, `users.uses_readable_addresses`, and the tables `vault_slug_reservations` and `contact_slug_reservations`. The three columns go into the create migrations, edited in place, which the migrations skill allows until the end of 2026. See [data-model.md](data-model.md).

**Testing**: PHPUnit. `SlugBuilder`, both models, `Account::usesReadableAddresses()` and `UpdatePreferences` under `tests/Unit`. The web and API routes, the preferences screen and the absorbed contact screen under `tests/Feature`. One test walks `Route::getRoutes()` and asserts the reserved list still covers every fixed segment a record could be named after.

**Target Platform**: the signed in web application and the JSON API, which take the same two forms. See [contracts/addressing-a-vault-or-a-contact.md](contracts/addressing-a-vault-or-a-contact.md).

**Project Type**: web application, server rendered Blade, plus the versioned JSON API.

**Constraints**: 37 `whereUuid` constraints in `routes/web.php` and 48 uuid patterns in `routes/api.php`; only those naming a vault or a contact are relaxed, and `contacts.show` is registered after `contacts/{filter?}` and `contacts/new`, so those words are unreachable as names and must be reserved. A readable name must never be lowercased with the account's locale, or a Turkish account gets a different name for the same contact.

**Scale/Scope**: three columns, two tables, two model hooks, two traits, one helper method, one screen, one preference control, and the two API resources.

## Constitution Check

*GATE: passes before Phase 0 and after Phase 1.*

| Principle | How this feature satisfies it |
| --- | --- |
| I Actions hold the business logic | No new action. `UpdatePreferences` is extended rather than joined by a second one, and the name is derived data, which the model already owns for `sort_name`, `sort_key`, `search_key` and `match_key`. |
| II Simplicity over abstraction | One helper method beside the one that exists, one method added to two traits, no base class and no interface. The reservation tables are two plain tables rather than one polymorphic one. |
| III Tested before merged | Both forms on both surfaces, the compartment refusals, the rename, the released name, a name in another script, a name of only invisible characters, the reserved words, the preference in both states, and the whole suite that passed before. |
| IV Laravel conventions | Route model binding does the resolving, `getRouteKey()` does the generating, migrations have only `up()` and declare foreign keys at the bottom, and the command name is kebab-case. |
| V Localized and safe | The preference control and the absorbed contact screen add copy, which `monica:localize` reports and which is translated in every configured locale. Nothing here is destructive, so nothing needs a warning. |
| VI Nothing changes under anybody's feet | Every address that answers today answers afterwards, in both surfaces, whatever the preference says. The preference governs what is built and never what is accepted. |
| VII A stable core | Vault and Contact keep their nature. A readable name is derived from the name they already carry, not a field anybody invents. |
| VIII Nothing disappears silently | A name a record released is reserved rather than recycled, so a stale link fails instead of opening somebody else. A contact absorbed by a merge explains itself rather than 404ing. |
| IX Accessible to everybody | The preference is one labelled control on a screen that exists, operable with the keyboard and announced. The absorbed screen is text and one link. |
| X Everything goes through the API | The API takes both forms wherever it names a vault or a contact and reports the readable name beside the identifier. No capability exists on one surface only. |
| XI The written record is short | `research.md` 67, `data-model.md` 43, `contracts/` 44, `quickstart.md` 56, each inside its cap. `spec.md` is over, and the Complexity table says why. |
| Product invariants | One account, one user, no role. A vault stays a compartment: both forms are refused unless the account matches and the vault the address names holds the record. Shares keep their own token and are untouched. |

## Project Structure

### Documentation (this feature)

`plan.md`, `research.md`, `data-model.md`, `contracts/addressing-a-vault-or-a-contact.md`, `quickstart.md` and `checklists/requirements.md`.

### Source Code (repository root)

```text
database/migrations/2026_08_08_000000_create_vaults_table.php      # slug, unique with account_id
database/migrations/2026_08_09_000007_create_contacts_table.php    # slug, unique with vault_id
database/migrations/0001_01_01_000000_create_users_table.php       # uses_readable_addresses
database/migrations/…_create_vault_slug_reservations_table.php     # and the contact one beside it
app/Helpers/SlugBuilder.php                                        # readable(), sharing the collision loop with build()
app/Helpers/ReservedAddressWords.php                               # the fixed segments a name may not take
app/Models/Vault.php                                               # booted(), getRouteKey(), routeKeyColumns(), reservations()
app/Models/Contact.php                                             # the same, in the saving hook that builds sort_name
app/Models/VaultSlugReservation.php, ContactSlugReservation.php     # two plain models
app/Models/Account.php                                             # usesReadableAddresses(), mirroring locale()
app/Models/User.php                                                # uses_readable_addresses on the docblock and the fillable
app/Models/Concerns/ResolvesModelInAccount.php                     # routeKeyColumns(), defaulting to today's behaviour
app/Models/Concerns/ResolvesModelInVault.php                       # the same
app/Actions/UpdatePreferences.php                                  # takes the preference
app/Http/Controllers/App/Settings/Account/Preferences/PreferenceController.php   # validates it
app/ViewModels/Settings/Account/Preferences/PreferencesViewModel.php             # reports it
resources/views/app/settings/account/preferences/_readable-addresses.blade.php   # one labelled control, included by index
app/Http/Controllers/App/Vaults/AbsorbedContactController.php       # renders instead of redirecting, finds by either column
resources/views/app/vaults/contacts/absorbed.blade.php              # names the contact that absorbed this one
routes/web.php                                                      # the whereUuid constraints naming a vault or a contact
routes/api.php                                                      # the uuid patterns naming a vault or a contact
app/Http/Resources/VaultResource.php, ContactResource.php           # slug beside id, self follows the preference
resources/docs/api/05-vaults.php, 06-contacts.php                   # both forms, and what a client must not assume
```

**Structure Decision**: nothing outside a folder that exists, and no new top level folder. The two reservation models sit in `app/Models` beside `ContactMerge`, the two helpers in `app/Helpers` beside `SlugBuilder` and `LocaleCollation`, and the screen under the vault views that already hold the contact pages.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| First `getRouteKey()` override in the codebase | The preference has to reach 123 `route()` call sites, the mail and both API resources, and only that seam does | Passing the chosen form at every call site cannot be kept true, and reading the session in the model is null in a queued job |
| Two tables that only stop a name being reused | FR-006: a released name reaching another record opens the wrong person | A discriminator on every address satisfies it by construction and makes every address carry three characters nobody reads |
| A model hook writing a row in another table | Five paths write a contact's name, and the hook is where the repository already meets them | Assigning in each action is the same code five times and absent in the sixth |
| Reversing shipped behaviour in `AbsorbedContactController` | It redirects to the survivor today; the clarification of 2026-09-12 asked for a screen that explains | Leaving the redirect shows a different person than the address named, silently |
| Editing three shipped migrations | The migrations skill asks for it until the end of 2026 | Three migrations adding one column each to tables nobody has in production |
| `spec.md` is 147 lines against a 120 line cap | The clarify session added a user story, four requirements, a success criterion and an entity | Dropping a decision to meet a line count |
