# Contract: the supplied Animal template

What any account can rely on once this feature ships. The layout itself is in
[data-model.md](../data-model.md).

## New account

- Holds Basic animal as today and Animal, slug `animal`, published at version 1, both applying to the
  one `animals` kind.
- Every vault, including vaults created later, starts a new animal on Basic animal unless the person
  picks Animal.
- Holds the `neuter_statuses` value list with three items.

## Existing account, after the backfill

- Every template, version, vault default and record it held is unchanged, Basic animal included.
- Holds Animal, applying to the same `animals` kind as Basic animal, unless one of the refusals in
  data-model.md applied, in which case the log names the account and the reason.
- Running the backfill again adds nothing.

## API and MCP

Animal is read and listed through the existing template and structure endpoints. Creating an entity
takes an optional `structure_version_id`, a published version of an active template of the same kind
in the account; without it the vault's default is used, as today. Anything else answers 422 on the
API and is relayed word for word by the `create-entity` tool.

## The form that adds a record

- Offers every active template of the kind with a published version, the vault's default first and
  current, when there are at least two. Each choice is a link to `entities.new` with the template's
  slug as last segment, the current one marked `aria-current="page"`.
- Draws the fields of the chosen template's published version and posts that version.
