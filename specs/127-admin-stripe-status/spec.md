# Feature Specification: Stripe status in the admin space

**Feature Branch**: `claude/beautiful-mestorf-1fe6ed`  
**Created**: 2026-11-17  
**Status**: Draft

**Input**: User description: "admin page for stripe status"

## Clarifications

### Session 2026-11-17
- Q: Should the screen show only the health of the link to Stripe, or business figures too? → A: Business figures too, read from what the instance already holds.
- Q: Which refunds and disputes should the figures count? → A: Those the instance acted on in the last thirty days (full refunds and disputes that ended access), purchases and subscriptions alike, from the confirmations it already keeps.
- Q: Should the month's amount paid subtract money later given back? → A: No. It is the amount as invoiced, tax included, with the refund and dispute counts shown beside it.
- Q: Should the screen warn when the kind of key does not match the kind of instance? → A: Yes, both ways: test keys on the production instance, and live keys on any other instance.
- Q: Should the screen list the Stripe prices that are not on sale, with the reason? → A: No. It names only the kinds of offer with no price on sale.

### Session 2026-11-18
- Q: Should the screen keep the keys and connection block? → A: No. The block is removed: the screen no longer shows which keys are set, their mode, the warnings about them, or the state and response time of the connection. Stripe is still asked for its endpoints, and the other blocks say when it was not reached.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An administrator sees whether billing can take money (Priority: P1)

An administrator opens Billing in the admin space and learns what the instance offers for sale, read from Stripe.

**Why this priority**: A missing key, a test key in production or a price without its tag leaves somebody unable to pay, and today that is diagnosed from a shell and the Stripe dashboard side by side. This answer alone removes that.

**Independent Test**: On an instance with three tagged prices, the screen lists the three offers with their amounts.

**Acceptance Scenarios**:

1. **Given** a readable catalogue, **When** the screen opens, **Then** it lists each offer on sale with its kind, its amount in each currency it is quoted in, and names every kind of offer the instance sells for which no price is on sale.
2. **Given** the catalogue cannot be read, **When** the screen opens, **Then** it says so, tells it apart from a catalogue with nothing on sale, and the rest of the screen still renders.
3. **Given** a self hosted instance, which meters nobody, **When** an administrator opens Billing, **Then** the screen says in one sentence that this instance takes no payment, and nothing is sent to Stripe.
4. **Given** a visitor, a regular user or a deactivated administrator, **When** they ask for the screen, **Then** they get not found.

---

### User Story 2 - An administrator sees whether Stripe's confirmations arrive (Priority: P2)

A payment unlocks nothing until Stripe confirms it to the instance. The screen shows whether Stripe knows where to send those confirmations, whether it sends every kind the instance needs, and when one last arrived.

**Why this priority**: An endpoint missing one kind of confirmation looks right and silently leaves a buyer charged and on the free offer. It is the second most likely failure after the keys, and the least visible.

**Independent Test**: With an endpoint at Stripe that leaves out the paid invoice confirmation, the screen names that confirmation as missing.

**Acceptance Scenarios**:

1. **Given** Stripe holds an enabled endpoint pointing at this instance, **When** the screen opens, **Then** it shows the endpoint's address, that it is enabled, and that it subscribes to every kind of confirmation the instance acts on.
2. **Given** the endpoint leaves out one or more kinds the instance acts on, **When** the screen opens, **Then** it names each missing kind and what goes wrong without it.
3. **Given** Stripe holds no endpoint pointing at this instance, or only a disabled one, **When** the screen opens, **Then** it says so.
4. **Given** confirmations were acted on in the last thirty days, **When** the screen opens, **Then** it shows when the most recent one arrived, its kind, and how many of each kind arrived in the last seven and the last thirty days.
5. **Given** no confirmation was acted on in the last thirty days, **When** the screen opens, **Then** it says none arrived in that period, without claiming that any was lost.

---

### User Story 3 - An administrator reads the catalogue again (Priority: P3)

After changing a price in Stripe, an administrator asks the instance to read the catalogue again rather than wait for it to do so on its own.

**Why this priority**: The instance reads the catalogue once an hour, so a corrected price stays wrong on every pricing screen for up to an hour. Useful, but the screen already explains the delay without it.

**Independent Test**: An administrator tags a new price in Stripe, presses the button, and the offer appears on the screen at once.

