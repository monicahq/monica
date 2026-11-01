# Contract: groups over the API, MCP and CardDAV

All API routes under `auth:sanctum`, prefix `/api/v1`, names prefixed `api.v1.`, behind the `groups`
flag. `{vault}` takes a slug or an id; `{group}` and `{membership}` are UUIDs. Anything outside the
account or the vault answers 404. Lists take `page` and `per_page` like life events.

| Method | Path | Name | Answer |
| --- | --- | --- | --- |
| GET | `vaults/{vault}/groups` | `groups` | 200, `data` + `meta` |
| POST | `vaults/{vault}/groups` | `groups.create` | 201, group |
| GET | `vaults/{vault}/groups/{group}` | `groups.show` | 200, group |
| PUT | same | `groups.update` | 200, group |
| DELETE | same | `groups.destroy` | 204 |
| PUT | `.../groups/{group}/restoration` | `groups.restoration.update` | 200, group |
| GET | `.../groups/{group}/members` | `groups.members` | 200, current then former, `data` + `meta` |
| POST | same | `groups.members.create` | 201, membership |
| PUT | `.../groups/{group}/members/{membership}` | `groups.members.update` | 200, membership |
| DELETE | same | `groups.members.destroy` | 204, removes a membership recorded by mistake |
| GET | `vaults/{vault}/contacts/{contact}/groups` | `contacts.groups` | 200, the contact's memberships |

Group fields: `name` (required on create, max 255), `description`, `started_on`, `started_precision`,
`ended_on`, `ended_precision`. Membership fields: `contact` (slug or id, create only), `role`,
`joined_on`, `joined_precision`, `left_on`, `left_precision`. Dates are `Y-m-d`; precision is one of
`day`, `month`, `year`, `decade`, `unknown`. On update an absent field keeps its value.

```json
{ "type": "group", "id": "uuid", "attributes": { "name": "Halpert household", "description": null,
  "started_on": "2005-01-01", "started_precision": "year", "ended_on": null, "ended_precision": "unknown",
  "current_member_count": 3, "created_at": "...", "updated_at": "..." } }
```

```json
{ "type": "group_membership", "id": "uuid", "attributes": { "group": { "id": "uuid", "name": "Scranton team" },
  "contact": { "id": "uuid", "slug": "ryan-howard", "name": "Ryan Howard" }, "role": "Temp",
  "joined_on": "2009-01-01", "joined_precision": "year", "left_on": null, "left_precision": "unknown",
  "is_current": true } }
```

## MCP tools

One tool per route, through `MirrorsAnApiCapability`: `ListGroups`, `GetGroup`, `CreateGroup`, `UpdateGroup`, `DeleteGroup`, `RestoreGroup`, `ListGroupMembers`, `AddGroupMember`, `UpdateGroupMember`, `RemoveGroupMember`, `ListContactGroups`. Registered only while the flag is on.

## CardDAV group card, at `<uuid>.vcf` or at `dav_uri` once a device named it

```text
BEGIN:VCARD
VERSION:4.0
UID:<group uuid>
KIND:group
FN:Halpert household
MEMBER:urn:uuid:<contact uuid>
X-ADDRESSBOOKSERVER-KIND:group
X-ADDRESSBOOKSERVER-MEMBER:urn:uuid:<contact uuid>
END:VCARD
```

Read back: either `KIND` form marks a group card; `FN` renames; either `MEMBER` form lists members.
Every other property is ignored. `CATEGORIES` on a contact card is never read as a membership.
