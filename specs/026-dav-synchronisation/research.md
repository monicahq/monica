# Research: Address book and calendar synchronisation

## 1. The package, how it is mounted, and the tree

`monicahq/laravel-sabre` 2.0.0 requires `illuminate/support ^12 || ^13`, so dropping Laravel 11 costs us nothing. It
routes the seventeen verbs its new `methods` key lists at `config('laravelsabre.path')` behind
`config('laravelsabre.middleware')`, and builds the server from what a provider registers. We publish the config with
middleware `[AuthenticateWithApiKeyOnBasicAuth::class, SetApiLocale::class, 'throttle:api']`: not `web`, whose CSRF
check refuses every `PUT`, and not the package's `Authorize`, since ours already decides. `DavServiceProvider`
registers `PrincipalCollection`, `AddressBookRoot`, `CalendarRoot` and the Auth, CardDAV, CalDAV, Sync and ACL plugins,
plus Browser outside production, as closures resolved once per request after middleware, so the tree reads the signed
in user, and held on the application rather than statically. Two fixes of 2.0 land under us: `MKCALENDAR` and `ACL`
reach the server rather than a router 405, and the server reads the framework request after middleware in every
environment. The tree is in the contract: `principals/{user id}` because users have no uuid and only the user's own key
reaches it, and vault names are display names rather than path segments because two vaults may share one.

## 2. The credential, its first scope, and the journal

A client sends `email:key` as Basic. The middleware points `Sanctum::getAccessTokenFromRequestUsing()` at the bearer
token or, failing that, the Basic password, asks the `sanctum` guard for the user, and requires the Basic username to
be that user's email. The guard stamps `last_used_at` and refuses a revoked key. The account password cannot pass: it
is not a token, so the second factor is never bypassed. Config `guard` is `sanctum`, so the package's `AuthBackend`
resolves the same user and we write none of ours; its challenge is Bearer, which no contacts client answers, so the
`Basic` 401 stays ours, ahead of the server. The principal comes from `LaravelSabre::principal(fn ($user) => (string)
$user->id)` rather than from `principal_attribute`, which would hand the resolver an integer and raise
`PrincipalResolutionException`.

`ApiKeyScopeEnum::SensitiveFields = 'sensitive-fields'` is the first scope. `CreateApiKey` takes `array $scopes = []`
and mints `abilities` as exactly that list. The DAV layer checks membership rather than `tokenCan()`, since the `['*']`
every existing key carries would otherwise reach sensitive fields nobody chose to expose; the wildcard keeps its
meaning.

`LogUserAction` reads `source` from `request()->attributes->get('log_source', LogSourceEnum::Web)`, as it already reads
`ipAddress` from the request; the middleware sets `LogSourceEnum::Dav` and the key's name as `source_detail`.

## 3. Sync and ETags without a table

The token of a collection is the greatest of `updated_at` and `deleted_at` over the vault's contacts, trashed included,
as unix seconds. `CollectionChanges` answers a token with what changed at or after it, `>=` so a change in the same
second is never lost; archived or deleted reads as deleted.

For that to hold a field value must move its record's `updated_at`, which it does not today: `FieldValue` and
`FieldValueRow` gain `$touches = ['valuable']`, honoured on save and on delete. Keeping a card writes the contact row,
so it moves too. The ETag hashes `updated_at`, the locale and the scope, so `getCards()` returns ETags without building
a card and multiget merges them in batches. **Rejected**: a token table as Monica v4 had, and a subquery over values.

## 4. A card is the device's card with the record merged over it

The card a device saves is kept on the contact, normalised to vCard 4.0 and minus its `PHOTO`, which the avatar
re-emits. Every read starts from that card, or from an empty one for a contact no device has written, and rewrites the
properties the record owns: `N`, `FN`, `NICKNAME`, `PHOTO`, `BDAY` from the `birthday` key, and `EMAIL`, `TEL`, `URL`,
`ADR`, `X-ABDATE` from the fields of that type in structure order. Ownership is positional and sized by the structure:
the first F properties of a kind in card order, F the number of fields of that type, or all of them when the type sits
in a repeater. An owned property keeps the parameters the device gave it and takes the record's value, is removed when
its field is empty, and is appended with `X-ABLabel` set to the field's translated label when the record holds more
than the card did. Everything else, a surplus phone, an `ORG`, an unknown property, passes through untouched; a value
cleared in Monica still leaves the card because its property was owned. `UID` is the card's, `REV` is `updated_at`, a
sensitive field is skipped unless the key carries the scope, and sabre converts to 3.0 on request.

**Rejected**: rebuilding the card from the record, which strips what has no field at the next refresh; matching values
to fields by label, which cannot be replayed on merge without remembering which value fed which field.

## 5. Reading a card back

`ImportVCard(user, vault, card, reachesSensitiveFields, ?contact)`. `N` and `NICKNAME` replace every name part through
`UpdateContactName`, or `CreateContact` on the vault's default when there is no contact; no name part at all is sabre's
`BadRequest`. The owned properties of each kind, by the rule in section 4, go to the record's fields through
`SetFieldValue`, one repeater row per value where the type is repeatable, and an owned property absent from the card
clears its field. `PHOTO` absent with an avatar present calls `DestroyContactAvatar`; present, it reaches
`UpdateContactAvatar` only when its bytes differ from the JPEG we emit. The card, minus `PHOTO`, is then kept.

## 6. The two calendars

Dates: one `VEVENT` per recurring date value of the people the address book holds, `DTSTART;VALUE=DATE` on the stored
date or on this year's when the year is unknown, `RRULE:FREQ=YEARLY`, `SUMMARY` from `__(':name · :label')`, `UID`
`{contact uuid}-{value id}`. Reminders: one `VTODO` per occurrence `upcomingInVault()` returns, `UID` the occurrence
uuid, `DUE` the UTC instant, `SUMMARY` the text `ReminderRow` shows, `DESCRIPTION` the contact's name. Tokens come from
the same timestamps, the reminders one from `reminder_occurrences.updated_at`. `ReadOnlyCalendar` overrides `getACL()`
to grant read alone, and every write in `CalendarBackend` throws `Forbidden`. A UTC instant renders at the same moment
on the device, so no `VTIMEZONE` is emitted.
