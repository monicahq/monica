#!/usr/bin/env python3
"""The living-spec fold-back: apply a feature spec's requirement deltas to the durable spec.

At mark-complete, the feature spec was the proposal and the living spec becomes
the record. Opt-in (livingSpecs.enabled), best-effort, and a clean no-op when the
feature spec carries no delta block.

Re-folding the same deltas is a no-op — ADDED resolves its heading through the
delta set's renames before both the existence check and the append, so it can
never re-add a section a RENAMED verb just renamed away from.

Stdlib only."""

from __future__ import annotations

import re
import subprocess
import sys
from pathlib import Path, PurePosixPath

from capture import append_capture_entries, set_living_specs_synced
from spec_context import _repo_root_for, feature_spec_path, read_ctx
from living_validate import (ADOPTED_LINE, ERROR, _fence_flags,
                             adopted_sources, check_feature_deltas,
                             fences_are_balanced)
from spec_deltas import _REQ_HEADING_RE, _has_deltas, parse_spec_deltas


_TOUCHES_LINE = re.compile(r"^\s*<!--\s*touches:\s*(.+?)\s*-->\s*$")
#: An edge to a rule under another capability. A delta never carries one, so it
#: survives a fold only by being carried across deliberately.
_ALIGNS_LINE = re.compile(r"^\s*<!--\s*aligns:\s*(.+?)\s*-->\s*$")


def _touches_globs(lines: list[str]) -> list[str]:
    """The globs on the marker directly under a `###` heading, or []."""
    if len(lines) < 2:
        return []
    m = _TOUCHES_LINE.match(lines[1])
    return [g.strip() for g in m.group(1).split(",") if g.strip()] if m else []


def _keep_marker(old: list[str], new: list[str]) -> list[str]:
    """Carry a requirement's file marker across a fold, widened, never narrowed.

    The replacement span covers the marker line, so a plain slice assignment
    deletes a marker `living-adopt` wrote the first time a feature folds into
    that requirement. A fold only ever learns about more files, so the two sets
    are unioned rather than replaced.
    """
    # An empty replacement section deletes the requirement block; there is no
    # heading left to hang a marker under.
    if not new:
        return new
    # A delta comes from a feature spec, which never adopts anything. Stripping
    # here means the only adopted markers in a living spec are the ones adoption
    # wrote, and a fold onto a requirement always clears them.
    rest = [ln for ln in new[1:] if not ADOPTED_LINE.match(ln)]
    # An `aligns` edge points at a rule under another capability, so no file match can
    # reach it and nothing would ever notice it going missing. A delta never carries one,
    # so dropping it here deletes it for good the first time a feature folds onto this
    # requirement — silently retiring the one edge the file match cannot rediscover.
    aligns = [ln for ln in old if _ALIGNS_LINE.match(ln)]
    rest = [ln for ln in rest if not _ALIGNS_LINE.match(ln)]
    globs = _touches_globs(old)
    globs += [g for g in _touches_globs(new) if g not in globs]
    if not globs:
        return [new[0]] + aligns + rest
    if rest and _TOUCHES_LINE.match(rest[0]):
        rest = rest[1:]
    return [new[0], f"<!-- touches: {', '.join(globs)} -->"] + aligns + rest


def _loaded_capabilities(feature_dir: Path) -> list[str]:
    """The capability names this feature recorded loading at specify time
    (livingSpecs.loaded), or []. Best-effort: any read/parse miss returns []."""
    try:
        ctx = read_ctx(feature_dir / ".spec-context.json")
    except Exception:  # noqa: BLE001 - best-effort
        return []
    loaded = (ctx.get("livingSpecs") or {}).get("loaded") or []
    return [c for c in loaded if isinstance(c, str) and c.strip()]


