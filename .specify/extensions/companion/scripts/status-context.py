#!/usr/bin/env python3
"""Resolve a feature's current pipeline position and next action.

The read side that complements write-context.py / derive-from-files.py. Reads a
feature's .spec-context.json (or derives state from on-disk artifacts when the
file is missing/malformed) and emits a `ResumeResolution`: current step, status,
recorded decisions, and the next action / command — including the next unchecked
task when inside the implement step.

Both the `/speckit.companion.status` and `/speckit.companion.resume` command
markdowns run this. It prints a human summary plus a final machine line
`RESOLUTION: {json}` that `resume` parses deterministically.

Best-effort by design: never raises, never exits non-zero. Stdlib only.
"""

from __future__ import annotations

import argparse
import importlib
import json
import sys
from pathlib import Path

# The sibling modules' filenames have hyphens, so import them dynamically.
sys.path.insert(0, str(Path(__file__).resolve().parent))
wc = importlib.import_module("write-context")
dff = importlib.import_module("derive-from-files")

# Canonical forward pipeline (clarify/analyze are optional and not part of the
# default next-action path). Mirrors src/core/types/specContext.ts STEP_NAMES.
NEXT_STEP = {
    "specify": "plan",
    "clarify": "plan",
    "plan": "tasks",
    "tasks": "implement",
    "analyze": "implement",
    "implement": None,
}

STEP_COMMAND = {
    "specify": "/speckit.specify",
    "plan": "/speckit.plan",
    "tasks": "/speckit.tasks",
    "implement": "/speckit.implement",
}

# Companion family — mirrors STEP_COMMAND's keys. Status reports and resume
# dispatches these when the spec records the companion workflow, so the command
# family matches the flow the spec has been running.
COMPANION_STEP_COMMAND = {
    "specify": "/speckit.companion.specify",
    "plan": "/speckit.companion.plan",
    "tasks": "/speckit.companion.tasks",
    "implement": "/speckit.companion.implement",
}

def _is_companion(ctx: dict) -> bool:
    """Whether the spec runs the companion pipeline. `workflow` is the live
    signal; `profile: turbo` is the retired field, still honored so specs
    written before the workflow-choice collapse keep resuming on companion."""
    return ctx.get("workflow") == "companion" or ctx.get("profile") == "turbo"


def _step_command(step: str | None, companion: bool) -> str | None:
    """Resolve a step to its command in the family the spec is running."""
    table = COMPANION_STEP_COMMAND if companion else STEP_COMMAND
    return table.get(step)

# Status that marks each step's own completion (the point at which we advance).
STEP_DONE_STATUS = {
    "specify": "specified",
    "plan": "planned",
    "tasks": "ready-to-implement",
    "implement": "implemented",
}

NEXT_LABEL = {
    "plan": "Plan the feature",
    "tasks": "Generate tasks",
    "implement": "Implement",
}

TERMINAL_STATUSES = {"implemented", "completed", "archived"}

# Pipeline ordering + the artifact each step requires on disk. Used to reconcile
# recorded state against on-disk evidence (FR-011).
PIPELINE_ORDER = {"specify": 0, "clarify": 0, "plan": 1, "tasks": 2, "analyze": 2, "implement": 3}
REQUIRED_FILE = {
    "specify": "spec.md", "clarify": "spec.md", "plan": "plan.md",
    "tasks": "tasks.md", "analyze": "tasks.md", "implement": "tasks.md",
}


def _should_prefer_disk(feature_dir: Path, rec_step: str, disk_step: str) -> bool:
    """FR-011: trust on-disk artifacts over recorded state when they disagree —
    when the recorded step claims an artifact that doesn't exist, or disk shows a
    later artifact than recorded. Recorded is kept when it's merely further along
    a step whose artifact is present (e.g. implementing against an existing
    tasks.md), since disk can't see in-progress work."""
    if rec_step not in PIPELINE_ORDER:
        return True
    req = REQUIRED_FILE.get(rec_step)
    if req and not (feature_dir / req).is_file():
        return True
    return PIPELINE_ORDER.get(disk_step, -1) > PIPELINE_ORDER[rec_step]


def _decisions(ctx: dict) -> list[str]:
    """The top-level `decisions[]` passthrough (surfaced as ViewerState.decisions)."""
    raw = ctx.get("decisions")
    if isinstance(raw, list):
        return [str(d) for d in raw if isinstance(d, (str, int, float)) and str(d).strip()]
    return []


def _next_unchecked_task(feature_dir: Path) -> str | None:
    """First not-yet-checked task id in tasks.md order, or None when all done."""
    all_ids, done_ids = wc.parse_task_markers(feature_dir / "tasks.md")
    done = set(done_ids)
    for tid in dict.fromkeys(all_ids):  # distinct, order-preserving
        if tid not in done:
            return tid
    return None


