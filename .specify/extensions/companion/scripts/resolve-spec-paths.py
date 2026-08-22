#!/usr/bin/env python3
"""Resolve living-spec paths for Companion capabilities.

Single source of truth for the rules the later Living Specs steps (sync / fold /
drift) call instead of re-interpreting the project's capability registry
(`living-specs.yml`, or the legacy `livingSpecs` block in `.specify/companion.yml`):

  - membership:  a file belongs to a capability if it matches any `match` glob
                 and no `exclude` glob.
  - path:        centralized -> `capabilities/<name>/spec.md` (default), or the
                 explicit `spec` path (colocated).
  - discovery:   union of configured capabilities and the on-disk scan of both
                 layouts (colocated `*.spec.md` and centralized
                 `capabilities/<name>/spec.md`), de-duped by resolved spec path
                 and by name.
  - boundary:    a subdirectory with its own capability registry (or legacy
                 `.specify/companion.yml`) is a separate project — the scan stops
                 there and never reports or promotes anything inside it.
  - ordering:    most-specific first (longest matching glob literal-prefix that
                 prefixes the file), tiebreak by capability name.
  - tiers:       `.spec.md` (hot, loaded in v1); `.arch.md` / `.coverage.md`
                 reserved siblings, never flagged as orphans.
  - orphans:     a spec of either layout in the tree not claimed by any capability.

OPT-IN: when `enabled` is unset/false (or there is no registry), the
resolver is inert — every mode returns empty with exit 0 and no error.

Usage:
  resolve-spec-paths.py --changed <file>...   # capabilities in scope (ordered)
  resolve-spec-paths.py --all                 # every capability (union) + orphans
  resolve-spec-paths.py --orphans             # unclaimed specs, either layout
  add --json for the machine-readable object; the default is a concise human
  list (capability names / orphan paths).
"""
from __future__ import annotations

import argparse
import json
import os
import re
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import companion_config as cc  # noqa: E402

# Map a tier key to the sibling suffix that replaces the hot `.spec.md` tail.
# Single source of truth for the reserved-tier filenames — RESERVED_TIERS (the
# orphan/drift exemption set) derives from it so the suffixes live in one place.
TIER_SUFFIXES = {"arch": ".arch.md", "coverage": ".coverage.md"}
RESERVED_TIERS = tuple(TIER_SUFFIXES.values())


def load_living(root: str) -> dict:
    """Load + normalize the project's capability registry."""
    return cc.resolve_living_specs(root)[0]


def load_living_with_meta(root: str):
    """`load_living` plus where the answer came from and any warnings to surface."""
    return cc.resolve_living_specs(root)


def _posix(p: str) -> str:
    return p.replace("\\", "/").replace(os.sep, "/")


def _literal_prefix(glob_pat: str) -> str:
    """Longest leading literal path of a glob (before the first wildcard).

    `src/checkout/**` -> `src/checkout`; stops at the first `*`/`?`/`[`.
    """
    out = []
    for ch in glob_pat:
        if ch in "*?[":
            break
        out.append(ch)
    return "".join(out).rstrip("/")


def _glob_to_regex(pat: str) -> str:
    """Translate a glob into a regex with POSIX-path semantics.

    `**` matches any depth (incl. zero), `*` matches within one segment (never
    crosses `/`), `?` one non-slash char. A trailing `/**` also matches the
    directory itself (`src/checkout/**` matches `src/checkout`).
    """
    out = ["^"]
    i, n = 0, len(pat)
    while i < n:
        c = pat[i]
        if c == "*":
            if i + 1 < n and pat[i + 1] == "*":
                if i + 2 == n and out and out[-1].endswith("/"):
                    # trailing `/**` — also match the bare directory: drop the
                    # `/` we already emitted and make the whole tail optional.
                    out[-1] = out[-1][:-1]
                    out.append("(?:/.*)?")
                    i += 2
                    continue
                # `**/` — consume the following slash; match any depth (incl. zero).
                if i + 2 < n and pat[i + 2] == "/":
                    out.append("(?:.*/)?")
                    i += 3
                    continue
                out.append(".*")
                i += 2
                continue
            out.append("[^/]*")
            i += 1
        elif c == "?":
            out.append("[^/]")
            i += 1
        else:
            out.append(re.escape(c))
            i += 1
    return "".join(out) + "$"


def _glob_matches(pat: str, f: str) -> bool:
    """Glob match with POSIX-path semantics (`*` never crosses `/`).

    `src/checkout/**` matches `src/checkout/cart/x.ts` AND `src/checkout` itself;
    `src/checkout/**/*.test.ts` matches only files ending `.test.ts` at any depth;
    `src/*.ts` matches only direct children, never nested files.
    """
    pat, f = _posix(pat), _posix(f)
    return re.match(_glob_to_regex(pat), f) is not None


