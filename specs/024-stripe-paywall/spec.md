# Feature Specification: Stripe Paywall

**Feature Branch**: `024-stripe-paywall` | **Created**: 2026-09-07 | **Status**: Draft

**Input**: User description: "Add a paywall, using Stripe". Feature 022 specified this paywall on Paddle and settled what this one inherits: three offers, monthly, yearly and lifetime, read from the provider's catalogue by the tag each price carries, one thing unlocked whatever the offer.

## Clarifications

### Session 2026-09-07

- Q: Does this replace the Paddle paywall of feature 022, or must both providers be supported side by side? → A: Stripe replaces Paddle. Branch 022 stays as a local reference, and everything it settled that does not name the provider holds here.
- Q: Who computes the tax due, and is the price shown the price paid? → A: The provider computes the tax share from the buyer's location at checkout; every price shown is tax inclusive and is what the buyer pays, the same in every country.
- Q: What is a monthly subscriber charged on the day they move to yearly? → A: Nothing. The subscription becomes yearly at once, and the yearly price is billed from the next renewal, on the date already planned.
- Q: What happens when an account with a running subscription is deleted? → A: Deleting the account cancels the subscription at the provider at once, without refunding the period in progress, and the warning says so. The product keeps no payment record of a deleted account; the invoices stay at the provider.
- Q: Which emails about money does the product send itself? → A: Only those about access, when it unlocks and when it ends with the reason. Receipts, invoices and failed payment reminders come from the provider.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Buy full access (Priority: P1)

Somebody on the hosted instance decides they want the whole product. They see what is on offer, pick one, pay at the provider, and come back to an account that no longer holds them to the free offer.

**Acceptance Scenarios**:

1. **Given** a hosted account on the free offer, **When** the owner completes the one time purchase, **Then** the account holds lifetime access with no renewal date, and shows the date and the billing email.
2. **Given** the same account, **When** the owner completes a subscription instead, monthly or yearly, **Then** the account holds full access and shows when it renews.
3. **Given** a payment the provider has not confirmed, **When** the owner returns from checkout, **Then** the account is unchanged and the screen says the payment is being confirmed.
4. **Given** an account that already holds lifetime access, **When** the owner opens billing, **Then** no offer is presented, because nothing can be added to owning it outright.

### User Story 2 - Be told why an action is refused (Priority: P2)

Somebody on the free offer reaches a cap. The product refuses the action, names the cap it hit, and offers the ways out from there.

**Acceptance Scenarios**:

1. **Given** a free account at a cap, **When** the owner attempts the capped action, **Then** it is refused with the cap named and the offers presented.
2. **Given** that same account, **When** the owner reads or edits what already exists, **Then** nothing is blocked.
3. **Given** a self hosted instance, **When** any account performs the same action, **Then** no cap applies and no offer, price or prompt appears anywhere.

### User Story 3 - Manage a subscription (Priority: P3)

A subscriber wants to know when they next pay, move up to a longer subscription, stop paying, or buy the product outright.

**Acceptance Scenarios**:

1. **Given** an active subscription, **When** the owner cancels, **Then** they are warned first, access continues to the end of the paid period, the screen says when it ends, and until that day they can change their mind and keep the same subscription running.
2. **Given** a cancelled subscription past the end of its period, **When** the owner uses the product, **Then** the free caps apply again and nothing they created has been removed.
3. **Given** an active subscription, **When** the owner buys lifetime access, **Then** the subscription stops renewing, access never drops, and they are not billed for it again.
4. **Given** a subscription whose payment fails repeatedly, **When** the provider gives up on it, **Then** the account returns to the free caps and the owner is told why.
5. **Given** a monthly subscription, **When** the owner moves to the yearly one, **Then** they are told before confirming that nothing is charged today and that the yearly price starts at the next renewal, the same subscription continues, its renewal date does not move, and no second subscription exists.

### User Story 4 - See what is on record (Priority: P4)

The owner opens their account settings and finds what they are paying, when, which address the invoice went to, and a way to reach every invoice.

**Acceptance Scenarios**:

1. **Given** an account that paid, **When** the owner opens billing, **Then** the offer held, the date, the renewal date where there is one, the billing email, and every payment made with a link to its invoice are shown.

### Edge Cases

