#!/usr/bin/env python3
"""Append one Living-Specs capability to the project's capability registry (LS·5 adoption).

The deterministic half of the brownfield adoption wizard. The wizard's prose
drafts a living spec from a code area's surface; this helper does the one part
that must be exact and idempotent: register the confirmed capability so the
shipped resolver starts recognizing it.

The registry is `living-specs.yml` at the project root — deliberately outside
`.specify/`, which this project's routine cleanup (`git restore … .specify/`)
throws away and re-creates.

Contract (incremental, never a whole-repo bootstrap):
  - absent config      -> create a minimal well-formed registry (enabled: true)
                          carrying the one capability.
  - legacy config only -> migrate the whole set into the registry and remove the
                          `livingSpecs` block from `.specify/companion.yml`.
  - both files present -> the registry answers; the legacy block is left alone
                          (its capabilities were never carried over) and reported.
  - name not present   -> append the capability; every existing capability and
                          unrelated config is preserved.
  - name present       -> no-op; file byte-identical; reported on stderr.
  - malformed config   -> refuse to write (exit 2); the file is never truncated
                          or overwritten — fix the parse error first.

Reuses `companion_config` (the LS·1 reader) so the registry never diverges from
the parser the rest of the pipeline trusts. Stdlib only.

Usage:
  register-capability.py --name billing --match "src/billing/**" [--match …]
                         [--exclude …] [--spec capabilities/billing/spec.md]
                         [--root .] [--json]
"""
from __future__ import annotations

import argparse
import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import companion_config as cc  # noqa: E402

CONFIG_REL = cc.LIVING_SPECS_REL
LEGACY_CONFIG_REL = cc.LEGACY_CONFIG_REL


def _default_spec(name: str) -> str:
    return f"{cc.DEFAULT_CAPABILITY_ROOT}/{name}/spec.md"


def _normalize_existing(living: dict) -> list[dict]:
    """Turn the reader's normalized capabilities into emit-ready dicts, dropping
    the centralized `spec` default so re-emission stays terse and stable."""
    out = []
    for cap in living.get("capabilities", []):
        entry = {"name": cap["name"], "match": list(cap.get("match") or [])}
        if cap.get("exclude"):
            entry["exclude"] = list(cap["exclude"])
        if cap.get("spec") and cap["spec"] != _default_spec(cap["name"]):
            entry["spec"] = cap["spec"]
        out.append(entry)
    return out


def register(root: str, name: str, match: list[str], exclude: list[str],
             spec: str | None) -> dict:
    """Append one capability idempotently. Returns the result object.

    Raises ValueError on a malformed existing config (the CLI maps it to exit 2)
    so a file the reader can't fully parse is never overwritten."""
    config_path = os.path.join(root, CONFIG_REL)
    existed = os.path.isfile(config_path)

    # The constrained YAML emitter double-quotes scalars and the reader can't
    # unescape; a value with a quote/newline would emit invalid YAML and risk
    # corrupting the file. Reject up-front (CLI maps ValueError to exit 2).
    for val in [name, spec or "", *match, *(exclude or [])]:
        if val and ('"' in val or "\n" in val or "\r" in val):
            raise ValueError(
                f"unsupported character (quote/newline) in value: {val!r}"
            )

    living, meta = cc.resolve_living_specs(root)
    # Refuse rather than overwrite a file we couldn't parse (would drop the user's content).
    if meta["errors"]:
        raise ValueError(meta["errors"][0])
    legacy_path = os.path.join(root, LEGACY_CONFIG_REL)
    _, legacy_warnings = cc.load_config(legacy_path)
    if legacy_warnings:
        raise ValueError(legacy_warnings[0])

    capabilities = _normalize_existing(living)
    spec_path = spec or _default_spec(name)

    existing = next((c for c in capabilities if c["name"] == name), None)
    if existing is not None:
        # Idempotent: report what's ACTUALLY on disk, not the requested inputs
        # (a custom spec/match already registered must not be misreported).
        return {
            "name": name,
            "action": "already-registered",
            "spec": existing.get("spec") or _default_spec(name),
            "match": existing.get("match", []),
            "configPath": CONFIG_REL,
        }

    new_cap = {"name": name, "match": match}
    if exclude:
        new_cap["exclude"] = exclude
    if spec_path != _default_spec(name):
        new_cap["spec"] = spec_path
    capabilities.append(new_cap)

    # Preserve an existing registry's enabled flag; a project adopting for the first
    # time is born enabled so the registered capability actually resolves — that is
    # the whole point of the adoption wizard.
    enabled = living["enabled"] if meta["origin"] != "none" else True
    _write_registry(config_path, enabled, capabilities, living.get("exempt"))
    migrated = cc.should_drop_legacy(meta) and _drop_legacy_block(legacy_path)

    result = {
        "name": name,
        "action": "created" if not existed else "appended",
        "spec": spec_path,
        "match": match,
        "configPath": CONFIG_REL,
    }
    if migrated:
        result["migratedFrom"] = LEGACY_CONFIG_REL
    elif meta["legacy_stale"]:
        result["staleLegacy"] = LEGACY_CONFIG_REL
    return result


