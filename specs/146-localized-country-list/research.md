# Research: Every country, named in the reader's language

## R1. Where the countries and their names come from

**Decision**: `CountryRepository` of `commerceguys/addressing`, already installed and already the
source of `CountryCodes::known()`. `getList($locale)` returns the 256 codes it knows with their CLDR
name in that locale.

**Rationale**: No new dependency, no list typed by hand, and the same codes the address layout reads,
so a country that can be picked always has a layout. The application locale can be passed as it is:
checked on 2026-11-21, `fr_FR` gives "Allemagne", `nb_NO` gives "Tyskland", `pt_BR` "Alemanha",
`zh_CN` "德国". The package ships a file for every shipped locale (Norwegian Bokmål resolves to `no`),
and falls back to English where it has none.

**Alternatives considered**: `Locale::getDisplayRegion()` from intl, which `TimezoneCatalogue` uses.
It names regions, not a closed list of countries, so the list itself would still have to come from
somewhere else, and its names can differ from the package's for the same code.

## R2. What an address stores

**Decision**: the address keeps `country` as text, and gains an optional `country_code` in the same
JSON value. `country_code` is written only when the country picked has no label written by the
account: a country from the full list, or a favourite whose label is the supplied one or a picked
country's. `country` holds the name in the saver's language at the time.

Reading: `country_code` present means the country is shown by its name in the reader's language.
Absent means the text is shown as written, which is every address saved before this feature, every
imported address, and every favourite whose label the account wrote.

An address without `country_code` opens with its stored text chosen as `keep`, even when that text
matches a favourite. So saving the section for another reason (a new street) leaves the country
exactly as it was, and only picking a country again records a code (FR-008, SC-004).

`country_code` is only ever derived in `SetFieldValues::addressFor()`, which always sets it (to a
code or to null), so a `country_code` sent by a caller is overwritten. `SetFieldValue::address()`
keeps the key, which today it drops with everything outside `ADDRESS_PARTS`; the imports never send
it.

**Rationale**: FR-005 needs the country itself recorded, FR-007 needs a written label kept as
written, FR-008 needs old values untouched. Keeping the text means the vCard export and the Monica 4
export keep working without change. No migration: the value is JSON already.

The search index reads an address through `FieldValueFormatter`, so a country with a code is indexed
by its name in the locale of whoever triggered the indexing, as every other localized value already
is. Searching in another language will not find it by its country; accepted.

**Including Kosovo and the other CLDR territories**: the package lists 256 codes, which is ISO
3166-1 plus AC, CP, DG, EA, IC, TA and XK. They are kept: every one has an address layout, and
dropping Kosovo would refuse a real address. FR-002 says so.

**Alternatives considered**: storing only the code (breaks every reader of `country` and every old
value); storing the favourite's id (a deleted favourite would lose the country, which the edge cases
forbid).

## R3. What the form and the API send

**Decision**: the `country` part of an address accepts, as today, `''`, `keep` or the id of an active
favourite, and now also a two letter code from `CountryCodes::known()`. A code that an active
favourite of the account carries, through a supplied or a picked label, is saved as that favourite
(FR-006). A favourite whose label the account wrote stands only for itself: the code saves the plain
country. `ValueListItem::readsAsItsCountry()` is that one rule, read by the action, the form and the API. `keep` keeps `country_code` along with the text.

The code an item carries is `country_code ?? key`, upper cased: the supplied ten hold theirs in `key`,
in lower case, and an item picked in the settings holds it in `country_code`. Every lookup by code
(this one, the duplicate refusal of R4, `CountryCodes`) compares that expression, or picking France
in the settings would duplicate the supplied France.

**Rationale**: an integer and two letters cannot be confused, so one part serves both the favourite
and the full list, and an API client sending an id keeps working (User Story 4, scenario 4).

## R4. A favourite picked in the settings

**Decision**: picking a country in the Countries settings creates an item with no label and the
country's code. The `label` accessor of `ValueListItem` falls back, after the translation key, to the
country's name in the reader's language. Typing a name instead creates an item with that label and no
code. `CreateValueListItem` refuses a code an active item of the list already carries.

**Rationale**: the supplied ten already work this way (no label, a translation key, a code in `key`),
so a picked country reads in every language as they do. The country code text input disappears: it
existed only because the code could not be picked (spec 134).

**A new state**: until now an item with no label always had a translation key. Readers of the raw
column must fall back too. `ValueListSnapshot::of()` copies `getRawOriginal('label')` and the
translation key into a structure version's frozen snapshot; it also copies `country_code`, and the
code reading a snapshot falls back to the country name when both are null. The other readers go
through the accessor.

**Existing items**: an item added under spec 134 with a typed label and a code keeps both and is shown
as written. Editing it offers the same choice: pick a country (the label is cleared) or keep a name.

## R5. The combo box

**Decision**: a new component, `x-country-picker`, with its Alpine state in
`resources/js/country-picker.js`, registered in `app.js` as `timezonePicker` is. A text input with
`role="combobox"`, `aria-expanded`, `aria-controls` and `aria-activedescendant`, a popup
`role="listbox"` with two `role="group"` blocks labelled "Favourites" and "All countries", a polite
live region announcing the result count, and "No country matches" when it is empty. Arrow keys move,
Enter picks, Escape closes and restores the chosen name, Tab leaves. A hidden input carries the value
the form posts.

Filtering: lower case, accents removed (`normalize('NFD')` and the diacritic class), a substring
match on the name in the reader's language. Favourites that match come first.

**Rationale**: the currency picker and the timezone picker hold the patterns (listbox with
`aria-activedescendant`, a search field stepping into a list), but neither filters a combobox, and
`x-combo-box` is a select that opens a free text box. The markup carries an accessibility contract and
is used in three places (the address, adding a favourite, editing one), so `components.md` asks for a
component now.

**Without JavaScript**: the component renders a `<noscript>` native select with the two groups as
`<optgroup>`, so the form still saves. The hidden input is rendered by Alpine (`x-if`), so without
JavaScript only the select submits the name, and with JavaScript only the hidden input exists.

**Alternatives considered**: extending `x-combo-box` (its job is the free wording of a label list, and
its one call site would carry two unrelated modes); a native select (rejected in clarification).

## R6. Sorting

**Decision**: `LocaleCollation::getCollator($locale)`, as `TimezoneCatalogue` sorts cities.

## R7. Size

256 entries of a code and a name, about 6 KB of JSON per picker. A contact form shows one picker per
address field; the list is built once per request and locale in `CountryCodes` and reused.

## R8. API and MCP

**Decision**: `GET /api/v1/countries` lists every country in the request's locale (set by `SetApiLocale`)
with `code`, `name` and `favourite_id` (the account's active favourite carrying it, or null), in the
dropdown's order. A `list-countries` MCP tool mirrors it through `answerThroughApi()`. Saving an
address through `ContactSectionController` takes the code through R3 with no change of its own;
no MCP tool mirrored that endpoint, so `update-contact-section` is added.

**Gap kept from spec 134**: there is still no API endpoint creating a value list item, so adding a
favourite by picking (User Story 3) is in the interface only. Spec 134 recorded the same gap, accepted
on 2026-11-19.
