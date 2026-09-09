# Feature Specification: The order behind a purchase

**Created**: 2026-09-08

**Status**: Draft

**Input**: User description: "Stripe selling products with a new table a by removing has_lifetime_access"

## Clarifications

### Session 2026-09-08

- Q: What does `stripe_status` hold, and what becomes of it after a refund? → A: Stripe's own word for the state, kept in step as Stripe changes it, the rule the subscriptions table already follows.
- Q: How does a refund or a dispute reach its order? → A: The way it reaches a subscription, from what the provider sends. Nothing translates a charge back into a session: the account the charge names finds the order, since an account holds at most one purchase.
- Q: Is the table keyed on the account or on the user? → A: On the account, `account_id`, as `subscriptions` and `payments` are.
- Q: What does `type` hold? → A: The name the product addresses the order by, `default`, as on `subscriptions`. What was ordered is read from `stripe_price`.
- Q: What becomes of an order the buyer never paid? → A: It stays in the state it was written in. Only a settled order is read, and the provider is not followed for expiry.
- Q: And when the buyer opens a second checkout while it stands? → A: It replaces the first rather than adding a second, so an account never holds more than one order nobody paid. The session behind the first is closed at the provider, so a page left open in another tab cannot be paid after the fact.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Paying for full access unlocks it (Priority: P1)

**Why this priority**: it does not today. The one time purchase raises no invoice at the provider, the confirmation the product waits for never arrives, and the buyer is charged and left on the free offer.

**Independent Test**: buy full access in test mode. The order is written when the page opens, settled when the buyer comes back, and the caps are lifted before the billing screen finishes loading.

1. **Given** an account on the free offer, **When** its owner opens the checkout for full access, **Then** an order is written naming the account, the session, the state and the price.
2. **Given** that order, **When** the buyer comes back and the provider says the session is paid, **Then** the order is settled and full access is held.
3. **Given** a settled order, **When** the buyer reloads the return address, **Then** access is unlocked once and no second order appears.

### User Story 2 - Access that ends says why (Priority: P2)

**Why this priority**: an order must not be a one way door. Access can end, and the reason has to be readable without asking the provider.

**Independent Test**: refund the payment at the provider, then read the billing screen and the admin screen. Both say what was ordered, that it no longer holds, when and why, and the caps are back.

1. **Given** an account holding a settled order, **When** the provider reports money back on it, **Then** the order stays, carries what the provider now says of it, and access is taken back.
2. **Given** money back on an account holding nothing to withdraw, **When** the provider reports it, **Then** it reaches the log at error level rather than being dropped in silence.
3. **Given** an order that no longer holds, **When** anybody reads the account, **Then** the product says what was ordered, when it ended and why, without asking the provider.

### User Story 3 - A grant is not an order (Priority: P3)

**Why this priority**: the marker being removed is what an administrator grant writes today, so the grant breaks unless it is given somewhere of its own. Nothing was ordered, so it is not an order.

**Independent Test**: grant access from the admin space and read that account. The grant is named as a grant, with who made it and when, holds no order, and withdrawing it leaves the trace behind.

1. **Given** an account on the free offer, **When** an administrator grants full access, **Then** the account holds it, the grant names the administrator and the day, and no order is written.
2. **Given** an account holding a grant, **When** the administrator withdraws it, **Then** access goes and the trace of the grant survives.
3. **Given** an account holding a grant, **When** its owner then buys full access, **Then** the order settles, access never gaps and nothing is charged twice.

### Edge Cases

