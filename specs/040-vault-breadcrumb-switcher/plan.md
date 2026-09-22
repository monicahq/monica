# Implementation Plan: Vault switcher in the breadcrumb

**Branch**: `2026-breadcrimb` | **Date**: 2026-09-21 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/040-vault-breadcrumb-switcher/spec.md`

## Summary

The first crumb of the trail inside a vault becomes the way into another vault, and the selector at
the top of the side column is removed. One Blade component gains a panel, one View component class
is created, one View component class loses a private method, and the block in the side column is
deleted. No migration, no model, no action, no route, no endpoint.

The component's signature does not change, so none of the 19 screens that render the trail are
edited. The vaults query moves from `App\View\Components\Vault\Sidebar` to the new
`App\View\Components\Breadcrumb\Vault`, and because both components appear on exactly the same 19
screens, no screen gains or loses a query.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Blade, Tailwind CSS 4, Alpine.js 3 with `@alpinejs/anchor`
(already installed and used by `x-dropdown`), Alpine Ajax, Turbo. No dependency is added.

**Storage**: MySQL, PostgreSQL and SQLite, as the suite runs all three. Read only for this change:
`vaults.name`, `vaults.colour`, `vaults.contact_count`, `vaults.is_default`, `vaults.position`,
`vaults.account_id`. No migration.

**Testing**: PHPUnit. Feature tests under `tests/Feature/Views/Components` for the component,
`tests/Feature/Controllers` for the screens.

**Target Platform**: the signed in web application, desktop, tablet and phone, in every configured
locale including right to left.

**Project Type**: Laravel monolith with server rendered Blade.

**Performance Goals**: no additional database query on any screen (SC-005). One query total, the one
the side column performs today.

**Constraints**: WCAG 2.2 AA as a delivery condition (constitution IX). Readable at 400 pixels wide
and at 200% zoom. No animation under reduced motion.

**Scale/Scope**: 19 screens render the trail. An account typically holds one vault and rarely more
than a handful.

No NEEDS CLARIFICATION remains. The three judgement calls the spec recorded as assumptions
(switching lands on the dashboard, the footer offers creating a vault rather than the full list, the
line under a name counts people) are settled there and are not reopened here.

## Constitution Check

*GATE: passed before Phase 0, re-checked after Phase 1. Re-check result is identical.*

| Principle | Gate | Verdict |
| --- | --- | --- |
| I. Actions hold the business logic | No business logic is added. The View component reads; it does not write. | Pass |
| II. Simplicity over abstraction | One class of about fifteen lines, one Blade file edited, one block deleted. The full ARIA menu pattern was rejected on this principle. See research decision 3. | Pass |
| III. Tested before merged | Every behaviour in the contract is covered. Two tests move from `SidebarTest` to `BreadcrumbVaultTest`, one loses a stale string assertion, and the new states are added. | Pass |
| IV. Laravel conventions | Class based View component mirroring `App\View\Components\Vault\Sidebar`. Named routes. No new mechanism. | Pass |
| V. Localized and safe | Three new strings through `__()`, the count through the existing `trans_choice()` key, `php artisan monica:localize` run and every configured locale translated. Switching is navigation, not destruction, so nothing warns. | Pass |
| VI. Nothing changes under anybody's feet | Nothing is published, versioned or installed here. | Not engaged |
| VII. Stable core | Vault is a fixed concept and its nature is untouched. `spec/models/007-vault-in-progress.md` stands. | Pass |
| VIII. Nothing disappears silently | No data is deactivated or deleted. The removed selector's destinations all stay reachable, which FR-012 states and the quickstart checks. | Pass |
| IX. Accessible to everybody (non-negotiable) | The reason the panel is a disclosure rather than an ARIA menu. Keyboard operable with no extra JavaScript, announced accurately, current vault marked by a glyph as well as a tint, focus visible and returned on Escape, readable at 200%. | Pass, with a deliberate divergence recorded below |
| X. Everything goes through the API | No capability is added. Reading the account's vaults is already `GET /vaults` (`routes/api.php:120`), tested. Nothing owed. | Pass |
| XI. The written record is short | Five files. The design's states live in the spec, the decisions in `research.md`, the component's shape in the contract. Nothing is stated twice. | Pass |

**Recorded divergence.** The three dropdowns already in the codebase (`x-dropdown`,
`x-actions-menu`, `x-button.split`) declare `role="menu"` without implementing the keyboard contract
that role promises. `CLAUDE.md` puts existing code above rule files, so following them would be the
default. This plan does not, because constitution IX is non-negotiable and an inaccurate
announcement is a defect rather than a style. Decision 3 in [research.md](./research.md) carries the
argument. Fixing the three existing components is out of scope and named so the inconsistency is a
known one rather than a discovered one.

## Project Structure

### Documentation (this feature)

```text
specs/040-vault-breadcrumb-switcher/
├── spec.md
├── plan.md                        # This file
├── research.md                    # Six decisions
├── data-model.md                  # What is read, and the one query
├── quickstart.md                  # How to prove it works
├── contracts/
│   └── breadcrumb-vault.md        # The component's signature and behaviour
├── checklists/
│   └── requirements.md
└── tasks.md                       # Not created by /speckit-plan
```

### Source code

```text
app/View/Components/
├── Breadcrumb/
│   └── Vault.php                  # New. Queries the account's vaults for the trail.
└── Vault/
    └── Sidebar.php                # Loses vaults() and the 'vaults' key in render().

