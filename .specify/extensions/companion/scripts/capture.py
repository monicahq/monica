#!/usr/bin/env python3
"""The additive capture writers — what a run decided, verified, worried about, and covered.

Everything here merges onto .spec-context.json without touching the lifecycle log:
free-form fields, decisions, verifications,
concerns, expectations, context entries, requirement coverage, step summaries,
and the size classification. All de-duped, so a re-run never doubles up.

Stdlib only."""

from __future__ import annotations

import json
import sys
from datetime import datetime
import re
from pathlib import Path

from spec_context import (
    CANONICAL_STEPS,
    _git_branch,
    _now_iso,
    _repo_root_for,
    atomic_write,
    fill_required,
    read_ctx,
)

def _coerce_value(raw: str):
    """Coerce a `--set key=value` string into bool/int/None where it reads as one, else the string."""
    low = raw.lower()
    if low in ("true", "false"):
        return low == "true"
    if low in ("null", "none"):
        return None
    try:
        return int(raw)
    except ValueError:
        pass
    # A decimal point is required, so "1e5", "nan" and "inf" stay strings and
    # json.dumps never has to emit a value JSON cannot express.
    if re.fullmatch(r"-?\d+\.\d+", raw):
        return float(raw)
    return raw


# `request` is write-once, so only `set_request` may write it.
PROTECTED_SET_KEYS = frozenset({"history", "transitions", "status", "currentStep", "request"})
# Kept exactly as written: a ticket named "42" or "none" is still that ticket.
TEXT_SET_KEYS = frozenset({"source", "extends", "supersedes"})


def set_fields(feature_dir: Path, pairs: list[str]) -> Path | None:
    """Merge top-level `key=value` fields onto the existing context, leaving the
    lifecycle log (history, status, currentStep) untouched. Used by auto to record
    `unattended=true` without disturbing it. Lifecycle keys are refused so `--set`
    can never bypass the `--mark-complete` / hook-driven status writers."""
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    for pair in pairs:
        if "=" not in pair:
            print(f"[companion] Skipping malformed --set '{pair}' (expected key=value).", file=sys.stderr)
            continue
        key, raw = pair.split("=", 1)
        key = key.strip()
        if not key:
            continue
        if key in PROTECTED_SET_KEYS:
            print(f"[companion] Refusing --set '{key}' — lifecycle keys are managed by the capture/mark-complete writers.", file=sys.stderr)
            continue
        ctx[key] = raw.strip() if key in TEXT_SET_KEYS else _coerce_value(raw.strip())
    atomic_write(target, ctx)
    return target


# --- Reasoning-trail capture --------------------------------------------------
#
# Additive, de-duped, best-effort writers for the run's reasoning trail:
# decisions/verified/concerns (JSON-or-text entry lists), expectations (string
# list), coverage (per-requirement upsert), step_summaries (per-step upsert),
# classification (one object). None touch lifecycle keys; all are idempotent.


def _coerce_entry(raw: str, identity_key: str) -> dict | None:
    """JSON-or-plain-text coercion: a JSON object carrying `identity_key` is kept
    as-is (unknown keys preserved); anything else wraps the raw text under the
    identity key so a weak emitter still captures the signal."""
    text = raw.strip()
    if not text:
        return None
    if text.startswith("{"):
        try:
            obj = json.loads(text)
        except json.JSONDecodeError:
            obj = None
        if isinstance(obj, dict):
            ident = obj.get(identity_key)
            if isinstance(ident, str) and ident.strip():
                return obj
    return {identity_key: text}


def _code_at(path: Path) -> dict:
    """The short commit and a snapshot tree of the working files of the repo holding `path`.

    The snapshot goes through a copy of the index, so the user's staging is never touched;
    gitignored files stay out. Any failure leaves the field out rather than failing the check."""
    import os
    import shutil
    import subprocess
    import tempfile

    def git(*args, env=None):
        return subprocess.run(["git", "-C", str(path), *args], capture_output=True, text=True,
                              check=True, timeout=20, env=env).stdout.strip()

    found: dict = {}
    try:
        found["commit"] = git("rev-parse", "--short", "HEAD")
    except Exception:  # noqa: BLE001
        pass
    try:
        index = Path(git("rev-parse", "--git-path", "index"))
        index = index if index.is_absolute() else Path(path) / index
        with tempfile.TemporaryDirectory() as tmp:
            scratch = Path(tmp) / "index"
            if index.is_file():
                shutil.copy2(index, scratch)  # keeps the mtime git's racy-clean check compares against
            env = {**os.environ, "GIT_INDEX_FILE": str(scratch), "GIT_OPTIONAL_LOCKS": "0"}
            git("add", "-A", env=env)
            found["tree"] = git("write-tree", env=env)
    except Exception:  # noqa: BLE001
        pass
    return found


