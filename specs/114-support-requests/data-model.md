# Data model: Support requests

Every table has `id` and timestamps, the first three a `uuid` too, and all cascade on the account's deletion (files: see plan.md).

## `support_requests` (`App\Models\SupportRequest`, `ResolvesModelInAccount`, route key `uuid`)
| Column | Type | Notes |
| --- | --- | --- |
| uuid, number | | `number`: unique random six digits, shown `MON-{number}` (R5) |
| account_id | foreignIdFor(Account) | |
| user_id | foreignIdFor(User), nullable, nullOnDelete | who opened it |
| category | string | `SupportCategoryEnum`: feature, billing, email, question, technical |
| subject | string(255) | |
| status | string | `SupportRequestStatusEnum`: to_handle, waiting_on_user, solved |
| application_version, locale | string | recorded when opened (R11) |
| last_activity_at | timestamp | last message from either side, ordering on "Your requests" |
| solved_at | timestamp, nullable | |

Index: `(status, created_at)`, `(account_id, last_activity_at)`.

## `support_messages` (`App\Models\SupportMessage`)
| Column | Type | Notes |
| --- | --- | --- |
| support_request_id | foreignIdFor(SupportRequest) | |
| author_user_id | foreignIdFor(User), nullable, nullOnDelete | |
| author_name | string | snapshot (R2) |
| kind | string | `SupportMessageKindEnum`: message (user), reply (administrator), note (administrator, never shown to the user) |
| body | text | at least 10 characters for the first message, 1 for the others |

`SupportRequest::visibleMessages()` excludes `note`; every user surface reads through it.

## `support_attachments` (`App\Models\SupportAttachment`)
| Column | Type | Notes |
| --- | --- | --- |
| account_id, support_message_id | foreignIdFor | account for route binding; only on `message` kind |
| disk, path | string | path built by the model, never taken from a request |
| original_name, mime_type | string | images, `application/pdf`, `text/plain` |
| size_in_bytes | unsigned integer | at most 10 MB; at most 5 per message |

## `support_status_changes` (`App\Models\SupportStatusChange`)
| Column | Type | Notes |
| --- | --- | --- |
| support_request_id | foreignIdFor(SupportRequest) | |
| from_status | string, nullable | null on opening |
| to_status | string | |
| user_id | foreignIdFor(User), nullable, nullOnDelete | |
| user_name | string | snapshot |
| created_at | | written once, never updated |

## Transitions (R7)
| Who | Event | From | To |
| --- | --- | --- | --- |
| User | opens | none | to_handle |
| User | writes a message | any | to_handle (reopens a solved one, same number) |
| User | marks solved | to_handle, waiting_on_user | solved |
| Administrator | replies | any | the status chosen with the reply, default waiting_on_user |
| Administrator | moves | any | any other |
| Administrator | adds a note | any | unchanged |

A transition to the same status writes no history row.
