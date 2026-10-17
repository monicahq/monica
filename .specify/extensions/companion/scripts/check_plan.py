#!/usr/bin/env python3
"""Check the call paths, screens, code blocks and state blocks a plan writes against the code they cite.

The `call-paths` node asks the plan step for fenced ```calls <title>``` blocks in
`plan.md`: one line per function on the path, a column-0 mark (`+` new, `~`
changed, `-` removed, space unchanged), two spaces per level, and `@ path:line`
after the name, or `**new**` and `@ path` for a file that does not exist yet.
The grammar is documented in docs/call-paths.md.

The `code-pins` node asks for a code fence that names a file: `<lang> sketch <file>`
for code that does not exist yet, or `<lang> <file>:<from>-<to>` for real lines,
with an optional `hl=3,6-7` and `pin N: text` lines straight after the fence. It
is read in `plan.md` and `tasks.md`, and documented in docs/code-pins.md.

A block is worth reading only while its paths are real, so this reads every cited
file. ERROR is a claim the code contradicts or a line that cannot be parsed;
WARNING is a budget or a citation it could not confirm.

The `screens` node asks for fenced ```screen <name> <title>``` blocks: a rough wireframe
of what a person sees, one part per line (`title:`, `row:`, `text:`, `chip:`, `button:`,
`field:`, `list: a | b`), two spaces to nest inside a `row:`, an optional `(new)` or
`(changed)` and a numbered dot `(1)` as a suffix, then one `1: Bold lead. Rest.` note
under the fence for each dot. The grammar is documented in docs/screens.md. Nothing is
read from the code for a screen, so it only holds the block to its grammar and budget.

A `states <title>` fence is checked on its own terms: every state reachable from the
start, every dead end marked `(final)`, every state on the grid, every arrow naming a
state that exists, a `shows` naming a screen block of the same plan, and the block inside
its budget. Its grammar is in docs/states.md.

A plan with none of these blocks prints nothing. Always exits 0, so it never fails the
step it runs in; `--strict` exits 1 on any error, for a caller that wants a gate.
Given a feature directory it also records its result in `.spec-context.json` (`--no-record` skips that);
given `--plan` it is read-only. Stdlib only.
"""
from __future__ import annotations

import argparse
import json
import os
import re
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from spec_context import _repo_root, _repo_root_for, resolve_feature_dir  # noqa: E402

MAX_BLOCKS = 3
MAX_LINES = 12
MAX_NOTE_LINES = 1
NEAR = 8
SIDE_FILES = ("call-paths.md", "screens.md")
CODE_FILE = "tasks.md"
MAX_SCREENS = 2
MAX_PARTS = 14
MAX_DOTS = 5
MAX_SKETCH_LINES = 12
MAX_PINS = 3
OTHER_BLOCKS = frozenset({"calls", "code", "states", "screen", "mermaid"})
MAX_DIGITS = 7
MAX_STATE_BLOCKS = 2
MAX_STATES = 8
MAX_ARROWS = 10
MAX_COLUMNS = 4
MAX_ROWS = 3

_FENCE = re.compile(r"^ {0,3}(`{3,}|~{3,})(.*)$")
_LOCATION = re.compile(r"^(?P<name>.*?)\s+@\s+(?P<path>\S+?)(?::(?P<line>\d+))?$")
_NEW = re.compile(r"\s*\*\*new\*\*\s*$")
_IDENT = re.compile(r"[A-Za-z_$][\w$]*")
_PART_KINDS = ("title", "row", "text", "chip", "button", "field", "list")
_TRAILER = re.compile(r"(?:^|[\s:])\((new|changed|\d{1,2})\)\s*$")
_SCREEN_NAME = re.compile(r"^[A-Za-z][\w-]{0,31}$")
_NOTE = re.compile(r"^(\d{1,2}):\s+\S")
_LANGUAGE = re.compile(r"^[a-z0-9][a-z0-9_+#.-]{0,31}$")
_CITE = re.compile(r"^(.+):([0-9]+)-([0-9]+)$")
_HL = re.compile(r"^([0-9]{1,7})(?:-([0-9]{1,7}))?$")
_PIN = re.compile(r"^\s*pin\s+([0-9]+):\s*(.*)$", re.IGNORECASE)
_OUTSIDE = re.compile(r"^([a-z]:|[\\/])", re.IGNORECASE)