def run_verification(what: str, command: str, timeout: int = 900, at: Path | None = None) -> dict:
    """Run a command and record what actually happened, rather than what anyone says did.

    This is the whole difference between a receipt and a claim. Everything in `verified[]`
    until now was a sentence an agent typed, including the command, which was a string it
    wrote rather than evidence anything ran. The Overview then drew a checkmark beside it.

    The outcome is the exit code, because that is the part nobody can talk their way past.
    Output is kept only as a tail: enough to recognise what happened, never enough to make
    the context file a log.
    """
    import subprocess
    import time

    # Before the command, so a file changed while a long check runs makes it stale rather than current.
    code_at = _code_at(at) if at is not None else {}
    started = time.monotonic()
    try:
        proc = subprocess.run(command, shell=True, capture_output=True, text=True, timeout=timeout)
        code, out = proc.returncode, (proc.stdout or "") + (proc.stderr or "")
    except subprocess.TimeoutExpired:
        code, out = 124, f"timed out after {timeout}s"
    except Exception as err:  # noqa: BLE001
        code, out = 127, f"could not run: {err}"
    took = round(time.monotonic() - started, 1)

    tail = [ln for ln in out.strip().splitlines() if ln.strip()][-3:]
    entry = {
        "what": what,
        "command": command,
        "source": "derived",
        "exitCode": code,
        "durationSeconds": took,
        "result": " · ".join(tail) if tail else ("no output" if code == 0 else f"exit {code}"),
    }
    # A non-zero exit is the finding, not a failure to record. Recording it is the point:
    # a run that could not prove its work should say so where the reader looks.
    if code != 0:
        entry["warnings"] = [f"exited {code}"]
    entry.update(code_at)
    return entry


def append_verification_runs(feature_dir: Path, specs: list[str]) -> tuple[Path | None, list[str]]:
    """Run each `WHAT::COMMAND` and append what actually happened. Returns the target and any skips."""
    entries, skipped = [], []
    for spec in specs:
        what, sep, command = spec.partition("::")
        if not sep or not command.strip():
            skipped.append(spec)
            continue
        entries.append(json.dumps(run_verification(what.strip(), command.strip(), at=feature_dir)))
    target = append_capture_entries(feature_dir, "verified", "what", entries, replace=True) if entries else None
    if entries:
        import subprocess

        try:
            record_lowered_bar(feature_dir)
        except subprocess.CalledProcessError:
            pass  # not a git project: nothing to scan
        except Exception as err:  # noqa: BLE001
            print(f"[companion] Lowered-bar scan skipped: {err}", file=sys.stderr)
    return target, skipped


def record_lowered_bar(feature_dir: Path) -> Path | None:
    """Replace the previous scan's concerns with what the change adds now; other concerns stay."""
    import lowered_bar

    found = lowered_bar.concerns(lowered_bar.scan(_repo_root_for(feature_dir)))
    target = feature_dir / ".spec-context.json"
    ctx = read_ctx(target)
    prior = ctx.get("concerns") if isinstance(ctx.get("concerns"), list) else []
    kept = [c for c in prior if not (isinstance(c, dict) and c.get("source") == "scan")]
    if not found and len(kept) == len(prior):
        return None
    ctx["concerns"] = kept + found
    atomic_write(target, ctx)
    for concern in found:
        print(f"[companion] {concern['note']}", file=sys.stderr)
    return target


PLAN_CHECK_WHAT = "plan blocks check out"


PLAN_KINDS = (("calls", "blocks"), ("code", "code_blocks"), ("states", "state_blocks"), ("screens", "screens"))


