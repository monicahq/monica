# Quickstart: validating vault card values

## Tests

```bash
php artisan test --compact tests/Feature/Controllers/Api/VaultControllerTest.php
php artisan test --compact tests/Feature/Controllers/Api/ContactAvatarControllerTest.php
php artisan test --compact tests/Unit/Models/VaultTest.php tests/Unit/ViewModels/Vaults/VaultsViewModelTest.php
composer test:parallel
```

## By hand, with a personal access token

1. Open a vault on the web, give one contact an avatar, add a reminder due this month.
2. `curl -H "Authorization: Bearer $TOKEN" $APP_URL/api/v1/vaults` shows `last_visited_at`, `faces`
   and `upcoming_reminder_count` matching the card on `/vaults`.
3. `curl ...?sort=recent` lists the vaults in the order `/vaults` draws them; `?sort=name` answers 422.
4. `curl -H "Authorization: Bearer $TOKEN" -o face.webp <faces[0].avatar_url>` saves the thumbnail;
   the same request without the header answers 401.
5. The `list-vaults` MCP tool returns the same three attributes.
6. `/docs/api` shows the attributes on the vaults page and the avatar address on the contacts page;
   `/docs/api/v1/openapi` carries both.

Shapes and status codes: [contracts/api.md](contracts/api.md).