class Finding:
    def __init__(self, level: str, rule: str, where: str, message: str) -> None:
        self.level, self.rule, self.where, self.message = level, rule, where, message

    def to_dict(self) -> dict:
        return {"level": self.level, "rule": self.rule, "where": self.where, "message": self.message}


def _lines(text: str) -> list:
    return text.replace("\r\n", "\n").replace("\r", "\n").split("\n")


def _fences(lines: list):
    """Every outermost fence as (info, start, body: [(lineno, text)], closed, index after it)."""
    i = 0
    while i < len(lines):
        m = _FENCE.match(lines[i])
        if not m:
            i += 1
            continue
        fence, info, start = m.group(1), m.group(2).strip(), i + 1
        body, closed = [], False
        i += 1
        while i < len(lines):
            close = _FENCE.match(lines[i])
            if close and close.group(1)[0] == fence[0] and len(close.group(1)) >= len(fence) \
                    and not close.group(2).strip():
                closed = True
                i += 1
                break
            body.append((i + 1, lines[i]))
            i += 1
        yield info, start, body, closed, i


def _after_blanks(lines: list, i: int) -> int:
    while i < len(lines) and not lines[i].strip():
        i += 1
    return i


def find_blocks(text: str, kind: str = "calls") -> list:
    """Every `kind` fence as {title, start, rows: [(lineno, text)], closed, notes}."""
    lines = _lines(text)
    blocks = []
    for info, start, body, closed, end in _fences(lines):
        words = info.split(None, 1)
        if not words or words[0] != kind:
            continue
        notes = 0
        j = _after_blanks(lines, end)
        while j < len(lines) and lines[j].lstrip().lower().startswith("note:"):
            notes += 1
            j += 1
        blocks.append({"title": words[1].strip() if len(words) > 1 else "", "start": start,
                       "rows": [(n, row.rstrip(" ")) for n, row in body if row.strip()],
                       "closed": closed, "notes": notes})
    return blocks


def parse_code_info(info: str):
    """{kind, path, first, last, hl} for a code block's info line, an error string, or None for any other fence."""
    words = info.split()
    if len(words) < 2 or not _LANGUAGE.match(words[0].lower()) or words[0].lower() in OTHER_BLOCKS:
        return None
    cite = _CITE.match(words[1])
    if words[1] == "sketch":
        if len(words) < 3 or words[2].startswith("hl="):
            return "`sketch` names no file"
        kind, path, first, last, extra = "sketch", words[2], 1, None, words[3:]
    elif cite:
        kind, path, extra = "cite", cite.group(1), words[2:]
        if max(len(cite.group(2)), len(cite.group(3))) > MAX_DIGITS:
            return f"`{words[1]}` is not a range of lines"
        first, last = int(cite.group(2)), int(cite.group(3))
    else:
        return None
    if len(extra) > 1 or (extra and not extra[0].startswith("hl=")):
        odd = next((w for w in extra if not w.startswith("hl=")), extra[-1])
        return f"`{odd}` is not allowed after the file: one `hl=` and nothing else"
    hl = []
    for part in extra[0][3:].split(",") if extra else []:
        m = _HL.match(part)
        low, high = (int(m.group(1)), int(m.group(2) or m.group(1))) if m else (1, 0)
        if low > high:
            return f"`{extra[0]}` is not numbers and low-to-high `a-b` ranges split by commas"
        hl.append((low, high))
    if _OUTSIDE.match(path) or ".." in re.split(r"[\\/]", path):
        return f"`{path}` is not a path inside the repo"
    if kind == "cite" and (first < 1 or first > last):
        return f"`{first}-{last}` is not a range of lines: count from 1, low to high"
    return {"kind": kind, "path": path, "first": first, "last": last, "hl": hl}


def find_code_blocks(text: str) -> list:
    """Every code block fence as {info, start, body: [(lineno, text)], closed, pins: [(lineno, line, text)]}."""
    lines = _lines(text)
    blocks = []
    for raw, start, body, closed, end in _fences(lines):
        info = parse_code_info(raw)
        if info is None:
            continue
        pins = []
        j = _after_blanks(lines, end) if closed else len(lines)
        while j < len(lines):
            m = _PIN.match(lines[j])
            if not m:
                break
            pins.append((j + 1, int(m.group(1)[:MAX_DIGITS + 1]), m.group(2).strip()))
            j += 1
        blocks.append({"info": info, "start": start, "body": body, "closed": closed, "pins": pins})
    return blocks


