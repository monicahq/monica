# Quickstart: Additional feature flags

What each answer is, per surface, is in [contracts/flags.md](contracts/flags.md). This guide proves
it on a local instance with nothing stored in the `features` table.

## 1. The shipped state

| Run | Expect |
| --- | --- |
| `php artisan monica:list-flags` | `carddav`, `file-upload`, `sms-notifications`, `vcard-export` off for every account; `mcp-server` on; `marketplace` as before |

## 2. Withdrawn until switched on (User Stories 1 and 2)

Sign in as Jim, with an API key and an SMS channel set up beforehand.

1. Point a CardDAV client at the address with Jim's key: it is refused as not found. Without a
   password it is first challenged (401).
2. Open a contact: no "Export as vCard"; `GET /vaults/{vault}/contacts/{contact}/vcard` answers 404.
3. Settings, Notifications: no SMS card, one sentence saying text messages are unavailable.
4. Profile and a contact's edit screen: no upload control; an existing photo is shown and removable.
5. A section with an image field: no file input; posting a file by hand returns the error on the field.
6. `php artisan monica:switch-flag carddav on --email=jim@example.com` and the same for the three
   others: each capability is back for Jim; Dwight still has none of them.

## 3. MCP stays, then goes (User Story 3)

1. Connect an assistant to `/mcp` as Pam: it works.
2. `php artisan monica:switch-flag mcp-server off --email=pam@example.com`: Pam's assistant gets 404,
   `/oauth/authorize` answers 404 to Pam, her security settings say her assistants are kept, and
   Dwight's assistant still works.
3. Switch it back on: the same assistant works without being authorised again.

## 4. Documentation (User Story 4)

Signed out: `/docs/carddav` answers 404 and the docs index shows no CardDAV card; `/docs/mcp` answers
200. `monica:switch-flag carddav on --every-account`: `/docs/carddav` answers 200.

## 5. Gates

```bash
php artisan test --compact
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```
