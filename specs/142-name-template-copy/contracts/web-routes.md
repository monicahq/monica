# Contract: web routes for copying a template

## GET `settings/account/templates/{template}/copy` (new)

- **Name**: `settings.templateCopy.new`
- **Controller**: `[TemplateCopyController::class, 'new']`
- **Binding**: `{template}` by slug, scoped to the account by `ResolvesModelInAccount`; another account's template answers 404
- **Answers**: 200 with the copy form. Fields: `name` (prefilled), `slug` (prefilled). Submit posts to `settings.templateCopy.create`. A link back to `settings.templates.index`

## POST `settings/account/templates/{template}/copy` (existing, now validates)

- **Name**: `settings.templateCopy.create`
- **Body**:

  | Field | Rules |
  | --- | --- |
  | `name` | `required`, `string`, `max:255` |
  | `slug` | `nullable`, `string`, `max:64`, `regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/`, unique in `templates.slug` for the account |

- **Messages**: the same as the creation form. `slug.regex`: "Use lowercase letters, numbers and dashes only, with no spaces and no accents." `slug.unique`: "Another template in this account already uses that slug."
- **Success**: redirect to `settings.templateBuilder.index` of the copy, flash `status` "The template is copied." and the existing `status_description`
- **Refusal**: redirect back to the form with `$errors` and the old input
- **Template with no version**: `ModelNotFoundException` from the action, 404, as today

## Unchanged

- `POST /api/templates/{template}/duplicate`: still accepts optional `name` and `slug`, still makes a taken slug unique by adding a number
- MCP tool `DuplicateTemplate`: unchanged