- The buyer pays and never comes back: the provider's confirmation settles the order anyway.
- The session named on the return is unpaid, unknown, or somebody else's: nothing is settled.
- The buyer closes the page at the provider: the order stays as written, holds nothing, and is shown nowhere.
- The buyer abandons a checkout and opens another: its session is closed at the provider and the same order is written over, never doubled.
- The provider refuses to close that session, which is what a session already paid looks like: the order is left alone and the new checkout gets one of its own.
- A purchase the provider raises no invoice for: the billing screen shows the order instead.
- The price an old order names is gone from the catalogue: access stands, and the screen shows the price.
- A self hosted instance: no order is written, no screen mentions any of it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: When a checkout is opened for a one time purchase, the product MUST write an order naming the account, the name it is addressed by, the provider's session, the state it gives it and the price. Only a one time purchase is an order: a subscription is never written as one, and renewals stay invoices.
- **FR-002**: The return address MUST carry the provider's session identifier, and the product MUST settle the order from the session that identifier names rather than from what the address claims, and only when the provider calls that session paid and it belongs to the account asking.
- **FR-003**: A settled order MUST unlock full access once, however often the return or the provider repeats itself.
- **FR-004**: The provider's own confirmation that a session completed MUST settle the order too, for a buyer who never comes back.
- **FR-005**: An order that was never settled MUST hold nothing and MUST NOT be shown as a purchase. Its state stays as it was written, and the provider is not followed for a session nobody paid. A checkout opened while such an order stands MUST replace it rather than write a second, so an account never holds more than one order nobody paid. Replacing MUST first close the session behind it at the provider, and an order whose session the provider will not close MUST be left exactly as it is, since money it may still carry needs somewhere to land.
- **FR-006**: Full access MUST be decided from a settled order, a subscription the provider still calls valid, and a standing grant. The marker on the account goes, and the day of purchase beside it.
- **FR-007**: An order MUST carry the state in the provider's own words, written when the order is placed and again on every confirmation the product listens for, and MUST keep the price it was placed against even once that price is retagged or gone.
- **FR-008**: A settled order MUST hold full access whatever the catalogue still knows of the price it names. A screen that cannot name the offer any more MUST show the price rather than nothing.
- **FR-009**: When the provider reports money back on an order, the order MUST stay in place carrying what the provider now says of it, and access MUST be taken back. When money comes back again, that same order MUST hold again rather than a second one being written. An order MUST NOT be deleted.
- **FR-010**: Money back on an account holding neither an order nor a subscription MUST be written to the log at error level rather than dropped, since somebody has to look at money that came back from nothing.
- **FR-011**: A purchase of something the account already holds MUST be refused before a session opens.
- **FR-012**: An administrator grant MUST keep working with the marker gone, naming the administrator and the day, and MUST NOT be written as an order.
- **FR-013**: The billing screen, the admin screen and the API MUST say what was ordered, when, the state it stands in, and why access ended when it did. The admin screen MUST carry the same facts as the owner's own, the subscription included, and MUST link to the customer on the provider's dashboard wherever the provider knows them.
- **FR-014**: Every account holding full access when the change ships MUST keep it, and no other gains it.
- **FR-015**: A self hosted instance MUST write no order and show none of it.

### Key Entities

- **Order** (`stripe_orders`): `account_id` the account that ordered, `type` the name it is addressed by, always `default`, `stripe_id` the checkout session at the provider, `stripe_status` what the provider last says of it in its own words, `stripe_price` the price it was placed against.
- **Payment**: one invoice the provider confirmed, unchanged here. A purchase may have none.
- **Account**: no longer carries the marker of full access, nor the day of the purchase.

## Success Criteria *(mandatory)*

- **SC-001**: A buyer who pays for full access holds it, whether they come back or close the page.
- **SC-002**: Every account holding full access before the change holds it after, and no other gains it.
- **SC-003**: One screen says what an account that lost access ordered, when it ended and why.
- **SC-004**: An administrator tells a purchase from a grant on any account without reading invoices.
- **SC-005**: No screen, cap, email or API answer decides access from a single marker on the account.

## Assumptions

- Only one thing is on sale, so a settled order means full access whatever price it names, and the order itself need not say which offer it was. A second sellable thing needs a constitution amendment first, and would need the order to say.
- A grant is a dated fact on the account, naming the administrator, and not a row among the orders.
- Nothing translates a charge back into the session that made it. It does not have to: an account holds at most one purchase, so the account the charge names is enough to find the order and take access back.
- The free offer needs no order. Holding none that stands is what being on it means.
- This builds on the paywall of feature 024, which is not merged yet, and restates none of it.
