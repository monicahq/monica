# 049. ShareLinkVisit

**Status:** exists (`app/Models/ShareLinkVisit.php`), built as far as the models it depends on allow
**Spec:** 4.4

## What it is

One opening of a private link. Section 4.4 requires every share link to be journalled, with its creation, its last consultation and its revocation. The first and last of those are columns on the link. This model is the middle one, kept in detail.

It exists separately because "last viewed" answers almost nothing on its own. Somebody who shares a page wants to know whether it was opened once or forty times, and roughly when. That is the question this table answers, and it is the reason the feature feels safe enough to use.

## Fields

```
id                          int                             primary key
account_id                  int                             account the link belongs to
share_link_id               int                             link that was opened
ip_address                  string (45)                     address it was opened from, kept coarse and only for a limited time
user_agent                  string (512)                    browser it was opened in
country                     string (2)                      country the address resolves to, which is usually all the owner cares about
password_was_required       boolean                         whether a password had to be given
password_failed             boolean                         whether the attempt failed on the password
viewed_at                   datetime                        when it happened
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [ShareLink](048-share-link-done.md)

## Rules

- A failed password attempt is recorded too. It is the only signal the owner has that a link may have leaked.
- Repeated failures throttle the link and notify the owner. Section 3.3 applies: the link is protected and the owner is told why, it is not silently disabled.
- The visitor is never identified. There is no account behind them and none is created (4.4). What is stored is coarse and enough to answer "was this opened, roughly when, roughly from where".
- Rows are pruned after a retention period, and the aggregate counters on the link survive the pruning.
- Revoking a link keeps its visits. That history is the point.

## Not done yet

- **Nothing prunes a row.** Retention is the open question below, so rows accumulate.
- **Repeated failures tell nobody.** The failed attempts are written down and the two public routes are throttled, which is the part that had to exist first. Telling the owner about a run of them waits for [Notification](042-notification-not-yet.md).
- **Nothing resolves an address to a country.** The column is filled from what the caller passes, `SharedContactController` passes nothing, and no lookup exists behind it. The screen reads the address instead, and switches to the country the day something fills that column.

## Decisions taken while building it

- **A link asking for a password and given none writes nothing.** It is the normal first step rather than a failure, and a row for it would drown the rows that matter.
- **A wrong password is written down before it is refused**, since that row is the whole reason a failed attempt is recorded at all.
- **The counters live on the link and the detail lives here**, which is what lets the aggregate survive the pruning of these rows.
- **The visit carries the account of the link**, so pruning and reading both stay inside one account without joining.
- **A row is written on every view rather than once per visitor.** Walking between two tabs of a shared page writes two, because there is no visitor to count instead: the only honest thing the screen can say is how many times the address was opened.

## Open questions

- How long the address is kept. It is personal data about somebody who never agreed to anything with us, which argues for keeping it briefly or hashing it. The country column exists so the useful part survives the pruning.
