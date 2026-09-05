# Quickstart: contact endpoints

How to prove the feature works. Fields and bodies are in [contracts/contacts.md](contracts/contacts.md).

## Prerequisites

An account with a vault, and an API key minted at Settings, API.

```bash
export MONICA_TOKEN=... MONICA_BASE=http://localhost:8000/api/v1 VAULT=<vault uuid>
alias api='curl -sS -H "Authorization: Bearer $MONICA_TOKEN" -H "Accept: application/json"'
```

## The run

```bash
# 1. Write somebody down with a first name alone. Expect 201, and note the uuid.
api -X POST "$MONICA_BASE/vaults/$VAULT/contacts" -d first_name=Pam

# 2. Read them back and list the vault. Expect the same object, and meta.total up by one.
api "$MONICA_BASE/vaults/$VAULT/contacts/$CONTACT"
api "$MONICA_BASE/vaults/$VAULT/contacts?sort=added&direction=desc"

# 3. Give them a full name. All eight parts must be sent. Expect 200 and a rebuilt name.
api -X PUT "$MONICA_BASE/vaults/$VAULT/contacts/$CONTACT/name" \
  -d prefix= -d first_name=Pam -d middle_name= -d last_name=Beesly \
  -d suffix= -d maiden_name= -d nickname=Pammy -d name_order=given_first

# 4. Mark a favourite. Expect the contact in filter=favourites.
api -X PUT "$MONICA_BASE/vaults/$VAULT/contacts/$CONTACT/favourite" -d favourite=1
api "$MONICA_BASE/vaults/$VAULT/contacts?filter=favourites"

# 5. Picture on, picture off. Expect avatar to become an object, then null.
api -X POST "$MONICA_BASE/vaults/$VAULT/contacts/$CONTACT/avatar" -F avatar=@pam.png
api -X DELETE "$MONICA_BASE/vaults/$VAULT/contacts/$CONTACT/avatar"

# 6. Remove them. Expect 204, then 404 on the same address.
api -X DELETE "$MONICA_BASE/vaults/$VAULT/contacts/$CONTACT"
```

## What must fail

| Try | Expect |
| --- | --- |
| Any of the nine addresses with another account's uuid, or a contact uuid from another vault of the same account | 404, never 403 |
| Step 3 without `nickname` in the body | 422, and the nickname still there |
| Deleting the contact whose `is_me` is true | 422 with a reason |
| `per_page=500`, then no `Authorization` header | 200 with `meta.per_page` of 100, then 401 |

## The suite

```bash
php artisan test --compact tests/Feature/Controllers/Api tests/Unit/Models/ContactTest.php \
  tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php tests/Unit/ViewModels/Docs
vendor/bin/pint --dirty --format agent && vendor/bin/phpstan analyse --memory-limit=2G
php artisan monica:localize --check
```

The screen and the API share the scopes, so `ContactsViewModelTest` passing unchanged is the evidence
that moving the query onto the model changed nothing a person sees.
