# Quickstart: Four supplied contact templates

## Automated

```bash
php artisan test --compact tests/Unit/Jobs/CreateDefaultTemplateTest.php tests/Unit/Jobs/SupplyContactTemplatesTest.php tests/Unit/Actions/MoveRecordToStructureVersionTest.php tests/Unit/Helpers/OwnedFieldsTest.php
```

```bash
php artisan monica:localize --check
```

Then the whole suite, since vault defaults and template moves are shared by many screens:

```bash
PAO_DISABLE=true php artisan test --parallel --compact
```

The backfill migration is proven by `SupplyContactTemplatesTest` and is not run against the local database.

## By hand, on erica.test

| Step | Expected |
| --- | --- |
| Sign up a new account, reading in French | Settings, Templates lists Basic contact, Essentiel, Amis et proches, Réseau professionnel, Portrait complet, Basic animal |
| Open each new template | Pages, sections and fields as in the spec tables, French wording |
| Add a contact without choosing a template | It uses Basic contact |
| Fill birthday, two emails, a phone, an address and a note, then move the contact to Essentiel | All five values show; the birthday reminder is still listed |
| Move it to Portrait complet, add a gift idea and an outing idea, move it to Amis et proches | The gift idea shows under Idées de cadeaux; the outing idea is set aside |
| Move it back to Portrait complet | The outing idea is back |
| Value lists | Loisirs, Canaux de communication and Secteurs hold their choices; Domaines d’expertise is empty |
| Export the contact as a vCard | Website syncs; no gift idea link appears as a URL |
| Create a second vault | Its default contact template is Basic contact |
