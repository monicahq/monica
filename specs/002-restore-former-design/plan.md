# Implementation Plan: Restore the Former Design

**Branch**: `2026-08-26-restore-former-design` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/002-restore-former-design/spec.md`

## Summary

Put back the design direction pull request 50 replaced, and make it the design of the whole product,
including the thirty odd pull requests of features built after it that have no former version to
return to. The restored direction is a near neutral palette, one interface text family, separation
carried by a hairline and by space, and a navigation column down the left instead of a floating
dock.

Technically: rewrite the token layer in `resources/css/app.css` to the palette and type of commit
`11f266e`, swap the bundled fonts in `vite.config.js` from Bricolage Grotesque and Hanken Grotesk
back to Inter, turn `layouts/shell.blade.php` from the dock shell into the sidebar shell and give it
a `sidebar` slot, restore the two navigation columns from the former tree with the dock's own habit
of finding its vaults itself, restyle forty three shared components, author one that neither tree
has (the tab strip), delete five that the dock brought with it, consolidate the two panel components
the codebase is currently carrying down to one, strip the hue class strings out of four view models,
and walk all sixty one screens.

Nothing is reverted with git: forty one pull requests sit between `89cf68a` and `HEAD` and most of
them add features on top of the replaced components, so `11f266e` is a reference to read from and
not a state to return to.

No migration, no schema change, no new dependency, and no capability lost.

## Technical Context

**Language/Version**: PHP 8.4.23

**Primary Dependencies**: Laravel 13.23, Blade, Tailwind CSS 4, Alpine.js with the morph, focus,
anchor and Ajax plugins, Turbo, instant.page. Nothing is added. `@alpinejs/focus` and
`@alpinejs/anchor` are explicitly retained: they are what the dialogs and the anchored menus depend
on, and FR-023 keeps both.

**Storage**: untouched. No migration. The five colour columns hold hex strings rather than token
names, so no stored value is orphaned by a token being deleted. See [data-model.md](./data-model.md).

**Testing**: PHPUnit, 338 test files holding 2588 tests today, all of which must still pass. `tests/Feature/Views/Components`
for the shared components, `tests/Feature/Views/Layouts` for the shells, `tests/Feature/Controllers`
for what a screen says, `tests/Unit/ViewModels` for the four view models being edited.

**Target Platform**: server rendered Blade in a browser, desktop, tablet and phone, in nine locales,
in a light and a dark theme.

**Project Type**: server rendered web application. Frontend only change with a reach into
`app/ViewModels` and `app/View/Components`.

**Performance Goals**: no regression, and one improvement available for free. Dropping Bricolage
Grotesque and Hanken Grotesk for Inter takes the bundle from three families to two. Fonts are
bundled rather than fetched at runtime and stay that way, so no visitor waits on another server and
none is announced to one.

**Constraints**: PHPStan level 5 over `app/`, `routes/` and `database/`. Pint clean. The suite passes
on SQLite, MySQL and PostgreSQL, which this feature cannot threaten since it writes no SQL. Every
string translated in all nine locales. WCAG 2.2 AA on every screen the change touches, which is all
of them.

**Scale/Scope**: 61 screens (30 on the sidebar shell, 11 guest, 7 admin, 2 account, 1 marketplace,
plus the partials each one includes), 51 shared components, 5 layout components, 4 view models, 338 test files
holding 2588 tests of which 14 files touch the replaced language, 139 Blade files in total of which the 7 email templates
and 1 meta partial are out of scope.

No NEEDS CLARIFICATION remains. The nineteen questions this context raised are answered in
[research.md](./research.md).

## Constitution Check

*Evaluated against `.specify/memory/constitution.md` v1.3.0, before Phase 0 and again after Phase 1.
Same result both times, except where noted.*

| Principle | Verdict | How this plan satisfies it |
|---|---|---|
| I. Actions hold the business logic | Pass | No action is written or changed. Four view models are edited to stop carrying palette strings, which keeps them reading only. No business logic moves into a view model or a component. |
| II. Simplicity over abstraction | Pass | Two panel components become one. Five components are deleted. Twenty one hue tokens and two class rotations go. The net is fewer moving parts, and the one component added, the tab strip, replaces markup currently repeated on two screens. |
| III. Tested before merged | **Conditional** | Every component keeps or gains a test and every migrated screen keeps its controller test. Two test files describe components this feature removes, and removing a test file needs the user's approval. See the gate below. |
| IV. Laravel conventions are the default | Pass | No route, controller or migration is touched. Blade files stay kebab-case, components stay anonymous with `@props`, the five layout classes stay in `app/View/Components`, one class per file. |
| V. Localized and safe user experience | Pass | Copy is preserved rather than rewritten, so few strings move, but `php artisan monica:localize` is run at the end of every slice and anything it reports is translated in all nine locales. Every destructive action keeps `<x-modal.warning>` in front of it (FR-024). |
| VI. Nothing changes under anybody's feet | Pass | Nothing about versions, snapshots or installed marketplace items is touched. A user's stored vault and entity colours are content and are left alone (FR-022). |
| VII. A stable core, unlimited personalisation | Pass | No new personalisation mechanism. Research decision 19 explicitly rejects letting a user colour a section, which would have been one. |
| VIII. Nothing disappears silently | Pass | No data is deactivated or deleted. The one thing removed from the user's view, the rotating section hue, carried no information: it was assigned by position. |
| IX. Accessible to everybody | **Pass, and this is the load bearing one** | FR-027 binds every screen, and this feature touches every screen, so the constitution's follow-up note that the 139 Blade files are unaudited stops being deferrable here. Every screen gets keyboard traversal, a visible focus ring, announced state, no meaning in colour alone, 200% zoom and reduced motion. The focus traps and anchored menus from pull request 50 are kept by name (FR-023). |
| X. Everything goes through the API | Not applicable | No capability is added, so no API surface is owed. Nothing moves out of an action into a screen. |
| Product invariants | Pass | No role, no membership, no sharing behaviour changes. What a share excludes stays excluded (FR-026), and the public screens keep a shell with no way into the product (FR-015). |
| Repository layout is fixed | Pass | No new top level folder. Everything lands in `resources/css`, `resources/views`, `app/View/Components`, `app/ViewModels`, `tests` and `vite.config.js`. |
| Three database matrix | Pass | No SQL written. |
| PHPStan level 5 | Pass | The four view model edits narrow array shapes, and the docblocks are updated with them. |
| Conventional Commits, no dashes in prose, no assistant mentioned | Pass | One commit per slice, subject naming the outcome. |

### The one gate that needs the user's answer

Principle III and `.claude/rules/testing.md` both say a test file is not removed without approval.
Two are candidates:

- `tests/Feature/Views/Components/DockTest.php`. The dock is removed by FR-011, so this file
  describes something that will not exist. Its coverage moves to a new `SidebarTest` asserting the
  same behaviours about the restored column: the destinations, the current one being announced, the
  vault switching, and the entries that are drawn and not offered.
- `tests/Feature/Views/Components/BarLinksTest.php`. Only removed if the shell commit moves the
  documentation, marketplace and administrator links out of the bar and into the column. If they stay
  in the bar, the file is rewritten and kept.

**This plan does not delete either file.** The first slice stops and asks. Everything else in the
slice can be built without touching them, because a test for a component that still exists can fail
temporarily within a branch and pass by the end of the commit.

## Project Structure

### Documentation (this feature)

```text
specs/002-restore-former-design/
├── plan.md              # This file
├── research.md          # Phase 0 output, nineteen decisions
├── data-model.md        # Phase 1 output, no schema change plus the presentation shapes
├── quickstart.md        # Phase 1 output, how to prove it works
├── contracts/
│   ├── tokens.md        # The token layer: names, both palettes, the type steps
│   ├── shell.md         # The five shells, their slots, and the screen by screen mapping
│   └── components.md    # All 52 components: restyled, authored, retired, consolidated
├── checklists/
│   └── requirements.md  # Spec quality checklist, complete
└── tasks.md             # Phase 2 output, NOT created by /speckit-plan
```

### Source code touched

```text
vite.config.js                              # Inter and IBM Plex Mono; the two 2026 faces go

