# Contract: Web Routes

In `routes/web.php`, signed in group, next to the locations. Prefix `settings/account/possession-types`,
written `…` below. Bound models resolve by uuid through `ResolvesModelInAccount`; a group or field
whose type is not the one in the URL is a 404 (`abort_unless`). Controllers in
`App\Http\Controllers\App\Settings\Account\PossessionTypes`.

| Verb | URI | Controller@method | Name |
| --- | --- | --- | --- |
| GET | `…` | `PossessionTypeController@index` | `settings.possessionTypes.index` |
| POST | `…` | `PossessionTypeController@create` | `settings.possessionTypes.create` |
| GET | `…/{possessionType}/edit` | `PossessionTypeController@edit` | `settings.possessionTypes.edit` |
| PUT | `…/{possessionType}` | `PossessionTypeController@update` | `settings.possessionTypes.update` |
| DELETE | `…/{possessionType}` | `PossessionTypeController@destroy` | `settings.possessionTypes.destroy` |
| POST | `…/{possessionType}/groups` | `PossessionFieldGroupController@create` | `settings.possessionFieldGroups.create` |
| PUT | `…/{possessionType}/groups/{possessionFieldGroup}` | `PossessionFieldGroupController@update` | `settings.possessionFieldGroups.update` |
| DELETE | `…/{possessionType}/groups/{possessionFieldGroup}` | `PossessionFieldGroupController@destroy` | `settings.possessionFieldGroups.destroy` |
| PUT | `…/{possessionType}/groups/{possessionFieldGroup}/position` | `PossessionFieldGroupPositionController@update` | `settings.possessionFieldGroupPosition.update` |
| POST | `…/{possessionType}/fields` | `PossessionFieldController@create` | `settings.possessionFields.create` |
| PUT | `…/{possessionType}/fields/{possessionField}` | `PossessionFieldController@update` | `settings.possessionFields.update` |
| DELETE | `…/{possessionType}/fields/{possessionField}` | `PossessionFieldController@destroy` | `settings.possessionFields.destroy` |
| PUT | `…/{possessionType}/fields/{possessionField}/position` | `PossessionFieldPositionController@update` | `settings.possessionFieldPosition.update` |
| GET | `…/{possessionType}/export` | `PossessionTypeExportController@index` | `settings.possessionTypeExport.index` |
| GET | `…/{possessionType}/export/file` | `PossessionTypeExportController@show` | `settings.possessionTypeExport.show` |
| GET | `…/import` | `PossessionTypeImportController@new` | `settings.possessionTypeImport.new` |
| POST | `…/import/check` | `PossessionTypeImportCheckController@create` | `settings.possessionTypeImportCheck.create` |
| POST | `…/import` | `PossessionTypeImportController@create` | `settings.possessionTypeImport.create` |

The import routes are declared before `{possessionType}` routes and throttled `20,1`, as the template
import is.

## Fields

| Route | Field | Validation (inline) |
| --- | --- | --- |
| type update | `name`, `colour` | `required, string, max:255`; `required, Rule::enum(VaultColourEnum::class)` |
| group update | `name` | `required, string, max:255` |
| field create | `group` | `nullable, uuid`, a group of this type |
| field update | `name`, `kind`, `options[]` | `required, string, max:255`; `Rule::enum(PossessionFieldKindEnum::class)`; `array, max:200`, each `nullable, string, max:255`, blanks dropped, `distinct` |
| position | `position` | `required, integer, min:1` |
| import | `file` | `required, string, max:` the reader's byte limit |

## Responses

Updates, positions and creations of groups and fields answer with the redrawn row or block for
`x-target` (research R8), or a redirect to `edit` without JavaScript. Type create redirects to `edit`;
type delete to `index` with a flag `status`; import to `edit` of the new type, or back with the
reader's problems as errors on `file`.

## Screens

`app.settings.account.possession-types.index`, `edit` (with `_group`, `_field` partials), `export`,
`import`. `x-settings.page current="possession-types"`; the sidebar's Possessions group and the
settings overview gain an entry with a count.
