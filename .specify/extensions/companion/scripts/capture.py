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
