# Contract: adding a new section through the API

`POST /api/v1/templates/{template}/versions/{version}/sections`, route
`api.v1.templates.versions.sections.create`. Existing endpoint, extended.

## Input

| Field | Rule |
| --- | --- |
| section_id | required without `name`, prohibited with `name`; uuid of a section of the account (as today) |
| name | required without `section_id`, prohibited with `section_id`; string, max 255 |
| icon | nullable, one of `Section::ICONS`; prohibited without `name` |
| colour | nullable, one of `VaultColourEnum::values()`; prohibited without `name` |
| section_version_number | as today; prohibited with `name` |
| page_id | as today |
| title_override | as today |
| is_collapsed_by_default | as today |

## Behaviour

- With `section_id`: unchanged.
- With `name`: a version that is not a draft is refused with 422 on `version` before anything is
  written. Otherwise `CreateSection` then `AddSectionToStructureVersion` run, as the web builder does.
- 201 with `StructureVersionSectionResource` in both cases.

## Published versions (research R4)

If the confirming test shows the section and field endpoints answer 500 on a published version, each
of them catches `PublishedVersionIsFrozen` and answers 422 on `version` with its message.

## Documentation

`resources/docs/api/11-templates.php` describes the new inputs and one example of each form. The
existing documented-versus-registered route check needs no change, since no route is added.
