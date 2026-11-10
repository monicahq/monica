# Research: Upgrade strip under the top bar

No question was left open by the spec. This file records the decisions the code settles.

## R1. Who sees the strip

- **Decision**: Keep `User::isOfferedFullAccess()` exactly as it is: the user never hid the banner,
  the account is metered, and it holds no full access.
- **Rationale**: Metered implies hosted and a free account, which is the condition under which
  `Account::contactLimit()` and `Account::vaultLimit()` return a number. So every user who sees the
  strip has two limits to draw, and the meters never meet a null ceiling. Changing the condition
  would also change what the API's `full_access_offer_dismissed_at` means to its clients.
- **Alternatives considered**: `Account::isOnFreeOffer()` alone. It would show the strip to a hosted
  free account whose storage ceiling an administrator removed. That is a change of audience the spec
  does not ask for.

## R2. Where the strip is drawn

- **Decision**: Directly after the `<header>` of both `layouts/shell.blade.php` and
  `layouts/account.blade.php`, outside the content column, in place of the `<x-full-access-offer />`
  each one renders inside its content today.
- **Rationale**: Design 3b draws the strip edge to edge under the bar. Those are the two layouts that
  render the old banner, so the set of screens is unchanged (FR-001), and `Shell` keeps leaving it
  off billing and the screens reached from it, which already show usage.
- **Alternatives considered**: Inside the content column, where the banner is today. It would make
  the strip a boxed panel, which is design 2b, not 3b.
- **Note**: Specification 110 rewrites the top bar of the shell on `2026-11-10-vault-header`. The
  strip only needs to follow `</header>`, so whichever branch merges second rebases with a one line
  conflict at most.

## R3. Where the numbers come from

- **Decision**: A view model, `App\ViewModels\FullAccessOfferViewModel`, built from the user, giving
  one row per meter: label, used, limit, percent and tone. `Shell` and `AccountLayout` replace their
  `bool $offersFullAccess` with `?FullAccessOfferViewModel $fullAccessOffer`, null when the user is
  not offered full access, and pass it to the component.
- **Rationale**: `NotificationBarViewModel` is the precedent for something every signed in screen
  draws: a top level view model, tested as a unit. The counts are the ones the policies enforce,
  `Account::contactCount()` and `$account->vaults()->count()`, and the same ones the billing screen
  prints (SC-002). Two counting queries per screen, only for users who see the strip.
- **Alternatives considered**: A class component like `NotificationBar`. The layouts already decide
  whether the offer shows, so a second class deciding it again would split one rule over two places.
  Computing in Blade with `@php` would put presentation logic (the tone) where no unit test reaches it.

## R4. The tone of a meter

- **Decision**: `danger` when `limit === 0` or `used >= limit`; `warning` when `used * 5 >= limit * 4`;
  `neutral` otherwise. The percent is capped at 100, and a limit of 0 gives 100.
- **Rationale**: 80% is the spec's threshold, and integer arithmetic avoids a float at the boundary
  (24 of 30 is a warning, 23 is not). Capping matches `BillingViewModel::percentOf()`.
- **Alternatives considered**: Reusing `BillingViewModel::percentOf()`. It is private to another
  screen's view model, and the four lines are repeated rather than shared, as `CLAUDE.md` asks.

## R5. Components

- **Decision**:
  - Badge: `x-badge tone="info"` with `uppercase tracking-wide` added through attributes. Its base
    names neither property, so nothing clashes (`.claude/rules/components.md`).
  - Upgrade: `x-button.secondary size="small"` to `settings.billing.index`, as the vault list's
    "Upgrade" button already does.
  - Close: `x-icon-button icon="close"` inside `x-form method="put"` to `fullAccessOffer.update`,
    exactly as the old banner does, with its label "Hide this offer for good".
  - Meter: written inline in `full-access-offer.blade.php`. It carries an ARIA contract
    (`role="progressbar"`, `aria-valuenow/min/max`, `aria-label`), but it stands in one file: the
    billing screen's bar is `aria-hidden` and carries none. One file is below the bar for a component.
- **Rationale**: Every piece except the meter already exists.

## R6. Copy and translation

- **Decision**: New strings "Free plan usage", "People" and "Upgrade your account". "Free plan",
  "Vaults" and "Hide this offer for good" exist. The count is printed as `25 / 30` with no wording,
  so it needs neither `__()` nor `trans_choice()`. "Subscribe now to lift the limits of the free
  plan!" and, if nothing else uses it, "See the plans" leave the locale files through
  `php artisan monica:localize`.
- **Rationale**: `.claude/rules/code-style.md` requires `trans_choice()` only for a phrase containing
  `:count`. A bare pair of numbers is not a phrase.

## R7. Tests

- **Decision**:
  - `tests/Unit/ViewModels/FullAccessOfferViewModelTest.php`: the counts match the ones the policies
    use (a Me contact and a partial contact are not counted), and the tone at 23, 24 and 30 of 30, at
    34 of 30, and at a limit of 0.
  - `tests/Feature/Views/Components/FullAccessOfferTest.php`, rewritten: the region name, each
    progress bar's name and values, the link to billing, the hiding form and its label.
  - `ShellTest` and `AccountLayoutTest`: the assertions on the old sentence move to the new region
    name, "Free plan usage". The billing exclusion test stays.
- **Rationale**: `.claude/rules/testing.md` puts what a screen shows in the view model test and the
  accessibility contract in the component test. No class string or tone class is asserted.
