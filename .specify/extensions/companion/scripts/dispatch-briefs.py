#!/usr/bin/env python3
"""Print the subagent briefs a step dispatches, and record each worker's check-in.

  dispatch-briefs.py --feature-dir <dir>                   one read-only reader per recorded `area:`
  dispatch-briefs.py --feature-dir <dir> --docs            one writer per Phase 1 design document
  dispatch-briefs.py --feature-dir <dir> --waves           workers for each Foundational wave of 4+ tasks over 5+ files
  dispatch-briefs.py --feature-dir <dir> --checkin <label> a dispatched worker started (a reader's is recorded by the step)

Briefs carry absolute paths and name the checkout, so a worker started elsewhere works in this one.

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
#: A Foundational wave goes to workers only this big, in tasks and in files, and each worker
#: gets two tasks at least: below that a worker's startup costs more than the work it is handed.
MIN_WAVE = 4
MIN_WAVE_FILES = 5
MIN_TASKS_PER_WORKER = 2
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


def project_root(feature_dir: Path) -> Path:
    """The checkout the spec lives in, absolute, so a worker started from another checkout reads this one."""
    import subprocess

    here = Path(feature_dir).resolve()
    try:
        top = subprocess.run(["git", "-C", str(here), "rev-parse", "--show-toplevel"],
                             capture_output=True, text=True, check=True, timeout=10).stdout.strip()
        if top:
            return Path(top).resolve()
    except Exception:  # noqa: BLE001
        pass
    for folder in here.parents:
        if (folder / ".specify").is_dir():
            return folder
    return here.parent.parent


def checkin_line(feature_dir: Path, label: str) -> str:
    feature_dir = Path(feature_dir).resolve()
    return f'python3 {project_root(feature_dir) / SCRIPT} --feature-dir {feature_dir} --checkin "{label}"'


def _where(feature_dir: Path) -> str:
    return f"Work only in `{project_root(feature_dir)}`; every path below is inside it. "


def reader_briefs(feature_dir: Path, ctx: dict) -> list:
    feature_dir = Path(feature_dir).resolve()
    spec = spec_file(feature_dir)
    found = areas(ctx)
    groups = [found[i::MAX_READERS] for i in range(min(len(found), MAX_READERS))]
    return [(f"reader: {n}",
             f"Read-only: run nothing that writes. {_where(feature_dir)}Read the code in "
             f"{', '.join(f'`{a}`' for a in g)} for the feature in `{spec}`, starting from what the `context` entries in "
             f"`{feature_dir}/.spec-context.json` already name. Return a distilled finding: the pattern to "
             "copy, the concrete file paths, the conventions to match, and anything the code does that the "
             "spec does not account for. Never file contents.")
            for n, g in enumerate(groups, 1)]


def doc_briefs(feature_dir: Path, ctx: dict) -> list:
    if (ctx.get("size") or "normal") == "simple":
        return []
    feature_dir = Path(feature_dir).resolve()
    spec = spec_file(feature_dir)
    return [(f"doc: {name}",
             f"{_where(feature_dir)}First run `{checkin_line(feature_dir, f'doc: {name}')}`. Then write "
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


_TASK_LINE = re.compile(r"^\s*[-*+]\s*\[[ xX]\]\s*(?:\*\*)?(T\d+)")
_PATH = re.compile(r"(?<![\w/.-])(?:[\w.-]+/)*[\w-]+\.[A-Za-z][\w]{0,7}(?![\w/])|(?<![\w/.-])[\w.-]+(?:/[\w.-]+)+/?")


def task_files(tasks_text: str, ids: list) -> set:
    """The distinct file paths the given tasks name; a task that names none counts as one file of its own."""
    wanted, files = set(ids), set()
    for line in tasks_text.splitlines():
        m = _TASK_LINE.match(line)
        if not m or m.group(1) not in wanted:
            continue
        found = {p.rstrip("/") for p in _PATH.findall(line[m.end():]) if not p.startswith(("e.g", "i.e"))}
        files |= found or {m.group(1)}
    return files


def wave_workers(tasks_text: str, tasks: list) -> int:
    """How many workers a wave earns: none below MIN_WAVE tasks or MIN_WAVE_FILES files, and two tasks each at least."""
    if len(tasks) < MIN_WAVE or len(task_files(tasks_text, tasks)) < MIN_WAVE_FILES:
        return 0
    return min(len(tasks) // MIN_TASKS_PER_WORKER, MAX_READERS)


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


def wave_briefs(feature_dir: Path, w: int, tasks: list, workers: int | None = None) -> list:
    feature_dir = Path(feature_dir).resolve()
    workers = workers or min(len(tasks), MAX_READERS)
    writer = project_root(feature_dir) / ".specify/extensions/companion/scripts/write-context.py"
    append = (f"python3 {writer} --feature-dir {feature_dir} "
              '--task <TaskID> --kind complete --by ai --did "<one line>" --files "<files>" --append')
    return [(f"wave: {w}.{n}",
             f"{_where(feature_dir)}First run `{checkin_line(feature_dir, f'wave: {w}.{n}')}`. Then build {', '.join(tasks[n - 1::workers])} "
             f"from `{feature_dir}/tasks.md` (Foundational wave {w}), reading the spec and `plan.md` as you need. "
             "Run the tests for each task's files, then append its finish with "
             f"`{append}`; never append a task whose tests fail, and never fold. Return what you built, the files you touched, the tests you ran and any task that failed, "
             "never file contents.")
            for n in range(1, workers + 1)]


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
        workers = wave_workers((feature_dir / "tasks.md").read_text(encoding="utf-8"), tasks)
        if workers < 2:
            print(f"Build Foundational wave {w} yourself: {', '.join(tasks)}. Run --waves again when they are done.")
            return 0
        _offer(feature_dir, f"waves:{w}:{workers}")
        print(f"Dispatch Foundational wave {w}'s {workers} workers now, all in ONE message, each "
              "brief as written. Start nothing after this wave until every one returns, then run --waves again for "
              "the next wave. If you have no subagent tool, build these tasks yourself instead.\n")
        for label, brief in wave_briefs(feature_dir, w, tasks, workers):
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
    if not args.docs:
        # A reader runs nothing that writes, so the step records its check-in when the finding comes back.
        print("As each reader returns its finding, record it:")
        for label, _brief in briefs:
            print(f"  {checkin_line(feature_dir, label)}")
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as exc:  # noqa: BLE001 — a dispatch helper must never fail the step
        print(f"[dispatch-briefs] skipped: {exc}", file=sys.stderr)
        sys.exit(0)
