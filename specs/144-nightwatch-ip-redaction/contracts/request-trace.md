# Contract: what a request trace may carry about a person

The application's outbound interface here is the trace handed to Laravel Nightwatch. After the
application's redaction callbacks run, a request trace MUST satisfy all of the following.

| Rule | Checked by |
| --- | --- |
| `ip` is the empty string | `it_withholds_the_address_from_a_nightwatch_request` |
| None of `X-Forwarded-For`, `X-Real-IP`, `Forwarded`, `CF-Connecting-IP`, `True-Client-IP` is present | the same test |
| Every other header is kept as it was | the same test, asserting `Content-Type` survives |
| The URL redactions of `it_redacts_a_token_a_signature_and_a_search_term_from_a_nightwatch_request_url` still apply | that existing test |
| The user details are exactly `['id' => <identifier>]`, with no `name` and no `username` | `it_identifies_a_nightwatch_user_by_id_only` |
