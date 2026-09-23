# API contract

## `GET vaults/{vault}/contact-list-columns`

```json
{
  "data": [
    { "source": "email", "section": null, "field_key": null, "heading": null, "default_heading": "Email", "is_visible": true, "is_supplied": true },
    { "source": "field", "section": "9f0c…uuid", "field_key": "company", "heading": "Employer", "default_heading": "Company", "is_visible": true, "is_supplied": true }
  ],
  "meta": { "is_default": false }
}
```

Name is never listed. `is_default` is true when the vault has saved nothing.

## `PUT vaults/{vault}/contact-list-columns`

The body is `{ "columns": [ … ] }`, the same shape as `data` without `default_heading` and
`is_supplied`. A template field is named by the section `uuid`. The answer is 200 with the `GET`
body, or 422 on a refusal. Another account's vault answers 404.

## `GET vaults/{vault}/contacts` (extended)

Every item gains:

```json
"columns": [ { "source": "email", "section": null, "field_key": null, "values": ["rachel@ralphlauren.com", "rach.green@gmail.com"] } ]
```

It holds the vault's visible columns in order, each with its formatted values. An empty array means
no value. Every value is the text the list shows, dates included; the raw dates stay on the contact as
`last_interaction_at` and `created_at`. Nothing else in the response changes.

Documented in `resources/docs/api/06-contacts.php`.