**Acceptance Scenarios**:

1. **Given** the catalogue was read earlier, **When** the screen opens, **Then** it says when the instance last read it.
2. **Given** an administrator presses "Read the catalogue again", **When** Stripe answers, **Then** the screen shows the new catalogue, every screen that shows prices uses it, and the administrator log records who did it.
3. **Given** Stripe does not answer, **When** the administrator presses the button, **Then** the screen says the catalogue could not be read, and every pricing screen behaves as it already does when the catalogue is unreadable.

---

### User Story 4 - An administrator sees what billing brings in (Priority: P2)

Under the health blocks, the screen shows the figures of the business: how many accounts hold full access and through what, how many subscriptions run and in what state, what was paid this month and last month, and how much money was given back.

**Why this priority**: These answers are scattered across the Stripe dashboard today, and the instance already holds a copy of every one of them. Less urgent than knowing the link works, more useful day to day.

**Independent Test**: With two monthly subscribers, one yearly subscriber, one purchase made once, one access granted by an administrator and three payments this month, the screen shows those counts and the month's total.

**Acceptance Scenarios**:

1. **Given** accounts holding full access, **When** the screen opens, **Then** it shows how many hold it through a monthly subscription, a yearly subscription, a purchase made once and a grant by an administrator, and how many accounts hold none.
2. **Given** running subscriptions, **When** the screen opens, **Then** it shows how many are active, how many are cancelled but run until the end of their period, and how many have a failed payment Stripe is still retrying.
3. **Given** payments recorded this month and last month, **When** the screen opens, **Then** it shows for each month the number of payments, the amount paid as invoiced and the tax within it, per currency, by kind of offer, and says that refunds are not subtracted.
4. **Given** payments in several currencies, **When** the screen opens, **Then** each currency has its own total and no amount is converted into another.
5. **Given** full refunds and disputes that ended access in the last thirty days, on purchases made once or on subscriptions, **When** the screen opens, **Then** it shows how many of each, and says that partial refunds are not counted.
6. **Given** no payment was ever recorded, **When** the screen opens, **Then** the figures block says so in one sentence instead of showing a run of zeros.
7. **Given** Stripe is unreachable, **When** the screen opens, **Then** the figures are still shown, since they come from what the instance holds.

---

### Edge Cases

- Stripe is slow: the check stops at the time limit and reports unreachable, it never holds the screen.
- Stripe accepts the key but refuses to list endpoints because of a restricted key: the keys block stays healthy and the confirmation block says the key may not read endpoints.
- Several endpoints point at this instance: each is listed, and a kind is missing only when no enabled endpoint covers it.
- An endpoint points at another address (a staging copy, an old domain): it is not counted as this instance's endpoint, and its address is not shown.
- A price is untagged, or tagged with a kind whose billing cycle disagrees (a yearly tag on a monthly price): it is not on sale and is not listed. The screen names the kind left with no price, and the Stripe dashboard shows why.
- A billing currency is configured that no offer is quoted in: the screen says so.
- An instance that meters its accounts but has no secret key: nothing is asked of Stripe, and the offers and confirmations blocks say Stripe was not reached.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The admin space MUST offer a Billing screen, reachable from the admin navigation, open to active administrators only and not found for everybody else.
- **FR-002**: On an instance that meters nobody, the screen MUST say in one sentence that the instance takes no payment, and MUST NOT contact Stripe or read any key.
- **FR-003**: Removed on 2026-11-18 (see Clarifications).
- **FR-004**: Removed on 2026-11-18 (see Clarifications).
- **FR-005**: Removed on 2026-11-18 (see Clarifications).
- **FR-006**: The screen MUST list every offer on sale with its kind and its amount in each currency it is quoted in, and MUST name every kind of offer the instance sells that has no price on sale.
- **FR-007**: The screen MUST tell a catalogue that cannot be read apart from a catalogue with nothing on sale.
- **FR-008**: The screen MUST list the endpoints Stripe holds for this instance's address, whether each is enabled, and every kind of confirmation the instance acts on that no enabled endpoint subscribes to.
- **FR-009**: The screen MUST show when the most recent confirmation was acted on, its kind, and the count of each kind over the last seven and the last thirty days, from what the instance already keeps.
- **FR-010**: Every check that contacts Stripe MUST finish within 5 seconds in total, and a failure of any part MUST NOT prevent the rest of the screen from rendering.
- **FR-011**: Opening the screen MUST change nothing at Stripe, in the instance or in the administrator log.
- **FR-012**: An administrator MUST be able to have the instance read the catalogue again. That MUST be written to the administrator log.
- **FR-013**: The screen MUST NOT show any customer, card, single payment or account. Every figure on it is a total.
- **FR-014**: All copy MUST be translated in every configured locale, and the screen MUST meet WCAG 2.2 AA.
- **FR-015**: The screen MUST show the number of accounts holding full access by how they hold it (monthly subscription, yearly subscription, purchase made once, grant by an administrator) and the number holding none.
- **FR-016**: The screen MUST show the number of subscriptions that are active, cancelled but running until the end of their period, and waiting on a failed payment.
- **FR-017**: The screen MUST show, for the current calendar month and the previous one, the number of payments, the amount paid as invoiced (tax included, refunds and disputes not subtracted) and the tax within it, per currency and by kind of offer. Amounts MUST NOT be converted between currencies.
- **FR-018**: The screen MUST show the number of full refunds and the number of disputes the instance acted on in the last thirty days, on purchases made once and on subscriptions alike, counted from the confirmations it keeps. Partial refunds, and refunds that ended no access, are not counted, and the screen MUST say so.
- **FR-019**: The figures MUST come from what the instance already holds, MUST NOT require Stripe to answer, and MUST be totals for the instance with no account named.

