# HTTP contracts

`{request}` is the uuid. Every user route reaches the signed in user's account only and answers 404
otherwise. Every admin route sits behind `administrator`; the admin API also needs the
`administration` key scope (R9) and answers 404 without it.

## Web, user (`routes/web.php`)

| Verb | URL | Name | Does |
| --- | --- | --- | --- |
| GET | `support/requests` | `support.requests.index` | Your requests, Open and Solved tabs |
| GET | `support/requests/new` | `support.requests.new` | Category, guidance, subject, message, files |
| POST | `support/requests` | `support.requests.create` | OpenSupportRequest, redirect to confirmation |
| GET | `support/requests/{request}/confirmation` | `support.requests.confirmation.show` | Request sent |
| GET | `support/requests/{request}` | `support.requests.show` | Thread, reply, Mark as solved |
| POST | `support/requests/{request}/messages` | `support.requests.messages.create` | AddSupportMessage |
| POST | `support/requests/{request}/solution` | `support.requests.solution.create` | SolveSupportRequest |
| GET | `support/attachments/{attachment}` | `support.attachments.show` | Streams the file |

## Web, admin (`routes/administrator.php`)

| Verb | URL | Name | Does |
| --- | --- | --- | --- |
| GET | `admin/support` | `admin.support.index` | Queue; `view`, `q`, `category`, `user`, `age` |
| GET | `admin/support/{request}` | `admin.support.show` | Queue with this request open |
| POST | `admin/support/{request}/replies` | `admin.support.replies.create` | SendSupportReply, `body`, `status` |
| POST | `admin/support/{request}/notes` | `admin.support.notes.create` | AddSupportNote |
| PUT | `admin/support/{request}/status` | `admin.support.status.update` | ChangeSupportRequestStatus |
| GET | `admin/support/attachments/{attachment}` | `admin.support.attachments.show` | Streams the file |

## API, user (`routes/api.php`, prefix `v1`, `auth:sanctum`)

| Verb | URL | Body / query | Answer |
| --- | --- | --- | --- |
| GET | `support-requests` | `status`, `page`, `per_page` | 200, `data[]` + `meta` |
| POST | `support-requests` | multipart: `category`, `subject`, `body`, `files[]` | 201, request |
| GET | `support-requests/{request}` | | 200, request with `messages[]` (no notes) and `attachments[]` |
| POST | `support-requests/{request}/messages` | multipart: `body`, `files[]` | 201, message |
| POST | `support-requests/{request}/solution` | | 200, request |
| GET | `support-requests/{request}/attachments/{attachment}/content` | | 200, file |

## API, admin (prefix `v1/admin`, `auth:sanctum`, `administrator`)

| Verb | URL | Body / query | Answer |
| --- | --- | --- | --- |
| GET | `support-requests` | `view`, `q`, `category`, `user`, `age`, `page` | 200, `data[]`, `meta.counts` per view |
| GET | `support-requests/{request}` | | 200, with notes, `context`, `status_changes[]` |
| POST | `support-requests/{request}/replies` | `body`, `status` | 201, message |
| POST | `support-requests/{request}/notes` | `body` | 201, message |
| PUT | `support-requests/{request}/status` | `status` | 200, request |

Request resource: `id` (uuid), `number`, `category`, `subject`, `status`, `status_label`,
`opened_at`, `last_activity_at`, `solved_at`. Message resource: `id`, `kind`, `author_name`,
`is_support_team`, `body`, `created_at`, `attachments[]` (`id`, `name`, `size_in_bytes`, `mime_type`).
The user resource never carries `kind: note`.