def parse_row(row: str):
    """(mark, depth, name, path, line, new) for one block line, or an error string."""
    if "\t" in row:
        return "a tab in the line: indent with two spaces per level"
    if len(row) < 2 or row[0] not in "+~- " or row[1] != " ":
        return "column 0 must be `+`, `~`, `-` or a space, followed by a space"
    mark, rest = row[0], row[2:]
    indent = len(rest) - len(rest.lstrip(" "))
    if indent % 2:
        return f"indent of {indent} spaces: use two per level"
    m = _LOCATION.match(rest.strip())
    if not m:
        return "no `@ path` after the name"
    name, path, line = m.group("name").strip(), m.group("path"), m.group("line")
    new = bool(_NEW.search(name))
    name = _NEW.sub("", name).strip()
    if not name:
        return "no name before `@`"
    if new and mark != "+":
        return "`**new**` marks a new file, so the line must start with `+`"
    if new and line:
        return "a `**new**` file has no line yet: write `@ path` alone"
    if os.path.isabs(path) or ".." in Path(path).parts:
        return f"`{path}` is not a path inside the repo"
    return mark, indent // 2, name, path, int(line) if line else None, new


def find_screens(text: str) -> list:
    """Every `screen` fence as {name, title, start, rows, closed, notes: [(lineno, n)]}."""
    lines = _lines(text)
    screens = []
    i = 0
    while i < len(lines):
        m = _FENCE.match(lines[i])
        if not m:
            i += 1
            continue
        fence, info = m.group(1), m.group(2).strip()
        words = info.split(None, 2)
        is_screen = bool(words) and words[0] == "screen"
        block = {"name": words[1] if is_screen and len(words) > 1 else "",
                 "title": words[2].strip() if is_screen and len(words) > 2 else "",
                 "start": i + 1, "rows": [], "closed": False, "notes": []}
        i += 1
        while i < len(lines):
            close = _FENCE.match(lines[i])
            if close and close.group(1)[0] == fence[0] and len(close.group(1)) >= len(fence) \
                    and not close.group(2).strip():
                block["closed"] = True
                i += 1
                break
            if is_screen and lines[i].strip():
                block["rows"].append((i + 1, lines[i].rstrip(" ")))
            i += 1
        if is_screen:
            j = i
            while j < len(lines) and not lines[j].strip():
                j += 1
            while j < len(lines) and _NOTE.match(lines[j]):
                block["notes"].append((j + 1, int(_NOTE.match(lines[j]).group(1))))
                j += 1
            screens.append(block)
    return screens


def parse_part(row: str):
    """(rule, message) for a bad part line, else (depth, kind, mark, dot)."""
    if "\t" in row:
        return "bad-indent", "a tab in the line: indent with two spaces per level"
    indent = len(row) - len(row.lstrip(" "))
    if indent % 2:
        return "bad-indent", f"indent of {indent} spaces: use two per level"
    rest, mark, dot = row.strip(), None, None
    while True:
        m = _TRAILER.search(rest)
        if not m:
            break
        if m.group(1).isdigit():
            if dot is not None:
                return "malformed", "two dots on one part"
            dot = int(m.group(1))
        else:
            if mark is not None:
                return "malformed", "two marks on one part"
            mark = m.group(1)
        rest = rest[:m.start() + (1 if m.group(0)[:1] == ":" else 0)].rstrip()
    head = re.match(r"^([a-z]+):(?:\s+(.*))?$", rest)
    if not head:
        return "malformed", "a line that is not `kind: text`"
    kind, body = head.group(1), (head.group(2) or "").strip()
    if kind not in _PART_KINDS:
        return "unknown-part", f"`{kind}:` is not a part: use {', '.join(_PART_KINDS)}"
    if kind == "row" and body:
        return "malformed", "a `row:` holds no text of its own, only the parts under it"
    if kind != "row" and not body:
        return "malformed", f"a `{kind}:` with no text"
    if kind == "list" and any(not item.strip() for item in body.split("|")):
        return "malformed", "an empty item in a `list:`"
    if dot is not None and dot < 1:
        return "malformed", "a dot is numbered from 1"
    return indent // 2, kind, mark, dot


