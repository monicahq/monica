# Contract: the Monica export file

One UTF-8 JSON document, `monica-export-YYYY-MM-DD.json`. The file opens with its `version`: `2.0-preview.1` here, where a Monica 4 export says `1.0-preview.1`. Readers MUST refuse a version they do not know (FR-017), and MUST ignore keys they do not know.

```json
{
  "version": "2.0-preview.1",
  "app_version": "1.4.0",
  "exported_at": "2026-10-18T09:00:00Z",
  "account": {
    "structures": [
      { "template": "uuid", "version_number": 3, "document": { "monica": { "format": "template", "format_version": 1 } } }
    ],
    "life_event_types": [ { "uuid": "…", "key": "call", "version_number": 2, "document": { "monica": { "format": "life_event_type" } } } ],
    "relationship_types": [ { "uuid": "…", "key": null, "category": "family", "name": "Godparent", "role_a": "godparent", "role_b": "godchild", "is_symmetric": false } ],
    "vaults": [
      {
        "uuid": "…", "name": "Family", "description": null, "colour": "blue",
        "contacts": [ { "uuid": "…", "created_at": "…", "is_me": false, "is_partial": false, "is_favourite": true, "is_archived": false,
          "nickname": null, "maiden_name": null, "name_representations": [ { "is_display": true, "kind": "written", "given_name": "Pam", "…": "…" } ],
          "structure": { "template": "uuid", "version_number": 3 }, "avatar_file": "uuid", "values": [ "…value…" ] } ],
        "entities": [ { "uuid": "…", "entity_type": "animals", "name": "Sprinkles", "is_archived": false, "structure": { "…": "…" }, "avatar_file": null, "values": [ "…" ] } ],
        "relationships": [ { "uuid": "…", "type": "parent_child", "contact": "uuid", "other_contact": "uuid", "status": "current", "started_on": null, "ended_on": null, "notes": null } ],
        "life_events": [ { "uuid": "…", "type": "call", "happened_on": "2026-03-01", "date_precision": "day", "title": "…", "narrative": "…", "participants": [ "uuid" ],
          "structure": { "life_event_type": "call", "version_number": 2 }, "values": [ "…" ] } ],
        "reminders": [ { "uuid": "…", "record": "uuid", "name": "…", "source": "manual", "value": null, "starts_on": "…", "cadence": "yearly", "frequency_interval": 1, "time_of_day": "09:00", "timezone": "UTC", "is_active": true } ],
        "files": [ { "uuid": "…", "original_name": "scan.pdf", "mime_type": "application/pdf", "size_in_bytes": 48211, "checksum": "sha256…", "data": "base64…" } ]
      }
    ]
  }
}
```

## A value

```json
{ "section": "contact-information", "field": "email", "row": 0, "parent_field": "emails",
  "value": "jim@dundermifflin.com", "label": "work", "label_wording": null,
  "year_is_unknown": false, "month_is_unknown": false,
  "file": null, "related_contact": null, "related_entity": null, "list_items": [] }
```

- A field is found by `section` and `field` keys inside the record's structure version, which is unique there (`fields` unique on section version and key). A life event's values are found in the version its `structure` names.
- `label` and `list_items` name items of the value list the structure document carries, by key, or by label for an item that has none.
- `file` names an entry of the vault's `files`; `related_contact` and `related_entity` name uuids of the same vault.
- A relationship's or a life event's `type` is the key of a type every instance ships, or the uuid of a type the account made itself.
- `life_event_types` holds one document per version a life event is on, built in types included, since a person may add fields to one. A type is named by its `key`, or by its `uuid` when it has none.
- An import keeps its own type of that key or name when it holds every field of the version, and otherwise writes a copy that gains one version per exported version.
- A reminder's `record` is a contact, entity or life event of the vault. When `source` is `field_value`, `value` names the date it reads as `{section, field, row}`.
- Every record also carries `created_at`; a life event also its `ended_on`, time, duration, timezone, location and sharing flags.
- The account carries no identifier of its own: it has none outside the instance.
- Sensitive values are written like any other (FR-014 warns before the export starts).

## Left out

Credentials, passkeys, API keys, connected providers, share links, notification channels, billing, the changelog, record snapshots, detached values and soft deleted records (FR-013).
