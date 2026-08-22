#!/usr/bin/env python3
"""Report requirement→test coverage for a Companion living spec (LS·8).

A capability's hot spec (centralized `capabilities/<name>/spec.md`, or a
colocated `<base>.spec.md`) lists requirements; its reserved coverage-tier
sibling (the `*.coverage.md` next to that spec) maps each requirement to the
test(s) that exercise it. This checker reads both, reuses the LS·1 resolver for the tier
paths, and reports — per requirement — whether it is covered (has a coverage
entry) or uncovered. It is the conformance ON-RAMP, not a gate:

  - READ-ONLY: it never edits a spec, coverage file, or context.
  - NON-FAILING: it always exits 0, even with uncovered requirements. A
    surrounding workflow / CI may treat findings as a signal; the command does
    not. (Mirrors the drift command's contract.)
  - OPT-IN: with `livingSpecs.enabled` unset/false (or no config) it reports
    nothing and exits 0 (the LS·1 inert contract).

A spec may write its requirements in EITHER of two shapes, and both are read:

  - the id form — a bullet or heading led by an `FR-NNN` / `NFR-NNN` (also
    `FR001`, `NFR4`) token. Identity is the normalized id.
  - the named form (OpenSpec requirement+scenario shape, what the LS·3 fold-back
    writes) — a `### <name>` or `### Requirement: <name>` heading, usually with
    `#### Scenario:` blocks under it and NO numeric id. Identity is the heading
    text, matching how `write-context.py` finds a requirement to fold into.

A requirement is COVERED when the coverage file names it on a line that also
names at least one test (a `.test.`/`.spec.`/`_test` path or a `tests/...`
reference) — by id for the id form, by heading text for the named form. A
capability with no `.coverage.md` reports every requirement uncovered (with
`hasCoverage: false`); a capability with no `.spec.md` requirements reports an
empty list.

Usage:
  check-coverage.py [--root <dir>] [--capability <name>] [--json]
"""
from __future__ import annotations

import argparse
import importlib.util
import json
import os
import re
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))


def _load_resolver():
    path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "resolve-spec-paths.py")
    spec = importlib.util.spec_from_file_location("resolve_spec_paths", path)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


rsp = _load_resolver()

# A requirement id: FR / NFR optionally hyphenated, then digits. Anchored to the
# start of a list bullet or heading so prose mentions of "FR-001" don't register
# as a new requirement.
REQ_LINE = re.compile(r"^\s*(?:[-*+]|#{1,6})\s*\**(?P<id>(?:N?FR)-?\d+)\b", re.I)
REQ_ANY = re.compile(r"\b(?P<id>(?:N?FR)-?\d+)\b", re.I)
# Any markdown ATX heading, with its level.
HEADING = re.compile(r"^(?P<hashes>#{1,6})\s+(?P<text>.+?)\s*$")
# The optional `Requirement:` label the fold's delta blocks may carry.
REQ_LABEL = re.compile(r"^\**\s*requirements?:\s*", re.I)
# A canonical id, post-normalization (`FR-1`) — tells an id apart from a name.
ID_FORM = re.compile(r"^N?FR-\d+$")
# A token that looks like a test reference.
TEST_TOKEN = re.compile(
    r"(?:[\w./-]*\.(?:test|spec)\.[\w]+"   # foo.test.ts / foo.spec.tsx
    r"|[\w./-]*_test\.[\w]+"               # foo_test.py
    r"|tests?/[\w./-]+"                    # tests/foo.py
    r"|[\w./-]+::[\w]+)",                  # pytest nodeid file::TestCase
    re.I,
)


def _norm_id(rid: str) -> str:
    """Canonicalize a requirement id for matching: upper, hyphen-normalized.

    `fr001` / `FR-001` / `FR-1` -> `FR-1`; leading zeros are stripped so the
    spec and coverage files can disagree on padding."""
    m = re.match(r"(N?FR)-?0*(\d+)", rid, re.I)
    if not m:
        return rid.upper()
    return f"{m.group(1).upper()}-{m.group(2)}"