def record_plan_report(feature_dir: Path, report: dict, started: float | None = None) -> Path | None:
    """Record a check_plan report as the derived verification and set planBlocks to the blocks the plan holds."""
    import time

    import check_plan

    held = [kind for kind, key in PLAN_KINDS if report[key]]
    ctx = read_ctx(feature_dir / ".spec-context.json")
    picked = ctx.get("planBlocksPicked")
    notes = []
    if isinstance(picked, list) and picked:
        notes = [f"picked {kind} but the plan has no {kind} block" for kind in picked if kind not in held]
        notes += [f"the plan has a {kind} block that was not picked" for kind in held if kind not in picked]
    for note in notes:
        print(f"[companion] Plan blocks: {note}.", file=sys.stderr)
    if not check_plan.has_blocks(report):
        return _set_plan_blocks(feature_dir, held) if picked else None
    root = _repo_root_for(feature_dir)
    try:
        shown = feature_dir.resolve().relative_to(root.resolve()).as_posix()
    except ValueError:
        shown = str(feature_dir)
    errors = report["errors"]
    tail = [ln for ln in check_plan.render_human(report).strip().splitlines() if ln.strip()][-3:]
    entry = {
        "what": PLAN_CHECK_WHAT,
        "command": f"python3 .specify/extensions/companion/scripts/check_plan.py --feature-dir {shown} --strict",
        "source": "derived",
        "exitCode": 1 if errors else 0,
        "durationSeconds": round(time.monotonic() - started, 1) if started is not None else 0,
        "result": " · ".join([*tail, *notes]),
    }
    if errors:
        entry["warnings"] = ["exited 1"]
        print(f"[companion] Plan blocks: {errors} error(s). Run check_plan.py --feature-dir {shown} to see them.",
              file=sys.stderr)
    path = append_capture_entries(feature_dir, "verified", "what", [json.dumps(entry)], replace=True)
    _set_plan_blocks(feature_dir, held)
    return path


def _set_plan_blocks(feature_dir: Path, held: list) -> Path | None:
    target = feature_dir / ".spec-context.json"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, _git_branch(_repo_root_for(feature_dir)) or "main")
    ctx["planBlocks"] = held
    atomic_write(target, ctx)
    return target


def record_plan_check(feature_dir: Path) -> Path | None:
    """Run check_plan in-process and record it as a derived verification; None when the plan has no checked block."""
    import time

    import check_plan

    started = time.monotonic()
    return record_plan_report(feature_dir, check_plan.check_feature(feature_dir), started)


def _entry_identity(item, identity_key: str) -> str | None:
    """The de-dup key for a stored entry: dicts key on identity_key, bare strings on themselves."""
    if isinstance(item, dict):
        v = item.get(identity_key)
        return v.strip() if isinstance(v, str) else None
    if isinstance(item, str):
        return item.strip()
    return None


def append_capture_entries(
    feature_dir: Path, field: str, identity_key: str, raws: list[str], replace: bool = False,
) -> Path | None:
    """De-duped additive append onto ctx[field] (decisions/verified/concerns).

    With `replace`, a new entry takes the place of an earlier one with the same
    identity: a check that was run again reports its latest result.

    Preserves first-seen order, normalizes pre-existing duplicates, never
    touches lifecycle keys. Bare strings already
    stored (hand-authored) participate in de-dup via their text."""
    entries = [e for e in (_coerce_entry(r, identity_key) for r in raws) if e]
    if not entries:
        return None
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    prior = ctx.get(field)
    merged: list = []
    seen: dict[str, int] = {}
    stored = list(prior) if isinstance(prior, list) else []
    for index, item in enumerate(stored + entries):
        ident = _entry_identity(item, identity_key)
        if ident is not None:
            if ident in seen:
                if replace and index >= len(stored):
                    merged[seen[ident]] = item
                continue
            seen[ident] = len(merged)
        merged.append(item)
    ctx[field] = merged
    atomic_write(target, ctx)
    return target


def _filled(value) -> bool:
    return isinstance(value, str) and bool(value.strip())


def _readable_time(value) -> bool:
    if not _filled(value) or "T" not in value:
        return False
    try:
        datetime.fromisoformat(value.strip().replace("Z", "+00:00"))
    except ValueError:
        return False
    return True


def _answer(entry: dict) -> dict:
    """What an earlier answer keeps of the decision it was."""
    return {k: entry[k] for k in ("decision", "why", "rejected", "by", "at") if k in entry}


