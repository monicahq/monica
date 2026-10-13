# Quickstart: proving CardDAV accepts any incoming vCard

The answers expected for each kind of card are in [contracts/carddav-save.md](contracts/carddav-save.md).

## In the suite

```bash
php artisan test --compact tests/Unit/Http/Dav/CardDavPluginTest.php tests/Unit/Actions/ImportVCardTest.php tests/Feature/Dav/AddressBookTest.php tests/Unit/Jobs/CreateDefaultTemplateTest.php
```

Every row of the contract has a test, and at least one of them runs on the default template built by
`CreateDefaultTemplate`, which no import test used before this change.

## Against a running instance

1. Sign in, create a DAV key on the security settings screen, and note the vault's address book URL
   from the CardDAV docs page.
2. Save a card holding two email addresses, two phone numbers and a line with no colon:

   ```bash
   printf 'BEGIN:VCARD\r\nVERSION:3.0\r\nN:Schrute;Dwight;;;\r\nEMAIL;TYPE=work:dwight@dundermifflin.test\r\nEMAIL;TYPE=home:dwight@schrutefarms.test\r\nTEL;TYPE=cell:+15705550100\r\nTEL;TYPE=work:+15705550200\r\nthis line has no colon\r\nEND:VCARD\r\n' > dwight.vcf
   ```

   ```bash
   curl -sS -o /dev/null -w '%{http_code}\n' -u "$EMAIL:$DAV_KEY" -X PUT -H 'Content-Type: text/vcard' --data-binary @dwight.vcf "$ADDRESS_BOOK_URL/dwight.vcf"
   ```

   Expect `201`. The contact shows both email addresses and both phone numbers.
3. Read it back:

   ```bash
   curl -sS -u "$EMAIL:$DAV_KEY" "$ADDRESS_BOOK_URL/dwight.vcf"
   ```

   Expect `this line has no colon` on a line of its own, unchanged.
4. Save `BEGIN:VCARD\r\nVERSION:3.0\r\nTEL:+15705550100\r\nEND:VCARD\r\n`: expect `201` and a contact
   named `+15705550100`. Save `BEGIN:VCARD\r\nVERSION:3.0\r\nNOTE:nothing else\r\nEND:VCARD\r\n`:
   expect `400`.
5. With a real device (iOS, DAVx⁵, Thunderbird), add a contact with several email addresses and
   addresses, and check that the device reports no sync error and the vault shows every value.
