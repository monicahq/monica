# Quickstart: exercising the Resend webhook

The endpoint verifies a signature, so it cannot be poked with a plain `curl`, and no Resend account
is needed to sign a body. The fields read and the column each event writes are in
[data-model.md](data-model.md).

## Prerequisites

- `RESEND_WEBHOOK_SECRET` set in `.env` to any `whsec_`-prefixed base64 value. Any throwaway string
  works locally; it never leaves the machine and does not have to match a real Resend webhook.
- One row in `emails_sent` with a known `uuid`. The fastest source is the factory from a tinker
  session, or an actual sign in email with `MAIL_MAILER=resend`.

Confirm the endpoint is armed before anything else:

```bash
php artisan route:list --path=resend/webhook -v
```

It must name `Resend\Laravel` and list `VerifyWebhookSignature` beneath it. A missing middleware means
the secret is not configured, so every report gets dropped by the action instead: the safe outcome,
not the one you want while testing.

## Sign a report and send it

`tests/Fixtures/Resend/SignedWebhookRequest.php` is the fixture the tests use, and the same four
steps work from a script: pick any `$id` and take `$ts = time()`; strip `whsec_` from the secret and
`base64_decode` what is left into `$key`; compute
`base64_encode(pack("H*", hash_hmac("sha256", "$id.$ts.$body", $key)))`; post `$body` with the
headers `svix-id: $id`, `svix-timestamp: $ts` and `svix-signature: v1,<that value>`. That is
`Resend\WebhookSignature::sign()` in the vendor tree, the same algorithm the route verifies with, so
a mismatch is a mistake in your script rather than in the feature. Keep the script outside the
repository, since it holds the secret.

## What to look for

| Do this | Expect |
| --- | --- |
| Post a signed `email.delivered` naming a known `uuid` | 200, and `delivered_at` set to the payload's top level `created_at` |
| Reload Settings, Emails sent | The row's badge reads Delivered rather than On its way |
| Post the identical report again | 200, and `delivered_at` unchanged |
| Post a signed `email.bounced` for the same row | 200, badge reads Bounced, and `delivered_at` still set |
| Post a signed `email.failed` for a second row | 200, and `bounced_at` set on it, exactly as a bounce would |
| Post a signed `email.bounced` whose `data.bounce.type` is `Temporary` | 200, and `bounced_at` set. The type is not read, and a delivery afterwards does not undo it |
| Post a signed `email.complained` for the first row | 200, badge reads the complaint wording, which outranks the bounce |
| Post a signed `email.opened` | 200, and nothing changes. No listener receives it |
| Post a signed report naming a `uuid` nobody has | 200, nothing changes, one warning in the log |
| Change one byte of the body and post it | 403 from the route's own middleware, nothing changes |
| Comment out `RESEND_WEBHOOK_SECRET`, then post any body at all | 200, because the route stops verifying, and still nothing changes, because the action refuses to act |

That last row is worth running twice: it is the whole of FR-009, and the case the route cannot protect.

## Before calling it done

```bash
php artisan test --compact tests/Feature/Webhooks tests/Unit/Listeners tests/Unit/Actions/RecordEmailDeliveryOutcomeTest.php tests/Unit/Models/EmailSentTest.php
```

Then `php artisan monica:localize --check`, `vendor/bin/pint --dirty --format agent`, and PHPStan.
The full suite has to pass on SQLite, MySQL and PostgreSQL, which CI does.