def append_decisions(feature_dir: Path, raws: list[str]) -> Path | None:
    """Decisions, each stamped with who answered and when.

    An entry whose `id` is already stored with a different answer takes its place,
    and the stored answer moves to the end of its `earlier` list, so a reopened
    decision keeps every answer it had. Entries without an `id` de-dup on their
    text, as they always have."""
    entries = [e for e in (_coerce_entry(r, "decision") for r in raws) if e]
    if not entries:
        return None
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    prior = ctx.get("decisions")
    merged = list(prior) if isinstance(prior, list) else []
    now = _now_iso()
    changed = False
    for entry in entries:
        entry.pop("earlier", None)
        if not _filled(entry.get("by")):
            entry["by"] = "agent"
        if not _readable_time(entry.get("at")):
            entry["at"] = now
        answer = entry["decision"].strip()
        ident = entry.get("id")
        stored_at = next(
            (i for i, item in enumerate(merged)
             if _filled(ident) and isinstance(item, dict) and item.get("id") == ident),
            None,
        )
        if stored_at is not None:
            stored = merged[stored_at]
            if str(stored.get("decision", "")).strip() == answer:
                continue
            earlier = stored.get("earlier")
            earlier = [e for e in earlier if isinstance(e, dict)] if isinstance(earlier, list) else []
            entry["earlier"] = earlier + [_answer(stored)]
            merged[stored_at] = entry
            changed = True
            continue
        if not _filled(ident) and any(_entry_identity(item, "decision") == answer for item in merged):
            continue
        merged.append(entry)
        changed = True
    if not changed:
        return target
    ctx["decisions"] = merged
    atomic_write(target, ctx)
    return target


#: What the Companion workflow appends to each step's input on spec-kit's engine;
#: an instruction to the step, never part of what the person asked for.
ENGINE_STEP_MARKER = "(workflow engine step: run this step only, the engine runs the next one)"


def set_request(feature_dir: Path, text: str) -> tuple[Path | None, bool]:
    """Store the request the run started from, word for word, once.

    Only outer blank space and the engine step marker are trimmed. A stored request
    is never replaced, so the first entry point to record it wins and later steps
    cannot paraphrase it. Returns the record and whether this call wrote it; a blank
    request is (None, False)."""
    request = text.replace(ENGINE_STEP_MARKER, "").strip()
    if not request:
        return None, False
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    if _filled(ctx.get("request")):
        return target, False
    fill_required(ctx, feature_dir, branch)
    ctx["request"] = request
    atomic_write(target, ctx)
    return target, True


def append_string_list(feature_dir: Path, field: str, values: list[str]) -> Path | None:
    """De-duped additive append of plain strings onto ctx[field] (expectations)."""
    cleaned = [v.strip() for v in values if v and v.strip()]
    if not cleaned:
        return None
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    prior = ctx.get(field)
    merged: list = []
    for v in (list(prior) if isinstance(prior, list) else []) + cleaned:
        if v not in merged:
            merged.append(v)
    ctx[field] = merged
    atomic_write(target, ctx)
    return target


def _as_name_list(value) -> list | None:
    """A list as given, a comma-separated string split, anything else dropped."""
    if isinstance(value, str):
        value = value.split(",")
    if not isinstance(value, list):
        return None
    names = [str(v).strip() for v in value if str(v).strip()]
    return names or None


def upsert_coverage(
    feature_dir: Path, req: str, tasks: list[str] | None, tests: list[str] | None,
    title: str | None = None, assumes: str | None = None,
) -> Path | None:
    """Upsert ctx["coverage"][req] non-destructively (clone of _upsert_task_summary):
    only a supplied value replaces its slot, so the tasks-complete write (title +
    tasks), the implement-close write (tests) and an assumption (what the
    requirement relies on without a test) compose without erasing each other."""
    req = req.strip()
    if not req:
        return None
    # `--batch` hands these through verbatim, so a comma string arrives where the
    # flag path already split one. Stored as text, every reader of the field sees
    # no tests at all.
    tasks = _as_name_list(tasks)
    tests = _as_name_list(tests)
    title = title.strip() if title else None
    assumes = assumes.strip() if isinstance(assumes, str) else None
    if not tasks and not tests and not title and not assumes:
        # Nothing to record — writing {} would fake a coverage entry.
        return None
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    coverage = ctx.get("coverage")
    if not isinstance(coverage, dict):
        coverage = {}
    existing = coverage.get(req)
    entry: dict = dict(existing) if isinstance(existing, dict) else {}
    if title:
        entry["title"] = title
    if tasks:
        entry["tasks"] = tasks
    if tests:
        if tests != entry.get("tests"):
            entry.pop("weak", None)
        entry["tests"] = tests
    if assumes:
        entry["assumes"] = assumes
    coverage[req] = entry
    ctx["coverage"] = coverage
    atomic_write(target, ctx)
    return target


