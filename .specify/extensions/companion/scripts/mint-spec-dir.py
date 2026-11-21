#!/usr/bin/env python3
"""Create the next spec folder, numbered the way the project chose, and point `.specify/feature.json` at it.

Two runs started together used to pick the same number, because each one listed `specs/`
and added one. The scan and the create now happen under one cross-process lock, and the
folder is made with a plain `mkdir`, so a second run always gets the next number.
`feature_numbering` in `.specify/init-options.json` picks sequential (`645-name`) or
timestamp (`20261103-142501-name`) folders, the same two spec-kit offers.

Prints the new folder, relative to the project. Stdlib only.
"""
from __future__ import annotations

import argparse
import json
import os
import re
import sys
import time
from datetime import datetime
from pathlib import Path

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import subprocess  # noqa: E402

from spec_context import _lock_root, _repo_root, atomic_write  # noqa: E402

SEQUENTIAL = re.compile(r"^(\d{3,})-")
TIMESTAMP = re.compile(r"^\d{8}-\d{6}-")


def _slug(text: str) -> str:
    slug = re.sub(r"[^a-z0-9]+", "-", text.lower()).strip("-")
    return slug or "feature"


def _numbering(root: Path) -> str:
    try:
        options = json.loads((root / ".specify" / "init-options.json").read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        return "sequential"
    return "timestamp" if isinstance(options, dict) and options.get("feature_numbering") == "timestamp" else "sequential"


def _branch_names(root: Path) -> list:
    """Local and remote branch names, so a teammate's `014-` branch claims its number before its folder lands here."""
    try:
        out = subprocess.run(["git", "branch", "-a", "--format=%(refname:short)"], cwd=root,
                             capture_output=True, text=True, check=True).stdout
    except (OSError, subprocess.CalledProcessError):
        return []
    return [line.strip().rsplit("/", 1)[-1] for line in out.splitlines() if line.strip()]


def _next_number(specs: Path, branches: list) -> int:
    highest = 0
    names = [entry.name for entry in (specs.iterdir() if specs.is_dir() else [])] + branches
    for name in names:
        match = SEQUENTIAL.match(name)
        if match and not TIMESTAMP.match(name):
            highest = max(highest, int(match.group(1)))
    return highest + 1


class _ProjectLock:
    """One lock per project for the scan and the create; held only for those few file operations."""

    def __init__(self, root: Path):
        key = re.sub(r"[^A-Za-z0-9]+", "_", str(root.resolve()))[-120:]
        self.path = _lock_root() / f"mint-{key}.lock"

    def __enter__(self):
        self.fh = open(self.path, "a+")
        try:
            try:
                import fcntl
                fcntl.flock(self.fh, fcntl.LOCK_EX)
            except ImportError:
                import msvcrt
                self.fh.seek(0)
                msvcrt.locking(self.fh.fileno(), msvcrt.LK_LOCK, 1)
        except OSError as exc:
            print(f"[companion] Warning: minting without the folder lock ({exc}).", file=sys.stderr)
        return self

    def __exit__(self, *exc):
        self.fh.close()


def _claim(specs: Path, number: int, name: str) -> Path:
    """The folder for a number the request named: refused when another folder already holds that number."""
    taken = [e.name for e in specs.iterdir() if (m := SEQUENTIAL.match(e.name)) and int(m.group(1)) == number]
    folder = specs / f"{number:03d}-{name}"
    if any(t != folder.name for t in taken):
        raise SystemExit(f"[companion] Spec number {number} is already taken by specs/{taken[0]}; no folder minted.")
    if (folder / "spec.md").exists():
        raise SystemExit(f"[companion] {folder.name} already holds a spec; no folder minted.")
    folder.mkdir(exist_ok=True)
    return folder


def mint(root: Path, short_name: str, now: datetime | None = None, branches: bool = False,
         number: int | None = None) -> Path:
    if not (root / ".specify").is_dir():
        raise SystemExit(f"[companion] {root} is not a Spec Kit project (no .specify/); no folder minted.")
    specs = root / "specs"
    specs.mkdir(exist_ok=True)
    name = _slug(short_name)
    timestamped = _numbering(root) == "timestamp"
    claimed = _branch_names(root) if branches else []
    with _ProjectLock(root):
        if number is not None:
            folder = _claim(specs, number, name)
            atomic_write(root / ".specify" / "feature.json", {"feature_directory": folder.relative_to(root).as_posix()})
            return folder
        for _ in range(50):
            prefix = (now or datetime.now()).strftime("%Y%m%d-%H%M%S") if timestamped else f"{_next_number(specs, claimed):03d}"
            folder = specs / f"{prefix}-{name}"
            try:
                folder.mkdir()
                break
            except FileExistsError:
                # Sequential: the next scan sees this folder and moves past it. Timestamp: wait for the next second.
                if timestamped:
                    now = None
                    time.sleep(1)
        else:
            raise SystemExit("[companion] Could not mint a spec folder after 50 tries.")
        atomic_write(root / ".specify" / "feature.json", {"feature_directory": folder.relative_to(root).as_posix()})
    return folder


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--short-name", required=True, help="2–4 words naming the change")
    parser.add_argument("--branches", action="store_true", help="also count numbers that git branches already claim")
    parser.add_argument("--number", type=int, help="the spec number the request named, used as given")
    args = parser.parse_args()
    root = _repo_root()
    print(mint(root, args.short_name, branches=args.branches, number=args.number).relative_to(root).as_posix())
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
