# Web routes

All under the `settings` group in `routes/web.php`, guarded like every other account setting. Model
binding is numeric, as the relationship type routes are, and `resolveRouteBinding()` refuses a record
of another account before a controller runs.

| Verb and path | Name | What it does |
| --- | --- | --- |
| GET `settings/account/activity-types` | `settings.activityTypes.index` | The rail, and every type of the account |
| GET `settings/account/activity-types/categories/{activityCategory}` | `settings.activityTypes.category` | The rail, with one category selected |
| POST `settings/account/activity-types/categories` | `settings.activityCategories.create` | Add a category, from the modal |
| PUT `settings/account/activity-types/categories/{activityCategory}` | `settings.activityCategories.update` | Rename it |
| DELETE `settings/account/activity-types/categories/{activityCategory}` | `settings.activityCategories.destroy` | Delete it, if the policy allows |
| PUT `settings/account/activity-types/categories/{activityCategory}/position` | `settings.activityCategoryPosition.update` | Move it in the rail |
| PUT `settings/account/activity-types/categories/{activityCategory}/activation` | `settings.activityCategoryActivation.update` | Switch it off or back on |
| GET `settings/account/activity-types/new` | `settings.activityTypes.new` | The add screen |
| POST `settings/account/activity-types` | `settings.activityTypes.create` | Save it, then the list or its fields |
| GET `settings/account/activity-types/{activityType}/edit` | `settings.activityTypes.edit` | The edit screen |
| PUT `settings/account/activity-types/{activityType}` | `settings.activityTypes.update` | Name, category, description, icon, colour |
| DELETE `settings/account/activity-types/{activityType}` | `settings.activityTypes.destroy` | Delete it, if the policy allows |
| PUT `settings/account/activity-types/{activityType}/position` | `settings.activityTypePosition.update` | Move it inside its category |
| PUT `settings/account/activity-types/{activityType}/activation` | `settings.activityTypeActivation.update` | Offer it, or stop offering it |

The custom fields of one type, on the same engine the template builder calls:

| Verb and path | Name |
| --- | --- |
| GET `settings/account/activity-types/{activityType}/custom-fields` | `settings.activityTypeFields.index` |
| POST `settings/account/activity-types/{activityType}/custom-fields/sections` | `settings.activityTypeFieldSections.create` |
| PUT, DELETE `.../custom-fields/sections/{structureVersionSection}` | `settings.activityTypeFieldSections.update`, `.destroy` |
| PUT `.../custom-fields/sections/{structureVersionSection}/position` | `settings.activityTypeFieldSectionPosition.update` |
| GET, POST `.../custom-fields/sections/{structureVersionSection}/fields` | `settings.activityTypeFields.new`, `.create` |
| PUT, DELETE `.../custom-fields/sections/{structureVersionSection}/fields/{field}` | `settings.activityTypeFields.update`, `.destroy` |
| PUT `.../custom-fields/sections/{structureVersionSection}/fields/{field}/position` | `settings.activityTypeFieldPosition.update` |
| PUT `.../custom-fields/{structureVersion}/publication` | `settings.activityTypeFieldPublication.update` |

`new` and `create` on the add screen: the form posts one payload, and a named submit value decides
whether the answer redirects to the list or to the type's custom fields (research D3).

Route parameter names are camelCase and the public paths kebab-case, as `.claude/rules/code-style.md`
requires. `custom-fields` is the only word the address bar shows for the engine, never `structure`,
`template` or `version` (FR-014).
