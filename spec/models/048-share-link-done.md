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
includes_activities         boolean                         whether activities appear, false by default
includes_activity_narrative boolean                         whether the free account of an activity appears, false by default
includes_day_narrative      boolean                         whether the account of a day appears, false by default
includes_relationships      boolean                         whether relationships appear
includes_groups             boolean                         whether group memberships appear
includes_sensitive_fields   boolean                         whether fields marked sensitive appear, false by default
excluded_section_ids        json                            sections held back from the shared page
excluded_activity_ids       json                            activities held back individually, tightening the general setting
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
- Activities and their narratives are excluded by default and enabled explicitly (9.6). The setting can be made at the link level and then tightened activity by activity, which `excluded_activity_ids` carries.
- A field marked sensitive stays excluded even when the activity or the section around it is shared (9.6, 5.6).
- A shared record uses the page structure of its precise template version. A page with no included section is omitted from the shared tab list. If only one page remains after exclusions, the tab list is hidden as it is on the private record. Page labels must never reveal the existence of excluded content through an empty tab.
- Revocation is immediate and permanent. A revoked link is never reactivated, a new one is made instead.
- Every link is logged with its creation, its last view and its revocation (4.4), which is what [ShareLinkVisit](049-share-link-visit-done.md) and these columns hold between them.
- Notifications and reminders never reach a share link viewer (10.4).
- Deleting the shared record revokes the link rather than leaving it pointing at nothing (3.3).

## Not done yet

- **Nothing renders a shared page.** The link can be made, edited, revoked and opened, and `OpenShareLink` hands back the link and what it points at. No screen and no API read it, so no exclusion is applied anywhere yet.
- **The page structure rules wait for that screen.** A shared record using the structure of its precise template version, a page with no included section being omitted, and the tab list disappearing when one page is left are all decisions taken while rendering, and nothing renders.
- **Four inclusions store an intent nothing reads.** Activities, activity narratives, day narratives, relationships and group memberships have no models yet ([Activity](036-activity-not-yet.md), [JournalDay](039-journal-day-not-yet.md), [Relationship](026-relationship-not-yet.md), [Group](032-group-not-yet.md)), and neither does `excluded_activity_ids`. The columns are written and read back honestly, and nothing acts on them.
- **Fields marked sensitive are not marked yet.** `includes_sensitive_fields` is stored, and the flag it would consult does not exist on [Field](017-field-done.md).
- **Nothing keeps notifications away from a viewer** (10.4), since [Notification](042-notification-not-yet.md) does not exist and there is nothing to keep away.

## Decisions taken while building it

- **Only the hash of the token is written down**, as for a [MagicLink](003-magic-link-in-progress.md). The token itself exists once, on `ShareLink::$plainToken`, for the length of the request that mints it. A link whoever made it fails to copy is lost rather than recoverable.
- **The uuid and the token are separate things.** The uuid addresses the link on the side of the owner, and the token opens it. Making one the other would put the secret in the address of the screen managing it.
- **Every inclusion is false in the column and false in the action.** The owner opts in, and a link made in a hurry shows the least rather than the most.
- **An update writes the inclusions as a whole rather than merging them**, so a box left unticked closes what it names. The password is the exception: saying nothing about it keeps it, so editing the label of a protected link does not quietly unprotect it.
- **Revocation is refused twice.** `UpdateShareLink` and `RevokeShareLink` both turn a revoked link away, and neither offers a way back.
- **Deleting the shared record revokes the link**, in `DestroyContact` and in `DestroyVault`, which closes every link carrying that vault id in one statement. Both deletions are soft, so a link left alone would go on working. Restoring does not bring the links back: whoever brings a record back decides again who may read it.
- **An unknown, an expired and a revoked token answer the same not found**, so trying tokens teaches nothing.

## Open questions

- **What granularity a link should offer** (16): the whole record, chosen pages or sections, a saved view, or a frozen snapshot. The columns here cover the whole record with section exclusions. Page visibility is derived from the sections that remain, rather than stored as a second exclusion mechanism that could disagree.
- **Whether a vault link always reflects current data or can publish a frozen snapshot** (16). This file assumes live data. A snapshot would need its own storage and is a different feature.
