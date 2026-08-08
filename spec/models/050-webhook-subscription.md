# 050. WebhookSubscription

**Status:** to create
**Spec:** 14, 14.1, 14.2, 14.3, 14.5, 3.6, 3.3

## What it is

A registration saying: when this happens, call this address.

Section 14.1 explains why this exists as a first class feature rather than an afterthought. The API lets an outside tool come and ask what is going on. It does not let it be told. Without webhooks, anything wanting to react has to poll in a loop, which is slow, expensive, and simply impossible for a self hosted instance behind a home connection. Webhooks are the outbound counterpart of the principle in section 3.6: if an action exists, it must be listenable from outside.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API
account_id                  int                             account the subscription belongs to
vault_id                    int                             vault it is confined to, null when it covers the whole account
name                        string (255)                    name the user gave it, so several are tellable apart
url                         string (512)                    address events are posted to
secret                      string (255)                    encrypted shared secret the receiver verifies the signature with
events                      json                            events subscribed to, never all of them by default
includes_values             boolean                         whether the payload carries the new values or only says what changed
excludes_sensitive_fields   boolean                         whether fields marked sensitive are stripped from the payload, true by default
is_active                   boolean                         whether events are still being sent
disabled_reason             enum (user, repeated_failures, account_limit)  why it stopped, so it is explained rather than gone
disabled_at                 datetime                        when it stopped
consecutive_failure_count   int                             failures in a row, which is what triggers automatic disabling
last_success_at             datetime                        when a delivery last succeeded
last_failure_at             datetime                        when one last failed
delivery_count              int                             how many events have been sent
created_by_user_id          int                             user who created it
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md), [User](002-user.md)
- `hasMany` [WebhookDelivery](051-webhook-delivery.md)

## Rules

- **Never all events by default** (14.3). The subscriber chooses what to receive. An empty selection subscribes to nothing.
- **The scope matters** (14.3). A subscription confined to a vault never receives an event from another one. Vaults are the compartment and a webhook is one of the easiest ways to breach one by accident.
- The secret lets the receiver verify the call came from Erica and was not altered. It is stored encrypted and shown once.
- A test event can be sent on demand, so a wiring can be checked before anything depends on it (14.3).
- **Repeated failures disable the subscription automatically, with a notification explaining why and a button to switch it back on** (14.4). It is disabled, never deleted, which is section 3.3 applied exactly.
- The interface says plainly, at creation, that what goes out leaves our control, and says it more loudly for a vault holding fields marked sensitive (14.5).
- On the hosted version the number of subscriptions and the volume are capped. Self hosted has no limit (14.5).
- Webhooks notify, they do not replace the API. An event says what happened and to what. The full state is fetched back through the API (14.5).

## Open questions

- **Whether an event carries the changed values or only the fact that they changed** (14.5, 16). The first is far more useful, the second keeps sensitive data from reaching a third party server. The columns above take the suggested path from section 14.5: make it configurable per subscription, with sensitive fields excluded by default. That is a recommendation and section 16 still lists it as open.
- The failure threshold and the retry schedule. Section 14.4 requires retries spread over several hours before giving up, without giving numbers.
