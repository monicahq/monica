# Implementation Plan: Address book and calendar synchronisation

**Branch**: `2026-09-09-dav-synchronisation` | **Date**: 2026-09-09 | **Spec**: [spec.md](spec.md)

## Summary

`monicahq/laravel-sabre` 2.0 mounts a sabre/dav server at `/dav` behind whatever middleware we name. We give it three backends under `app/Http/Dav`: a principal per user, one address book per vault, and per vault two read only calendars, one of yearly events built from recurring dates and one of tasks built from reminder deadlines. A card a device saves is kept on the contact and read back with the record's values merged over it, through one new action, `ImportVCard`, which calls the actions the browser and the API already call. The credential is an API key sent as the Basic password, checked by one middleware through the Sanctum guard, which the package is configured to resolve its own user on, so last use, revocation and the journal come for free and we write no auth backend.

Four decisions carry the plan, three of them argued in [research.md](research.md): the key becomes the first scoped token (section 2), the sync token is derived from timestamps rather than stored (section 3), and the card is the device's own document with the record merged over it by a positional ownership rule (section 4). The fourth is in [data-model.md](data-model.md): the address type gets its stored shape here, because a device is the first thing able to write one.

## Technical Context

| | |
| --- | --- |
| Language, dependencies | PHP 8.4, Laravel 13, Sanctum 4.3. New: `monicahq/laravel-sabre` ^2.0, which needs Laravel 12 or 13 and brings `sabre/dav` 4.7 and `sabre/vobject`. |
| Storage | Two nullable columns on `contacts`: `dav_uri`, and `vcard` holding the card as a device last sent it, minus its photo. `field_values` and `field_value_rows` start touching their record. Nothing else. |
| Testing | PHPUnit. DAV requests through `$this->call('PROPFIND', ...)` with a Basic header, the way the package's own tests do, and what 2.0 has the server read in every environment. Five captured client cards under `tests/Fixtures/Dav`. |
| Target, type | Linux server, hosted and self hosted alike. Server rendered Laravel with a DAV endpoint beside the JSON API. |
| Performance | Listing an address book returns ETags from timestamps and builds no card. Cards are merged in batches by multiget, one query per batch for values and avatars; a merge is one parse and a few property rewrites. A first synchronisation of 1,000 contacts stays under 20 requests for every verified client. |
| Constraints | 60 requests a minute per key, the API's limiter. vCard 4.0 kept, 3.0 negotiated by sabre for Apple. Task times are UTC instants. The suite runs on SQLite, MySQL and PostgreSQL. |
| Scope | 3 backends, 1 merger, 1 reader, 1 calendar writer, 1 middleware, 1 action, 1 enum, 1 migration, 1 provider, 1 config, 1 guide page, 5 edits around the key screen and its API. Roughly 29 files, 19 new. |

## Constitution Check

*GATE: must pass before Phase 0. Re-checked after Phase 1 below.*

| Principle | Verdict | How this feature satisfies it |
| --- | --- | --- |
| I. Actions hold the business logic | **Pass** | Every write a device makes goes through `ImportVCard`, which calls `CreateContact`, `UpdateContactName`, `SetFieldValue`, `UpdateContactAvatar` and `DestroyContactAvatar`; deletion calls `DestroyContact`. The backends are transport, like controllers. |
| II. Simplicity over abstraction | **Pass** | No base backend, no sync token table, no service layer. One ownership rule, positional and sized by the structure, serves both the import and the merge. `CollectionChanges` exists because three collections compute the same three lists. |
| III. Tested before merged | **Pass** | A unit test per backend, merger, reader, writer and action, feature tests driving the endpoint as each client does, a round trip test per captured client card, and the key screen and API tests extended. |
| IV. Laravel conventions | **Pass, one thing to say** | Package config stays as published. `app/Http/Dav` is a namespace under `Http`, which the constitution lists, beside `Resources`, which it does not; if the parenthetical is read as exhaustive this needs an amendment, and the pull request says so. The migration has `up()` only. |
| V. Localized and safe | **Pass** | Card labels are the fields' translated labels in the person's locale, set by `SetApiLocale`. New copy on the key screen and in the guide goes through `monica:localize`. Deletion from a device is the recoverable removal the spec chose. |
| VI. Nothing changes under anybody's feet | **Pass** | A stale save is refused with 412 through the ETag sabre already checks. A card never changes a structure, and what the device put on the card comes back to it untouched. |
| VII. A stable core | **Pass, with a reading** | A card writes only the name and values of fields the structure already has, and the address shape is a value of the existing `address` type. The kept card is the device's own document: the product never reads, searches or shows it, so it is round trip storage and not a second way to attach data. |
| VIII. Nothing disappears silently | **Pass, one cost accepted** | Deletion reuses `DestroyContact`, journalled with the key as source. Nothing a device wrote is stripped from its card, and a sensitive field a key cannot see is left untouched on write. A card dragged between vaults is the standard's create and delete, decided in the spec's 2026-09-10 session: the person's history stays with the removed record, and the journal carries both halves. |
| IX. Accessible to everybody | **Pass** | One screen touched, the security screen: a checkbox and a copyable address, built from the existing `x-input` and `x-row` components. |
| X. Everything goes through the API | **Pass** | The key's new scope is settable from `POST api-keys` and read from its resource, documented in the reference. The DAV endpoint is itself a machine interface. |
| XI. The written record is short | **Pass, one cap exceeded** | The caps are spec 120, plan 100, research 80, data model 60, contract 60, quickstart 60. `spec.md` runs to 130 after two clarification sessions; the pull request says so. |

