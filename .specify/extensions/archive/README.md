# Spec-Kit Archive

A Spec-Kit extension to archive merged features into the main project memory.

## Overview

The `speckit.archive.run` command runs after a merge. It consolidates finalized feature specifications, plans, and technical debt into the project's canonical memory (`.specify/memory/`).

This extension is the "Outer Loop" of the Double-Loop Parity framework: once a PR is merged, it makes sure the project remembers the feature correctly.

## Features

- **Lifecycle separation**: Operates purely on merging feature-level knowledge into project-level memory.
- **Layout tolerant**: Accepts both feature-directory naming schemes spec-kit creates, sequential (`specs/007-invoice-settings`) and timestamped (`specs/20260814-101500-invoice-settings`). It also tolerates a feature nested under a scope directory (`specs/billing/006-invoice-settings`), which spec-kit does not create itself but which projects do arrange by hand. Nothing keys on a three-digit prefix, and a feature is always named by its full path relative to the repository root, so two features sharing a basename in different scopes are never confused. Passing a scope directory is refused rather than expanded into a batch run.
- **Ecosystem consistency**: Uses the core Spec-Kit `check-prerequisites` script to locate the repository root, in whichever runtime the project was initialised with: bash, PowerShell or Python. The feature to archive always comes from the path you pass, never from the script's own feature state, which points at whatever you worked on last rather than what you are archiving.
- **Consolidation**: A detection pass keys every incoming item with a semantic slug, shortlists lookalike pairs against the existing entries, and issues an explicit fold / separate / contradiction verdict per pair. It then folds exactly the fold verdicts, so the main spec stays a single consolidated specification instead of a per-feature digest. The report always states how many pairs were examined and folded, so "zero duplicates" means "examined and found distinct", not "did not look". Existing entries are never merged into each other, so an established requirement ID cannot disappear behind your back.
- **Traceability**: Adds item-level `[Source: specs/007-invoice/spec.md -> FR-012]` refs and revision notes in the main memory artifacts. A ref names the artifact the content actually came from (`spec.md`, `plan.md`, `data-model.md`), and an entry consolidated from several features carries one ref per feature.
- **Supersession**: Detects requirements a later feature wholly replaces and asks you to confirm before deleting anything. Confirmed removals are deleted from the main spec and recorded in `changelog.md`, and their IDs are never reissued. Anything you do not confirm stays put and is recorded as an unresolved contradiction, so the next archival raises it again instead of losing it.
- **Constitution compliance**: Checks each feature against the project constitution in three ways, because a MUST rule can fail in more than one. A **conflict** is feature content that contradicts a rule; you are asked, and an unresolved one withholds *that item only* while the rest of the feature archives normally. An **unmet obligation** is a rule requiring a statement the feature never makes ("every feature that stores user data MUST state its retention rule"); you are asked about it, but it is never a reason to withhold anything. An **action-requiring** rule ("all API routes MUST have automated tests") is reported as unverified and never flagged, because this command reads artifacts and cannot inspect a test run: a plan claiming coverage is a claim, not a verification. A feature's own `## Constitution Check` is read but never archived, and it cannot close a flag by asserting compliance, though a sentence in it recording what changed and why does satisfy a rule that asks for exactly that.
- **Bounded inputs**: Declares the complete list of files it may take content from. Git history, deleted files, ad-hoc notes and agent memory stores are not sources, and a missing artifact is never reconstructed. This is what keeps runs reproducible and keeps the `[Source: ...]` refs honest.
- **Bug awareness**: Works with both bug-report layouts, the feature-scoped `specs/###-feature/bugs/BUG-###.md` that extensions such as `spec-kit-bugfix` write and the repo-level `.specify/bugs/<slug>/` that the first-party `bug` extension writes. It takes requirement text from neither, and reads the field names and headings those tools actually emit rather than plausible-sounding variants. Patched amendments arrive through the feature's own artifacts, where struck-through text is never archived as live. Each report is audited by status (treated as a claim, not a verification), addressed bug identifiers land in the changelog entry, and root-cause sections feed the agent file's Known Issues. A repo-level report counts as this feature's only when a `**Bugfix**:` annotation in the feature's artifacts names its slug: that layout records no owning feature, so there is no honest way to infer one, and resemblance, dates and touched files never attribute a report. Attribution reads no file at all, since a slug is its directory name. The report states how many slugs were found against how many were attributed, and an unattributed report is never opened.
- **Agent context aware**: Current spec-kit does not manage agent context files itself; the opt-in `agent-context` extension does. This command reads that extension's config to find the files it manages, honours a custom marker pair, and updates **every** anchor a project keeps in sync rather than guessing a single filename. It never writes inside a tool-managed marker block, never creates a context file, and skips and reports any target that is missing or has no writable region. When that config names nothing, which is the state a fresh install leaves it in, the command performs the same lookup `agent-context` performs, resolving the integration recorded in `.specify/init-options.json` against that extension's own defaults map. A Copilot, Cursor or Qwen project therefore lands on its real anchor (`.github/copilot-instructions.md`, `.cursor/rules/specify-rules.mdc`, `QWEN.md`) rather than being missed. Only when nothing names a file does it probe `GEMINI.md`, `AGENTS.md`, `CLAUDE.md`, and it says so in the report when it does.
- **Reporting**: Uses absolute paths throughout the final Archival Report, so the log stays readable whichever directory you ran the command from.

