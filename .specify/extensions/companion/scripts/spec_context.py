#!/usr/bin/env python3
"""The .spec-context.json store: read, atomic write, feature-dir resolution, history log.

The bottom of the scripts' dependency order — every other companion script reads
and writes a spec's context through here, so the merge rules, the atomic write,
and the canonical step/status vocabulary have exactly one home.

Stdlib only."""

from __future__ import annotations

import datetime
import json
import os
import re
import subprocess
import sys
from pathlib import Path


# Canonical vocab (mirrors src/core/types/specContext.ts). Kept here only to
# reject the legacy terminal step and to avoid regressing an advanced spec.
CANONICAL_STEPS = {"specify", "clarify", "plan", "tasks", "analyze", "implement"}
STEP_ORDER = {"specify": 0, "clarify": 1, "plan": 2, "tasks": 3, "analyze": 4, "implement": 5}
# The single home for the step -> canonical completed-status map. `--advance`
# flips status to this when finishing a step; clarify/analyze are absent (no
# status advance) so the verb records only the finish for them.
STEP_COMPLETED_STATUS = {
    "specify": "specified",
    "plan": "planned",
    "tasks": "ready-to-implement",
    "implement": "implemented",
}
# A spec at one of these statuses must never be dragged backward by a hook that
# fires after an earlier step (e.g. after_specify re-resolving to a shipped spec).
TERMINAL_STATUSES = {"implemented", "completed", "archived"}
# Narrower guard for per-task / backstop writes: "implemented" is the implement
# step's own same-step terminal, so per-task journaling is still allowed there;
# only a genuinely shipped spec (completed/archived) is left untouched.
CROSS_STEP_TERMINAL = {"completed", "archived"}

PREFIX_RE = re.compile(r"^(\d+)-")


def _now_iso() -> str:
    now = datetime.datetime.now(datetime.timezone.utc)
    return now.strftime("%Y-%m-%dT%H:%M:%S.") + f"{now.microsecond // 1000:03d}Z"


def _repo_root() -> Path:
    try:
        out = subprocess.run(
            ["git", "rev-parse", "--show-toplevel"],
            capture_output=True, text=True, check=True,
        ).stdout.strip()
        if out:
            return Path(out)
    except (subprocess.CalledProcessError, FileNotFoundError):
        pass
    return Path.cwd()


def _repo_root_for(path: Path) -> Path:
    """Repo root that contains `path`, anchoring git on that directory rather than
    cwd — so a write into a sandbox spec dir resolves the sandbox's root, not the
    process cwd. Falls back to the cwd-based root (`_repo_root()`) when git can't
    answer for `path`."""
    try:
        out = subprocess.run(
            ["git", "-C", str(path), "rev-parse", "--show-toplevel"],
            capture_output=True, text=True, check=True,
        ).stdout.strip()
        if out:
            return Path(out)
    except (subprocess.CalledProcessError, FileNotFoundError):
        pass
    return _repo_root()


def _git_branch(root: Path) -> str | None:
    try:
        out = subprocess.run(
            ["git", "-C", str(root), "rev-parse", "--abbrev-ref", "HEAD"],
            capture_output=True, text=True, check=True,
        ).stdout.strip()
        return out or None
    except (subprocess.CalledProcessError, FileNotFoundError):
        return None


def _match_by_prefix(specs_dir: Path, name: str) -> Path | None:
    """Map a branch/feature name to specs/<prefix>-* by its numeric prefix.

    Mirrors common.sh find_feature_dir_by_prefix. Exact dir name wins first.
    """
    exact = specs_dir / name
    if exact.is_dir():
        return exact
    m = PREFIX_RE.match(name)
    if not m:
        return None
    prefix = str(int(m.group(1)))  # normalize 007 -> 7 for comparison
    matches = []
    if specs_dir.is_dir():
        for child in sorted(specs_dir.iterdir()):
            if not child.is_dir():
                continue
            cm = PREFIX_RE.match(child.name)
            if cm and str(int(cm.group(1))) == prefix:
                matches.append(child)
    if len(matches) == 1:
        return matches[0]
    if len(matches) > 1:
        print(
            f"[companion] Warning: multiple spec dirs with prefix '{m.group(1)}': "
            f"{', '.join(c.name for c in matches)}; skipping ambiguous match",
            file=sys.stderr,
        )
    return None


def feature_dir_from_tasks_file(root: Path, tasks_file: str) -> Path:
    """The spec dir that owns a tasks.md is its parent directory.

    In task-sync mode the tasks file is authoritative: the spec whose task list
    was handed in is the spec to settle, regardless of which spec the active-
    feature pointer (env / feature.json / branch) currently names. This is what
    prevents settling the wrong spec when a later spec is "active"."""
    p = Path(tasks_file)
    if not p.is_absolute():
        p = root / p
    return p.parent