def upsert_step_summary(feature_dir: Path, step: str, raw: str) -> Path | None:
    """Upsert ctx["step_summaries"][step] from a JSON-or-text value keyed on `summary`."""
    if step not in CANONICAL_STEPS:
        print(
            f"[companion] Skipping --step-summary: '{step}' is not a canonical step "
            f"({', '.join(sorted(CANONICAL_STEPS))}).",
            file=sys.stderr,
        )
        return None
    entry = _coerce_entry(raw, "summary")
    if entry is None:
        return None
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    summaries = ctx.get("step_summaries")
    if not isinstance(summaries, dict):
        summaries = {}
    existing = summaries.get(step)
    merged: dict = dict(existing) if isinstance(existing, dict) else {}
    merged.update(entry)
    summaries[step] = merged
    ctx["step_summaries"] = summaries
    atomic_write(target, ctx)
    return target


CLASSIFICATION_VERDICTS = frozenset({"simple", "normal", "oversized"})


def _parsed_classification(raw: str) -> dict:
    """The classification object, or ValueError on an unparseable value or unknown
    verdict — the one caller-error case (main maps it to exit 2)."""
    try:
        obj = json.loads(raw)
    except json.JSONDecodeError as exc:
        raise ValueError(f"--classification is not valid JSON: {exc}") from exc
    if not isinstance(obj, dict) or obj.get("verdict") not in CLASSIFICATION_VERDICTS:
        raise ValueError(
            "--classification requires a JSON object with a verdict of simple|normal|oversized."
        )
    return obj


RISK_TIERS = ("low", "normal", "high")
RISK_HIGH_DEFAULT = ("**/migrations/**", "**/*.sql", "**/auth*/**", "**/auth*.*", ".github/workflows/**")
RISK_LOW_DEFAULT = ("**/*.md", "docs/**", "**/__tests__/**", "**/tests/**", "**/*.test.*", "**/*.spec.*")
RISK_MANY_FILES = 20


def _changed_files(root: Path) -> list[str] | None:
    """Files the change touches: the merge base with the default branch against the working tree,
    plus untracked files, outside the spec folders. None when git cannot say."""
    import subprocess

    def git(*args):
        return subprocess.run(["git", "-C", str(root), *args], capture_output=True, text=True,
                              check=True, timeout=20).stdout

    bases = ["origin/main", "main", "origin/master", "master"]
    try:
        bases.insert(0, git("symbolic-ref", "--short", "refs/remotes/origin/HEAD").strip())
    except Exception:  # noqa: BLE001
        pass
    for base in bases:
        try:
            mb = git("merge-base", base, "HEAD").strip()
        except Exception:  # noqa: BLE001
            continue
        if not mb:
            continue
        try:
            scope = ["--", ":(top)", ":(top,exclude)specs", ":(top,exclude).specify"]
            names = git("diff", "--name-only", "--no-renames", "-z", mb, *scope).split("\0")
            names += git("ls-files", "-z", "--others", "--exclude-standard", *scope).split("\0")
        except Exception:  # noqa: BLE001
            return None
        return sorted({n for n in names if n})
    return None


def _risk_globs(root: Path) -> tuple[list[str], list[str]]:
    high, low = list(RISK_HIGH_DEFAULT), list(RISK_LOW_DEFAULT)
    try:
        import companion_config
        cfg, _warnings = companion_config.load_config(str(root / companion_config.CONFIG_REL))
        risk = cfg.get("risk") if isinstance(cfg, dict) else None
        if isinstance(risk, dict):
            high += [g for g in risk.get("high") or [] if isinstance(g, str)]
            low += [g for g in risk.get("low") or [] if isinstance(g, str)]
    except Exception:  # noqa: BLE001
        pass
    return high, low


def _glob_to_regex(pat: str) -> str:
    """POSIX-path glob: `**` any depth (a trailing `/**` also the dir itself), `*` one segment, `?` one char."""
    out = ["^"]
    i, n = 0, len(pat)
    while i < n:
        c = pat[i]
        if c == "*" and i + 1 < n and pat[i + 1] == "*":
            if i + 2 == n and out and out[-1].endswith("/"):
                out[-1] = out[-1][:-1]
                out.append("(?:/.*)?")
                i += 2
            elif i + 2 < n and pat[i + 2] == "/":
                out.append("(?:.*/)?")
                i += 3
            else:
                out.append(".*")
                i += 2
        elif c == "*":
            out.append("[^/]*")
            i += 1
        elif c == "?":
            out.append("[^/]")
            i += 1
        else:
            out.append(re.escape(c))
            i += 1
    return "".join(out) + "$"


def glob_matches(pattern: str, path: str) -> bool:
    """Case-insensitive glob match on POSIX paths."""
    pattern, path = pattern.replace("\\", "/").lower(), path.replace("\\", "/").lower()
    return re.match(_glob_to_regex(pattern), path) is not None


