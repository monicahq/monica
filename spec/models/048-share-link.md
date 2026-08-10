# 048. ShareLink

**Status:** to create
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

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md), [User](002-user.md)
- `morphTo` shareable, a [Contact](019-contact.md) or a [Vault](007-vault.md)
- `hasMany` [ShareLinkVisit](049-share-link-visit.md)

## Rules

- **Read only. Always.** A shared vault is a read only view (4.4). There is no path from a share link to a write, and no feature may be added that creates one.
- Only the hash of the token is stored, as for a magic link. A database dump must not yield working links.
- **Everything sensitive is excluded by default** (4.4): fields marked sensitive, the personal narrative of the journal, the modification history and technical data. The owner opts in, never out.
- Activities and their narratives are excluded by default and enabled explicitly (9.6). The setting can be made at the link level and then tightened activity by activity, which `excluded_activity_ids` carries.
- A field marked sensitive stays excluded even when the activity or the section around it is shared (9.6, 5.6).
- A shared record uses the page structure of its precise template version. A page with no included section is omitted from the shared tab list. If only one page remains after exclusions, the tab list is hidden as it is on the private record. Page labels must never reveal the existence of excluded content through an empty tab.
- Revocation is immediate and permanent. A revoked link is never reactivated, a new one is made instead.
- Every link is logged with its creation, its last view and its revocation (4.4), which is what [ShareLinkVisit](049-share-link-visit.md) and these columns hold between them.
- Notifications and reminders never reach a share link viewer (10.4).
- Deleting the shared record revokes the link rather than leaving it pointing at nothing (3.3).

## Open questions

- **What granularity a link should offer** (16): the whole record, chosen pages or sections, a saved view, or a frozen snapshot. The columns here cover the whole record with section exclusions. Page visibility is derived from the sections that remain, rather than stored as a second exclusion mechanism that could disagree.
- **Whether a vault link always reflects current data or can publish a frozen snapshot** (16). This file assumes live data. A snapshot would need its own storage and is a different feature.