def resolve_feature_dir(root: Path, explicit: str | None) -> Path | None:
    """spec-kit resolution precedence, most-specific first."""
    specs_dir = root / "specs"

    # 1. explicit --feature-dir
    if explicit:
        p = Path(explicit)
        return p if p.is_absolute() else root / p

    # 2. SPECIFY_FEATURE_DIRECTORY env (a path)
    env_dir = os.environ.get("SPECIFY_FEATURE_DIRECTORY")
    if env_dir:
        p = Path(env_dir)
        return p if p.is_absolute() else root / p

    # 3. SPECIFY_FEATURE env (a feature name)
    env_feature = os.environ.get("SPECIFY_FEATURE")
    if env_feature:
        hit = _match_by_prefix(specs_dir, env_feature)
        if hit:
            return hit

    # 4. .specify/feature.json -> feature directory. Accept both the canonical
    #    `feature_directory` key and stock spec-kit's `FEATURE_DIR` (the upstream
    #    create-new-feature.sh shape) so a pointer written either way resolves —
    #    otherwise a bare call (e.g. --mark-complete with no --feature-dir) fails
    #    to find the spec even though the pointer is present.
    feature_json = root / ".specify" / "feature.json"
    if feature_json.is_file():
        try:
            data = json.loads(feature_json.read_text(encoding="utf-8"))
            fd = data.get("feature_directory") or data.get("FEATURE_DIR")
            if fd:
                p = Path(fd)
                return p if p.is_absolute() else root / p
        except (json.JSONDecodeError, OSError):
            pass

    # 5. git current branch -> numeric-prefix match
    branch = _git_branch(root)
    if branch:
        hit = _match_by_prefix(specs_dir, branch)
        if hit:
            return hit

    return None


def _spec_name(feature_dir: Path) -> str:
    spec_md = feature_dir / "spec.md"
    if spec_md.is_file():
        try:
            for line in spec_md.read_text(encoding="utf-8").splitlines():
                line = line.strip()
                if line.startswith("# "):
                    title = line[2:].strip()
                    # Drop a leading "Feature Specification:" / "Spec:" label.
                    title = re.sub(r"^(Feature Specification|Spec|Feature)\s*:\s*", "", title)
                    if title:
                        return title
        except OSError:
            pass
    # Fallback: humanized slug from the dir name (strip NNN- prefix).
    slug = PREFIX_RE.sub("", feature_dir.name)
    return slug.replace("-", " ").strip() or feature_dir.name


def _is_more_advanced(ctx: dict, step: str) -> bool:
    """True if the existing context already records a state past `step` — so a
    hook firing after an earlier step must not regress it."""
    if ctx.get("status") in TERMINAL_STATUSES:
        return True
    cur = ctx.get("currentStep")
    return cur in STEP_ORDER and STEP_ORDER[cur] > STEP_ORDER[step]


def read_ctx(target: Path) -> dict:
    """Read the existing context, tolerating absence or corruption."""
    if target.is_file():
        try:
            ctx = json.loads(target.read_text(encoding="utf-8"))
            if isinstance(ctx, dict):
                return ctx
        except (json.JSONDecodeError, OSError):
            pass
    return {}