resources/
├── css/app.css                             # The token layer, rewritten to contracts/tokens.md
└── views/
    ├── layouts/
    │   ├── shell.blade.php                 # Dock shell becomes sidebar shell, gains `sidebar`
    │   ├── account.blade.php               # Restyled
    │   ├── guest.blade.php                 # Restyled
    │   ├── admin.blade.php                 # Restyled, keeps a shell of its own
    │   └── marketplace.blade.php           # Restyled, keeps a shell of its own
    ├── partials/meta.blade.php             # Untouched. The pre-paint theme script stays
    ├── components/                         # 43 restyled, 1 authored (tabs), 2 restored, 5 deleted, 2 consolidated
    │   ├── vault/sidebar.blade.php         # Restored from 11f266e, finds its own vaults
    │   ├── settings/sidebar.blade.php      # Restored from 11f266e
    │   ├── tabs.blade.php                  # New
    │   ├── dock.blade.php                  # Deleted
    │   ├── launcher/                       # Deleted, all three files
    │   ├── vault/bar.blade.php             # Deleted
    │   └── box/                            # Deleted, callers move to panel and row
    ├── app/                                # 51 screens across settings, vaults, marketplace, admin, auth
    ├── shared/                             # 3 screens a private link opens
    └── mail/                               # Out of scope

