---
description: "Brownfield adoption wizard — draft living specs for the code areas you name, central or colocated, and register them (opt-in, surface-first, [DRAFT])"
---

# Adopt a Code Area into a Living Spec

Bring an existing code area under living specs without hand-writing one from scratch. You point at **one** area; the assistant reads its surface, proposes a small tree of capabilities for **just that area**, drafts a living spec for each from what the code already exposes, and — on your confirmation — registers the capability so the rest of the Living Specs pipeline starts recognizing it.

This is **opt-in** and **incremental**. You run it deliberately for the areas you care about. It never scans or rewrites the whole repository on its own, and it changes no other command's behavior. Because the drafts are read **surface-first** (exports, routes, props, signatures — not a deep behavioral read), every draft is clearly marked as a starting point, not verified ground truth.

**Surface-first constrains how much you may claim, not what you may write about.** Reading only the surface limits your *confidence*, so the drafts are marked `[DRAFT]` and low-confidence items are flagged. It does not mean the requirements should describe the surface. A requirement that restates a function signature is not a specification — see step 2.

## Input

```text
$ARGUMENTS
```

The argument is the code area (or areas) to adopt — a directory (e.g. `src/billing/`), a small set of related files, or several directories.

If the argument is **empty**, do not fall back to scanning the whole repo. List the plausible top-level areas, describe each in a line, and ask which to adopt. Offer adopting several at once as an explicit choice — the developer picking "all of them" is a legitimate answer, not something to argue with.

If the argument names **several areas**, adopt them in one run: propose the full capability tree across all of them, and bring the whole tree to the single review gate in step 1. Do not silently expand beyond what was named.

## What to do

### 1. Scope the area and propose capabilities

List the files under the named area. From their **surface only** — exported symbols, route registrations, component props, public function/class signatures, config keys — propose a **small tree of capabilities for just this area**. Most areas are one capability; a clearly layered area (e.g. a parent module with a distinct nested sub-area) may warrant a leaf capability plus its parent, mirroring the resolver's most-specific-first model. Never propose a capability outside the named area.

For each proposed capability, derive:
- a **name** (a short slug for the area, e.g. `billing`),
- a **match** glob from the area path (e.g. adopting `src/billing/` → `["src/billing/**"]`; a nested leaf → `["src/billing/invoices/**"]`),
- a **spec** path, which depends on the storage layout chosen below.

#### Choose the storage layout

Living specs support two layouts, and the choice is the developer's:

- **central** — every spec under `capabilities/<name>/spec.md`. One folder holds the whole record; easy to read end to end, and the spec stays put when code moves.
- **colocated** — the spec sits next to the code it describes, at `<area root>/<name>.spec.md`. Ownership is obvious, the spec travels with the code in a move, and it shows up in the same folder a developer already has open.

If the invocation named a layout, use it. **Otherwise ask before proposing anything**, since the layout determines the spec paths shown at the review gate. Offer central, colocated, or per-capability, and say plainly that it can be changed later.

Deriving a **colocated** path: take the capability's match glob, strip the trailing `/**`, and place `<name>.spec.md` in that directory — `src/features/spec-viewer/**` → `src/features/spec-viewer/spec-viewer.spec.md`.

Two consequences to state out loud when proposing colocated paths, because both surprise people:

1. **The filename stem becomes the capability's display name.** A capability named `speckit-extension-capture` colocated as `capture.spec.md` shows as `capture` in the sidebar. Either keep the stem equal to the name, or tell the developer the name it will display as.
2. **A capability whose match globs span unrelated directories has no obvious home.** If a capability matches `speckit-extension/commands/**` *and* `speckit-extension/nodes/**`, there is no single area root. Propose the shallowest common directory, and if there isn't a sensible one, say so and propose central for that capability specifically — a mix is fine.

Show the proposed capability tree to the developer — names, match globs, and the resolved spec path for each — and pause for confirmation before drafting and registering. This is the one review gate in this command.

### 2. Draft each living spec — surface-first, honestly marked

For each confirmed capability, draft the spec at the path confirmed at the review gate — `capabilities/<name>/spec.md` for central, `<area root>/<name>.spec.md` for colocated. Read the area's files; if a file is unreadable (binary, permission) or too large to read within a reasonable budget, **do not silently skip it** — record its path for the `## Uncovered` section.

A living spec uses the **requirement-and-scenario shape** — a named requirement heading with at least one scenario under it. This is not a stylistic preference: fold-back identifies a requirement by its exact `###` heading text, so a living spec written any other way cannot be updated by the pipeline. Numbered `FR-001` bullets are the *feature*-spec format and must not be used here.

