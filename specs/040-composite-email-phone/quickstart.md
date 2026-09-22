# Quickstart: validating composite email and phone

## Prerequisites

A working checkout with the suite green on the branch, and a seeded account. The default template
already supplies an Email field and a Phone field on every contact, so nothing has to be built first.

## Run the suite

```bash
php artisan test --compact --filter=FieldValue
vendor/bin/phpunit tests/Unit/Actions/SetFieldValueTest.php tests/Unit/Actions/SetFieldValuesTest.php
vendor/bin/phpunit tests/Unit/Actions/ImportVCardTest.php tests/Feature/Controllers/Api/ContactVCardControllerTest.php
```

Then the whole suite, because `x-field-input` and `x-field-value` are shared by every record screen
and a handful of view tests assert on their markup.

```bash
php artisan test --compact
```

## Check it by hand

1. Open a contact and edit the section holding Phone. The number input is there as before, with a
   label select beside it offering Mobile, Home, Work, Fax and Other.
2. Type a number, pick Mobile, save. The record reads the number with Mobile beside it.
3. Clear the label, save. The record reads the number alone and the field is still accepted.
4. Reach the section with the keyboard alone. The number and its label are announced as belonging
   together, and the select is reachable in order.
5. In Settings, deactivate Mobile in the Phone labels list. The contact still reads Mobile, and a
   newly opened form no longer offers it.
6. Export the contact: `GET /api/vaults/{vault}/contacts/{contact}/vcard`. The card carries
   `TEL;TYPE=cell`. Import it back and the label survives. The mapping is in
   [contracts/vcard-mapping.md](contracts/vcard-mapping.md).
7. Give two contacts the same email address under different labels and run duplicate detection. They
   are still reported as likely the same person.

## Before it is done

```bash
php artisan monica:localize
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
```

Translate everything `monica:localize` reports, in every configured locale, and never by editing
`lang/*.json` by hand.
