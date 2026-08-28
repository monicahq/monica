---

description: "Task list for restoring the former design across the application"
---

# Tasks: Restore the Former Design

**Input**: Design documents from `/specs/002-restore-former-design/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/)

**Tests**: included. Principle III makes a test a delivery condition, so test tasks are not optional
here. Where a task adds behaviour (the sidebar, the tab strip), write the test first and watch it
fail. Where a task only changes a utility string, the test moves with the markup in the same task,
because a class string is not a behaviour to drive out.

**Organization**: grouped by the user story it serves, in the spec's priority order. Each story phase
is one commit that leaves the product usable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an incomplete task)
- **[Story]**: which user story the task serves
- Paths are repository relative

## Path conventions

Server rendered Laravel. `resources/css` for the token layer, `resources/views` for every screen and
component, `app/View/Components` for the five shells, `app/ViewModels` for the four view models being
edited, `tests/Feature/Views` for component and shell tests, `tests/Feature/Controllers` for what a
screen says, `tests/Unit/ViewModels` for the view models.

---

## Phase 1: Setup

**Purpose**: get the build honest and settle the one question that blocks a deletion.

- [X] T001 Record the baseline: run `php artisan test --compact` and save the passing count (2588 tests, 13928 assertions, 1 risky) to compare against at every checkpoint
- [X] T002 Swap the bundled families in `vite.config.js`: Inter at 400, 500, 600 and 700 replaces Bricolage Grotesque and Hanken Grotesk, IBM Plex Mono at 400 and 500 stays, and the `latin`, `latin-ext` and `vietnamese` subsets stay on both per [contracts/tokens.md](./contracts/tokens.md)
- [X] T003 Rewrite the font comment in `vite.config.js` so it explains Inter and mono rather than a display face that is gone, keeping the reason the subsets are asked for
- [X] T004 Clear the stale font and build output (`public/build`, `public/fonts`, `public/fonts-manifest.dev.json`) and run `bun run build` to prove the new families resolve
- [X] T005 **Ask the user to approve removing `tests/Feature/Views/Components/DockTest.php`**, per `.claude/rules/testing.md` and Principle III. Do not delete it before the answer. Coverage moves to `SidebarTest` in T018

**Checkpoint**: the build serves Inter. Every screen still renders in the replaced palette, which is expected.

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: the token layer. Nothing else can be restyled until the tokens the utilities resolve to
are the restored ones.

**CRITICAL**: T007 and T008 must land in the same commit as T006. Tailwind emits nothing for a class
it cannot resolve, so dropping a token while PHP still writes it produces a screen that silently
loses its tint with nothing in the build to say so.

- [X] T006 Rewrite the `@theme` block and the `:root` and `.dark` palettes in `resources/css/app.css` to the token names and values in [contracts/tokens.md](./contracts/tokens.md): the twenty three colour tokens with both themes, Inter as `--font-sans`, IBM Plex Mono as `--font-mono`, the three radii, and the five toast keyframes unchanged
- [X] T007 Remove `--font-display`, the `display-1` and `display-2` type steps, and the twenty one object hue tokens (`terracotta`, `marigold`, `forest`, `indigo`, `plum`, `sky`, `rose` with their `-deep` and `-tint` variants) plus `sand`, `sand-deep`, `ink-soft`, `focus-ring`, `scrim`, `link`, `link-hover`, the five `action-*` tokens, `error-hover` and `error-tint` from `resources/css/app.css`
- [X] T008 Re-point the surviving named type steps (`page-title`, `section-title`, `card-title`, `label`, `nav`, `code`) in `resources/css/app.css` to the former direction's flatter sizes, weights and tracking, keeping one utility per step
- [X] T009 [P] Remove `iconClass` from the row shape in `app/ViewModels/Settings/SettingsViewModel.php`, update the array shape docblock, and keep `icon`
- [X] T010 [P] Replace `typeIconClass` and `typeTextClass` with a single `type` key holding `template` or `section` in `app/ViewModels/Marketplace/MarketplaceViewModel.php`, and update the array shape docblock
- [X] T011 [P] Remove `titleClass` and `iconClass` from the block shapes in `app/ViewModels/Vaults/VaultDashboardViewModel.php` and update the docblocks
- [X] T012 [P] Remove the `FACT_HUES` and `SECTION_HUES` constants and the keys carrying them from `app/ViewModels/Vaults/ContactViewModel.php`, per [research decision 19](./research.md)
- [X] T013 [P] Update `tests/Unit/ViewModels/Marketplace/MarketplaceViewModelTest.php` to assert `type` rather than the two hue class strings
- [X] T014 [P] Change the one hue class assertion in `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php` to assert the copy that carries the same meaning
- [X] T015 [P] Change the one hue class assertion in `tests/Feature/Controllers/App/Settings/Account/Logs/LogControllerTest.php` to assert the delivery state's word rather than its colour
- [X] T016 Verify no PHP still asks for a removed token: `grep -rnE "terracotta|marigold|forest|indigo|plum|sky-tint|rose-tint" app` returns nothing. Blade keeps hue classes until each screen's own phase, which is what transitional means, so the same grep over `resources/views` is the polish check in T123 and not this one
- [X] T017 Run `php artisan test --compact` and `vendor/bin/phpstan analyse`. Expect the four view model tests and any screen test asserting a hue to be the only failures, and fix them rather than the palette

**Checkpoint**: every screen in the product now reads in the restored palette and type, with its
current structure. Screens look transitional. None may be broken, illegible or unreachable.

---

## Phase 3: User Story 1 - The navigation column comes back (Priority: P1) 🎯 MVP

**Goal**: the sidebar shell replaces the dock shell, both navigation columns exist, and the dock,
the launcher and the vault bar are gone.

**Independent Test**: reach every area of the product from the column alone, on a wide window and a
narrow one, with the keyboard only, in both themes. The dock is nowhere and no screen is unreachable.

### Tests for User Story 1

- [X] T018 [P] [US1] Write `tests/Feature/Views/Components/SidebarTest.php` covering the vault column: the destinations it offers, `aria-current` on the current one, the switcher listing every vault of the account and marking the current one rather than linking it, the entries drawn and not offered, and the identity row at the foot leading to the settings
- [X] T019 [P] [US1] Extend `tests/Feature/Views/Components/SidebarTest.php` (or a sibling case in the same file) for the settings column: its destinations, the way out above them, `aria-current`, and the `settings-nav` id the profile screen's Ajax form targets
- [X] T020 [P] [US1] Rewrite `tests/Feature/Views/Layouts/TopBarTest.php` for the restored bar: the menu button below the large breakpoint, the breadcrumb slot, the actions slot, and the signed in email address shown when there are no actions
- [X] T021 [P] [US1] Add a case to `tests/Feature/Views/Layouts/TopBarTest.php` asserting the shell renders the `footer` slot as a bar at the foot of the window, and that `wide` swaps the reading column for the content column

### Implementation for User Story 1

- [X] T022 [US1] Turn `resources/views/layouts/shell.blade.php` into the sidebar shell per [contracts/shell.md](./contracts/shell.md): the grid at `264px` above `lg`, the drawer backdrop below it, the sticky `h-13` bar, the capped content column, the optional footer bar. Add the `sidebar`, `breadcrumb`, `actions` and `footer` slots and remove `dock` and `barEnd`
- [X] T023 [US1] Declare `x-data="{ navOpen: false }"` on the grid inside `<body>` in `resources/views/layouts/shell.blade.php`, with the escape handler and the `overflow-hidden` effect, and carry over the comment from `11f266e:resources/views/layouts/app.blade.php` explaining why it cannot sit above the body
- [X] T024 [US1] Update the docblock of `app/View/Components/Shell.php` to describe the sidebar shell. The class keeps its name and its two properties per [research decision 3](./research.md)
- [X] T025 [US1] Create `resources/views/components/vault/sidebar.blade.php` from `11f266e:resources/views/components/vault/sidebar.blade.php`, restyled to the restored tokens, taking `vault`, `current` and `taskCount`, and querying the account's vaults itself rather than taking a `vaults` prop, per [research decision 4](./research.md)
- [X] T026 [US1] Add the entries the former tree did not have to `resources/views/components/vault/sidebar.blade.php`: the share links under a contact, and anything moved off the bar
- [X] T027 [US1] Create `resources/views/components/settings/sidebar.blade.php` from `11f266e:resources/views/components/settings/sidebar.blade.php`, restyled, keeping the `settings-nav` id, the plain closed class rather than an Alpine binding, and adding entries for the template share, export and import screens
- [X] T028 [US1] Decided: the three links stay in the bar, restyled, and `BarLinksTest` is kept. They are about the product rather than about the vault, so a column about one vault is the wrong home for them. Original task: decide `resources/views/components/bar-links.blade.php`: keep and restyle it if the documentation, marketplace and administrator links stay in the bar, or fold them into the columns. Record the decision in [contracts/shell.md](./contracts/shell.md) and rewrite or remove `tests/Feature/Views/Components/BarLinksTest.php` to match, asking for approval first if the answer is removal
- [X] T029 [US1] Rename the `bar` slot to `breadcrumb` and replace the `dock` slot with `sidebar` on all thirty screens using `<x-shell>`, per the mapping table in [contracts/shell.md](./contracts/shell.md)
- [X] T030 [US1] Move `resources/views/app/vaults/index.blade.php` from `<x-shell>` to `<x-account-layout>`: it is the one shell screen with no vault to navigate, which is why it had no dock
- [X] T031 [US1] Give the four screens that pass no dock today a vault column: `resources/views/app/vaults/contacts/set-aside.blade.php` and the three under `resources/views/app/vaults/contacts/share-links/`
- [X] T032 [US1] Restyle `resources/views/layouts/account.blade.php` to the restored tokens, keeping its structure: bar with the mark, theme switch, way to the settings and avatar, then the optional breadcrumb strip
- [X] T033 [US1] Verify every destination the dock, launcher and vault bar offered is reachable from a column or the bar, by listing the anchors in each of the five files and finding each one in the restored navigation
- [X] T034 [US1] Delete `resources/views/components/dock.blade.php`, `resources/views/components/launcher/index.blade.php`, `resources/views/components/launcher/tile.blade.php`, `resources/views/components/launcher/tile-mark.blade.php` and `resources/views/components/vault/bar.blade.php`, only after T033 passes
- [X] T035 [US1] Delete `tests/Feature/Views/Components/DockTest.php`, only if T005 was approved
- [X] T036 [US1] Confirm nothing references what is gone: `grep -rn "x-dock\|x-launcher\|x-vault.bar\|x-slot:dock\|x-slot:barEnd" resources/views` returns nothing
- [~] T037 [US1] Walk steps 1 to 10 of the slice 1 check in [quickstart.md](./quickstart.md) in both themes, including the Turbo navigation test for `navOpen` and the reduced motion pass. **Not done: this needs a person at a browser.** The suite covers that every screen still answers 200 and that the column, the drawer control, `aria-current` and the focus rings are in the markup; it cannot tell you whether the result looks right
- [X] T038 [US1] Run `php artisan monica:localize` and translate everything it reports in all nine locales, then `php artisan monica:localize --check`

**Checkpoint**: the product has one shell and one navigation. Screens below are still transitional in
their own structure but every one is usable, keyboard operable and legible in both themes.

---

## Phase 4: User Story 2 - The vault path on the restored design (Priority: P2)

**Goal**: the vault list, the vault form, the dashboard, the contact list, the contact form and the
vault settings are drawn in the restored language.

**Independent Test**: create a vault, open it, create a contact, sort and filter the list, change the
vault's settings, delete the vault, without meeting a screen in the replaced language and without
losing a control.

### Shared components this story is the first to need

- [ ] T039 [P] [US2] Restyle `resources/views/components/panel.blade.php` to a hairline bordered container with no shadow, keeping its `title`, `description`, `additionalInfo`, `help` and `footer` slots and its padding prop
- [ ] T040 [P] [US2] Restyle `resources/views/components/row.blade.php` to a row separated by `hairline-soft`
- [ ] T041 [P] [US2] Restyle `resources/views/components/page-header.blade.php` so the page title is set in the interface family at the `page-title` step
- [ ] T042 [P] [US2] Restyle the five button components in `resources/views/components/button/` (`index`, `secondary`, `danger`, `ghost`, `bar`), keeping their variants and their focus rings
- [ ] T043 [P] [US2] Restyle the form primitives: `resources/views/components/input.blade.php`, `textarea.blade.php`, `select.blade.php`, `checkbox.blade.php`, `label.blade.php`, `error.blade.php`, `help.blade.php`, `form.blade.php`, `file-input.blade.php`
- [ ] T044 [P] [US2] Restyle `resources/views/components/badge.blade.php` to a tint plus a hairline plus the word, with the four tones `neutral`, `success`, `warning` and `danger` from [data-model.md](./data-model.md)
- [ ] T045 [P] [US2] Restyle `resources/views/components/chip.blade.php` so the chip in force fills with ink, which is how the restored direction says every toggle
- [ ] T046 [P] [US2] Restyle `resources/views/components/notice.blade.php`, keeping the glyph beside the tint that stops it being colour alone
- [ ] T047 [P] [US2] Restyle `resources/views/components/dropdown.blade.php`, keeping `x-anchor`
- [ ] T048 [P] [US2] Restyle `resources/views/components/modal/warning.blade.php` and `resources/views/components/modal/form.blade.php`, keeping `x-trap.inert` on both
- [ ] T049 [P] [US2] Restyle `resources/views/components/empty-state.blade.php`, `breadcrumb.blade.php`, `link.blade.php`, `status.blade.php`, `icon.blade.php` (stroke weight to 1.4), `avatar.blade.php`, `avatar-initials.blade.php`, `entity-icon.blade.php`, `image.blade.php`, `option-row.blade.php`, `language-picker.blade.php`, `theme-toggle.blade.php`, `toaster.blade.php`, `turnstile.blade.php`, `vault/search.blade.php`
- [ ] T050 [P] [US2] Update `tests/Feature/Views/Components/PanelTest.php`, `RowTest.php`, `BadgeTest.php`, `ChipTest.php`, `NoticeTest.php`, `IconTest.php`, `OptionRowTest.php`, `SelectTest.php`, `EmptyStateTest.php`, `ButtonTest.php`, `InputTest.php`, `DropdownTest.php`, `AvatarInitialsTest.php`, `VaultSearchTest.php` so each asserts the copy and the markup that carries the meaning, and a class only where the class is the meaning

### Screens for User Story 2

- [ ] T051 [US2] Restyle `resources/views/app/vaults/index.blade.php`: each vault a row carrying its name, its content colour and the way in, plus the way to create another
- [ ] T052 [US2] Restyle `resources/views/app/vaults/form.blade.php` and move it from `<x-box>` to `<x-panel>`
- [ ] T053 [US2] Restyle `resources/views/app/vaults/show.blade.php`, the dashboard, whose blocks no longer carry `titleClass` or `iconClass` after T011 and are told apart by heading and position
- [ ] T054 [US2] Restyle `resources/views/app/vaults/settings.blade.php`, keeping the `footer` slot and the warning in front of every destructive action
- [ ] T055 [US2] Restyle `resources/views/app/vaults/contacts/index.blade.php`, keeping every sort, filter and page control and the Ajax swap that updates the list
- [ ] T056 [US2] Restyle `resources/views/app/vaults/contacts/form.blade.php`, keeping the error attached to its field, announced, and never colour alone
- [ ] T057 [US2] Restyle `resources/views/app/getting-started/index.blade.php`
- [ ] T058 [US2] Run the five per screen checks from [quickstart.md](./quickstart.md) on all seven screens, plus the slice 2 specifics: sort and filter the contact list, reorder something and confirm the Ajax swap lands, delete a vault and read the warning
- [ ] T059 [US2] Run `php artisan test --compact tests/Feature/Views tests/Feature/Controllers/App/Vaults` and `php artisan monica:localize --check`

**Checkpoint**: the daily path is consistent end to end.

---

## Phase 5: User Story 3 - A contact on the restored design (Priority: P2)

**Goal**: the contact detail with its pages, sections, values, groups, set aside values and version
history, plus editing and deleting, all in the restored language.

**Independent Test**: open a contact, read every page, edit each field type including a photo and a
document, apply a newer template version, read the version history and restore from it, and view the
set aside values.

### Tests and the one new component

- [ ] T060 [P] [US3] Write `tests/Feature/Views/Components/TabsTest.php` first: arrow key traversal, one tab in the tab order, `aria-selected` on the current tab, and the current tab marked by more than its colour
- [ ] T061 [US3] Create `resources/views/components/tabs.blade.php` satisfying T060, replacing the page strip markup currently repeated on the contact and the builder
- [ ] T062 [P] [US3] Restyle `resources/views/components/field-input.blade.php` keeping the label, control, hint and error as one definition
- [ ] T063 [P] [US3] Restyle `resources/views/components/field-repetitions.blade.php`, keeping each repetition labelled so a screen reader says which one it is
- [ ] T064 [P] [US3] Restyle `resources/views/components/field-value.blade.php`, machine values in mono, sensitive fields marked by a word
- [ ] T065 [P] [US3] Restyle `resources/views/components/contact-avatar.blade.php`, content colour behind the initials in a neutral frame
- [ ] T066 [P] [US3] Update `tests/Feature/Views/Components/FieldValueTest.php` to assert the copy and the sensitive marking rather than a hue class

### Screens for User Story 3

- [ ] T067 [US3] Restyle `resources/views/app/vaults/contacts/show.blade.php` and put the page strip on `<x-tabs>`
- [ ] T068 [US3] Restyle `resources/views/app/vaults/contacts/_page.blade.php` and `resources/views/app/vaults/contacts/_section.blade.php`, which no longer receive a rotating hue after T012 and are told apart by heading, space and position
- [ ] T069 [US3] Restyle `resources/views/app/vaults/contacts/_groups.blade.php`
- [ ] T070 [US3] Restyle `resources/views/app/vaults/contacts/edit.blade.php`, keeping the `footer` slot for a long form
- [ ] T071 [US3] Restyle `resources/views/app/vaults/contacts/_version-history.blade.php`, each entry saying who changed what and when, with confirmation still in front of restoring
- [ ] T072 [US3] Restyle `resources/views/app/vaults/contacts/set-aside.blade.php`
- [ ] T073 [US3] Run the five per screen checks on all six screens, plus the slice 3 specifics: traverse the page strip with the arrow keys, add and remove a repetition, apply a newer template version and read what was set aside, and confirm a contact with six or more sections still reads now the rotation is gone
- [ ] T074 [US3] Run `php artisan test --compact tests/Feature/Controllers/App/Vaults tests/Unit/ViewModels/Vaults` and `php artisan monica:localize --check`

**Checkpoint**: the largest surface built after pull request 50 is on the restored design.

---

## Phase 6: User Story 4 - The settings on the restored design (Priority: P3)

**Goal**: the settings home and all nineteen screens under it, the template builder last.

**Independent Test**: walk every settings screen, change something on each, and confirm each change
persists and is confirmed.

- [ ] T075 [US4] Restyle `resources/views/app/settings/index.blade.php`, the settings home, whose rows no longer carry `iconClass` after T009
- [ ] T076 [P] [US4] Restyle `resources/views/app/settings/account/profile/index.blade.php`
- [ ] T077 [P] [US4] Restyle `resources/views/app/settings/account/preferences/index.blade.php` and `_carried-values.blade.php`, keeping picking as the save and each row its own form
- [ ] T078 [P] [US4] Restyle `resources/views/app/settings/account/security/index.blade.php` and its four partials (`_api-keys`, `_change-password`, `_two-factor-off`, `_two-factor-on`) plus `two-factor.blade.php`
- [ ] T079 [P] [US4] Restyle `resources/views/app/settings/account/logs/index.blade.php`, `emails.blade.php`, `_log-row.blade.php` and `_email-sent-row.blade.php`, keeping the delivery state as a labelled badge
- [ ] T080 [P] [US4] Restyle `resources/views/app/settings/account/default-values/index.blade.php`, `_list.blade.php` and `_value.blade.php`, keeping the drag to reorder, adding a keyboard equivalent, and keeping a retired value marked by a word as well as struck out
- [ ] T081 [P] [US4] Restyle `resources/views/app/settings/account/entity-types/index.blade.php`, `_row.blade.php`, `_delete.blade.php` and `form.blade.php`, keeping the drag to reorder and showing a settled slug as a value rather than a disabled box
- [ ] T082 [P] [US4] Restyle `resources/views/app/settings/account/templates/index.blade.php`, `_row.blade.php` and `form.blade.php`, the three template states as badges and the filters as chips
- [ ] T083 [P] [US4] Restyle the five sharing and moving screens under `resources/views/app/settings/account/templates/`: `share.blade.php`, `export.blade.php`, `import.blade.php`, `section-share.blade.php`, `section-export.blade.php`
- [ ] T084 [US4] Restyle `resources/views/app/settings/account/templates/builder.blade.php` with its four partials (`_pages`, `_section`, `_field`, `_inspector`), putting the page strip on `<x-tabs>`, keeping `wide`, keeping the inspector stuck to the top, and keeping the keyboard able to traverse pages and move a section between them
- [ ] T085 [US4] Restyle `resources/views/app/settings/account/templates/field-type.blade.php`, keeping `wide` and showing a field's type as a value rather than a disabled box
- [ ] T086 [US4] Confirm no menu is clipped: open the menu behind each preferences row and each panel header action, and check each is anchored against the window
- [ ] T087 [US4] Run the five per screen checks on all nineteen screens, plus the builder with the keyboard alone
- [ ] T088 [US4] Run `php artisan test --compact tests/Feature/Controllers/App/Settings tests/Unit/ViewModels/Settings` and `php artisan monica:localize --check`

**Checkpoint**: every screen a signed in user can reach is on the restored design.

---

## Phase 7: User Story 5 - Sharing by link (Priority: P3)

**Goal**: the share link screens, and the three screens a stranger sees.

**Independent Test**: create a share link with a password and an expiry, open it signed out, read the
record, let it expire, and confirm the password and gone screens are both restored.

- [ ] T089 [P] [US5] Restyle `resources/views/app/vaults/contacts/share-links/index.blade.php`, each link saying what it points at, when it expires and whether it has a password, with revoking behind a warning
- [ ] T090 [P] [US5] Restyle `resources/views/app/vaults/contacts/share-links/form.blade.php`
- [ ] T091 [P] [US5] Restyle `resources/views/app/vaults/contacts/share-links/show.blade.php`
- [ ] T092 [US5] Restyle `resources/views/layouts/guest.blade.php` to the restored tokens, keeping the quote panel beside the content and the rule that below the small breakpoint the panel gives way rather than pushing the content off the screen
- [ ] T093 [P] [US5] Restyle `resources/views/shared/contact.blade.php`, confirming the excluded content is still excluded and nothing added reveals more
- [ ] T094 [P] [US5] Restyle `resources/views/shared/password.blade.php`, a wrong password refused with the error attached to the field
- [ ] T095 [P] [US5] Restyle `resources/views/shared/gone.blade.php`, saying nothing about what the link pointed at and offering no way into the product
- [ ] T096 [US5] Run the slice 5 check from [quickstart.md](./quickstart.md) in a signed out browser: wrong password, right password, expired link, and on all three confirm no navigation, no way in, and nothing naming the account
- [ ] T097 [US5] Run `php artisan test --compact --filter=Share` and `php artisan monica:localize --check`

**Checkpoint**: the only screens a stranger sees are restored.

---

## Phase 8: User Story 6 - The marketplace (Priority: P4)

**Goal**: the catalogue reads as part of the same product.

**Independent Test**: open the marketplace, read its statistics, search the catalogue, search for
something that matches nothing.

- [ ] T098 [US6] Restyle `resources/views/layouts/marketplace.blade.php` to the restored tokens, keeping a shell of its own
- [ ] T099 [US6] Restyle `resources/views/app/marketplace/index.blade.php`
- [ ] T100 [US6] Restyle `resources/views/app/marketplace/_card.blade.php`, which now receives `type` rather than two hue class strings after T010, with the word on the card carrying the distinction
- [ ] T101 [US6] Restyle `resources/views/app/marketplace/_statistics.blade.php`
- [ ] T102 [US6] Run the five per screen checks on the three screens and search for something that matches nothing
- [ ] T103 [US6] Run `php artisan test --compact tests/Feature/Controllers/App/Marketplace tests/Unit/ViewModels/Marketplace`

**Checkpoint**: the catalogue matches the product.

---

## Phase 9: User Story 7 - The admin space (Priority: P4)

**Goal**: the seven admin screens and their shell, restored, and still invisible to everybody else.

**Independent Test**: as an administrator walk all seven screens, approve and reject a submission,
then as a regular user and signed out confirm every admin address still answers as not found.

- [ ] T104 [US7] Restyle `resources/views/layouts/admin.blade.php`, keeping a shell of its own so an administrator always knows which area they are in
- [ ] T105 [P] [US7] Restyle `resources/views/app/admin/overview/index.blade.php`
- [ ] T106 [P] [US7] Restyle `resources/views/app/admin/accounts/index.blade.php` and `show.blade.php`
- [ ] T107 [P] [US7] Restyle `resources/views/app/admin/moderation/index.blade.php` and `show.blade.php`, the decision states as badges
- [ ] T108 [P] [US7] Restyle `resources/views/app/admin/logs/index.blade.php`
- [ ] T109 [P] [US7] Restyle `resources/views/app/admin/administrators/index.blade.php`, every action behind a warning
- [ ] T110 [US7] Rewrite `tests/Feature/Views/Layouts/AdminLayoutTest.php` for the restored shell
- [ ] T111 [US7] Run the five per screen checks on all seven screens, then confirm a regular user and a visitor still get not found on every admin address and see no hint the area exists
- [ ] T112 [US7] Run `php artisan test --compact tests/Feature/Controllers/App/Admin --filter=Admin`

**Checkpoint**: every area of the product is restored.

---

## Phase 10: User Story 8 - Signing in and registering (Priority: P5)

**Goal**: the credentials screens and the landing screen a visitor sees.

**Independent Test**: register, sign out, sign in, request a magic link, reset a password, answer a
two factor challenge, on a phone and on a wide window, in both themes.

- [ ] T113 [P] [US8] Restyle `resources/views/app/auth/login.blade.php` and `register.blade.php`, moving both from `<x-box>` to `<x-panel>`
- [ ] T114 [P] [US8] Restyle `resources/views/app/auth/request-magic-link.blade.php` and `magic-link-sent.blade.php`, moving both to `<x-panel>`
- [ ] T115 [P] [US8] Restyle `resources/views/app/auth/forgot-password.blade.php` and `reset-password.blade.php`, moving both to `<x-panel>`
- [ ] T116 [P] [US8] Restyle `resources/views/app/auth/two-factor-challenge.blade.php` and `verify-email.blade.php`, moving both to `<x-panel>`
- [ ] T117 [P] [US8] Restyle `resources/views/app/auth/_quote.blade.php`, keeping the `.quote-panel` gradient and `.quote-card` shadow classes and their variables, which are in both trees
- [ ] T118 [US8] Restyle `resources/views/welcome.blade.php`, which `HomeController` serves at `/` to a visitor. The spec's FR-019 covers it by rule; its enumeration does not name it
- [ ] T119 [US8] Run the five per screen checks on all nine screens at phone width, confirming the quote panel gives way to the form
- [ ] T120 [US8] Run `php artisan test --compact tests/Feature/Controllers/App/Auth tests/Unit/ViewModels/Auth`

**Checkpoint**: every screen in the product is on the restored design.

---

## Phase 11: Polish and cross cutting

**Purpose**: remove what is now unreferenced, and prove FR-031.

- [ ] T121 Confirm `resources/views/components/box/index.blade.php` and `box/row.blade.php` have no callers left: `grep -rn "x-box" resources/views` returns nothing but the two files themselves
- [ ] T122 Delete `resources/views/components/box/index.blade.php` and `resources/views/components/box/row.blade.php`, and the `box` directory with them
- [ ] T123 Prove one design language is left: `grep -rnE "terracotta|marigold|forest|indigo|plum|sand|font-display|Bricolage|Hanken" resources app vite.config.js` returns nothing
- [ ] T124 Sweep every screen for information carried by colour alone, state by state, per FR-006: delivery states, template states, retired values, sensitive fields, expiring links, moderation decisions, the current navigation entry and the current tab
- [ ] T125 Sweep every screen at 200% zoom and at 375px wide, per FR-027 and SC-005
- [ ] T126 Sweep every screen with the keyboard alone, per FR-027 and SC-003, including every drawer, dialog, tab strip and reorderable list
- [ ] T127 Run `php artisan monica:localize` a final time and translate everything reported in all nine locales, then `php artisan monica:localize --check`
- [ ] T128 Run `vendor/bin/pint --format agent` and `vendor/bin/phpstan analyse`, fixing rather than ignoring
- [ ] T129 Run the full suite on all three connections and confirm the count is at least the 2588 recorded in T001, with no test removed beyond what T005 and T028 approved
- [ ] T130 Walk the whole product once per theme from the sign in screen to the admin space, per the last step of [quickstart.md](./quickstart.md). If you can tell which slice landed first, something in [contracts/tokens.md](./contracts/tokens.md) was not followed

---

## Dependencies and execution order

### Phase dependencies

- **Phase 1 (Setup)**: no dependencies. T005 asks a question whose answer is only needed by T035
- **Phase 2 (Foundational)**: depends on Phase 1. **Blocks every story.** T006, T007 and T008 are one commit with T009 to T012
- **Phase 3 (US1)**: depends on Phase 2. **Blocks every remaining story**, because a screen cannot be restyled into a shell that does not exist yet
- **Phases 4 to 10 (US2 to US8)**: each depends on Phase 3 and on nothing else. They are independent of one another
- **Phase 11 (Polish)**: T121 and T122 depend on US2 and US8, which hold the eleven `box` callers between them. The rest depends on every story being done

### The one ordering trap

US1 blocks the rest, which the template's usual shape does not expect. That is not a modelling
mistake: the shell is what every screen sits inside, so the spec's P1 story is genuinely a
prerequisite rather than a parallel slice. Phases 4 to 10 are parallel with each other, not with
Phase 3.

### Within a story

Shared components before the screens that use them. A component is never left half migrated across
two commits, which is why T039 to T050 sit inside US2 rather than in a phase of their own: US2 is
simply the first story to need them.

### Parallel opportunities

- Phase 2: T009 to T015 are seven different files, all parallel, after T006 to T008
- Phase 3: T018 to T021 are the tests and are parallel. T025 and T027 are the two columns and are parallel
- Phase 4: T039 to T050 are twelve independent component groups, all parallel
- Phase 5: T062 to T066 are parallel. T060 must precede T061
- Phase 6: T076 to T083 are eight independent screen groups, all parallel. T084 and T085 come last
- Phases 4 to 10 can be worked by different people at the same time once Phase 3 lands

---

## Parallel example: Phase 4

```bash
# The component restyles, all different files:
Task: "Restyle resources/views/components/panel.blade.php"
Task: "Restyle resources/views/components/row.blade.php"
Task: "Restyle resources/views/components/badge.blade.php"
Task: "Restyle resources/views/components/chip.blade.php"
Task: "Restyle the five files in resources/views/components/button/"
Task: "Restyle the nine form primitive components"
```

---

## Implementation strategy

### MVP

Phases 1, 2 and 3. That is the token layer, the fonts, the sidebar shell, both navigation columns and
the five deletions. At that point the product has changed direction everywhere, every screen is
usable, and each remaining phase is cosmetic rather than structural. It is the only stopping point
that is defensible on its own: stopping mid Phase 2 leaves tokens that nothing resolves, and stopping
mid Phase 3 leaves two navigations.

### Incremental delivery

One branch, one pull request, one commit per phase, in order. A reviewer reads the Phase 2 and 3
commits against the whole product, then each later commit against its own screens.

### Where this will go wrong

- A removed token with a live reference. T016 and T123 are the guard, and Tailwind will not warn you
- A reworded string. `monica:localize --check` after every phase, not at the end
- A lost focus trap. T048 names it, and every dialog is checked again in T126
- A lost capability. T055, T080 and T081 name the reorder and the Ajax swap because those are the
  ones a restyle quietly costs

---

## Notes

- Tests are not optional in this project. Principle III makes them a delivery condition
- No test file is deleted without the user's approval. T005 and T028 are the only two candidates
- Commit after each phase, with a subject naming the outcome rather than the mechanics
- Every phase ends with the localisation check, because that is the gate this feature is most likely
  to trip
