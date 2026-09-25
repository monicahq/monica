# Quickstart: Welcome screen

The column is added to the create migration, so a local database is rebuilt, not patched:
`php artisan migrate:fresh --seed`.

## Automated

```bash
php artisan test --compact tests/Unit/Actions/MarkUserAsWelcomedTest.php \
  tests/Unit/ViewModels/Welcome tests/Feature/Controllers/App/WelcomeControllerTest.php \
  tests/Feature/Controllers/App/HomeControllerTest.php \
  tests/Feature/Controllers/Api/MeWelcomeControllerTest.php tests/Unit/Actions/CreateAccountTest.php \
  tests/Feature/RouteProtectionTest.php tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand

| Step | Expected |
| --- | --- |
| Register, confirm the email address | The welcome screen, greeting you by first name |
| Press Let me in | Getting started (no vault yet) |
| Sign out, sign in again | Getting started, no welcome |
| Open `/welcome` | The screen renders; Let me in sends you on |
| Tab through the screen | Focus reaches Let me in, ring visible; Enter submits |
| Zoom to 200 per cent, then a phone width | The letter reflows, no horizontal scroll |
| `GET /api/v1/me`, then `PUT /api/v1/me/welcome` | `welcomed_at` is a timestamp, unchanged by a second call |
