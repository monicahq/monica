# 051. WebhookDelivery

**Status:** to create
**Spec:** 14.4, 14.2

## What it is

One attempt to deliver one event to one subscription, with what came back.

Section 14.4 calls this the part that decides whether the feature is usable at all, and it is right. It requires four things and each is a column here. Sending happens in the background so a slow endpoint never slows down saving a record. Failures are retried on a spreading schedule over several hours before the event is abandoned. Every event carries a unique identifier so the receiver can recognise a duplicate, because a retry can arrive twice and any integration must assume it will. And the whole log is consultable, because it is the only way somebody can debug their own integration without writing to us.

## Fields

```
id                          int                             primary key
account_id                  int                             account the delivery belongs to
webhook_subscription_id     int                             subscription being delivered to
event_id                    string (36)                     unique identifier of the event, the same across every retry of it
event_type                  string (64)                     what happened, such as activity.created
event_occurred_at           datetime                        when the thing actually happened, which is not when it was sent
vault_id                    int                             vault the event came from, null for an account level event
subject_type                string (255)                    class of the object concerned
subject_id                  int                             id of that object
actor_type                  enum (user, system, api, module)  who caused it, a person or the system
actor_id                    int                             id of the actor when there is one
payload                     json                            body that was posted
attempt_number              int                             which attempt this is, starting at 1
status                      enum (pending, delivered, failed, abandoned)  outcome of this attempt
response_status_code        int                             HTTP status that came back, null when nothing did
response_body               text                            first part of the response, truncated, kept for debugging
error_message               string (512)                    why it failed when there was no response at all
duration_in_ms              int                             how long the call took
next_attempt_at             datetime                        when the next retry is due, null when there will not be one
delivered_at                datetime                        when it succeeded
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md)
- `belongsTo` [WebhookSubscription](050-webhook-subscription.md)

## Rules

- Delivery is always queued. Nothing in a request path ever waits on an outbound call (14.4).
- `event_id` is stable across retries. That is what lets a receiver deduplicate, and section 14.4 says integrations must assume an event can arrive more than once.
- Every payload carries the event type, the moment, the object concerned, what changed, and who is behind it, a person or the system (14.2).
- Retries spread out over several hours, then the event is abandoned. An abandoned event keeps its row.
- Consecutive failures increment the counter on the subscription, and crossing the threshold disables it with a notification (14.4).
- The log is visible to the user, in full, including the responses. It is the only self service diagnostic there is.
- Rows are pruned after a retention period. The counters on the subscription survive.
- The body is truncated before storage. An endpoint returning a megabyte of HTML must not fill the database.

## Open questions

- Retention length, which is a size question more than a product one.
- Whether ordering is guaranteed per subscription. It is not, as specified, and receivers must tolerate out of order arrival. Saying so in the documentation is cheaper than guaranteeing it.
