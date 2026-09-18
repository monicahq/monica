#!/usr/bin/env python3
"""The additive capture writers — what a run decided, verified, worried about, and covered.

Everything here merges onto .spec-context.json without touching the lifecycle log:
free-form fields, loaded/synced living-spec names, decisions, verifications,
concerns, expectations, context entries, requirement coverage, step summaries,
and the size classification. All de-duped, so a re-run never doubles up.

Stdlib only."""

from __future__ import annotations

import json
import sys
import re
from pathlib import Path

from spec_context import (
    CANONICAL_STEPS,
    _git_branch,
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


PROTECTED_SET_KEYS = frozenset({"history", "transitions", "status", "currentStep"})


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
        ctx[key] = _coerce_value(raw.strip())
    atomic_write(target, ctx)
    return target


def set_living_specs_loaded(feature_dir: Path, names: list[str]) -> Path | None:
    """Record the capability names whose living specs were loaded into context.

    Merges onto ctx["livingSpecs"]["loaded"] preserving the resolver's
    most-specific-first order and de-duplicating, never rebuilding the record and
    never touching lifecycle keys (livingSpecs is additive metadata the strict
    schema already permits). With no names it is a no-op — opt-out writes nothing."""
    cleaned = [n.strip() for n in names if n and n.strip()]
    if not cleaned:
        return None
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    block = ctx.get("livingSpecs")
    if not isinstance(block, dict):
        block = {}
    prior = block.get("loaded")
    # De-dupe the FULL list (prior + new), preserving first-occurrence order, so a
    # record carrying pre-existing duplicates is normalized too (truly idempotent).
    merged: list = []
    for n in (list(prior) if isinstance(prior, list) else []) + list(cleaned):
        if n not in merged:
            merged.append(n)
    block["loaded"] = merged
    ctx["livingSpecs"] = block
    atomic_write(target, ctx)
    return target


def set_living_specs_loaded_requirements(feature_dir: Path, per_cap: dict) -> Path | None:
    """Record which REQUIREMENTS a run read, per capability.

    A sibling of `livingSpecs.loaded`, never a change to it: that list is a plain
    list of names several readers already consume — including the completion
    accounting that requires every loaded capability to end with a delta or a
    recorded skip — so widening its element type would break each of them for a
    feature none of them care about.

    Written only for a capability read by requirement. A capability read whole
    gets no entry, because naming all of its requirements would say nothing.
    Merges per capability, de-duping while preserving order, so a re-run is a
    no-op."""
    # An empty list is meaningful: the capability was consulted and its markers
    # all missed, which is a different fact from being read whole (no entry).
    cleaned = {
        str(cap).strip(): [str(h) for h in heads if str(h).strip()]
        for cap, heads in (per_cap or {}).items()
        if str(cap).strip() and heads is not None
    }
    if not cleaned:
        return None
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    block = ctx.get("livingSpecs")
    if not isinstance(block, dict):
        block = {}
    prior = block.get("loadedRequirements")
    merged = dict(prior) if isinstance(prior, dict) else {}
    for cap, heads in cleaned.items():
        seen: list = []
        for h in list(merged.get(cap) or []) + heads:
            if h not in seen:
                seen.append(h)
        merged[cap] = seen
    block["loadedRequirements"] = merged
    ctx["livingSpecs"] = block
    atomic_write(target, ctx)
    return target


def set_living_specs_rules(feature_dir: Path, rules: dict) -> Path | None:
    """Record the authored guidance a run was given, per step.

    A sibling of `livingSpecs.loaded` for the same reason as
    `loadedRequirements`: a reader asking "what was this run told?" should get
    the guidance beside the capabilities, and neither list changes shape for the
    other. A project with no rules writes nothing, so absence stays absence."""
    cleaned = {
        str(step).strip(): [str(line) for line in lines if str(line).strip()]
        for step, lines in (rules or {}).items()
        if str(step).strip() and lines
    }
    if not cleaned:
        return None
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    block = ctx.get("livingSpecs")
    if not isinstance(block, dict):
        block = {}
    prior = block.get("rules")
    merged = dict(prior) if isinstance(prior, dict) else {}
    for step, lines in cleaned.items():
        # The prior side was read off disk: coerce it, or a hand-corrupted
        # non-string entry raises inside a writer that must never fail a run.
        kept = [str(line) for line in (merged.get(step) or [])]
        merged[step] = list(dict.fromkeys(kept + lines))
    block["rules"] = merged
    ctx["livingSpecs"] = block
    atomic_write(target, ctx)
    return target


def set_living_specs_synced(feature_dir: Path, names: list[str]) -> Path | None:
    """Record the capability names whose living specs were folded into on completion.

    Mirrors set_living_specs_loaded: merges onto ctx["livingSpecs"]["synced"]
    de-duplicating and preserving first-seen order, never rebuilding the record
    and never touching lifecycle keys (livingSpecs is additive metadata kept OUT
    of the strict capture schema). With no names it is a no-op — opt-out and the
    no-delta case write nothing."""
    cleaned = [n.strip() for n in names if n and n.strip()]
    if not cleaned:
        return None
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    block = ctx.get("livingSpecs")
    if not isinstance(block, dict):
        block = {}
    prior = block.get("synced")
    merged: list = []
    for n in (list(prior) if isinstance(prior, list) else []) + list(cleaned):
        if n not in merged:
            merged.append(n)
    block["synced"] = merged
    ctx["livingSpecs"] = block
    atomic_write(target, ctx)
    return target


def set_living_specs_skipped(feature_dir: Path, entries: list[dict]) -> Path | None:
    """Record capabilities completion deliberately did NOT fold, with a reason.

    Mirrors set_living_specs_synced: merges onto ctx["livingSpecs"]["skipped"]
    (a list of {name, reason}), de-duped on the stripped name (first reason
    wins), never touching lifecycle keys. This is what turns "silently nothing"
    into "correctly nothing" — a loaded capability the change didn't alter records
    an explicit skip note here instead of leaving the fold to guess. A skip must
    both name a capability AND justify it: an entry with a blank reason is
    dropped, so an unexplained skip never counts as accountability (the capability
    stays unaccounted and the fold's backstop keeps nagging). With no valid
    entries it is a no-op."""
    cleaned: list[dict] = []
    for e in entries:
        if not isinstance(e, dict):
            continue
        name = str(e.get("name", "")).strip()
        reason = str(e.get("reason", "")).strip()
        if not name or not reason:
            continue
        cleaned.append({"name": name, "reason": reason})
    if not cleaned:
        return None
    target = feature_dir / ".spec-context.json"
    branch = _git_branch(_repo_root_for(feature_dir)) or "main"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, branch)
    block = ctx.get("livingSpecs")
    if not isinstance(block, dict):
        block = {}
    prior = block.get("skipped")
    merged: list = list(prior) if isinstance(prior, list) else []
    seen = {str(e.get("name", "")).strip() for e in merged if isinstance(e, dict)}
    for e in cleaned:
        if e["name"] not in seen:
            merged.append(e)
            seen.add(e["name"])
    block["skipped"] = merged
    ctx["livingSpecs"] = block
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