app/
├── View/Components/                        # Five classes. Only Shell's docblock changes
└── ViewModels/
    ├── Settings/SettingsViewModel.php      # iconClass removed
    ├── Marketplace/MarketplaceViewModel.php# typeIconClass and typeTextClass become `type`
    ├── Vaults/VaultDashboardViewModel.php  # titleClass and iconClass removed
    └── Vaults/ContactViewModel.php         # FACT_HUES and SECTION_HUES removed

tests/
├── Feature/Views/Components/               # 12 rewritten, 1 added (SidebarTest), 1 to 2 pending approval
├── Feature/Views/Layouts/                  # AdminLayoutTest and TopBarTest rewritten
├── Feature/Controllers/                    # Two single-assertion edits: VaultControllerTest, LogControllerTest
└── Unit/ViewModels/                        # MarketplaceViewModelTest and the three others as their shapes narrow
```

**Structure Decision**: no structural change. Every file above already exists except
`components/tabs.blade.php`, the two sidebar components and `SidebarTest`. The shell keeps the name
`Shell` and the view keeps the name `layouts/shell.blade.php` rather than going back to `AppLayout`
and `layouts/app.blade.php`, because the two classes are otherwise identical and the rename would
edit the opening tag of thirty screens to change a word for a word
([research decision 3](./research.md)).

## Work order

One branch, one pull request, one commit per slice, in the spec's priority order. Each commit leaves
the product usable.

| Slice | Spec story | What lands | Screens |
|---|---|---|---|
| 1 | US1, P1 | Token layer, fonts, the sidebar shell, both navigation columns, the five deletions. Every screen goes transitional and stays usable | 0 migrated, all affected |
| 2 | US2, P2 | Vault list, vault form, vault dashboard, contact list, contact form, vault settings | 6 |
| 3 | US3, P2 | Contact detail with its pages and sections, contact edit, set aside values, version history, contact avatar | 8 |
| 4 | US4, P3 | Settings home and every screen under it, template builder and field type picker last | 19 |
| 5 | US5, P3 | Share link screens, and the three screens a private link opens | 6 |
| 6 | US6, P4 | Marketplace catalogue, card, statistics | 3 |
| 7 | US7, P4 | Admin space, seven screens and its shell | 7 |
| 8 | US8, P5 | The eight credentials screens and the quote panel | 8 |

Slice 1 is the only one that can break every screen at once, which is why it is one commit on its
own and why the two navigation columns and the five deletions are in it rather than spread out: a
half removed dock is two navigations at the same time.

Slices 2 to 8 are independent of each other. Within each, the shared components that slice is the
first to need are restyled in the same commit, so a component is never half migrated across two
commits.

## Complexity Tracking

| Violation | Why needed | Simpler alternative rejected because |
|---|---|---|
| Removing a test file (Principle III) | `DockTest` describes a component FR-011 removes. A test for a deleted component cannot pass | Keeping it skipped was rejected: a skipped test claims coverage that is not there. The coverage moves to `SidebarTest` rather than disappearing, and the removal is put to the user for approval rather than assumed |
| Editing four view models in a design change | They carry Tailwind class strings, so the palette is hard coded in PHP and cannot be removed from CSS alone | Re-pointing the hue tokens to neutral values was rejected: it leaves seven token families that all resolve to grey, which is a lie the next reader has to unpick ([research decision 8](./research.md)) |
| Keeping IBM Plex Mono, which the former tree did not bundle | FR-005 needs a machine value to read as one | The former tree's answer was a disabled input box holding the slug, which pull request 50 correctly replaced and FR-023 forbids restoring ([research decision 5](./research.md)) |
| Keeping the named type scale, which the former tree did not have | It is how the tokens are configured, not what the design looks like | Raw Tailwind sizes were rejected: they spread size, leading, weight and tracking across every heading, to be copied correctly by hand each time ([research decision 6](./research.md)) |
