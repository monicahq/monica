# API contract: Closing Registration

One endpoint of `/api/v1` changes. It needs no token, and nothing else in the API moves.

## `POST /register`

| Registration | Answer |
| --- | --- |
| Enabled | Unchanged: 201 with `token` and `expires_at`, or 422 on invalid input |
| Disabled | 404, whatever the body holds, and no account is created |

The 404 comes before validation, so a disabled instance never reports which fields were wrong.
