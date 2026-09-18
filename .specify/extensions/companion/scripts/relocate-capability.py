#!/usr/bin/env python3
"""Migrate a Living-Specs capability between centralized and colocated storage (#460).

Until now the only way to move a capability was by hand: `git mv` the spec, remember
its `.arch.md` / `.coverage.md` siblings, then hand-edit the capability registry.
Miss either half and the shipped resolver raises
`capability "x" is colocated but has no resolvable spec path` — which fails the WHOLE
living-specs config, not just that capability. This helper does both halves as one
deterministic, all-or-nothing operation.

Contract:
  - validate everything first (paths derivable, no destination collisions), then move
    files, then write the config. A failed config write rolls the moves back, so the
    config and the disk are never out of sync.
  - idempotent: a capability already in the target layout is a reported no-op, exit 0.
  - `--all` migrates every capability; one that can't be resolved is skipped and
    reported, never aborting the run (exit 1 signals "partial").
  - a missing / malformed config, or an unknown capability, is a clear error — never
    a traceback (the "never fail the host" posture the sibling scripts keep).

Reuses `register-capability.py`'s renderer/splicer so the emitted YAML is byte-for-byte
the shape the adoption wizard writes, and `resolve-spec-paths.py`'s location + tier
rules so relocation can never disagree with the resolver. Stdlib only.

Usage:
  relocate-capability.py --name billing --to colocated [--spec src/billing/billing.spec.md]
  relocate-capability.py --name billing --to central
  relocate-capability.py --all --to colocated [--root .] [--json]
"""
from __future__ import annotations

import argparse
import importlib.util
import json
import os
import subprocess
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import companion_config as cc  # noqa: E402

CONFIG_REL = cc.LIVING_SPECS_REL
LEGACY_CONFIG_REL = cc.LEGACY_CONFIG_REL
SPEC_SUFFIX = cc.SPEC_SUFFIX


def _load_sibling(module_name: str, filename: str):
    """Import a hyphenated sibling script by path (same trick drift.py uses)."""
    path = os.path.join(os.path.dirname(os.path.abspath(__file__)), filename)
    spec = importlib.util.spec_from_file_location(module_name, path)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


rsp = _load_sibling("resolve_spec_paths", "resolve-spec-paths.py")
regcap = _load_sibling("register_capability", "register-capability.py")


class RelocateError(Exception):
    """A capability-level failure: reported, and under --all skipped rather than fatal."""


# --------------------------------------------------------------------------- #
# git detection (mirrors drift.py's `_git` / `_is_git_repo`)
# --------------------------------------------------------------------------- #
def _git(root: str, args: list[str]) -> tuple[int, str]:
    try:
        out = subprocess.run(
            ["git", "-C", root, *args], capture_output=True, text=True, check=False
        )
        return out.returncode, out.stdout
    except (OSError, FileNotFoundError):
        return 1, ""


def _is_git_repo(root: str) -> bool:
    code, out = _git(root, ["rev-parse", "--is-inside-work-tree"])
    return code == 0 and out.strip() == "true"


def _is_tracked(root: str, rel: str) -> bool:
    code, _ = _git(root, ["ls-files", "--error-unmatch", "--", rel])
    return code == 0


# --------------------------------------------------------------------------- #
# Path derivation
# --------------------------------------------------------------------------- #
def _posix(p: str) -> str:
    return rsp._posix(p)


def _default_spec(name: str) -> str:
    return regcap._default_spec(name)


def _display_name(spec: str) -> str:
    """The sidebar name the extension derives from a spec path.

    Mirrors `livingCapabilityName` in src/features/spec-viewer/livingDocs.ts: the file
    stem, or the parent directory when the stem is the bare `spec`. This is why moving
    a capability can silently rename it in the UI."""
    base = os.path.basename(_posix(spec))
    stem = base
    for suffix in (SPEC_SUFFIX, ".rules.md", ".arch.md", ".coverage.md", ".md"):
        if stem.endswith(suffix):
            stem = stem[: -len(suffix)]
            break
    if stem in ("", "spec"):
        return os.path.basename(os.path.dirname(_posix(spec)))
    return stem