def _skipped_capability_names(feature_dir: Path) -> set[str]:
    """The capability names completion explicitly recorded skipping
    (livingSpecs.skipped[*].name), or empty. Best-effort."""
    try:
        ctx = read_ctx(feature_dir / ".spec-context.json")
    except Exception:  # noqa: BLE001 - best-effort
        return set()
    skipped = (ctx.get("livingSpecs") or {}).get("skipped") or []
    return {
        str(e.get("name", "")).strip()
        for e in skipped
        if isinstance(e, dict) and str(e.get("name", "")).strip()
    }


def _synced_capability_names(feature_dir: Path) -> set[str]:
    """The capability names already folded on a prior run (livingSpecs.synced),
    or empty. A re-fold writes nothing new (idempotent), so its in-run synced list
    is empty — but the capability IS accounted for. Best-effort."""
    try:
        ctx = read_ctx(feature_dir / ".spec-context.json")
    except Exception:  # noqa: BLE001 - best-effort
        return set()
    synced = (ctx.get("livingSpecs") or {}).get("synced") or []
    return {c.strip() for c in synced if isinstance(c, str) and c.strip()}


def _accountability_gap(feature_dir: Path, synced) -> list[str]:
    """Loaded capabilities completion neither folded nor recorded a skip for. The
    core accountability check — computed in BOTH fold branches so a partial
    multi-capability fold (one delta authored, another capability forgotten)
    can't silence the gap the way the no-delta-only check did. A capability counts
    as accounted if it was folded THIS run (`synced` arg), folded on a PRIOR run
    (persisted livingSpecs.synced — so an idempotent re-fold doesn't false-alarm),
    or explicitly skipped."""
    loaded = _loaded_capabilities(feature_dir)
    if not loaded:
        return []
    accounted = set(synced) | _synced_capability_names(feature_dir) | _skipped_capability_names(feature_dir)
    return [c for c in loaded if c not in accounted]


def _note_nothing_loaded(feature_dir: Path, root: Path) -> None:
    """Record, where a human will see it, that a configured project resolved nothing.

    stderr is where this used to end and end quietly. The doctor reads concerns and the
    panel shows them, so the one run that produced no living-spec work at all stops looking
    identical to one that had none to do. Silent on a project that does not use living
    specs, and best-effort throughout: this must never fail the host command.
    """
    try:
        if not (root / "living-specs.yml").exists():
            return
        append_capture_entries(feature_dir, "concerns", "note", [
            "This change resolved no living-spec capability, so nothing was loaded before it "
            "and nothing was folded after it. Either the areas it touched belong to no "
            "capability yet, or a capability claims them and no requirement describes them "
            "— `living-validate` names which."
        ])
    except Exception:  # noqa: BLE001
        pass


def _living_requirement_span(living_lines: list[str], heading: str) -> tuple[int, int] | None:
    """Find the [start, end) line span of a `### <heading>` requirement in a living
    spec, end being the next `###`/`##` or EOF. Heading match is exact (stripped).

    Fenced blocks are skipped, so a heading inside a code example is never
    matched or used as a boundary — the same rule the slicer, the coverage
    denominator and the shape check all count by. Counting differently here is
    how the empty-spec guard came to refuse a fold that had written
    requirements, and permit one that had removed them all.
    """
    heading = heading.strip()
    fenced = _fence_flags(living_lines)
    start = None
    for i, line in enumerate(living_lines):
        if fenced[i]:
            continue
        m = _REQ_HEADING_RE.match(line)
        if m and m.group(1).strip() == heading:
            start = i
            break
    if start is None:
        return None
    end = len(living_lines)
    for j in range(start + 1, len(living_lines)):
        if fenced[j]:
            continue
        s = living_lines[j]
        if s.startswith("### ") or s.startswith("## "):
            end = j
            break
    return start, end


