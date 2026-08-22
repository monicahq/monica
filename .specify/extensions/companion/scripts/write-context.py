#!/usr/bin/env python3
"""Write/update a feature's .spec-context.json from a spec-kit lifecycle hook.

Invoked by the `speckit.companion.after-*` command-markdowns (registered on the
spec-kit lifecycle hooks). Resolves the active feature directory using spec-kit's
own precedence, then does a crash-safe read-merge-write of the Companion's
canonical .spec-context.json:

  - preserves every existing/unknown top-level key (read-then-merge)
  - appends to the canonical `history[]` (append-only; never rewritten or
    shrunk), migrating a legacy `transitions[]` array forward so the extension
    and the VS Code GUI write the same single field
  - writes atomically (temp file + os.replace)
  - emits Companion-canonical values; never the legacy `currentStep: "done"`

This module owns the command line, the step lifecycle, the journal, terminal
promotion, and the no-regress guard. The rest lives in siblings — `spec_context`
(the store), `spec_deltas` (the delta grammar), `capture` (the additive capture
writers), `task_sync` (task markers and the per-task journal), and
`living_spec_fold` (the fold-back). Every name they hold is re-exported here, so
anything that imports this module keeps reaching them by their original path.

Stdlib only. Safe to run anywhere `python3` is available.
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path

# The siblings live beside this script; a caller may load it by file path with
# no import path set up, so anchor on our own directory rather than the cwd.
sys.path.insert(0, str(Path(__file__).resolve().parent))

from spec_context import (  # noqa: E402,F401
    CANONICAL_STEPS,
    CROSS_STEP_TERMINAL,
    PREFIX_RE,
    STEP_COMPLETED_STATUS,
    STEP_ORDER,
    TERMINAL_STATUSES,
    _entry_kind,
    _git_branch,
    _has_complete,
    _has_step_start,
    _is_more_advanced,
    _is_per_task,
    _is_step_level,
    _journaled_tasks,
    _match_by_prefix,
    _now_iso,
    _open_ctx_or_none,
    _repo_root,
    _repo_root_for,
    _spec_name,
    append_complete,
    atomic_write,
    canonical_log,
    commit_log,
    feature_dir_from_tasks_file,
    fill_required,
    read_ctx,
    resolve_feature_dir,
)
from spec_deltas import (  # noqa: E402,F401
    _CAP_MARKER_RE,
    _DELTA_HEADER_RE,
    _RENAME_RE,
    _REQ_HEADING_RE,
    _has_deltas,
    _split_requirements,
    parse_spec_deltas,
)
from capture import (  # noqa: E402,F401
    CLASSIFICATION_VERDICTS,
    PROTECTED_SET_KEYS,
    _coerce_entry,
    _coerce_value,
    _entry_identity,
    _parsed_classification,
    append_capture_entries,
    append_string_list,
    set_classification,
    set_fields,
    set_living_specs_loaded,
    set_living_specs_skipped,
    set_living_specs_synced,
    upsert_coverage,
    upsert_step_summary,
)
from task_sync import (  # noqa: E402,F401
    COMPLETED_TASK_RE,
    PENDING_TASK_RE,
    _feature_tasks_at_100,
    _fold_task_finish,
    _gc_events_log,
    _mark_tasks_done,
    _maybe_close_implement,
    _tasks_at_100,
    _upsert_task_summary,
    append_task_log,
    journal_task_finish,
    materialize_log,
    parse_task_markers,
    sync_tasks,
)
from living_spec_fold import (  # noqa: E402,F401
    _git_changed_files,
    _initial_living_spec,
    _living_requirement_span,
    _load_resolver,
    _rename_map,
    _resolve_fold_targets,
    _resolve_rename,
    _retitle,
    apply_deltas,
    fold_living_spec,
)


def update_context(
    feature_dir: Path, step: str, status: str, by: str, kind: str = "start",
    substep: str | None = None,
) -> Path | None:
    target = feature_dir / ".spec-context.json"
    now = _now_iso()
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"

    ctx = read_ctx(target)

    # Never drag a more-advanced (e.g. shipped) spec backward. Leave it fully
    # intact — this is the bug the schema reconciliation exists to prevent.
    if ctx and _is_more_advanced(ctx, step):
        print(
            f"[companion] {target} already at currentStep={ctx.get('currentStep')} / "
            f"status={ctx.get('status')}; not regressing to {step}/{status}.",
            file=sys.stderr,
        )
        return None

    log = canonical_log(ctx)
    fill_required(ctx, feature_dir, branch)

    ctx["currentStep"] = step
    ctx["status"] = status

    if kind == "complete":
        # Deterministic self-close. Idempotent: skip if the step is already closed,
        # so the body's `--kind complete` and the GUI's guarded completeStep (or a
        # re-run) never produce two completes. No `from` on a complete. A `substep`
        # ("fast-path") folds plan/tasks into the specify run; it dedups on (step,
        # substep) so it never collides with a real step-level complete.
        append_complete(log, step, substep=substep, by=by, at=now)
    else:
        # A step is started once. Skip a redundant start if this (step, substep)
        # already has a start anywhere in the log — this collapses the GUI startStep +
        # the body start + the late after_specify hook-start into one entry.
        if not _has_step_start(log, step, substep):
            log.append({
                "step": step,
                "substep": substep,
                "kind": "start",
                "by": by,
                "at": now,
            })
    commit_log(ctx, log)

    atomic_write(target, ctx)
    return target


def journal_finish(feature_dir: Path, step: str, by: str, substep: str | None = None) -> Path | None:
    """Append a single step- or substep-level **finish** to history and nothing else.

    This is the AI's timing self-close for the boundaries the extension doesn't
    stamp: a step-level finish for clarify/analyze (substep=None), or a substep
    boundary (plan: research/design; tasks: generate). The pipeline steps
    (specify/plan/tasks/implement) are extension-stamped in Companion runs —
    bodies record the start, the specify body / after-step hooks record the
    complete — and an earlier `ai` step-level complete would win the idempotent
    append and block that trusted close; only hook-less stock runs still
    self-close plan/tasks. The AI used to hand-author the JSON, which is what
    produced a duplicate `status` key. Routing it through the script makes the write atomic
    (no malformed file possible) and stops the AI ever editing .spec-context.json
    by hand. Deliberately does NOT touch `status` or `currentStep` (the hooks own
    those) — it only adds the honest finish timestamp. Idempotent on (step, substep);
    best-effort; a genuinely shipped spec (completed/archived) is left untouched."""
    # A finish is only meaningful for a canonical step; reject a typo'd or omitted
    # step (which would otherwise default to "specify" and journal a junk complete).
    if step not in CANONICAL_STEPS:
        print(
            f"[companion] Skipping --finish: '{step}' is not a canonical step "
            f"({', '.join(sorted(CANONICAL_STEPS))}).",
            file=sys.stderr,
        )
        return None
    target = feature_dir / ".spec-context.json"
    opened = _open_ctx_or_none(feature_dir, f"a {step}{('/' + substep) if substep else ''} finish")
    if opened is None:
        return None
    ctx, log, _branch = opened
    append_complete(log, step, substep=substep, by=by, at=_now_iso())
    commit_log(ctx, log)
    atomic_write(target, ctx)
    return target


def journal_advance(feature_dir: Path, step: str, by: str) -> Path | None:
    """Finish a step AND flip status to its canonical completed-status in one write.

    The single-call alternative to `--finish` followed by a status write: it appends
    the step's completion (idempotent — like `--finish`, never a duplicate, never a
    start) and flips `status`/`currentStep` to `STEP_COMPLETED_STATUS[step]`. The flip
    is forward-only: it reuses `_is_more_advanced` so advancing an earlier step on a
    spec that already moved past it (a re-run or a double-fired hook) records the finish
    but never drags status/currentStep backward. A step with no canonical completed-status
    (clarify/analyze) records only the finish, leaving status untouched — mirroring
    `--finish`. Idempotent; a shipped spec is left untouched."""
    if step not in CANONICAL_STEPS:
        print(
            f"[companion] Skipping --advance: '{step}' is not a canonical step "
            f"({', '.join(sorted(CANONICAL_STEPS))}).",
            file=sys.stderr,
        )
        return None
    target = feature_dir / ".spec-context.json"
    opened = _open_ctx_or_none(feature_dir, f"an {step} advance")
    if opened is None:
        return None
    ctx, log, _branch = opened
    append_complete(log, step, by=by, at=_now_iso())
    completed_status = STEP_COMPLETED_STATUS.get(step)
    if completed_status is not None:
        if _is_more_advanced(ctx, step):
            print(
                f"[companion] {target} already at currentStep={ctx.get('currentStep')} / "
                f"status={ctx.get('status')}; recorded the {step} finish without regressing status.",
                file=sys.stderr,
            )
        else:
            ctx["status"] = completed_status
            ctx["currentStep"] = step
    commit_log(ctx, log)
    atomic_write(target, ctx)
    return target


def mark_spec_complete(feature_dir: Path, by: str) -> Path | None:
    """Promote a finished spec to the terminal `completed` status.

    This is the only sanctioned writer of `status: completed`. The Companion
    workflow's terminal `mark-complete` node dispatches the command that calls
    this; the AI never hand-writes `completed`. `update_context` deliberately
    refuses to advance a spec whose status is already terminal (`implemented`),
    so the final promotion needs this dedicated path. `currentStep` stays at
    `implement` (the last real step), keeping the canonical invariant that the
    last `history` entry's step equals `currentStep`.

    Source state: promotes a spec that has finished implement (`status ==
    "implemented"`), and also one still `implementing` whose tasks are **all
    checked off** — that 100%-done spec is finished in fact, so it advances
    implementing → implemented → completed in a single atomic write (the
    implement step is closed in `history` first; no distinct `implemented` status
    is persisted — the status goes straight to `completed`).
    A spec still `specifying` / `planning`, or `implementing` with work left, is
    not done, so a stray or out-of-order invocation can never "ship" incomplete
    work. Idempotent: a spec already `completed`/`archived` is left untouched.
    """
    target = feature_dir / ".spec-context.json"
    ctx = read_ctx(target)
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"

    if ctx.get("status") in CROSS_STEP_TERMINAL:
        print(
            f"[companion] {target} already at status={ctx.get('status')}; "
            f"nothing to mark complete.",
            file=sys.stderr,
        )
        return None

    status = ctx.get("status")
    from_implementing_at_100 = status == "implementing" and _feature_tasks_at_100(feature_dir)
    if status != "implemented" and not from_implementing_at_100:
        print(
            f"[companion] {target} is at status={status!r} with implement not "
            f"finished; refusing to mark complete (only a finished implement step, "
            f"or an implementing spec with every task checked, can be shipped).",
            file=sys.stderr,
        )
        return None

    # Fold any still-pending appended finishes into the json before the GC below
    # removes the events log — a straggler line appended after step-close would
    # otherwise be dropped. Idempotent and quiet (internal prerequisite); re-read
    # so the folded entries are in scope.
    materialize_log(feature_dir, by, quiet=True)
    ctx = read_ctx(target)

    log = canonical_log(ctx)
    fill_required(ctx, feature_dir, branch)
    ctx.setdefault("currentStep", "implement")
    # Promoting straight from implementing@100%: close the implement step first so the canonical `implemented` state exists before `completed`.
    if from_implementing_at_100:
        append_complete(log, "implement", by=by, at=_now_iso())
    ctx["status"] = "completed"
    commit_log(ctx, log)
    atomic_write(target, ctx)
    _gc_events_log(feature_dir)
    return target


def main() -> int:
    parser = argparse.ArgumentParser(description="Write/update a feature's .spec-context.json")
    parser.add_argument("--step", default="specify")
    parser.add_argument("--status", default="specified")
    parser.add_argument("--by", default="extension")
    parser.add_argument("--kind", default="start", choices=["start", "complete"])
    parser.add_argument(
        "--substep", default=None,
        help="Tag the step-level start/complete with a substep (e.g. 'fast-path' "
             "to fold plan/tasks into the specify run).",
    )
    parser.add_argument("--feature-dir", default=None)
    parser.add_argument(
        "--tasks-file", default=None,
        help="Per-task journaling: append a transition per completed marker in this tasks.md.",
    )
    parser.add_argument(
        "--task", default=None,
        help="Per-task finish (finish-only): append one complete event for this task id.",
    )
    parser.add_argument(
        "--append", action="store_true",
        help="With --task: append the finish to .spec-context.events.jsonl (no read of "
             ".spec-context.json) so parallel workers never contend. Fold later with --materialize.",
    )
    parser.add_argument(
        "--materialize", action="store_true",
        help="Fold every appended .spec-context.events.jsonl task line into .spec-context.json "
             "in one write (idempotent). Run after each batch and at step close.",
    )
    parser.add_argument(
        "--mark-complete", action="store_true",
        help="Promote a finished spec to the terminal status 'completed' "
             "(the only sanctioned writer of completed; keeps currentStep=implement).",
    )
    parser.add_argument(
        "--finish", action="store_true",
        help="Append a pure timing finish for --step (and optional --substep) to history "
             "without touching status/currentStep — the AI's self-close for clarify/analyze "
             "and the plan/tasks substeps (hook-less stock runs also self-close plan/tasks). "
             "Replaces hand-authored JSON edits.",
    )
    parser.add_argument(
        "--advance", action="store_true",
        help="Finish --step AND flip status to that step's canonical completed-status "
             "(specify->specified, plan->planned, tasks->ready-to-implement, "
             "implement->implemented) in one atomic write. No start entry; idempotent. "
             "clarify/analyze record only the finish (no status change).",
    )
    parser.add_argument(
        "--did", default=None,
        help="With --task: a one-line summary of what the task did, written to "
             "task_summaries.<id>.did (the Activity panel's Tasks card).",
    )
    parser.add_argument(
        "--files", default=None,
        help="With --task: comma-separated files the task touched, written to "
             "task_summaries.<id>.files.",
    )
    parser.add_argument(
        "--set", dest="set_pairs", action="append", default=None, metavar="KEY=VALUE",
        help="Merge a top-level key=value onto .spec-context.json (e.g. --set unattended=true). "
             "Repeatable. Lifecycle keys (history/status/currentStep) are refused.",
    )
    parser.add_argument(
        "--living-specs", dest="living_specs", action="append", default=None, metavar="NAME",
        help="Record a loaded living-specs capability name onto livingSpecs.loaded "
             "(most-specific-first order, de-duped). Repeatable. Additive metadata; "
             "never a lifecycle key.",
    )
    parser.add_argument(
        "--living-spec-skip", dest="living_spec_skips", action="append", default=None,
        metavar="NAME: REASON",
        help="Record a loaded living-specs capability completion deliberately did NOT "
             "fold, with a reason (onto livingSpecs.skipped as {name, reason}). This is "
             "how completion accounts for a loaded capability the change didn't alter — "
             "'correctly nothing' instead of 'silently nothing'. Additive metadata; "
             "never a lifecycle key. Repeatable. Format: \"<name>: <reason>\".",
    )
    parser.add_argument(
        "--fold-living-spec", dest="fold_living_spec", action="store_true",
        help="Fold this feature spec's ADDED/MODIFIED/REMOVED/RENAMED requirement "
             "deltas into the resolved capability's living spec (LS·3 archive-as-merge). "
             "Opt-in (livingSpecs.enabled), best-effort, no-op without a delta block, "
             "idempotent. Records synced names onto livingSpecs.synced.",
    )
    parser.add_argument(
        "--decision", dest="decisions", action="append", default=None, metavar="JSON|TEXT",
        help="Append a decision to decisions[] (de-duped on the decision text). "
             "JSON object with a 'decision' key (plus why/rejected), or bare text. Repeatable.",
    )
    parser.add_argument(
        "--verified", dest="verified", action="append", default=None, metavar="JSON|TEXT",
        help="Append a verification to verified[] (de-duped on 'what'). "
             "JSON object with a 'what' key (plus result/command/warnings), or bare text. Repeatable.",
    )
    parser.add_argument(
        "--concern", dest="concerns", action="append", default=None, metavar="JSON|TEXT",
        help="Append a concern to concerns[] (de-duped on 'note'). "
             "JSON object with a 'note' key (plus step/kind), or bare text. Repeatable.",
    )
    parser.add_argument(
        "--expectation", dest="expectations", action="append", default=None, metavar="TEXT",
        help="Append an out-of-scope/non-goal string to expectations[] (de-duped). Repeatable.",
    )
    parser.add_argument(
        "--context", dest="context_entries", action="append", default=None, metavar="TEXT",
        help="Append a context entry to context[] — what the run worked from (a loaded "
             "living spec, an investigated area, a constraint). De-duped. Repeatable.",
    )
    parser.add_argument(
        "--coverage-req", dest="coverage_req", default=None, metavar="REQ_ID",
        help="Upsert coverage.<REQ_ID> with --tasks and/or --tests (non-destructive merge: "
             "only a supplied list replaces its slot).",
    )
    parser.add_argument(
        "--tests", dest="coverage_tests", default=None,
        help="With --coverage-req: comma-separated test refs covering the requirement.",
    )
    parser.add_argument(
        "--tasks", dest="coverage_tasks", default=None,
        help="With --coverage-req: comma-separated task ids covering the requirement.",
    )
    parser.add_argument(
        "--title", dest="coverage_title", default=None,
        help="With --coverage-req: the requirement's one-line text, so requirements "
             "are captured as readable content, not just ids.",
    )
    parser.add_argument(
        "--step-summary", dest="step_summary", default=None, metavar="JSON|TEXT",
        help="Upsert step_summaries.<--step> from a JSON object with a 'summary' key "
             "(plus key_finding/risks) or bare text.",
    )
    parser.add_argument(
        "--classification", dest="classification", default=None, metavar="JSON",
        help="Store the size classification object {projectedFiles, projectedTasks, "
             "scopeSignal, verdict}; verdict (simple|normal|oversized) is required.",
    )
    args = parser.parse_args()

    # Best-effort guard: a non-canonical step is a no-op, never a host failure.
    # Terminal state belongs in `status`, not `currentStep`. Skipped in task-sync
    # mode, which always operates on the implement step.
    capture_mode = bool(
        args.decisions or args.verified or args.concerns or args.expectations
        or args.coverage_req or args.step_summary or args.classification or args.context_entries
    )
    if not args.tasks_file and not args.task and not args.mark_complete and not args.set_pairs and not args.living_specs and not args.living_spec_skips and not args.fold_living_spec and not args.materialize and not args.finish and not args.advance and not capture_mode and (args.step == "done" or args.step not in CANONICAL_STEPS):
        print(
            f"[companion] Skipping: '{args.step}' is not a canonical currentStep "
            f"({', '.join(sorted(CANONICAL_STEPS))}).",
            file=sys.stderr,
        )
        return 0

    root = _repo_root()

    # Task-sync mode: the `--tasks-file` parent is the authoritative spec dir.
    # The active-feature pointer (env / feature.json / branch) can name a LATER
    # spec while settling an earlier one, so trusting it here writes completion
    # into the wrong spec. When `--feature-dir` is also given and disagrees with
    # the tasks file's dir, refuse to write (surface the mismatch) rather than
    # silently picking one.
    if args.tasks_file:
        tf_dir = feature_dir_from_tasks_file(root, args.tasks_file)
        if args.feature_dir:
            explicit_dir = resolve_feature_dir(root, args.feature_dir)
            if explicit_dir is not None and explicit_dir.resolve() != tf_dir.resolve():
                print(
                    f"[companion] --feature-dir ({explicit_dir}) and --tasks-file dir "
                    f"({tf_dir}) disagree; refusing to write to avoid settling the "
                    f"wrong spec. Drop --feature-dir or point --tasks-file at its tasks.md.",
                    file=sys.stderr,
                )
                return 0
        feature_dir: Path | None = tf_dir
    else:
        feature_dir = resolve_feature_dir(root, args.feature_dir)

    if feature_dir is None or not feature_dir.is_dir():
        print(
            "[companion] Could not resolve the active feature directory "
            "(checked --feature-dir, SPECIFY_FEATURE_DIRECTORY, SPECIFY_FEATURE, "
            ".specify/feature.json, git branch prefix). Skipping context write.",
            file=sys.stderr,
        )
        return 0  # best-effort: never fail the host command

    # Caller-error validation for --classification (exit 2, per the capture contract):
    # a malformed classification is a bug in the emitting body, not a runtime miss.
    # Validated before anything is written so a bad value records nothing at all.
    if args.classification:
        try:
            _parsed_classification(args.classification)
        except ValueError as exc:
            print(f"[companion] {exc}", file=sys.stderr)
            return 2

    # Capture flags are additive: every one given in a single call takes effect.
    # A ladder here recorded the first and dropped the rest, exit 0, with the
    # caller told only about the one that landed.
    captured: list[str] = []
    try:
        if args.classification:
            target = set_classification(feature_dir, args.classification)
            captured.append(f"[companion] Recorded classification in {target}")
        if args.set_pairs:
            target = set_fields(feature_dir, args.set_pairs)
            captured.append(f"[companion] Set {', '.join(args.set_pairs)} in {target}")
        if args.decisions:
            target = append_capture_entries(feature_dir, "decisions", "decision", args.decisions)
            captured.append(f"[companion] Recorded {len(args.decisions)} decision(s) in {target}")
        if args.verified:
            target = append_capture_entries(feature_dir, "verified", "what", args.verified)
            captured.append(f"[companion] Recorded {len(args.verified)} verification(s) in {target}")
        if args.concerns:
            target = append_capture_entries(feature_dir, "concerns", "note", args.concerns)
            captured.append(f"[companion] Recorded {len(args.concerns)} concern(s) in {target}")
        if args.expectations:
            target = append_string_list(feature_dir, "expectations", args.expectations)
            captured.append(f"[companion] Recorded {len(args.expectations)} expectation(s) in {target}")
        if args.context_entries:
            target = append_string_list(feature_dir, "context", args.context_entries)
            captured.append(f"[companion] Recorded {len(args.context_entries)} context entr(y/ies) in {target}")
        if args.coverage_req:
            cov_tasks = (
                [t.strip() for t in args.coverage_tasks.split(",") if t.strip()]
                if args.coverage_tasks else None
            )
            cov_tests = (
                [t.strip() for t in args.coverage_tests.split(",") if t.strip()]
                if args.coverage_tests else None
            )
            target = upsert_coverage(feature_dir, args.coverage_req, cov_tasks, cov_tests, args.coverage_title)
            captured.append(f"[companion] Upserted coverage for {args.coverage_req} in {target}")
        if args.step_summary:
            target = upsert_step_summary(feature_dir, args.step, args.step_summary)
            captured.append(f"[companion] Recorded {args.step} step summary in {target}")
        if args.living_specs:
            target = set_living_specs_loaded(feature_dir, args.living_specs)
            captured.append(
                f"[companion] Recorded loaded living specs ({', '.join(args.living_specs)}) in {target}")
        if args.living_spec_skips:
            entries = []
            for raw in args.living_spec_skips:
                name, sep, reason = str(raw).partition(":")
                if name.strip() and not (sep and reason.strip()):
                    print(
                        f"[companion] Warning: --living-spec-skip \"{raw}\" has no reason and "
                        "was NOT recorded — an unexplained skip isn't accountability. Use "
                        "\"<name>: <reason>\"; the capability stays unaccounted until you fold a "
                        "delta or record a reasoned skip.",
                        file=sys.stderr,
                    )
                entries.append({"name": name.strip(), "reason": reason.strip()})
            target = set_living_specs_skipped(feature_dir, entries)
            if target is not None:
                names = ", ".join(e["name"] for e in entries if e["name"])
                captured.append(f"[companion] Recorded living-spec skip note(s) ({names}) in {target}")
        if args.fold_living_spec:
            target = fold_living_spec(feature_dir, args.by)
            if target is not None:
                ctx = read_ctx(target)
                synced = ((ctx.get("livingSpecs") or {}).get("synced")) or []
                captured.append(
                    f"[companion] Folded feature deltas into living spec(s): {', '.join(synced)} ({target})")
    except Exception as exc:  # noqa: BLE001 - best-effort, swallow + report
        print(f"[companion] Warning: skipped .spec-context.json write: {exc}", file=sys.stderr)
        return 0

    # A no-op fold already named its own exact reason on stderr (from
    # fold_living_spec) — don't paper over it with a generic OR-string.

    if captured or capture_mode or args.set_pairs or args.living_specs or args.living_spec_skips or args.fold_living_spec:
        for line in captured:
            print(line)
        skipped = [
            name for name, given in (
                ("--tasks-file", args.tasks_file), ("--task", args.task),
                ("--materialize", args.materialize), ("--mark-complete", args.mark_complete),
                ("--finish", args.finish), ("--advance", args.advance),
            ) if given
        ]
        if skipped:
            print(
                f"[companion] Warning: {', '.join(skipped)} not applied — a capture flag "
                f"in the same call takes precedence. Run it as a separate call.",
                file=sys.stderr,
            )
        return 0

    # Lifecycle modes stay exclusive — these are alternative readings of one
    # invocation, not composable writes.
    try:
        if args.tasks_file:
            tasks_md = Path(args.tasks_file)
            if not tasks_md.is_absolute():
                tasks_md = root / tasks_md
            # Task-sync operates on the implement step; the global --status default
            # ("specified") would be an incoherent terminal status here.
            final_status = args.status if args.status != parser.get_default("status") else "implemented"
            target = sync_tasks(feature_dir, tasks_md, final_status, args.by)
        elif args.mark_complete:
            target = mark_spec_complete(feature_dir, args.by)
        elif args.finish:
            target = journal_finish(feature_dir, args.step, args.by, args.substep)
        elif args.advance:
            target = journal_advance(feature_dir, args.step, args.by)
        elif args.materialize:
            target = materialize_log(feature_dir, args.by)
        elif args.task:
            files = (
                [f.strip() for f in args.files.split(",") if f.strip()]
                if args.files else None
            )
            did = args.did.strip() if args.did else None
            if args.append:
                target = append_task_log(feature_dir, args.task, args.by, did, files)
            else:
                target = journal_task_finish(feature_dir, args.task, args.by, did, files)
        else:
            target = update_context(feature_dir, args.step, args.status, args.by, args.kind, args.substep)
    except Exception as exc:  # noqa: BLE001 - best-effort, swallow + report
        print(f"[companion] Warning: skipped .spec-context.json write: {exc}", file=sys.stderr)
        return 0

    if target is not None and not args.tasks_file:
        if args.mark_complete:
            print(f"[companion] Marked {target} complete (status=completed, by={args.by})")
        elif args.finish:
            _label = f"{args.step}{('/' + args.substep) if args.substep else ''}"
            print(f"[companion] Journaled {_label} finish in {target} (by={args.by})")
        elif args.advance:
            print(f"[companion] Advanced {args.step} in {target} (by={args.by})")
        elif args.materialize:
            print(f"[companion] Materialized append-log into {target}")
        elif args.task and args.append:
            print(f"[companion] Appended finish for task {args.task} to {target} (by={args.by})")
        elif args.task:
            print(f"[companion] Journaled finish for task {args.task} in {target} (by={args.by})")
        else:
            print(f"[companion] Updated {target} (currentStep={args.step}, status={args.status}, kind={args.kind}, by={args.by})")
    return 0


if __name__ == "__main__":
    sys.exit(main())
