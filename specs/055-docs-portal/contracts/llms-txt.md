# Contract: `GET /llms.txt`

Public, no authentication. `200`, `Content-Type: text/plain; charset=UTF-8`. Format from
llmstxt.org: an `h1`, a blockquote, then `h2` sections of Markdown links. Absolute URLs.

```text
# Monica

> Monica is an open source personal relationship manager. This file lists its documentation as Markdown.

## API reference

- [Complete API reference](https://{host}/docs/api.md): every endpoint in one file
- [{section title}](https://{host}/docs/api/{section id}.md)
- ... one line per section, in the order of the reference
```

- One line per section returned by `ApiDocsViewModel::sections()`, so a new endpoint appears without
  touching this file.
- An area gets its own `h2` once its pages exist (FR-010); none exists today but the API.
- The file is the same for every visitor: English, no dependence on the session or locale.
