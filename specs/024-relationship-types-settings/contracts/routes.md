# Routes

Eleven routes in `routes/web.php`, inside the signed in group, in the shape Entity types already uses.

| Verb | URL | Name | Controller |
| --- | --- | --- | --- |
| GET | `settings/account/relationship-types` | `settings.relationshipTypes.index` | `RelationshipTypeController@index` |
| GET | `settings/account/relationship-types/new` | `settings.relationshipTypes.new` | `RelationshipTypeController@new` |
| POST | `settings/account/relationship-types` | `settings.relationshipTypes.create` | `RelationshipTypeController@create` |
| GET | `settings/account/relationship-types/{relationshipType}/edit` | `settings.relationshipTypes.edit` | `RelationshipTypeController@edit` |
| PUT | `settings/account/relationship-types/{relationshipType}` | `settings.relationshipTypes.update` | `RelationshipTypeController@update` |
| DELETE | `settings/account/relationship-types/{relationshipType}` | `settings.relationshipTypes.destroy` | `RelationshipTypeController@destroy` |
| PUT | `settings/account/relationship-types/{relationshipType}/position` | `settings.relationshipTypePosition.update` | `RelationshipTypePositionController@update` |
| POST | `settings/account/relationship-types/categories` | `settings.relationshipCategories.create` | `RelationshipCategoryController@create` |
| PUT | `settings/account/relationship-types/categories/{relationshipCategory}` | `settings.relationshipCategories.update` | `RelationshipCategoryController@update` |
| DELETE | `settings/account/relationship-types/categories/{relationshipCategory}` | `settings.relationshipCategories.destroy` | `RelationshipCategoryController@destroy` |
| PUT | `settings/account/relationship-types/categories/{relationshipCategory}/position` | `settings.relationshipCategoryPosition.update` | `RelationshipCategoryPositionController@update` |

Notes.

- `new` and `edit` render the form screen; `create` and `update` redirect back to `index` with a status.
- The category routes answer Alpine Ajax from the list screen and redirect back to `index`.
- Both position endpoints take a one-based `position` and reorder the siblings around it, as `UpdateEntityTypePosition` does.
- The two `{relationshipType}` and `{relationshipCategory}` bindings resolve by id and refuse another account's record, so every one of these returns not found across accounts.
