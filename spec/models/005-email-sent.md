# 005. EmailSent

**Status:** exists (`app/Models/EmailSent.php`), minor changes
**Spec:** 3.7, 10.4

## What it is

A record of every email the application sent, with what the provider did with it afterwards. It exists so that a question like "did the reminder actually reach me" has an answer that does not require reading provider logs.

## Fields

```
id                          int                             primary key
account_id                  int                             account the email was sent on behalf of
user_id                     int                             user who received it, null when it went to someone with no account
uuid                        string (255)                    identifier the provider gave the email
email_type                  string (255)                    type of email sent
email_address               string (255)                    address it was sent to
locale                      string (5)                      language it was rendered in
subject                     text                            subject line
body                        text                            body, with the links stripped
sent_at                     datetime                        when it was sent
delivered_at                datetime                        when it was delivered
bounced_at                  datetime                        when it bounced
complained_at               datetime                        when the recipient marked it as spam
created_at                  datetime
updated_at                  datetime
```

## What changes

- Add `locale` (3.7). Every email is rendered in a language and the record should say which, otherwise a translation bug is invisible after the fact.
- Add `complained_at`. A spam complaint has to stop further sending to that address, and the current columns cannot express it.
- Extend `EmailType` as the product grows: reminder digest, marketplace update available, webhook subscription disabled, share link expiring.

## Relationships

- `belongsTo` [Account](001-account.md)
- `belongsTo` [User](002-user.md)

## Rules

- `user_id` is nullable and stays that way. A private share link can send an email to somebody with no account (4.4).
- The body is stored with links stripped. It is a trace, not a copy of a credential. A magic link or a share token must never be readable from this table.
- A bounce or a complaint marks the address as undeliverable and suppresses further sending to it. Where that suppression is stored is a separate concern and does not belong on this row.

## Open questions

- Retention. Bodies are the bulk of the table and lose their value quickly. A policy that drops `subject` and `body` after a period while keeping the delivery outcome is probably right, but the specification does not say.