def _symbol(name: str) -> str | None:
    found = _IDENT.findall(name.split("(", 1)[0])
    return found[-1] if found else None


_STATE_TRAILER = re.compile(r"\s*(\((?:start|final|proposed)\)|shows\s+[\w.-]+)\s*$")
_ARROW = re.compile(r"^(?P<from>.+?)\s*->\s*(?P<to>[^:]+?)\s*(?::\s*(?P<label>.*))?$")


def _state_line(row: str, names: set):
    """(name, flags) for one state line, or an error string."""
    name, colon, rest = row.partition(":")
    name, rest = name.strip(), rest.strip()
    if not colon or not name:
        return "a state line is `name: one sentence`"
    if name in names:
        return f"the state `{name}` is written twice"
    flags, shows = set(), None
    while True:
        m = _STATE_TRAILER.search(rest)
        if not m:
            break
        word = m.group(1).split()
        flags.add(word[0].strip("()"))
        if word[0] == "shows":
            shows = word[1]
        rest = rest[:m.start()].strip()
    if not rest:
        return f"the state `{name}` has no sentence"
    return name, flags, shows


def _grid_cells(row: str) -> list:
    if "|" in row:
        return [c.strip() for c in re.sub(r"^\||\|$", "", row.strip()).split("|")]
    return row.split()


def check_states(block: dict, label: str, findings: list, screens: set, shown: set) -> int:
    """Check one `states` block. Returns its state count."""
    start_line = block["start"]

    def add(level, rule, lineno, message):
        findings.append(Finding(level, rule, f"{label}:{lineno}", message))

    if not block["closed"]:
        add("ERROR", "malformed", start_line, "the `states` block is never closed")
    if not block["title"]:
        add("WARNING", "no-title", start_line, "name the lifecycle after `states`")
    if block["notes"] > MAX_NOTE_LINES:
        add("WARNING", "long-note", start_line,
            f"{block['notes']} `note:` lines under the block, the budget is {MAX_NOTE_LINES}")
    if not block["rows"]:
        add("ERROR", "malformed", start_line, "the `states` block is empty")
        return 0

    states: dict = {}
    arrows, grid_rows, in_grid = [], [], False
    for lineno, row in block["rows"]:
        text = row.strip()
        if re.fullmatch(r"grid:", text, re.I):
            if in_grid:
                add("ERROR", "malformed", lineno, "a second `grid:`")
            in_grid = True
        elif in_grid:
            grid_rows.append((lineno, text))
        elif re.match(r"^[^:]*->", text):
            m = _ARROW.match(text)
            if not m:
                add("ERROR", "malformed", lineno, "an arrow is `from -> to: label`")
                continue
            to = re.sub(r"\s*\(proposed\)\s*$", "", m.group("to"))
            arrows.append((lineno, m.group("from").strip(), to.strip()))
        else:
            parsed = _state_line(text, set(states))
            if isinstance(parsed, str):
                add("ERROR", "malformed", lineno, parsed)
            else:
                states[parsed[0]] = {"flags": parsed[1], "shows": parsed[2], "line": lineno}

    if len(states) > MAX_STATES:
        add("ERROR", "over-budget", start_line,
            f"{len(states)} states, the viewer draws {MAX_STATES} at most and shows more as plain code")
    if len(arrows) > MAX_ARROWS:
        add("WARNING", "over-budget", start_line, f"over budget: {len(arrows)} arrows, at most {MAX_ARROWS}")
    for lineno, a, b in arrows:
        for name in (a, b):
            if name not in states:
                add("ERROR", "unknown-state", lineno, f"the arrow names `{name}`, which is not a listed state")

    placed: set = set()
    columns = 0
    for lineno, row in grid_rows:
        cells = _grid_cells(row)
        columns = max(columns, len(cells))
        for cell in cells:
            if cell in (".", ""):
                continue
            if cell not in states:
                add("ERROR", "unknown-state", lineno, f"the grid names `{cell}`, which is not a listed state")
            elif cell in placed:
                add("ERROR", "malformed", lineno, f"the grid places `{cell}` twice")
            placed.add(cell)
    if states and not grid_rows:
        add("ERROR", "not-in-grid", start_line, "no `grid:` section places the states")
    elif grid_rows:
        for name, info in states.items():
            if name not in placed:
                add("ERROR", "not-in-grid", info["line"], f"`{name}` is missing from the grid")
    if len(grid_rows) > MAX_ROWS or columns > MAX_COLUMNS:
        add("ERROR", "over-budget", start_line,
            f"a grid of {columns} by {len(grid_rows)}, the viewer draws {MAX_COLUMNS} by {MAX_ROWS} at most")

    for name, info in states.items():
        if info["shows"]:
            shown.add(info["shows"])
        if info["shows"] and info["shows"] not in screens:
            add("ERROR", "unknown-screen", info["line"],
                f"state {name} shows screen {info['shows']}, which no screen block defines")

    starts = [n for n, i in states.items() if "start" in i["flags"]]
    if len(starts) > 1:
        add("ERROR", "malformed", states[starts[1]]["line"], "more than one `(start)` state")
    first = (starts or list(states))[:1]
    reached = set(first)
    queue = list(first)
    while queue:
        here = queue.pop()
        for _lineno, a, b in arrows:
            if a == here and b in states and b not in reached:
                reached.add(b)
                queue.append(b)
    for name, info in states.items():
        if name not in reached:
            add("ERROR", "unreachable", info["line"], f"`{name}` cannot be reached from the start state")
    leaving = {a for _lineno, a, _b in arrows}
    for name, info in states.items():
        if name not in leaving and "final" not in info["flags"]:
            add("ERROR", "dead-end", info["line"], f"`{name}` has no way out and is not marked `(final)`")
    return len(states)


