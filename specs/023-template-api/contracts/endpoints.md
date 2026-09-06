# Contract: template endpoints

Every path is under `/api/v1`, inside the `auth:sanctum` and `throttle:60,1` group in `routes/api.php`.
Route names take the `api.v1.` prefix from that group. `{template}` and `{page}` are uuids, `{version}`
is the version number within its template, `{section}` and `{field}` are ids constrained to `[1-9][0-9]*`.
Responses follow the envelope the sibling resources use: `type`, `id`, `attributes`, `links`, and a list
answers `data` plus a `meta` block of `page`, `per_page` and `total`.

| Phase | Verb | Path | Route name | Status |
| --- | --- | --- | --- | --- |
| P1 | GET | `templates` | `templates` | 200 |
| P1 | GET | `templates/{template}` | `templates.show` | 200 |
| P1 | GET | `templates/{template}/versions` | `templates.versions` | 200 |
| P1 | GET | `templates/{template}/versions/{version}` | `templates.versions.show` | 200 |
| P2 | POST | `templates` | `templates.create` | 201 |
| P2 | PUT | `templates/{template}` | `templates.update` | 200 |
| P2 | DELETE | `templates/{template}` | `templates.destroy` | 204 |
| P2 | PUT | `templates/{template}/activation` | `templates.activation.update` | 200 |
| P2 | PUT | `templates/{template}/position` | `templates.position.update` | 200 |
| P2 | POST | `templates/{template}/duplicate` | `templates.duplicate.create` | 201 |
| P3 | POST | `templates/{template}/versions` | `templates.versions.create` | 201 |
| P3 | PUT | `templates/{template}/versions/{version}/publication` | `templates.versions.publication.update` | 200 |
| P3 | DELETE | `templates/{template}/versions/{version}` | `templates.versions.destroy` | 204 |
| P4 | POST | `templates/{template}/versions/{version}/pages` | `templates.versions.pages.create` | 201 |
| P4 | PUT | `templates/{template}/versions/{version}/pages/{page}` | `templates.versions.pages.update` | 200 |
| P4 | DELETE | `templates/{template}/versions/{version}/pages/{page}` | `templates.versions.pages.destroy` | 204 |
| P4 | PUT | `templates/{template}/versions/{version}/pages/{page}/position` | `templates.versions.pages.position.update` | 200 |
| P4 | POST | `templates/{template}/versions/{version}/sections` | `templates.versions.sections.create` | 201 |
| P4 | PUT | `templates/{template}/versions/{version}/sections/{section}` | `templates.versions.sections.update` | 200 |
| P4 | DELETE | `templates/{template}/versions/{version}/sections/{section}` | `templates.versions.sections.destroy` | 204 |
| P4 | PUT | `templates/{template}/versions/{version}/sections/{section}/position` | `templates.versions.sections.position.update` | 200 |
| P4 | PUT | `templates/{template}/versions/{version}/sections/{section}/page` | `templates.versions.sections.page.update` | 200 |
| P5 | POST | `templates/{template}/versions/{version}/sections/{section}/fields` | `templates.versions.sections.fields.create` | 201 |
| P5 | PUT | `templates/{template}/versions/{version}/sections/{section}/fields/{field}` | `templates.versions.sections.fields.update` | 200 |
| P5 | DELETE | `templates/{template}/versions/{version}/sections/{section}/fields/{field}` | `templates.versions.sections.fields.destroy` | 204 |
| P5 | PUT | `templates/{template}/versions/{version}/sections/{section}/fields/{field}/position` | `templates.versions.sections.fields.position.update` | 200 |

## Notes that change a caller's code

- `templates.versions.show` is the only nested answer: it carries the version's pages, the sections placed
  on each and the fields of each, in order. Everything else answers one flat resource.
- `templates.versions.create` is idempotent. Called while a draft is open it answers `200` with that draft
  rather than `201` with a new one.
- `templates.versions.publication.update` takes a `change_note`, required unless the version is the first
  of its template. It refuses a version holding a page with no section, and a version with no page at all.
- Every write against a version that is published or retired answers `422`, not `404`: the version exists
  and the caller may read it, so hiding it would be a lie.
- A template the product supplied answers every write of phase two exactly as one the account made. `is_system`
  says where a template came from; it locks nothing, in the browser or here.
- `templates.destroy` answers `422` when the template still renders records, and `204` otherwise.
- A section placement is created by naming an existing section version, a page and a position. Creating the
  section itself is out of scope, so `sections.create` places rather than invents.
