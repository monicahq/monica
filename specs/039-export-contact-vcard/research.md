# Research: Export a contact as a vCard

Seven decisions, each settled by reading what the application already does.

## 1. Build the card with `VCardMerger`, unchanged

**Decision**: call `new VCardMerger($reachesSensitiveFields)->merge($contact)`. Add nothing to it.

**Rationale**: it already writes the name, the nickname, the photo, the field values and the identity, and it preserves properties the product has no field for by reading `$contact->vcard` first. FR-003 asks the export and synchronisation to hand over the same card, and reusing the class is the only way that stays true as either side changes.

**Alternatives considered**: a dedicated exporter, rejected because two writers drift; extending `VCardMerger` with an export mode, rejected because the two surfaces want identical output.

## 2. Sensitive fields: a constructor flag, read differently per surface

**Decision**: the web controller passes `true`. The API controller reads the token, following `DavServiceProvider::reachesSensitiveFields()`: `currentAccessToken()`, check for `ApiKeyScopeEnum::SensitiveFields` in `abilities`.

**Rationale**: `VCardMerger:147` already drops a sensitive value when the flag is false, so both behaviours exist today and neither is new code. The clarification settled that an interactive reader sees everything, while a token sees what its scopes allow.

**Alternatives considered**: asking for a password first, rejected because `027-sensitive-action-verification` deliberately excluded record-level actions from that list.

## 3. The API answers with the card itself, which the docs layer cannot express yet

**Decision**: stream `text/vcard` with a `Content-Disposition` file name. Give `BuildOpenApiDocument` a per-endpoint response media type.

**Rationale**: `BuildOpenApiDocument::answer()` hardcodes `application/json` for every status except 204, so the published OpenAPI document would describe this endpoint as returning JSON, which is a lie a client would act on. A `responseMediaType` key on the docs section, defaulting to what it does now, is the smallest honest fix and serves any later endpoint that returns a file.

**Alternatives considered**: leaving the document wrong, rejected outright; a special case keyed on the endpoint id, rejected as the same work with a worse name.

## 4. Match a returning card by its identifier inside `createCard()`

**Decision**: in `AddressBookBackend::createCard()`, read `UID` from the card and look for a contact in that vault carrying it. Found, pass it to `ImportVCard` as `contact:`, which updates. Not found, create as today.

**Rationale**: `createCard` is the only path where a card arrives under a filing address the product has never seen, which is exactly the case FR-011 describes. `ImportVCard` already takes an optional `contact:` and `updateCard` already uses it, so the update path is proven. `VCardReader::first('UID')` reads the identifier with no new parsing.

**Alternatives considered**: matching inside `ImportVCard`, rejected because the action would then own a lookup its other caller must not perform; stripping the UID on export, rejected by the clarification.

## 5. The identifier lookup is confined by the query, not by a check afterwards

**Decision**: `Contact::query()->where('vault_id', $vault->id)->where('uuid', $uid)->first()`.

**Rationale**: FR-012 forbids reading or changing a record in another vault or another account. Filtering in the query means the row is never loaded, so there is nothing to leak through an error message or a timing difference. `vault()` has already confined the address book to the account.

**Alternatives considered**: loading by uuid then comparing `vault_id`, rejected because it reads another account's row before refusing.

## 6. The web route goes above the catch-all

**Decision**: declare `vaults/{vault}/contacts/{contact}/vcard` before `routes/web.php:255`.

**Rationale**: that line is `vaults/{vault}/contacts/{contact}/{page?}`, which matches `vcard` as a page name. Declared after it, the export route is unreachable and the failure looks like a missing page rather than a routing mistake.

**Alternatives considered**: a constraint on `{page?}`, rejected as a change to an existing route to accommodate a new one.

## 7. Journalling and the file name follow existing precedent

**Decision**: one new `UserActionEnum` case, `ContactExported`, with the sentence `Exported the contact :name`, written through `LogUserAction` as the other contact actions are. The file is named from the contact slug, the way `StructureFile::nameOfTemplate()` names a template file.

**Rationale**: both patterns exist and are tested. The slug is already the contact's readable address and is rebuilt on rename, so it is safe for a file name and needs no sanitising of its own.

**Alternatives considered**: naming the file from the display name, rejected because it carries whatever a person typed, including characters a file system refuses.