def check_screens(screens: list, add, size: str | None, seen: set) -> int:
    """Hold each screen block to its grammar, budget and note pairing. Returns the parts drawn."""
    if size == "simple" and screens:
        add("WARNING", "simple-size", screens[0]["start"],
            "this spec is sized `simple`, which writes no screens")
    if len(screens) > MAX_SCREENS:
        add("WARNING", "over-budget", screens[MAX_SCREENS]["start"],
            f"{len(screens)} screens, the budget is {MAX_SCREENS}")

    total = 0
    for block in screens:
        start, name = block["start"], block["name"]
        if not block["closed"]:
            add("ERROR", "malformed", start, "the `screen` block is never closed")
        if not name:
            add("ERROR", "malformed", start, "name the screen after `screen`: one word other blocks can refer to")
        elif not _SCREEN_NAME.match(name):
            add("ERROR", "malformed", start, f"`{name}` is not a one-word name (letters, digits, `-` and `_`)")
        elif name in seen:
            add("ERROR", "duplicate-screen", start, f"a screen called `{name}` is already in this plan")
        seen.add(name)
        if name and not block["title"]:
            add("WARNING", "no-title", start, "give the screen a title after its name")
        if not block["rows"]:
            add("ERROR", "malformed", start, "the `screen` block is empty")

        depth_kinds: list = []
        dots: list = []
        count = 0
        for lineno, row in block["rows"]:
            parsed = parse_part(row)
            if isinstance(parsed[0], str):
                add("ERROR", parsed[0], lineno, parsed[1])
                continue
            depth, kind, _mark, dot = parsed
            count += 1
            if depth > len(depth_kinds):
                add("ERROR", "bad-indent", lineno, "indented more than one level past the line above")
            elif depth > 0 and depth_kinds[depth - 1] != "row":
                add("ERROR", "bad-indent", lineno, "only a `row:` has parts under it")
            del depth_kinds[depth:]
            depth_kinds.append(kind)
            if dot is not None:
                if dot in dots:
                    add("ERROR", "malformed", lineno, f"dot {dot} is on two parts")
                dots.append(dot)
        total += count

        if count > MAX_PARTS:
            add("ERROR", "over-budget", start, f"{count} parts, the budget is {MAX_PARTS} a screen")
        if len(set(dots)) > MAX_DOTS:
            add("ERROR", "over-budget", start, f"{len(set(dots))} dots, the budget is {MAX_DOTS} a screen")
        noted = [n for _, n in block["notes"]]
        for n in sorted(set(dots) - set(noted)):
            add("ERROR", "dot-without-note", start, f"dot {n} has no `{n}: …` note under the block")
        first: set = set()
        for lineno, n in block["notes"]:
            if n in first:
                add("ERROR", "malformed", lineno, f"note {n} is written twice")
            first.add(n)
            if n not in dots:
                add("ERROR", "note-without-dot", lineno, f"note {n} has no `({n})` dot in the block")
    return total


