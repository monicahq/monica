# Contract: MCP tools

Both tools use `MirrorsAnApiCapability`: they look the vault, the contact and the template up through the
trait (so a record of another account answers "does not exist"), then answer through the API controller
their `$mirrors` route names. They hold no rule of their own (`.claude/rules/mcp.md`).

## `preview-contact-template-change`

- `$mirrors = 'api.v1.contacts.templatePreview.show'`
- `#[IsReadOnly]`, `#[IsDestructive(false)]`, `#[IsOpenWorld(false)]`
- Description: "Say what changing a contact's template would do, without changing anything: the answers
  that would be set aside under Unused answers, those that would come back, and the reminders that would
  stop or run again. Call it before change-contact-template and tell the person what it says."
- Parameters: `vault` (string, required), `contact` (string, required), `template` (string, required, as
  list-templates gives it), `structure_version_id` (integer, optional).
- Answer: the API's `attributes`, without the `data` wrapper.

## `change-contact-template`

- `$mirrors = 'api.v1.contacts.template.update'`
- `#[IsReadOnly(false)]`, `#[IsDestructive]`, `#[IsOpenWorld(false)]`
- Description: "Put a contact on another template. Answers with no matching field on the new template are
  set aside, not deleted, and reminders on them stop. Preview the change with
  preview-contact-template-change first and show the result to the person before calling this. The
  change is written to the account log and to the contact's version history."
- Parameters: as the preview.
- Answer: the contact as the API returns it.

Both are added to the `Contacts` family in `McpDocsViewModel::FAMILIES` and registered in
`MonicaServer`.
