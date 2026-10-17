#!/usr/bin/env python3
"""Print the grammars of the plan blocks a change needs, and record the choice.

  plan-blocks.py --feature-dir <dir> calls code states screens
  plan-blocks.py --feature-dir <dir> none

The plan step decides which blocks the change calls for and names them here, so
the grammar text reaches the model only for the blocks it chose. The choice lands
in `.spec-context.json` as `planBlocks` and `planBlocksPicked`; the plan check later corrects `planBlocks` to what the plan holds. A project copy of a part under
`.specify/companion/nodes/` wins over the shipped one. Recording is best-effort.
Stdlib only.
"""
from __future__ import annotations

import argparse
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE))

from capture import _git_branch, _repo_root_for, atomic_write, fill_required, read_ctx  # noqa: E402
from spec_context import _repo_root, resolve_feature_dir  # noqa: E402

PARTS = {"calls": "call-paths", "code": "code-pins", "states": "states", "screens": "screens"}
NAMES = ", ".join([*PARTS, "none"])
CHECK = (
    "Then run `python3 .specify/extensions/companion/scripts/check_plan.py --feature-dir <feature_directory>`, "
    "fix what it reports once, and record anything left with `write-context.py --concern`."
)
LINK = (
    "Link each screen to the state it belongs to: end that state's line with `shows <screen-name>`, "
    "naming a `screen` block of this plan. For example:"
)
LINK_EXAMPLE = (
    "```text\n"
    "In-review: A reviewer reads the article. shows reviewer\n"
    "```screen reviewer Reviewer on an article\n"
    "```"
)


def part_text(root: Path, part: str) -> str:
    for base in (root / ".specify" / "companion" / "nodes", HERE.parent / "presets" / "_parts"):
        path = base / f"{part}.md"
        if path.is_file():
            return path.read_text(encoding="utf-8").strip()
    raise FileNotFoundError(f"{part}.md")


def record(feature_dir: Path, chosen: list[str]) -> None:
    target = feature_dir / ".spec-context.json"
    ctx = read_ctx(target)
    fill_required(ctx, feature_dir, _git_branch(_repo_root_for(feature_dir)) or "main")
    ctx["planBlocks"] = chosen
    ctx["planBlocksPicked"] = list(chosen)
    atomic_write(target, ctx)


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="Print the plan blocks a change needs and record the choice")
    ap.add_argument("--feature-dir", default=None, help="the spec to record the choice in")
    ap.add_argument("names", nargs="*", help=NAMES)
    args = ap.parse_args(argv)

    names = args.names
    if not names or any(n not in (*PARTS, "none") for n in names) or ("none" in names and len(names) > 1):
        print(f"Usage: plan-blocks.py --feature-dir <dir> <name>... (names: {NAMES}; none stands alone)",
              file=sys.stderr)
        return 2

    chosen = [] if names == ["none"] else [n for n in PARTS if n in names]
    feature_dir = resolve_feature_dir(_repo_root(), args.feature_dir)
    root = _repo_root_for(feature_dir) if feature_dir else _repo_root()

    if not chosen:
        print("No plan block applies to this change.")
    else:
        if "states" in chosen and "screens" in chosen:
            print(LINK + "\n\n" + LINK_EXAMPLE + "\n")
        for name in chosen:
            print(part_text(root, PARTS[name]) + "\n")
        print(CHECK)

    if feature_dir is None or not feature_dir.is_dir():
        print("[companion] Warning: could not resolve the spec, so the plan's blocks were not recorded.",
              file=sys.stderr)
        return 0
    try:
        record(feature_dir, chosen)
    except OSError as err:
        print(f"[companion] Warning: could not record the plan's blocks ({err}).", file=sys.stderr)
    return 0


if __name__ == "__main__":
    sys.exit(main())
