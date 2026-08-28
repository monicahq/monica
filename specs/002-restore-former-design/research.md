# Research: Restore the Former Design

**Feature**: [spec.md](./spec.md) | **Date**: 2026-08-26

Eighteen decisions. The first six settle how the restoration is done at all, the next seven settle
the vocabulary, and the last five settle the tests and the risk.

The reference point throughout is commit `11f266e`, the last commit before pull request 50 was
merged as `89cf68a`. Every "former" file quoted below is read out of that tree with
`git show 11f266e:<path>`.

---

## 1. Restore by re-authoring, not by reverting

**Decision**: the former design is rebuilt by hand, using `11f266e` as the reference for the
palette, the type, the shell and the components that existed then. No commit is reverted.

**Rationale**: forty-one pull requests were merged between `89cf68a` and `HEAD`, and roughly thirty
of them add features on top of the replaced design. A revert of pull request 50 would take the
contact detail screen, the share links, the marketplace and the admin space with it, because those
screens were written against components that pull request 50 introduced. The spec forbids losing a
capability (FR-022), so a revert is not available.

**Alternatives considered**: `git revert 89cf68a` then fix the fallout, rejected because the fallout
is the majority of the product. A branch off `11f266e` with the new features cherry picked forward,
rejected for the same reason in the other direction.

---

## 2. The token layer and the shell land first, then one area at a time

**Decision**: the first commit installs the restored palette, type and shell globally. Every screen
picks them up at once and looks transitional until its own commit. Areas are then migrated in the
priority order the spec gives.

**Rationale**: this is exactly how pull request 50 rolled its own change out, and its commit message
says so: "the token layer is installed globally, so every existing screen picks up the palette and
the type while keeping its current structure. Those screens will look transitional until they are
migrated one at a time." It is the only order where the product is usable at every commit, because
the alternative has two palettes in force at once and a screen that reads in neither.

**Alternatives considered**: migrating screen by screen with both token sets live and each screen
opting in, rejected because two palettes and two shells maintained in parallel is the thing FR-031
exists to prevent, and every shared component would have to work in both.

---

## 3. Keep the shell's name, change what it is

**Decision**: `app/View/Components/Shell.php` and `resources/views/layouts/shell.blade.php` keep
their names and become the sidebar shell. The former tree called the class `AppLayout` and the view
`layouts/app.blade.php`; that rename is not made.

**Rationale**: the two classes are otherwise identical, character for character, apart from the view
they render. Renaming edits the opening and closing tag of thirty screens and every test that
renders one, to change a word for a word. "Shell" still describes a shell.

**Alternatives considered**: restoring the `AppLayout` name for fidelity, rejected as a diff with no
reader.

---

## 4. The sidebar finds its own vaults

**Decision**: the restored navigation column queries the account's vaults itself, the way the dock
does today. The former `vaults` prop, filled by each screen's view model, is not restored.

**Rationale**: the dock's own comment records why it was changed: "the alternative was every
screen's view model carrying the same two queries, since the dock is on all of them and none of them
is about it." That reasoning is about where a query lives, not about how the navigation looks, and
the spec reverts appearance and navigation, not behaviour. Restoring the prop would mean editing
every view model behind thirty screens to add two queries back.

**Alternatives considered**: the former prop, rejected above. A view composer, rejected because the
project has none and `.claude/CLAUDE.md` lists view models as the mechanism.

---

## 5. Fonts: Inter comes back, the display face goes, the mono face stays

**Decision**: `vite.config.js` asks for Inter at 400, 500, 600 and 700, and IBM Plex Mono at 400 and
500. Bricolage Grotesque and Hanken Grotesk are removed. The `latin`, `latin-ext` and `vietnamese`
subsets stay on every family.

**Rationale**: Inter at those four weights is exactly what the former tree bundled. The display face
is the thing FR-003 forbids, so it goes. The mono face is kept because FR-005 requires a machine
value to stay distinguishable from prose, and the alternative the former tree used for a slug was a
disabled input box, which pull request 50 correctly replaced and FR-023 forbids bringing back. The
subsets are kept because the current config's comment is right about why: a person's name is not a
translation, and a browser falling back a glyph at a time sets one name in two typefaces.

