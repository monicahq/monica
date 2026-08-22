---
description: "Report living-spec requirement→test coverage — per requirement, whether its coverage tier maps a test (opt-in, read-only, never halts)"
---

# Spec Coverage

Show, for each living-spec capability, which of its requirements have a test mapped in the capability's coverage-tier sibling (the `*.coverage.md` next to its spec — `spec.coverage.md` for a centralized `capabilities/<name>/spec.md`, or `<base>.coverage.md` for a colocated spec) and which are still uncovered. A living spec's requirements file says **what** the area must do; its coverage tier says **which test proves each one**. This command reads both and reports the gap. **Read-only** — it never edits anything — and it **never fails** (always exits success). It's the conformance on-ramp, a signal you act on, not a gate that blocks you (the same contract as `/speckit.companion.living-drift`).

This is **opt-in**. With living specs disabled (or no config), it reports nothing and exits clean. A capability that ships only a `.spec.md` with no `.coverage.md` sibling reports every requirement uncovered — never an error.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip:
  `[companion] Warning: python3 not detected; skipped coverage`.
  Do not fail the host command.

## Execution

Run the coverage checker from the repository root:

```bash
python3 .specify/extensions/companion/scripts/check-coverage.py
```

The script reads the capability registry (`living-specs.yml`), reuses the
resolver for the capability and tier paths, extracts each requirement id
(`FR-NNN` / `NFR-NNN`) from the capability's `.spec.md`, and looks each id up in
its `.coverage.md` map. A requirement is **covered** when its id appears in the
coverage file on a line that also names at least one test (a `.test.` / `.spec.`
path, a `tests/...` reference, or a `file::TestCase` nodeid); otherwise it's
**uncovered**.

Restrict to one capability by name, or get a machine-readable object:

```bash
python3 .specify/extensions/companion/scripts/check-coverage.py --capability billing
python3 .specify/extensions/companion/scripts/check-coverage.py --json
```

## What to do with the report

Coverage is a signal, not an error. For each uncovered requirement, either add a
test and map it in the capability's `.coverage.md`, or accept the gap for now. The
command never blocks the pipeline on its own.
