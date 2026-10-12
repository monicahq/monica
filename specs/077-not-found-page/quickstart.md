# Quickstart: Not Found Page

## Automated

```bash
php artisan test --compact tests/Feature/Controllers/App/NotFoundControllerTest.php tests/Feature/RouteProtectionTest.php tests/Feature/Mcp/McpTransportTest.php tests/Feature/Controllers/Api/EmailSentControllerTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

Each test in `NotFoundControllerTest` proves one scenario:

| Scenario | Expected |
| --- | --- |
| Visitor requests `/no-such-address` | 404, the heading, "Return home" linking `route('home.index')`, "Docs" and "Legal" links |
| Signed in person with locale `fr_FR` requests `/no-such-address` | 404, the French heading |
| Signed in person opens a contact through the wrong vault | 404, the heading |
| JSON request to `/api/v1/no-such-address` | 404, JSON body, no heading |

## By eye

Open `/no-such-address` in light and dark themes, at 375 px and at 1440 px, and zoomed to 200%.

- The page never scrolls sideways.
- Tab reaches the logo, the button, then the two footer links, with focus visible on each.
