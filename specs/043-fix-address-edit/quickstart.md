# Quickstart: validating the editable address

## Prerequisites

A working checkout with the suite green on the branch, and a seeded account. The default template
already supplies an Addresses section holding an Address field on every contact, so nothing has to
be built first.

## Run the suite

```bash
vendor/bin/phpunit tests/Unit/Enums/FieldTypeEnumTest.php tests/Unit/Helpers/FieldValueRulesTest.php
vendor/bin/phpunit tests/Unit/Actions/SetFieldValueTest.php tests/Unit/Actions/SetFieldValuesTest.php
vendor/bin/phpunit tests/Unit/Http/Dav/VCardMergerTest.php tests/Unit/Actions/ImportVCardTest.php
vendor/bin/phpunit tests/Feature/Views/Components/FieldInputTest.php tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php
```

Then the whole suite, because `x-field-input` and `x-field-value` are shared by every record screen.

```bash
php artisan test --compact
```

## Check it by hand

1. Open a contact and edit the Addresses section. Seven boxes stand under the field's name, street
   first and country last, with a label box beside them offering Home, Work, Holiday home and Other,
   then `Custom...`, which opens a box for a wording of your own that the record reads
   beside the address. The line saying the field cannot be filled in here is gone.
2. Type a street, a city and a postcode, pick a country and Home, save. The record reads the parts on
   one line with Home beside them.
3. Change the postcode alone, save. Every other part is as it was.
4. Empty every box, save. The record says the address is not recorded.
5. Mark the field required in the template, save the block with every box empty. The save is refused
   and names the field. Fill one box, save. It is accepted.
6. Reach the section with the keyboard alone. The field's name is announced once, then each box by
   the name of its part, then the country select.
7. Import a card carrying `ADR;TYPE=work:PO Box 12;Suite 3;1725 Slough Avenue;Scranton;PA;18505;USA`.
   Open the section: every part is in its box, USA is offered as it arrived, Work is the label. Change
   the city and save. The post office box and the suite are still there.
8. Export the contact: `GET /api/vaults/{vault}/contacts/{contact}/vcard`. The card carries the seven
   parts and `TYPE=work`. The mapping is in [contracts/vcard-mapping.md](contracts/vcard-mapping.md).
9. In Settings, deactivate Home in the Address labels list. The contact still reads Home, and a newly
   opened form no longer offers it.
10. Put an address field inside a repeater. Fill two repetitions, save, remove the first, save. The
    second is intact.
11. Open the field catalogue. The address tile shows a label above its rows, and adding an address
    field arrives with Address labels preselected.

## Before it is done

```bash
php artisan monica:localize
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
```

Translate everything `monica:localize` reports, in every configured locale, and never by editing
`lang/*.json` by hand.