**Gate result: passes.** Complexity Tracking has no entry.

## Project Structure

```text
app/Http/Dav/                    PrincipalBackend, AddressBookBackend, CalendarBackend,                 new
                                 ReadOnlyCalendar, CollectionChanges, VCardMerger, VCardReader, ICalendarWriter
app/Http/Middleware/             AuthenticateWithApiKeyOnBasicAuth                                     new
app/Actions/ImportVCard.php      one card in, one contact written, the card kept        new
app/Actions/CreateApiKey.php     takes the scopes; its login and registration callers pass the wildcard   edit
app/Enums/ApiKeyScopeEnum.php    SensitiveFields                               new     app/Enums/LogSourceEnum.php  Dav      edit
app/Models/FieldValue.php, FieldValueRow.php   $touches = ['valuable']         edit    app/Models/Contact.php  dav_uri, vcard  edit
app/Jobs/LogUserAction.php       source resolved from the request              edit    app/Helpers/FieldValueFormatter.php  edit
app/Providers/DavServiceProvider.php   nodes and plugins                       new     bootstrap/providers.php, app.php     edit
config/laravelsabre.php          path dav, our middleware, the sanctum guard    new
database/migrations/2026_09_09_000001_add_dav_columns_to_contacts_table.php    new
app/Http/Controllers/App/Settings/Account/Security/ApiKeyController.php, Api/ApiKeyController.php,
app/Http/Resources/ApiKeyResource.php, app/ViewModels/Settings/Account/Security/SecurityViewModel.php,
resources/views/app/settings/account/security/_api-keys.blade.php              edit
resources/docs/api/04-api-keys.php  the scope        edit    resources/docs/api/12-devices.php  guide   new
tests/Feature/Dav/               AuthenticationTest, AddressBookTest, CalendarTest                     new
tests/Unit/Http/Dav/, tests/Unit/Actions/ImportVCardTest.php, tests/Fixtures/Dav/*.vcf                new
```

**Structure Decision**: DAV classes together under `app/Http/Dav`, because they are the transport layer of one surface, exactly as `app/Http/Controllers/Api` is for the other. The action and the enum go where every action and enum goes. Tests mirror the namespaces; the client shaped feature tests sit under `tests/Feature/Dav` because there is no controller of ours to name them after.

## Order of work

1. Dependency, config, provider, middleware, the principal mapping, `PrincipalBackend`: a `PROPFIND` on `/dav/` answers 207 with a key and 401 without.
2. `$touches`, the two columns, `CollectionChanges`, `VCardMerger` over an empty card, the read side of `AddressBookBackend`: story 1.
3. `ApiKeyScopeEnum`, `CreateApiKey`, both key controllers, the resource, the screen, the reference: sensitive fields.
4. `VCardReader`, `ImportVCard`, `VCardMerger` over a kept card, the write side of the backend, the address formatter, the journal source: story 2.
5. `ICalendarWriter`, `CalendarBackend`, `ReadOnlyCalendar`: story 3.
6. The guide page, then the quickstart against two real clients.

## The risk worth naming

Five clients, five parsers, and now a merge that promises to hand each of them its own card back. The suite cannot prove a client accepts what we emit, but it can prove fidelity: for every card under `tests/Fixtures/Dav`, saving it and reading it back yields a card that differs only in the properties the record owns and in `REV`. That test, plus the quickstart run by hand against Apple and DAVx5 before the pull request opens, is the guard.

## Constitution Re-check (post Phase 1)

Still passes. Three things the design settled. `field_values` touching the record is the only edit to shipped behaviour, and it is what keeps a card's ETag honest, so it is argued in research section 3 rather than slipped in. The kept card is argued under Principle VII above rather than assumed. The address shape is a value of an existing type in an existing column, and two things the record still lacks, the address input and room for several values of one kind, are named as owed rather than built here.
