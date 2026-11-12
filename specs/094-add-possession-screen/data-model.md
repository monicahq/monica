# Data Model: Add a Possession Screen

## `possessions` (edited in `2026_10_26_000002`)

| Column | Type | Rule |
| --- | --- | --- |
| `acquisition` | string, nullable | a `PossessionAcquisitionEnum` value |
| `acquired_from_contact_id` | `foreignIdFor(Contact::class, 'acquired_from_contact_id')->nullable()->constrained('contacts')->nullOnDelete()` (as `current_location_id`) | same vault, not trashed; kept only when the acquisition takes a person (R3) |
| `acquired_on` | date, nullable | not after today in the timezone of the person saving it |
| `declared_value` | decimal(12, 2), nullable | 0 to 9999999999.99 |
| `declared_value_currency` | string(3), nullable | a `CurrencyEnum` value; required with a value, null without (R4) |

`Possession` gains these in `#[Fillable]`, casts (`PossessionAcquisitionEnum`, `immutable_date`,
`decimal:2`), `acquiredFrom(): BelongsTo<Contact>` and `tags(): BelongsToMany<PossessionTag>`.

## `possession_possession_tag` (new, `2026_10_26_000006`)

| Column | Type |
| --- | --- |
| `id` | id |
| `possession_id` | `foreignIdFor(Possession::class)->constrained()->cascadeOnDelete()` |
| `possession_tag_id` | `foreignIdFor(PossessionTag::class)->constrained()->cascadeOnDelete()` |
| | unique `[possession_id, possession_tag_id]` |

`PossessionTag` gains `possessions(): BelongsToMany<Possession>`; its docblock loses "cannot carry a tag yet".

## Enum `PossessionAcquisitionEnum`

| Case | Value | `label()` | `dateLabel()` | `takesPerson()` | Person label |
| --- | --- | --- | --- | --- | --- |
| Bought | `bought` | Bought | Bought on | no | |
| Gift | `gift` | A gift | Received on | yes | Given by |
| Inherited | `inherited` | Inherited | Inherited on | yes | Inherited from |
| Made | `made` | Made it | Made on | no | |

No case: "Acquired on". `translationKeys()` returns every wording.

## Actions

| Action | Change |
| --- | --- |
| `CreatePossession` | gains `?PossessionAcquisitionEnum $acquisition`, `?Contact $acquiredFrom`, `?CarbonImmutable $acquiredOn`, `?string $declaredValue`, `?CurrencyEnum $declaredValueCurrency`, `array $values = []`, `array $tagNames = []`; validates all, then one transaction (R5) |
| `UpdatePossession` | the same parameters except `values`, which stay with `UpdatePossessionFieldValues`; `?array $tagNames = null`, and null leaves the tags as they are |
| `DestroyPossessionTag` | unchanged code; docblock says the possessions lose it |

Refusals (ValidationException, keyed to the form): `acquired_from` "This person is not in this vault.",
`acquired_on` "This date is in the future.", `declared_value` "Write a value of zero or more.",
`declared_value_currency` "Choose the currency of this value.", `tags` "A tag has at most 50 characters.",
`values.{uuid}` as `PossessionFieldValueFormat::refusal()`. Another account: `ModelNotFoundException`.

## Helper `PossessionTagNames`

`normalise(array $names): list<string>`: trims, drops empties and duplicates ignoring case, refuses a name
longer than 50 characters. `sync(User $user, Possession $possession, array $names): void`: reuses the
tags matching a name ignoring case, creates the others through `CreatePossessionTag`, syncs. A tag
deleted since the form opened is created again by its name.

## View models (`app/ViewModels/Vaults`)

| Class | Gives the screen |
| --- | --- |
| `PossessionFormViewModel` | old input, types (uuid, name, colour, colour name, field count, fields url), recently used (four), locations (uuid, path, address, address holder name), acquisitions, currencies and default, account tags, featured count, url of the giver search, cancel and action urls |
| `PossessionTypeFieldsBlock` | one type's groups and fields with old values, used by the form and the fragment |
| `PossessionContactCandidatesViewModel` | up to 25 contacts of the vault matching the query, as `FieldChoicesViewModel` |
| `PossessionTagsViewModel` (settings, edited) | per tag, how many possessions carry it |
