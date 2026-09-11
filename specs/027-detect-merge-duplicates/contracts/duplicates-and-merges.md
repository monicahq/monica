# Contract: the duplicate and merge surfaces

Columns are in [../data-model.md](../data-model.md). Every route sits behind `auth` and `active`,
resolves its vault against the account, and answers 404 when either compartment fails.

## Routes

Controllers are named once and exist twice, under `App` for the web route and under `Api` for the JSON
one. The URLs are identical, so only the web route name is given.

| Verb | URL | Controller, method | Name | API answer |
| --- | --- | --- | --- | --- |
| GET | `vaults/{vault}/duplicates` | `ContactDuplicateController@index` | `vaults.duplicates.index` | 200, paginated `ContactDuplicateCandidateResource` |
| PUT | `vaults/{vault}/duplicates/{candidate}/dismissal` | `ContactDuplicateDismissalController@update` | `vaults.duplicates.dismissal.update` | 200, the candidate |
| GET | `vaults/{vault}/contacts/{contact}/merge` | `ContactMergeController@new` | `contacts.merge.new` | none, it is a form |
| GET | `vaults/{vault}/contacts/{contact}/merge/{other}` | `ContactMergeController@show` | `contacts.merge.show` | 200, `ContactMergePreviewResource` |
| POST | `vaults/{vault}/contacts/{contact}/merge/{other}` | `ContactMergeController@create` | `contacts.merge.create` | 201, `ContactMergeResource` |
| DELETE | `vaults/{vault}/merges/{contactMerge}` | `ContactMergeController@destroy` | `vaults.merges.destroy` | 204 |

`show` is the preview and warns before the confirmation, `destroy` is the undo. The catch-all
`contacts.show` route already ends `routes/web.php`, so both `merge` routes are declared above it.

## Payloads

The merge accepts one resolution per conflict the preview reported, `keep` being `kept` or `other`.
Every reported conflict MUST appear exactly once and no other MUST appear. Either failure is 422.

```json
{ "resolutions": [{ "field_key": "email", "row": null, "keep": "other" },
                  { "field_key": "phone", "row": 2, "keep": "kept" }] }
```

The preview reports what would happen and nothing else.

```json
{
  "kept": { "uuid": "...", "name": "Jim Halpert" },
  "other": { "uuid": "...", "name": "James Halpert" },
  "conflicts": [{ "field_key": "email", "label": "Email", "row": null,
                  "kept_value": "jim@dundermifflin.com", "other_value": "james@dundermifflin.com" }],
  "moves": [{ "kind": "field_value", "count": 4 }, { "kind": "reminder", "count": 1 }],
  "collisions": [],
  "set_aside": [{ "field_key": "loyalty_number", "reason": "no such field on the surviving structure" }]
}
```

## Errors

| Case | Answer |
| --- | --- |
| Either contact in another account or vault | 404 |
| One contact named twice, or the absorbed one is `is_me` | 422 |
| A contact changed since the preview was built | 409, and the web preview is rebuilt |
| An undo of an undone merge, or one whose absorbed contact is gone | 422 |

## Also changed

`FieldResource` gains `is_identifying`, `Api\FieldController` accepts it on create and update, and the
reference pages under `resources/docs/api` gain the five routes and the field attribute.