def compute_risk(feature_dir: Path) -> tuple[str, list[str]]:
    """The tier the change's files call for, and why."""
    root = _repo_root_for(feature_dir)
    files = _changed_files(root)
    if files is None:
        return "normal", ["the change could not be read"]
    if not files:
        return "normal", ["no changes against the base branch"]
    high, low = _risk_globs(root)
    matches = glob_matches
    risky = [f for f in files if any(matches(g, f) for g in high)]
    if risky:
        shown = ", ".join(risky[:3]) + (f" and {len(risky) - 3} more" if len(risky) > 3 else "")
        return "high", [f"touches {shown}"]
    if len(files) > RISK_MANY_FILES:
        return "high", [f"{len(files)} files changed"]
    if all(any(matches(g, f) for g in low) for f in files):
        return "low", ["only docs and tests changed"]
    return "normal", [f"{len(files)} file{'s' if len(files) != 1 else ''} changed"]


def _tier_rank(tier) -> int:
    return RISK_TIERS.index(tier) if tier in RISK_TIERS else -1


def set_risk(feature_dir: Path) -> tuple[Path, dict]:
    """Compute and store the tier; a standing raise above the computed tier is kept."""
    computed, reasons = compute_risk(feature_dir)
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    prior = ctx.get("risk") if isinstance(ctx.get("risk"), dict) else {}
    risk = {"tier": computed, "computed": computed, "reasons": reasons}
    raised = prior.get("raised")
    if isinstance(raised, dict) and _tier_rank(raised.get("tier")) >= 0:
        risk["raised"] = raised
        if _tier_rank(raised["tier"]) > _tier_rank(computed):
            risk["tier"] = raised["tier"]
    ctx["risk"] = risk
    atomic_write(target, ctx)
    return target, risk


def raise_risk(feature_dir: Path, tier: str, reason: str, by: str = "ai") -> tuple[Path | None, str]:
    """Raise the tier with a reason. Returns (target, "") on success, or (None, why it was refused)."""
    tier, reason = (tier or "").strip().lower(), (reason or "").strip()
    if tier not in RISK_TIERS:
        return None, f"'{tier}' is not a tier (low, normal, high)"
    if not reason:
        return None, "a raise needs a reason"
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    risk = ctx.get("risk") if isinstance(ctx.get("risk"), dict) else None
    if risk is None or _tier_rank(risk.get("tier")) < 0:
        computed, reasons = compute_risk(feature_dir)
        risk = {"tier": computed, "computed": computed, "reasons": reasons}
    if _tier_rank(tier) <= _tier_rank(risk.get("tier")):
        return None, f"the tier is already {risk.get('tier')}; it can only be raised"
    fill_required(ctx, feature_dir, branch)
    risk = {**risk, "tier": tier, "raised": {"tier": tier, "reason": reason, "by": by, "at": _now_iso()}}
    ctx["risk"] = risk
    atomic_write(target, ctx)
    return target, ""


MUTANT_CAUGHT = ("Killed", "Timeout")
MUTANT_UNCOUNTED = ("Ignored", "CompileError", "RuntimeError", "Pending")


def _read_mutation_report(path: Path) -> dict:
    """Killed and total counts, the tool, and for Stryker which test files faced a mutant and
    which killed one. Raises ValueError for anything it cannot read as a report."""
    try:
        doc = json.loads(Path(path).read_text(encoding="utf-8"))
    except (OSError, ValueError, RecursionError) as exc:
        raise ValueError(f"cannot read {path}: {exc}") from exc
    try:
        report = _parse_mutation_report(doc)
    except (TypeError, AttributeError, KeyError, OverflowError) as exc:
        raise ValueError(f"{path} has an unexpected shape: {exc}") from exc
    if report is None:
        raise ValueError(f"{path} is neither a Stryker report nor a mutmut summary")
    if report["total"] <= 0:
        raise ValueError(f"{path} holds no mutants")
    return report


