# Research: Vault switcher in the breadcrumb

Six decisions the plan rests on. Everything else follows from the code that is already there.

## 1. Where the list of vaults comes from

**Decision**: turn `x-breadcrumb.vault` into a class based component,
`App\View\Components\Breadcrumb\Vault`, rendering the existing
`resources/views/components/breadcrumb/vault.blade.php`. The class queries the vaults of
`$vault->account_id` and passes the collection to the view. All 19 call sites stay exactly as they
are written today.

**Rationale**: `App\View\Components\Vault\Sidebar` already does precisely this for the block being
removed, including the same query, so the pattern is settled in this codebase rather than invented
here. Keying the query off the vault's own account rather than off a `$user` prop keeps the
component's signature unchanged, which is what leaves the 19 call sites untouched, and it is the
correct scope: the vaults on offer are the ones belonging to the account that owns the vault the
reader is in.

**Alternatives considered**:

- *Each of the 19 view models exposes the list.* Nineteen classes, nineteen tests and nineteen call
  sites changed, to move one query. Constitution II says the simpler reading wins.
- *A view composer bound to the component's view.* The codebase has no view composer, and adding the
  first one to save a five line class is a new mechanism for no gain.
- *Take a `$user` prop, as `Vault\Sidebar` does.* It would force `$user` onto all 19 call sites and
  into the component's own tests, for an account id the vault already carries.

## 2. The query count does not move

**Decision**: `Sidebar::vaults()` is deleted in the same change that adds the query to the
breadcrumb. No caching, no sharing between the two components.

**Rationale**: the two components appear on exactly the same 19 screens, verified by listing both
sets of call sites. One query leaves the sidebar and one query arrives in the breadcrumb, so
SC-005 holds with nothing clever. `contact_count` is a column on `vaults`
(`2026_08_08_000000_create_vaults_table.php`), maintained as a running count, so reading the figure
for every vault in the menu costs nothing beyond that single query and raises no N+1.

**Alternatives considered**: memoising the collection on the request. Premature: there is one
consumer once the sidebar's copy is gone.

## 3. The menu is a disclosure, not an ARIA menu

**Decision**: the vault crumb is a button carrying `aria-expanded` and `aria-controls`. The panel it
opens is a plain `<ul>` of links. The current vault is marked with `aria-current="true"` and a check
glyph. No `role="menu"`, no `role="menuitem"`, no `role="menuitemradio"`, no `aria-haspopup`.

**This diverges from the three dropdowns already in the codebase** (`x-dropdown`, `x-actions-menu`,
`x-button.split`), which all declare `role="menu"`. Under the precedence order in `CLAUDE.md` the
existing code normally wins over a rule file, so the divergence is called out here rather than made
quietly.

**Rationale**: `role="menu"` is a contract, not a label. It promises that arrow keys move between
items and that Tab leaves the menu entirely, and a screen reader user who hears "menu" navigates on
that promise. None of the three existing components implement it: their items are ordinary tabbable
controls. Declaring the role without the keyboard behaviour is worse for an assistive technology
user than declaring nothing, because it replaces a widget that behaves as it is announced with one
that does not. Constitution IX makes correct announcement a delivery condition and is
non-negotiable, so a new component does not get to inherit the defect. A disclosure needs no extra
JavaScript: Tab already walks a list of links, Escape already closes, and the announcement is
accurate.

The design source declares `aria-haspopup="menu"`, `role="menu"` and `role="menuitemradio"`. That is
a visual mock and its ARIA is not authoritative; its `menuitemradio` also carries the same unmet
keyboard contract. The visible result is identical either way.

**Alternatives considered**:

- *Follow the house pattern exactly.* Consistent, and consistently wrong in the same way three times
  over. Rejected on constitution IX.
- *Implement the full menu pattern with roving focus.* Correct, and roughly forty lines of Alpine
  that nothing else in the codebase needs, for a list of vaults an account rarely has more than a
  handful of. Rejected on constitution II. It stays the right answer if the three existing dropdowns
  are ever fixed together, which is not this change's job.

Fixing the three existing components is explicitly out of scope. They are named here so the
inconsistency is a known one.

## 4. How the panel is positioned and dismissed

**Decision**: follow `x-dropdown` exactly: `x-data="{ open: false }"`, `x-ref="trigger"`,
`x-anchor.bottom-start.offset.6="$refs.trigger"`, `x-cloak`, `x-show`, the same enter and leave
transitions, `@click.outside`, `@keydown.escape.window` and `@ajax:before.window`. Escape and
outside activation both return focus to the trigger.

**Rationale**: `@alpinejs/anchor` and `@alpinejs/focus` are already installed and `x-dropdown` is the
nearest neighbour to this component. Matching it means the panel behaves the same as every other
panel in the product, and the transitions already honour reduced motion through the shared duration
tokens. The only departure is `bottom-start` in place of `bottom-end`, because the crumb sits at the
left edge of the bar.

**Alternatives considered**: `x-trap`. It is used by the three modals, where trapping is right. A
dropdown that traps focus cannot be left with Tab, which is a regression, so it is not used here.

## 5. What the menu's footer offers

**Decision**: one entry, "New vault", pointing at `route('vaults.new')`, separated from the vaults by
a hairline. The removed sidebar block's "All your vaults" link is not carried over.

**Rationale**: this is the design's own composition, and the full vault list stays one activation
away from the same screen: the product name at the top of the sidebar already links to
`route('vaults.index')`, and `SidebarTest` already asserts that it does. Carrying both entries would
put two routes into a menu whose job is picking a vault.

**Consequence for an existing test**: `VaultControllerTest::it_leads_back_to_the_list_of_vaults`
asserts the string "All your vaults" alongside `route('vaults.index')`. The route assertion still
holds through the product name. The string assertion goes, and the test keeps its meaning.

## 6. Nothing is owed to the API, the product spec or the database

**Decision**: no migration, no model change, no action, no endpoint, no change under `spec/`.

**Rationale**: constitution X asks where a new capability's API coverage is. This change adds no
capability. Switching vault is navigation between two screens that already exist, and reading the
account's vaults is already `GET /vaults` (`routes/api.php:120`), tested. Constitution VII fixes
Vault as a core concept and nothing here touches its nature, so `spec/models/007-vault-in-progress.md`
stands as written. No column is added: `colour` and `contact_count` are both already on `vaults`.

## Strings the change introduces

Three, all through `__()` or `trans_choice()`, all to be produced by `php artisan monica:localize`
and translated in every configured locale:

- the accessible name of the vault crumb when it opens the panel,
- the heading above the list of vaults,
- "New vault".

`:count person|:count people` already exists and is reused for the line under each vault name, which
satisfies FR-015 and the counted phrase rules in `.claude/rules/code-style.md` without a new key.

Two strings stop being used and `monica:localize` should report them for removal: "All your vaults"
and "You are here".