- The provider's confirmation arrives before the buyer returns from checkout, or hours later: both end in the same state.
- An administrator granted access, then a payment lands: the account keeps access and the payment is recorded once.
- A subscriber's card is refused once, then succeeds: access never drops, because only the provider giving up ends it.
- A refund or a dispute lands against a subscription rather than a purchase: access ends the same way, and the record keeps both.
- A price carries a tag the product does not know, or a tag that disagrees with how the price bills: it is left out of the offers and the mismatch is recorded.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: On the hosted instance, an account that does not hold full access MUST be held to the free offer's caps, however it came to hold it. An account that holds it MUST have no vault cap and no contact cap, and MUST be held to a configured paid storage ceiling, defaulting to 10 GB. Paying MUST NOT overwrite the ceiling stamped on the account: the effective ceiling is the paid figure while full access is held and the stamped one otherwise, so a refund or a lapse needs nothing put back.
- **FR-002**: On a self hosted instance, no cap MUST apply, and no offer, price or upgrade prompt MUST appear on any screen. The instance's current self hosted setting is the only thing that decides this: the flag stamped on each account MUST be removed, and every reader of it, the metering check and the administrator screens included, MUST read the instance setting instead. A self hoster MUST NOT be able to turn a paywall on for their own users.
- **FR-003**: A refused action MUST name the cap it hit and present the offers, reachable from the refusal and from account settings. It MUST NOT fail silently, and it MUST NOT make existing data unreachable, uneditable or invisible.
- **FR-004**: Payment MUST be taken on the provider's hosted checkout. The product MUST NOT receive, store or transmit card details.
- **FR-005**: Access MUST unlock only on a confirmation received from the provider, never on the buyer's return from checkout. A confirmation MUST be authenticated before it is acted on, a confirmation that fails authentication or names an account that no longer exists MUST be rejected and recorded, and handling MUST be idempotent: repeated confirmations for one payment MUST leave one payment record, one unlock and one notification. Confirmation MUST be handled in the background and retried before it is abandoned, so a slow or unreachable provider never slows down or breaks a screen.
- **FR-006**: A completed payment MUST record its date, its billing email, the provider's identifier, which offer was bought, and the invoice the provider issued for it. Every payment, a one time purchase and each renewal alike, MUST have an invoice the owner can reach.
- **FR-007**: A refund or a dispute MUST withdraw access, leave the payment record and its date in place, and notify the account owner.
- **FR-008**: An account that already holds lifetime access MUST be refused any further offer before payment is taken, because nothing can be added to owning the product outright.
- **FR-009**: Account settings MUST show whether access is held, which offer holds it, when it was bought, when it renews where it renews, the billing email, a way to open the subscription and the payment method at the provider's own customer portal, and on a tab of its own every payment made, newest first, each with a way to reach its invoice. An account granted access by an administrator MUST be shown as such, with no invoice.
- **FR-010**: The existing administrator grant and withdrawal MUST keep working, MUST stay distinguishable from a paid offer in the record, and MUST NOT be ended by a refund or a lapse of a payment it was never based on.
- **FR-011**: Reading whether access is held, which offer holds it, and the payments on record, MUST be available through the API, along with what a client needs to send a buyer to the provider's checkout. Cancelling, taking a cancellation back, moving up, and opening the customer portal MUST be available through it too.
- **FR-012**: Every string this feature adds MUST be translatable. The offer list MUST show each offer's price as the provider's catalogue quotes it, written in the reader's language and in the currency the reader picks among those the catalogue quotes, suggested from their language. Every price MUST be tax inclusive, the same figure in every country, so that what is shown is what the buyer pays; the provider MUST work out the tax share from the buyer's location at checkout and show the split on the invoice, and the product MUST NOT show a price as before tax anywhere.
- **FR-013**: The caps settled here, the removal of the stamped self hosted flag, and the arrival of subscriptions are written into `spec/spec.md` §3.4, `spec/models/001-account-in-progress.md` and the constitution's product invariant, amended and merged before this feature is built (#143, 2026-09-06).
- **FR-014**: The free offer MUST cap vaults, contacts and storage. The vault and contact ceilings MUST be read from configuration at the moment of the check, defaulting to one vault and 30 contacts, and the contact ceiling MUST count every contact the account holds across all its vaults, except each vault's Me contact and any contact still marked partial, archived ones included. The storage ceiling MUST stay the per account value copied at creation.
- **FR-015**: An account already over a cap MUST keep everything it holds, reachable and editable, and MUST be refused only additions, with the cap named. This applies identically to an account that falls over a cap because a payment was refunded or a subscription lapsed.
- **FR-016**: Every cap MUST be enforced where the operation itself lives, so that the web screens, the API and any later surface are bound by the same check and none of them can be used to get past it.
- **FR-017**: Which offers are sold MUST be read from the provider's price catalogue through its API, never hardcoded and never configured by hand. A price is an offer when it carries the product's tag naming its kind, monthly, yearly or lifetime; an untagged price is not for sale here, and a price whose billing cycle disagrees with its tag MUST NOT be offered. The catalogue MUST be cached so that no screen waits on the provider, and an unreadable catalogue MUST leave every screen working with the offers marked unavailable. The product MUST NOT hold a price, a currency or an offer name of its own, and MUST NOT trust a tag when deciding what a payment unlocked: that MUST be derived from the payment itself, so that a mislabelled price cannot grant permanent access.
- **FR-018**: Every offer MUST unlock exactly the same thing. The product MUST NOT attach an entitlement, a ceiling or a feature to a particular offer, so that adding an offer never means changing what the product can do.
- **FR-019**: A subscriber MUST be able to cancel. Cancelling MUST warn first, MUST keep access to the end of the paid period, and MUST NOT take effect immediately. Until that day the owner MUST be able to take the cancellation back, keeping the same subscription and its renewal date, at no charge.
- **FR-020**: Access MUST survive a failed payment and end only when the provider abandons the subscription. When it ends, the owner MUST be told why.
- **FR-021**: A subscriber MUST be offered only what sits above their current offer: a longer subscription, and lifetime access. Moving to a longer subscription MUST change the running subscription in place, MUST NOT open a second one, and MUST charge nothing on the day of the change: the longer price is billed from the next renewal, on the date already planned, and the owner MUST be told so before confirming. Buying lifetime access while subscribed MUST stop the subscription renewing without any gap in access, and MUST NOT bill the subscription again.
- **FR-022**: Deleting an account MUST cancel any subscription it holds at the provider at once, without refunding the period in progress, and the deletion warning MUST say so. The product MUST keep no payment record of a deleted account; the invoices stay at the provider.
- **FR-023**: The product MUST email the owner about access only: when it unlocks, and when it ends, with the reason. Receipts, invoices and failed payment reminders MUST come from the provider, and the product MUST NOT send its own.