def _rename_map(deltas: dict) -> dict[str, str]:
    """The delta set's old-heading -> new-heading map, first rename of a heading winning.

    Renames whose chain loops back on itself are dropped: a cycle names no final
    heading, so it is unsatisfiable input and skipped like any unmatched target."""
    mapping: dict[str, str] = {}
    for old_head, new_head in deltas["renamed"]:
        mapping.setdefault(old_head, new_head)
    cyclic: set[str] = set()
    for start in mapping:
        chain = [start]
        current = start
        while current in mapping:
            current = mapping[current]
            if current in chain:
                cyclic.update(chain)
                break
            chain.append(current)
    return {old: new for old, new in mapping.items() if old not in cyclic}


def _resolve_rename(heading: str, mapping: dict[str, str]) -> str:
    """Follow a rename chain to its end. The map is already cycle-free."""
    current = heading
    for _ in range(len(mapping) + 1):
        if current not in mapping:
            break
        current = mapping[current]
    return current


def _retitle(section: str, heading: str) -> str:
    """The same requirement section under a different `### ` heading."""
    heading = heading.strip()
    lines = section.splitlines()
    for i, line in enumerate(lines):
        if _REQ_HEADING_RE.match(line):
            lines[i] = re.sub(r"^(###\s+).+$", lambda m: m.group(1) + heading, line)
            break
    return "\n".join(lines) + "\n"


def apply_deltas(living_text: str, deltas: dict) -> tuple[str, dict]:
    """Apply ADDED/MODIFIED/REMOVED/RENAMED deltas to a living-spec text.

    ADDED appends a requirement, MODIFIED replaces the matching requirement in
    place, REMOVED deletes it, RENAMED rewrites the matching heading's name.
    Unmatched REMOVED/RENAMED targets are skipped (best-effort). An unmatched
    MODIFIED (no existing heading to replace) is promoted to ADDED and appended,
    counted under `promoted`, so a genuinely-new requirement authored under
    `## MODIFIED Requirements` still lands instead of being silently dropped.

    Re-applying a delta set to its own output is a no-op. ADDED runs last, so it
    resolves its heading through this delta set's renames before both the
    existence check and the append — otherwise it would re-add the section the
    RENAMED verb had just renamed away from, once per apply, without limit. A
    MODIFIED body for the same resolved heading wins over the ADDED body, so an
    add-plus-edit pair settles instead of alternating between the two.

    RENAMED also rewrites each heading straight to the end of its chain rather
    than one link at a time, so a chain lands in a single pass whatever order its
    links were declared in — applying them literally left a half-walked chain that
    moved again on the next fold.

    Returns the updated text and the per-verb count of what was applied."""
    lines = living_text.splitlines()
    applied = {"added": 0, "modified": 0, "removed": 0, "renamed": 0, "promoted": 0, "promoted_present": 0,
               "confirmed": 0}
    renames = _rename_map(deltas)
    modified_bodies = {head: section for head, section in deltas["modified"]}

    rename_targets = {_resolve_rename(old, renames) for old in renames}
    for old_head in renames:
        final_head = _resolve_rename(old_head, renames)
        span = _living_requirement_span(lines, old_head)
        if span is None:
            continue
        if _living_requirement_span(lines, final_head) is not None:
            continue  # the name is taken; renaming onto it would duplicate a heading
        lines[span[0]] = re.sub(
            r"^(###\s+).+$", lambda m: m.group(1) + final_head, lines[span[0]]
        )
        applied["renamed"] += 1

    readded = {_resolve_rename(head, renames) for head, _ in deltas["added"]}
    for head, _ in deltas["removed"]:
        if _resolve_rename(head, renames) in readded | rename_targets:
            continue  # contradictory input: the same set puts this heading back
        span = _living_requirement_span(lines, head)
        if span:
            del lines[span[0]:span[1]]
            applied["removed"] += 1

    promoted_modified: list[tuple[str, str]] = []
    for head, section in deltas["modified"]:
        span = _living_requirement_span(lines, head)
        if span:
            old_body = lines[span[0]:span[1]]
            body = _keep_marker(old_body, section.rstrip("\n").splitlines())
            # A requirement adoption transcribed is a claim about the code that
            # nothing has checked. A run that folds a delta onto it has now built
            # against it, so the claim is confirmed and the marker goes. `touches`
            # is carried across deliberately; everything else comes from the delta,
            # which is what promotes this one by dropping it.
            if adopted_sources(old_body) and not adopted_sources(body):
                applied["confirmed"] += 1
            if span[1] < len(lines):
                body.append("")  # keep the blank line separating the next requirement
            lines[span[0]:span[1]] = body
            applied["modified"] += 1
        else:
            promoted_modified.append((head, section))

    appended = "\n".join(lines).rstrip() + "\n"
    for head, section in deltas["added"]:
        target = _resolve_rename(head, renames)
        body = modified_bodies.get(target) or modified_bodies.get(head) or section
        if _living_requirement_span(appended.splitlines(), target) is not None:
            continue  # already present under its final heading
        appended = appended.rstrip() + "\n\n" + _retitle(body, target).rstrip() + "\n"
        applied["added"] += 1

    for head, section in promoted_modified:
        target = _resolve_rename(head, renames)
        if _living_requirement_span(appended.splitlines(), target) is not None:
            applied["promoted_present"] += 1  # redundant: the requirement is already there
            continue
        appended = appended.rstrip() + "\n\n" + _retitle(section, target).rstrip() + "\n"
        applied["promoted"] += 1
    return appended, applied


