# Research: Phone numbers and addresses shown the way their country writes them

## R1. Two packages, approved on 2026-11-19

**Decision**: `commerceguys/addressing` (MIT) for address layouts, `giggsey/libphonenumber-for-php-lite`
(Apache 2.0) for phone numbers.

**Rationale**: every country (Clarifications) is CLDR and Google data nobody should copy by hand.
The lite build parses and formats only; the full build's carrier and geocoder data serve nothing.

**Rejected**: hand written layouts (a handful of countries at most); the full libphonenumber build.

## R2. Finding the country code of a stored address

An address stores its country as text: the label the item had when the form or the API saved it
(`SetFieldValues::addressFor()`), or free text from a vCard or Monica 4 import (`SetFieldValue`).
No item id is kept. So the code is found at display time, from that text, in this order:

1. An item of the account's Countries list whose current label matches gives its code.
2. A supplied country whose name, in any shipped locale, matches gives its code. This keeps the
   layout of values saved before a rename, and of values saved in another interface language. It
   loads every locale file, so it runs only when step 1 misses, once per request (R4).
3. Text of two letters that is a code the package knows is that code. A vCard may carry `FR`, since
   `ImportVCard::address()` stores the country component as written.
4. Otherwise there is no code, and the address keeps today's order (FR-002).

Matching ignores case and surrounding spaces. Nothing is written back, so no backfill (SC-003).

**Rejected**: storing the code in `value_json` at save time (imports bypass it; old values need a backfill).

## R3. Where an added country keeps its code

**Decision**: a new nullable `value_list_items.country_code` column, two uppercase letters, written
only for items of the Countries list. A supplied country already carries its code in `key`, so the
resolver reads `country_code ?? key` and the ten need no backfill.

**Rejected**: writing the code into `key`. `WriteAccountExport` writes `key ?? label` for an item and
`WriteAccountImportItem` matches on `key` first, so a code there would break the round trip and make
an added item look supplied.

## R4. One resolver per request

**Decision**: `App\Helpers\CountryCodes`, registered as a scoped singleton, holding one map of
matched text to code per account. A contact page with twenty addresses runs one query.

**Rationale**: `scoped` is flushed between requests and jobs; a static cache outlives a worker.

## R5. Address layout

**Decision**: `FieldValueFormatter::addressLines(array $parts, int $accountId): list<string>`, built
on the package's address format for the code. `addressLine()` joins the same lines with `, `, so
one value reads in one order everywhere (SC-004).

Part mapping: street to address line 1, extended to line 2, post office box to line 3, city to
locality, region to administrative area, postcode to postal code. The country line is the stored
text, not the package's country name, so a label the account chose is shown as written. A filled
part the format has no token for is appended on its own line (FR-003). A region typed as a
subdivision code (`PA`) stays as typed. Confirmed on 2026-11-19: the lines are filled from the
format string of `AddressFormatRepository`, not from its formatter, which would print its own country
and subdivision names. That repository answers any code with a generic format, so whether a code is
known is asked of `CountryRepository`.

## R6. Phone numbers

**Decision**: `FieldValueFormatter::phone(string $text): string`. Text starting with `+` that parses
and is a valid number is formatted in the international form; anything else is returned untouched
(FR-005, FR-006). Text holding a letter is left as typed: the parser rewrites `+33… (ask Pam)` and
`ext 12`. The number is shown with `dir="ltr"` so a right to left screen keeps its digits in
order. The insurance record's `tel:` link keeps reading the stored value (FR-010).

## R7. Surfaces

| Surface | Reaches the new reading through |
| --- | --- |
| Record page, repeater rows | `x-field-value` (address lines, phone) |
| Shared page | `SharedContactViewModel` hands an address as lines, `shared/contact.blade.php` prints them |
| List cells, timeline, markdown for the assistant, life event resource | `FieldValueFormatter::format()` |
| Insurance record | `PossessionInsuranceViewModel` |
| Raw values in the API | unchanged, by design (FR-008) |
| Search index | unchanged: `SearchesRecords` keeps a phone's stored text, which grouping would split (FR-008) |