**Alternatives considered**: dropping mono for strict fidelity, rejected because it would leave
identifiers and keys with no way to read as machine values. Adding Inter's variable file by hand
rather than through the plugin, rejected because the plugin already bundles rather than fetching at
runtime, which is the property that matters.

---

## 6. Keep the named type scale, drop its display steps

**Decision**: the mechanism where one utility carries a step's size, leading, weight and tracking is
kept. The `display-1` and `display-2` steps are removed. `page-title`, `section-title`,
`card-title`, `label`, `nav` and `code` stay as names and are re-pointed to the flatter values of
the former direction. `base`, `sm` and `xs` keep Tailwind's names, as they do now.

**Rationale**: the named step is not part of the look, it is how the look is configured, and FR-030
keeps the configuration file holding the tokens. Going back to raw Tailwind sizes would spread four
decisions (size, leading, weight, tracking) across every heading in the product, to be copied
correctly by hand each time. That is the problem the scale was added to solve, and it is orthogonal
to which direction the product is in.

**Alternatives considered**: raw Tailwind sizes for fidelity to `11f266e`, rejected above.

---

## 7. Colour: content keeps it, the interface gives it up

**Decision**: the seven object hues are removed as interface colour. Colour survives in exactly two
places. Content colour, which the user chose and the database holds as a hex string: the vault
colour, the entity type colour, the value list item colour and the generated avatar colour. Fixed
semantic colour, which says a thing happened: success, warning, error, and the one blue accent.

**Rationale**: FR-002 defines the palette as near neutral with one accent, and FR-006 requires that
no meaning ride on colour alone. A hue vocabulary that says "this is a template and that is a
section" is decoration once the label is there, which FR-004 forbids. Content colour is different:
the user picked it to recognise their own vault at a glance, and taking it away would remove a
capability, which FR-022 forbids.

**Finding that removes a risk**: `app/Enums/VaultColourEnum.php` has not been touched since pull
request 50, and its eight hex values are the former direction's own palette. Blue is `#3f5fd0`,
which was the former `--brand`. Green, amber and rust are the former `--color-success`,
`--color-warning` and `--color-error` exactly. Every colour column in the schema holds a hex string
seven characters long, never a token name. So removing the hue tokens is a change to CSS and to four
view models, and not a data migration.

**Alternatives considered**: keeping the hues as a muted set, rejected because a muted seven hue
system is still a seven hue system and FR-031 wants one language. Removing content colour too,
rejected because it is a capability.

---

## 8. Four view models carry class strings and must be edited

**Decision**: `SettingsViewModel`, `MarketplaceViewModel`, `VaultDashboardViewModel` and
`ContactViewModel` carry Tailwind class strings for the hues. Each is edited to carry either a
neutral class, a semantic tone name the component resolves, or nothing at all where the component
can decide by itself.

**Rationale**: a view model may hold presentation logic, so the strings are not a defect, but they
mean the restoration reaches into `app/ViewModels` and `tests/Unit/ViewModels` and is not a Blade
only change. Naming the four now is what stops that being a surprise halfway through.

Where a component can decide by itself, it should: a tone name (`success`, `warning`, `danger`,
`neutral`) travelling from a view model to a badge is one word that survives any palette, while
`bg-marigold-tint text-marigold-deep` is a palette hard coded in PHP and is why this edit is
needed at all.

**Alternatives considered**: leaving the strings and re-pointing the hue tokens to neutral values,
rejected because it leaves seven token families that all resolve to grey, which is a lie the next
reader has to unpick.

---

## 9. There are two panel components today, and one must go

**Decision**: `panel` is kept and restyled. `box` and `box/row`, which are the former direction's
container and are still in use, are retired, and their eleven callers move to `panel` and `row`.

**Finding**: this is not the decision it looked like before the tree was counted. Both components
are live right now. `panel` has forty-two callers, all of them screens migrated after pull request
50. `box` has eleven, and they are exactly the screens nobody got to: the eight credentials screens
and the vault form. So the codebase already holds two panel components in two design languages,
which is the state FR-031 exists to end.

**Rationale**: keeping `box` would mean editing forty-two callers to restore a name, and keeping
both would leave the defect that started this decision. Keeping `panel` means editing eleven, and
`box`'s own comment shows the two are the same object anyway: a titled container with a description,
a help slot beside the title and a footer for the one line that is not a row. The restyle is the
work; the name is not.