### Key Entities

- **Access**: what the caps read. It is held or not, and when held it names how: bought outright, subscribed, or granted by an administrator. It carries a renewal date only when subscribed. This is one concept in the product, whatever the provider models underneath.
- **Offer**: one price in the provider's catalogue, carrying the tag that names its kind and the amounts it quotes by currency. The product reads it, keeps it in a short lived cache, and holds nothing else about it.
- **Payment record**: one payment or one subscription at the provider. Carries the provider's identifier, which is what makes a repeated confirmation recognisable, which offer it was, the amount and currency the buyer paid, the invoice reference, and its current state.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A buyer goes from a refused action to full access in under three minutes, without leaving the flow to find out how.
- **SC-002**: 100% of confirmed payments unlock access within one minute of the provider confirming, with no further action from the buyer.
- **SC-003**: 100% of refused actions name the cap they hit and present the offers.
- **SC-004**: A self hosted instance presents zero prices, zero prompts and zero caps.
- **SC-005**: A refund, a dispute or an abandoned subscription withdraws access within one minute of the provider's notice, and the record stays visible afterwards.
- **SC-006**: A subscriber can cancel in under one minute and can see, before confirming, the date their access ends.
- **SC-007**: Adding or removing an offer is done at the provider alone, by tagging a price, and takes no change to the product, its configuration or any screen beyond the list it renders.
- **SC-008**: 100% of payments, renewals included, have an invoice the owner can open from the product.

## Assumptions

- **The constitutional amendment is merged.** Since 2026-09-06 (#143) the product invariant on `main` names the subscription beside the one time purchase, and `spec/spec.md` §3.4 says the same. FR-013 is met; nothing here waits on governance.
- **One provider.** Stripe replaces Paddle. Nothing was sold through Paddle, so no customer, subscription or invoice has to be moved, and the branch of feature 022 is a reference for building, never merged.
- Unlike Paddle, Stripe is not the merchant of record. The instance operator is the seller, holds the tax registrations, and turns the provider's tax computation on; FR-012 cannot be met without it.
- The provider hosts checkout and the customer portal, issues invoices, owns the catalogue with its prices, currencies and offer names, all managed by hand there and read from there, and notifies the product of payments, refunds, disputes, renewals and abandonments. At least three prices are tagged: monthly, yearly and lifetime.
