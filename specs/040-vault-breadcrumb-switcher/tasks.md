---

description: "Task list for the vault switcher in the breadcrumb"
---

# Tasks: Vault switcher in the breadcrumb

**Input**: Design documents from `/specs/040-vault-breadcrumb-switcher/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md),
[data-model.md](./data-model.md), [contracts/breadcrumb-vault.md](./contracts/breadcrumb-vault.md),
[quickstart.md](./quickstart.md)

**Tests**: required, not optional. Constitution III is non-negotiable here: no change ships without
a test covering the behaviour it adds or alters. The tests-optional default of the task template
does not apply to this repository.

**Organization**: grouped by user story. Read the two notes below before starting, because the
stories are not as independent as the template assumes and one ordering constraint is not
negotiable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: US1, US2, US3, US4, mapping to the user stories in [spec.md](./spec.md)

## Two notes on ordering

**US4 must not ship before US1.** US4 removes the only way to switch vault that exists today. If it
lands first, an account with several vaults has no switcher at all. This is the one hard constraint
in the list.

**SC-005 is only restored at US4.** Between US1 and US4 both components query the account's vaults,
so a vault screen runs one query more than it does on `main`. That is expected and temporary. The
query count is checked once, at the end, in T026.

## Path conventions

Laravel monolith, existing tree. No new directory.

---

## Phase 1: Setup

Nothing to set up. No dependency is added, no directory is created, no configuration changes. The
tree this change needs already exists, as recorded in the plan's Structure Decision.

Take the query count baseline now, before anything is edited, because T026 compares against it.

- [X] T001 Record the query count for `route('vaults.show', $vault)` on the current `main`, using a throwaway feature test, and write the figure into this file under T027. **Baseline: 11 queries** on a vault dashboard for an account holding three vaults.

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: the component class that US1 and US3 both need in order to know how many vaults the
account holds.

**⚠️ Blocks US1 and US3.** US2 does not depend on it and may proceed in parallel.

- [X] T002 Create `app/View/Components/Breadcrumb/Vault.php`: a class based View component extending `Illuminate\View\Component`, constructor with promoted `public VaultModel $vault` and `public array $items = []`, importing `App\Models\Vault as VaultModel` the way `app/View/Components/Vault/Sidebar.php` does, `#[Override]` on `render()`, returning `view('components.breadcrumb.vault', ['vaults' => $this->vaults()])`
- [X] T003 Add the private `vaults()` method to `app/View/Components/Breadcrumb/Vault.php`: `VaultModel::query()->where('account_id', $this->vault->account_id)->orderByDesc('is_default')->orderBy('position')->get()`, with the `/** @return Collection<int, VaultModel> */` docblock that PHPStan reads. Ordering and columns are fixed by [data-model.md](./data-model.md)
- [X] T004 Create `tests/Feature/Views/Components/BreadcrumbVaultTest.php` coverage proving the class resolves: render `<x-breadcrumb.vault :vault="$vault" />` with no `$user` prop and assert it still renders the vault name, confirming the tag resolves to the class and the 19 existing call sites keep working unchanged

**Checkpoint**: `php artisan test --compact tests/Feature/Views/Components/BreadcrumbVaultTest.php` passes. The trail renders exactly as before; nothing is visibly different yet.

---

## Phase 3: US2, read where you are without the control getting in the way (P1)

**Goal**: the trail draws itself instead of delegating to `x-breadcrumb`, and gains the colour tile
and the vault name in the first crumb. The crumb is a plain link at this stage, with no panel.

**Why first**: `resources/views/components/breadcrumb/vault.blade.php` currently delegates entirely
to `x-breadcrumb` with the vault prepended as an item, which cannot express a crumb that is a
control. The file has to be restructured before a panel can be added to it, and the restructuring is
worth proving on its own.

**Independent test**: open a screen several levels deep inside a vault. The trail starts at the
vault with its colour tile, each following crumb links to the screen it names except the last, and
the last is marked as the current page.