def _parse_mutation_report(doc) -> dict | None:
    if not isinstance(doc, dict):
        return None
    if isinstance(doc.get("files"), dict) and isinstance(doc.get("schemaVersion"), str):
        test_file_of = {}
        for file, entry in (doc.get("testFiles") or {}).items():
            for test in (entry or {}).get("tests") or []:
                if isinstance(test, dict) and "id" in test:
                    test_file_of[str(test["id"])] = file
        killed = total = 0
        killers: set = set()
        ran: set = set()
        for entry in doc["files"].values():
            for mutant in (entry or {}).get("mutants") or []:
                status = mutant.get("status") if isinstance(mutant, dict) else None
                if status in MUTANT_UNCOUNTED or not isinstance(status, str):
                    continue
                total += 1
                faced = [str(t) for t in list(mutant.get("coveredBy") or []) + list(mutant.get("killedBy") or [])]
                ran.update(test_file_of[t] for t in faced if t in test_file_of)
                if status in MUTANT_CAUGHT:
                    killed += 1
                    killers.update(test_file_of[str(t)] for t in mutant.get("killedBy") or [] if str(t) in test_file_of)
        return {"tool": "stryker", "killed": killed, "total": total, "killers": killers, "ran": ran,
                "root": doc.get("projectRoot") if isinstance(doc.get("projectRoot"), str) else None}
    if "killed" in doc and ("total" in doc or "survived" in doc):
        killed = int(doc.get("killed") or 0) + int(doc.get("timeout") or 0)
        outcomes = ("survived", "no_tests", "suspicious", "segfault")
        if doc.get("total"):
            total = int(doc["total"]) - int(doc.get("skipped") or 0)
        else:
            total = killed + sum(int(doc.get(k) or 0) for k in outcomes)
        return {"tool": "mutmut", "killed": killed, "total": total, "killers": set(), "ran": set(), "root": None}
    return None


def _rel(path: str, root: str | None = None) -> str:
    path = path.strip().replace("\\", "/")
    if root:
        prefix = root.replace("\\", "/").rstrip("/") + "/"
        if path.startswith(prefix):
            path = path[len(prefix):]
    while path.startswith("./"):
        path = path[2:]
    return path


def _test_file(ref: str) -> str:
    return _rel(ref.split("::", 1)[0])


def _same_file(ref: str, key: str) -> bool:
    """A coverage ref (repo-relative) and a report key (relative to where the tool ran) name one file."""
    return ref == key or ref.endswith("/" + key) or key.endswith("/" + ref)


def apply_mutation_report(feature_dir: Path, path: Path) -> tuple[Path, dict]:
    """Record the mutation check and mark weak each requirement whose test files faced the
    mutants and caught none. Only requirements this report judged have their marks replaced."""
    report = _read_mutation_report(path)
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    coverage = ctx.get("coverage") if isinstance(ctx.get("coverage"), dict) else {}
    ran = {_rel(f, report["root"]) for f in report["ran"]}
    killers = {_rel(f, report["root"]) for f in report["killers"]}
    weak, judged_any = [], False
    for req, entry in coverage.items():
        if not isinstance(entry, dict):
            continue
        files = {_test_file(t) for t in _as_name_list(entry.get("tests")) or []}
        faced = {k for k in ran if any(_same_file(f, k) for f in files)}
        if not faced:
            continue
        judged_any = True
        entry.pop("weak", None)
        if not (faced & killers):
            entry["weak"] = True
            weak.append(req)
    ctx["coverage"] = coverage
    row = {
        "what": "Mutation check",
        "command": report["tool"],
        "source": "derived",
        "result": f"{report['killed']} of {report['total']} caught",
    }
    warnings = []
    if weak:
        warnings.append(f"weak: {', '.join(sorted(weak))}")
    if report["tool"] == "stryker" and not judged_any:
        warnings.append("no requirement's tests matched the report, so none was judged")
    if warnings:
        row["warnings"] = warnings
    verified, placed = [], False
    for v in ctx.get("verified") or []:
        if (v.get("what") if isinstance(v, dict) else v) == "Mutation check":
            if not placed:
                verified.append(row)
                placed = True
            continue
        verified.append(v)
    ctx["verified"] = verified if placed else verified + [row]
    atomic_write(target, ctx)
    return target, {**report, "weak": sorted(weak)}


def set_classification(feature_dir: Path, raw: str) -> Path:
    """Store the size classification's inputs + verdict as one object."""
    obj = _parsed_classification(raw)
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    ctx["classification"] = obj
    atomic_write(target, ctx)
    return target


# --------------------------------------------------------------------------- #
# Batched end-of-step capture
#
# The capture flags are already additive and already compose in one call — the
# end-of-step volley is several calls only because the command bodies emit them
# separately. One document through the same writers changes the number of file
# rewrites, not the record.
# --------------------------------------------------------------------------- #

BATCH_KEYS = ("verified", "decisions", "concerns", "expectations", "context",
              "coverage", "step_summary", "last_action", "classification", "set")