def _common_dir(dirs: list[str]) -> str:
    """Shallowest common directory of a list of POSIX dirs ("" when they share none)."""
    split = [d.strip("/").split("/") for d in dirs]
    common: list[str] = []
    for parts in zip(*split):
        if len(set(parts)) != 1:
            break
        common.append(parts[0])
    return "/".join(common)


def _area_root(root: str, cap: dict) -> str:
    """The code-area directory a colocated spec should live in, derived from `match`.

    `src/features/spec-viewer/**` -> `src/features/spec-viewer`. With several globs the
    shallowest common directory wins (`src/a/**` + `src/b/**` -> `src`); globs spanning
    unrelated trees have no single area root and raise."""
    patterns = cap.get("match") or []
    if not patterns:
        raise RelocateError(
            f"capability '{cap['name']}' has no match globs — pass --spec explicitly"
        )
    dirs = []
    for pat in patterns:
        lit = _posix(rsp._literal_prefix(pat))
        if not lit:
            raise RelocateError(
                f"capability '{cap['name']}': glob '{pat}' has no literal directory "
                f"prefix, so the colocated directory can't be derived — "
                f"pass --spec explicitly"
            )
        # A glob that names a file (`src/store/index.ts`) contributes its directory.
        if os.path.isfile(os.path.join(root, lit)):
            lit = _posix(os.path.dirname(lit))
        if lit:
            dirs.append(lit)
    if not dirs:
        raise RelocateError(
            f"capability '{cap['name']}': no directory could be derived from its match "
            f"globs — pass --spec explicitly"
        )
    area = dirs[0] if len(set(dirs)) == 1 else _common_dir(dirs)
    if not area:
        raise RelocateError(
            f"capability '{cap['name']}': match globs span unrelated directories "
            f"({', '.join(sorted(set(dirs)))}) so there is no single area root — "
            f"pass --spec explicitly (e.g. --spec <dir>/{cap['name']}{SPEC_SUFFIX})"
        )
    return area


def _target_spec(root: str, cap: dict, to: str, spec_override: str | None) -> str:
    if spec_override:
        return _posix(spec_override)
    if to == "central":
        # `capabilities/<capability>/<name>.spec.md`, the shape adoption writes
        # and `living-move`'s own doc promises. `_default_spec` still answers the
        # legacy `capabilities/<name>/<name>.spec.md` so a registry written before the
        # rename keeps resolving; moving a capability is not the place to keep it.
        return f"{cc.DEFAULT_CAPABILITY_ROOT}/{cap['name']}/{cap['name']}{SPEC_SUFFIX}"
    area = _area_root(root, cap)
    why = _needs_central(root, cap, area)
    if why:
        # Colocated would put this spec in a folder full of other capabilities'
        # code. Adoption already sends such a capability central; moving it
        # must not undo that.
        print(f"[relocate] {cap['name']} stays central: {why}", file=sys.stderr)
        return f"{cc.DEFAULT_CAPABILITY_ROOT}/{cap['name']}/{cap['name']}{SPEC_SUFFIX}"
    return f"{area}/{cap['name']}{SPEC_SUFFIX}"


def _needs_central(root: str, cap: dict, area: str) -> str | None:
    """Why a colocated home would be wrong for this capability, or None.

    Two shapes have no folder of their own: globs over several sibling
    directories (the common parent belongs to all of them), and a glob over a
    whole area that other capabilities live inside (the layer capability)."""
    dirs = set()
    for pat in cap.get("match") or []:
        lit = _posix(rsp._literal_prefix(pat))
        if lit and os.path.isfile(os.path.join(root, lit)):
            lit = _posix(os.path.dirname(lit))
        if lit:
            dirs.add(lit)
    if len(dirs) > 1:
        return f"its globs cover {len(dirs)} sibling directories, so {area}/ is nobody's folder"
    try:
        others, _ = cc.resolve_living_specs(root)
    except Exception:  # noqa: BLE001 — best-effort; a bad registry is reported elsewhere
        return None
    for other in others.get("capabilities") or []:
        if other.get("name") == cap.get("name"):
            continue
        try:
            inner = _area_root(root, other)
        except RelocateError:
            continue
        if inner != area and inner.startswith(area + "/"):
            return f"{other['name']} lives inside {area}/, which makes this the layer's capability"
    return None


