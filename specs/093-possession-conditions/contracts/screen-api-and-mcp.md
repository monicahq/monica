# Contract: Screen, API Endpoints and MCP Tools

## Screen

`routes/web.php`, after the possession tag routes. Controllers under
`App\Http\Controllers\App\Settings\Account\PossessionConditions`: `PossessionConditionController`
(index, create, update, destroy) and `PossessionConditionPositionController` (update). View
`resources/views/app/settings/account/possession-conditions/index.blade.php` with `_row.blade.php`.

| Verb | URI | Name | Answer |
| --- | --- | --- | --- |
| GET | `settings/account/possession-conditions` | `settings.possessionConditions.index` | the screen |
| POST | `settings/account/possession-conditions` | `settings.possessionConditions.create` | back, `status` "“:name” is added." |
| PUT | `settings/account/possession-conditions/{possessionCondition}` | `settings.possessionConditions.update` | back, `status` "Saved." |
| PUT | `settings/account/possession-conditions/{possessionCondition}/position` | `settings.possessionConditionPosition.update` | back, `status` "“:name” is moved to position :position.", `moved`, `direction` |
| DELETE | `settings/account/possession-conditions/{possessionCondition}` | `settings.possessionConditions.destroy` | back, `status` "“:name” is deleted." |

`{possessionCondition}` is `whereUuid`, bound through `ResolvesModelInAccount`. Inputs: `name`
(required, string, max 50), `colour` (required, `Rule::enum(VaultColourEnum::class)`), `icon`
(nullable, string, `Rule::in(Section::ICONS)`), `position` (required, integer, min 1). Error bags:
`newPossessionCondition` for create, `possessionCondition{uuid}` for update.

What the screen carries (asserted by the view test):

- `x-settings.page` with `current="possession-conditions"`; `x-page-header` with the title and the
  description.
- The add form: `name` with `maxlength="50"`, `x-mark-picker` (hidden `icon` and `colour`), "Add
  condition", and a preview hidden from screen readers (`aria-hidden="true"`).
- The list `id="possession-conditions"`, and inside it a `<div role="list" x-data="sortableRows">`
  labelled "Your conditions" whose rows are `role="listitem"`, and the count line. The sortable
  lists of the application are `div`s, and the row's edit form has to sit beside its draggable
  row rather than inside it, which a list item holding both satisfies.
- Each row: the swatch with its colour name for screen readers, the icon when there is one, the
  name, "Move :name up" and "Move :name down" (absent on the first and last row), "Edit" (aria-label
  "Edit :name") and "Delete" (aria-label "Delete :name") opening `x-modal.warning` titled "Delete
  the condition “:name”?".
- The row form: `name`, `x-mark-picker`, "Save changes" and "Cancel"; Escape closes it.
- With no condition: `x-empty-state` "No conditions yet".

## API

`routes/api.php`, `v1` group, after the possession tag routes. Controllers under
`App\Http\Controllers\Api\PossessionConditions`. `{possessionCondition}` constrained to a uuid.

| Verb | URI | Name | Answer |
| --- | --- | --- | --- |
| GET | `possession-conditions` | `api.v1.possessionConditions` | 200, `data` + `meta {page, per_page, total}`, by position |
| GET | `possession-conditions/{possessionCondition}` | `api.v1.possessionConditions.show` | 200 |
| POST | `possession-conditions` | `api.v1.possessionConditions.create` | 201; `name`, `colour?` (Blue when left out), `icon?` |
| PUT | `possession-conditions/{possessionCondition}` | `api.v1.possessionConditions.update` | 200; `name`, `colour` required, `icon` nullable (left out or null removes it) |
| PUT | `possession-conditions/{possessionCondition}/position` | `api.v1.possessionConditions.position` | 200; `position` |
| DELETE | `possession-conditions/{possessionCondition}` | `api.v1.possessionConditions.destroy` | 204 |

Answer: `type: possession_condition`, `id`, `attributes {name, colour, icon, position, created_at,
updated_at}`, `links {self, web}`.

| Refusal | Answer |
| --- | --- |
| Condition of another account, or unknown | 404 |
| Empty name, longer than 50, or taken ignoring case | 422 on `name` |
| Colour outside the palette | 422 on `colour` |
| Icon outside the catalogue | 422 on `icon` |
| Position missing or below 1 | 422 on `position` |

Reference page `resources/docs/api/29-possession-conditions.php`, written with the `api-docs-writer`
skill.

## MCP

`app/Mcp/Tools`, registered in `MonicaServer` after the possession tag tools, each
`MirrorsAnApiCapability`: `list-possession-conditions` and `get-possession-condition`
(`#[IsReadOnly]`), `create-possession-condition`, `update-possession-condition` (keeps what is left
out; `icon: ""` removes the icon), `set-possession-condition-position`,
`delete-possession-condition` (`#[IsDestructive]`). Unknown condition: "That possession condition
does not exist." `McpDocsViewModel` gains "Possession conditions" and "Possession conditions,
changing".
