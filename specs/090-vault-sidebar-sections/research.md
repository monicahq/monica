# Research: Vault Sidebar Sections

## R1. A stricter scope for the column, the old one kept for access

- **Decision**: add `EntityType::scopeConfiguredInVault()`: active, with a vault default template whose
  version is published and whose template is active. `scopeVisibleInVault()` becomes
  `configuredInVault` or holds records in the vault. The column asks `configuredInVault`.
- **Rationale**: `visibleInVault` also guards the list and form of a type, on the web
  (`EntityController::refuseAHiddenType()`) and on the API (`Api\EntityController::entityType()`).
  Narrowing it would make records of an unconfigured type answer not found, which principle VIII
  forbids. Splitting it keeps one statement of "configured" and leaves access exactly as it is.
- **Rejected**: narrowing `visibleInVault` (records become unreachable); filtering in the sidebar
  component (a second copy of the rule).
- A deleted type is already absent: `EntityType` uses `SoftDeletes`.

## R2. The placeholder screen has no view model

- **Decision**: `PossessionController::index()` passes `user` and `vault` to
  `app.vaults.possessions.index`. The screen is the shell, the vault breadcrumb, a page header and
  `x-empty-state`.
- **Rationale**: a view model assembles what a screen shows; this one shows nothing from the
  database. A class returning its two constructor arguments is the over-engineering `CLAUDE.md`
  warns against. Controllers passing plain values to a view exist (`ContactLifeEventFieldController`).
- **Rejected**: an empty `PossessionsViewModel`.
- The screen answers for an account with no possession type as for any other (spec, US3): only the
  column decides whether to offer it.

## R3. No flag, at the user's request

- **Decision**: the entry and the screen ship on, without a Pennant flag.
- **Rationale**: `.claude/rules/flags.md` says a capability still being built stays out of sight
  behind a flag. The user asked for the link to show now, so the conversation wins (`CLAUDE.md`,
  "Which rules win", rank 1). Named here so the departure is on record.

## R4. The column is built the way the settings column already is

- **Decision**: the vault column becomes a list of groups, each `['title' => ?string, 'entries' => [...]]`,
  rendered with the same `h2` caption markup `x-settings.sidebar` uses. Vault settings is a last
  group with no title, pushed to the foot with `mt-auto` in a `flex flex-col` nav.
- **Rationale**: one caption style across both columns, which is what variant 1a asks for
  ("Micro-labels, same as ‘This vault’"). Not extracted into a component: two files, and the markup
  carries no behavioural contract (`.claude/rules/components.md`).
- The custom types move from "after People in a flat loop" to entries of the relationships group,
  so the special case `$section['key'] === 'contacts'` goes.
- `Sidebar::render()` gains `hasPossessionTypes`, one `exists()` query on the account.

## R5. Words

| Copy | Note |
| --- | --- |
| Your relationships, Your possessions, All possessions | new; “possession” in the glossary sense of the possession type row |
| Possessions | exists (`Biens` in French); the screen title |
| One sentence for the empty state | new, says possessions are coming and nothing is lost |

Every locale through `php artisan monica:localize`, French first as the reference, the others by
subagents checked against the glossary.

## R6. The guide

`docs/portal/en/3-people/6-profiles.md` says a profile kind sits "under **People**"; it becomes
"under **Your relationships**", and its troubleshooting gains the case of a kind not configured in the
vault. The screenshot `3-reminders.index.webp` shows the old column and is retaken.
