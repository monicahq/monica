# Feature Specification: Billing screen redesign

**Feature Branch**: `2026-09-29-billing-redesign` · **Created**: 2026-09-29 · **Status**: Draft

**Input**: Redesign `settings/account/billing` after the design `Billing.dc.html` in claude.ai/design
project `9dbb31f7-7a62-45eb-9fa5-146848d2bc33` (Monica design system). The design's "Preview state"
switcher is a design tool and does not ship. The settings shell around the screen is unchanged.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A free account sees what full access costs and takes it (Priority: P1)

Somebody on the free plan opens Billing and sees a headline, the subscription (monthly and yearly
side by side, yearly marked with what it saves) and lifetime access set apart as a one time fee, each
with its price in the chosen currency and a button to Stripe's checkout. Below: what full access
changes, the questions people ask before paying, and why Monica asks for money.

**Why this priority**: it is the page's purpose for most readers.

**Independent Test**: sign in as a free account, open Billing, switch currency, press each button
and land on Stripe's checkout for that price.

**Acceptance Scenarios**:

1. **Given** a free account, **When** the screen opens, **Then** the monthly, yearly and lifetime
   prices show in the currency the reader last picked, else the one their language suggests.
2. **Given** two or more quoted currencies, **When** the reader picks one, **Then** every price
   changes to it and the picked one is announced as selected.
3. **Given** monthly at 5 and yearly at 50, **When** the screen opens, **Then** yearly says it saves
   10 against twelve months, and says nothing when there is no saving.

### User Story 2 - A paying account sees where it stands (Priority: P1)

A subscriber or lifetime holder sees a summary: a badge for the state, the access in large type, one
line with its date (renews on, paid on, ends on), the actions that apply, and usage of contacts,
vaults and storage against their ceilings.

**Why this priority**: today this is split between "Your access" and "On record" and reads as a form.

**Independent Test**: open Billing as a yearly subscriber, a lifetime holder and a free account whose
subscription ended; each shows its own summary, and the free account shows the plans again.

**Acceptance Scenarios**:

1. **Given** a running subscription, **When** the reader presses "Cancel renewal", **Then** a warning
   says access lasts until the paid period ends and nothing is deleted, and renewal stops only once
   they confirm.
2. **Given** a subscription that is ending, **When** the screen opens, **Then** it says the end date
   and offers to keep the subscription.
3. **Given** a monthly subscriber, **When** the screen opens, **Then** the yearly and lifetime plans
   still show, yearly as a switch confirmed in a dialog saying nothing is charged today.
4. **Given** a lifetime holder, **When** the screen opens, **Then** no plan and no "what changes"
   block shows.

### User Story 3 - The reader reaches what was paid (Priority: P2)

A paying or formerly paying account sees "On record": the three newest payments with date, what it bought
and amount, and a "View invoices" action leading to the existing invoices screen.

**Independent Test**: an account with four payments shows the three newest, and the action opens
the invoices screen.

### Edge Cases

- Self hosted instance: only the notice saying there is nothing to pay for, as today.
- Back from checkout before Stripe confirms: the confirmation notice stays above everything.
- Stripe does not answer: the plans are replaced by the existing warning; summary and questions stay.
- Access granted by us: summary says so; no invoices, no Stripe link, no plans.
- Payment refunded or charged back: summary says which and when, and the plans show again.
- A refused checkout or subscription change: the error shows above the plans, as today.
- A resource with no ceiling shows its count and "Unlimited", with no bar.
- Only one currency quoted: no currency choice is shown.
- Narrow screen: plan cards, benefits and the two column sections stack; nothing scrolls sideways.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The screen MUST keep every state it handles today: free, subscribed, ending, lifetime,
  granted, refunded, charged back, awaiting confirmation, plans unavailable, self hosted.
- **FR-002**: Prices, the yearly saving, the storage figure and the free ceilings MUST come from the
  catalogue and configuration, never from copy.
- **FR-003**: The plans shown MUST be exactly those the account may still take up, as today.
- **FR-004**: The currency choice MUST be a set of toggle buttons, one per quoted currency, and MUST
  be remembered for the session as today.
- **FR-005**: Every destructive or billing changing action MUST ask for confirmation first.
- **FR-006**: A usage bar MUST show only where a ceiling exists, its value also given as text.
- **FR-007**: The questions MUST be a disclosure list operable by keyboard, the first one open.
- **FR-008**: The screen MUST render correctly in light and dark themes; the lifetime card stays the
  inverted card in both.
- **FR-009**: The tabs MUST give way to "View invoices"; the invoices screen keeps a way back.
- **FR-010**: Every new string MUST be translated in every configured locale.
- **FR-011**: The product MUST call this concept a "plan" everywhere a reader sees it: every screen,
  email, flash message, the product guide and the API documentation prose, in every locale.
- **FR-012**: The translations glossary MUST gain a "plan" row, and each locale MUST use that one
  word throughout; the constitution's invariant MUST say "free plan".
- **FR-013**: API field names (`offer`, `offers`, `offers_available`) and code identifiers MUST NOT
  change: renaming them breaks API clients and is not a reader-facing change.

### Key Entities

- **Plan**: monthly, yearly or lifetime, priced per quoted currency. **Payment**: date, what, amount.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A free reader reaches Stripe's checkout for any plan in two actions from the screen.
- **SC-002**: Every state in FR-001 renders without error and says in its first line what the
  account holds.
- **SC-003**: The screen passes WCAG 2.2 AA: keyboard only, screen reader, 200% zoom, both themes.
- **SC-004**: No price, ceiling or storage figure on the screen differs from the configured value.

## Assumptions

- Copy comes from the design, adjusted to real values. The FAQ statements (tax included, nothing
  deleted on cancellation, switching charges nothing today) match what the product does today.
- The "Powered by Stripe" mark and the currency flags are static images shipped with the screen; a
  currency without a flag shows its code alone.
- "Choose a plan" in the ended state moves the reader to the plans on the same screen.
