#!/usr/bin/env python3
"""Resolve living-spec paths for Companion capabilities.

Single source of truth for the rules the later Living Specs steps (sync / fold /
drift) call instead of re-interpreting the project's capability registry
(`living-specs.yml`, or the legacy `livingSpecs` block in `.specify/companion.yml`):

  - membership:  a file belongs to a capability if it matches any `match` glob
                 and no `exclude` glob.
  - path:        centralized -> `capabilities/<name>/<name>.spec.md` (default), or the
                 explicit `spec` path (colocated).
  - discovery:   union of configured capabilities and the on-disk scan of both
                 layouts (colocated `*.spec.md` and centralized
                 `capabilities/<name>/<name>.spec.md`), de-duped by resolved spec path
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
#: The rules tier was called `.arch.md`, which promised structure and diagrams
#: and delivered a lint policy. `.rules.md` says what is in it. The old name
#: still resolves, so a project written before the rename keeps working.
TIER_SUFFIXES = {"rules": ".rules.md", "coverage": ".coverage.md"}
LEGACY_TIER_SUFFIXES = {"rules": ".arch.md"}
RESERVED_TIERS = tuple(TIER_SUFFIXES.values()) + tuple(LEGACY_TIER_SUFFIXES.values())


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


#: `<!-- touches: a/**, b.ts -->` — recognised only directly under a heading.
_TOUCHES_RE = re.compile(r"^\s*<!--\s*touches:\s*(.+?)\s*-->\s*$")
#: `<!-- adopted: CLAUDE.md:18 -->` — written by adoption, cleared by the fold.
_ADOPTED_RE = re.compile(r"^\s*<!--\s*adopted:\s*(.+?)\s*-->\s*$")
#: `<!-- aligns: session-access#Writing requires being signed in -->` — a
#: requirement that constrains this one but lives under another capability.
#: Every other edge points at code; this is the one that points at a rule.
_ALIGNS_RE = re.compile(r"^\s*<!--\s*aligns:\s*(.+?)\s*-->\s*$")


def requirement_slices(spec_text: str) -> list:
    """Every requirement in a spec, with the files its marker claims.

    The Python half of a parser that must exist twice — the viewer has no Python
    and the command bodies have no TypeScript. Both count the same headings off
    the same fence-stripped text and are pinned against
    `tests/fixtures/requirement-slices/`; a fixture only one side reads fails.
    """
    # Fences decide which `###` is a heading; they are never removed from the
    # text. Slicing a fence-stripped document would delete a code example from
    # the middle of a requirement, and the reader would have no way to tell.
    lines = spec_text.splitlines()
    fenced = _fence_flags(lines)

    def is_heading(k):
        return not fenced[k] and re.match(r"^###(?!#)\s+", lines[k])

    def is_section(k):
        return not fenced[k] and re.match(r"^##(?!#)\s+", lines[k])

    # Every `###` in the document, not just the ones under `## Requirements`.
    # Fold-back appends to the end of the file, so real specs carry requirements
    # past the Uncovered section; scoping to the section hid them from the load
    # while the coverage denominator still counted them.
    end = len(lines)
    out = []
    i = 0
    while i < end:
        head = re.match(r"^###(?!#)\s+(.+?)\s*$", lines[i]) if not fenced[i] else None
        if not head:
            i += 1
            continue
        # A requirement ends at the next requirement OR the next section
        # heading. Without the second, the last requirement before an uncovered
        # section swallows that whole section and hands it to a load step as its
        # own normative prose.
        j = i + 1
        while j < end and not is_heading(j) and not is_section(j):
            j += 1
        body = lines[i + 1:j]
        # The marker is the first non-blank line after the heading. It used to
        # have to be the line immediately after, which a markdown formatter
        # breaks: prettier puts a blank line between a heading and an HTML
        # comment, so one pre-commit hook silently unmarked every requirement
        # in a spec and every load quietly fell back to reading it whole (#690).
        # Still only the first non-blank line, so a marker discussed further
        # down is body, because a spec may legitimately discuss a marker.
        # Up to three markers may sit here, in any order, with blank lines among
        # them. All are parser metadata: handing any of them to a reader as prose
        # is a leak. Reading only the first line let the second marker leak.
        touches = adopted = aligns = None
        cut = 0
        for k, ln in enumerate(body):
            if not ln.strip():
                continue
            t = _TOUCHES_RE.match(ln); a = _ADOPTED_RE.match(ln); al = _ALIGNS_RE.match(ln)
            if not (t or a or al):
                break
            if t and touches is None:
                touches = [g.strip() for g in t.group(1).split(",") if g.strip()] or None
            if a and adopted is None:
                adopted = a.group(1).strip()
            if al and aligns is None:
                aligns = [g.strip() for g in al.group(1).split(",") if g.strip()] or None
            cut = k + 1
        body = body[cut:]
        item = {"heading": head.group(1), "touches": touches, "body": body}
        if adopted:
            item["adopted"] = adopted
        if aligns:
            item["aligns"] = aligns
        out.append(item)
        i = j
    return out


def requirements_for_change(slices: list, changed: list) -> list:
    """Requirements whose marker matches a changed file, plus every unmarked one.

    A marker can only narrow: an unmarked requirement is always contributed, so
    a missing or too-narrow marker costs a run an extra requirement rather than
    starving it of one.
    """
    files = [_posix(f) for f in changed]
    out = []
    for s in slices:
        if not s.get("touches"):
            out.append(s)
            continue
        if any(_glob_matches(g, f) for g in s["touches"] for f in files):
            out.append(s)
    return out


def has_no_markers(slices: list) -> bool:
    """True when a spec carries no marker at all, so it is read whole as before."""
    return all(not s.get("touches") for s in slices)


def purpose_section(spec_text: str) -> str:
    """The `## Purpose` section's text, or an empty string when there is none.

    Finds the boundaries while ignoring fenced blocks, but returns the original
    lines. Returning the fence-stripped text would silently delete a snippet
    from the middle of a purpose, and the reader would have no way to tell.
    """
    lines = spec_text.splitlines()
    fenced = _fence_flags(lines)
    start = next((i for i, l in enumerate(lines)
                  if not fenced[i] and re.match(r"^##\s+Purpose\s*$", l)), None)
    if start is None:
        return ""
    end = len(lines)
    for i in range(start + 1, len(lines)):
        if not fenced[i] and re.match(r"^##(?!#)\s+", lines[i]):
            end = i
            break
    return "\n".join(lines[start:end]).strip()


def _fence_flags(lines: list) -> list:
    """True for every line inside a fenced block, and for the fences themselves."""
    flags = []
    inside = False
    for line in lines:
        if re.match(r"^\s*(```|~~~)", line):
            inside = not inside
            flags.append(True)
            continue
        flags.append(inside)
    return flags


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
    """Centralized means "under the capability root", not one exact filename.

    A capability folder holds one spec or several granular ones, so equality
    with `capabilities/<name>/<name>.spec.md` called every split spec colocated and
    sent `living-move` the wrong way.
    """
    spec = _posix(cap.get("spec") or "")
    root = f"{cc.DEFAULT_CAPABILITY_ROOT}/"
    return "centralized" if spec.startswith(root) else "colocated"


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

    `capabilities/x/spec.md` -> rules `capabilities/x/spec.rules.md`,
    coverage `capabilities/x/spec.coverage.md`. Each entry carries the POSIX path
    and (when `root` is given) on-disk existence. Single source of truth for the
    tier filenames — the plan node and coverage checker reuse this rather than
    re-deriving `.rules.md`/`.coverage.md`.
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
            # A project written before the rename has the old name on disk, and
            # the tier it holds is the one being asked for.
            legacy = LEGACY_TIER_SUFFIXES.get(key)
            if legacy and not entry["exists"]:
                legacy_path = base + legacy
                if os.path.isfile(os.path.join(root, legacy_path)):
                    entry = {"path": legacy_path, "exists": True}
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
        # Carried through, because the fold's empty-spec guard reads it and the
        # entry is the only shape the fold ever sees.
        "retire": cap.get("retire") is True,
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

    --changed          -> `[name, name]` (most-specific first)
    --orphans          -> `[path, path]`
    --all              -> capability names line + orphans line
    --requirements-for -> one line per capability: whole, or its requirements
    Empty modes print `[]` (no error), matching the inert/opt-out contract.
    """
    if "matched" in result:
        return _fmt_list([m["name"] for m in result["matched"]])
    # Keyed on `changed`, not on `capabilities`: both shapes carry capabilities,
    # and falling into the --all branch printed an orphans line that was never
    # computed.
    if "changed" in result and "capabilities" in result:
        lines = []
        for c in result["capabilities"]:
            if c.get("whole"):
                lines.append(f"{c['name']}: whole")
            else:
                heads = [r["heading"] for r in c.get("requirements") or []]
                lines.append(f"{c['name']}: {_fmt_list(heads)}")
        return "\n".join(lines) if lines else "[]"
    if "capabilities" in result:
        caps = _fmt_list([c["name"] for c in result["capabilities"]])
        orphans = _fmt_list(result.get("orphans", []))
        return f"capabilities: {caps}\norphans: {orphans}"
    return _fmt_list(result.get("orphans", []))


