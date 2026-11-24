# Quickstart: validating the upgrade strip

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/FullAccessOfferViewModelTest.php tests/Feature/Views/Components/FullAccessOfferTest.php tests/Feature/Views/Layouts tests/Feature/Controllers/App/FullAccessOfferControllerTest.php tests/Feature/Controllers/Api/MeFullAccessOfferControllerTest.php
```

Then the whole suite, since both layouts change:

```bash
PAO_DISABLE=true php artisan test --parallel --compact
```

And the checks `CLAUDE.md` lists:

```bash
php artisan monica:localize --check
```

```bash
vendor/bin/phpstan analyse --no-progress
```

```bash
vendor/bin/pint --dirty --format agent
```

## By hand, on `monica.test`

With a hosted (not self hosted) free account whose user never hid the offer:

1. Open the vault list. The strip sits under the top bar, edge to edge, with "Free plan", People and
   Vaults with their counts, "Upgrade your account" and a close button.
2. Open a vault, then a settings screen other than billing. The strip is in the same place.
3. Open billing. No strip.
4. Hold 24 of 30 people: the people meter turns to the warning tone. Hold 30: the danger tone. One
   vault of one: the vault meter is in the danger tone.
5. Narrow the window to 375 pixels. Nothing scrolls sideways and the button stays reachable.
6. Switch to the dark theme. The meters and the badge stay legible.
7. Activate the close button. The strip is gone on every screen, and after signing in again.

Expected outcomes are the acceptance scenarios of [spec.md](spec.md) and the markup in
[contracts/full-access-offer.md](contracts/full-access-offer.md).
