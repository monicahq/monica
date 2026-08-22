#!/usr/bin/env python3
"""Reconstruct a feature's .spec-context.json from on-disk artifacts + git.

The derive-from-files fallback that complements the hook-driven write-context.py
in this same directory. When a spec-kit lifecycle hook never fired (so no
.spec-context.json exists or it lags the real artifacts), this script infers the
feature's step/status purely from which files are present (spec.md / plan.md /
tasks.md and the task-marker checkbox state) and writes the same canonical
.spec-context.json shape that write-context.py emits.

Best-effort by design: never raises, never exits non-zero, and never drags a
more-advanced spec backward (no-backward-clobber).

Stdlib only. Safe to run anywhere `python3` is available.
"""

from __future__ import annotations

import argparse
import importlib
import sys
from pathlib import Path

# The sibling module's filename has a hyphen, so it can't be a normal import.
sys.path.insert(0, str(Path(__file__).resolve().parent))
wc = importlib.import_module("write-context")


def _infer(feature_dir: Path) -> tuple[str, str] | None:
    """Map artifact presence to (step, status); None when nothing is present."""
    tasks_md = feature_dir / "tasks.md"
    plan_md = feature_dir / "plan.md"
    spec_md = feature_dir / "spec.md"

    if tasks_md.is_file():
        all_ids, done_ids = wc.parse_task_markers(tasks_md)
        # Distinct-id + set-coverage, matching write-context.py sync_tasks, so a
        # duplicated marker id can't make derive and task-sync disagree on "done".
        distinct_all = set(all_ids)
        if distinct_all and set(done_ids) >= distinct_all:
            return "implement", "implemented"
        return "tasks", "ready-to-implement"
    if plan_md.is_file():
        return "plan", "planned"
    if spec_md.is_file():
        return "specify", "specified"
    return None


def derive(feature_dir: Path, by: str = "derive") -> Path | None:
    target = feature_dir / ".spec-context.json"
    ctx = wc.read_ctx(target)

    inferred = _infer(feature_dir)
    if inferred is None:
        print(
            f"[companion] No spec.md/plan.md/tasks.md in {feature_dir}; "
            "nothing to derive.",
            file=sys.stderr,
        )
        return None
    step, status = inferred

    if ctx and wc._is_more_advanced(ctx, step):
        print(
            f"[companion] {target} already at currentStep={ctx.get('currentStep')} / "
            f"status={ctx.get('status')}; not regressing.",
            file=sys.stderr,
        )
        return None

    now = wc._now_iso()
    branch = wc._git_branch(wc._repo_root_for(feature_dir)) or "main"

    log = wc.canonical_log(ctx)
    wc.fill_required(ctx, feature_dir, branch)

    ctx["currentStep"] = step
    ctx["status"] = status

    log.append({
        "step": step,
        "substep": None,
        "kind": "start",
        "by": by,
        "at": now,
    })

    if step == "implement":
        all_ids, done_ids = wc.parse_task_markers(feature_dir / "tasks.md")
        distinct_all = list(dict.fromkeys(all_ids))
        distinct_done = list(dict.fromkeys(done_ids))
        already = wc._journaled_tasks(log)
        # Finish-only per-task entries (substep null, task carries the id) — matching
        # write-context.py sync_tasks so a derived spec and a hook-captured one have
        # the same shape. No paired start/complete: one finish event per task.
        for tid in distinct_done:
            if tid in already:
                continue
            log.append({
                "step": "implement",
                "substep": None,
                "task": tid,
                "kind": "complete",
                "by": by,
                "at": wc._now_iso(),
            })
        pending = [tid for tid in distinct_all if tid not in distinct_done]
        ctx["currentTask"] = (pending[0] if pending else (distinct_done[-1] if distinct_done else None))
        # Close the implement step itself once every marker is checked off.
        all_done = bool(distinct_all) and set(distinct_done) >= set(distinct_all)
        if all_done and not wc._has_complete(log, "implement", None):
            log.append({
                "step": "implement",
                "substep": None,
                "kind": "complete",
                "by": by,
                "at": wc._now_iso(),
            })

    wc.commit_log(ctx, log)

    wc.atomic_write(target, ctx)
    print(
        f"[companion] Derived {target} from files "
        f"(currentStep={step}, status={status}, by={by}).",
        file=sys.stderr,
    )
    return target


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Reconstruct a feature's .spec-context.json from on-disk artifacts."
    )
    parser.add_argument("--feature-dir", default=None)
    parser.add_argument("--by", default="derive")
    args = parser.parse_args()

    root = wc._repo_root()
    feature_dir = wc.resolve_feature_dir(root, args.feature_dir)
    if feature_dir is None or not feature_dir.is_dir():
        print(
            "[companion] Could not resolve the active feature directory "
            "(checked --feature-dir, SPECIFY_FEATURE_DIRECTORY, SPECIFY_FEATURE, "
            ".specify/feature.json, git branch prefix). Skipping derive.",
            file=sys.stderr,
        )
        return 0  # best-effort: never fail the host command

    try:
        derive(feature_dir, args.by)
    except Exception as exc:  # noqa: BLE001 - best-effort, swallow + report
        print(f"[companion] Warning: skipped .spec-context.json derive: {exc}", file=sys.stderr)
        return 0

    return 0


if __name__ == "__main__":
    sys.exit(main())