def _follow_aligns(out: list, living: dict, root: str) -> list:
    """One hop along `aligns` edges. A requirement the file match found may name
    a rule under another capability; add that rule to that capability's entry,
    creating the entry if the files never touched it. One hop, never the
    targets' own targets, so a load stays bounded.
    """
    wanted: dict = {}
    for item in out:
        for req in item.get("requirements") or []:
            for ref in req.get("aligns") or []:
                cap_name, _, heading = ref.partition("#")
                if cap_name and heading:
                    wanted.setdefault(cap_name.strip(), set()).add(heading.strip())
    if not wanted:
        return out
    by_name = {i["name"]: i for i in out}
    caps = {c.get("name"): c for c in living.get("capabilities") or []}
    for cap_name, headings in wanted.items():
        cap = caps.get(cap_name)
        if not cap:
            continue
        spec_rel = _resolve_spec(cap)
        try:
            with open(os.path.join(root, spec_rel), encoding="utf-8") as fh:
                text = fh.read()
        except OSError:
            continue
        item = by_name.get(cap_name)
        if item is None:
            item = {"name": cap_name, "spec": spec_rel, "exists": True, "whole": False,
                    "purpose": purpose_section(text) or None, "requirements": []}
            out.append(item); by_name[cap_name] = item
        have = {r["heading"] for r in item["requirements"]}
        for sl in requirement_slices(text):
            if sl["heading"] in headings and sl["heading"] not in have:
                item["requirements"].append({
                    "heading": sl["heading"], "matched": False, "via": "aligns",
                    "body": "\n".join(sl.get("body") or []).strip(),
                })
    return out