def matches(cap: dict, f: str) -> bool:
    """File belongs to capability: any `match` glob, minus any `exclude` glob."""
    f = _posix(f)
    for ex in cap.get("exclude") or []:
        if _glob_matches(ex, f):
            return False
    return any(_glob_matches(pat, f) for pat in cap.get("match") or [])


def _specificity(cap: dict, f: str) -> int:
    """How specific this capability is for file f: longest matching-glob literal
    prefix that prefixes f. Deeper code area -> higher specificity."""
    f = _posix(f)
    best = 0
    for pat in cap.get("match") or []:
        if not _glob_matches(pat, f):
            continue
        lit = _literal_prefix(pat)
        if lit and (f == lit or f.startswith(lit + "/")):
            best = max(best, len(lit))
        else:
            best = max(best, 1)
    return best


def _location(cap: dict) -> str:
    expected = f"{cc.DEFAULT_CAPABILITY_ROOT}/{cap['name']}/spec.md"
    return "centralized" if _posix(cap.get("spec") or "") == expected else "colocated"


def _resolve_spec(cap: dict) -> str:
    """The capability's spec path. A colocated capability with no path is an error."""
    spec = cap.get("spec")
    if spec in (None, ""):
        raise ValueError(
            f'capability "{cap["name"]}" is colocated but has no resolvable spec path'
        )
    return spec


def tier_paths(spec: str, root: str | None = None) -> dict:
    """Derive a capability's reserved-tier sibling paths from its `spec` path.

    `capabilities/x/spec.md` -> arch `capabilities/x/spec.arch.md`,
    coverage `capabilities/x/spec.coverage.md`. Each entry carries the POSIX path
    and (when `root` is given) on-disk existence. Single source of truth for the
    tier filenames — the plan node and coverage checker reuse this rather than
    re-deriving `.arch.md`/`.coverage.md`.
    """
    spec = _posix(spec)
    # `<base>.spec.md` -> `<base>` (colocated `billing.spec.md` -> `billing`);
    # a plain `spec.md` (centralized `capabilities/x/spec.md`) keeps `spec` as
    # the base, so its siblings are `spec.arch.md` / `spec.coverage.md`.
    if spec.endswith(".spec.md"):
        base = spec[: -len(".spec.md")]
    elif spec.endswith(".md"):
        base = spec[: -len(".md")]
    else:
        base = spec
    out = {}
    for key, suffix in TIER_SUFFIXES.items():
        path = base + suffix
        entry = {"path": path}
        if root is not None:
            entry["exists"] = os.path.isfile(os.path.join(root, path))
        out[key] = entry
    return out


def _entry(cap: dict, root: str) -> dict:
    spec = _resolve_spec(cap)
    return {
        "name": cap["name"],
        "spec": spec,
        "location": _location(cap),
        "exists": os.path.isfile(os.path.join(root, spec)),
        "tiers": tier_paths(spec, root),
    }


def match_changed(files: list[str], living: dict, root: str) -> list[dict]:
    hits = []
    for cap in living["capabilities"]:
        hit_files = [f for f in files if matches(cap, f)]
        if not hit_files:
            continue
        entry = _entry(cap, root)
        entry["specificity"] = max(_specificity(cap, f) for f in hit_files)
        hits.append(entry)
    hits.sort(key=lambda e: (-e["specificity"], e["name"]))
    return hits


def _is_project_root(path: str) -> bool:
    return cc.is_project_root(path)


CENTRAL_SPEC_NAME = "spec.md"

VENDORED_DIRS = {"node_modules"}


def is_central_spec(rel: str) -> bool:
    """True for `<capability root>/<name>/spec.md` — the centralized layout.

    A central spec is named exactly `spec.md`, which does not end in `.spec.md`,
    so it needs its own shape test to enter discovery alongside colocated specs.
    """
    parts = _posix(rel).split("/")
    return (
        len(parts) == 3
        and parts[0] == cc.DEFAULT_CAPABILITY_ROOT
        and parts[2] == CENTRAL_SPEC_NAME
    )


def find_spec_files(root: str) -> list[str]:
    """Every living spec under `root` that belongs to `root`'s own project.

    Both layouts are candidates: colocated `*.spec.md` anywhere, and centralized
    `<capability root>/<name>/spec.md`.

    A subdirectory carrying its own capability registry (or legacy
    `.specify/companion.yml`) is a separate project: the walk prunes it and never
    looks inside, whatever that config says or fails to say. `root`'s own config
    is not a boundary against itself.
    Dot-directories, dotfiles, and vendored `node_modules` are excluded.
    """
    found = []
    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = sorted(
            d for d in dirnames
            if not d.startswith(".")
            and d not in VENDORED_DIRS
            and not _is_project_root(os.path.join(dirpath, d))
        )
        for name in filenames:
            if name.startswith("."):
                continue
            rel = os.path.normpath(os.path.relpath(os.path.join(dirpath, name), root))
            if not (name.endswith(".spec.md") or is_central_spec(rel)):
                continue
            found.append(rel)
    return sorted(found)


