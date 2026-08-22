#!/usr/bin/env python3
"""Task markers, checkbox writing, and the per-task implement journal.

Reads and flips the `- [ ]` / `- [x]` markers in tasks.md, folds a task's finish
into the context (live, or replayed from the append log), closes the implement
step when every task is both checked and journaled, and backfills the journal
from tasks.md as a backstop.

Stdlib only."""

from __future__ import annotations

import json
import os
import re
import sys
from pathlib import Path

from spec_context import (
    CROSS_STEP_TERMINAL,
    _git_branch,
    _has_complete,
    _journaled_tasks,
    _now_iso,
    _open_ctx_or_none,
    _repo_root_for,
    append_complete,
    atomic_write,
    canonical_log,
    commit_log,
    fill_required,
    read_ctx,
)

# `**` is optional: matches the turbo/companion bold form `- [x] **T001**` AND the
# standard tasks-template plain form `- [x] T001 …`. A `T\d+` is still required right
# after the checkbox, so non-task checkboxes never false-match.
COMPLETED_TASK_RE = re.compile(r"^\s*[-*]\s*\[[xX]\]\s*(?:\*\*)?(T\d+)")
PENDING_TASK_RE = re.compile(r"^\s*[-*]\s*\[\s\]\s*(?:\*\*)?(T\d+)")


def parse_task_markers(tasks_md: Path) -> tuple[list[str], list[str]]:
    """Return (all_task_ids, completed_task_ids) in document order from tasks.md."""
    all_ids: list[str] = []
    done_ids: list[str] = []
    try:
        for line in tasks_md.read_text(encoding="utf-8").splitlines():
            m = COMPLETED_TASK_RE.match(line)
            if m:
                all_ids.append(m.group(1))
                done_ids.append(m.group(1))
                continue
            m = PENDING_TASK_RE.match(line)
            if m:
                all_ids.append(m.group(1))
    except OSError:
        pass
    return all_ids, done_ids


def _mark_tasks_done(tasks_md: Path, ids: set) -> None:
    """Flip `- [ ] **<id>**` → `- [x]` in tasks.md for every journaled task id.

    The script owns the checkboxes so the model (and any subagent) never has to
    edit the shared tasks.md — it only appends its finish to the event log, and
    this single writer derives the checkboxes from that log. Targeted: only a
    *pending* line whose captured id is in `ids` is flipped (idempotent — an
    already-checked line never matches PENDING_TASK_RE), and only that line's
    first `[ ]` is rewritten, so surrounding text is untouched."""
    if not ids or not tasks_md.is_file():
        return
    try:
        lines = tasks_md.read_text(encoding="utf-8").splitlines(keepends=True)
    except OSError:
        return
    changed = False
    for i, line in enumerate(lines):
        m = PENDING_TASK_RE.match(line)
        if m and m.group(1) in ids:
            lines[i] = line.replace("[ ]", "[x]", 1)
            changed = True
    if not changed:
        return
    tmp = tasks_md.with_suffix(tasks_md.suffix + ".tmp")
    try:
        tmp.write_text("".join(lines), encoding="utf-8")
        os.replace(tmp, tasks_md)
    except OSError:
        try:
            tmp.unlink(missing_ok=True)
        except OSError:
            pass


def _feature_tasks_at_100(feature_dir: Path) -> bool:
    """True when feature_dir/tasks.md exists, has markers, and every one is checked."""
    tasks_md = feature_dir / "tasks.md"
    if not tasks_md.is_file():
        return False
    return _tasks_at_100(parse_task_markers(tasks_md))


def _gc_events_log(feature_dir: Path) -> None:
    """Remove `.spec-context.events.jsonl` at the terminal `completed` transition —
    the one state after which CROSS_STEP_TERMINAL blocks every further append, so the
    file can't be recreated and a re-run of the spec dir can't re-fold stale lines."""
    try:
        (feature_dir / ".spec-context.events.jsonl").unlink(missing_ok=True)
    except OSError:
        pass