def requirements_for_changed(files: list, living: dict, root: str, follow_aligns: bool = False) -> list:
    """What a load should contribute, per capability, for a set of changed files.

    A capability whose spec carries no marker anywhere is reported `whole`, and
    the caller reads the file exactly as it does today. Otherwise the caller gets
    the Purpose section plus the requirements to contribute — those whose marker
    matches, and every unmarked one.

    A capability whose markers all miss still appears, with its purpose and no
    requirements: it was consulted, and completion accounting must still see it.
    """
    out = []
    for entry in match_changed(files, living, root):
        spec_rel = entry.get("spec") or ""
        item = {
            "name": entry.get("name"),
            "spec": spec_rel,
            "exists": entry.get("exists", False),
            "whole": True,
            "purpose": None,
            "requirements": [],
        }
        if not item["exists"]:
            out.append(item)
            continue
        try:
            with open(os.path.join(root, spec_rel), encoding="utf-8") as fh:
                text = fh.read()
        except OSError:
            out.append(item)
            continue
        slices = requirement_slices(text)
        if has_no_markers(slices):
            out.append(item)
            continue
        picked = requirements_for_change(slices, files)
        item["whole"] = False
        item["purpose"] = purpose_section(text) or None
        # The body rides along. A heading alone is a table of contents, not the
        # normative prose and scenarios the load step exists to hand over.
        item["requirements"] = [
            {
                "heading": s["heading"],
                "matched": bool(s.get("touches")),
                "body": "\n".join(s.get("body") or []).strip(),
                **({"aligns": s["aligns"]} if s.get("aligns") else {}),
            }
            for s in picked
        ]
        out.append(item)
    return _follow_aligns(out, living, root) if follow_aligns else out


def capability_names(living: dict) -> list:
    """Every registered capability name, in registry order — what a miss lists back."""
    return [str(c.get("name")) for c in (living.get("capabilities") or []) if c.get("name")]


