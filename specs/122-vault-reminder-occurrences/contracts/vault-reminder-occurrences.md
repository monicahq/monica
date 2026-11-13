# Contract: vault reminder occurrences

## API

`GET /api/v1/vaults/{vault}/reminder-occurrences` (route `api.v1.reminderOccurrences`)

Query: `category`, `kind`, `days`, `page`, `per_page` (see data-model.md).

Answers: 200 with the list; 404 for a vault outside the account; 422 for an unknown or out of range
parameter; 401 without a key.

```json
{
  "data": [
    {
      "type": "reminder_occurrence",
      "id": "a9d3f6e1-2b47-4c08-8e5a-17f4c0b9d262",
      "attributes": {
        "reminder": "4e7b2c90-3d15-4a86-9f21-8b0c6e5d7a13",
        "name": "Michael's birthday",
        "group": null,
        "date": "2026-11-15",
        "scheduled_for": 1794733200,
        "category": "upcoming",
        "answer": null,
        "answered_at": null,
        "cancelled_reason": null,
        "subject": { "type": "contact", "id": "…", "name": "Michael Scott" },
        "age": 63
      },
      "links": {
        "reminder": "…/vaults/dunder-mifflin/contacts/michael-scott/reminders/4e7b…",
        "answer": "…/vaults/dunder-mifflin/contacts/michael-scott/reminder-occurrences/a9d3…/answer"
      }
    },
    {
      "type": "birthday",
      "id": null,
      "attributes": {
        "date": "2026-11-17",
        "category": "upcoming",
        "subject": { "type": "contact", "id": "…", "name": "Dwight Schrute" },
        "age": 52
      },
      "links": { "contact": "…/vaults/dunder-mifflin/contacts/dwight-schrute" }
    }
  ],
  "meta": { "page": 1, "per_page": 15, "total": 2 }
}
```

- `subject.type` is `contact`, `group` or `entity`; `subject` is null when the record is gone.
- `age` is null except for a birthday whose year is known and whose field the key may read.
- `links.answer` is null for an occurrence that cannot be answered (computed, answered, cancelled).
- Order: by date, earliest first; history by answer, most recent first.

## MCP

Tool `list-vault-reminder-occurrences`, read only, mirrors `api.v1.reminderOccurrences`.
Arguments: `vault` (required), `category`, `kind`, `days`, `page`, `per_page`, with the same rules.
Returns the same JSON as the API. An unknown vault answers with the tool error "That vault does not
exist.", as every vault tool does.
