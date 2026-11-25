# Quickstart: Vault trash

## Automated

```bash
php artisan test --compact tests/Unit/Actions --filter='Restore|DestroyTrashItem|EmptyVaultTrash|DestroyEntity|DestroyContact|DestroyGroup|RemoveGroupFile|AddGroupFile|MergeContacts'
php artisan test --compact tests/Unit/Helpers/VaultTrashTest.php tests/Unit/Jobs/PurgeVaultTrashTest.php
php artisan test --compact tests/Feature/Controllers --filter='Trash|Entity'
composer test:parallel
php artisan monica:localize --check
```

## By hand, in a vault seeded with The Office

1. Delete Jan Levinson (two life events of her own, one relationship with Michael, a reminder). Open Vault settings, then Trash: one row under Everything, four under the Show menu's kinds, the life events saying they went with Jan and their Restore unavailable.
2. Restore Jan: the notice names what came back; her reminder appears on the vault's reminders again.
3. Delete the group "Party Planning Committee" with a file attached, then delete that file's group row for good: the file leaves the group; storage drops after the orphan grace period.
4. Delete an animal from its page: it is in the trash as its type's name.
5. Empty the trash: the confirmation counts by kind; afterwards the tab reads "The trash is empty".
6. Through the API with a token: `GET api/v1/vaults/{vault}/trash`, then the restoration of one item, as in [contracts/api.md](contracts/api.md).
7. Run `php artisan schedule:test` and pick `PurgeVaultTrash` after moving one row's `deleted_at` 31 days back: the row is gone.

Keyboard only: reach the Show menu, open it, move with the arrows, close with Escape; reach a countdown and hear its date; open and dismiss each confirmation.
