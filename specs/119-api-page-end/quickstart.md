# Quickstart: End of an API reference page

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php
php artisan test --compact tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

All pass, PHPStan reports nothing.

## By hand

| Open | Expect |
| --- | --- |
| `/docs/api/authentication` | "Previous" card to the first guide, "Next" card to the following page, footer, date |
| The first page of the reference | Only "Next", on the right |
| The last page in the sidebar | Only "Previous", on the left |
| Any page with `MONICA_SUPPORT_ENABLED=false` | No support sentence, the `llms.txt` one remains |
| Tab from the end of the endpoint | Each card and link takes a visible focus ring |
| `/docs/api/authentication.md` | Unchanged, no cards or footer |

The section ids above are examples; read the real ones from the sidebar.
