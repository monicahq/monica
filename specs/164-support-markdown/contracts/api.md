# Contract: API and MCP

No route, parameter or field is added, removed or renamed. What changes is what `body` may hold and
how it is documented.

| Field | Change |
| --- | --- |
| `body` on `support_message` | Markdown, exactly as written. No longer stripped of anything resembling a tag, so it may contain `<` and `>`. |
| `body` parameter of the four writing endpoints | Accepts Markdown. Limits unchanged: required, at most 20,000 characters, at least 10 when opening a request. |

## Documentation

- `body` described as "What was written, in Markdown, exactly as written. Format it safely before
  displaying it, and never insert it into a page as HTML." in
  `resources/docs/api/32-support-requests.php` and `33-support-administration.php`.
- An entry in `resources/docs/api-changelog/`, dated the day it ships, saying `body` is Markdown and
  is no longer stripped of tags.
- The MCP tools `create-support-request`, `add-support-message`, `send-support-reply` and
  `add-support-note` describe `body` as Markdown rather than plain text.