### Key Entities

- **Stripe connection**: whether Stripe answered the endpoint call. Read live, never stored.
- **Offer**: one price on sale, with its kind and its amounts by currency, as the instance already reads it once an hour.
- **Confirmation endpoint**: an address Stripe sends its confirmations to, whether it is enabled, and the kinds it subscribes to. Read live, never stored.
- **Confirmation received**: one confirmation the instance acted on, with its kind and when it arrived. Already kept for thirty days.
- **Payment**: the instance's copy of one paid invoice, with its amount, tax, currency, kind of offer and billing date. Already kept.
- **Subscription**: one subscription an account runs, with its state and its price. Already kept.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An administrator learns whether the instance can take a payment in one screen and under 10 seconds, without a shell or the Stripe dashboard.
- **SC-002**: With Stripe down, the screen renders in under 6 seconds and says so.
- **SC-003**: An endpoint missing one kind of confirmation the instance needs is named on the screen, every time.
- **SC-004**: No key, secret or customer detail appears in the screen's output.
- **SC-005**: A price corrected in Stripe is shown on every pricing screen within one minute of an administrator asking for it, instead of up to an hour.
- **SC-006**: An administrator reads this month's amount paid, per currency, from the screen and it matches the sum of the invoiced totals of the payments the instance recorded for the month, refunded ones included, to the smallest unit.

## Assumptions

- "Stripe status" means the health of the instance's link to Stripe, read live, and the figures of the business, read from the instance's own copy.
- Months follow the calendar in the instance's time zone. Revenue forecasts, recurring revenue, churn rates, charts and history beyond the previous month are out of scope; the Stripe dashboard keeps them.
- Refunds and disputes are counted from the confirmations the instance acted on, so a partial refund, or a refund of an old subscription payment that ended no access, leaves no trace and is not counted. The Stripe dashboard keeps those. No new data is recorded.
- A free account is any account without full access. On an instance that meters nobody, no figure is shown.
- Web only, like the Search, Flags and Configuration screens of the admin space. This leaves Principle X uncovered, and the pull request must say so.
- The screen exists on every instance, as the constitution forbids a feature on one deployment only. On a self hosted instance it holds a single sentence.
- Prices that are not on sale are not listed, and finding out why a price was left out happens in the Stripe dashboard. The screen reads nothing from Stripe beyond the catalogue, the account check and the endpoints.
- Keys, the signing secret and the currency stay in the environment. Changing them, creating or repairing the endpoint at Stripe, and alerts are out of scope.
- Confirmations Stripe failed to deliver, and confirmations the instance refused (a bad signature), are not recorded today. Showing them would need new data and is out of scope. The screen only reports what was acted on, kept thirty days.
- Reading the catalogue again is not a change to how the instance behaves, so it asks for no password confirmation, unlike the Configuration screen. It is still written to the administrator log.
- The endpoint belongs to this instance when its address is the instance's own address followed by the confirmation path.
