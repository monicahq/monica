# Quickstart: Russian locale

How to prove the feature works once implemented. The decisions behind each check are in
[research.md](research.md).

## Gates

```bash
php artisan monica:localize --check
php artisan test --compact tests/Unit/Translation tests/Unit/Helpers/LocaleCollationTest.php tests/Unit/Helpers/AvailableLocalesTest.php
php artisan test --compact
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

Expected: the check reports nothing missing or stale for `ru_RU`, every test passes, PHPStan and
Pint report nothing.

## By hand

| Step | Expected |
| --- | --- |
| Open the sign in screen, open the language picker | `Русский`, region `Россия`, a white, blue and red flag |
| Pick it and sign in | Every screen reads in Russian, no English sentence |
| Send an empty sign in form | The validation message is in Russian |
| Preferences, with no date or number preference set | `12.10.2026` and `1 234,56` |
| A vault with 1, 2, 5, 21 contacts | `1 контакт`, `2 контакта`, `5 контактов`, `21 контакт` |
| Contacts Анна, Евгений, Ёлкин, Жанна, Йорк, Иван | Sorted Анна, Евгений, Ёлкин, Жанна, Иван, Йорк; Ёлкин under heading Е, Йорк under Й |
| Search `елкин` | Finds Ёлкин |
| Search `иорк` | Does not find Йорк |
| Request the password reset email | Subject and body in Russian |
| API request with `Accept-Language: ru` | Error messages in Russian |
| Zoom to 200% on the vault dashboard and settings | No button or menu label cut so it cannot be read |

Do not run `migrate:fresh` against the local database to set this up; seed through the factories in
a test or use an existing account.