- [X] T005 [US2] Rewrite `resources/views/components/breadcrumb/vault.blade.php` to draw the trail itself: a `<nav aria-label="{{ __('Breadcrumb') }}">`, the vault crumb first, then one crumb per entry of `$items` separated by a `chevron-right` icon that is hidden from assistive technology. Keep the opening `{{-- --}}` line and the `@var` block, per `.claude/rules/code-style.md`
- [X] T006 [US2] In the same file, draw the vault crumb as a colour tile carrying the first character of `$vault->name` on `style="background: {{ $vault->colour }}"` with `aria-hidden="true"`, followed by the name. Uppercase the character only where it has an uppercase form, as [data-model.md](./data-model.md) specifies
- [X] T007 [US2] In the same file, render each entry of `$items` as a link when it carries a `url` and is not the last, and as a `<span>` with `aria-current="page"` when it is the last. This preserves what `x-breadcrumb` does today, which the three existing tests in `BreadcrumbVaultTest` already assert
- [X] T008 [US2] Make the trail hold at 400 pixels wide and at 200% zoom: truncate a long vault name and long crumb labels rather than letting the trail push the top bar's own controls off. FR-013
- [X] T009 [US2] Extend `tests/Feature/Views/Components/BreadcrumbVaultTest.php`: the first crumb renders the vault name and the tile is hidden from assistive technology, the last crumb carries `aria-current="page"` and no link, intermediate crumbs link to the URL they were given. Assert the contract, never a class string or a tint, per `.claude/rules/testing.md`

**Checkpoint**: the three original tests in `BreadcrumbVaultTest` still pass unchanged, the new ones pass, and the trail looks like the design's "one vault only" state on every screen.

---

## Phase 4: US1, move to another vault from where you already are (P1)

**Goal**: when the account holds more than one vault, the first crumb becomes a control that opens
the list, and picking a vault navigates to its dashboard.

**Depends on**: T002 and T003 for the list, T005 for the file structure.

**Independent test**: sign in with an account holding three vaults, open any screen inside one,
activate the first crumb, pick a different vault, land on that vault's dashboard.

- [X] T010 [US1] In `resources/views/components/breadcrumb/vault.blade.php`, give the vault crumb its two forms: a `<button type="button">` carrying `aria-expanded`, `aria-controls` and a translated accessible name when `$vaults->count() > 1`, and the plain link of T006 otherwise. No CSS property may be named in both the shared class string and a form's own branch, per `.claude/rules/components.md`
- [X] T011 [US1] Add the chevron to the button form only. `x-icon name="chevron-down"`, hidden from assistive technology, rotating on open the way `resources/views/components/dropdown.blade.php` does it
- [X] T012 [US1] Add the panel to the same file, copying the Alpine idiom of `resources/views/components/dropdown.blade.php` exactly: `x-data="{ open: false }"`, `x-ref="trigger"`, `x-cloak`, `x-show`, `x-anchor.bottom-start.offset.6="$refs.trigger"` (start, not end, because the crumb sits at the left edge), the same enter and leave transitions, `@click.outside`, `@keydown.escape.window` and `@ajax:before.window`. No `role="menu"`, no `role="menuitem"`, no `aria-haspopup`: research decision 3 is the reason and it is deliberate
- [X] T013 [US1] Fill the panel: a translated heading, then a `<ul>` with one `<li>` per vault, each an `<a href="{{ route('vaults.show', $option) }}"` holding the colour tile, the name, and the people count through `trans_choice(':count person|:count people', $option->contact_count)`. Reuse the existing key; do not write a new one. `.claude/rules/code-style.md` forbids choosing a wording by comparing a count to one
- [X] T014 [US1] Mark the current vault's entry with `aria-current="true"` and a visible `check` glyph, so the mark is carried by the markup and by a shape and never by colour alone. FR-004, constitution IX
- [X] T015 [US1] Close the panel with a hairline and a single `<a href="{{ route('vaults.new') }}">` labelled with a translated "New vault", carrying a `plus` icon. FR-006
- [X] T016 [US1] Make Escape and outside activation return focus to the trigger. Do not use `x-trap`: a dropdown that traps focus cannot be left with Tab, which is why the three modals use it and `x-dropdown` does not. FR-007
- [X] T017 [US1] Extend `tests/Feature/Views/Components/BreadcrumbVaultTest.php`: with several vaults the crumb is a button carrying `aria-expanded`, the panel lists every vault with its route and its people count, the current vault carries `aria-current="true"`, and the panel offers `route('vaults.new')`
- [X] T018 [P] [US1] Move `the_switcher_lists_every_vault_and_does_not_link_to_the_one_you_are_in` and `the_switcher_leaves_out_the_vaults_of_another_account` from `tests/Feature/Views/Components/SidebarTest.php` into `tests/Feature/Views/Components/BreadcrumbVaultTest.php`, rewriting them against `<x-breadcrumb.vault>`. The second one is the compartment test and must keep proving that another account's vaults never appear

