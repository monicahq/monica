# Contract: Web Routes

All in `routes/web.php`, inside the signed in group. `{location}` is the
uuid, bound through `ResolvesModelInAccount`. Controllers live in `App\Http\Controllers\App\Settings\Account\Locations`.

| Verb | URI | Controller | Name |
| --- | --- | --- | --- |
| GET | `settings/account/locations` | `LocationController@index` | `settings.locations.index` |
| GET | `settings/account/locations/new` | `LocationController@new` | `settings.locations.new` |
| GET | `settings/account/locations/{location}/new` | `ChildLocationController@new` | `settings.locations.children.new` |
| POST | `settings/account/locations` | `LocationController@create` | `settings.locations.create` |
| GET | `settings/account/locations/{location}/edit` | `LocationController@edit` | `settings.locations.edit` |
| PUT | `settings/account/locations/{location}` | `LocationController@update` | `settings.locations.update` |
| DELETE | `settings/account/locations/{location}` | `LocationController@destroy` | `settings.locations.destroy` |

The three GET form routes render `app.settings.account.locations.index` with the panel open (research R3).

## Form fields

| Field | Validation (inline in the controller) |
| --- | --- |
| `name` | `['required', 'string', 'max:255']` |
| `parent` | `['nullable', 'uuid', Rule::exists('locations', 'uuid')->where('account_id', $user->account_id)]` |
| `emoji` | `['nullable', 'string', 'max:32', new SingleEmoji]` |
| `address` | `['nullable', 'string', 'max:2000']` |

The emoji radio group and the free field share the name `emoji`; the free field is used when filled.

## Responses

| Case | Response |
| --- | --- |
| Created or updated | Redirect to `settings.locations.index`, flash `status` |
| Validation fails | Redirect back to the form URL with errors and old input |
| Cycle refused by `UpdateLocation` | Redirect back, error on `parent` |
| Delete refused by the policy | Redirect to `settings.locations.index`, error carrying the policy's message |
| Deleted | Redirect to `settings.locations.index`, flash `status` |
| Location of another account | 404, from `ResolvesModelInAccount` |

## Screen

`x-settings.page` with `current="locations"`. The settings sidebar gains a Locations entry, and the
settings overview a Locations row with a count.
