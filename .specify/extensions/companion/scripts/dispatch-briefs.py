#!/usr/bin/env python3
"""Print the subagent briefs a step dispatches, and record each worker's check-in.

  dispatch-briefs.py --feature-dir <dir>                   one read-only reader per recorded `area:`
  dispatch-briefs.py --feature-dir <dir> --docs            one writer per Phase 1 design document
  dispatch-briefs.py --feature-dir <dir> --waves           workers for each Foundational wave of 4+ tasks
  dispatch-briefs.py --feature-dir <dir> --checkin <label> a dispatched worker says it started

The decision lives here, not in the prompt: prose that says "dispatch when…" gets
argued away by an unattended run, a printed brief does not. Two or more briefs
means dispatch them all; fewer means the step works inline. Check-ins land in the
spec's trace, where the doctor compares them with what was printed. Never fails
the host command. Stdlib only.
"""
from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE))

SCRIPT = ".specify/extensions/companion/scripts/dispatch-briefs.py"
#: Past this many, areas share a reader: specify often records single files as areas.
MAX_READERS = 4
MIN_READER_AREAS = 3
#: A Foundational wave this big goes to workers; smaller ones cost more to start than to build.
MIN_WAVE = 4
_FOUNDATIONAL = re.compile(r"^##\s+Phase\s+\d+\s*:\s*Foundational", re.IGNORECASE)
DOCS = {
    "data-model.md": "the entities this feature introduces or reshapes, with fields, relationships, "
                     "validation rules drawn from the requirements, and any state transitions",
    "contracts/": "the interface the feature exposes (API / CLI / schema, or a UI contract listing routes "
                  "and the identifiers a consumer or test codes against), copying every identifier from "
                  "the spec's Verbatim Constraints exactly. If the feature exposes no interface, write nothing "
                  "and say so",
}


def areas(ctx: dict) -> list:
    return [c.split(":", 1)[1].strip() for c in ctx.get("context") or []
            if isinstance(c, str) and c.startswith("area:")]


def spec_file(feature_dir: Path) -> str:
    from spec_context import feature_spec_path

    return str(feature_spec_path(feature_dir))


def checkin_line(feature_dir: Path, label: str) -> str:
    return f'python3 {SCRIPT} --feature-dir {feature_dir} --checkin "{label}"'


def reader_briefs(feature_dir: Path, ctx: dict) -> list:
    spec = spec_file(feature_dir)
    found = areas(ctx)
    groups = [found[i::MAX_READERS] for i in range(min(len(found), MAX_READERS))]
    return [(f"reader: {n}",
             f"Read-only. First run `{checkin_line(feature_dir, f'reader: {n}')}`. Then read the code in "
             f"{', '.join(f'`{a}`' for a in g)} for the feature in `{spec}`, starting from what the `context` entries in "
             f"`{feature_dir}/.spec-context.json` already name. Return a distilled finding: the pattern to "
             "copy, the concrete file paths, the conventions to match, and anything the code does that the "
             "spec does not account for. Never file contents.")
            for n, g in enumerate(groups, 1)]


def doc_briefs(feature_dir: Path, ctx: dict) -> list:
    if (ctx.get("size") or "normal") == "simple":
        return []
    spec = spec_file(feature_dir)
    return [(f"doc: {name}",
             f"First run `{checkin_line(feature_dir, f'doc: {name}')}`. Then write "
             f"`{feature_dir}/{name}`: {what}. Work from `{spec}`, `{feature_dir}/plan.md` and "
             f"`{feature_dir}/research.md`. Return only the path you wrote.")
            for name, what in DOCS.items()]


def _waves(tasks_text: str) -> list:
    """Foundational waves as lists of (task id, done). A join, a `###` block or a `Wave` header ends one."""
    from task_sync import COMPLETED_TASK_RE, PENDING_TASK_RE, prose_lines

    waves, current, inside = [], [], False
    for line in prose_lines(tasks_text):
        if line.startswith("## "):
            if inside:
                break
            inside = bool(_FOUNDATIONAL.match(line))
            continue
        head = line.strip()
        if inside and head.startswith(("**⟶", "### ", "**Wave")):
            waves.append(current)
            current = []
        elif inside and (m := PENDING_TASK_RE.match(line) or COMPLETED_TASK_RE.match(line)):
            current.append((m.group(1), m.re is COMPLETED_TASK_RE))
    waves.append(current)
    return [w for w in waves if w]


def foundational_waves(tasks_text: str) -> list:
    """Every Foundational wave as a list of task ids, finished or not."""
    return [[tid for tid, _ in w] for w in _waves(tasks_text)]


