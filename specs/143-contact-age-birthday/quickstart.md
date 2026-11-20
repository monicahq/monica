# Quickstart: Contact age and upcoming birthday

## Automated

```bash
php artisan test --compact tests/Unit/Helpers/BirthdayTest.php
php artisan test --compact --filter=birthday tests/Unit/Models/ContactTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults/ContactViewModelTest.php
php artisan test --compact tests/Feature/Controllers/Api/ContactControllerTest.php
php artisan monica:localize --check
composer test:parallel
```

Expected: every state of the table in [data-model.md](data-model.md) is covered by `BirthdayTest` and
the view model test, with `CarbonImmutable::setTestNow()` on 20 November 2026; the API test proves
both attributes and the sensitive refusal ([contracts/api-contact.md](contracts/api-contact.md)).

## By hand

1. Sign in, open a contact on Basic contact, set the birthday to 26 November 1978.
2. On 20 November 2026 the line reads "47 years old · Turns 48 in 6 days, on Thursday 26 November".
3. Change it to 28 November 1978: the line reads "47 years old" alone (8 days away).
4. Tick "year unknown" on 26 November: "Birthday in 6 days, on Thursday 26 November".
5. Remove the birthday: the line disappears and the facts sit under the name.
6. Switch the interface to French: the line reads in French, the weekday and month included.
7. The dashboard still titles its group "Next seven days" and lists the same deadlines as before.