def _upsert_task_summary(
    ctx: dict, task_id: str, did: str | None, files: list[str] | None,
    status: str = "DONE",
) -> None:
    """Upsert `ctx["task_summaries"][task_id]` to the shape the Activity panel reads.

    The Tasks card (`TasksCard.tsx`, fed by `stateDerivation.ts`
    `pickRecord('task_summaries')`) keys the map by task id and reads
    `TaskSummary = { status; did?; files?; concerns? }`. We write exactly that shape
    so a script-journaled task shows up with no hand-authored `.spec-context.json` edit
    — this is the field that was silently absent on turbo runs (it used to depend on a
    skippable AI edit). Idempotent and non-destructive: re-journaling updates the single
    keyed entry, never a duplicate key, and other tasks' summaries are preserved.
    Empty `did`/`files` are omitted so the entry stays minimal but still renders the row.
    """
    summaries = ctx.get("task_summaries")
    if not isinstance(summaries, dict):
        summaries = {}
    existing = summaries.get(task_id)
    # Merge onto the existing entry rather than replacing it: a re-journal must
    # preserve previously-recorded fields (incl. hand-authored `concerns`) and
    # must NOT erase prior `did`/`files` when those flags are omitted this time.
    # Only overwrite a field when a new non-empty value is supplied (backfill).
    entry: dict = dict(existing) if isinstance(existing, dict) else {}
    entry["status"] = status
    if did:
        entry["did"] = did
    if files:
        entry["files"] = files
    summaries[task_id] = entry
    ctx["task_summaries"] = summaries


def _tasks_at_100(markers: tuple[list[str], list[str]]) -> bool:
    """100% verdict from already-parsed `(all_ids, done_ids)` — per-occurrence length
    equality, not set subset (a duplicate id with one marker unchecked isn't 100%)."""
    all_ids, done_ids = markers
    return bool(all_ids) and len(done_ids) == len(all_ids)


def _fold_task_finish(
    ctx: dict, log: list, feature_dir: Path, task_id: str, by: str,
    did: str | None, files: list[str] | None, at: str,
    markers: tuple[list[str], list[str]],
) -> None:
    """Fold one task's finish into ctx+log in place (no I/O). Shared by the live
    read-modify-write path and the append-log materializer, so both produce an
    identical `history` entry and `task_summaries` row. Idempotent on (implement,
    task_id); stamps the history entry with the supplied `at` so a materialized
    line keeps its own real finish time, not the fold time. `markers` is the caller's
    single tasks.md parse, threaded through so the file isn't re-read per task."""
    ctx["currentStep"] = "implement"
    ctx["currentTask"] = task_id
    # At 100% tasks land at `implemented`, not `implementing` — re-asserting `implementing` was the race that left a done spec unmarkable.
    if ctx.get("status") not in ("implemented", "completed", "archived"):
        ctx["status"] = "implemented" if _tasks_at_100(markers) else "implementing"
    append_complete(log, "implement", task=task_id, by=by, at=at)
    _upsert_task_summary(ctx, task_id, did, files)


def _maybe_close_implement(
    ctx: dict, log: list, feature_dir: Path, by: str,
    markers: tuple[list[str], list[str]],
) -> None:
    """Close the implement step once tasks.md is 100% AND every task has a journaled
    finish — never on one signal alone, so a journaled-but-unchecked task can't close
    the step while status is still implementing. `markers` is the caller's single
    tasks.md parse (empty when the file is absent), threaded through to avoid a re-read."""
    all_ids = markers[0]
    distinct = list(dict.fromkeys(all_ids))
    all_done = (
        bool(all_ids)
        and len(distinct) == len(all_ids)
        and _tasks_at_100(markers)
        and set(distinct) <= _journaled_tasks(log)
    )
    if all_done and not _has_complete(log, "implement", None):
        append_complete(log, "implement", by=by, at=_now_iso())
        # Keep status consistent with the closed step. The fold that ran before
        # the script checked the boxes may have left status at `implementing`
        # (tasks.md wasn't 100% yet); now that the step is closing, it's implemented.
        if ctx.get("status") not in ("completed", "archived"):
            ctx["status"] = "implemented"