def _deltas_for(deltas: dict, cap_name: str, is_default: bool) -> dict:
    """The subset of `deltas` that belongs to one capability.

    A requirement unit belongs to `cap_name` when its block carried
    `<!-- capability: cap_name -->`. Unmarked units belong to the capability the
    changed files resolved to (`is_default`), so a plain delta block still folds
    into the matched capability. This is what keeps two blocks marked for
    different capabilities from bleeding requirements into each other."""
    unit_caps = deltas.get("unit_caps") or {}
    out: dict = {
        "added": [], "modified": [], "removed": [], "renamed": [], "markers": {},
        "unit_caps": {"added": [], "modified": [], "removed": [], "renamed": []},
    }
    for verb in ("added", "modified", "removed", "renamed"):
        caps = unit_caps.get(verb) or [None] * len(deltas[verb])
        for unit, cap in zip(deltas[verb], caps):
            if cap == cap_name or (cap is None and is_default):
                out[verb].append(unit)
                out["unit_caps"][verb].append(cap)
    return out


def _initial_living_spec(capability_name: str) -> str:
    """A minimal well-formed living-spec scaffold for a capability whose spec file
    doesn't exist yet, so the first ADDED fold creates a titled, sectioned spec
    (the accumulation story LS·4 relies on) rather than a headerless fragment.
    The slug is humanized into a title; ADDED requirements append under
    `## Requirements`."""
    title = capability_name.replace("-", " ").replace("_", " ").strip()
    if title:
        title = title[0].upper() + title[1:]
    else:
        title = capability_name
    return f"# {title} — Living Spec\n\n## Requirements\n"


def _git_changed_files(root: Path) -> list[str]:
    """Files this feature changed vs its merge-base with the default branch — committed, uncommitted and untracked.

    Best-effort: returns [] if git can't answer (detached/odd checkout, no
    merge-base). On the write-side fold, an empty result means the caller
    (`_resolve_fold_targets`) folds ONLY capabilities named by an explicit
    `<!-- capability: ... -->` marker and never fans out to every durable spec —
    the conservative choice for a write path."""
    for base in ("origin/main", "main", "origin/master", "master"):
        try:
            mb = subprocess.run(
                ["git", "merge-base", base, "HEAD"], cwd=str(root),
                capture_output=True, text=True, check=True,
            ).stdout.strip()
        except (subprocess.CalledProcessError, FileNotFoundError):
            continue
        if not mb:
            continue
        try:
            # Working tree against the merge-base, not HEAD against it: at the
            # moment the fold runs the feature is usually uncommitted, and a new
            # slice is untracked. Both are what this run changed.
            out = subprocess.run(
                ["git", "diff", "--name-only", mb], cwd=str(root),
                capture_output=True, text=True, check=True,
            ).stdout
            tracked = [x for x in out.splitlines() if x.strip()]
            dirs = sorted({str(PurePosixPath(f).parent) for f in tracked}) or ["."]
            out += subprocess.run(
                ["git", "ls-files", "--others", "--exclude-standard", "--", *dirs],
                cwd=str(root), capture_output=True, text=True, check=True,
            ).stdout
        except (subprocess.CalledProcessError, FileNotFoundError):
            return []
        return [f.strip() for f in out.splitlines() if f.strip()]
    return []


