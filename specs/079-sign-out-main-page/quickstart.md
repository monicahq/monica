# Quickstart: Sign out from the main page

## Automated

```bash
php artisan test --compact tests/Feature/Views/Components/AccountMenuTest.php tests/Feature/Views/Layouts tests/Feature/Views/Components/BarLinksTest.php tests/Feature/Views/Components/SidebarTest.php tests/Feature/Views/Components/ActionsMenuTest.php tests/Feature/Controllers/App/Auth/LoginControllerTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

What each file proves is in [research.md](research.md), R7.

## By eye

Sign in, then open the list of vaults, the form that creates a vault, the marketplace and (as an
administrator) the admin space. On each, in light and dark themes, at 375 px and at 1440 px, and
zoomed to 200%:

- The avatar is the last control of the bar, and never leaves the screen.
- Clicking it, or Tab then Enter, opens a menu holding Account settings and Sign out. Escape closes
  it and a click outside closes it.
- Sign out shows the sign in screen. Going back to the address you left asks you to sign in.
- The bar of the vaults list shows no "Account settings" link of its own, and no email address.

Open a vault: its top bar has no avatar menu, and the menu at the foot of the navigation column is
unchanged. Open the marketplace signed out: Sign in and Create an account, and no menu.