def _clean_name(name: str) -> str:
    """Display form of a named requirement: label + emphasis stripped."""
    s = REQ_LABEL.sub("", name.strip())
    s = re.sub(r"[*_`]", "", s)
    return re.sub(r"\s+", " ", s).strip()


def _norm_name(name: str) -> str:
    """Canonicalize a requirement NAME for matching: label/emphasis stripped,
    whitespace collapsed, trailing punctuation dropped, lowercased."""
    return _clean_name(name).strip(" .:;—–-").lower()


def is_named(rid: str) -> bool:
    """True when this requirement's identity is a heading name, not an id."""
    return not ID_FORM.match(rid)


def _key(rid: str) -> str:
    """The coverage-map key for a requirement returned by spec_requirements."""
    return _norm_name(rid) if is_named(rid) else rid


def spec_requirements(text: str) -> list[str]:
    """Ordered, de-duped requirements from a spec.

    Returns a mixed list: normalized ids (`FR-1`) for the id form, and the
    cleaned heading text for the named (fold-back / OpenSpec) form.

    Section-vs-requirement rule for `###` headings — a `###` heading is a NAMED
    REQUIREMENT unless its body (down to the next `###`/`##`/`#`) contains an
    `FR-NNN`/`NFR-NNN` requirement line. Specs commonly use `###` as a section
    GROUPING (`### Public surface`) over id-bearing bullets; in that shape the
    bullets are the requirements and the heading is just a label, so counting the
    heading too would double-count. A `#### Scenario:` (or any level >= 4
    heading) is never a requirement — it is part of a requirement's body.
    """
    out, seen = [], set()
    pending: str | None = None   # an undecided `###` candidate
    is_section = False

    def flush() -> None:
        nonlocal pending, is_section
        if pending and not is_section:
            key = _norm_name(pending)
            if key and key not in seen:
                seen.add(key)
                out.append(pending)
        pending, is_section = None, False

    for line in text.splitlines():
        hm = HEADING.match(line)
        level = len(hm.group("hashes")) if hm else 0
        # Any heading at `###` or above closes the previous candidate's body.
        if hm and level <= 3:
            flush()
        rm = REQ_LINE.match(line)
        if rm:
            rid = _norm_id(rm.group("id"))
            if rid not in seen:
                seen.add(rid)
                out.append(rid)
            # An id-bearing line inside a `###` body (a bullet, or a `####`
            # sub-heading) demotes that heading to a section grouping.
            if pending is not None and not (hm and level <= 3):
                is_section = True
            continue
        if hm and level == 3:
            pending = _clean_name(hm.group("text"))
    flush()
    return out


def coverage_map(text: str, names: list[str] | None = None) -> dict[str, list[str]]:
    """Map requirement key -> list of test references found on its line(s).

    Keys are normalized ids (`FR-1`) and normalized requirement names. Names are
    only looked for when `names` is supplied (the spec is what defines them);
    a coverage line references a named requirement by writing its heading text
    anywhere on the line — e.g. a table cell `| Users can add a todo | ... |` or
    `- Users can add a todo → src/todos.test.tsx`. The `Requirement:` label and
    markdown emphasis are tolerated on either side.
    """
    wanted = []
    for n in names or []:
        if is_named(n):
            k = _norm_name(n)
            if k:
                wanted.append((k, re.compile(rf"(?<![a-z0-9]){re.escape(k)}(?![a-z0-9])")))

    out: dict[str, list[str]] = {}
    for line in text.splitlines():
        tests = TEST_TOKEN.findall(line)
        # Harvest ids only from the line MINUS its test references, so an `fr-N`
        # buried inside a test path (`src/feature-fr-2/x.test.ts`) can't register
        # as a covered requirement (the LS·3 substring lesson). Names are matched
        # against the same test-stripped text, for the same reason.
        id_text = TEST_TOKEN.sub(" ", line)
        keys = {_norm_id(m.group("id")) for m in REQ_ANY.finditer(id_text)}
        if wanted:
            hay = _norm_name(id_text)
            keys.update(k for k, pat in wanted if pat.search(hay))
        if not keys:
            continue
        for rid in keys:
            bucket = out.setdefault(rid, [])
            for t in tests:
                if t not in bucket:
                    bucket.append(t)
    return out


