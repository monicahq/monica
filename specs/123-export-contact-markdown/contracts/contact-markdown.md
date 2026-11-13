# Contract: the contact as Markdown

## Endpoint

`GET /api/v1/vaults/{vault}/contacts/{contact}/markdown` (web: same path without `/api/v1`)

| Case | Answer |
| --- | --- |
| Contact reachable | 200, body is the document, `Content-Type: text/markdown; charset=utf-8`, `Content-Disposition: attachment; filename={slug}.md` |
| Key without `sensitive-fields` | 200, sensitive values absent (not blanked) |
| Other account, or wrong vault | 404 |
| No token | 401 |

MCP `get-contact-markdown`, arguments `vault` and `contact` (as `get-contact`), answers the same document as text.

## Document

Headings and labels are in the reader's locale. A part with nothing in it is left out, heading included. Order:

```markdown
# Joey Tribbiani

Exported from Monica on 13 November 2026, from the vault Friends.

## Names

- **Nickname**: Joey
- **Also written**: ジョーイ・トリビアーニ

## {page label}

### {section name}

- **{field label}**: {formatted value}
- **{repeater label}**
  - {child label}: {value}; {child label}: {value}

## Relationships

### {category}

- **{role}**: {name}, {when line}, ended

## Life events

- **{date}**: {title} ({type})
  - With: Chandler, Ross
  - {detail}
  - {narrative, indented}

## Groups

- **{group}**: {role}, {period}

## Reminders

- **{name}**: {cadence}, next on {date}

## Loans

- **{possession}**: {verb}, {status}, {period}
```

Pages follow the structure version order, then sections by their assembly order, then fields by position, exactly as the contact screen draws them. Life events are newest first. Values are formatted by `FieldValueFormatter` for the reader, so dates keep their precision and a file field shows its file name.
