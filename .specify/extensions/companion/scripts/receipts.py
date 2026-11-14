#!/usr/bin/env python3
"""Print a pull request's Receipts section from a feature's run record.

Every row comes from `.spec-context.json`, not from the agent: each check with its
command and exit code, checks the agent only reported marked as claimed, checks whose
code changed since they ran marked out of date, then the review depth and the
requirements that rest on an assumption. Read-only, stdlib only, never exits non-zero.

    python3 receipts.py --feature-dir specs/042-x
"""
from __future__ import annotations

import argparse
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from capture import _code_at, _tier_rank  # noqa: E402
from spec_context import read_ctx  # noqa: E402

LEAD = "Recorded by the run, not typed by the agent, except where marked."
SCOPE = ["--", ":(top)", ":(top,exclude)specs", ":(top,exclude).specify"]


def cell(text) -> str:
    """Text safe inside a Markdown table cell, with no @mention that would notify anyone."""
    text = " ".join(str(text).split()).replace("\\", "\\\\").replace("|", "\\|")
    return text.replace("@", "@​")


def code(text) -> str:
    text = " ".join(str(text).split())
    if "`" in text or "\\" in text:
        return cell(text)
    return f"`{text}`".replace("|", "\\|")


def changed_since(feature_dir: Path, tree: str, now: str | None) -> int | None:
    """Files changed between a check's snapshot and now, outside the spec folders."""
    if not now or not tree or not all(c in "0123456789abcdef" for c in tree):
        return None
    if now == tree:
        return 0
    try:
        out = subprocess.run(["git", "-C", str(feature_dir), "diff-tree", "-r", "--name-only", "-z", tree, now, *SCOPE],
                             capture_output=True, text=True, check=True, timeout=20).stdout
    except Exception:  # noqa: BLE001
        return None
    return len([n for n in out.split("\0") if n])


def head_tree(feature_dir: Path, head: str) -> str | None:
    try:
        return subprocess.run(["git", "-C", str(feature_dir), "rev-parse", f"{head}^{{tree}}"],
                              capture_output=True, text=True, check=True, timeout=20).stdout.strip()
    except Exception:  # noqa: BLE001
        return None


def result(entry: dict, now: str | None, feature_dir: Path) -> str:
    if entry.get("source") != "derived":
        return "claimed by the agent, not run"
    parts = []
    code_ = entry.get("exitCode")
    if isinstance(code_, int):
        parts.append(f"exit {code_}")
    elif entry.get("result"):
        parts.append(cell(entry["result"]))
    seconds = entry.get("durationSeconds")
    if isinstance(code_, int) and isinstance(seconds, (int, float)):
        parts.append(f"{seconds:g} s")
    if entry.get("tree"):
        stale = changed_since(feature_dir, str(entry["tree"]), now)
        if stale is None:
            parts.append("freshness unknown")
        elif stale:
            parts = [f"{parts[0]} at {cell(entry.get('commit') or str(entry['tree'])[:7])}", "out of date"] if parts else ["out of date"]
    warnings = [cell(w) for w in entry.get("warnings") or [] if isinstance(w, str) and not w.startswith("exited ")]
    return " · ".join(parts + warnings) or "recorded"


def receipts(feature_dir: Path) -> str:
    ctx = read_ctx(feature_dir / ".spec-context.json")
    lines = ["## Receipts", ""]
    raw = ctx.get("verified")
    verified = [v if isinstance(v, dict) else {"what": v} for v in (raw if isinstance(raw, list) else [])]
    now = _code_at(feature_dir)
    head, tree_now = now.get("commit"), now.get("tree")
    lead = LEAD
    if head:
        dirty = changed_since(feature_dir, head_tree(feature_dir, head) or "", tree_now)
        lead += f" Code at `{head}`" + (" plus uncommitted changes." if dirty else ".")
    lines += [lead, ""]
    if not verified:
        lines.append("No checks were recorded.")
    else:
        lines += ["| Check | Command | Result |", "| --- | --- | --- |"]
        for v in verified:
            command = code(v["command"]) if v.get("command") else "—"
            lines.append(f"| {cell(v.get('what') or 'check')} | {command} | {result(v, tree_now, feature_dir)} |")
    risk = ctx.get("risk") if isinstance(ctx.get("risk"), dict) else None
    if risk and risk.get("tier"):
        raised = risk.get("raised") if isinstance(risk.get("raised"), dict) else None
        depth = f"**Review depth:** {cell(risk['tier'])}"
        if raised and raised.get("reason") and _tier_rank(raised.get("tier")) > _tier_rank(risk.get("computed")):
            who = "the agent" if raised.get("by") in ("ai", "agent") else cell(raised.get("by") or "someone")
            depth += f", raised by {who}: \"{cell(raised['reason'])}\""
        elif risk.get("reasons"):
            depth += f" ({cell('; '.join(str(r) for r in risk['reasons']))})"
        lines += ["", depth]
    coverage = ctx.get("coverage") if isinstance(ctx.get("coverage"), dict) else {}
    assumed = [(req, e["assumes"]) for req, e in sorted(coverage.items())
               if isinstance(e, dict) and isinstance(e.get("assumes"), str) and e["assumes"].strip() and not e.get("tests")]
    if assumed:
        lines += ["", "**Assumed, not tested:** " + "; ".join(f"{cell(req)} {cell(text)}" for req, text in assumed) + "."]
    concerns = ctx.get("concerns") if isinstance(ctx.get("concerns"), list) else []
    lowered = [c["note"].removeprefix("Lowered the bar: ") for c in concerns
               if isinstance(c, dict) and c.get("source") == "scan" and isinstance(c.get("note"), str)]
    if lowered:
        lines += ["", "**Lowered the bar:** " + "; ".join(cell(n) for n in lowered) + "."]
    return "\n".join(lines) + "\n"


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--feature-dir", required=True)
    try:
        args = parser.parse_args()
    except SystemExit:
        return 0
    try:
        sys.stdout.write(receipts(Path(args.feature_dir)))
    except Exception as exc:  # noqa: BLE001
        sys.stdout.write(f"## Receipts\n\nThe run record could not be read: {cell(exc)}\n")
    return 0


if __name__ == "__main__":
    sys.exit(main())