def atomic_write(target: Path, ctx: dict) -> None:
    """Crash-safe write: serialize to a temp file, then rename over the target."""
    tmp = target.with_suffix(target.suffix + ".tmp")
    try:
        tmp.write_text(json.dumps(ctx, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
        os.replace(tmp, target)
    except OSError:
        try:
            tmp.unlink(missing_ok=True)  # don't litter on a failed write
        except OSError:
            pass
        raise


def canonical_log(ctx: dict) -> list:
    """The append-only lifecycle log. Canonical field is `history`; an older
    file may still carry the legacy `transitions` name — migrate it forward so
    both the extension and the VS Code GUI write the same single array."""
    log = ctx.get("history")
    if isinstance(log, list):
        return log
    legacy = ctx.get("transitions")
    if isinstance(legacy, list):
        return legacy
    return []


def commit_log(ctx: dict, log: list) -> None:
    """Persist the log under the canonical `history` key and drop the legacy
    `transitions` / derived `stepHistory` keys (the GUI derives stepHistory)."""
    ctx["history"] = log
    ctx.pop("transitions", None)
    ctx.pop("stepHistory", None)


def fill_required(ctx: dict, feature_dir: Path, branch: str) -> None:
    """Set required keys only when missing (read-then-merge preserves the rest)."""
    ctx.setdefault("workflow", "speckit")
    ctx.setdefault("specName", _spec_name(feature_dir))
    ctx.setdefault("branch", branch)


def _open_ctx_or_none(feature_dir: Path, step: str = "") -> tuple[dict, list, str] | None:
    """Read the context for a finish/journal write, returning `(ctx, log, branch)`
    primed (required keys filled, log migrated forward), or None for a spec that is
    already shipped (completed/archived) and must be left untouched. The shared
    read + cross-step-terminal guard + canonical_log + fill_required preamble of
    journal_finish / journal_task_finish / materialize_log."""
    ctx = read_ctx(feature_dir / ".spec-context.json")
    if ctx.get("status") in CROSS_STEP_TERMINAL:
        # Only the journal paths (which pass a step) announce the skip; materialize
        # passes step="" and stays silent, as it did before this preamble was shared.
        if step:
            print(
                f"[companion] {feature_dir / '.spec-context.json'} already at "
                f"status={ctx.get('status')} (not journaling {step}).",
                file=sys.stderr,
            )
        return None
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    log = canonical_log(ctx)
    fill_required(ctx, feature_dir, branch)
    return ctx, log, branch


def append_complete(
    log: list, step: str, *, substep: str | None = None, task: str | None = None,
    by: str, at: str,
) -> None:
    """Append a `complete` event for (step, substep|task) unless one already exists —
    the single home for the `if not _has_complete(...): log.append(...)` pattern.
    Key order: step, substep, [task], kind, by, at."""
    if not _has_complete(log, step, task if task is not None else substep):
        entry: dict = {"step": step, "substep": substep}
        if task is not None:
            entry["task"] = task
        entry.update({"kind": "complete", "by": by, "at": at})
        log.append(entry)


def _journaled_tasks(transitions: list) -> set[str]:
    """Task ids already recorded as per-task transitions (idempotency key)."""
    return {
        t["task"]
        for t in transitions
        if isinstance(t, dict) and isinstance(t.get("task"), str)
    }


def _entry_kind(e: dict) -> str:
    """The entry's kind. Legacy `transitions[]`/pre-`kind` migrated entries may
    carry no explicit `kind`; there the old convention is that a self-loop
    (`from.step == step` with the matching substep) is a completion and anything
    else is a start. Inferring it keeps the dedup correct on migrated specs."""
    k = e.get("kind")
    if k in ("start", "complete"):
        return k
    frm = e.get("from") or {}
    if frm.get("step") == e.get("step") and frm.get("substep") == e.get("substep"):
        return "complete"
    return "start"


def _is_step_level(e: dict) -> bool:
    """A step-level boundary entry: no substep and no per-task id. The single
    Python expression of the rule TypeScript's `isStepLevelEntry` owns."""
    return e.get("substep") is None and e.get("task") is None


def _is_per_task(e: dict) -> bool:
    """A per-task implement finish: carries a `task` id (`isPerTaskEntry`)."""
    return e.get("task") is not None


def _has_step_start(log: list, step: str, substep: object = None) -> bool:
    """True if a `start` for `(step, substep)` already exists. A step (or a folded
    substep entry) is started once; this collapses every redundant start — the
    GUI's startStep, the body's own start call, and the after_specify hook-start
    that lands AFTER the body already self-closed specify (which the old
    last-entry-only dedup missed, since the preceding entry was the complete). The
    `substep` arg keeps a folded fast-path start (substep="fast-path") idempotent
    without colliding with the step-level (substep None) start."""
    return any(
        isinstance(e, dict)
        and e.get("step") == step
        and e.get("substep") == substep
        and not _is_per_task(e)
        and _entry_kind(e) == "start"
        for e in log
    )


def _has_complete(log: list, step: str, task: object = None) -> bool:
    """True if a `complete` for (step, task) already exists. task=None matches the
    step-level complete (substep None); a task id matches that per-task complete.
    Per-task entries are keyed on `task` (the canonical id); a legacy record that
    still mirrors the id into `substep` matches via the fallback. Makes
    script-driven completes idempotent — it absorbs the GUI's guarded completeStep,
    re-runs, the per-task backstop double-writing a task, and a legacy self-loop
    completion entry on a migrated spec."""
    def _matches(e: dict) -> bool:
        if task is None:
            # Step-level complete only. A per-task finish now also has substep None
            # (the id lives in `task`), so it must NOT count as the step's complete —
            # otherwise the first task finish would skip the real step close and leave
            # the step permanently in-flight.
            return _is_step_level(e)
        return e.get("task") == task or e.get("substep") == task
    return any(
        isinstance(e, dict)
        and e.get("step") == step
        and _matches(e)
        and _entry_kind(e) == "complete"
        for e in log
    )
