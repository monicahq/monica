# API contract: Signing up with an admission

One endpoint of `/api/v1` changes. It needs no token, and nothing else in the API moves. The preview
list itself has no endpoint (spec, clarification of 2026-11-14).

## `POST /register`

One optional field is added to the body.

| Field | Type | Meaning |
| --- | --- | --- |
| `admission` | string, 64 characters | The token at the end of the link an admitted person received |

| Registration | `admission` | Answer |
| --- | --- | --- |
| Enabled | absent | Unchanged: 201 with `token` and `expires_at`, or 422 on invalid input |
| Enabled or disabled | usable | 201 as above. The account takes the admitted address, whatever `email` holds, and that address is verified |
| Disabled | absent, unknown, used, replaced or expired | 404, whatever the body holds, and no account is created |
| Enabled | unknown, used, replaced or expired | The field is ignored and the request is handled as if it were absent |

The 404 still comes before validation, as in `specs/071-closed-registration/contracts/api.md`.
