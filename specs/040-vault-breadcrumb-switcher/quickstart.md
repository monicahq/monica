# Quickstart: validating the vault switcher

How to prove the change works. Run the automated part first; the manual part covers what a test
cannot see.

## Prerequisites

The application runs locally and the assets are built.

```bash
composer run dev
```

## The automated part

```bash
php artisan test --compact tests/Feature/Views/Components/BreadcrumbVaultTest.php
php artisan test --compact tests/Feature/Views/Components/SidebarTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/VaultControllerTest.php
```

Then the whole suite, because `x-breadcrumb.vault` renders on 19 screens and `x-vault.sidebar` on the
same 19. `.claude/rules/components.md` is explicit that changing a shared component is a change to
every screen that uses it.

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
```

`monica:localize --check` is the gate that matters most here: it enforces the counted phrase rules
on the line under each vault name, and it fails if any configured locale is missing a string the
change introduced.

## Setting up data by hand

Two accounts are needed: one with several vaults, one with a single vault.

```bash
php artisan tinker --execute '
$user = App\Models\User::first();
App\Models\Vault::factory()->count(2)->create(["account_id" => $user->account_id]);
echo $user->email;
'
```

Sign in as that user for the several vaults case. Register a second account, which starts with one
vault, for the single vault case.

## The manual part

Each row is a requirement a test cannot reach. Check them on a vault screen several levels deep, for
example a contact inside a vault.

| What to do | What must happen | Covers |
| --- | --- | --- |
| Read the trail | It starts at the vault, with its colour tile and name, and ends at the screen you are on | FR-001, FR-010 |
| Activate the first crumb | A panel opens listing every vault of the account, each with its tile, name and number of people | FR-002, FR-003 |
| Look at the current vault's entry | It carries a check glyph, not only a tint | FR-004 |
| Pick another vault | You land on that vault's dashboard | FR-005 |
| Pick the vault you are in | You stay where that vault begins; nothing is warned | FR-005 |
| Press `Escape` | The panel closes and the focus ring is back on the crumb | FR-007 |
| Activate somewhere outside | The panel closes | FR-007 |
| Reach the crumb with `Tab` alone, open it with `Enter`, walk the list with `Tab`, pick with `Enter` | All of it works without a pointer, and focus is visible at every step | FR-008 |
| Turn on a screen reader and walk the trail | The crumb is announced as a collapsed control that expands, the entries as links, the current one as current, the current screen as the current page. Nothing announces "menu" | FR-008 |
| Sign in to the single vault account | The first crumb has no chevron, no hover fill, and activating it goes to the vault dashboard | FR-009 |
| Look at the side column | No vault selector at its top; it starts at the list of what the vault holds | FR-011 |
| Activate the product name at the top of the column | The full vault list | FR-012 |
| Open the panel, activate "New vault" | The form for a new vault | FR-006, FR-012 |
| Narrow the window to 400 pixels | The trail stays on the screen, the vault crumb stays reachable and operable, nothing is cut off unreachably | FR-013 |
| Zoom to 200% | The same | FR-013 |
| Ask the system for reduced motion, then open the panel | It appears without animation | Edge case, constitution IX |
| Switch the theme to dark, open the panel | Contrast holds and the check glyph is still legible | Constitution IX |
| Switch the locale to French, then to a right to left locale | Every string is translated and the trail reads in the right direction | FR-014, constitution V |
| Give a vault a very long name | It is shortened in the crumb and in the panel; the rest of the trail is not pushed off | Edge case |

## Counting the queries

SC-005 says the change must add no query. Compare before and after on the same screen.

```bash
php artisan tinker --execute '
DB::enableQueryLog();
$user = App\Models\User::first();
auth()->login($user);
app("router")->dispatch(Illuminate\Http\Request::create(route("vaults.show", $user->account->vaults->first())));
echo count(DB::getQueryLog());
'
```

The figure must match the one taken from `main` before the change.