def _parsed_batch(raw: str) -> dict:
    """Validate the batch document. A malformed payload is a caller error."""
    try:
        doc = json.loads(raw)
    except ValueError as exc:
        raise ValueError(f"--batch is not valid JSON: {exc}") from exc
    if not isinstance(doc, dict):
        raise ValueError("--batch must be a JSON object")
    unknown = sorted(set(doc) - set(BATCH_KEYS))
    if unknown:
        raise ValueError(
            f"--batch has unknown key(s): {', '.join(unknown)}. "
            f"Known keys: {', '.join(BATCH_KEYS)}."
        )
    for key in ("verified", "decisions", "concerns", "expectations", "context", "coverage"):
        if key in doc and not isinstance(doc[key], list):
            raise ValueError(f"--batch '{key}' must be a list")
    if "set" in doc and doc["set"] is not None:
        if not isinstance(doc["set"], dict):
            raise ValueError("--batch 'set' must be a map of field to value")
        bad = sorted(set(doc["set"]) & PROTECTED_SET_KEYS)
        if bad:
            raise ValueError(
                f"--batch 'set' cannot write lifecycle key(s): {', '.join(bad)} — "
                f"they are managed by the capture/mark-complete writers.")
        for key, val in doc["set"].items():
            if isinstance(val, (dict, list)):
                raise ValueError(
                    f"--batch 'set.{key}' must be a string, number or boolean "
                    f"(a nested value has nowhere to go in a flat field)")
    for item in doc.get("coverage") or []:
        if not isinstance(item, dict) or not item.get("req"):
            raise ValueError("--batch 'coverage' entries need a 'req' key")
    if "step_summary" in doc and not isinstance(doc["step_summary"], dict):
        raise ValueError("--batch 'step_summary' must be an object")
    return doc


def _as_raw(items: list) -> list:
    """Capture writers take JSON strings or bare text; pass objects through as JSON."""
    return [x if isinstance(x, str) else json.dumps(x, ensure_ascii=False) for x in items]


def apply_batch(feature_dir: Path, raw: str, step: str) -> tuple:
    """Apply the whole end-of-step volley, returning (target, [what landed])."""
    doc = _parsed_batch(raw)
    target, landed = None, []

    def note(result, label):
        nonlocal target
        if result is not None:
            target = result
            landed.append(label)

    decisions = doc.get("decisions")
    if decisions:
        note(append_decisions(feature_dir, _as_raw(decisions)), f"{len(decisions)} decisions")
    for field, key, identity in (
        ("verified", "verified", "what"),
        ("concerns", "concerns", "note"),
    ):
        items = doc.get(key)
        if items:
            note(append_capture_entries(feature_dir, field, identity, _as_raw(items)),
                 f"{len(items)} {key}")
    for field in ("expectations", "context"):
        items = doc.get(field)
        if items:
            note(append_string_list(feature_dir, field, [str(x) for x in items]),
                 f"{len(items)} {field}")
    for item in doc.get("coverage") or []:
        note(upsert_coverage(feature_dir, item["req"], item.get("tasks"),
                             item.get("tests"), item.get("title"), item.get("assumes")),
             f"coverage {item['req']}")
    summary = doc.get("step_summary")
    if summary:
        # The step is which slot to write, not part of the record. Passing it
        # through would store `{"step": …, "summary": …}` where the single-flag
        # form stores `{"summary": …}` — the two would not be byte-equivalent.
        body = {k: v for k, v in summary.items() if k != "step"}
        note(upsert_step_summary(feature_dir, summary.get("step") or step,
                                 json.dumps(body, ensure_ascii=False)),
             "step summary")
    if doc.get("last_action"):
        note(set_fields(feature_dir, [f"last_action={doc['last_action']}"]), "last_action")
    # `set` carries the plain fields a wrap-up would otherwise spend one call
    # each on — intent, approach, workflow, size, unattended. They are ordinary
    # `--set` pairs; batching them changes nothing about what lands, only how
    # many times the file is opened to land it.
    classification = doc.get("classification")
    if isinstance(classification, dict) and classification:
        note(set_classification(feature_dir, json.dumps(classification, ensure_ascii=False)),
             "classification")
    pairs = doc.get("set")
    if isinstance(pairs, dict) and pairs:
        note(set_fields(feature_dir, [f"{k}={v}" for k, v in pairs.items()
                                      if k not in TEXT_SET_KEYS or isinstance(v, str)]),
             f"{len(pairs)} field(s)")
    return target, landed
