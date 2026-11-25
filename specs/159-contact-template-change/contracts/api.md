# Contract: API

Both endpoints sit in the `api.v1` group, behind the same authentication and rate limit as the other
contact endpoints ("Sixty requests per minute, per user, shared by every key and device"). `{vault}` and
`{contact}` are addressed as every contact endpoint addresses them. A vault, contact or template of
another account answers 404.

## Preview: `GET /vaults/{vault}/contacts/{contact}/template-preview`

Route name `api.v1.contacts.templatePreview.show`, `Api\ContactTemplatePreviewController@show`.

| Query parameter | Type | Required | Meaning |
| --- | --- | --- | --- |
| `template` | uuid | yes | The template to preview, as `list-templates` and `GET /templates` give it |
| `structure_version_id` | integer | no | A published version of that template; its published version when left out |

200, nothing written, nothing logged:

```json
{
  "data": {
    "type": "template_change_preview",
    "attributes": {
      "is_change": true,
      "from": {"template": "b6d…", "template_name": "Basic contact", "structure_version_id": 4, "version_number": 2},
      "to": {"template": "9f1…", "template_name": "Coworker", "structure_version_id": 11, "version_number": 1},
      "set_aside": [{"field_id": 52, "label": "Favourite paper", "section": "Information", "count": 1}],
      "brought_back": [],
      "reminders_stopped": [{"id": "3c2…", "label": "Dwight’s work anniversary"}],
      "reminders_restarted": [],
      "hidden_count": 1
    }
  }
}
```

- `is_change` is false, and every list empty, when the contact is already on that version.
- Each listed field carries `count`, the number of the contact's answers it holds.
- An answer in a sensitive field is listed only for a key with the `sensitive-fields` scope; otherwise it is counted in `hidden_count` (research R10).

422, with the action's sentence on `template`:

- the template describes another kind of record;
- the template is turned off;
- the template has no published version;
- `structure_version_id` is not a published version of that template.

A `structure_version_id` that is not a version of the account answers 404.

## Change: `PUT /vaults/{vault}/contacts/{contact}/template`

Route name `api.v1.contacts.template.update`, `Api\ContactTemplateController@update`.

| Body field | Type | Required | Meaning |
| --- | --- | --- | --- |
| `template` | uuid | yes | The template to put the contact on |
| `structure_version_id` | integer | no | A published version of that template; its published version when left out |

200 with the contact object, as `GET /vaults/{vault}/contacts/{contact}` returns it, on its new template.
The refusals are those of the preview, with the same sentences. Sending the template and version the
contact already uses answers 200 and changes and records nothing.

## Unchanged: `PUT /vaults/{vault}/contacts/{contact}/template-version`

Same behaviour, same documentation: a published version of the contact's own template, 404 for anything
else.