**Alternatives considered**: restoring `box` as the survivor, rejected on the caller count.
Keeping both with one marked as the old one, rejected because that is where the two came from.

## 10. Every component that carries meaning stays a component

**Decision**: badge, chip, tabs, notice, select, option row, field value and icon all stay as
components and are restyled. None of them goes back to being assembled inline per screen.

**Rationale**: FR-023 preserves the accessibility gains, and these components are where those gains
live. Pull request 50's commits name what they replaced: a delivery state that "was a coloured dot
with the word hidden behind sr-only, which is colour alone for everybody who can see it", and three
template states that were "three differently bordered spans". Going back to either breaks FR-006.
The icon component is kept for the same class of reason: a named glyph set used everywhere is not a
visual language, and the former way, an inline SVG written out at each site, is how a stroke weight
drifts screen by screen.

**Alternatives considered**: inlining the glyphs for fidelity, rejected above.

---

## 11. The focus traps and the anchored menus stay

**Decision**: `x-trap.inert` on every dialog and `x-anchor` on every menu opened from inside a
clipping container are kept exactly as they are. `@alpinejs/focus` and `@alpinejs/anchor` stay
installed.

**Rationale**: these are the two fixes pull request 50's commits call out by name, and they are the
regression this feature is most likely to reintroduce, because the tempting move is to lift a dialog
out of `11f266e` where the trap did not exist. FR-023 and FR-024 both forbid it. The anchored menu
is also load bearing rather than cosmetic: the former preferences screen put a menu inside a panel
and the panel clipped it.

**Alternatives considered**: none. This is not a design decision.

---

## 12. Dark mode is untouched

**Decision**: the `.dark` class on `<html>`, the custom variant that drives every `dark:` utility,
and the script in `partials/meta.blade.php` that applies the saved theme before paint all stay as
they are. Only the values behind the tokens change.

**Rationale**: both directions ship both themes through the identical mechanism, which the two trees
share character for character. FR-007 is already satisfied structurally; what changes is the
palette it flips between.

---

## 13. The shell slot contract

**Decision**: the restored `x-shell` exposes `title`, `wide`, `sidebar`, `breadcrumb`, `actions` and
`footer`. The `dock` and `barEnd` slots are removed. The contract is written down in
[contracts/shell.md](./contracts/shell.md), and each of the thirty screens using `x-shell` is
mapped there.

**Rationale**: `wide` and `footer` are the former tree's own slots and both are still needed, the
first by the template builder and the field type picker, the second by long forms. `dock` becomes
`sidebar`, which is the whole of the navigation change at the screen's level. `barEnd` existed only
because a screen without a dock had no other way to reach the settings, and the restored shell has
a navigation column, so it has nowhere left to be.

---

## 14. The marketplace and the admin space keep shells of their own

**Decision**: `layouts/marketplace.blade.php` and `layouts/admin.blade.php` are kept and restyled.
Neither existed in the former tree.

**Rationale**: the admin space is a different area with a different audience and the spec for it
says an administrator must always know they are in it, which a shell of its own is how it says. The
marketplace is read by somebody who may not be signed in to anything. Folding either into the
sidebar shell would mean a navigation column pointing at a vault from a screen that is not about a
vault.

---

## 15. Two test files describe something that will not exist

**Decision**: `tests/Feature/Views/Components/DockTest.php` and
`tests/Feature/Views/Components/BarLinksTest.php` describe components this feature removes.
Removing a test file needs approval under `.claude/rules/testing.md` and Principle III, so both are
listed for the user's explicit approval before the branch touches them. The dock's behaviour that
survives, the four destinations and the vault switching, gains a `SidebarTest` that asserts the same
things about the restored component, so the coverage moves rather than disappearing.

**Rationale**: `BarLinksTest` is the less obvious of the two. The documentation, marketplace and
admin links it covers all survive, so if they stay in the top bar the test is rewritten rather than
removed, and only if they move into the navigation column does the file go. That is a design
decision the plan defers to the shell work, so the file is flagged and not pre-emptively condemned.

**Alternatives considered**: keeping both files as skipped tests, rejected because a skipped test is
a claim of coverage that is not there.

