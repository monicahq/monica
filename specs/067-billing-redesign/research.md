# Research: Billing screen redesign

## R1. Renaming "offer" to "plan" (FR-011, FR-012)

- **Decision**: rewrite the 27 reader-facing `lang/en.json` keys that use the pricing noun, their
  32 call sites, the uncaught `StartCheckout` message (wrap it in `__()`), 11 product guide pages
  under `docs/portal/en`, `docs/STRIPE.md` prose, and constitution lines 267 and 269. Identifiers,
  API fields, the error bag key `offer`, `MONICA_OFFER_TAG` and `docs/portal/ROADMAP.md` stay.
- **Rationale**: `monica:localize` has no rename. A changed key is dropped from every locale and
  reported missing, so each old value is read from `git show main:lang/<locale>.json` first and
  rewritten with the locale's new glossary word. Values are written; keys stay the command's job.
- **Alternatives**: a script moving values to the new keys (rejected: edits keys by hand).
- The constitution change is a PATCH (2.4.2): wording, no obligation changes. The pull request says so.
- `Switch to :offer?` becomes `Switch to :plan?`, since the placeholder travels with the key anyway.

## R2. Two pull requests

- **Decision**: the rename ships first, on its own; the redesign is based on it.
- **Rationale**: the rename touches about 60 files unrelated to billing and 810 locale values; mixed
  into the redesign, neither diff can be reviewed.

## R3. Where the plans are drawn

- **Decision**: rewrite `x-offer-list` in place to draw the subscription card (monthly and yearly
  side by side) and the lifetime card. The switch dialog for a subscriber stays inside it.
- **Rationale**: it has one caller and a test; rewriting keeps both, and deleting a component needs
  its own approval (memory: no deleting app code on blanket approval).
- The component receives the formatted amount alone (`amount`) next to `price`, so the suffix
  ("/ month") can be set smaller, and the yearly saving as a separate prop.

## R4. Yearly saving

- **Decision**: `BillingViewModel::yearlySaving()` returns twelve monthly amounts minus the yearly
  one, in the displayed currency, formatted; null when either price is missing or the saving is not
  positive. Integer minor units, so no rounding.

## R5. Currency choice (FR-004)

- **Decision**: the existing `PUT settings/account/billing/currency` form, with one submit button per
  currency (`name="currency" value="eur"`) carrying `aria-pressed`. No JavaScript, no new route.
- Flags: inline SVG for EUR and USD from the design; any other currency shows its code alone.

## R6. Usage (FR-006)

- **Decision**: `BillingViewModel::usage()` returns contacts, vaults and storage, each with its text
  (the existing `:used of :limit` wording or "Unlimited") and a percentage, null without a ceiling.
- The bar is `aria-hidden`; the text carries the value. A `role="meter"` was rejected as a second
  announcement of the same figure.

## R7. On record (story 3)

- **Decision**: the three newest payments, from `payments()`, labelled as `InvoicesViewModel` does,
  then "View invoices". A monthly subscriber of three years would otherwise list 36 rows here.

## R8. Questions (FR-007)

- **Decision**: native `<details>`/`<summary>`, the first `open`; the "+" is `aria-hidden`. Keyboard
  and screen reader support come with the element. Storage figure from
  `monica.paid_storage_limit_in_bytes` through `StorageSize::readable()`.

## R9. Look

- **Decision**: tokens the app already has (`bg-page`, `bg-card`, `bg-canvas`, `border-hairline`,
  `text-ink`, `text-body`, `text-muted`, `shadow-panel`, `x-caption`). Display sizes the scale lacks
  (32 to 160px) are arbitrary values, as 34 other places already do; a new token for one screen
  fails the three file bar.
- Lifetime card: `bg-primary text-on-primary`, so it is dark on light and light on dark (FR-008),
  in place of the design's fixed `#0a0a0b`.
- Stripe mark: inlined with `fill="currentColor"` and its metadata stripped; the file has no fill of
  its own, so one drawing serves both themes. 150 by 34.

## R10. Page heading and tabs

- **Decision**: `x-page-header` stays in every state, though the design omits it for a free account:
  every screen needs its `h1`. The free headline becomes an `h2`.
- The `_tabs` partial loses its callers; the invoices screen takes an `x-back-link` to Billing.
  Deleting `_tabs.blade.php` is asked separately.
