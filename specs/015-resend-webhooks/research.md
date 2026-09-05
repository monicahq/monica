# Research: Resend delivery webhooks

## 1. Consume the package's endpoint, write nothing that receives

**Decision**: listen to `Resend\Laravel\Events\*`. Add no route, no middleware and no controller.

**Rationale**: the package already ships the whole receiving side, and it works in this application today. Three things confirmed rather than assumed:

| Checked | Result |
| --- | --- |
| `Controller::middleware()`, the deprecated constructor API the package uses to attach its signature check | Still present in Laravel 13.29, and `ControllerDispatcher` still reads `getMiddleware()`, so the check applies |
| `php artisan route:list --path=resend/webhook -v` | `POST resend/webhook` carries `Resend\Laravel\Http\Middleware\VerifyWebhookSignature` and nothing else |
| `php artisan event:list` | Discovery already resolves `Resend\Laravel\Events\EmailDelivered` to the listener staged on this branch, so no registration is needed |

**Rejected**: declaring our own route at the same path to own the receiving side. It works, the route collection keys on method plus domain plus URI and the application's route file loads after the package provider, but it buys a 403 instead of a 200 on a misconfigured instance and costs a route, a middleware, a controller and a standing dependence on provider ordering. Decision 2 gets the same protection for one condition.

## 2. What a missing secret means, and where that is enforced

**Decision**: the action refuses to act when `config('resend.webhook.secret')` is not a non-empty string, and logs that it did.

**Rationale**: the package attaches its signature check only when the secret is truthy, so an instance without one accepts any POST at that path and dispatches events from it. The endpoint's answer is not ours to change, and it does not need to be: FR-009 asks that nothing be acted on, not that something be refused with a status. Putting the condition in the action rather than in each of the four listeners keeps one statement of what makes a report trustworthy.

Where the secret is set, the package's check brings replay protection with it, rejecting a timestamp outside `resend.webhook.tolerance`, 300 seconds by default. That is why no event identifier needs storing for idempotency.

**Rejected**: relying on the package's middleware alone (absent exactly when it matters). Reimplementing verification (decision 1).

## 3. The secret's config key

**Decision**: read `config('resend.webhook.secret')`, the key the package already merges from `RESEND_WEBHOOK_SECRET`. Add that variable to `.env.example` beside `RESEND_API_KEY`. Publish no config file and add nothing to `config/services.php`.

**Rationale**: the config key exists and resolves already, so no `env()` call enters our code and there is one name for one thing.

## 4. Which instant an outcome happened at, and why the action parses

**Decision**: the top level `created_at` of the payload, falling back to `now()` when absent or unparseable. Never `data.created_at`. The reading happens once, inside the action, which is why the action takes the payload array rather than parsed scalars.

**Rationale**: confirmed against Resend's published payload for `email.bounced`: the top level `created_at` is "when the webhook event was created", while `data.created_at` is "when the email was created". Reading the nested one records the moment the email was composed as the moment it arrived, wrong by however long it sat in a queue. It is also the mistake already made on this branch, which is the argument for one place: four listeners each doing their own `Arr::get` would state this fact four times and offer four chances to get it wrong. No third fallback, because falling back to the nested value would silently record the wrong instant.

## 5. Four events, three columns, and no new enum

**Decision**: one listener per event, each naming an outcome and delegating.

| Listener | Event | Outcome passed | Column |
| --- | --- | --- | --- |
| `RecordEmailDelivered` | `email.delivered` | `Delivered` | `delivered_at` |
| `RecordEmailBounced` | `email.bounced` | `Bounced` | `bounced_at` |
| `RecordEmailFailed` | `email.failed` | `Bounced` | `bounced_at` |
| `RecordEmailComplained` | `email.complained` | `Complained` | `complained_at` |

The action's input is `EmailDeliveryStatusEnum`, which gains `Complained`. Its `match` therefore carries a `Sent` arm that no report can reach, mapping to no column, and that arm earns a one line comment saying so.

**Rationale**: the package's controller already maps type strings to event classes, so a second enum of type strings would restate its work. The mapping that remains is event class to fact, and four class names state it more plainly than a lookup table would. Reusing the existing status enum avoids two enums with confusingly similar names, at the cost of one unreachable arm that PHPStan keeps honest. `email.failed` shares `bounced_at` because both mean the email reached nobody, which is what that column's API reference already says, and because model 005 lists exactly three delivery facts (clarification, 2026-09-04).

**Rejected**: a `failed_at` column of its own, more accurate about whose fault a failure was but needing an amendment to model 005 and a fourth badge word in nine locales. A base listener class holding the parse, which is inheritance bought for four three line bodies.

## 6. Repeats, ordering, and both bounce types alike

**Decision**: the action writes its timestamp only where the column is currently null. `email.bounced` writes `bounced_at` whatever `data.bounce.type` says, so the bounce object is not read at all.

**Rationale**: the null check gives FR-005 and FR-006 together, leaves precedence to the accessor, and honours Principle VI by never rewriting a recorded instant. It also settles the interaction with decision 5: a bounce and a failure for one email write `bounced_at` once and ignore the second, which is right.

Reading both bounce types alike was decided at clarification on 2026-09-04, against the recommendation made at the time. The accepted consequence: a temporary bounce, say a full mailbox, followed by a successful delivery on retry leaves the row reading as bounced, because a bounce outranks a delivery. The spec records it under Edge Cases and a test asserts it, so it stands as a decision rather than surfacing later as a defect.

## 7. Synchronous, and a listener that must never throw

**Decision**: the work happens in the request. No job, no table of received reports. Every failure to act is a return, never an exception.

**Rationale**: one indexed lookup and one update, well inside any timeout, and a queue would add a failure mode with nothing to show for it (Principle II). The constitution's rule that webhook delivery is a background concern governs outbound sends, where a slow endpoint must not slow a save; nothing is being saved here. The no-throw rule is not style: the package answers 200, so an exception from a listener turns that into a 500 the provider retries until it expires, which is what FR-007 and FR-011 forbid.

## 8. The staged listener is replaced

`app/Listeners/EmailDelivered.php` goes, and `RecordEmailDelivered.php` takes its place under a name that does not collide with the event class it listens for, matching `RecordEmailSent`. Listening to the package's event was the right instinct and is what this plan does. The four faults are in the body: it writes `opened_at`, a column that does not exist and that model 005 does not want; it reads `data.created_at`, the wrong instant per decision 4; `firstOrFail()` turns an unmatched report into a 500 the provider retries, against FR-007; and it logs every success at info level, against FR-013.