def _write_registry(config_path: str, enabled: bool, capabilities: list[dict],
                    exempt=None) -> None:
    """Write the registry, splicing into an existing file so its comments survive."""
    rendered = cc.render_registry(enabled, capabilities, exempt)
    if os.path.isfile(config_path):
        with open(config_path, encoding="utf-8") as fh:
            rendered = cc.splice_registry(fh.read(), rendered)
    parent = os.path.dirname(config_path)
    if parent:
        os.makedirs(parent, exist_ok=True)
    with open(config_path, "w", encoding="utf-8") as fh:
        fh.write(rendered)


def _drop_legacy_block(legacy_path: str) -> bool:
    """Remove the `livingSpecs` block from the legacy config, leaving siblings intact."""
    if not os.path.isfile(legacy_path):
        return False
    with open(legacy_path, encoding="utf-8") as fh:
        original = fh.read()
    lines = original.splitlines(keepends=True)
    start = next(
        (i for i, ln in enumerate(lines)
         if cc.is_top_level_key(ln) and ln.split(":", 1)[0].strip() == "livingSpecs"),
        None,
    )
    if start is None:
        return False
    remaining = "".join(lines[:start]) + "".join(lines[cc.block_end(lines, start):])
    with open(legacy_path, "w", encoding="utf-8") as fh:
        fh.write(remaining)
    return True


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="Append a Living-Specs capability to the registry.")
    ap.add_argument("--name", required=True, help="capability name (idempotency key)")
    ap.add_argument("--match", action="append", default=[], help="membership glob (repeatable)")
    ap.add_argument("--exclude", action="append", default=[], help="exclusion glob (repeatable)")
    ap.add_argument("--spec", default=None, help="spec path (default: capabilities/<name>/spec.md)")
    ap.add_argument("--root", default=".", help="repo root (default: cwd)")
    ap.add_argument("--json", action="store_true", help="emit the machine-readable result object")
    args = ap.parse_args(argv)

    if not args.match:
        sys.stderr.write("register-capability: --match is required (a capability with no match never resolves)\n")
        return 2

    try:
        result = register(args.root, args.name, args.match, args.exclude, args.spec)
    except ValueError as exc:
        sys.stderr.write(f"register-capability: refusing to write — {exc}\n")
        return 2

    if args.json:
        print(json.dumps(result, indent=2))
    elif result["action"] == "already-registered":
        sys.stderr.write(f"[companion] capability '{result['name']}' already registered — no change.\n")
    else:
        print(f"[companion] {result['action']} capability '{result['name']}' "
              f"({result['spec']}) in {result['configPath']}")
        if result.get("migratedFrom"):
            print(f"[companion] moved your capability registrations out of "
                  f"{result['migratedFrom']} into {result['configPath']} — commit it; "
                  f"it no longer sits where the routine cleanup step wipes it.")
        elif result.get("staleLegacy"):
            sys.stderr.write(
                f"[companion] {result['staleLegacy']} still lists capabilities. "
                f"{result['configPath']} is the registry, so those are ignored — move any "
                f"you still want into it, then delete the old livingSpecs block.\n")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