**Checkpoint**: an account with several vaults can switch from the trail. Two switchers now exist, which US4 resolves. A vault screen runs one query more than the baseline, which T026 resolves.

---

## Phase 5: US3, an account with a single vault is not offered a choice (P2)

**Goal**: prove the link form. Most of it was built in T006 and T010; this phase is the explicit
guarantee that nothing opens and no chevron appears.

**Depends on**: T010.

**Independent test**: sign in with an account holding one vault, open a screen inside it, confirm
the first crumb is a link with no chevron and nothing to open.

- [X] T019 [US3] Confirm in `resources/views/components/breadcrumb/vault.blade.php` that the single vault form emits no chevron, no panel markup at all, no `aria-expanded`, and no hover fill, and that it links to `route('vaults.show', $vault)`. FR-009
- [X] T020 [US3] Extend `tests/Feature/Views/Components/BreadcrumbVaultTest.php`: with exactly one vault in the account the crumb resolves to an `<a>` and not a `<button>`, carries no `aria-expanded`, and the panel is absent from the output entirely. Which element a component resolves to is one of the things `.claude/rules/testing.md` explicitly permits asserting

**Checkpoint**: both forms are proven. The design's four states are all reachable.

---

## Phase 6: US4, the side column loses the selector it no longer needs (P2)

**Goal**: remove the block and the query behind it.

**Depends on**: US1 complete. This is the hard ordering constraint stated at the top.

**Independent test**: open any screen inside a vault, confirm the side column holds no vault
selector, and confirm every destination it used to reach is still reachable.

- [X] T021 [US4] Delete the vault selector block from `resources/views/components/vault/sidebar.blade.php`: the whole `<div x-data="{ open: false }" ... class="relative mx-2 mb-1.5">` and everything inside it, including the "You are here" and "All your vaults" strings. Remove the `@var` line for `$vaults` from the docblock at the top
- [X] T022 [US4] Delete the private `vaults()` method from `app/View/Components/Vault/Sidebar.php` and the `'vaults' => $this->vaults()` key from its `render()`. Remove the now unused `use Illuminate\Database\Eloquent\Collection;` import. The component's constructor signature does not change
- [X] T023 [US4] Remove the two switcher tests from `tests/Feature/Views/Components/SidebarTest.php`, which T018 moved, and confirm `the_name_of_the_application_at_the_top_of_either_column_leads_to_the_vaults` still passes: it is now the only thing proving the full vault list stays reachable. FR-012
- [X] T024 [US4] Drop `->assertSee('All your vaults')` from `it_leads_back_to_the_list_of_vaults` in `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php`. Keep the test, its name and its `route('vaults.index')` assertion, which still holds through the product name at the top of the column

**Checkpoint**: one switcher, in the trail. The side column starts at the list of what the vault holds.

---

## Phase 7: Polish and cross-cutting