The exact required structure:

1. **Title** — `# <Capability> — Living Spec`, matching the title the fold scaffolds when it creates a capability spec itself (`_initial_living_spec` in `write-context.py`).
2. **Draft banner** — immediately under the title, a line marking the whole spec a draft and stating the default confidence: `> [DRAFT] Surface-first draft from existing code — every requirement is observed from the code surface unless tagged otherwise. Review before trusting.`

   Keep `[DRAFT]` as the first token on that line. The viewer detects it there and badges the spec accordingly.
3. **`## Purpose`** — one or two sentences on **why this capability exists** and what would go wrong without it. Write this first, before any requirement. It is the anchor that keeps the rest of the document about intent; a spec without it drifts into inventorying the code.
4. **`## Requirements`** — then, under it, one `### <requirement>` per requirement.

   **Never use `###` for section groupings.** Do not organize requirements under headings like `### Public surface` or `### Layout primitives` — those mirror the code's structure, and fold-back would read them as requirement names and overwrite whole groups at once. Requirements are the only thing at `###`.

   Each requirement is:

   - a **heading** naming the behavior — `### Pages delegate their chrome to a layout primitive`,
   - a sentence or two of **normative prose** using MUST/SHALL/SHOULD, saying what is guaranteed and why,
   - one or more **`#### Scenario: <short name>`** blocks with `- **WHEN** …` / `- **THEN** …` (and `- **AND** …`) bullets giving a concrete, checkable case.

   Write about behavior, contracts, and intent. Do **not** transcribe the surface you read. A requirement must never be a restatement of:

   - a prop list or function parameter list,
   - an export inventory,
   - literal class names, CSS variable names, or design-token values (`max-w-4xl`, `h-14`, `--surface`),
   - a type signature, or an enum's members.

   Those are the code. They change on every routine edit, they generate drift noise, and a reader gets them faster from the source file.

   Ask of every requirement: *would this still be true and useful after a reasonable refactor?* If renaming a prop would falsify it, rewrite it one level up.

   ```markdown
   ✗ Wrong — the feature-spec format, transcribing the surface:

   ### Layout primitives

   - **FR-004** `ContentPage` MUST accept `title` (required), `description`,
     `breadcrumbItems`, `actions`, `children`, and `width`.
   - **FR-005** `ContentPage` MUST support exactly three width variants —
     `medium`, `wide`, `full` — mapping to `max-w-4xl` and `max-w-none`.

   ✓ Right — named requirement, intent-level prose, concrete scenarios:

   ### Pages delegate their chrome to a layout primitive

   Pages SHALL compose a shell-provided layout primitive rather than
   hand-rolling header, scroll, or width behavior, so responsive behavior
   changes in one place instead of per page.

   #### Scenario: a reading page is added
   - **WHEN** a page presents scrollable content
   - **THEN** it composes the content-page primitive
   - **AND** the page header stays fixed while the body scrolls

   #### Scenario: a workbench page is added
   - **WHEN** a page hosts a board or editor that scrolls internally
   - **THEN** the shell surrenders scroll control to the page body
   ```

   Note what the ✓ version dropped: the prop names, the class values, the component identifier. Note what it kept: the contract. Four transcribed requirements collapsed into one real one.

   Name concrete symbols only when the symbol *is* the contract — a public entry point, a route path, a documented config key. Cite it as context, not as the requirement's content.

   Aim for the requirements a competent engineer would need to rebuild this area correctly, not a list of everything it currently contains. Fewer, denser requirements beat exhaustive transcription; if an area yields thirty requirements, you are almost certainly describing the code.

5. **Confidence** — the whole document is a surface-first draft, so `observed` is the **default and is stated once in the draft banner**. Do not tag individual requirements `[observed]`; a tag on every line carries no information.

   Tag only the exceptions, inline in the requirement's prose: `[inferred]` for a requirement extrapolated beyond what the surface shows (likely intent you could not confirm). If you find yourself tagging nearly everything `[inferred]`, the draft is guesswork — say so in your report rather than shipping it quietly.
6. **`[NEEDS CLARIFICATION: …]`** — append this marker inline to any requirement you are genuinely unsure about (an ambiguous name, an inferred behavior you could not confirm). Use it sparingly — it flags the low-confidence items for a human to resolve, and step 3 walks them.
7. **`## Uncovered`** — a section listing every file you could **not** read (unreadable or over budget), one per line, so the draft's coverage is honest. If you read everything, write `_None — every file in the area was read._`

