# API contract: changelog entries (`/api/v1`, `auth:sanctum`)

The reference is `resources/docs/api/40-changelog-entries.php`; this file records the shape decided.
Texts come in the reader's locale (`SetApiLocale`). `{entry}` is a ULID; an unknown or future entry
answers 404.

| Method | URI | Name | Body | Answer |
| --- | --- | --- | --- | --- |
| GET, QUERY | `changelog-entries` | `api.v1.changelogEntries` | `period` 30 (default) or 90, `page`, `per_page` | 200 `{data, meta}` |
| PUT | `changelog-entries/seen` | `api.v1.changelogEntries.seen.update` | none | 204, every visible entry seen |
| PUT | `changelog-entries/{entry}/vote` | `api.v1.changelogEntries.vote.update` | `direction`: `up`, `down` | 200 vote |
| DELETE | `changelog-entries/{entry}/vote` | `api.v1.changelogEntries.vote.destroy` | none | 204 |
| GET, QUERY | `changelog-entries/{entry}/comments` | `api.v1.changelogEntries.comments` | none | 200 `{data}` |
| POST | `changelog-entries/{entry}/comments` | `api.v1.changelogEntries.comments.create` | `body` (1 to 2000), `is_private`, `is_anonymous` | 201 comment, `throttle:10,1` |

The list is paged like every other list; `meta` also carries the figures of the whole period:
`page`, `per_page`, `total`, `period`, `unseen_count`, `days`, `features`, `improvements`, `fixes`.
`data` is ordered as the screen: days newest first, features and improvements, then fixes (FR-003).

## Entry

```json
{
  "type": "changelog_entry",
  "id": "01KB1J7XY8M4NPQ2RST6VW9ABC",
  "attributes": {
    "entry_type": "feature",
    "area": "Contacts",
    "title": "Birthdays show the age somebody turns",
    "body": "Next to a birthday…",
    "body_html": "<p>Next to a birthday…</p>",
    "link": "https://monica.test/vaults",
    "day": "2026-11-17",
    "is_seen": false,
    "my_vote": null,
    "comment_count": 3,
    "published_at": 1795000000
  },
  "links": { "comments": "…/changelog-entries/01KB…/comments", "web": "…/changelog" }
}
```

`is_seen` is the state before this request; listing marks nothing.

## Comment

`type` `changelog_comment`, `id` the uuid, `attributes`: `author_name` ("Anonymous" for an anonymous
comment, whoever reads it, FR-007), `is_mine`, `is_private`, `is_anonymous`, `body`, `replies` (public
replies only: `author_name`, `body`, `created_at`), `created_at`, `updated_at`. Hidden comments and
other people's private ones are absent.

Errors: 422 on validation and on a body left empty once markup is removed; 429 past the rate limit.
