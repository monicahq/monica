# Contract: the right side of the top bar

What `x-bar-links` renders and the view tests may assert. The look is not part of the contract.

## Order and accessible names

| # | Control | Element | Accessible name | Present when |
| --- | --- | --- | --- | --- |
| 1 | Docs | `<a href="route('docs.index')">` | "Docs" | Always |
| 2 | Marketplace | `<a href="route('marketplace.index')">` | "Marketplace" | The `marketplace` flag is on for the account |
| 3 | Account settings | `<a href="route('settings.index')">` | "Account settings" | `settings` prop is true (no caller today) |
| 4 | Admin space | `<a href="route('admin.overview.index')">` | "Admin space" | `isAdministrator()` |
| 5 | Divider | `<span aria-hidden="true">` | None | Always |
| 6 | Notifications | `<button aria-haspopup="dialog" aria-controls="notification-bar-panel">` | "Notifications, :count unread" | Always |
| 7 | Theme switch | `<button>` | "Switch between the light and dark theme" | Always |
| 8 | Account | `<button aria-haspopup="menu">` | "Your account" | Signed in |

DOM order equals visual order, so Tab reaches them 1 to 8.

## Behaviour kept from today

- Notifications: `aria-expanded` follows the panel; Escape closes it; the count is `aria-hidden` and
  the label carries the number; "9+" above nine.
- Account: the menu holds "Account settings" and a real submit button "Sign out"; Escape closes it.
- Theme switch: `$store.theme.toggle()`.

## Where it is rendered

| Layout | Left side | Right side |
| --- | --- | --- |
| `layouts/account.blade.php` | Product link to `vaults.index` | `x-bar-links` |
| `layouts/shell.blade.php` | Menu button below `lg`, breadcrumb | Vault search, `actions`, `x-bar-links` |

Nowhere else. `x-vault.sidebar` and `x-settings.sidebar` render no theme switch and no account menu.
