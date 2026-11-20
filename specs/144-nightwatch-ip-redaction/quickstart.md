# Quickstart: Withhold the visitor's network address from monitoring

## Prerequisites

The application installed with its Composer dependencies. Nightwatch does not need a token: the tests
call the registered callbacks directly.

## Validate

```bash
php artisan test --compact tests/Unit/Providers/AppServiceProviderTest.php
```

Expected: every test passes, including the two added for this feature (see
[contracts/request-trace.md](contracts/request-trace.md)).

```bash
composer test:parallel
```

Expected: the whole suite passes. `tests/Feature/NightwatchSamplingTest.php` is the other file that
touches Nightwatch.

## Check by hand (optional)

On an environment that reports to Nightwatch, open a request trace in the Nightwatch dashboard.
Expected: no IP address shown, and the headers list contains no forwarding header.