def _source(cache: dict, root: Path, path: str) -> list:
    if path not in cache:
        src = _lines((root / path).read_text(encoding="utf-8", errors="replace"))
        cache[path] = src[:-1] if src and src[-1] == "" else src
    return cache[path]


def _check_code(block: dict, root: Path, cache: dict, add) -> None:
    start, info, body = block["start"], block["info"], block["body"]
    if isinstance(info, str):
        return add("ERROR", "malformed", start, info)
    if not block["closed"]:
        return add("ERROR", "malformed", start, "the code block is never closed")
    if not any(row.strip() for _n, row in body):
        return add("ERROR", "malformed", start, "the code block is empty")

    kind, path, first, last = info["kind"], info["path"], info["first"], info["last"]
    shown = f"{first}-{first + len(body) - 1}"
    for low, high in info["hl"]:
        if low < first or high > first + len(body) - 1:
            add("ERROR", "malformed", start,
                f"`hl` names line {low if low < first else high}, the block shows {shown}")
            break
    for lineno, line, note in block["pins"]:
        if not note:
            add("ERROR", "malformed", lineno, f"`pin {line}:` has no text")
        if not first <= line < first + len(body):
            add("ERROR", "malformed", lineno, f"`pin {line}` is outside the lines shown, {shown}")
    if len(block["pins"]) > MAX_PINS:
        add("WARNING", "too-many-pins", start,
            f"{len(block['pins'])} pins, the budget is {MAX_PINS} a block")

    if kind == "sketch":
        if len(body) > MAX_SKETCH_LINES:
            add("WARNING", "sketch-too-long", start,
                f"{len(body)} lines, the budget is {MAX_SKETCH_LINES} a sketch")
        return
    want = last - first + 1
    if len(body) != want:
        add("ERROR", "range-mismatch", start,
            f"{len(body)} lines in the block, `{first}-{last}` is {want}")
    try:
        src = _source(cache, root, path) if (root / path).is_file() else None
    except (OSError, ValueError):
        return add("WARNING", "unreadable", start, f"`{path}` could not be read, so it is unverified")
    if src is None:
        return add("ERROR", "missing-file", start, f"`{path}` does not exist (code not written yet is a `sketch`)")
    if last > len(src):
        return add("ERROR", "line-out-of-range", start,
                   f"`{path}` has {len(src)} lines, there is no line {last}")
    if len(body) != want:
        return
    for offset, (lineno, row) in enumerate(body):
        if row.rstrip() != src[first - 1 + offset].rstrip():
            add("WARNING", "text-differs", lineno,
                f"line {first + offset} of `{path}` does not read like this")
            break