## Hooks

The command checks `.specify/extensions.yml` for `before_archive` and `after_archive` hooks. **These are extension-defined events, not core Spec-Kit ones.** Core fires `before_`/`after_` hooks for its own commands (`specify`, `plan`, `tasks`, `implement`, and so on); archival runs after the cycle, so this command reads and reports the hooks itself. Another extension can register on them, but they only fire when `speckit.archive.run` is invoked.

## Requirements

Spec-Kit **0.14.0 or later**. That floor is set by the last core feature this extension depends on: `scripts/python` shipping in the wheel, which is what the new `py:` frontmatter entry needs in order to resolve. Declaring `py:` is what creates that dependency; before it, a `--script py` project fell back to the platform shell scripts, which `--script py` installs too, so the extension ran but ignored the runtime the project had chosen. The other dependencies land earlier: validated `category`/`effect` manifest fields in 0.10.2, the `agent-context` extension owning context files in 0.12.0, and `py:` command frontmatter in 0.12.10. There is no upper bound, because every lookup this command added degrades to previous behaviour when its input is absent.

## Installation

You can install this extension via the Spec-Kit CLI:

```bash
specify extension add archive --from https://github.com/stn1slv/spec-kit-archive/archive/refs/tags/v1.3.0.zip
```
*(Note: Replace `v1.3.0` with the latest release version)*

To upgrade an existing installation, add `--force`. Without it the CLI refuses to overwrite the installed version:

```bash
specify extension add archive --from https://github.com/stn1slv/spec-kit-archive/archive/refs/tags/v1.3.0.zip --force
```

## Usage

```bash
/speckit.archive.run <feature-dir>
```

> The command ID is `speckit.archive.run`. Invoke it using the syntax your integration uses: `/speckit.archive.run` for dot-command agents; `/speckit-archive-run` for hyphen and skills agents (Copilot, Cline, Forge, Junie among them); `$speckit-archive-run` for Codex or ZCode in skills mode; `/skill:speckit-archive-run` for Kimi.

**One feature per run.** There is no batch or range mode: `specs/001 thru specs/008` and `specs/00*` are rejected. Archive several features by running the command once per feature, in ascending order, so each run builds on the previous one.

You can optionally restrict the scope of the updates:
- `--spec-only` updates only `.specify/memory/spec.md`
- `--plan-only` updates only `.specify/memory/plan.md`
- `--changelog-only` updates only `.specify/memory/changelog.md`
- `--agent-only` updates only the agent context file(s)

Free-form text after the feature path is **guidance**, like in the core spec-kit commands:

```bash
/speckit.archive.run specs/007-invoice Pay extra attention to the entity model.
```

Guidance steers attention, emphasis, and report detail. It cannot add content sources, skip steps, change scope or IDs, or approve removals, and the report echoes it verbatim so every run stays auditable. Do not put feature paths (in any form) or leading bare feature references into guidance, because those are rejected as a second feature. A feature path is recognised by its shape, so **any** `specs/...` token in guidance is rejected, not only a numbered one; name a path in prose without the `specs/` prefix when guidance must mention it. The path-less form stays deliberately narrow, matching only a token that opens with three or more digits which then end it or meet a hyphen. So `2FA`, `3rd-party`, `24/7` and `v2` read as ordinary guidance, and so do measure-and-unit tokens such as `90-day`, `30-day` and `24-hour`. Three digits is the floor because no feature directory spec-kit creates has a shorter leading run. The bound this leaves: a token that does open with three or more digits and a hyphen, `100-day` or an ISO date such as `2026-08-21`, is still read as a feature reference when it sits beside `to`, `thru` or `through`, so write those in prose. Prose, punctuation, and numbers inside sentences are fine.

## Workflow

1.  **Resolve paths**: run `check-prerequisites` for the repository root, then take the feature directory from the path you passed.
2.  **Verify constitution compliance**: check that feature implementations do not violate the project's MUST rules.
3.  **Map the impact**: ask up to 5 clarifying questions before proceeding, including confirmation of any superseded requirements.
4.  **Archive the data**: consolidate entities, requirements, dependencies, and architecture notes into the main memory, and apply confirmed supersessions.
5.  **Report**: list the changed files and say what you should do next.
