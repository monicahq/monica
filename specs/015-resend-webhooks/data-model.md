# Data model: Resend delivery webhooks

Nothing new is stored beyond one timestamp. One column joins a table that already holds three like it, and one index joins a column that already exists. See [model 005](../../spec/models/005-email-sent-in-progress.md) for the object itself.

## `emails_sent`, altered in place

`database/migrations/2026_07_31_000000_create_emails_sent_table.php` gains:

| Change | Why |
| --- | --- |
| `complained_at` datetime nullable, commented "when the recipient marked the email as spam", placed after `bounced_at` | The fourth fact, required by model 005 and by FR-003 |
| `$table->index('uuid')` | Every report is a lookup on it. Nothing looked it up before, so it has no index today |

The column name matches model 005. The provider identifier keeps the name `uuid` here; renaming it to `provider_uuid`, which model 005 also asks for, is out of scope in [spec.md](spec.md).

## What is read off a report, and what it writes

Three things are read and the rest of the payload is ignored, `data.bounce` included: `data.email_id`, matched against `emails_sent.uuid` to find the row; the top level `created_at` for the instant, never `data.created_at`, which is when the email was composed; and the event class the listener received, which decides the column.

| Event | Column written |
| --- | --- |
| `email.delivered` | `delivered_at` |
| `email.bounced` | `bounced_at` |
| `email.failed` | `bounced_at` |
| `email.complained` | `complained_at` |

Every other event the provider sends reaches no listener, so it changes nothing.

## `EmailSent`

- `complained_at` joins the `#[Fillable]` list and the `datetime` casts, beside `delivered_at` and `bounced_at`.
- `@property CarbonImmutable|null $complained_at` joins the class docblock.
- A timestamp is written only where it is currently null. That makes a repeated report a no-op (FR-005), makes arrival order irrelevant (FR-006), and means no recorded instant is ever rewritten (Principle VI).
- The `deliveryStatus()` accessor gains one arm. Precedence, most significant first:

| Order | Condition | Reads as |
| --- | --- | --- |
| 1 | `complained_at` is not null | `Complained` |
| 2 | `bounced_at` is not null | `Bounced` |
| 3 | `delivered_at` is not null | `Delivered` |
| 4 | otherwise | `Sent` |

A complaint outranks a bounce because it is the stronger statement, and the accessor's existing comment already explains why the reading is ordered rather than exclusive: a provider can report two facts out of order, and the record keeps both. Rows 2 and 3 are also what makes a temporary bounce followed by a delivery read as bounced, which is accepted rather than incidental (Edge Cases in [spec.md](spec.md)).

## `EmailDeliveryStatusEnum`

Gains `Complained = 'complained'`. Still not a column, still read off the timestamps, still carrying no `label()`, for the reasons its own docblock gives. It is now also the action's input, which is why its `Sent` case appears in a `match` that can never reach it.

## Surfaces that read it

| Surface | Change |
| --- | --- |
| `resources/views/app/settings/account/logs/_email-sent-row.blade.php` | A fourth `match` arm: tone `danger`, one new translated word |
| `app/Http/Resources/EmailSentResource.php` | `complained_at` as a timestamp, beside the other three |
| `resources/docs/api/07-emails-sent.php` | The `complained_at` field, and `delivery_status` naming four words instead of three |
| `database/factories/EmailSentFactory.php` | A `complained()` state beside `delivered()` and `bounced()` |