def next_wave(feature_dir: Path):
    """The first Foundational wave with unfinished tasks, as (number, pending ids), or None."""
    try:
        text = (feature_dir / "tasks.md").read_text(encoding="utf-8")
    except (OSError, ValueError):
        return None
    for w, wave in enumerate(_waves(text), 1):
        pending = [tid for tid, done in wave if not done]
        if pending:
            return w, pending
    return None


def wave_briefs(feature_dir: Path, w: int, tasks: list) -> list:
    append = (f"python3 .specify/extensions/companion/scripts/write-context.py --feature-dir {feature_dir} "
              '--task <TaskID> --kind complete --by ai --did "<one line>" --files "<files>" --append')
    return [(f"wave: {w}.{n}",
             f"First run `{checkin_line(feature_dir, f'wave: {w}.{n}')}`. Then build {', '.join(tasks[n - 1::MAX_READERS])} "
             f"from `{feature_dir}/tasks.md` (Foundational wave {w}), reading the spec and `plan.md` as you need. "
             "Run the tests for each task's files, then append its finish with "
             f"`{append}`; never append a task whose tests fail, and never fold. Return what you built, the files you touched, the tests you ran and any task that failed, "
             "never file contents.")
            for n in range(1, min(len(tasks), MAX_READERS) + 1)]


def _offer(feature_dir: Path, kind: str) -> None:
    import run_trace
    run_trace.record("dispatch-briefs", "dispatch-offer", True, ms=0,
                     feature_dir=feature_dir, spec=feature_dir.name, files=[kind])


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--feature-dir", required=True, type=Path)
    ap.add_argument("--docs", action="store_true")
    ap.add_argument("--waves", action="store_true")
    ap.add_argument("--checkin")
    args = ap.parse_args()
    feature_dir = args.feature_dir

    if args.checkin:
        import run_trace
        run_trace.record("dispatch-briefs", "dispatch-checkin", True, ms=0,
                         feature_dir=feature_dir, spec=feature_dir.name, files=[args.checkin])
        return 0

    try:
        ctx = json.loads((feature_dir / ".spec-context.json").read_text(encoding="utf-8"))
    except (OSError, ValueError):
        ctx = {}
    if args.waves:
        from task_sync import materialize_log
        materialize_log(feature_dir, by="ai", quiet=True)
        try:
            has_phase = any(_FOUNDATIONAL.match(l) for l in (feature_dir / "tasks.md").read_text(encoding="utf-8").splitlines())
        except (OSError, ValueError):
            has_phase = False
        if not has_phase:
            print("No `## Phase N: Foundational` heading in tasks.md: build its Foundational tasks yourself.")
            return 0
        found = next_wave(feature_dir)
        if found is None:
            print("Foundational is done: no wave has unfinished tasks.")
            return 0
        w, tasks = found
        if len(tasks) < MIN_WAVE:
            print(f"Build Foundational wave {w} yourself: {', '.join(tasks)}. Run --waves again when they are done.")
            return 0
        _offer(feature_dir, f"waves:{w}:{min(len(tasks), MAX_READERS)}")
        print(f"Dispatch Foundational wave {w}'s {min(len(tasks), MAX_READERS)} workers now, all in ONE message, each "
              "brief as written. Start nothing after this wave until every one returns, then run --waves again for "
              "the next wave. If you have no subagent tool, build these tasks yourself instead.\n")
        for label, brief in wave_briefs(feature_dir, w, tasks):
            print(f"=== {label} ===\n{brief}\n")
        return 0
    briefs = doc_briefs(feature_dir, ctx) if args.docs else reader_briefs(feature_dir, ctx)
    what = "design docs" if args.docs else "readers"

    # Specify already read the areas it recorded; readers pay a cold start each, so two areas read inline.
    floor = 2 if args.docs else MIN_READER_AREAS
    if len(briefs) < floor:
        why = "simple size keeps at most one" if args.docs else f"{len(briefs)} area(s) recorded, read inline"
        print(f"Working inline: {why}. No {what} to dispatch.")
        return 0

    _offer(feature_dir, "docs" if args.docs else "readers")
    print(f"Dispatch these {len(briefs)} {what} now, all in ONE message, each brief as written. "
          "If you have no subagent tool, do each yourself instead.\n")
    for label, brief in briefs:
        print(f"=== {label} ===\n{brief}\n")
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as exc:  # noqa: BLE001 — a dispatch helper must never fail the step
        print(f"[dispatch-briefs] skipped: {exc}", file=sys.stderr)
        sys.exit(0)
