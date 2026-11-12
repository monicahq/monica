# Contract: Screen, API Endpoints and MCP Tools

## Screen

`routes/web.php`, next to the possession type routes. Controller
`App\Http\Controllers\App\Settings\Account\PossessionTags\PossessionTagController`. View
`resources/views/app/settings/account/possession-tags/index.blade.php`, with `_row.blade.php` and
`_colours.blade.php`.

| Verb | URI | Name | Answer |
| --- | --- | --- | --- |
| GET | `settings/account/possession-tags` | `settings.possessionTags.index` | the screen |
| POST | `settings/account/possession-tags` | `settings.possessionTags.create` | back to the screen, `status` "“:name” is added." |
| PUT | `settings/account/possession-tags/{possessionTag}` | `settings.possessionTags.update` | back to the screen, `status` "Saved." |
| DELETE | `settings/account/possession-tags/{possessionTag}` | `settings.possessionTags.destroy` | back to the screen, `status` "“:name” is deleted." |

`{possessionTag}` is `whereUuid`, bound through `ResolvesModelInAccount`. Inputs: `name` (required,
string, max 50), `colour` (required, `Rule::enum(VaultColourEnum::class)`). A refusal returns to the
screen with the error on `name`, and the row that sent it opens its form again.

What the screen carries (asserted by the view test):

- `x-settings.page` with `current="possession-tags"`; `x-page-header` with the title and the
  description.
- The add form: an input `name` with `maxlength="50"`, a `<fieldset>` whose `<legend>` is "Colour"
  holding eight radios `name="colour"`, "Add tag", and a preview of the tag hidden from screen readers
  (`aria-hidden="true"`), since the name and the colour are already announced by their controls.
- The list `id="possession-tags"`, a `<ul>` labelled "Your tags", and the count line.
- Each row: the swatch with its colour name for screen readers, the name, "Edit" (with
  `aria-label` "Edit :name"), and "Delete" (`aria-label` "Delete :name") opening `x-modal.warning` titled "Delete the tag “:name”?".
- The row form: `name`, the same colour fieldset, "Save changes" and "Cancel"; Escape closes it.
- With no tag: `x-empty-state` "No tags yet".

## API

`routes/api.php`, `v1` group, next to `possession-types`. Controller
`App\Http\Controllers\Api\PossessionTags\PossessionTagController`. `{possessionTag}` constrained to a
uuid.

| Verb | URI | Name | Answer |
| --- | --- | --- | --- |
| GET | `possession-tags` | `api.v1.possessionTags` | 200, `data` + `meta {page, per_page, total}`, sorted by name |
| GET | `possession-tags/{possessionTag}` | `api.v1.possessionTags.show` | 200 |
| POST | `possession-tags` | `api.v1.possessionTags.create` | 201; `name`, `colour?` (hexadecimal vault colour, Blue when left out) |
| PUT | `possession-tags/{possessionTag}` | `api.v1.possessionTags.update` | 200; `name`, `colour`, both required |
| DELETE | `possession-tags/{possessionTag}` | `api.v1.possessionTags.destroy` | 204 |

Answer: `type: possession_tag`, `id`, `attributes {name, colour, created_at, updated_at}`,
`links {self, web}` (`web` is the settings screen).

| Refusal | Answer |
| --- | --- |
| Tag of another account, or an unknown one | 404 |
| Empty name, longer than 50, or taken ignoring case | 422 on `name` |
| A colour outside the palette | 422 on `colour` |

Reference page `resources/docs/api/28-possession-tags.php`, written with the `api-docs-writer`
skill.

## MCP

`app/Mcp/Tools`, registered in `MonicaServer` after the possession tools, each
`MirrorsAnApiCapability`: `list-possession-tags` and `get-possession-tag` (`#[IsReadOnly]`),
`create-possession-tag`, `update-possession-tag` (keeps what is left out, 087 R6),
`delete-possession-tag` (`#[IsDestructive]`). Unknown tag: "That possession tag does not exist."
`McpDocsViewModel` gains the groups "Possession tags" and "Possession tags, changing".