def _load_resolver():
    """Import the LS·1 resolver (hyphenated filename) by file path, or None."""
    try:
        import importlib.util
        path = Path(__file__).resolve().parent / "resolve-spec-paths.py"
        spec = importlib.util.spec_from_file_location("resolve_spec_paths", path)
        mod = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(mod)
        return mod
    except Exception:  # noqa: BLE001 - best-effort
        return None


def _resolve_fold_targets(rsp, living: dict, root: Path, deltas: dict) -> tuple[list[dict], str | None]:
    """Capabilities to fold into, plus the name of the changed-files-matched default.

    The targets are the most-specific match for the change surface, plus every
    capability named by a `<!-- capability: <name> -->` marker. The default is the
    matched head's name: unmarked delta units fold into it (marked units fold into
    their own capability), so each target's spec gets only its own requirements.

    Write-most-specific: from the (most-specific-first) matched list keep only the
    head. Markered targets are added by name from the full capability set, so a
    block can route to a different/additional capability than the code change
    resolves to. When git can't determine the changed files, this folds ONLY the
    markered capabilities (never fans out to every durable spec) and there is no
    default — the conservative choice for a write path; the no-changed-files
    fallback is logged so the markers-only narrowing is observable rather than a
    silent no-op."""
    changed = _git_changed_files(root)
    if not changed:
        print(
            "[companion] Living-spec fold: could not determine changed files; "
            "folding markered capabilities only (no fan-out to all durable specs).",
            file=sys.stderr,
        )
    matched = rsp.match_changed(changed, living, str(root)) if changed else []
    targets: list[dict] = []
    seen: set[str] = set()
    default_name: str | None = None
    if matched:
        head = matched[0]
        targets.append(head)
        seen.add(head["name"])
        default_name = head["name"]

    marker_names = {v for verb in deltas.get("unit_caps", {}).values() for v in verb if v}
    marker_names |= {v for v in deltas.get("markers", {}).values() if v}
    if marker_names:
        by_name = {c["name"]: c for c in living["capabilities"]}
        all_entries = rsp.discover_all(living, str(root))
        entry_by_name = {e["name"]: e for e in all_entries}
        for name in sorted(marker_names):
            if name in seen:
                continue
            entry = entry_by_name.get(name)
            if entry is None and name in by_name:
                try:
                    entry = rsp._entry(by_name[name], str(root))
                except Exception:  # noqa: BLE001
                    entry = None
            if entry is not None:
                targets.append(entry)
                seen.add(name)
    return targets, default_name


def _would_empty(after: str) -> bool:
    """True when the folded text carries no requirement at all.

    Counted by the same slicer every other reader uses, so a heading in an
    uncovered-files list is not mistaken for a requirement and a heading inside
    a fence is not counted at all.
    """
    if not fences_are_balanced(after):
        # Everything under an unclosed fence is invisible here, so the count is
        # not the spec's — refusing on it means refusing a fold that wrote
        # requirements, and advising a retirement that would then empty the file.
        return False
    rsp = _load_resolver()
    if rsp is None:
        return False  # unanswerable, and a guard that cannot count must not refuse
    try:
        return len(rsp.requirement_slices(after)) == 0
    except Exception:  # noqa: BLE001
        return False


