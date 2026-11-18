# Quickstart: Skip the list of vaults

## Automated

```bash
php artisan migrate --no-interaction
php artisan test --compact --filter='VaultPolicy|DestroyVault|UpdateVaultListVisibility|VaultController|MeVaultList|VaultSettingsViewModel|RouteProtection'
php artisan monica:localize --check
```

## By hand, at https://erica.test

1. Settings, Preferences, section Vaults: turn **Show the list of vaults** off. A confirmation appears.
2. Open `/vaults`, click the logo, leave the settings: each lands on the default vault.
3. Open that vault's settings: **Delete this vault** is greyed, a sentence says why and links to Preferences, and pressing it opens nothing. With a screen reader, the button is announced as unavailable with that sentence.
4. Open another vault's settings: deletion still works.
5. Turn the switch back on: `/vaults` shows the list again.
6. With an API token: `curl -X DELETE .../api/v1/vaults/{default}` answers 422 while the switch is off, and `PUT .../api/v1/me/vault-list` with `{"shows_vault_list": true}` turns it back on.
