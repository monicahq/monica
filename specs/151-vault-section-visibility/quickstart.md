# Quickstart: Hide the main sections of a vault

## Automated

```bash
php artisan test --compact --filter='UpdateVaultSectionVisibility|VaultSectionVisibilityController|VaultControllerTest|UpdateVaultTest|SidebarTest|SearchRecordsTest|McpCatalogueTest'
composer test:parallel
php artisan monica:localize --check
```

## By hand

1. Open a vault, then **Vault settings**. The **What this vault shows** panel lists People, Things and Knowledge, all **Shown**.
2. Switch Knowledge off. The row reads **Hidden** before saving. Choose **Save changes**: "The vault is saved." The Knowledge group is gone from the sidebar.
3. Open a knowledge note by its address: it still opens.
4. Switch People off and save. Search the vault for a contact's name: no People group. Search every vault: the contact is found only if it lives in a vault that shows People.
5. Open the account log: one entry per save, naming the vault.
6. With a token: `GET /api/vaults/{vault}` shows `shows_people: false`. `PUT` with `{"shows_things": false}` changes only that value. `PUT` with `{"shows_things": "maybe"}` answers 422.
7. From an assistant: `update-vault` with `shows_people: true` brings People back.
8. Keyboard only: tab to each switch, Space flips it, the focus ring is visible, a screen reader announces "People, switch, on".
