# Contract: what each flag withdraws

Every flag is declared in `app/Features`, listed by `monica:list-flags`, switched by
`monica:switch-flag` and the admin Flags screen exactly as specification 068's contract says.

| Flag | On deployment | Surface | Answer when off for the account |
| --- | --- | --- | --- |
| `carddav` | off | every method on `dav/{path?}`, signed in | 404 (a request without credentials still gets the 401 challenge) |
| `carddav` | off | DAV address block, security settings | not drawn |
| `carddav` | off | `docs.carddav.index`, `docs.carddav.markdown.index` | 404 |
| `vcard-export` | off | `GET vaults/{vault}/contacts/{contact}/vcard`, web and `v1` API | 404 |
| `vcard-export` | off | "Export as vCard" in the contact's actions menu | not drawn |
| `vcard-export` | off | `contacts-vcard` in the API docs, OpenAPI and `llms.txt` | left out |
| `sms-notifications` | off | every `notification-channels/sms...` route, web and `v1` API | 404 |
| `sms-notifications` | off | `GET v1/notification-channels`, notification channels screen | the SMS row is left out |
| `sms-notifications` | off | writing, digesting and delivering a notification on SMS | none written; a queued one is abandoned without counting a failure |
| `file-upload` | off | `POST settings/account/profile/photo`, `POST .../contacts/{contact}/avatar` (web and `v1`) | 404 |
| `file-upload` | off | a file, image or avatar field in a section, life event or entity (web and `v1`) | 302 back with errors, or 422, on `fields.{id}`: "Uploads are unavailable on this account for now." |
| `file-upload` | off | a PHOTO in a vCard written over CardDAV | ignored; the avatar is kept |
| `mcp-server` | **on** | `mcp` endpoint | 404 |
| `mcp-server` | **on** | `GET oauth/authorize` | 404 |
| `mcp-server` | **on** | `docs.mcp.index`, `docs.mcp.markdown.index` | 404 |

Left open whatever the flag: deleting a profile photo or an avatar, downloading a stored file, the
`oauth/token` routes, the OAuth discovery documents, and the instance settings for Vonage.

## The sentence of FR-014

| Where | Shown when the flag is off and | Copy |
| --- | --- | --- |
| Notification channels | the SMS row holds a destination | "Text messages are unavailable on this account for now. Your number is kept." |
| Connected assistants | at least one assistant is connected | "The MCP server is unavailable on this account for now. Your connected assistants are kept." |
| A field, the profile photo, the avatar | a file is held, or the field is required | "Uploads are unavailable on this account for now." |

The copy is a proposal for review; it goes through `__()` and `monica:localize` like any string.
