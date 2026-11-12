# Quickstart: Possessions

## Automated

```bash
php artisan test --compact tests/Unit/Actions --filter=Possession
php artisan test --compact tests/Feature/Controllers/Api/Possessions
php artisan test --compact tests/Unit/Mcp/Tools --filter=Possession
php artisan test --compact tests/Feature/RouteProtectionTest.php
php -d memory_limit=4G vendor/bin/phpunit --no-progress
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress --memory-limit=2G
vendor/bin/pint --dirty --format agent
```

## By hand, with an API key

| Do | Expect |
| --- | --- |
| POST a possession of type Car in a vault | 201, status `owned`, quantity 1 |
| PUT values for Plate, Fuel, First registration | 200, values typed and in the type's order |
| PUT a value `banana` for a number field | 422 naming that field |
| Feature three, move the third to 1 | positions 1, 2, 3, no gap |
| Delete the Car type | the possessions are gone; the log says how many |
| Delete the location they sat at | possessions kept, `location` null |
| Ask the assistant to list the vault's possessions | the same list as the API |
| Open /docs/api and the MCP docs | Possessions documented |
