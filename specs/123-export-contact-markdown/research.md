# Research: Export a contact as Markdown

## R1. Where the document is written

- **Decision**: `App\ViewModels\Vaults\ContactMarkdownViewModel::document()` returns the string. `ExportContactMarkdown` calls it after refusing another account, then journals.
- **Rationale**: the document is a rendering of the contact screen, which is presentation, not business logic. Three view models already produce a Markdown string served with no Blade in between (`McpDocsViewModel`, `CarddavDocsViewModel`, `PortalDocsViewModel`). No action has called a whole view model before; this one does, which goes against the `CLAUDE.md` line that view models end in Blade. The requester decides at review. Reusing `RelationshipsBlock`, `ContactGroupsBlock` and `ContactLoansBlock` keeps the document saying what the screen says.
- **Rejected**: a Blade template with a `.md` output (Blade's newline handling makes list structure fragile); a writer in `app/Helpers` (it would have to call view model blocks, or copy them).

## R2. Sensitive values

- **Decision**: a `reachesSensitiveFields` flag on the view model. When false it leaves out the values of sensitive fields everywhere they appear: contact fields, repeater children, life event fields, and reminders driven by a sensitive field. The web passes `true`; the API and MCP read the key scope the way `ContactVCardController` and `MirrorsAnApiCapability::reachesSensitiveFields()` do.
- **Consequence**: life events are queried by the view model itself rather than through `ContactTimelineBlock`, which pages, mixes in memberships and has no sensitive filter.

## R3. Keeping user text from breaking the structure

- **Decision**: values are written as typed, except that each line of a multi-line value is indented under its list item, and a line beginning with a block marker (`#`, `>`, `-`, `*`, `+`, `|`, a fence, or a number followed by `.` or `)`) gets a leading backslash. Inline characters are not escaped.
- **Rationale**: an assistant reads the text best unescaped; only block markers can move a value out of its item or forge a heading. SC-007 is tested with a value holding `# Heading` and a list.

## R4. Copying to the clipboard

- **Decision**: the copy item fetches the same web route and writes the text with `navigator.clipboard.write([new ClipboardItem({'text/plain': promise})])`, which keeps the user gesture in Safari; where `ClipboardItem` is missing it awaits the text and falls back to `window.copyText()`. The result is announced in an `aria-live="polite"` region beside the menu.
- **Consequence**: a copy is an export and is journalled like a download (FR-010).
- **Rejected**: printing the document into the page on every visit (cost on every view, and a journal entry nobody asked for).

## R5. No flag

- **Decision**: no Pennant flag. `.claude/rules/flags.md` keeps flags for a capability still being built; this ships whole in one change. The vCard export keeps its own flag, untouched.

## R6. Journal

- **Decision**: a new case `ContactExportedAsMarkdown = 'contact_exported_as_markdown'`, sentence "Exported the contact :name as Markdown". The surface is recorded by `LogUserAction` already (`source`: web, API or MCP).

## R7. Names and routes

| Surface | Name |
| --- | --- |
| Web route | `GET vaults/{vault}/contacts/{contact}/markdown`, `contacts.markdown.show` |
| API route | same path, `api.v1.contacts.markdown.show` |
| MCP tool | `get-contact-markdown`, mirrors `api.v1.contacts.markdown.show`, read only |
| File name | `{slug}.md`, `text/markdown; charset=utf-8` |

## R8. Documentation

API reference entry in `resources/docs/api/06-contacts.php` (with `responseMediaType`), an entry in today's API changelog file, the tool in `McpDocsViewModel::toolGroups()` under "Contacts, reading", a help centre page `export.markdown` beside `export.vcard` plus a line in `sharing.introduction`, and `docs/FEATURES.md`.