Keep the whole spec `[DRAFT]` — you are proposing a record drawn from the surface, not certifying behavior.

### 3. Walk the clarifications

If any drafted requirement carries `[NEEDS CLARIFICATION: …]`, do not leave them sitting in the file. Markers nobody returns to are the same as no markers at all, and some of what surfaces here is real — an inconsistency between two modules, a value the code never actually produces, an assumption worth making deliberately.

Collect them across every capability drafted in this run and walk them with the developer, one at a time. For each: show the requirement, state plainly what you could not determine, and offer

- **resolve** — the developer answers; rewrite the requirement with the answer folded in and strip the marker,
- **keep** — leave the marker in place for later,
- **drop** — the requirement was not real; remove it.

Offer skipping the rest at any point, and treat an interrupted walk as normal — everything unresolved simply keeps its marker.

If a clarification reveals something that is not an ambiguity but a **defect** (a mismatch between two parts of the code, an unreachable branch, a value that cannot occur), say so explicitly in the report. That finding is worth more than the spec line that surfaced it.

Do not invent answers to close markers out. An unresolved marker is honest; a fabricated resolution is a lie the record then carries forward.

### 4. Register the confirmed capability

For each confirmed capability, register it so the shipped resolver recognizes it. Use the deterministic registry-append helper — it appends one capability to the project's registry, `living-specs.yml`, idempotently, preserves every existing capability, and refuses to write a config it cannot parse:

```bash
python3 .specify/extensions/companion/scripts/register-capability.py --name <name> --match "<glob>" [--match "<glob>" …] [--exclude "<glob>"] [--spec <path>]
```

**Pass `--spec` for every colocated capability**, with the same path you drafted the spec to. Omit it for central ones — the helper emits `spec` only when it differs from the centralized default, which keeps the config terse.

The registry lives at the project root, deliberately outside `.specify/`, so a routine `git restore … .specify/` can never wipe it. Commit `living-specs.yml` along with the specs it registers. If this project still keeps its capabilities in the older `.specify/companion.yml`, the helper moves them across on its first write and says so — nothing is lost and nothing needs doing by hand.

Register a colocated capability only *after* its spec file is on disk at that path. The two must agree: the resolver raises `capability "<name>" is colocated but has no resolvable spec path` if a capability is registered with a `spec` path that isn't there, and the whole living-specs config fails to load, not just that capability.

This is **incremental** — it appends one capability per confirmed proposal; it never bootstraps the whole repo and never rewrites unrelated capabilities. Re-running it for an already-registered name is a safe no-op. After it appends, confirm the resolver recognizes the area:

```bash
python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --changed <a file under the area> --json
```

The new capability should appear in `matched[]`.

If you have no terminal tool, report the exact `register-capability.py` command you would run for each capability (with the resolved name, match, and spec) so the developer can run it, and continue.

### 5. Report

Summarize, in plain language: which capabilities you proposed and registered, the storage layout used (and any capability that deviated from it), where each living spec was drafted, how many requirements each carries, how many were tagged `[inferred]`, how many clarifications you walked and how they landed (resolved / kept / dropped), any **defects** the clarification walk turned up, and what landed under `## Uncovered`. Make clear the drafts are `[DRAFT]` starting points to review, not finished specs.

## Boundaries

- **Opt-in and isolated.** This command changes no existing command's behavior and touches no spec's lifecycle. It only creates `capabilities/<name>/spec.md` files and appends to the capability registry.
- **The layout is the developer's call.** Never assume central because it is the default. Ask when it was not specified, and show the resulting spec paths before writing anything.
- **Only what was named.** Adopt the areas the developer named or chose, and nothing else. Several areas in one run is fine; silently widening past the agreed scope is not.
- **Specify, don't transcribe.** A requirement that a prop rename would falsify is a bug in the draft, not a detail. Fewer, durable requirements beat an exhaustive inventory of the code.
- **Write the shape the pipeline can update.** Named `###` requirements with scenarios, never numbered `FR-` bullets, and never `###` section groupings. Fold-back matches requirements by heading text; a spec in any other shape is one the pipeline can silently fail to update.
- **Honest by construction.** The `[inferred]` tag, clarification markers, and the `## Uncovered` section are required — a surface draft that hid its blind spots would be worse than no draft.
- **Never fail the host.** A missing resolver, missing helper, or unparseable config is reported and skipped, not crashed through.