def journal_task_finish(
    feature_dir: Path, task_id: str, by: str,
    did: str | None = None, files: list[str] | None = None,
) -> Path | None:
    """Append a SINGLE finish event for one implement task (finish-only model).

    Called live by the assistant after each task (`--task <id> --kind complete`).
    The delta to the previous finish (or the implement start) is the task's real
    duration — no start/complete pair, so a task can never collapse to a 0s tick.
    Idempotent (skips a task already closed) and same-step safe: it journals even
    when implement already self-closed to `implemented`; only a genuinely shipped
    spec (completed/archived) is left untouched.

    This is the read-modify-write path. For parallel runs the assistant uses the
    append path (`--append`) instead, which writes a line to `.spec-context.events.jsonl`
    with no read, and `--materialize` folds those lines through the same core here.

    Also writes `task_summaries.<task_id>` (the field the Activity panel's Tasks card
    reads) in the SAME atomic write, from `--did`/`--files`, so the panel is populated
    by the script call rather than a separately-skippable AI edit.
    """
    target = feature_dir / ".spec-context.json"
    opened = _open_ctx_or_none(feature_dir, f"task {task_id}")
    if opened is None:
        return None
    ctx, log, _branch = opened
    tasks_md = feature_dir / "tasks.md"
    _mark_tasks_done(tasks_md, {task_id})
    # One parse after the checkbox flip, shared by the fold's status verdict and the close check.
    markers = parse_task_markers(tasks_md)
    _fold_task_finish(ctx, log, feature_dir, task_id, by, did, files, _now_iso(), markers=markers)
    _maybe_close_implement(ctx, log, feature_dir, by, markers=markers)
    commit_log(ctx, log)
    atomic_write(target, ctx)
    return target


def append_task_log(
    feature_dir: Path, task_id: str, by: str,
    did: str | None = None, files: list[str] | None = None,
) -> Path | None:
    """Append ONE task-finish line to `.spec-context.events.jsonl`. The only WRITE
    is the append, so concurrent workers (subagents) each record themselves without
    contending — a single `O_APPEND` write of a short line is atomic across appenders
    on POSIX, and parallel finishes never interleave. (It reads `.spec-context.json`
    once to skip a shipped spec, but never rewrites it — concurrent reads don't
    contend, and the atomic temp+rename materialize never exposes a partial file.)

    The line carries its own `at` timestamp (real finish time) plus `did`/`files`,
    so `--materialize` can fold it later with the task's true duration preserved.
    This path never closes the step or updates status — that happens at fold time.
    A genuinely shipped spec (completed/archived) is left untouched, so a stray
    late append can't orphan a post-completion line into the events log.
    """
    if read_ctx(feature_dir / ".spec-context.json").get("status") in CROSS_STEP_TERMINAL:
        print(
            f"[companion] {feature_dir} already shipped; not appending task {task_id}.",
            file=sys.stderr,
        )
        return None
    log_path = feature_dir / ".spec-context.events.jsonl"
    entry: dict = {
        "step": "implement",
        "substep": None,
        "task": task_id,
        "kind": "complete",
        "by": by,
        "at": _now_iso(),
    }
    if did:
        entry["did"] = did
    if files:
        entry["files"] = files
    line = json.dumps(entry, ensure_ascii=False) + "\n"
    with open(log_path, "a", encoding="utf-8") as fh:
        fh.write(line)
    return log_path


def materialize_log(feature_dir: Path, by: str, quiet: bool = False) -> Path | None:
    """Fold every appended task-finish line into `.spec-context.json` in one write.

    Replays each `.spec-context.events.jsonl` line through `_fold_task_finish`, so the
    materialized `history`/`task_summaries` are byte-identical to what the live path
    would have produced — only batched into a single read-modify-write instead of one
    per task. Idempotent: dedup on (implement, task_id) means re-folding the whole log
    (per batch and again at step close) never double-counts. Leaves a genuinely shipped
    spec untouched. No log file → nothing to fold."""
    log_path = feature_dir / ".spec-context.events.jsonl"
    if not log_path.is_file():
        return None
    target = feature_dir / ".spec-context.json"
    opened = _open_ctx_or_none(feature_dir)
    if opened is None:
        return None
    ctx, log, _branch = opened
    tasks_md = feature_dir / "tasks.md"
    markers = parse_task_markers(tasks_md)
    folded = 0
    for raw in log_path.read_text(encoding="utf-8").splitlines():
        raw = raw.strip()
        if not raw:
            continue
        try:
            e = json.loads(raw)
        except json.JSONDecodeError:
            continue  # tolerate a torn line; the rest still fold
        tid = e.get("task")
        if not tid:
            continue
        _fold_task_finish(
            ctx, log, feature_dir, tid, e.get("by", by),
            e.get("did"), e.get("files"), e.get("at") or _now_iso(),
            markers=markers,
        )
        folded += 1
    # The script owns the checkboxes: flip tasks.md `[ ]` → `[x]` for every
    # journaled task (single writer, so parallel subagents that only append are
    # race-free). Must run BEFORE the step-close check, which reads tasks.md.
    _mark_tasks_done(tasks_md, _journaled_tasks(log))
    _maybe_close_implement(ctx, log, feature_dir, by, markers=parse_task_markers(tasks_md))
    commit_log(ctx, log)
    atomic_write(target, ctx)
    if not quiet:
        print(
            f"[companion] Materialized {folded} task line(s) from {log_path.name} into {target}.",
            file=sys.stderr,
        )
    return target