resources/views/components/
├── breadcrumb/
│   ├── index.blade.php            # Unchanged. Still the trail outside vaults.
│   └── vault.blade.php            # Rewritten. The crumb, its two forms, and the panel.
└── vault/
    └── sidebar.blade.php          # Loses the selector block at its top.

lang/
└── *.json                         # Three strings added, two removed, every locale.

tests/Feature/
├── Views/Components/
│   ├── BreadcrumbVaultTest.php    # Gains the switcher; receives two tests from SidebarTest.
│   └── SidebarTest.php            # Loses the two switcher tests.
└── Controllers/App/Vaults/
    └── VaultControllerTest.php    # One assertion on a string that no longer exists.
```

**Structure Decision**: the existing tree, unchanged. `app/View/Components` already holds
`Vault/Sidebar.php` doing the same job for the block being removed, so `Breadcrumb/Vault.php` sits
beside it without inventing a location. No new directory.

## Approach

Four steps, in this order, because each leaves the suite green.

1. **Create `App\View\Components\Breadcrumb\Vault`.** Promoted `Vault $vault` and
   `array $items = []` in the constructor, `render()` passing the account's vaults, aliasing the
   model as `VaultModel` the way `Vault\Sidebar` does. `#[Override]` on `render()`, per
   `.claude/rules/code-style.md`. The query and its ordering are in
   [data-model.md](./data-model.md).

2. **Rewrite `resources/views/components/breadcrumb/vault.blade.php`.** Today it delegates entirely
   to `x-breadcrumb` with the vault prepended as an item. It can no longer do that: the first crumb
   is now a control with a panel, which `x-breadcrumb`'s item array cannot express. The file draws
   the vault crumb and its panel itself, then renders the remaining crumbs. The panel copies
   `x-dropdown`'s Alpine idiom exactly, `bottom-start` in place of `bottom-end`. Behaviour is in
   [contracts/breadcrumb-vault.md](./contracts/breadcrumb-vault.md).

   Two traps from `.claude/rules/components.md` apply directly here. No CSS property may be named in
   both the shared class string and a variant branch, since the crumb has two forms that differ in
   background and border. And the panel's ARIA, its Escape handler and its Alpine state are one
   contract: they ship together or the component is broken for a screen reader in a way review will
   not catch.

3. **Remove the selector from `resources/views/components/vault/sidebar.blade.php`** and delete
   `Sidebar::vaults()`, the `'vaults'` key in its `render()`, and the `@var` line for it. The
   component's own signature does not change.

4. **Tests, then the gates.** Move
   `the_switcher_lists_every_vault_and_does_not_link_to_the_one_you_are_in` and
   `the_switcher_leaves_out_the_vaults_of_another_account` from `SidebarTest` to
   `BreadcrumbVaultTest`, where the behaviour now lives. Add the two forms of the crumb, the
   `aria-expanded` and `aria-current` contract, the check glyph, the count line and the "New vault"
   entry. Drop the `assertSee('All your vaults')` from
   `VaultControllerTest::it_leads_back_to_the_list_of_vaults`; its `route('vaults.index')` assertion
   still holds through the product name at the top of the column, so the test keeps its meaning and
   its name. Then run the whole suite, Pint, PHPStan and `monica:localize`, as
   [quickstart.md](./quickstart.md) sets out.

What the tests must not do: `.claude/rules/testing.md` forbids asserting a class string, a tint or a
gutter. The states in the design (rest, hover, focus, one vault only) are reviewed by looking at the
screen. What is asserted is the contract: which element the crumb resolves to, the ARIA, the routes,
the copy, and whether the panel is present at all.

## Risks

- **The suite fails on markup assertions elsewhere.** `.claude/rules/components.md` warns that a
  shared component under twenty screens is a change to twenty screens, and expects a handful of
  feature tests to fail on literal strings. Expected, not a surprise. Each one is rewritten to
  assert behaviour.
- **The trail crowds the top bar on a phone.** The bar already holds the menu button and the bar
  links, and the crumb grows from plain text to a 34 pixel control. FR-013 is the requirement and
  the quickstart checks it at 400 pixels. If it does not fit, the crumbs between the vault and the
  current screen are dropped before the vault crumb is, which the spec's edge case already allows.
- **`monica:localize` reports more than the three new strings.** Likely, since two go out of use.
  Removing them is part of the change, not a separate cleanup.

## Complexity Tracking

No constitution violation requires justification. The one deliberate divergence is from existing
code rather than from the constitution, and it is recorded in the Constitution Check above with its
argument in [research.md](./research.md).
