# Quickstart: Documentation portal header

## Automated

```bash
php artisan test --compact tests/Unit/Helpers/MarketingSiteTest.php tests/Feature/Views/Components/Docs/HeaderTest.php
php artisan monica:localize --check
composer test:parallel
```

## By eye

1. `bun run build`, then open `/docs` signed out, and `https://www.monicahq.com/` beside it.
2. At 375, 768, 1280 and 1920 pixels, in light and dark, compare the two headers (SC-001).
3. Open Features and Docs by click, by keyboard (Tab, Enter, Escape returns focus) and by resting the
   mouse; move from one menu to the other: only one stays open.
4. Follow every link once (SC-002). Switch the application to French and check `/fr/tarifs/` is
   where Pricing leads.
5. Sign in: one Dashboard button replaces Sign in and Get started. Set
   `MONICA_REGISTRATION_ENABLED=false`: Get started disappears.
6. Disable JavaScript: both menus still open and close by click.
7. Print preview: no header.
