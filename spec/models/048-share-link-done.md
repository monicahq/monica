# 048. ShareLink

**Status:** exists (`app/Models/ShareLink.php`), built as far as the models it depends on allow
**Spec:** 4.4, 9.6, 5.6, 3.3

## What it is

A private link giving read access to one contact page or one vault, the way a document is shared.

Section 4.4 is careful about what this is not. It is not collaborative access to the application. The recipient needs no account and never becomes a user of the account they are looking into. Sharing a vault does not recreate fine grained permissions, multi person editing, shared notifications or collective ownership. This is how the product answers the sharing need without letting multi user back in through the side door.

Section 4.4 lists what every link must be, and every item on that list is a column here.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier of the link itself, never the secret token in the URL
account_id                  int                             account that created the link
vault_id                    int                             vault being shared, or the vault of the contact being shared
shareable_type              string (255)                    class of what is shared, a contact or a vault
shareable_id                int                             id of that object
token                       string (64)                     hash of the unguessable token in the URL, the token itself never stored
label                       string (255)                    name the owner gave the link, so several links are tellable apart
recipient_hint              string (255)                    who it was meant for, for the owner's own memory
password_hash               string (255)                    hash of the password protecting it, null when there is none
expires_at                  datetime                        when it stops working, null when it never does
revoked_at                  datetime                        when it was revoked, which is immediate and permanent
includes_files              boolean                         whether attached files are reachable through it
includes_life_events         boolean                         whether life events appear, false by default
includes_life_event_narrative boolean                         whether the free account of a life event appears, false by default
includes_day_narrative      boolean                         whether the account of a day appears, false by default
includes_relationships      boolean                         whether relationships appear
includes_groups             boolean                         whether group memberships appear
includes_sensitive_fields   boolean                         whether fields marked sensitive appear, false by default
excluded_section_ids        json                            sections held back from the shared page
excluded_life_event_ids       json                            life events held back individually, tightening the general setting
view_count                  int                             how many times it was opened
last_viewed_at              datetime                        when it was last opened
created_by_user_id          int                             user who created it
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md), [User](002-user-done.md)
- `morphTo` shareable, a [Contact](019-contact-done.md) or a [Vault](007-vault-in-progress.md)
- `hasMany` [ShareLinkVisit](049-share-link-visit-done.md)

## Rules

- **Read only. Always.** A shared vault is a read only view (4.4). There is no path from a share link to a write, and no feature may be added that creates one.
- Only the hash of the token is stored, as for a magic link. A database dump must not yield working links.
- **Everything sensitive is excluded by default** (4.4): fields marked sensitive, the personal narrative of the journal, the modification history and technical data. The owner opts in, never out.
- Life events and their narratives are excluded by default and enabled explicitly (9.6). The setting can be made at the link level and then tightened life event by life event, which `excluded_life_event_ids` carries.
- A field marked sensitive stays excluded even when the life event or the section around it is shared (9.6, 5.6).
- A shared record uses the page structure of its precise template version. A page with no included section is omitted from the shared tab list. If only one page remains after exclusions, the tab list is hidden as it is on the private record. Page labels must never reveal the existence of excluded content through an empty tab.
- Revocation is immediate and permanent. A revoked link is never reactivated, a new one is made instead.
- Every link is logged with its creation, its last view and its revocation (4.4), which is what [ShareLinkVisit](049-share-link-visit-done.md) and these columns hold between them.
- Notifications and reminders never reach a share link viewer (10.4).
- Deleting the shared record revokes the link rather than leaving it pointing at nothing (3.3).

## Not done yet

- **A vault link renders nothing.** `s/{token}` opens a contact page and says plainly that a whole vault has no page yet. The columns and the actions treat both alike; only the screen is missing.
- **Five inclusions store an intent nothing reads.** Files, life events, life event narratives, day narratives, relationships and group memberships have no models yet ([LifeEvent](036-life-event-not-yet.md), [JournalDay](039-journal-day-not-yet.md), [Relationship](026-relationship-not-yet.md), [Group](032-group-not-yet.md)), and neither does `excluded_life_event_ids`. The columns are written and read back honestly, nothing acts on them, and the form does not offer a control for any of them.
- **Nothing has to keep notifications away from a viewer** (10.4). [Notification](042-notification-done.md) is addressed to a `user_id`, and somebody holding a link is not a user of the account, so there is no path by which one could reach them.

## What renders it

`s/{token}/{page?}` sits outside the auth group and is the only place a link is opened, which is what makes it the only place a visit is written. `App\ViewModels\Shared\SharedContactViewModel` is where the exclusions stop being a stored intention: a section the link excludes is never read, a page whose every section is excluded is not a tab, one page left means no tab list, and a field marked sensitive is dropped unless the link says otherwise. The screens that make and manage a link are `ContactShareLinkController` under `vaults/{vault}/contacts/{contact}/share-links`, addressed by uuid so the token is never in the address of the screen managing it.

## Decisions taken while building it

- **Only the hash of the token is written down**, as for a [MagicLink](003-magic-link-in-progress.md). The token itself exists once, on `ShareLink::$plainToken`, for the length of the request that mints it. A link whoever made it fails to copy is lost rather than recoverable.
- **The uuid and the token are separate things.** The uuid addresses the link on the side of the owner, and the token opens it. Making one the other would put the secret in the address of the screen managing it.
- **Every inclusion is false in the column and false in the action.** The owner opts in, and a link made in a hurry shows the least rather than the most.
- **An update writes the inclusions as a whole rather than merging them**, so a box left unticked closes what it names. The password is the exception: saying nothing about it keeps it, so editing the label of a protected link does not quietly unprotect it.
- **Revocation is refused twice.** `UpdateShareLink` and `RevokeShareLink` both turn a revoked link away, and neither offers a way back.
- **Deleting the shared record revokes the link**, in `DestroyContact` and in `DestroyVault`, which closes every link carrying that vault id in one statement. Both deletions are soft, so a link left alone would go on working. Restoring does not bring the links back: whoever brings a record back decides again who may read it.
- **An unknown, an expired and a revoked token answer the same not found**, so trying tokens teaches nothing.
- **A field holding a file is dropped from a shared page whatever `includes_files` says.** Handing the file over would need a public route onto it carrying the link's authority, and the screen has none. Repeated blocks go with them, since what they hold lives on rows of its own.
- **A password that opened a link is kept in the visitor's session**, keyed by the hash of the token so the session holds no working address. Without it, walking between two tabs would ask again; the alternatives were re-posting the form on every refresh, or a second way into the record that skips the password and would one day be reached by something that should not have skipped it.
- **The form only asks about what the product can honour.** Sensitive fields and section exclusions are the two questions on it. The five inclusions with no model behind them are named instead, in the list of what stays here, so nothing on screen promises something the page does not do.
- **Live, expired and revoked are read rather than stored.** They are three readings of two nullable dates, and only a screen needs the reading, so there is no status column and no enum.

## Open questions

- **What granularity a link should offer** (16): the whole record, chosen pages or sections, a saved view, or a frozen snapshot. The columns here cover the whole record with section exclusions. Page visibility is derived from the sections that remain, rather than stored as a second exclusion mechanism that could disagree.
- **Whether a vault link always reflects current data or can publish a frozen snapshot** (16). This file assumes live data. A snapshot would need its own storage and is a different feature.