def resolve(feature_dir: Path) -> dict:
    """Build the ResumeResolution for a feature directory."""
    target = feature_dir / ".spec-context.json"
    ctx = wc.read_ctx(target)
    source = "state"

    if not ctx or not ctx.get("currentStep"):
        # Missing or malformed/empty state — reconstruct from files (research R4).
        source = "derived"
        inferred = dff._infer(feature_dir)
        if inferred is None:
            return {
                "source": "derived",
                "empty": True,
                "specName": wc._spec_name(feature_dir),
                "currentStep": None,
                "status": None,
                "decisions": [],
                "nextStep": None,
                "nextCommand": None,
                "nextActionLabel": "Nothing to summarize",
                "nextTask": None,
                "complete": False,
            }
        step, status = inferred
        ctx = {**ctx, "currentStep": step, "status": status}
    else:
        # FR-011: recorded state present — prefer on-disk evidence when the
        # recorded position contradicts what the artifacts show.
        disk = dff._infer(feature_dir)
        if disk is not None and _should_prefer_disk(feature_dir, ctx["currentStep"], disk[0]):
            ctx = {**ctx, "currentStep": disk[0], "status": disk[1]}
            source = "derived"

    current_step = ctx.get("currentStep")
    status = ctx.get("status")
    spec_name = ctx.get("specName") or wc._spec_name(feature_dir)
    decisions = _decisions(ctx)
    companion = _is_companion(ctx)

    resolution = {
        "source": source,
        "empty": False,
        "specName": spec_name,
        "currentStep": current_step,
        "status": status,
        "decisions": decisions,
        "nextStep": None,
        "nextCommand": None,
        "nextActionLabel": "",
        "nextTask": None,
        "complete": False,
    }

    # Terminal: implemented / completed / archived.
    if status in TERMINAL_STATUSES:
        resolution["complete"] = True
        resolution["nextActionLabel"] = "Pipeline complete"
        return resolution

    if current_step == "implement":
        next_task = _next_unchecked_task(feature_dir)
        if next_task is None:
            resolution["complete"] = True
            resolution["nextActionLabel"] = "Pipeline complete"
            return resolution
        resolution["nextStep"] = "implement"
        resolution["nextCommand"] = _step_command("implement", companion)
        resolution["nextTask"] = next_task
        resolution["nextActionLabel"] = f"Continue implementation at {next_task}"
        return resolution

    done_status = STEP_DONE_STATUS.get(current_step)
    if done_status is not None and status == done_status:
        # Current step finished — advance to the next pipeline step.
        next_step = NEXT_STEP.get(current_step)
        resolution["nextStep"] = next_step
        resolution["nextCommand"] = _step_command(next_step, companion) if next_step else None
        resolution["nextActionLabel"] = NEXT_LABEL.get(next_step, "Continue") if next_step else "Pipeline complete"
        if next_step is None:
            resolution["complete"] = True
    else:
        # In-progress (specifying/planning/tasking/draft) — finish current step.
        # clarify/analyze (optional, no dedicated done-status) fall through here
        # and re-dispatch toward their next canonical step.
        if current_step in ("clarify", "analyze"):
            next_step = NEXT_STEP.get(current_step)
            resolution["nextStep"] = next_step
            resolution["nextCommand"] = _step_command(next_step, companion) if next_step else None
            resolution["nextActionLabel"] = NEXT_LABEL.get(next_step, "Continue")
        else:
            resolution["nextStep"] = current_step
            resolution["nextCommand"] = _step_command(current_step, companion)
            resolution["nextActionLabel"] = f"Finish {current_step}"

    return resolution


def _print_summary(res: dict) -> None:
    if res.get("empty"):
        print("Nothing to summarize (no spec files or recorded state found).")
        print(f"RESOLUTION: {json.dumps(res, ensure_ascii=False)}")
        return

    print(f"Spec: {res['specName']}   (source: {res['source']})")
    print(f"Step: {res['currentStep']}   Status: {res['status']}")

    decisions = res.get("decisions") or []
    if decisions:
        print("Decisions:")
        for d in decisions:
            print(f"  - {d}")
    else:
        print("Decisions: (none recorded)")

    if res.get("complete"):
        print("Next: Pipeline complete  →  —")
    elif res.get("nextTask"):
        print(f"Next: {res['nextActionLabel']}  →  dispatching {res['nextCommand']}")
    else:
        nxt = res.get("nextCommand") or "—"
        print(f"Next: {res['nextActionLabel']}  →  {nxt}")

    print(f"RESOLUTION: {json.dumps(res, ensure_ascii=False)}")


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Resolve a feature's pipeline position and next action."
    )
    parser.add_argument("--feature-dir", default=None)
    args = parser.parse_args()

    root = wc._repo_root()
    feature_dir = wc.resolve_feature_dir(root, args.feature_dir)
    if feature_dir is None or not feature_dir.is_dir():
        print(
            "[companion] Could not resolve the active feature directory "
            "(checked --feature-dir, SPECIFY_FEATURE_DIRECTORY, SPECIFY_FEATURE, "
            ".specify/feature.json, git branch prefix). Skipping status.",
            file=sys.stderr,
        )
        return 0  # best-effort: never fail the host command

    try:
        res = resolve(feature_dir)
        _print_summary(res)
    except Exception as exc:  # noqa: BLE001 - best-effort, swallow + report
        print(f"[companion] Warning: skipped status resolution: {exc}", file=sys.stderr)
        return 0

    return 0


if __name__ == "__main__":
    sys.exit(main())
