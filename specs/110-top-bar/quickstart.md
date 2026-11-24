# Quickstart: validate the top bar

## Automated

```bash
php artisan test --compact tests/Feature/Views
```

```bash
php artisan monica:localize --check
```

```bash
vendor/bin/phpstan analyse --no-progress
```

The view tests prove the order, names and presence rules of [contracts/top-bar.md](contracts/top-bar.md).
Run the whole suite before the pull request: `x-account-menu` and `x-icon` are shared by every
screen.

## By looking

Herd serves the checkout at `https://monica.test`. Screenshots are taken with the `take-screenshot`
skill, at 1280 and 375 pixels, light and dark.

| Screen | Check |
| --- | --- |
| `/vaults` as an administrator | Matches design 1a, Admin space the only bordered button |
| `/vaults` as a regular person | No Admin space, nothing else moves |
| Any vault dashboard | Right side identical to `/vaults` at 1280 (SC-001), breadcrumb on the left |
| A contact page, which fills `actions` | No sideways scroll at 375 (R7 in [research.md](research.md)) |
| Any settings screen | Right side identical to `/vaults` |
| Vault column and settings column | No theme switch at the head, no account block at the foot |
| Marketplace and admin bars | Unchanged apart from the account trigger (R5) |

## Keyboard

From the address bar, Tab through the bar on `/vaults` and on a vault screen: every control gets a
visible ring, in the order of the contract, and Escape closes the notification panel and the account
menu.
