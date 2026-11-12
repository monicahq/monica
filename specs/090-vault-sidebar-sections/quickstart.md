# Quickstart: Vault Sidebar Sections

## Automated

```bash
php artisan test --compact tests/Unit/Models/EntityTypeTest.php
php artisan test --compact tests/Feature/Views/Components/SidebarTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php
php artisan test --compact tests/Feature/RouteProtectionTest.php
php artisan test --compact
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand

| Do | Expect |
| --- | --- |
| Open any vault | This vault (Dashboard, Reminders), Your relationships (People, Animals), Your possessions (All possessions), Vault settings at the foot |
| Choose All possessions | The placeholder screen, All possessions marked current |
| Settings, deactivate Animals, reopen the vault | Animals gone from the column; an animal's own address still opens |
| Remove the vault's default template for Animals | Animals gone from that vault only |
| Delete every possession type, reopen the vault | No Your possessions label, no entry |
| Read the column with a screen reader | Three section headings, none of them a link |
| Narrow the window, open the menu | The column slides in and closes as before |
| Switch to French | Every label translated |