# --------------------------------------------------------------------------- #
# Planning
# --------------------------------------------------------------------------- #
def plan_capability(root: str, cap: dict, to: str, spec_override: str | None) -> dict:
    """Validate one relocation and return its plan. Raises RelocateError on anything
    that would leave config and disk disagreeing. Performs no writes."""
    name = cap["name"]
    location = rsp._location(cap)
    try:
        current = _posix(rsp._resolve_spec(cap))
    except ValueError as exc:
        # A colocated capability with an empty `spec:` already breaks the resolver.
        # Relocating it to central is exactly the repair, so allow that direction.
        if to != "central":
            raise RelocateError(str(exc)) from exc
        current = ""

    target = _target_spec(root, cap, to, spec_override)
    for value in (target,):
        if '"' in value or "\n" in value or "\r" in value:
            raise RelocateError(f"unsupported character (quote/newline) in path: {value!r}")

    already = (
        (to == "central" and location == "centralized")
        # Already colocated and no explicit destination asked for: leave it where the
        # user put it rather than second-guessing their filename.
        or (to == "colocated" and location == "colocated"
            and (spec_override is None or current == target))
    )
    if already:
        return {
            "name": name, "action": "already-" + to, "from": location,
            "to": location, "spec": current or target, "moves": [],
            "displayName": _display_name(current or target), "renamed": False,
        }

    if current == target:
        return {
            "name": name, "action": "already-" + to, "from": location,
            "to": location, "spec": target, "moves": [],
            "displayName": _display_name(target), "renamed": False,
        }

    # Every tier that exists moves with the hot spec, under the target's naming.
    src_tiers = rsp.tier_paths(current, None) if current else {}
    dst_tiers = rsp.tier_paths(target, None)
    pairs = []
    if current:
        pairs.append((current, target))
    for key in sorted(dst_tiers):
        src = src_tiers.get(key, {}).get("path")
        if src and os.path.isfile(os.path.join(root, src)):
            pairs.append((src, dst_tiers[key]["path"]))

    moves = []
    for src, dst in pairs:
        src_abs, dst_abs = os.path.join(root, src), os.path.join(root, dst)
        if not os.path.isfile(src_abs):
            continue  # only the hot tier can be absent; config-only relocation
        if os.path.exists(dst_abs):
            raise RelocateError(
                f"capability '{name}': destination '{dst}' already exists — "
                f"refusing to overwrite"
            )
        moves.append({"from": src, "to": dst})

    spec_on_disk = bool(current) and os.path.isfile(os.path.join(root, current))
    old_display = _display_name(current) if current else name
    new_display = _display_name(target)
    return {
        "name": name,
        "action": "relocate",
        "from": location,
        "to": "centralized" if to == "central" else "colocated",
        "specFrom": current,
        "spec": target,
        "moves": moves,
        "specMissing": not spec_on_disk,
        "displayNameFrom": old_display,
        "displayName": new_display,
        "renamed": old_display != new_display,
    }


# --------------------------------------------------------------------------- #
# Execution (moves, then config, with rollback)
# --------------------------------------------------------------------------- #
def _move(root: str, src: str, dst: str, use_git: bool) -> None:
    dst_abs = os.path.join(root, dst)
    os.makedirs(os.path.dirname(dst_abs) or root, exist_ok=True)
    if use_git and _is_tracked(root, src):
        code, _ = _git(root, ["mv", "--", src, dst])
        if code == 0:
            return
        # git refused (e.g. dirty index state) — the plain rename still preserves
        # content, and git will simply record it as delete+add.
    os.replace(os.path.join(root, src), dst_abs)


def _apply_moves(root: str, plans: list[dict], use_git: bool,
                 done: list[tuple[str, str]]) -> None:
    """Callers pass their own list so a move that raises part-way through still
    leaves them the set to roll back — a return value would never arrive."""
    for plan in plans:
        for mv in plan["moves"]:
            # Recorded before the attempt, not after: `_move` creates the
            # destination directories before it renames, so a rename that then
            # fails leaves those directories behind. Rollback is best-effort per
            # entry, so an entry whose move never landed costs one failed rename
            # and buys the directory pruning that restores the tree.
            done.append((mv["from"], mv["to"]))
            _move(root, mv["from"], mv["to"], use_git)