def sync_tasks(feature_dir: Path, tasks_md: Path, final_status: str, by: str) -> Path | None:
    """Per-task journaling for the implement step.

    Reads completed task markers in tasks.md and appends one transition per
    newly-completed task (idempotent — task ids already journaled are skipped).
    Sets currentStep=implement, currentTask to the last completed (or next
    pending) task, and status to `final_status` once every marker is checked,
    else "implementing". Honors the same no-backward-clobber guard.
    """
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)

    # Same-step safe: journal per-task even when implement already self-closed
    # (status "implemented"), so the backstop fills the journal regardless of AI
    # behavior. Only a genuinely shipped spec (completed/archived) is left alone.
    if ctx.get("status") in CROSS_STEP_TERMINAL:
        print(
            f"[companion] {target} already at status={ctx.get('status')}; "
            f"not regressing to implement.",
            file=sys.stderr,
        )
        return None

    # Fold any appended task lines first (idempotent) so a parallel run that used
    # the append path but skipped --materialize still gets its did/files into the
    # json before this marker-based backstop fills the rest.
    materialize_log(feature_dir, by)
    ctx = read_ctx(target)

    all_ids, done_ids = parse_task_markers(tasks_md)
    if not all_ids:
        print(f"[companion] No task markers found in {tasks_md}; nothing to sync.", file=sys.stderr)
        return None

    # Distinct, order-preserving — a marker id repeated in tasks.md is one task.
    distinct_all = list(dict.fromkeys(all_ids))
    distinct_done = list(dict.fromkeys(done_ids))

    log = canonical_log(ctx)
    already = _journaled_tasks(log)
    fresh = [tid for tid in distinct_done if tid not in already]

    fill_required(ctx, feature_dir, branch)
    ctx["currentStep"] = "implement"
    # Per-occurrence verdict (same single source as _maybe_close_implement): a
    # duplicate id with one marker still unchecked must not read as 100%.
    all_done = _tasks_at_100((all_ids, done_ids))
    ctx["status"] = final_status if all_done else "implementing"

    pending = [tid for tid in distinct_all if tid not in distinct_done]
    ctx["currentTask"] = (pending[0] if pending else (distinct_done[-1] if distinct_done else None))

    # Finish-only backstop: append ONE finish per fresh task (no start/complete
    # pair → no 0s tick). The live path (`--task <id> --kind complete`) already
    # journaled tasks captured during the run; `_journaled_tasks` skips those, so
    # this only fills gaps. Each is stamped with the script's own real clock.
    for tid in fresh:
        append_complete(log, "implement", task=tid, by=by, at=_now_iso())

    # Close the implement step itself once every marker is checked off — the hook
    # owns the implement self-close (the AI is told not to write it), so its end is
    # a real script timestamp, not the next step's start.
    if all_done:
        append_complete(log, "implement", by=by, at=_now_iso())
    commit_log(ctx, log)

    atomic_write(target, ctx)
    print(
        f"[companion] Synced {len(fresh)} new task event(s) "
        f"({len(distinct_done)}/{len(distinct_all)} complete) into {target}.",
        file=sys.stderr,
    )
    return target
