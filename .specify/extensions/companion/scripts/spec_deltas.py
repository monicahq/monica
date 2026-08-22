#!/usr/bin/env python3
"""The requirement-delta grammar a feature spec declares, and its parser.

`## ADDED / MODIFIED / REMOVED / RENAMED Requirements` blocks, each holding
`### <heading>` requirement units. Pure parsing — nothing here touches the disk,
so it can be exercised on a string.

Stdlib only."""

from __future__ import annotations

import re

# A top-level delta block header: `## ADDED Requirements` (case-insensitive verb).
_DELTA_HEADER_RE = re.compile(
    r"^##\s+(ADDED|MODIFIED|REMOVED|RENAMED)\s+Requirements\s*$", re.IGNORECASE
)
# A per-block target marker: `<!-- capability: todos -->`.
_CAP_MARKER_RE = re.compile(r"<!--\s*capability:\s*([^\s>]+)\s*-->", re.IGNORECASE)
# A requirement heading inside a block (OpenSpec requirement + scenario shape).
_REQ_HEADING_RE = re.compile(r"^###\s+(.+?)\s*$")
# RENAMED uses `### FROM -> TO` (or `→`) to express the heading rename.
_RENAME_RE = re.compile(r"^(.*?)\s*(?:->|→)\s*(.+)$")


def _split_requirements(block_body: str) -> list[tuple[str, str]]:
    """Split a delta block body into (heading, full_section_text) requirement units.

    A requirement is a `### <heading>` line plus everything up to the next `###`.
    Returns the heading text and the section text (heading line included)."""
    lines = block_body.splitlines()
    reqs: list[tuple[str, str]] = []
    cur_head: str | None = None
    cur_lines: list[str] = []
    for line in lines:
        m = _REQ_HEADING_RE.match(line)
        if m:
            if cur_head is not None:
                reqs.append((cur_head, "\n".join(cur_lines).rstrip() + "\n"))
            cur_head = m.group(1).strip()
            cur_lines = [line]
        elif cur_head is not None:
            cur_lines.append(line)
    if cur_head is not None:
        reqs.append((cur_head, "\n".join(cur_lines).rstrip() + "\n"))
    return reqs


def parse_spec_deltas(spec_text: str) -> dict:
    """Parse top-level delta blocks from a feature spec.

    Returns {"added": [...], "modified": [...], "removed": [...],
    "renamed": [...], "markers": {verb: capability_name},
    "unit_caps": {verb: [capability_name | None per unit]}} where each verb list
    holds (heading, section_text) requirement units (REMOVED/RENAMED carry only
    the heading). `unit_caps` runs parallel to each verb list, recording the
    `<!-- capability: <name> -->` marker of the block each unit came from (None
    when its block had no marker), so a feature that folds into several
    capabilities can route each requirement to the right one. `markers` is the
    older last-write-wins {verb: name} view, kept for compatibility.
    An empty result (no recognized block) means a clean no-op."""
    out: dict = {
        "added": [], "modified": [], "removed": [], "renamed": [], "markers": {},
        "unit_caps": {"added": [], "modified": [], "removed": [], "renamed": []},
    }
    lines = spec_text.splitlines()
    cur_verb: str | None = None
    buf: list[str] = []

    def flush() -> None:
        if cur_verb is None:
            return
        body = "\n".join(buf)
        marker_m = _CAP_MARKER_RE.search(body)
        marker = marker_m.group(1).strip() if marker_m else None
        if marker:
            out["markers"][cur_verb] = marker
        if cur_verb == "removed":
            for head, _ in _split_requirements(body):
                out["removed"].append((head, ""))
                out["unit_caps"]["removed"].append(marker)
        elif cur_verb == "renamed":
            for head, _ in _split_requirements(body):
                rm = _RENAME_RE.match(head)
                if rm:
                    out["renamed"].append((rm.group(1).strip(), rm.group(2).strip()))
                    out["unit_caps"]["renamed"].append(marker)
        else:
            units = _split_requirements(body)
            out[cur_verb].extend(units)
            out["unit_caps"][cur_verb].extend([marker] * len(units))

    for line in lines:
        hm = _DELTA_HEADER_RE.match(line)
        if hm:
            flush()
            cur_verb = hm.group(1).lower()
            buf = []
            continue
        # A new top-level `## ` heading (not a delta header) closes the block.
        if line.startswith("## ") and cur_verb is not None:
            flush()
            cur_verb = None
            buf = []
            continue
        if cur_verb is not None:
            buf.append(line)
    flush()
    return out


def _has_deltas(deltas: dict) -> bool:
    return any(deltas[k] for k in ("added", "modified", "removed", "renamed"))
