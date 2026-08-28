# Quickstart: Proving the Restoration Works

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md)

How to run this and how to know it is right. A design change cannot be proved by the suite alone, so
this guide is half commands and half a walk through the product with your eyes and your keyboard.

## Prerequisites

```bash
git switch 2026-08-26-restore-former-design
composer install
bun install
```

The fonts change in `vite.config.js`, so the first build after slice 1 has to actually run rather
than being served from a stale manifest:

```bash
rm -rf public/build public/fonts public/fonts-manifest.dev.json
bun run build
```

If a screen renders in Times New Roman, that is this step not having happened.

## Running it

```bash
composer run dev
```

Then sign in. If the database is empty:

```bash
php artisan migrate:fresh --seed
```

## The gates, in the order they will fail

Run these after every slice, not at the end.

```bash
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
php artisan test --compact
```

`monica:localize --check` is the one most likely to catch this feature out. Restyling a screen tempts
you into rewording it, and a reworded string is an untranslated string in nine locales. If it reports
anything, run `php artisan monica:localize` and translate every locale before the commit.

`phpstan` is the second. The four view models narrow their array shapes, and a docblock left
describing the old shape is a level 5 finding.

Targeted runs while working:

```bash
php artisan test --compact tests/Feature/Views
php artisan test --compact --filter=SidebarTest
php artisan test --compact tests/Unit/ViewModels
```

## Slice 1: the shell and the tokens

This is the slice that can break everything, so it gets the longest check.

```bash
php artisan test --compact tests/Feature/Views/Layouts
```

Then, in the browser, in both themes:

1. Sign in. The page is off white, not warm. Text is Inter. No page title is set in a display face.
2. A navigation column is fixed down the left above 1024px, on the sunken surface, with a hairline
   down its right. No dock floats at the foot of the window anywhere in the product.
3. Narrow the window under 1024px. The column is gone. Press the button in the bar: it slides in
   over the page, the page behind it stops scrolling, focus lands inside it. Press escape: it closes
   and focus returns to the button. Do all of that with the keyboard only.
4. Inside a vault, the column is that vault. The switcher above the navigation lists every vault and
   marks the current one rather than linking to it. The row at the foot is who you are and leads to
   your settings.
5. Inside the settings, the column is the settings, with the way out above the navigation.
6. Follow a link that Turbo drives, then open the drawer again. If it will not open, `navOpen` has
   been declared above `<body>` and Alpine is no longer walking it.
7. Open a menu from a row inside a panel. It is not clipped by the panel.
8. Open a destructive confirmation. Tab repeatedly: the keyboard stays inside it. Escape cancels.
9. Turn on reduced motion in the operating system. Open the drawer and save something. Nothing
   animates, everything still works.
10. Visit a screen from a slice that has not landed yet. It reads in the restored palette, is
    legible, and every control on it works. Transitional is fine. Broken is not.

The mechanical check that nothing was orphaned by the five deletions:

```bash
grep -rn "x-dock\|x-launcher\|x-vault.bar\|x-slot:dock\|x-slot:barEnd" resources/views
```

Zero results, or the deletion was premature.

And that no token was dropped while something still asks for it:

```bash
grep -rnE "terracotta|marigold|forest|indigo|plum|sky-tint|rose-tint|font-display|sand" resources/views app/ViewModels
```

Zero results before the tokens come out of `resources/css/app.css`. Tailwind emits nothing for a
class it cannot resolve, so a leftover reference is a screen that silently loses its tint with
nothing in the build to say so. This is the single most likely silent failure in the feature.

## Slices 2 to 8: per area

For each slice, the same five things on every screen it touches.

1. **Both themes.** Switch and look. No screen may be legible in one and not the other.
2. **Keyboard only.** Reach every control, see the focus ring at all times, complete the screen's
   purpose without a pointer.
3. **No meaning in colour alone.** For every state on the screen, cover the colour and check the word
   is still there. Delivery states, template states, retired values, sensitive fields, expiring share
   links, moderation decisions, the current navigation entry and the current tab.
4. **200% zoom and 375px wide.** Nothing clipped, nothing overlapping, no horizontal scrolling to
   read a sentence.
5. **The capability is intact.** Every filter, sort, page control, drag to reorder and live update
   that worked before still works. This is where a restyle quietly costs something.

Slice specific things worth doing by hand:

- **Slice 2**: sort and filter the contact list, then reorder something and confirm the Ajax swap
  still lands. Delete a vault and read the warning.
- **Slice 3**: traverse a contact's page strip with the arrow keys. Add and remove a repetition on a
  multi value field. Apply a newer template version and read what was set aside. The rotating section
  hues are gone, so check a contact with six or more sections still reads: if it does not, the answer
  is the page strip, not a colour.
- **Slice 4**: the template builder with the keyboard alone, including moving a section between
  pages. Open a menu from a row on the preferences screen and confirm it is not clipped. Check a
  settled slug and a field type are shown as values, not as disabled boxes.
- **Slice 5**: open a share link in a signed out browser. Type a wrong password, then the right one.
  Let one expire. On every one of those three screens, confirm there is no navigation, no way into the
  product, and nothing naming the account.
- **Slice 6**: search the catalogue for something that matches nothing.
- **Slice 7**: as an administrator, walk all seven screens. Then, as a regular user and signed out,
  request an admin address and confirm it still answers as not found. Then check the bar: a regular
  user sees no hint the area exists.
- **Slice 8**: complete registration, sign in, request a magic link, reset a password, answer a two
  factor challenge, on a phone width. The quote panel gives way to the form rather than pushing it
  off the screen.

## Done when

```bash
php artisan test --compact
php artisan monica:localize --check
vendor/bin/pint --format agent
vendor/bin/phpstan analyse
```

All four clean, then:

```bash
grep -rnE "terracotta|marigold|forest|indigo|plum|sand|font-display|Bricolage|Hanken" resources app vite.config.js
```

Zero results. That grep returning nothing is what FR-031 means by one design language left in the
codebase, and it is the last thing to check before the pull request.

Finally, walk the product once from the sign in screen to the admin space in one sitting, in one
theme, and then again in the other. If you can tell which slice landed first, something in
[contracts/tokens.md](./contracts/tokens.md) was not followed.
