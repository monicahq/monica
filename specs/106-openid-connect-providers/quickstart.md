# Quickstart: checking the result

The settings and addresses are in [contracts/configuration.md](contracts/configuration.md).

## Automated

```bash
php artisan test --compact tests/Unit/Enums/SsoProviderEnumTest.php tests/Unit/Helpers/SsoProviderAvailabilityTest.php
```

```bash
php artisan test --compact --filter=Sso
```

```bash
php artisan monica:localize --check
```

```bash
vendor/bin/phpstan analyse --no-progress
```

Run the whole suite serially before the pull request. Do not use paratest.

## By hand, against a real provider

Needs a provider on the local machine. Keycloak in development mode is the shortest way: it accepts
plain http on localhost, which the package allows there and nowhere else.

1. Create a realm and a client with the callback `http://<app host>/sso/keycloak/callback`.
2. Set the four Keycloak variables, then open the sign in screen. **Expected**: "Continue with
   Keycloak" after Facebook.
3. Sign in with a Keycloak user that has no Monica account. **Expected**: an account is created and
   asked to confirm its address.
4. Sign out, sign in again through Keycloak. **Expected**: the same account.
5. In the security settings, disconnect Keycloak with no password set. **Expected**: refused, with
   the reason. Set a password, disconnect, connect again. **Expected**: both succeed.
6. Remove `KEYCLOAK_REALM`. **Expected**: Keycloak is on no screen, and `/sso/keycloak/redirect`
   answers not found.
7. Restore it and stop Keycloak, then use the button. **Expected**: back on the sign in screen with
   "That did not work. Try again, or sign in another way.", not an error page.
8. In the admin space, store a wrong Keycloak secret, then sign in. **Expected**: the same refusal,
   which proves the stored setting reaches the driver. Clear it.

Repeat steps 2 to 4 with `OIDC_BASE_URL` pointing at the same realm
(`http://localhost:<port>/realms/<realm>`) and `OIDC_NAME` set. **Expected**: a second button with
that name, and a second, independent connection.

## Not checkable here

Microsoft, Auth0 and Okta need an account at each. For Microsoft, an existing application
registration keeps working unchanged, since the callback address does not move.
