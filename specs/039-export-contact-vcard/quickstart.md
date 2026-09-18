# Quickstart: validating the vCard export

How to prove the feature works. The three checks below are the ones that are not obvious from the test names. Everything else is covered by running the suite.

## Prerequisites

A seeded account with one vault, one contact carrying an avatar and at least one field marked sensitive, and an API key. The contact factory and `CreateField(isSensitive: true)` produce all of it in a test.

## Run the suite for this feature

```
php artisan test --compact tests/Unit/Actions/ExportContactVCardTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/ContactVCardControllerTest.php
php artisan test --compact tests/Feature/Controllers/Api/ContactVCardControllerTest.php
php artisan test --compact tests/Feature/Dav/AddressBookTest.php
```

`AddressBookTest` is in that list because FR-011 changes `createCard`. Run it even when the change looks confined to the export.

## Check 1: the two surfaces hand over the same bytes (SC-007)

Download the card from the interface and fetch it from the API with a key carrying the `sensitive-fields` scope. The two bodies must be identical, not merely equivalent. Compare them as strings in the test rather than by parsing both into vCard objects, because parsing hides exactly the differences this criterion exists to catch, such as property order and line folding.

## Check 2: a scope actually removes values (SC-006)

Fetch the same contact with a key that does not carry `sensitive-fields`. The sensitive value must be absent from the body. Assert its absence, not an empty property: `VCardMerger` omits the property rather than emptying it, and a test asserting an empty value would pass against a card that still carries the data.

## Check 3: a card coming back does not duplicate (SC-008)

This is the one worth doing by hand once, because it crosses two subsystems.

1. Note the contact count in the vault.
2. Export the contact and keep the file.
3. Present that file back through CardDAV as a **new** card, under a filing address the product has never seen, which is what a phone does after the file is loaded into it. In a test this is `createCard($addressBookId, 'something-new.vcf', $exportedCard)`.
4. The vault must hold the same number of contacts, and the original contact must carry any change the file made.
5. Repeat with the UID rewritten to one belonging to a contact in another vault. A second contact must appear in the vault addressed, and the contact in the other vault must be untouched. This is FR-012, and it is the check that matters most.

## Before the change is done

```
vendor/bin/pint --dirty --format agent
php artisan monica:localize
vendor/bin/phpstan analyse
php artisan test --compact
```

`monica:localize` will report the new strings for the button and the journal sentence. Translate what it reports in every configured locale, and never edit `lang/*.json` by hand.

Run the whole suite, not only the four files above. `ContactViewModel` gains a method and the contact screen gains a control, and both are reached by more tests than this feature owns.
