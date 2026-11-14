# Quickstart: Preview list and admission

## Automated

```bash
php artisan test --compact --filter='PreviewList|PreviewAdmission|Registration|CreateAccount|HomeController|InstanceSetting|Configuration|RouteProtection|AdminAccess'
```

```bash
php artisan test --compact
```

```bash
php artisan monica:localize --check
```

```bash
vendor/bin/phpstan analyse --no-progress
```

```bash
vendor/bin/pint --dirty --format agent
```

## By hand

1. On a fresh instance, open `/` signed out: it leads to the sign in screen, as before.
2. Set `MONICA_REGISTRATION_ENABLED=false` and `MONICA_PREVIEW_LIST_ENABLED=true` in `.env`. As an
   administrator, open Configuration: the preview list setting names its variable and reads on.
3. Open `/` signed out: it leads to `/preview`, where the card presents the product, with one email field and a link to sign in.
4. Submit an address. The card confirms. Submit it again, then submit the address of an existing
   account: the same confirmation each time, and no email leaves the instance.
5. Open the admin space, Preview list: the address is there, waiting. Admit it. The administrator
   log shows the admission and an email is queued in the language the visitor used.
6. Follow the link from the email: the sign up screen opens with the address filled in and read
   only. Open `/register` without the link in another browser: not found.
7. Create the account with a password. No verification email is sent, the account is signed in, and
   the entry reads registered. Follow the link again: not found.
8. Add three more addresses, type 2 in the field that admits the oldest, and submit: the two oldest
   are admitted and the third still waits.
9. Admit an admitted entry again: a new email leaves and the earlier link answers not found.
10. Remove an entry: the screen asks first, then the entry is gone and its link answers not found.
11. Call the API with a usable token (see [contracts/api.md](./contracts/api.md)) and expect `201`.
    Call it without one and expect `404`.
12. Switch the preview list off on the Configuration screen: `/` leads to the sign in screen again, the entries are still listed,
    and a link already sent still opens sign up.