def capability_by_name(name: str, living: dict, root: str):
    """Resolve one registered capability to its spec path and text.

    Returns None when nothing is registered under that name. Three other answers
    stay distinct, because collapsing any two of them reports an empty spec:
    the file is missing (`exists` false), the file is there but could not be read
    (`readable` false — a permission error or non-UTF-8 bytes, never the same
    answer as absent), and the file was read (`text` set).
    """
    wanted = (name or "").strip().lower()
    for cap in living.get("capabilities") or []:
        if str(cap.get("name", "")).strip().lower() != wanted:
            continue
        entry = _entry(cap, root)
        text = None
        entry["readable"] = bool(entry.get("exists"))
        if entry.get("exists"):
            try:
                with open(os.path.join(root, entry["spec"]), encoding="utf-8") as fh:
                    text = fh.read()
            except (OSError, ValueError):
                # UnicodeDecodeError is a ValueError, not an OSError.
                entry["readable"] = False
        entry["text"] = text
        return entry
    return None


def _slices_of(entry: dict) -> list:
    """The requirement slices of a resolved capability, empty when it has no spec text."""
    return requirement_slices(entry.get("text") or "") if entry.get("text") else []


def _requirement_payload(cap_name: str, s: dict) -> dict:
    return {
        "capability": cap_name,
        "heading": s["heading"],
        "touches": s.get("touches"),
        "body": "\n".join(s.get("body") or []).strip(),
    }


def show_headings(name: str, living: dict, root: str) -> dict:
    """One capability's requirement headings — the table of contents, nothing else."""
    entry = capability_by_name(name, living, root)
    if entry is None:
        return {"show": "headings", "capability": name, "registered": False,
                "specExists": False, "requirements": [],
                "capabilities": capability_names(living)}
    return {
        "show": "headings",
        "capability": entry["name"],
        "registered": True,
        "specExists": bool(entry.get("exists")),
        "specReadable": bool(entry.get("readable")),
        "spec": entry.get("spec"),
        "requirements": [
            {"heading": s["heading"], "touches": s.get("touches")}
            for s in _slices_of(entry)
        ],
        "capabilities": capability_names(living),
    }


def show_requirement(name: str, living: dict, root: str, capability: str | None = None) -> dict:
    """One requirement in full, matched case-insensitively on trimmed text.

    An ambiguous name returns every candidate rather than picking one — a guess
    here would hand a reader another capability's rule under the name they asked for.
    """
    wanted = (name or "").strip().lower()
    scope = [capability] if capability else capability_names(living)
    matches, headings = [], []
    for cap_name in scope:
        entry = capability_by_name(cap_name, living, root)
        if entry is None:
            continue
        for s in _slices_of(entry):
            headings.append({"capability": entry["name"], "heading": s["heading"]})
            if s["heading"].strip().lower() == wanted:
                matches.append(_requirement_payload(entry["name"], s))
    return {"show": "requirement", "requested": name, "matches": matches,
            "headings": headings}


def show_for_file(path: str, living: dict, root: str) -> dict:
    """The requirements describing one file, grouped by capability, most-specific first."""
    caps = []
    for item in requirements_for_changed([path], living, root):
        entry = capability_by_name(item["name"], living, root)
        if item.get("whole"):
            # No marker anywhere means every requirement describes the file.
            reqs = [_requirement_payload(item["name"], s)
                    for s in _slices_of(entry or {})]
        else:
            reqs = [
                {"capability": item["name"], "heading": r["heading"],
                 "matched": r.get("matched"), "body": r.get("body", "")}
                for r in item.get("requirements") or []
            ]
        caps.append({"name": item["name"], "spec": item.get("spec"),
                     "exists": item.get("exists", False), "requirements": reqs})
    return {"show": "file", "file": path, "capabilities": caps}


def render_rules(rules: dict) -> str:
    """The authored guidance, per step. Silence when a project wrote none."""
    lines = []
    for step, items in (rules or {}).items():
        for item in items:
            lines.append(f"{step}: {item}")
    return "\n".join(lines) if lines else "no rules authored"