def run_verification(what: str, command: str, timeout: int = 900) -> dict:
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
    return entry


def append_verification_runs(feature_dir: Path, specs: list[str]) -> tuple[Path | None, list[str]]:
    """Run each `WHAT::COMMAND` and append what actually happened. Returns the target and any skips."""
    entries, skipped = [], []
    for spec in specs:
        what, sep, command = spec.partition("::")
        if not sep or not command.strip():
            skipped.append(spec)
            continue
        entries.append(json.dumps(run_verification(what.strip(), command.strip())))
    target = append_capture_entries(feature_dir, "verified", "what", entries) if entries else None
    return target, skipped


def _entry_identity(item, identity_key: str) -> str | None:
    """The de-dup key for a stored entry: dicts key on identity_key, bare strings on themselves."""
    if isinstance(item, dict):
        v = item.get(identity_key)
        return v.strip() if isinstance(v, str) else None
    if isinstance(item, str):
        return item.strip()
    return None


def append_capture_entries(
    feature_dir: Path, field: str, identity_key: str, raws: list[str],
) -> Path | None:
    """De-duped additive append onto ctx[field] (decisions/verified/concerns).

    Mirrors set_living_specs_loaded: preserves first-seen order, normalizes
    pre-existing duplicates, never touches lifecycle keys. Bare strings already
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
    seen: set[str] = set()
    for item in (list(prior) if isinstance(prior, list) else []) + entries:
        ident = _entry_identity(item, identity_key)
        if ident is not None:
            if ident in seen:
                continue
            seen.add(ident)
        merged.append(item)
    ctx[field] = merged
    atomic_write(target, ctx)
    return target


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


def upsert_coverage(
    feature_dir: Path, req: str, tasks: list[str] | None, tests: list[str] | None,
    title: str | None = None,
) -> Path | None:
    """Upsert ctx["coverage"][req] non-destructively (clone of _upsert_task_summary):
    only a supplied value replaces its slot, so the tasks-complete write (title +
    tasks) and the implement-close write (tests) compose without erasing each other."""
    req = req.strip()
    if not req:
        return None
    title = title.strip() if title else None
    if not tasks and not tests and not title:
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
        entry["tests"] = tests
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
              "coverage", "step_summary", "last_action", "set")


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

    for field, key, identity in (
        ("decisions", "decisions", "decision"),
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
                             item.get("tests"), item.get("title")),
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
    pairs = doc.get("set")
    if isinstance(pairs, dict) and pairs:
        note(set_fields(feature_dir, [f"{k}={v}" for k, v in pairs.items()]),
             f"{len(pairs)} field(s)")
    return target, landed