def _rollback(root: str, done: list[tuple[str, str]], use_git: bool) -> None:
    """Undo applied moves, newest first. Best-effort per file: one stubborn rename
    must not stop the rest of the tree from being restored."""
    for src, dst in reversed(done):
        try:
            _move(root, dst, src, use_git)
        except OSError:
            pass
    for src, dst in reversed(done):
        _prune_empty_dirs(root, os.path.dirname(dst))


def _prune_empty_dirs(root: str, rel_dir: str) -> None:
    """Remove directories we created for a destination that is now empty again."""
    while rel_dir and rel_dir not in (".", os.sep):
        abs_dir = os.path.join(root, rel_dir)
        if not os.path.isdir(abs_dir) or os.listdir(abs_dir):
            return
        try:
            os.rmdir(abs_dir)
        except OSError:
            return
        rel_dir = os.path.dirname(rel_dir)


def _write_config(config_path: str, original: str | None, enabled: bool,
                  capabilities: list[dict], exempt=None, rules=None) -> None:
    """Re-emit the registry through the shared renderer and splice it back, writing via
    a temp file + os.replace so a partial file never lands."""
    rendered = cc.render_registry(enabled, capabilities, exempt, rules)
    if original is not None:
        rendered = cc.splice_registry(original, rendered)
    cc.atomic_write_text(config_path, rendered)


def relocate(root: str, to: str, name: str | None = None, spec: str | None = None,
             every: bool = False) -> dict:
    """Plan, move, and rewrite the config as one atomic operation.

    Raises ValueError for run-level failures (no config, malformed config, unknown
    capability) — the CLI maps those to exit 2 with a plain message."""
    config_path = os.path.join(root, CONFIG_REL)
    legacy_path = os.path.join(root, LEGACY_CONFIG_REL)
    living, meta = cc.resolve_living_specs(root)
    # Errors first: a file that is present but unreadable must not be reported as absent.
    if meta["errors"]:
        raise ValueError(meta["errors"][0])
    if meta["origin"] == "none":
        raise ValueError(
            f"no {CONFIG_REL} in {os.path.abspath(root)} — nothing to relocate"
        )

    caps = living["capabilities"]
    if not caps:
        raise ValueError("no capabilities registered — nothing to relocate")

    if every:
        targets = list(caps)
    else:
        match = next((c for c in caps if c["name"] == name), None)
        if match is None:
            known = ", ".join(sorted(c["name"] for c in caps)) or "(none)"
            raise ValueError(f"unknown capability '{name}' — registered: {known}")
        targets = [match]

    # --- validate everything before touching a single file -------------------
    plans, skipped = [], []
    for cap in targets:
        try:
            plans.append(plan_capability(root, cap, to, None if every else spec))
        except RelocateError as exc:
            if not every:
                raise ValueError(str(exc)) from exc
            skipped.append({"name": cap["name"], "reason": str(exc)})

    moving = [p for p in plans if p["action"] == "relocate"]
    result = {
        "to": to, "configPath": CONFIG_REL,
        "relocated": moving,
        "unchanged": [p for p in plans if p["action"] != "relocate"],
        "skipped": skipped,
    }
    # A run that changes nothing still leaves the ignored legacy block looking authoritative.
    if meta["legacy_stale"]:
        result["staleLegacy"] = LEGACY_CONFIG_REL
    if not moving:
        return result

    # --- files first, then config; a failed config write rolls the files back --
    use_git = _is_git_repo(root)
    original = None
    if os.path.isfile(config_path):
        with open(config_path, encoding="utf-8") as fh:
            original = fh.read()

    done: list[tuple[str, str]] = []
    try:
        _apply_moves(root, moving, use_git, done)
        capabilities = regcap._normalize_existing(living)
        by_name = {p["name"]: p for p in moving}
        for entry in capabilities:
            plan = by_name.get(entry["name"])
            if plan is None:
                continue
            if plan["spec"] == _default_spec(entry["name"]):
                # Terse-by-default: the resolver fills the centralized path itself,
                # exactly as register-capability emits it.
                entry.pop("spec", None)
            else:
                entry["spec"] = plan["spec"]
        _write_config(config_path, original, living["enabled"], capabilities,
                      living.get("exempt"), living.get("rules"))
        if cc.should_drop_legacy(meta) and regcap._drop_legacy_block(legacy_path):
            result["migratedFrom"] = LEGACY_CONFIG_REL
    except BaseException:
        # BaseException, not Exception: Ctrl-C during a multi-file relocation is
        # exactly when a user reaches for it, and unwinding past this handler
        # would leave half the specs moved with the registry naming the old paths.
        _rollback(root, done, use_git)
        try:
            if original is None:
                # Absent before the run. If the write never landed there is
                # nothing to remove, and that is the ordinary case — not a
                # failed restore worth warning about.
                if os.path.exists(config_path):
                    os.remove(config_path)
            else:
                cc.atomic_write_text(config_path, original)
        except BaseException as exc:
            # Warn only when the registry on disk actually differs from what it
            # was before the run. A restore that failed because the write never
            # landed in the first place leaves everything consistent, and saying
            # otherwise sends the user hunting a problem that is not there.
            try:
                if os.path.exists(config_path):
                    with open(config_path, encoding="utf-8") as fh:
                        current = fh.read()
                else:
                    current = None
            except OSError:
                current = None
            if current != original:
                print(f"[companion] Rollback could not restore {config_path}: {exc}. "
                      f"The files were restored; the registry may still name the new paths.",
                      file=sys.stderr)
        raise
    for plan in moving:
        _prune_empty_dirs(root, os.path.dirname(plan["specFrom"]))
    return result


