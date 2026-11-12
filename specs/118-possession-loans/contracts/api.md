# Contract: API and assistant

Prefix `v1/vaults/{vault}/possessions/{possession}`. Bearer token; another vault, account or
possession answers 404. Validation errors answer 422.

| Method | Path | Answer |
| --- | --- | --- |
| GET | `loans` | 200, `data` list newest first, not paginated |
| POST | `loans` | 201, one loan |
| GET | `loans/{loan}` | 200 |
| PUT | `loans/{loan}` | 200, one loan; what is left out keeps its value, as the transaction PUT |
| DELETE | `loans/{loan}` | 204; the contact stays; linked provenance events are deleted |

## Loan

```json
{ "data": {
  "type": "possession_loan", "id": "uuid",
  "attributes": {
    "direction": "lent", "status": "active", "is_overdue": false,
    "contact_id": "uuid", "contact_name": "Dwight Schrute",
    "started_on": "2024-06-12", "due_on": "2024-06-15", "returned_on": null,
    "condition_out_id": "uuid", "condition_back_id": null,
    "deposit_amount": "500.00", "deposit_currency": "usd",
    "purpose": "Worn at his cousin Mose's wedding", "is_in_provenance": true,
    "created_at": 1773446400 },
  "links": { "self": "...", "web": "..." } } }
```

`direction`, `status` and `started_on` are required on POST, with exactly one of `contact_id` (a
contact uuid of the vault) or `new_contact` (`{ "first_name": "Nate", "last_name": null }`).
`contact_id` and `contact_name` are null once the contact is deleted. A second active loan answers 422
on `status`. `is_overdue` is derived.

## Assistant

New tools `list-possession-loans`, `get-possession-loan`, `create-possession-loan`,
`update-possession-loan` and `delete-possession-loan`, each calling the endpoint's action, as the
provenance tools do. The contact is named by uuid, found first with the existing contact search tool.