def check_text(text: str, label: str, root: Path, size: str | None = None, calls: bool = True) -> dict:
    findings = []
    declared = []

    def add(level, rule, lineno, message):
        findings.append(Finding(level, rule, f"{label}:{lineno}", message))

    blocks = find_blocks(text) if calls else []
    if size == "simple" and blocks:
        add("WARNING", "simple-size", blocks[0]["start"],
            "this spec is sized `simple`, which writes no call paths")
    if len(blocks) > MAX_BLOCKS:
        add("WARNING", "too-many-blocks", blocks[MAX_BLOCKS]["start"],
            f"{len(blocks)} call-path blocks, the budget is {MAX_BLOCKS}")

    cache: dict = {}
    for block in blocks:
        start = block["start"]
        if not block["closed"]:
            add("ERROR", "malformed", start, "the `calls` block is never closed")
        if not block["title"]:
            add("WARNING", "no-title", start, "name the behaviour after `calls`")
        if not block["rows"]:
            add("ERROR", "malformed", start, "the `calls` block is empty")
        if len(block["rows"]) > MAX_LINES:
            add("WARNING", "too-long", start,
                f"{len(block['rows'])} lines, the budget is {MAX_LINES} a block")
        if block["notes"] > MAX_NOTE_LINES:
            add("WARNING", "long-note", start,
                f"{block['notes']} `note:` lines under the block, the budget is {MAX_NOTE_LINES}")

        depth = -1
        for lineno, row in block["rows"]:
            parsed = parse_row(row)
            if isinstance(parsed, str):
                add("ERROR", "malformed", lineno, parsed)
                continue
            mark, level, name, path, line, new = parsed
            if depth == -1 and level != 0:
                add("ERROR", "malformed", lineno, "the first line is the entry point, at no indent")
            elif depth != -1 and level == 0:
                add("ERROR", "malformed", lineno, "a second entry point: one per block")
            elif level > depth + 1:
                add("ERROR", "malformed", lineno, "indented more than one level past the line above")
            depth = level
            declared.append({"mark": mark.strip() or " ", "name": name, "path": path,
                             "line": line, "new": new})

            target = root / path
            if new:
                if target.exists():
                    add("ERROR", "new-exists", lineno,
                        f"`{path}` already exists: drop `**new**` and cite the line")
                continue
            if not target.is_file():
                add("ERROR", "missing-file", lineno,
                    f"`{path}` does not exist (a file the change creates takes `**new**` and no line)")
                continue
            if line is None:
                add("WARNING", "no-line", lineno, f"`{path}` has no `:line`, so it is unverified")
                continue
            src = _source(cache, root, path)
            if line < 1 or line > len(src):
                add("ERROR", "line-out-of-range", lineno,
                    f"`{path}` has {len(src)} lines, there is no line {line}")
                continue
            symbol = _symbol(name)
            if mark != "+" and symbol:
                window = "\n".join(src[max(0, line - 1 - NEAR):line + NEAR])
                if not re.search(rf"(?<![\w$]){re.escape(symbol)}(?![\w$])", window):
                    add("WARNING", "symbol-not-near", lineno,
                        f"`{symbol}` is not within {NEAR} lines of `{path}:{line}`")

    screens = find_screens(text) if calls else []
    parts = check_screens(screens, add, size, set())

    state_blocks = find_blocks(text, "states") if calls else []
    if size == "simple" and state_blocks:
        add("WARNING", "simple-size", state_blocks[0]["start"],
            "this spec is sized `simple`, which writes no state block")
    if len(state_blocks) > MAX_STATE_BLOCKS:
        add("WARNING", "too-many-blocks", state_blocks[MAX_STATE_BLOCKS]["start"],
            f"{len(state_blocks)} state blocks, the budget is {MAX_STATE_BLOCKS}")
    screen_names = {b["name"] for b in screens if b["name"]}
    shown: set = set()
    states_total = sum(check_states(b, label, findings, screen_names, shown) for b in state_blocks)
    if state_blocks:
        for b in screens:
            if b["name"] and b["name"] not in shown:
                add("WARNING", "screen-not-shown", b["start"],
                    f"screen `{b['name']}` is not shown by any state: end the state it belongs to with "
                    f"`shows {b['name']}`, or leave it if it belongs to none")

    code = find_code_blocks(text)
    for block in code:
        _check_code(block, root, cache, add)

    errors = sum(1 for f in findings if f.level == "ERROR")
    return {
        "plan": label,
        "blocks": len(blocks),
        "lines": sum(len(b["rows"]) for b in blocks),
        "screens": len(screens),
        "parts": parts,
        "code_blocks": len(code),
        "state_blocks": len(state_blocks),
        "states": states_total,
        "errors": errors,
        "warnings": len(findings) - errors,
        "findings": [f.to_dict() for f in findings],
        "declared": declared,
    }


