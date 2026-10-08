# Quickstart: Closing Registration

How to prove the feature end to end once it is built. What each endpoint answers is in
[contracts/api.md](./contracts/api.md); why it is built this way is in [research.md](./research.md).

## Automated

```bash
php artisan test --compact --filter='Registration|SignInWithProvider|InstanceSetting|LoginViewModel|DocsViewModel|MarketplaceViewModel|MarketplaceLayout|RouteProtection'
```

Then the whole suite, because the Configuration screen and the marketplace layout are shared:

```bash
php artisan test --compact
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand, on a local instance

1. Set `MONICA_REGISTRATION_ENABLED=false` in `.env` and run `php artisan config:clear`.
2. Open `/login`: no "Create an account" under the card. Open `/register`: not found.
3. Open `/marketplace` signed out: "Sign in" in the header, no "Create an account" anywhere.
4. Open `/docs`: "Get started with Monica" leads to the sign in screen.
5. Call the API:

   ```bash
   curl -s -o /dev/null -w '%{http_code}\n' -X POST http://localhost/api/v1/register
   ```

   Expect `404`.
6. Sign in with an existing account by password: it works as before.
7. As an administrator, open the Configuration screen, find the setting under signing in, switch it
   on and save: the warning appears and nothing is saved. Save again: it is saved, and the
   administrator log shows the change. `/register` now answers.
8. Switch it off again and save: no warning, saved at once.
9. Remove `MONICA_REGISTRATION_ENABLED` from `.env`, revert the setting on the Configuration screen,
   and confirm `/register` answers: the default is enabled.