---

## 16. Twelve more test files are rewritten, not removed

**Decision**: `BadgeTest`, `ChipTest`, `FieldValueTest`, `IconTest`, `NoticeTest`, `OptionRowTest`,
`PanelTest`, `RowTest`, `SelectTest`, `VaultSearchTest`, `AdminLayoutTest` and
`MarketplaceViewModelTest` are rewritten in place. `VaultControllerTest` and `LogControllerTest`
each assert one hue class string and get that one assertion changed.

**Rationale**: `.claude/rules/testing.md` says a view test asserts "the classes that carry the
meaning and the copy". Where a test asserts a hue class that no longer carries meaning, the
assertion moves to the copy and the announced attribute, which is what should have been asserted
anyway and is what makes the next design change cheap. The component keeps its test file because the
component keeps existing.

---

## 17. The blast radius, measured

**Decision**: the numbers below are the scope, and the plan is sized against them rather than
against an impression.

| Surface | Count | Notes |
|---|---|---|
| Blade templates | 139 | Of which 7 are email and 1 is a partial, both out of scope |
| Screens on `x-shell` | 30 | Every one passes a `dock` slot today; 25 pass one explicitly |
| Screens on `x-guest-layout` | 11 | The credentials screens and the screens a private link opens |
| Screens on `x-admin-layout` | 7 | The admin space |
| Screens on `x-account-layout` | 2 | The vault list and the vault form |
| Screens on `x-marketplace-layout` | 1 | The catalogue |
| Shared components | 52 | Under `resources/views/components` |
| Layout classes | 5 | `Shell`, `AccountLayout`, `AdminLayout`, `GuestLayout`, `MarketplaceLayout` |
| View models carrying class strings | 4 | Named in decision 8 |
| Test files touching the replaced language | 14 | Two proposed for removal, twelve rewritten |
| Tests in the suite | 338 | All must still pass |

**Rationale**: FR-018 to FR-020 say "every screen" and "every element", and a plan that does not
count them cannot tell when it is finished. The counts are also what makes the priority slices
reviewable: story 4, the settings, is the largest by screen count and is correctly last of the
signed in areas.

---

## 18. One branch, one pull request, a commit per slice

**Decision**: branch `2026-08-26-restore-former-design`, one pull request, and one commit per
priority slice in the spec's order. Each commit leaves the product usable and each is reviewable on
its own.

**Rationale**: the user asked for one pull request. The slices are how it stays readable: a
reviewer reads the token and shell commit against the whole product, then each area commit against
its own screens. Conventional Commits applies to the pull request title and each commit subject, and
the subject names the outcome rather than the mechanics.

**Alternatives considered**: one pull request per slice, rejected because the user asked for one. A
single commit, rejected because a 130 file diff in one commit cannot be reviewed.

---

## 19. The rotating section hues go, and the argument against that is real

**Decision**: `ContactViewModel::SECTION_HUES`, its seven entry rotation, and
`ContactViewModel::FACT_HUES` are removed. A section on a contact is told apart by its heading, its
space and its position, not by a hue drawn from a rotation.

**The argument against**: the constant carries a comment making the opposite case, and it is not a
weak one. "A section carries no colour of its own, and four panels of identical grey are four panels
somebody has to read the heading of to tell apart." That is true, and a contact with eight sections
is the screen where it is most true.

**Why it goes anyway**: the hue is assigned by position in a rotation, so it means nothing. The
fourth section is marigold because it is fourth, and it becomes forest when somebody reorders their
template. A colour that changes when the content has not is not a way to tell things apart, it is a
way to think you can. FR-004 forbids decoration standing in for structure and FR-002 leaves one
accent, so a seven hue rotation has nowhere to live in the restored direction.

**What replaces it**: the things that actually differ between two sections. The heading at the
`section-title` step, the space between panels, and the hairline that closes each one. If a contact
with many sections then reads as a wall, the answer is the page strip that already exists to split
it, and not a colour that means nothing.

**Alternatives considered**: keeping the rotation in the one accent at varying opacity, rejected
because a positional meaning stays positional whatever the palette. Letting the user choose a colour
per section, rejected outright: it is a new personalisation mechanism, which Principle VII forbids
outside Template, Page, Section and Field.
