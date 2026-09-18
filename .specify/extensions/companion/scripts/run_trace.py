#!/usr/bin/env python3
"""The run self-trace — one line per handled capture or drift call.

Every script in the capture runtime returns 0 on failure by design, printing the
reason to stderr and discarding it. That contract is what keeps a capture defect
from halting a user's pipeline, and it is also why capture failures are invisible
today. The tracer is the record those discarded reasons land in: one append per
handled call, success or failure, written from inside the scripts the command
bodies already invoke — so it costs no extra call and adds no prompt weight.

The file is local, per-spec, size-capped, and never committed. Nothing but the
doctor reads it; no production code path may branch on its contents. A call that
could not resolve a spec at all lands in the repo-level unattributed log instead
of being dropped — that failure is precisely the one worth keeping.

Named `run_trace` rather than `trace` because the scripts directory is prepended
to `sys.path`, and a module called `trace` there would shadow the standard
library's for every process that loads one of these scripts.

Every failure inside this module is swallowed. It runs on paths that are already
failing, so a tracer that could raise would turn a recorded problem into a crash.
Stdlib only.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

TRACE_NAME = ".trace.jsonl"

#: Operations a traced call resolves to. `unknown` covers a call that failed
#: before its intent could be determined — which is most of the interesting ones.
OPS = (
    "lifecycle",
    "capture",
    "set",
    "finish",
    "advance",
    "task-append",
    "task-journal",
    "task-close",
    "tasks-sync",
    "materialize",
    "mark-complete",
    "fold-living-spec",
    "drift-compute",
    "unknown",
)

#: Where a call that could not resolve any spec records itself. Dropping those
#: lines would hide the single most common capture failure there is.
UNATTRIBUTED_DIR = "specs"

#: Field order is fixed so a trace line diffs cleanly and reads left to right.
FIELDS = ("at", "tool", "op", "ok", "reason", "spec", "files", "bytes", "in_bytes", "ms")


class TraceEvent:
    """One handled call. `at` and `ms` are stamped by the tracer, never supplied."""

    def __init__(self, tool: str, op: str, ok: bool, *, at: str, ms: int,
                 reason: str | None = None, spec: str | None = None,
                 files: list | None = None, written: int = 0, read: int = 0):
        self.at = at
        self.tool = tool
        self.op = op if op in OPS else "unknown"
        self.ok = bool(ok)
        self.reason = None if self.ok else (reason or None)
        self.spec = spec
        self.files = list(files or [])
        self.bytes = int(written)
        self.in_bytes = int(read)
        self.ms = int(ms)

    def as_dict(self) -> dict:
        return {f: getattr(self, f) for f in FIELDS}

    def line(self) -> str:
        """The serialized line — compact, one per row, newline-terminated."""
        return json.dumps(self.as_dict(), ensure_ascii=False, separators=(",", ":")) + "\n"


def trace_path(feature_dir: Path) -> Path:
    return Path(feature_dir) / TRACE_NAME


#: Bytes the trace may occupy before the oldest entries roll off. The doctor only
#: ever looks backwards a short way, so the newest entries are the valuable ones.
MAX_BYTES = 256 * 1024

IGNORE_NAME = ".gitignore"


def _ensure_ignored(feature_dir: Path) -> None:
    """Make the trace self-ignoring.

    The file must be gitignored in every project that installs the extension, and
    a capture call cannot edit a user's root ignore file as a side effect. A
    one-line sibling needs no user action and no installer step, and is skipped
    when a rule already covers the file.
    """
    ignore = Path(feature_dir) / IGNORE_NAME
    try:
        if ignore.is_file():
            if any(line.strip() == TRACE_NAME for line in ignore.read_text(encoding="utf-8").splitlines()):
                return
            with ignore.open("a", encoding="utf-8") as fh:
                fh.write(f"{TRACE_NAME}\n")
            return
        ignore.write_text(
            "# Run self-trace — local diagnostic, read only by the doctor.\n"
            f"{TRACE_NAME}\n",
            encoding="utf-8",
        )
    except OSError:
        pass


def _enforce_cap(path: Path) -> None:
    """Roll the oldest entries off once the file passes the cap.

    A marker line records how many were dropped, so the doctor reports counts as
    "at least N" rather than presenting a partial file as a total.
    """
    try:
        if path.stat().st_size <= MAX_BYTES:
            return
        lines = path.read_text(encoding="utf-8", errors="replace").splitlines(keepends=True)
        dropped_before = 0
        if lines and lines[0].lstrip().startswith('{"truncated"'):
            try:
                dropped_before = int(json.loads(lines[0]).get("truncated", 0))
            except (ValueError, TypeError):
                dropped_before = 0
            lines = lines[1:]
        keep, size = [], 0
        for line in reversed(lines):
            size += len(line.encode("utf-8"))
            if size > MAX_BYTES // 2:
                break
            keep.append(line)
        keep.reverse()
        dropped = dropped_before + (len(lines) - len(keep))
        header = json.dumps({"truncated": dropped}, separators=(",", ":")) + "\n"
        try:
            import companion_config as cc

            cc.atomic_write_text(str(path), header + "".join(keep))
        except ImportError:
            tmp = path.with_suffix(path.suffix + ".tmp")
            tmp.write_text(header + "".join(keep), encoding="utf-8")
            tmp.replace(path)
    except (OSError, ValueError):
        pass


def record(tool: str, op: str, ok: bool, *, ms: int, feature_dir=None,
           reason: str | None = None, spec: str | None = None,
           files=None, written: int = 0, read: int = 0) -> None:
    """Append one event. Swallows every failure — it runs on failing paths.

    A call that could not resolve a spec directory lands in the repo-level
    unattributed log rather than being dropped — that is the failure the doctor
    most needs to see.
    """
    try:
        from spec_context import _now_iso

        if feature_dir is None:
            from spec_context import _repo_root

            feature_dir = Path(_repo_root()) / UNATTRIBUTED_DIR
            if not feature_dir.is_dir():
                return

        event = TraceEvent(
            tool, op, ok, at=_now_iso(), ms=ms, reason=reason, spec=spec,
            files=files, written=written, read=read,
        )
        path = trace_path(feature_dir)
        _ensure_ignored(feature_dir)
        with path.open("a", encoding="utf-8") as fh:
            fh.write(event.line())
        _enforce_cap(path)
    except Exception as exc:  # noqa: BLE001 — a tracer that can raise is worse than no tracer
        # ...but a tracer that fails in total silence is barely better. A capture
        # can succeed while its trace entry cannot be written — the publish renames
        # into the directory, the trace appends to a file — and the run then has a
        # write nothing recorded, with the reader reporting its short count as a
        # total. Say it once, on stderr, and leave a marker the reader can find.
        _note_trace_failure(feature_dir, exc)


#: Written beside the trace when the trace itself could not be appended, so a
#: reader can say "this count is short" instead of presenting it as complete.
LOST_NAME = ".trace-lost"


def _note_trace_failure(feature_dir, exc: Exception) -> None:
    try:
        print(f"[companion] Warning: this call was not recorded in the run trace "
              f"({type(exc).__name__}: {exc}). The trace is incomplete for this run.",
              file=sys.stderr)
    except Exception:  # noqa: BLE001
        pass
    try:
        from spec_context import _now_iso

        stamp = _now_iso()
    except Exception:  # noqa: BLE001 — a timestamp is nice, evidence is the point
        stamp = "unknown-time"
    # The shared file is one spec's last resort, but it is every spec's neighbour.
    # A line there with no spec on it cannot be told apart from a sibling's failure,
    # and a reader that merges it into each spec's verdict turns one real problem
    # into a false alarm on every spec in the repo. So the fallback names its spec.
    own = Path(feature_dir) / LOST_NAME
    for target in (own, Path(feature_dir).parent / LOST_NAME):
        tag = "" if target == own else f" spec={Path(feature_dir).name}"
        try:
            with target.open("a", encoding="utf-8") as fh:
                fh.write(f"{stamp}{tag} {type(exc).__name__}: {exc}\n")
            return
        except Exception:  # noqa: BLE001 — nowhere writable; stderr was the last resort
            continue


class Read:
    """What a trace file says. Every count is honest about what it could not read."""

    def __init__(self):
        self.events: list = []
        self.verdicts: list = []
        self.unparseable = 0
        self.truncated = 0

    @property
    def exact(self) -> bool:
        """False once entries have rolled off — counts are lower bounds, not totals."""
        return self.truncated == 0 and self.unparseable == 0

    def failures(self) -> list:
        return [e for e in self.events if not e.get("ok")]

    def rewrites(self) -> dict:
        """How many times each file was written during the traced calls."""
        counts: dict = {}
        for e in self.events:
            for f in e.get("files") or []:
                counts[f] = counts.get(f, 0) + 1
        return counts

    def bytes_written(self) -> int:
        return sum(int(e.get("bytes") or 0) for e in self.events)

    def bytes_read(self) -> int:
        return sum(int(e.get("in_bytes") or 0) for e in self.events)


def read(feature_dir) -> Read | None:
    """Parse a spec's trace, or None when there is no trace to read."""
    path = trace_path(feature_dir)
    if not path.is_file():
        return None
    out = Read()
    try:
        raw = path.read_text(encoding="utf-8", errors="replace")
    except OSError:
        return None
    for line in raw.splitlines():
        line = line.strip()
        if not line:
            continue
        try:
            obj = json.loads(line)
        except ValueError:
            out.unparseable += 1
            continue
        if not isinstance(obj, dict):
            out.unparseable += 1
        elif "truncated" in obj:
            try:
                out.truncated += int(obj["truncated"])
            except (TypeError, ValueError):
                out.unparseable += 1
        elif "verdict" in obj:
            out.verdicts.append(obj)
        elif "op" in obj:
            out.events.append(obj)
        else:
            out.unparseable += 1
    return out