def _shape_errors(spec_text: str, spec_rel: str, targets: list, root: Path,
                  default_name=None) -> dict:
    """Error-level shape findings in the feature spec's deltas, per capability.

    Imported and called in-process rather than run as a command: a correctness
    gate that a missing interpreter or a subprocess failing for its own reasons
    can turn into "no findings" is not a gate.
    """
    known = [c.get("name") for c in targets if c.get("name")]
    texts: dict = {}
    try:
        for cap in targets:
            # Inside the guard, and reading `name` the same way the line above
            # does. A target with no name raised straight out of here and took
            # the whole fold with it.
            rel, name = cap.get("spec"), cap.get("name")
            if not rel or not name:
                continue
            path = root / rel
            if path.exists():
                try:
                    texts[name] = path.read_text(encoding="utf-8")
                except OSError:
                    pass
        findings = check_feature_deltas(spec_text, spec_rel, known, texts, default_name)
    except Exception:  # noqa: BLE001
        return {}  # the check breaking must never block a sound fold
    out: dict = {}
    for f in findings:
        if f["severity"] != ERROR:
            # Not a block, but not silence either: a warning here is the one
            # chance to say "this ADDED heading restates one the spec already
            # has" BEFORE it becomes a permanent second requirement. A run that
            # folded two near-duplicates never saw this, because it was dropped.
            print(
                f"[companion] Living-spec fold: warning at {f['path']}:{f['line']} "
                f"[{f['code']}] {f['message']} {f['fix']}",
                file=sys.stderr,
            )
            continue
        out.setdefault(f.get("capability"), []).append(f)
    return out


