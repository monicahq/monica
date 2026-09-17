# Contract: the endpoints this feature adds

Ten endpoints, all inside the `auth:sanctum` group of `routes/api.php`, all answering 404 for a
record outside the caller's account. Paths are relative to `/api/v1`.

| Method | Path | Route name | Controller, method | Answers |
| --- | --- | --- | --- | --- |
| GET | `/activity-types` | `activityTypes` | `ActivityTypeController@index` | 200, paged |
| GET | `/activity-types/{activityType}` | `activityTypes.show` | `ActivityTypeController@show` | 200 |
| POST | `/activity-types` | `activityTypes.create` | `ActivityTypeController@create` | 201 |
| PUT | `/activity-types/{activityType}` | `activityTypes.update` | `ActivityTypeController@update` | 200 |
| DELETE | `/activity-types/{activityType}` | `activityTypes.destroy` | `ActivityTypeController@destroy` | 204, or 422 for a supplied type |
| PUT | `/activity-types/{activityType}/activation` | `activityTypes.activation.update` | `ActivityTypeActivationController@update` | 200 |
| PUT | `/activity-types/{activityType}/position` | `activityTypes.position.update` | `ActivityTypePositionController@update` | 200 |
| GET | `/activity-types/{activityType}/fields` | `activityTypes.fields` | `ActivityTypeFieldController@index` | 200 |
| PUT | `/activity-types/{activityType}/fields` | `activityTypes.fields.update` | `ActivityTypeFieldController@update` | 200 |
| GET | `/activity-categories/{activityCategory}/types` | `activityCategories.types` | `ActivityCategoryTypeController@index` | 200, paged |

Every `{activityType}` and `{activityCategory}` is constrained `[0-9a-fA-F-]{36}`, so a path that is
not a uuid answers 404 without a query being run.

## What a type looks like

`ActivityTypeResource`, type `activity_type`, id the uuid, `links.self` the show route. Attributes,
in this order: `activity_category_id` (uuid), `name`, `description`, `icon`, `colour`, `position`,
`is_system`, `is_active`, `created_at`, `updated_at`. Timestamps are seconds since 1970, and
`updated_at` is null until the type changes.

## What each write takes

| Endpoint | Body |
| --- | --- |
| create | `name` required, `activity_category_id` required and of the caller's account, `description`, `icon`, `colour` optional |
| update | the same four, plus the category, which may move the type |
| activation | `is_active`, required, a boolean sent as the state wanted rather than a toggle |
| position | `position`, required, integer, at least 1 |
| fields | `groups[]` of `{placement_id, name, fields[]}` and a top level `fields[]` for the ungrouped |

`icon` is one of `Section::ICONS`, `colour` one of `VaultColourEnum`, and each field's `type` one of
`FieldTypeEnum`. A field carries `key`, `label`, `type`, `is_required`, `help_text`, `settings` and
`choices`, as `ReplaceActivityTypeFields` documents them.

## What the fields read answers

A list of `StructureVersionSectionResource`, one per group of the type's published version, in their
recorded order, each carrying its `id`, its title and its fields. That `id` is the `placement_id` the
write takes back, so a caller reads a layout, edits it and sends it without inventing anything. A
type nobody has given fields to answers an empty list, never 404.

## What changes on the categories

`GET /activity-categories/{activityCategory}` and its index are untouched in shape. Only the
reference prose for `activity_type_count` changes, from a promise that it always answers 0 to what it
actually counts.
