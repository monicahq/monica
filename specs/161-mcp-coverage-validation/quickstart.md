# Quickstart: validating the change

Shapes are in [contracts/api.md](contracts/api.md). Run from the repository root.

## Automated

```bash
php artisan test --compact tests/Unit/Helpers/FieldValueRulesTest.php tests/Unit/Helpers/DurationTest.php
php artisan test --compact --filter=ValueList
php artisan test --compact tests/Feature/Controllers/Api/ContactSectionControllerTest.php
composer test:parallel      # FieldValueRules is under every section form
php artisan monica:localize --check
```

## By hand, against a local instance with an API key

| Check | Expected |
| --- | --- |
| `PUT …/sections/{id}` with a phone row `{"13": "+1 570 555 0100"}` (child id beside `values`) | 422 naming the row; the section is unchanged |
| The same row as `{"values": {"13": "+1 570 555 0100"}}` | 200; the number is on the contact |
| A blank repeated row saved from the contact screen | Saved, no error (R2) |
| `fields` naming an id from another section | 422 |
| A repeated date saved with "I do not know the year" ticked | Stored and shown without its year |
| `POST value-lists` `{"name": "Paper suppliers"}`, then add Staples and Office Depot | 201 each; the settings screen shows the list |
| `DELETE` a list the application reads (`is_system`) | 422 "This list stays." |
| `DELETE` a value used by a contact | 422 "The value is in use and stays." |
| An assistant calls `delete-contact`, `set-contact-list-columns`, each value list tool | Same answer as the endpoint; delete tools answer with a sentence |
| A Duration field set to 131400, then to 525601 | Accepted and read "13 weeks 6 hours"; then refused |
| A life event of 10080 minutes | Reads "1 week" on the event, the timeline and the markdown copy |

## Documentation

`/docs/api` lists the eight value list endpoints and the row shape of the section endpoint; the MCP
tools page lists the eleven new tools in their groups.
