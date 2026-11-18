# Quickstart: Admin space sidebar

Prerequisites: signed in at `https://erica.test` as an administrator, `bun run build` done.

## Automated

```bash
php artisan test --compact tests/Feature/Views/Layouts/AdminLayoutTest.php
```

Then the whole suite, since `x-nav-drawer` also draws settings and support:

```bash
PAO_DISABLE=true php artisan test --parallel --compact
```

## By hand

| Width | Do | Expect |
| --- | --- | --- |
| 1280 | Open Accounts, then one account | Sidebar left of the warning band; Accounts current; the page scrolls, the sidebar does not |
| 1280 | Leave a changelog comment unreviewed | Changelog line shows the figure |
| 375 | Open any admin screen | No sidebar, no sideways scroll, bar on one line with the menu button |
| 375 | Press the menu button, then Escape | Drawer opens over a dimmed page, closes, focus back on the button |
| 375 | Open the menu, choose Flags | Flags opens |
| 375 | With a comment to review | Menu button shows its signal |
| 375 open → 1280 | Widen the window | No dimmed layer left, page scrolls |
| Any | Tab through, dark theme, reduced motion, print preview | Visible focus, contrast holds, no slide, no sidebar printed |
| 1280 | Settings and support screens | Unchanged |