def _discovered_name(rel: str, taken: set[str]) -> str:
    """A distinct capability name for a discovered spec at `rel`.

    Prefers the parent directory name; on collision widens to more of the path
    until distinct, so two `notes/stray.spec.md` files stay individually
    addressable and never displace a configured capability.
    """
    parts = _posix(rel).split("/")
    dirs = parts[:-1]
    for i in range(1, len(dirs) + 1):
        widened = "/".join(dirs[-i:])
        if widened not in taken:
            return widened
    base = "/".join(dirs) if dirs else parts[-1][: -len(".spec.md")] or parts[-1]
    candidate, n = base, 2
    while candidate in taken:
        candidate = f"{base}-{n}"
        n += 1
    return candidate


def discover_all(living: dict, root: str, orphans: list[str] | None = None) -> list[dict]:
    out, seen, names = [], set(), set()
    for cap in living["capabilities"]:
        entry = _entry(cap, root)
        out.append(entry)
        seen.add(os.path.normpath(entry["spec"]))
        names.add(entry["name"])
    for rel in find_orphans(living, root) if orphans is None else orphans:
        norm = os.path.normpath(rel)
        if norm in seen:
            continue
        name = _discovered_name(norm, names)
        names.add(name)
        location = "centralized" if is_central_spec(norm) else "colocated"
        out.append({"name": name, "spec": _posix(norm), "location": location,
                    "exists": True, "tiers": tier_paths(_posix(norm), root)})
        seen.add(norm)
    out.sort(key=lambda e: e["name"])
    return out


def find_orphans(living: dict, root: str) -> list[str]:
    """A spec in this project — either layout — not claimed by, and not owned by, a capability.

    A spec is NOT an orphan when it is: the exact claimed `spec` path of a
    capability; a reserved-tier sibling (`.arch.md` / `.coverage.md`); or any
    spec living inside a configured capability's resolved spec directory
    (e.g. another file under `capabilities/checkout/`). A genuinely-unclaimed,
    differently-named spec elsewhere stays an orphan. `specs/` (feature specs)
    and every nested project are always excluded.
    """
    # _resolve_spec raises on an empty/missing spec, so --orphans surfaces the
    # same config error the --changed/--all paths do (the CLI contract).
    claimed = {os.path.normpath(_resolve_spec(c)) for c in living["capabilities"]}
    owned_dirs = {os.path.dirname(c) for c in claimed if os.path.dirname(c)}
    orphans = []
    for rel in find_spec_files(root):
        if rel.split(os.sep, 1)[0] == "specs":
            continue
        if any(rel.endswith(t) for t in RESERVED_TIERS):
            continue
        if rel in claimed:
            continue
        if any(rel == d or rel.startswith(d + os.sep) for d in owned_dirs):
            continue
        orphans.append(_posix(rel))
    return sorted(orphans)


def _fmt_list(items: list[str]) -> str:
    """Concise human list: `[a, b]` (matches the README examples)."""
    return "[" + ", ".join(items) + "]"


def render_human(result: dict) -> str:
    """Concise human-readable view of a result object.

    --changed -> `[name, name]` (most-specific first)
    --orphans -> `[path, path]`
    --all     -> capability names line + orphans line
    Empty modes print `[]` (no error), matching the inert/opt-out contract.
    """
    if "matched" in result:
        return _fmt_list([m["name"] for m in result["matched"]])
    if "capabilities" in result:
        caps = _fmt_list([c["name"] for c in result["capabilities"]])
        orphans = _fmt_list(result.get("orphans", []))
        return f"capabilities: {caps}\norphans: {orphans}"
    return _fmt_list(result.get("orphans", []))


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="Resolve Companion living-spec paths.")
    ap.add_argument("--root", default=".", help="repo root (default: cwd)")
    ap.add_argument("--changed", nargs="*", help="changed files -> capabilities in scope")
    ap.add_argument("--all", action="store_true", help="every capability (union) + orphans")
    ap.add_argument("--orphans", action="store_true", help="orphan spec files (either layout)")
    ap.add_argument("--json", action="store_true",
                    help="emit the machine-readable JSON object (default: a concise human list)")
    args = ap.parse_args(argv)
    root = args.root
    living = load_living(root)

    def emit(result: dict) -> None:
        print(json.dumps(result, indent=2) if args.json else render_human(result))

    if not living["enabled"]:
        if args.orphans:
            result = {"orphans": []}
        elif args.all:
            result = {"capabilities": [], "orphans": []}
        else:
            result = {"changed": args.changed or [], "matched": []}
        emit(result)
        return 0

    try:
        if args.orphans:
            result = {"orphans": find_orphans(living, root)}
        elif args.all:
            orphans = find_orphans(living, root)
            result = {"capabilities": discover_all(living, root, orphans),
                      "orphans": orphans}
        else:
            files = args.changed or []
            result = {"changed": files, "matched": match_changed(files, living, root)}
    except ValueError as exc:
        sys.stderr.write(f"resolve-spec-paths: {exc}\n")
        return 2
    emit(result)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