- [X] T025 Run `php artisan monica:localize`, translate every string it reports as missing in all 30 non-source locales, and remove "All your vaults" and "You are here" now that nothing uses them. Never edit a `lang/*.json` key by hand. Follow `.claude/rules/translations.md` for the typography each language declares, in particular the French narrow non-breaking spaces
- [X] T026 Run `php artisan monica:localize --check`, which is what enforces the counted phrase rules on the people count line and refuses a value breaking a locale's typography
- [X] T027 Count the queries on `route('vaults.show', $vault)` again and confirm the figure matches the baseline recorded in T001, which is **11**. SC-005
- [X] T028 Run `php artisan test --compact` in full. **6537 of 6540 pass.** The three failures (`it_opens_a_vault_whose_name_is_written_in_another_alphabet`, `it_opens_a_contact_named_in_another_alphabet_written_either_way`, `it_reaches_a_contact_named_in_another_alphabet_written_either_way`) return 400 on a raw multibyte URL and fail identically on `main`, verified by stashing this change. Pre-existing, out of scope. `.claude/rules/components.md` warns that a component under nineteen screens is a change to nineteen screens: expect feature tests elsewhere to fail on literal markup, and rewrite each one to assert behaviour rather than restoring the old string
- [X] T029 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T030 [P] Run `vendor/bin/phpstan analyse` and confirm nothing new is reported. CI runs level 5 and the `CLAUDE.md` checklist omits it, so a green suite is not a green pipeline
- [ ] T031 (**owed, cannot be done from here**) Walk the manual table in [quickstart.md](./quickstart.md) end to end: keyboard alone, a screen reader, dark theme, reduced motion, 400 pixels, 200% zoom, a right to left locale, and a very long vault name. Constitution IX is a delivery condition, not an audit at the end, and most of this table is invisible to the suite

---

## Dependencies

```text
T001 (baseline)
  │
  ├─► Phase 2: T002 ─► T003 ─► T004
  │                      │
  │                      ▼
  ├─► Phase 3 (US2): T005 ─► T006 ─► T007 ─► T008 ─► T009
  │                              │
  │                              ▼
  │      Phase 4 (US1): T010 ─► T011 ─► T012 ─► T013 ─► T014 ─► T015 ─► T016 ─► T017
  │                        │                                                      ║
  │                        ▼                                                  T018 (parallel)
  │      Phase 5 (US3): T019 ─► T020
  │                        │
  │                        ▼
  │      Phase 6 (US4): T021 ─► T022 ─► T023 ─► T024      (requires all of Phase 4)
  │                                                │
  ▼                                                ▼
Phase 7: T025 ─► T026 ─► T027 ─► T028 ─► T029 ‖ T030 ─► T031
```

- **T002 blocks T010 and T019**: neither form can be chosen without knowing how many vaults there are.
- **T005 blocks T010**: the panel cannot be added to a file that still delegates to `x-breadcrumb`.
- **Phase 4 blocks Phase 6**: removing the sidebar selector before the trail switches leaves accounts with several vaults no switcher at all.
- **T025 blocks T026**: the check has nothing to check until the strings exist.
- **T027 requires Phase 6**: the query budget is only back to baseline once `Sidebar::vaults()` is gone.

## Parallel opportunities

Thin, and honestly so. Eleven of the thirty-one tasks edit
`resources/views/components/breadcrumb/vault.blade.php`, which serialises most of the work. What can
genuinely run alongside something else:

- **T018 alongside T017**: different files, both test work, neither depends on the other.
- **T029 and T030**: Pint and PHPStan read the same tree and write nothing that collides.
- **Phase 3 alongside Phase 2**: T005 to T008 do not need the component class. T009 does not either. Only T010 onward does.

Do not parallelise within Phase 4. T010 to T016 are successive edits to one file.

## Implementation strategy

**MVP**: Phase 2 plus Phase 3, that is T001 to T009. It ships the design's new trail on all nineteen
screens, with the colour tile and the vault name, and leaves the sidebar selector in place as the
way to switch. Nothing regresses and the visible change lands.

**Increment two**: Phase 4, T010 to T018. Switching from the trail works. Two switchers coexist,
which is untidy but not broken, and a vault screen is one query over budget.

**Increment three**: Phase 5 and Phase 6, T019 to T024. The single vault form is proven and the
duplicate is gone.

**Then Phase 7 in full, before the pull request.** It is not optional polish: T025 to T027 and T031
are the four conditions the constitution's own quality gate names, and T031 in particular covers
what no test in this list can see.