def render_show(result: dict) -> str:
    """The human view of a slice. One answer per line, never a JSON dump."""
    mode = result.get("show")
    if mode == "headings":
        if not result.get("registered"):
            names = _fmt_list(result.get("capabilities") or [])
            return f"{result['capability']}: not a registered capability; registered: {names}"
        if not result.get("specExists"):
            return f"{result['capability']}: registered, but no spec file on disk"
        if not result.get("specReadable"):
            return f"{result['capability']}: spec file could not be read"
        reqs = result.get("requirements") or []
        head = f"{result['capability']}: {len(reqs)} requirements"
        return "\n".join([head] + [f"- {r['heading']}" for r in reqs])
    if mode == "requirement":
        matches = result.get("matches") or []
        if len(matches) == 1:
            m = matches[0]
            return f"### {m['heading']}  ({m['capability']})\n\n{m['body']}".rstrip()
        if len(matches) > 1:
            candidates = "\n".join(f"- {m['heading']}  ({m['capability']})" for m in matches)
            return (f'"{result["requested"]}" names more than one requirement:\n'
                    f"{candidates}\n\nName the capability to pick one.")
        heads = result.get("headings") or []
        if not heads:
            return f'"{result["requested"]}": nothing to search — no living spec is readable'
        listing = "\n".join(f"- {h['heading']}  ({h['capability']})" for h in heads)
        return f'"{result["requested"]}" matches no requirement. These exist:\n{listing}'
    caps = result.get("capabilities") or []
    if not caps:
        return f"{result['file']}: no living spec claims this file"
    lines = []
    for c in caps:
        reqs = c.get("requirements") or []
        lines.append(f"{c['name']} ({c.get('spec')}): {len(reqs)} requirements")
        lines.extend(f"- {r['heading']}" for r in reqs)
    return "\n".join(lines)


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="Resolve Companion living-spec paths.")
    ap.add_argument("--root", default=".", help="repo root (default: cwd)")
    ap.add_argument("--changed", nargs="*", help="changed files -> capabilities in scope")
    ap.add_argument("--all", action="store_true", help="every capability (union) + orphans")
    ap.add_argument("--orphans", action="store_true", help="orphan spec files (either layout)")
    ap.add_argument("--follow-aligns", action="store_true",
                    help="with --requirements-for: also load each requirement a matched one aligns with (one hop)")
    ap.add_argument("--requirements-for", action="store_true",
                    help="with --changed: what each capability should contribute, sliced by requirement")
    ap.add_argument("--headings", metavar="CAPABILITY",
                    help="print one capability's requirement headings")
    ap.add_argument("--requirement", metavar="NAME",
                    help="print one requirement in full, by heading")
    ap.add_argument("--file", metavar="PATH",
                    help="print the requirements whose marker describes this file")
    ap.add_argument("--capability", metavar="NAME",
                    help="with --requirement: search only this capability")
    ap.add_argument("--rules", action="store_true",
                    help="the registry's authored per-step guidance")
    ap.add_argument("--json", action="store_true",
                    help="emit the machine-readable JSON object (default: a concise human list)")
    args = ap.parse_args(argv)
    root = args.root
    living = load_living(root)

    def emit(result: dict) -> None:
        if args.json:
            print(json.dumps(result, indent=2))
        elif args.rules:
            print(render_rules(result["rules"]))
        elif "show" in result:
            print(render_show(result))
        else:
            print(render_human(result))

    if not living["enabled"]:
        if args.rules:
            result = {"rules": cc.load_rules(None)}
        elif args.headings:
            result = {"show": "headings", "capability": args.headings, "registered": False,
                      "specExists": False, "requirements": [], "capabilities": []}
        elif args.requirement:
            result = {"show": "requirement", "requested": args.requirement,
                      "matches": [], "headings": [], "capabilities": []}
        elif args.file:
            result = {"show": "file", "file": args.file, "capabilities": []}
        elif args.orphans:
            result = {"orphans": []}
        elif args.all:
            result = {"layout": "central", "capabilities": [], "orphans": []}
        elif args.requirements_for:
            result = {"changed": args.changed or [], "capabilities": [],
                      "rules": cc.load_rules(None)}
        else:
            result = {"changed": args.changed or [], "matched": []}
        emit(result)
        return 0

    try:
        if args.rules:
            result = {"rules": living.get("rules") or cc.load_rules(None)}
        elif args.headings:
            result = show_headings(args.headings, living, root)
        elif args.requirement:
            result = show_requirement(args.requirement, living, root, args.capability)
        elif args.file:
            result = show_for_file(args.file, living, root)
        elif args.orphans:
            result = {"orphans": find_orphans(living, root)}
        elif args.all:
            orphans = find_orphans(living, root)
            # The layout rides along so adoption can read the answer the developer
            # already gave at set-up instead of asking a second time.
            result = {"layout": living.get("layout") or "central",
                      "capabilities": discover_all(living, root, orphans),
                      "orphans": orphans}
        elif args.requirements_for:
            files = args.changed or []
            # The rules ride along so a load step never spawns a second process.
            result = {"changed": files,
                      "capabilities": requirements_for_changed(files, living, root, follow_aligns=args.follow_aligns),
                      "rules": living.get("rules") or cc.load_rules(None)}
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
