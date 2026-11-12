# Contracts: Screen, API and MCP

## Web routes (`routes/web.php`, vault group, after `possessions.index`)

| Verb and path | Controller | Name |
| --- | --- | --- |
| GET `vaults/{vault}/possessions/new` | `PossessionController@new` | `possessions.new` |
| POST `vaults/{vault}/possessions` | `PossessionController@create` | `possessions.create` |
| GET `vaults/{vault}/possessions/types/{possessionType}/fields` | `PossessionTypeFieldController@index` | `possessions.typeFields.index` |
| GET `vaults/{vault}/possessions/contact-candidates` | `PossessionContactCandidateController@index` | `possessions.contactCandidates.index` |

`{possessionType}` uses `whereUuid`. `create` redirects to `possessions.index` with
`status` ":name is written down." (the entity create wording); an action refusal returns
`back()->withInput()->withErrors()`.

## Form fields (POST)

| Name | Inline rule |
| --- | --- |
| `name` | required, string, max:255 |
| `description` | nullable, string, max:2000 |
| `type` | required, uuid |
| `values` | nullable, array |
| `location` | nullable, uuid |
| `acquisition` | nullable, `Rule::enum(PossessionAcquisitionEnum::class)` |
| `acquired_from` | nullable, uuid |
| `acquired_on` | nullable, date |
| `declared_value` | nullable, numeric |
| `declared_value_currency` | nullable, `Rule::enum(CurrencyEnum::class)` |
| `tags` | nullable, array; `tags.*` string |
| `is_featured` | nullable, boolean |

Ids are looked up in the controller within the account (type, location) and the vault (contact),
answering a validation error on the field when missing, as `EntityController` does.

## Screen contract (what a view test may assert)

- `x-data="possessionTypePicker("`, `aria-haspopup="listbox"`, `role="listbox"`, `role="option"`, `aria-expanded`.
- `id="possession-type-fields"` with `x-merge="replace"`; the fragment carries `values[{uuid}]` names.
- `x-data="tagInput("`, hidden `tags[]`, each remove button labelled "Remove the tag :name".
- The footer bar: Cancel to `possessions.index`, submit with `form="possession-form"`.
- The photo area's reason text: "Photos come in a later version."
- All possessions: a link "Add a possession" to `possessions.new`.

## API (`api.v1.possessions.create`, `.update`, `.show`)

Request gains: `acquisition` (enum, nullable), `acquired_from_id` (contact uuid in the vault,
nullable), `acquired_on` (date, nullable), `declared_value` (numeric, nullable),
`declared_value_currency` (enum, required_with declared_value), `tags` (array of strings, max:50 each).
`update` is a full replace, so a left out field is cleared, tags included.

Resource gains:

```json
"acquisition": { "way": "gift", "acquired_from": { "id": "uuid", "name": "Andy Bernard" },
  "acquired_on": "2024-12-25", "declared_value": { "amount": "120.00", "currency": "usd" } },
"tags": [ { "id": "uuid", "name": "insured", "colour": "#3f5fd0" } ]
```

`acquired_from` is null for a trashed contact. `resources/docs/api/27-possessions.php` documents both.

## MCP tools

`CreatePossession`, `UpdatePossession` gain the same six inputs; `UpdatePossession` keeps what is
left out (its existing `array_key_exists` pattern), `tags` included. `GetPossession` returns the new
resource. No new tool.