def check_capability(name: str, spec_path: str, root: str, tiers: dict) -> dict:
    cov = tiers.get("coverage", {})
    cov_path = cov.get("path")
    has_coverage = bool(cov.get("exists"))

    spec_abs = os.path.join(root, spec_path)
    reqs = []
    if os.path.isfile(spec_abs):
        with open(spec_abs, encoding="utf-8") as fh:
            reqs = spec_requirements(fh.read())

    cmap: dict[str, list[str]] = {}
    if has_coverage and cov_path:
        with open(os.path.join(root, cov_path), encoding="utf-8") as fh:
            cmap = coverage_map(fh.read(), reqs)

    requirements = []
    for rid in reqs:
        tests = cmap.get(_key(rid), [])
        requirements.append({
            "id": rid,
            "kind": "name" if is_named(rid) else "id",
            "covered": bool(tests),
            "tests": tests,
        })

    covered = sum(1 for r in requirements if r["covered"])
    return {
        "name": name,
        "spec": spec_path,
        "coverage": cov_path,
        "hasCoverage": has_coverage,
        "total": len(requirements),
        "covered": covered,
        "uncovered": len(requirements) - covered,
        "requirements": requirements,
    }


def run(root: str, capability: str | None) -> dict:
    living = rsp.load_living(root)
    if not living["enabled"]:
        return {"enabled": False, "capabilities": []}

    # Only CONFIGURED capabilities (not orphan *.spec.md); skip a capability whose
    # colocated spec path is unresolvable rather than raising (read-only, never-fail).
    entries = []
    for cap in living["capabilities"]:
        try:
            entries.append(rsp._entry(cap, root))
        except ValueError:
            continue
    if capability:
        entries = [e for e in entries if e["name"] == capability]

    caps = []
    for e in entries:
        if not e.get("exists"):
            continue
        caps.append(check_capability(e["name"], e["spec"], root, e.get("tiers", {})))
    return {"enabled": True, "capabilities": caps}


def render_human(result: dict) -> str:
    if not result["enabled"]:
        return ""  # opt-in: disabled feature reports nothing in human mode
    if not result["capabilities"]:
        return "no living specs with a spec file to check"
    lines = ["📊 Coverage report"]
    for cap in result["capabilities"]:
        if not cap["hasCoverage"]:
            lines.append(f"\n📁 {cap['name']} — no .coverage.md (all {cap['total']} requirements uncovered)")
        else:
            lines.append(
                f"\n📁 {cap['name']} — {cap['covered']}/{cap['total']} requirements covered"
            )
        for r in cap["requirements"]:
            if r["covered"]:
                lines.append(f"   ✓ {r['id']} → {', '.join(r['tests'])}")
            else:
                lines.append(f"   ✗ {r['id']} — uncovered")
    return "\n".join(lines)


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="Report living-spec requirement→test coverage.")
    ap.add_argument("--root", default=".", help="repo root (default: cwd)")
    ap.add_argument("--capability", help="restrict to one capability by name")
    ap.add_argument("--json", action="store_true", help="emit the machine-readable object")
    args = ap.parse_args(argv)

    result = run(args.root, args.capability)
    if args.json:
        print(json.dumps(result, indent=2))
    else:
        text = render_human(result)
        if text:  # no blank line when disabled / nothing to say
            print(text)
    return 0  # never fails — informational, like drift.


if __name__ == "__main__":
    raise SystemExit(main())
