---
description: "Move a living spec between central and colocated storage — file, tiers, and registry together (opt-in, reversible)"
---

# Relocate a Living Spec

Move a capability's living spec between the two storage layouts, without hand-editing config or moving files yourself.

- **central** — `capabilities/<capability>/<name>.spec.md`. One folder per capability, holding one spec or several granular ones. A legacy `capabilities/<name>/spec.md` still reads, and moving it centrally renames it to the new shape.
- **colocated** — `<area root>/<name>.spec.md`, sitting next to the code it describes.

Neither is more correct. Colocating makes ownership obvious and the spec travels with the code when it moves; centralizing keeps the whole record readable in one place and survives code being reorganized. Repos commonly end up mixed, and that is a fine outcome.

The move is reversible and loses nothing. A requirement's identity is its `###` heading text *inside* the spec, so nothing outside the file points at its location — there is no id to renumber, no link to fix, no index to rebuild.

## Input

```text
$ARGUMENTS
```

The argument names what to move and where — a capability name and a target layout (`billing to colocated`), or a repo-wide instruction (`everything to central`).

If either is missing, ask. List the capabilities with their current layout so the developer can see what they are choosing between, and never guess at a target layout.

## What to do

### 1. Show what will move, and pause

Resolve the current layout of each named capability and the exact path each spec will land at. Show that before touching anything:

```text
billing        capabilities/billing/billing.spec.md  →  src/billing/billing.spec.md
billing-tax    capabilities/billing/tax.spec.md  →  src/billing/tax/billing-tax.spec.md
```

Add one line only where something would surprise them: a display name that changes (colocated names come from the file stem), a capability that stays central because its globs cover several folders or the whole area, and any `.rules.md` or `.coverage.md` that travels with the spec.

Then ask. One question, one word back. This is the only pause.

### 2. Run the relocation

Use the deterministic helper — it moves the spec and every tier sibling, rewrites the registry, and keeps the two in step:

```bash
python3 .specify/extensions/companion/scripts/relocate-capability.py --name <name> --to <central|colocated> [--spec <path>] [--root .]
```

For a repo-wide move, `--all --to <layout>`.

Pass `--spec` when the derived path was wrong or ambiguous, using the path confirmed at the gate.

Do **not** move files yourself and do **not** hand-edit `living-specs.yml`. The file and the registry must change together: the resolver raises `capability "<name>" is colocated but has no resolvable spec path` and fails the **entire** living-specs config — not just that capability — if they disagree. The helper is atomic and rolls back on failure; a hand-rolled move is not.

Re-running against a capability already in the target layout is a safe no-op.

### 3. Verify

Confirm the resolver agrees with what you just did:

```bash
python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --root . --all --json
```

Each moved capability should report the new `spec` path and the expected location. Then resolve a real file from the capability's area and check it still matches — most-specific-first ordering must be unchanged, since relocation moves the spec, not the globs:

```bash
python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --root . --changed <a file under the area> --json
```

If a capability vanished from the listing, or the config now fails to load, the move and the registry are out of step — report it plainly rather than patching around it.

### 4. Report

Say which capabilities moved and where, which were already in the target layout and skipped, which were left alone because their area root was ambiguous, any display name that changed, and whether the resolver confirmed the result. If tier files moved, list them.

## Boundaries

- **Move, never rewrite.** Spec content is not touched. If a spec needs reshaping, that is a separate job.
- **The registry and the disk move together.** Never leave them disagreeing; a partial move breaks the whole config, not one capability.
- **Only what was named.** Do not opportunistically relocate a capability the developer did not ask about, even when its layout looks inconsistent with the rest.
- **Reversible by construction.** Every move can be run in the opposite direction, and re-running is a no-op.
- **Never fail the host.** A missing capability, an unparseable config, or an ambiguous area root is reported and skipped, not crashed through.
