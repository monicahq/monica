# Phase 1 Data Model: Sent emails endpoints

**Feature**: `specs/005-sent-emails-api` | **Date**: 2026-08-29

No migration. The table exists and every column this feature exposes is already on it. What follows
is what is there, what is derived, and what is deliberately withheld.

## Entity: Sent email

Model `App\Models\EmailSent`, table `emails_sent`. Written when mail leaves; updated only by delivery
reports. Never edited by a person, which is why the API offers no way to write it.

| Column | Type | Null | Exposed as | Notes |
| --- | --- | --- | --- | --- |
| `id` | int | no | `id`, cast to string | The envelope casts every identifier to a string. |
| `account_id` | int | no | not exposed | It is the scope, so every row a caller sees carries their own. Emitting it would say nothing. |
| `user_id` | int | yes | not exposed | Null for mail sent to an address belonging to nobody. An account holds exactly one person, so it distinguishes nothing a caller needs. |
| `uuid` | string | yes | not exposed | Internal correlation with the mail transport. Not a public identifier and not stable enough to become one. |
| `email_type` | string | no | `email_type` | The raw value, what a program matches on. Backed by `App\Enums\EmailType`, though the column is not cast. |
| `email_address` | string | no | `email_address` | Where it went. |
| `subject` | string | yes | `subject` | Null on records predating our keeping it. |
| `body` | text | yes | `body`, **single email only** | The copy that left. Withheld from list rows, per FR-008 and SC-005. |
| `sent_at` | datetime | yes | `sent_at`, Unix integer | Also the sort key. |
| `delivered_at` | datetime | yes | `delivered_at`, Unix integer | |
| `bounced_at` | datetime | yes | `bounced_at`, Unix integer | |
| `created_at` | datetime | no | `created_at`, Unix integer | When the record was written, which can trail `sent_at`. |
| `updated_at` | datetime | yes | not exposed | An implementation detail of delivery reporting. `ApiKeyResource` exposes it; there it is meaningful, here it only says a provider called back, which the three timestamps already say better. |

### Derived: `delivery_status`

Not a column. An accessor on the model returning `App\Enums\EmailDeliveryStatusEnum`, derived from the
three timestamps by the rule the sent emails screen already applies:

```text
bounced_at is set      -> Bounced   ('bounced')
delivered_at is set    -> Delivered ('delivered')
otherwise              -> Sent      ('sent')
```

**Bounced wins over delivered.** A provider can report the two out of order, and an email that came
back is the fact worth surfacing. This is the precedence the blade partial encodes today and the
precedence FR-003 requires the API to match.

The enum carries values and nothing else: no `label()`, no `description()`, and it does not implement
`Translatable`. The words a person reads stay in the screen that shows them.

## Entity: Delivery status

Enum `App\Enums\EmailDeliveryStatusEnum`, string backed.

| Case | Value | Means |
| --- | --- | --- |
| `Bounced` | `bounced` | It came back. |
| `Delivered` | `delivered` | It arrived. |
| `Sent` | `sent` | It left and nothing has come back yet. |

The screen renders the third as "On its way", which is the same fact said to a person. The wire value
names the fact so that rewording the badge never moves the value underneath a caller.

### State transitions

A record moves forward only, and only by delivery reports:

```text
Sent ──> Delivered
  │
  └────> Bounced   (also reachable from Delivered, when reports arrive out of order)
```

Nothing returns to `Sent`. No API call causes any of these transitions; the API only reads them.

## Relationships

`Account hasMany EmailSent` (`app/Models/Account.php:178`), which is the scope both endpoints query
through. `EmailSent belongsTo User` exists and is not used here: the endpoints scope by account, per
the assumption recorded in the spec.

Neither endpoint eager loads anything. The resource reads no relationship, so there is no N+1 to
avoid and no `with()` to add. This is the one place the design differs from `LogController`, which
loads `user` because `LogResource` renders an author.

## Validation rules

None. Both endpoints are reads with no request body. The only input is the page and the page size,
and neither is validated: an out-of-range size is clamped rather than refused (FR-005), and an
identifier that is not a positive integer never reaches the controller because the route constrains
it to `[1-9][0-9]*`.