def _size(feature_dir: Path) -> str | None:
    try:
        ctx = json.loads((feature_dir / ".spec-context.json").read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return None
    return ctx.get("size") if isinstance(ctx, dict) else None


def _merge(reports: list) -> dict:
    out = {"plan": ", ".join(r["plan"] for r in reports), "blocks": 0, "lines": 0,
           "screens": 0, "parts": 0, "code_blocks": 0, "state_blocks": 0, "states": 0, "errors": 0, "warnings": 0, "findings": [], "declared": []}
    for r in reports:
        for key in ("blocks", "lines", "screens", "parts", "code_blocks", "state_blocks", "states", "errors", "warnings"):
            out[key] += r[key]
        out["findings"] += r["findings"]
        out["declared"] += r["declared"]
    return out


def render_human(report: dict) -> str:
    counts = []
    if report["blocks"]:
        counts += [f"{report['blocks']} call path{'' if report['blocks'] == 1 else 's'}",
                   f"{report['lines']} lines"]
    if report["screens"]:
        counts += [f"{report['screens']} screen{'' if report['screens'] == 1 else 's'}",
                   f"{report['parts']} parts"]
    if report["code_blocks"]:
        counts.append(f"{report['code_blocks']} code block{'' if report['code_blocks'] == 1 else 's'}")
    if report["state_blocks"]:
        counts.append(f"{report['state_blocks']} state block{'' if report['state_blocks'] == 1 else 's'}"
                      f" ({report['states']} states)")
    head = f"[plan-check] {report['plan']}: {', '.join(counts)}"
    if not report["findings"]:
        what = ("Every cited file and line checks out" if report["blocks"] or report["code_blocks"]
                else "Every screen holds to its grammar" if not report["state_blocks"]
                else "Every block holds to its grammar")
        return f"{head}. {what}."
    rows = [f"  {f['level']:<7} {f['where']}  {f['message']} ({f['rule']})"
            for f in report["findings"]]
    tail = f"  {report['errors']} error(s), {report['warnings']} warning(s)"
    return "\n".join([head, *rows, tail])


def _check_files(files: list, root: Path, feature_dir: Path | None) -> dict:
    size = _size(feature_dir) if feature_dir else None
    reports = []
    for path in files:
        try:
            text = path.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        reports.append(check_text(text, path.name, root, size))
    if feature_dir:
        try:
            tasks = check_text((feature_dir / CODE_FILE).read_text(encoding="utf-8", errors="replace"),
                               CODE_FILE, root, calls=False)
        except OSError:
            tasks = None
        if tasks and tasks["code_blocks"]:
            reports.append(tasks)
    return _merge(reports)


def check_feature(feature_dir: Path, root: Path | None = None) -> dict:
    files = [feature_dir / "plan.md", *(feature_dir / side for side in SIDE_FILES)]
    return _check_files(files, root or _repo_root_for(feature_dir), feature_dir)


def has_blocks(report: dict) -> bool:
    return bool(report["blocks"] or report["screens"] or report["code_blocks"] or report["state_blocks"])


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="Check a plan's call paths and screens (records the result unless --plan or --no-record)")
    ap.add_argument("--feature-dir", default=None, help="the spec whose plan.md to check")
    ap.add_argument("--plan", default=None, help="check this file instead of the spec's plan")
    ap.add_argument("--root", default=None,
                    help="where cited paths resolve from (default: the spec's repository)")
    ap.add_argument("--no-record", action="store_true", help="do not record the result in the spec's context")
    ap.add_argument("--json", dest="as_json", action="store_true")
    ap.add_argument("--strict", action="store_true",
                    help="exit 1 when any error is found; without it the check always exits 0")
    args = ap.parse_args(argv)

    feature_dir = None
    if args.plan:
        files = [Path(args.plan)]
        root = Path(args.root) if args.root else _repo_root()
    else:
        feature_dir = resolve_feature_dir(_repo_root(), args.feature_dir)
        if feature_dir is None or not feature_dir.is_dir():
            print("[plan-check] Could not resolve the spec to check. Nothing checked.", file=sys.stderr)
            return 0
        files = [feature_dir / "plan.md", *(feature_dir / side for side in SIDE_FILES)]
        root = Path(args.root) if args.root else _repo_root_for(feature_dir)

    started = time.monotonic()
    report = _check_files(files, root, feature_dir)
    if feature_dir and not args.no_record:
        try:
            from capture import record_plan_report
            record_plan_report(feature_dir, report, started)
        except Exception as err:
            print(f"[companion] Warning: could not record the plan check ({err}).", file=sys.stderr)

    if args.as_json:
        print(json.dumps(report, indent=2))
    elif has_blocks(report):
        print(render_human(report))
    return 1 if (args.strict and report["errors"]) else 0


if __name__ == "__main__":
    sys.exit(main())
