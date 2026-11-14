#!/usr/bin/env python3
"""Find the ways a change quietly lowered the bar, in the lines it added.

A skipped test, a suppression comment, an empty stub or a lower coverage threshold all
still go green, so the checks never show them. This reads the uncommitted change (edits
and new files, outside the spec folders) and returns one hit per line that does one of
those. A line that gives its reason after ` -- ` is a reasoned hit. Read-only, stdlib only.
"""
from __future__ import annotations

import os
import re
import shutil
import subprocess
import tempfile
from pathlib import Path

LIMIT = 20
EMPTY_TREE = "4b825dc642cb6eb9a060e54bf8d69288fbee4904"
SCOPE = ["--", ".", ":(exclude)specs", ":(exclude).specify", ":(exclude)*.md", ":(exclude)*.mdx"]
NOT_A_FUNCTION = {"if", "for", "while", "switch", "catch", "with", "return"}

PATTERNS = (
    ("skipped test", re.compile(r"\b(?:it|test|describe|context|suite)\.skip\s*\(|\b(?:xit|xtest|xdescribe)\s*\(|@(?:pytest\.mark|unittest)\.skip\b")),
    ("focused test", re.compile(r"\b(?:it|test|describe|context|suite)\.only\s*\(|\b(?:fit|fdescribe)\s*\(")),
    ("type check suppressed", re.compile(r"@ts-(?:ignore|expect-error|nocheck)\b")),
    ("lint rule disabled", re.compile(r"\beslint-disable\b")),
)
EMPTY_STUB = re.compile(r"^\s*(?:export\s+)?(?:async\s+)?(?:function\s*\*?\s*(\w+)|(\w+))\s*\([^)]*\)\s*(?::\s*[^{=]+)?\{\s*\}\s*;?\s*$"
                        r"|^\s*(?:async\s+)?def\s+(\w+)\s*\([^)]*\)[^:]*:\s*pass\s*$")
COVERAGE_FILE = re.compile(r"(?:^|/)(?:[^/]*(?:jest|vitest|karma|nyc|c8|cover)[^/]*|package\.json|pyproject\.toml|setup\.cfg|tox\.ini)$")
COVERAGE =re.compile(r"[\"']?\b(branches|functions|lines|statements|threshold|fail_under|fail-under)\b[\"']?\s*[:=]\s*(\d+(?:\.\d+)?)")
REASON_ELSEWHERE = re.compile(r"reason\s*=\s*[\"']([^\"']+)|@ts-(?:ignore|expect-error|nocheck)\b:?\s*(\S.*)")
HUNK = re.compile(r"^@@ -\d+(?:,\d+)? \+(\d+)(?:,\d+)? @@")


def _diff(root: Path) -> str:
    """The unified diff of every working change against HEAD, new files included, staging untouched."""
    def git(*args, env=None):
        return subprocess.run(["git", "-C", str(root), *args], capture_output=True, text=True,
                              check=True, timeout=20, env=env).stdout

    index = Path(git("rev-parse", "--git-path", "index").strip())
    index = index if index.is_absolute() else root / index
    try:
        base = git("rev-parse", "--verify", "-q", "HEAD").strip()
    except subprocess.CalledProcessError:
        base = EMPTY_TREE
    with tempfile.TemporaryDirectory() as tmp:
        scratch = Path(tmp) / "index"
        if index.is_file():
            shutil.copy2(index, scratch)
        env = {**os.environ, "GIT_INDEX_FILE": str(scratch), "GIT_OPTIONAL_LOCKS": "0"}
        git("add", "-A", env=env)
        return git("-c", "core.quotePath=false", "diff", "--cached", "--no-color", "--no-ext-diff", "-U0", base, *SCOPE, env=env)


def _reason(text: str) -> str | None:
    """The reason a line gives: after ` -- `, a pytest `reason=`, or the words after a ts directive."""
    _, sep, after = text.partition(" -- ")
    if not sep:
        given = REASON_ELSEWHERE.search(text)
        after = next((g for g in given.groups() if g), "") if given else ""
    after = after.strip().rstrip("*/").strip()
    return after or None


def _kind(text: str) -> str | None:
    for kind, pattern in PATTERNS:
        if pattern.search(text):
            return kind
    stub = EMPTY_STUB.match(text)
    if stub and not ({g for g in stub.groups() if g} & NOT_A_FUNCTION):
        return "empty function stub"
    return None


def hits_in(diff: str) -> list[dict]:
    """Every hit in a `-U0` unified diff, in the order the diff lists them."""
    hits: list[dict] = []
    removed: dict[tuple[str, str], float] = {}
    added_coverage: list[tuple[str, int, str, float, str]] = []
    path, line, header = None, 0, False
    for raw in diff.splitlines():
        if raw.startswith("diff --git"):
            path, header = None, True
        elif header and raw.startswith("+++ "):
            path = raw[6:] if raw.startswith("+++ b/") else None
        elif (m := HUNK.match(raw)):
            line, header = int(m.group(1)), False
        elif header:
            continue
        elif path and raw.startswith("-") and COVERAGE_FILE.search(path):
            for key, value in COVERAGE.findall(raw):
                removed[(path, key)] = max(removed.get((path, key), 0.0), float(value))
        elif path and raw.startswith("+"):
            text = raw[1:]
            kind = _kind(text)
            if kind:
                hits.append({"file": path, "line": line, "kind": kind, "reason": _reason(text)})
            for key, value in COVERAGE.findall(text) if COVERAGE_FILE.search(path) else ():
                added_coverage.append((path, line, key, float(value), text))
            line += 1
    for path, at, key, value, text in added_coverage:
        before = removed.get((path, key))
        if before is not None and value < before:
            hits.append({"file": path, "line": at, "kind": f"coverage threshold lowered ({key} {before:g} to {value:g})",
                         "reason": _reason(text)})
    return hits


def scan(root: Path) -> list[dict]:
    """The hits in `root`'s working change. Raises when git cannot answer, so the caller keeps the last scan."""
    return hits_in(_diff(root))


def concerns(hits: list[dict]) -> list[dict]:
    """The hits as concerns for the run record, capped at LIMIT plus one line for the rest."""
    out = []
    for hit in hits[:LIMIT]:
        said = f"reasoned: {hit['reason']}" if hit.get("reason") else "no reason given"
        out.append({"note": f"Lowered the bar: {hit['kind']} at `{hit['file']}:{hit['line']}`, {said}",
                    "step": "implement", "source": "scan", "file": hit["file"], "line": hit["line"]})
    if len(hits) > LIMIT:
        out.append({"note": f"Lowered the bar: {len(hits) - LIMIT} more hit(s) not listed", "step": "implement",
                    "source": "scan"})
    return out
