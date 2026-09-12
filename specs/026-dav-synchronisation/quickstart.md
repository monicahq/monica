# Quickstart: address book and calendar synchronisation

How to prove the feature works. Paths and properties are in [contracts/dav.md](contracts/dav.md).

## Prerequisites

This worktree has no `vendor/`: run `composer install` first. Then an account with one vault holding a contact with an
email, a phone, a birthday and a picture, and two keys minted at Settings, Security, one plain, one reaching sensitive fields.

```bash
export EMAIL=michael@dundermifflin.test KEY=... BASE=http://localhost:8000/dav USER_ID=1 VAULT=<vault uuid>
alias dav='curl -sS -u "$EMAIL:$KEY" -H "Depth: 1"'
CARD="$BASE/addressbooks/$USER_ID/$VAULT/dwight.vcf"
```

## The run

```bash
# 1. Principal, address books, cards. Expect 207, one address book per vault, one .vcf per person the everyone grouping shows.
dav -X PROPFIND "$BASE/"
dav -X PROPFIND "$BASE/addressbooks/$USER_ID/"
dav -X PROPFIND "$BASE/addressbooks/$USER_ID/$VAULT/"

# 2. What changed. Expect a sync-token, then an empty change set when asked again with it.
dav -X REPORT "$BASE/addressbooks/$USER_ID/$VAULT/" --data '<d:sync-collection xmlns:d="DAV:"><d:sync-token/><d:sync-level>1</d:sync-level><d:prop><d:getetag/></d:prop></d:sync-collection>'

# 3. Write a card with two phones and an ORG, read it back. Expect 201, the contact in the browser with the first phone
#    only, and the card with both phones and the ORG untouched. Rename Dwight in the browser and read again: the new
#    name arrives and the rest stays.
dav -H "Depth: 0" -X PUT -H "Content-Type: text/vcard" "$CARD" --data $'BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nN:Schrute;Dwight;;;\r\nFN:Dwight Schrute\r\nORG:Schrute Farms\r\nTEL;TYPE=cell:+15705551212\r\nTEL;TYPE=work:+15705550100\r\nEND:VCARD\r\n'
dav -H "Depth: 0" "$CARD"

# 4. Delete it, then the calendars. Expect 204 and the journal naming the key; then two calendars per vault, the
#    birthday as a yearly event and each upcoming deadline as a task.
dav -H "Depth: 0" -X DELETE "$CARD"
dav -X PROPFIND "$BASE/calendars/$USER_ID/"
```

## What must fail

| Try | Expect |
| --- | --- |
| Step 1 with the account password, or with another person's email and this key; then on a vault of another account | 401 with a Basic challenge, then 404 |
| Step 3 without `N` and `FN`, or with `If-Match` of an ETag that moved | 400, then 412 |
| Deleting the Me contact's card, or any `PUT` or `DELETE` under `calendars/` | 403 |
| Step 1 with the plain key on a contact holding a sensitive field | the card without that field, and a `PUT` of it leaves the field as it was |

## The suite

```bash
php artisan test --compact tests/Feature/Dav tests/Unit/Http/Dav tests/Unit/Actions/ImportVCardTest.php \
  tests/Unit/Actions/CreateApiKeyTest.php tests/Feature/Controllers/Api/ApiKeyControllerTest.php \
  tests/Feature/Controllers/App/Settings/Account/Security tests/Unit/ViewModels/Docs
vendor/bin/pint --dirty --format agent && vendor/bin/phpstan analyse --memory-limit=2G
php artisan monica:localize --check
```

## The clients

Connect Apple Contacts and DAVx5 before the pull request opens, noting the vCard version each asked for. The other three are checked from cards they sent, kept in `tests/Fixtures/Dav`.