# --------------------------------------------------------------------------- #
# CLI
# --------------------------------------------------------------------------- #
def render_human(result: dict) -> str:
    lines = []
    for p in result["relocated"]:
        lines.append(
            f"[companion] relocated '{p['name']}' {p['from']} → {p['to']}: {p['spec']}"
        )
        for mv in p["moves"]:
            lines.append(f"             moved {mv['from']} → {mv['to']}")
        if p.get("specMissing"):
            lines.append("             note: no spec file on disk — config updated only")
        if p["renamed"]:
            lines.append(
                f"             ⚠ sidebar name changes: "
                f"'{p['displayNameFrom']}' → '{p['displayName']}' "
                f"(colocated capabilities are named after the file stem)"
            )
    for p in result["unchanged"]:
        lines.append(
            f"[companion] '{p['name']}' is already {p['to']} ({p['spec']}) — no change."
        )
    for s in result["skipped"]:
        lines.append(f"[companion] skipped '{s['name']}': {s['reason']}")
    if result.get("migratedFrom"):
        lines.append(
            f"[companion] moved your capability registrations out of "
            f"{result['migratedFrom']} into {result['configPath']} — commit it; "
            f"it no longer sits where the routine cleanup step wipes it."
        )
    elif result.get("staleLegacy"):
        lines.append(
            f"[companion] {result['staleLegacy']} still lists capabilities. "
            f"{result['configPath']} is the registry, so those are ignored — move any "
            f"you still want into it, then delete the old livingSpecs block."
        )
    return "\n".join(lines) or "[companion] nothing to do."


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(
        description="Migrate a Living-Specs capability between central and colocated storage."
    )
    ap.add_argument("--name", default=None, help="capability to relocate")
    ap.add_argument("--all", action="store_true", dest="every",
                    help="relocate every registered capability")
    ap.add_argument("--to", required=True, choices=["central", "colocated"],
                    help="target storage layout")
    ap.add_argument("--spec", default=None,
                    help="explicit destination path (overrides derivation; not with --all)")
    ap.add_argument("--root", default=".", help="repo root (default: cwd)")
    ap.add_argument("--json", action="store_true", help="emit the machine-readable result")
    args = ap.parse_args(argv)

    if bool(args.name) == bool(args.every):
        sys.stderr.write("relocate-capability: pass exactly one of --name or --all\n")
        return 2
    if args.spec and args.every:
        sys.stderr.write("relocate-capability: --spec applies to a single --name, not --all\n")
        return 2

    try:
        result = relocate(args.root, args.to, args.name, args.spec, args.every)
    except ValueError as exc:
        sys.stderr.write(f"relocate-capability: {exc}\n")
        return 2
    except OSError as exc:
        sys.stderr.write(f"relocate-capability: rolled back — {exc}\n")
        return 2

    if args.json:
        print(json.dumps(result, indent=2))
    else:
        print(render_human(result))
    # Partial success under --all: the run completed, but something needs --spec.
    return 1 if result["skipped"] else 0


if __name__ == "__main__":
    raise SystemExit(main())