def fold_living_spec(feature_dir: Path, by: str) -> Path | None:
    """Fold the feature spec's requirement deltas into the resolved living spec(s).

    Opt-in (livingSpecs.enabled) and best-effort: any miss (feature off, no
    config, no resolver, no delta block, no spec file) is a clean no-op that
    returns None and writes nothing. On a real fold, applies the deltas to each
    target's registered spec path, records the synced names onto
    livingSpecs.synced, logs a one-line per-capability summary, and returns the
    updated .spec-context.json path. Idempotent: re-running folds nothing new."""
    root = _repo_root_for(feature_dir)
    rsp = _load_resolver()
    if rsp is None:
        print(
            "[companion] Living-spec fold: the capability resolver is unavailable; nothing folded.",
            file=sys.stderr,
        )
        return None
    try:
        living = rsp.load_living(str(root))
    except Exception:  # noqa: BLE001
        print(
            "[companion] Living-spec fold: could not read the living-specs config; nothing folded.",
            file=sys.stderr,
        )
        return None
    if not living.get("enabled"):
        print(
            "[companion] Living-spec fold: living specs are off in this repo "
            "(livingSpecs.enabled is not true); nothing folded.",
            file=sys.stderr,
        )
        return None

    spec_md = feature_spec_path(feature_dir)
    try:
        spec_text = spec_md.read_text(encoding="utf-8")
    except OSError:
        print(
            f"[companion] Living-spec fold: could not read {spec_md}; nothing folded.",
            file=sys.stderr,
        )
        return None
    deltas = parse_spec_deltas(spec_text)
    if not _has_deltas(deltas):
        # The standard specify -> plan -> tasks pipeline never emits a delta
        # block, so a normally-built feature always lands here. Name the exact
        # reason and, when the feature loaded capabilities, make the miss
        # actionable instead of a silent success.
        loaded = _loaded_capabilities(feature_dir)
        if loaded:
            skipped = _skipped_capability_names(feature_dir)
            # Accounted = folded on a prior run (persisted synced) OR skipped, so a
            # re-fold of an already-synced spec doesn't false-alarm.
            unaccounted = _accountability_gap(feature_dir, [])
            if unaccounted:
                # The loud, actionable backstop: a capability was loaded, no delta
                # was authored, and no skip note explains why. This is the exact
                # "silently nothing" state — surface it, never bless it.
                print(
                    f"[companion] Living-spec fold: {len(loaded)} capabilit"
                    f"{'y' if len(loaded) == 1 else 'ies'} loaded "
                    f"({', '.join(loaded)}), 0 delta blocks, {len(skipped)} skip "
                    f"note(s) — {len(unaccounted)} unaccounted "
                    f"({', '.join(unaccounted)}). The loop did not close: for each, "
                    "author a delta block or record a skip "
                    '(write-context.py --living-spec-skip "<name>: <reason>").',
                    file=sys.stderr,
                )
            else:
                # Every loaded capability is accounted for — folded on an earlier
                # run and/or carrying an explicit skip note. "Correctly nothing,"
                # visibly distinct from the unaccounted case above.
                print(
                    f"[companion] Living-spec fold: all {len(loaded)} loaded "
                    f"capabilit{'y' if len(loaded) == 1 else 'ies'} "
                    f"({', '.join(loaded)}) {'is' if len(loaded) == 1 else 'are'} "
                    "accounted for (folded earlier or skipped); nothing to fold — "
                    "correctly nothing.",
                    file=sys.stderr,
                )
        else:
            # Nothing loaded is not the same as nothing to do. On a configured project it
            # means the run was briefed on nothing and wrote nothing back, which is exactly
            # the state living specs exist to prevent — and the one state the accountability
            # check above cannot see, because it has no loaded capability to hold to account.
            print(
                "[companion] Living-spec fold: this feature's spec carries no delta "
                "block and loaded no capabilities; nothing to fold.",
                file=sys.stderr,
            )
            _note_nothing_loaded(feature_dir, root)
        return None  # additive case — no delta block

    try:
        targets, default_name = _resolve_fold_targets(rsp, living, root, deltas)
    except Exception:  # noqa: BLE001
        targets, default_name = [], None
    if not targets:
        print(
            "[companion] Living-spec fold: no capability resolved for this change; "
            "nothing folded.",
            file=sys.stderr,
        )
        return None

    blocked = _shape_errors(
        spec_text, str(spec_md.relative_to(root)) if spec_md.is_relative_to(root) else str(spec_md),
        targets, root, default_name)

    # A block marked for a capability nobody registered is never a target, so
    # keying its refusal on that name made the refusal unreachable: the block
    # was dropped and the author was told nothing at all. Reported here, before
    # the loop, because there is no target to hang it on.
    target_names = {c.get("name") for c in targets}
    for name, findings in sorted(blocked.items(), key=lambda kv: str(kv[0])):
        if name in target_names:
            continue
        for f in findings:
            print(
                f"[companion] Living-spec fold: refused {name or '(unmarked)'} — "
                f"{f['message']} [{f['code']}] ({f['path']}:{f['line']})",
                file=sys.stderr,
            )

    # A well-formed block naming a capability nobody registered is not a target
    # either, and until now it was dropped without a word: the run wrote a
    # requirement, the fold said it synced, and the requirement was nowhere. New
    # behaviour with no home is exactly what a growing codebase produces, so say
    # so and name the command that gives it one.
    marked = {c for v in ("added", "modified", "removed", "renamed")
              for c in (deltas.get("unit_caps", {}).get(v) or []) if c}
    for name in sorted(marked - target_names):
        print(
            f"[companion] Living-spec fold: '{name}' is not a registered capability, "
            f"so its requirements were not folded. Register it and fold again: "
            f"register-capability.py --name {name} --match '<glob>'",
            file=sys.stderr,
        )

    synced: list[str] = []
    for cap in targets:
        spec_rel = cap.get("spec")
        if not spec_rel:
            continue
        cap_deltas = _deltas_for(deltas, cap["name"], cap["name"] == default_name)
        if not _has_deltas(cap_deltas):
            continue  # no requirement routed to this capability
        refused = blocked.get(cap["name"]) or []
        if refused:
            # Per capability, never the whole fold: one broken block must not
            # cost every sound one its write.
            for f in refused:
                print(
                    f"[companion] Living-spec fold: refused {cap['name']} — "
                    f"{f['message']} [{f['code']}] ({f['path']}:{f['line']})",
                    file=sys.stderr,
                )
            continue
        living_path = root / spec_rel
        try:
            before = living_path.read_text(encoding="utf-8") if living_path.exists() else _initial_living_spec(cap.get("name") or living_path.parent.name)
        except OSError:
            continue
        after, applied = apply_deltas(before, cap_deltas)
        if after != before and _would_empty(after) and not cap.get("retire"):
            # An emptied spec has lost the thing that made it worth keeping, and
            # a stale spec is recoverable where an empty one is not. Retiring a
            # capability is deliberate, so it has to be declared as one.
            print(
                f"[companion] Living-spec fold: refused {cap['name']} — this would "
                f"leave {spec_rel} with no requirements at all. Set `retire: true` on "
                f"{cap['name']} in living-specs.yml if that is intended.",
                file=sys.stderr,
            )
            continue
        unmatched = (
            (len(cap_deltas["modified"]) - applied["modified"] - applied["promoted"] - applied["promoted_present"])
            + (len(cap_deltas["removed"]) - applied["removed"])
            + (len(cap_deltas["renamed"]) - applied["renamed"])
        )
        if after == before:
            if unmatched:
                print(
                    f"[companion] Living-spec fold: {cap['name']} — {unmatched} "
                    f"delta(s) matched no requirement in {spec_rel}; nothing applied.",
                    file=sys.stderr,
                )
            else:
                print(
                    f"[companion] Living-spec fold: {cap['name']} already up to date "
                    f"({spec_rel}); no change.",
                    file=sys.stderr,
                )
            continue
        try:
            living_path.parent.mkdir(parents=True, exist_ok=True)
            living_path.write_text(after, encoding="utf-8")
        except OSError as exc:
            print(f"[companion] Living-spec fold: could not write {spec_rel}: {exc}", file=sys.stderr)
            continue
        synced.append(cap["name"])
        counts = (
            f"+{applied['added']} added, ~{applied['modified']} modified, "
            f"-{applied['removed']} removed, ↻{applied['renamed']} renamed"
        )
        already_present = len(cap_deltas["added"]) - applied["added"]
        reasons = []
        if applied["promoted"]:
            reasons.append(
                f"{applied['promoted']} added (MODIFIED with no existing match)"
            )
        if applied["confirmed"]:
            reasons.append(
                f"{applied['confirmed']} confirmed (adopted, and this run built against it)"
            )
        if unmatched:
            reasons.append(f"{unmatched} change(s) skipped: no matching requirement heading")
        if already_present:
            reasons.append(f"{already_present} addition(s) skipped: heading already present")
        note = f" — {'; '.join(reasons)}" if reasons else ""
        print(f"[companion] Living-spec fold: {cap['name']} ← {counts} ({spec_rel}){note}",
              file=sys.stderr)

    # Accountability applies even when SOME deltas were authored: a spec that
    # loaded several capabilities and folded only one still leaves the others
    # unaccounted. Check loaded − synced − skipped here too, not just on the
    # no-delta path, so a single delta block can't silence the gap.
    unaccounted = _accountability_gap(feature_dir, synced)
    if unaccounted:
        print(
            f"[companion] Living-spec fold: folded {len(synced)} capabilit"
            f"{'y' if len(synced) == 1 else 'ies'} ({', '.join(synced)}) but "
            f"{len(unaccounted)} loaded capabilit"
            f"{'y is' if len(unaccounted) == 1 else 'ies are'} neither folded nor "
            f"skipped ({', '.join(unaccounted)}). The loop did not close for "
            "those: author a delta block or record a skip "
            '(write-context.py --living-spec-skip "<name>: <reason>").',
            file=sys.stderr,
        )

    if not synced:
        return None
    return set_living_specs_synced(feature_dir, synced)
