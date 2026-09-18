#!/usr/bin/env python3
"""The doctor's record-derived checks.

Everything here reads `.spec-context.json` and the spec's own documents, so it
works on a spec created long before run tracing existed — which is the point:
the specs causing pain today are the ones already on disk.

Each check returns `(CheckStatus, [Finding, …])`. A check that cannot look
returns a skip with its reason instead of an empty finding list, because "found
nothing" and "could not look" must never print the same way. Stdlib only.
"""

from __future__ import annotations

import json
import re
import sys
from datetime import datetime, timedelta, timezone
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from doctor import (  # noqa: E402
    CheckStatus,
    Finding,
    log_entries,
    parse_time,
    plural,
    run_window,
)
from spec_context import (  # noqa: E402
    STEP_COMPLETED_STATUS,
    STEP_ORDER,
    _entry_kind,
    _is_per_task,
    _is_step_level,
)
from task_sync import parse_task_markers  # noqa: E402

#: Steps whose boundaries the extension stamps; an `ai` complete there is an
#: anomaly, because it lands first and permanently blocks the hook's close.
EXTENSION_STEPS = {"specify", "plan", "tasks", "implement"}
#: Steps the AI self-closes. An `extension` complete here is the mirror anomaly.
AI_STEPS = {"clarify", "analyze"}

#: Task finishes packed tighter than this are journaling recorded in one burst,
#: not work that genuinely took that long.
BURST_WINDOW_SECONDS = 5.0
BURST_MIN_TASKS = 3

#: How long a step may sit open before an unfinished start stops reading as "still
#: running" and starts reading as "never finished". The doctor cannot see whether a
#: process is alive, so time since the start is the only honest signal — and the
#: symptom this check exists for is a step that has been open for days.
IN_FLIGHT_GRACE_SECONDS = 30 * 60

#: Floor for the cadence-derived grace, and the multiple of a step's own longest
#: observed gap it may exceed before an open start reads as abandoned. A flat
#: 30-minute grace let a step sit open for 8m33s — with the next step unreachable
#: and the viewer still saying "creating tasks" — and reported `clean`, because
#: 8m33s is under 30 minutes. A step that has been recording boundaries every
#: ~70 seconds and then goes quiet for eight minutes is the signal; its own
#: cadence is the only honest yardstick for that, not a constant.
CADENCE_GRACE_FLOOR_SECONDS = 5 * 60
CADENCE_GRACE_MULTIPLE = 4


def _no_record(check: str, feature_dir: Path, ctx: dict) -> CheckStatus | None:
    """The shared "there is nothing to read" skip."""
    path = feature_dir / ".spec-context.json"
    if not path.is_file():
        return CheckStatus(check, "skipped", "no .spec-context.json — nothing recorded for this spec")
    if not ctx:
        return CheckStatus(check, "skipped", f"{path.name} is unreadable or not an object")
    if not log_entries(ctx):
        return CheckStatus(check, "skipped", "history[] is empty — nothing to check")
    return None


def _step_activity(log: list, step: str) -> list:
    """Every timestamp this step recorded, in order — its start, substeps and tasks."""
    out = []
    for e in log:
        if e.get("step") != step:
            continue
        ts = parse_time(e.get("at"))
        if ts is not None:
            out.append(ts)
    return sorted(out)


def _cadence_grace(log: list, step: str) -> tuple:
    """How long this step may sit quiet, judged by how often it was recording.

    Returns (grace_seconds, last_activity). A step with fewer than two recorded
    boundaries has no cadence to reason from, so it keeps the flat grace.
    """
    stamps = _step_activity(log, step)
    if len(stamps) < 2:
        return IN_FLIGHT_GRACE_SECONDS, (stamps[-1] if stamps else None)
    longest = max((b - a).total_seconds() for a, b in zip(stamps, stamps[1:]))
    grace = max(CADENCE_GRACE_FLOOR_SECONDS, longest * CADENCE_GRACE_MULTIPLE)
    return min(grace, IN_FLIGHT_GRACE_SECONDS), stamps[-1]


def _thin_boundary_steps(ctx: dict) -> list:
    """Steps whose recorded substep count is far below what the run's other steps managed.

    A step that logs one boundary for fifteen minutes is not measured, it is
    labelled. Comparing against the run's own other steps avoids hardcoding how
    many boundaries a step "should" have, which varies by command and by recipe.
    """
    counts = {}
    for e in log_entries(ctx):
        step, sub = e.get("step"), e.get("substep")
        if not isinstance(step, str):
            continue
        counts.setdefault(step, set())
        if isinstance(sub, str) and sub:
            counts[step].add(sub)
    sizes = {k: len(v) for k, v in counts.items()}
    others = [n for n in sizes.values() if n > 0]
    if len(others) < 3:
        return []
    others.sort()
    median = others[len(others) // 2]
    if median < 2:
        return []
    return sorted(
        (step, n, median) for step, n in sizes.items()
        if n < median / 2 and n < median - 1
    )


def _dangling_steps(ctx: dict, now: datetime | None = None) -> list:
    """Step-level starts with no matching complete.

    The run's own current step is given a grace period — it may genuinely still
    be running. That grace is derived from the step's own recorded cadence rather
    than a flat constant, so a step that was journaling every minute and has been
    silent for eight is named instead of being read as in-flight. Past the grace,
    an open start is the symptom: a step that started and never finished, which is
    what leaves a spec stuck.
    """
    log = log_entries(ctx)
    current = ctx.get("currentStep")
    terminal = ctx.get("status") in ("completed", "archived", "implemented")
    now = now or datetime.now(timezone.utc)
    started, completed = {}, set()
    for e in log:
        step = e.get("step")
        if not isinstance(step, str) or not _is_step_level(e):
            continue
        if _entry_kind(e) == "start":
            started.setdefault(step, e.get("at"))
        else:
            completed.add(step)
    out = []
    for step, at in started.items():
        if step in completed:
            continue
        if step == current and not terminal:
            grace, last = _cadence_grace(log, step)
            ts = last or parse_time(at)
            if ts is None or (now - ts).total_seconds() < grace:
                continue  # still plausibly running, by this step's own cadence
        out.append((step, at))
    return sorted(out, key=lambda p: STEP_ORDER.get(p[0], 99))


def _journaled_task_ids(ctx: dict) -> set:
    return {
        e["task"] for e in log_entries(ctx)
        if _is_per_task(e) and isinstance(e.get("task"), str) and _entry_kind(e) == "complete"
    }


def _task_finish_times(ctx: dict) -> list:
    out = []
    for e in log_entries(ctx):
        if _is_per_task(e) and _entry_kind(e) == "complete":
            ts = parse_time(e.get("at"))
            if ts is not None:
                out.append((e.get("task"), ts))
    return sorted(out, key=lambda p: p[1])


def _attribution_anomalies(ctx: dict) -> list:
    out = []
    for e in log_entries(ctx):
        step, by = e.get("step"), e.get("by")
        if not isinstance(step, str) or _entry_kind(e) != "complete" or not _is_step_level(e):
            continue
        if step in EXTENSION_STEPS and by == "ai":
            out.append((step, by, e.get("at"),
                        "the extension stamps this step's boundaries; an ai complete lands "
                        "first and permanently blocks the hook's close"))
        elif step in AI_STEPS and by == "extension":
            out.append((step, by, e.get("at"),
                        "this step is self-closed by the ai; an extension complete here means "
                        "a hook fired for a step it does not own"))
    return out


def check_record(feature_dir: Path, ctx: dict, now: datetime | None = None) -> tuple:
    """Dangling steps, unjournaled tasks, burst journaling, attribution anomalies.

    `now` is injectable so a test can evaluate a fixture from a fixed vantage
    point — the in-flight grace period is relative to the moment of the check.
    """
    skip = _no_record("record", feature_dir, ctx)
    if skip is not None:
        return skip, []

    findings = []

    for step, at in _dangling_steps(ctx, now):
        findings.append(Finding(
            "record", "problem",
            f"Step `{step}` started and never finished",
            f"start at {at}, no matching complete in history[]",
            {"step": step, "start_at": at},
        ))

    for step, own, median in _thin_boundary_steps(ctx):
        findings.append(Finding(
            "record", "warning",
            f"Step `{step}` recorded {own} internal "
            f"{'boundary' if own == 1 else 'boundaries'} where its siblings recorded {median}",
            "the step's internal shape was never measured, so its span is a single number with "
            "nothing inside it — anchor its boundaries, or read the per-task journal instead",
            {"step": step, "boundaries": own, "sibling_median": median},
        ))

    tasks_md = feature_dir / "tasks.md"
    if tasks_md.is_file():
        _all_ids, done = parse_task_markers(tasks_md)
        journaled = _journaled_task_ids(ctx)
        missing = [t for t in done if t not in journaled]
        if missing:
            shown = ", ".join(missing[:8]) + (f", +{len(missing) - 8} more" if len(missing) > 8 else "")
            findings.append(Finding(
                "record", "problem",
                f"{plural(len(missing), 'task')} checked in tasks.md with no journal entry",
                shown,
                {"tasks": missing},
            ))

    # Batching is a LOCAL property: several tasks stamped together. Measuring
    # first-to-last across the whole run missed the strongest possible case — a
    # run journaling four tasks at the identical millisecond, three times over,
    # scored a 60s span and warned about nothing. Cluster first, then judge each
    # cluster on its own span.
    finishes = _task_finish_times(ctx)
    clusters, current = [], []
    for entry in finishes:
        if current and (entry[1] - current[0][1]).total_seconds() > BURST_WINDOW_SECONDS:
            clusters.append(current)
            current = []
        current.append(entry)
    if current:
        clusters.append(current)

    bursts = [c for c in clusters if len(c) >= BURST_MIN_TASKS]
    if bursts:
        worst = max(bursts, key=len)
        span = (worst[-1][1] - worst[0][1]).total_seconds()
        batched = sum(len(c) for c in bursts)
        extra = (f" across {len(bursts)} batches" if len(bursts) > 1 else "")
        findings.append(Finding(
            "record", "warning",
            f"{batched} task finishes recorded in bursts{extra} — journaling was batched",
            f"the largest batch stamped {len(worst)} tasks inside {span:.1f}s; those "
            "timestamps reflect when the batch was written, not how long each task "
            "took, so the summaries are still trustworthy and the durations are not",
            {"tasks": [t for c in bursts for t, _ in c],
             "batches": len(bursts), "largest_batch": len(worst), "span_seconds": span},
        ))

    for step, by, at, why in _attribution_anomalies(ctx):
        findings.append(Finding(
            "record", "warning",
            f"Step `{step}` was closed by `{by}`",
            f"{why} (at {at})",
            {"step": step, "by": by, "at": at},
        ))

    return CheckStatus("record", "ran"), findings


def _derived_badges(ctx: dict) -> dict:
    """The viewer's step badges, re-derived from history[] in Python.

    The viewer builds its stepper from history[] alone once a context file is
    present — there is no file-existence fallback. So this derivation is what the
    pipeline bar actually shows, and comparing it against `status` is what makes
    the status-versus-display question decidable rather than a guess.
    """
    badges = {}
    for e in log_entries(ctx):
        step = e.get("step")
        if not isinstance(step, str) or not _is_step_level(e):
            continue
        kind = _entry_kind(e)
        if kind == "complete":
            badges[step] = "completed"
        elif badges.get(step) != "completed":
            badges[step] = "in-progress"
    return badges


def check_triage(feature_dir: Path, ctx: dict) -> tuple:
    """Is a status-versus-pipeline-bar mismatch a capture bug or a display bug?"""
    skip = _no_record("triage", feature_dir, ctx)
    if skip is not None:
        return skip, []

    status = ctx.get("status")
    badges = _derived_badges(ctx)

    #: The step whose completion `status` is asserting, if any.
    claimed = next((s for s, st in STEP_COMPLETED_STATUS.items() if st == status), None)
    if claimed is None:
        return CheckStatus("triage", "not-applicable"), []

    if badges.get(claimed) == "completed":
        return CheckStatus("triage", "ran"), [Finding(
            "triage", "note",
            "Records are consistent — suspect the display",
            f"status `{status}` and history[] agree that `{claimed}` finished, so the stepper "
            f"has what it needs; if the pipeline bar still will not advance, the defect is on "
            f"the display side, not in capture",
            {"status": status, "step": claimed, "badge": badges.get(claimed)},
        )]

    return CheckStatus("triage", "ran"), [Finding(
        "triage", "problem",
        "Records disagree with each other — capture path",
        f"status says `{status}`, but history[] has no step-level complete for `{claimed}` "
        f"(derived badge: {badges.get(claimed) or 'not-started'}). The viewer derives its "
        f"stepper from history[] alone, so the next step cannot be offered.",
        {"status": status, "step": claimed, "badge": badges.get(claimed)},
    )]


#: Substrings that identify a completion attempt in the trace.
_COMPLETION_OPS = ("mark-complete",)


#: Unattributed capture failures carry `spec: null` — they could not resolve a
#: spec, which is the whole reason they matter — so they can only be matched to a
#: run by time. The window is the first-to-last history entry, and the failures
#: worth finding happen OUTSIDE it: before the first entry exists, or after the
#: last one lands. An unpadded window discarded exactly those, so a run that
#: silently lost writes reported "failures: 0". Pad it, and never drop in silence.
WINDOW_PAD = timedelta(minutes=5)


def _in_window(ts, start, end) -> bool:
    """True when a timestamp falls inside the padded run window, or cannot be placed."""
    if ts is None or start is None or end is None:
        return True  # undateable: count it rather than hide it
    return start <= ts <= end


def _completion_attempts(feature_dir: Path, ctx: dict) -> list:
    """Completion attempts belonging to THIS spec.

    The spec's own trace is unambiguous. The repo-level unattributed log is
    shared, so an attempt is only this spec's if it falls inside this spec's run
    window — otherwise one unresolvable mark-complete from months ago would be
    reported as a refusal against every spec in the repository.
    """
    import run_trace

    out = []
    own = run_trace.read(feature_dir)
    if own is not None:
        out += [e for e in own.events if e.get("op") in _COMPLETION_OPS]

    shared = run_trace.read(Path(feature_dir).parent)
    if shared is not None:
        start, end = run_window(ctx, WINDOW_PAD)
        rel = Path(feature_dir).name
        for e in shared.events:
            if e.get("op") not in _COMPLETION_OPS:
                continue
            spec = e.get("spec")
            if spec and rel not in str(spec):
                continue
            if not _in_window(parse_time(e.get("at")), start, end):
                continue
            out.append(e)
    return out


def check_completion(feature_dir: Path, ctx: dict, report=None) -> tuple:
    """Why a spec that should be completed is not.

    Four outcomes stay strictly distinct. "Never attempted" and "attempted and
    never arrived" are different problems with different fixes, and collapsing
    them is exactly what leaves a spec silently stuck.
    """
    skip = _no_record("completion", feature_dir, ctx)
    if skip is not None:
        return skip, []

    status = ctx.get("status")
    attempts = _completion_attempts(feature_dir, ctx)
    verdict = {"attempted": bool(attempts), "outcome": None, "reason": None}

    def settled(findings: list) -> tuple:
        """Publish the verdict onto the report, then hand back this check's result."""
        if report is not None:
            report.completion = verdict
        return CheckStatus("completion", "ran"), findings

    if status in ("completed", "archived"):
        verdict.update(outcome="completed")
        return settled([])

    if not attempts:
        verdict.update(outcome="not-attempted")
        if not _tasks_all_checked(feature_dir):
            return settled([])
        return settled([Finding(
            "completion", "note",
            "Every task is checked but completion was never attempted",
            f"the spec sits at `{status}`; nothing tried to mark it complete, so this is "
            f"a step that did not run rather than a write that failed",
            verdict,
        )])

    failed = [a for a in attempts if not a.get("ok")]
    if failed:
        reason = failed[-1].get("reason") or "no reason recorded"
        verdict.update(outcome="refused", reason=reason)
        return settled([Finding(
            "completion", "problem",
            "Marking this spec complete was refused",
            reason,
            verdict,
        )])

    verdict.update(
        outcome="never-arrived",
        reason=f"a completion call was recorded as succeeding, but the status is still `{status}`",
    )
    return settled([Finding(
        "completion", "problem",
        "A completion write was recorded but the spec never landed as completed",
        verdict["reason"] + " — the write went somewhere other than this spec, or was "
        "overwritten afterwards",
        verdict,
    )])


def _tasks_all_checked(feature_dir: Path) -> bool:
    tasks_md = Path(feature_dir) / "tasks.md"
    if not tasks_md.is_file():
        return False
    all_ids, done = parse_task_markers(tasks_md)
    return bool(all_ids) and len(all_ids) == len(done)


def check_verification(feature_dir: Path, ctx: dict) -> tuple:
    """Did the run prove anything before implement closed?

    Running the project's own checks is prompt text, and prompt text has no
    observer. `verified[]` is already written whenever a run records a check it
    executed, so a completed implement with an empty list is a run that shipped
    code nothing was ever run against. Judged only as empty-or-not: interpreting
    entry contents is drift's job, not this check's.
    """
    skip = _no_record("verification", feature_dir, ctx)
    if skip is not None:
        return skip, []

    # Through _entry_kind/_is_step_level, not a raw `kind` read: the legacy
    # from/to entry shape is a completion too, and the specs that carry it are
    # exactly the already-on-disk runs this report exists for.
    closed = any(e.get("step") == "implement" and _is_step_level(e)
                 and _entry_kind(e) == "complete"
                 for e in log_entries(ctx))
    if not closed:
        return CheckStatus(
            "verification", "skipped",
            "implement has not closed — nothing to judge until a step claims it finished",
        ), []

    verified = ctx.get("verified")
    if isinstance(verified, list) and verified:
        return CheckStatus("verification", "ran"), []

    return CheckStatus("verification", "ran"), [Finding(
        "verification", "problem",
        "implement closed with nothing verified",
        "the step recorded no check it actually executed, so nothing it built was proven to "
        "work — record what you ran with `write-context.py --verified`, and if the checks "
        "genuinely could not run, record that as a concern instead",
        {"verified": 0},
    )]


#: What the build declared this pipeline must produce, written beside the command
#: bodies by the same build that assembled them. The doctor reads the JSON rather
#: than importing `manifest.py`, which is a build-time script and is not packaged.
MANIFEST_PATH = Path(__file__).resolve().parent.parent / "commands" / ".manifest.json"


def _declared_artifacts(manifest_path=None) -> dict | None:
    """`{command: [entry, ...]}` from the built manifest, or None when there is none.

    Absent, unreadable, or not the shape the build writes all read the same way —
    there is nothing to hold a run to, which is a skip, never a finding.
    """
    try:
        data = json.loads(Path(manifest_path or MANIFEST_PATH).read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return None
    commands = data.get("commands") if isinstance(data, dict) else None
    return commands if isinstance(commands, dict) else None


def _closed_steps(ctx: dict) -> set:
    """Every step this run recorded as finished, legacy entry shape included."""
    return {e.get("step") for e in log_entries(ctx)
            if isinstance(e.get("step"), str) and _is_step_level(e)
            and _entry_kind(e) == "complete"}


def check_artifact(feature_dir: Path, ctx: dict, manifest_path=None) -> tuple:
    """Did each closed step leave behind the file it declared it would write?

    Every author node declares its output in `writes:`, and the build collects
    those into a manifest of what a run of this pipeline must produce. Nothing
    compared that declaration against the disk, so a step that quietly stopped
    writing its document closed exactly like one that wrote it.

    Reported, never blocking, and deliberately narrow. Only `writes:` is judged —
    a `may-write:` artifact is one the size budget is allowed to fold away, and
    calling a `simple` run incomplete for obeying its budget is the manifest
    crying wolf. A step that produced none of what it declared is read as a run of
    some other pipeline (stock spec-kit, or a run older than the node that writes
    the file) and reported as no record rather than as a fault.

    A warning, not a problem: the manifest describes the pipeline as it is built
    today, and a spec on disk may have been produced by an earlier one. That is
    worth saying and is not worth failing a gate over.
    """
    skip = _no_record("artifact", feature_dir, ctx)
    if skip is not None:
        return skip, []

    declared = _declared_artifacts(manifest_path)
    if declared is None:
        return CheckStatus(
            "artifact", "skipped",
            "no readable artifact manifest — this install's build declared nothing to check against",
        ), []

    judged, findings = 0, []
    for step in sorted(_closed_steps(ctx)):
        entries = declared.get(step)
        if not isinstance(entries, list):
            continue
        names = [str(e.get("artifact") or "").strip() for e in entries
                 if isinstance(e, dict) and not e.get("conditional")]
        nodes = {str(e.get("artifact") or "").strip(): e.get("node") for e in entries
                 if isinstance(e, dict)}
        names = [n for n in names if n]
        if not names:
            continue
        missing = [n for n in names if not (Path(feature_dir) / n).exists()]
        if len(missing) == len(names):
            # None of it landed. That is a step that ran some other pipeline, not
            # one that dropped an artifact — the manifest has no claim on it.
            continue
        judged += 1
        for name in missing:
            findings.append(Finding(
                "artifact", "warning",
                f"`{step}` closed without {name}",
                f"the step declares it writes {name} (node `{nodes.get(name) or 'unknown'}`) "
                f"and the file is not there — either the node did not run, or its `writes:` "
                f"names something it never writes",
                {"step": step, "artifact": name, "node": nodes.get(name)},
            ))

    if not judged:
        return CheckStatus(
            "artifact", "skipped",
            "no closed step produced anything this pipeline declares — nothing to hold this run to",
        ), []
    return CheckStatus("artifact", "ran"), findings


#: The generated task-list shape: one phase per user story, waves inside each
#: phase, join lines between waves, a checkpoint at the end. Implement executes
#: that list — it never restructures it, so a later rewrite is a defect.
_STORY_PHASE = re.compile(r"^##\s+Phase\s+\d+:\s*User Story\s+\d+", re.MULTILINE)
_ANY_PHASE = re.compile(r"^##\s+Phase\s+\d+:", re.MULTILINE)
_TOP_LEVEL_WAVE = re.compile(r"^##\s+Wave\s+\d+", re.MULTILINE)
_WAVE_HEAD = re.compile(r"^\*\*Wave\s+\d+", re.MULTILINE)
_JOIN_LINE = re.compile(r"⟶\s*Wait")
_CHECKPOINT = re.compile(r"^\*\*Checkpoint\*\*", re.MULTILINE)


def check_template(feature_dir: Path) -> tuple:
    """Did the task list keep the shape it was generated with?"""
    tasks_md = Path(feature_dir) / "tasks.md"
    if not tasks_md.is_file():
        return CheckStatus("template", "not-applicable"), []
    try:
        text = tasks_md.read_text(encoding="utf-8")
    except OSError as exc:
        return CheckStatus("template", "skipped", f"tasks.md unreadable — {exc}"), []

    findings = []
    story_phases = _STORY_PHASE.findall(text)
    top_waves = [m.group(0).strip() for m in _TOP_LEVEL_WAVE.finditer(text)]

    if top_waves and not story_phases:
        findings.append(Finding(
            "template", "problem",
            "User-story phases were replaced by top-level wave headings",
            "offending headings: " + ", ".join(sorted(set(top_waves))[:6])
            + " — waves belong inside a story phase, not in place of one",
            {"headings": sorted(set(top_waves))},
        ))
    elif top_waves:
        findings.append(Finding(
            "template", "problem",
            "Top-level wave headings were added alongside the story phases",
            "offending headings: " + ", ".join(sorted(set(top_waves))[:6]),
            {"headings": sorted(set(top_waves))},
        ))
    elif not story_phases and _ANY_PHASE.search(text):
        findings.append(Finding(
            "template", "warning",
            "No user-story phase headings remain",
            "the file has phases but none names a user story, so task-to-story traceability "
            "is gone",
            {},
        ))

    if _WAVE_HEAD.search(text) and not _JOIN_LINE.search(text):
        findings.append(Finding(
            "template", "problem",
            "Wave join lines were removed",
            "the file declares waves but carries no `⟶ Wait` line, so the dependency "
            "boundaries between them are gone",
            {},
        ))

    if story_phases and not _CHECKPOINT.search(text):
        findings.append(Finding(
            "template", "warning",
            "Story checkpoints were removed",
            "each user-story phase should end with a Checkpoint line stating the story is "
            "independently functional",
            {},
        ))

    return CheckStatus("template", "ran"), findings


def _lost_entries(feature_dir: Path) -> list:
    """Calls that did work the trace could not record.

    The trace is the evidence a capture happened. When appending to it fails but
    the capture itself succeeded, the run holds a write nothing recorded — and a
    reader presenting its short count as a total is exactly the false clean this
    check exists to prevent. `run_trace` leaves a marker; read it.
    """
    try:
        import run_trace as rt
    except ImportError:
        return []
    own = Path(feature_dir) / rt.LOST_NAME
    name = Path(feature_dir).name
    out = []
    for path in (own, Path(feature_dir).parent / rt.LOST_NAME):
        try:
            if not path.is_file():
                continue
            lines = [ln.strip() for ln in path.read_text(encoding="utf-8").splitlines()
                     if ln.strip()]
        except OSError:
            continue
        if path == own:
            out += lines
            continue
        # The shared file holds every spec's fallback writes. Only the lines this
        # spec put there are evidence about this spec; an untagged line predates
        # the tag and belongs to nobody, which is not the same as belonging to
        # everybody. Claiming those here is how one spec's failure would be
        # reported as a problem on every spec that never traced anything.
        out += [ln for ln in lines if f" spec={name} " in f"{ln} "]
    return out


def _lost_finding(lost: list) -> Finding:
    return Finding(
        "trace", "problem",
        f"{plural(len(lost), 'call')} succeeded but could not be recorded in the trace",
        "the run performed work the trace does not contain, so every count below is a "
        "lower bound — most often an unwritable spec directory: " + lost[0],
        {"lost": lost[:5]},
    )


#: A write that did not land because a guard correctly refused it. The commonest
#: is a late hook trying to re-close a spec that mark-complete already finished —
#: the system defending itself, not failing. Reporting those as problems put a
#: false alarm in every single run, which is how a report stops being read.
_EXPECTED_DECLINE = re.compile(
    r"not regressing|already at currentStep|already (complete|completed)|"
    r"left untouched|nothing to (do|sync)",
    re.I,
)


def _is_expected_decline(event: dict) -> bool:
    return bool(_EXPECTED_DECLINE.search(str(event.get("reason") or "")))


def check_trace(feature_dir: Path, ctx: dict | None = None) -> tuple:
    """What the self-trace recorded: failures with reasons, volumes, churn.

    Also reports calls that could not resolve any spec at all. Those land in the
    repo-level unattributed log, and they are the single most common capture
    failure — so a spec whose own trace is clean is not evidence that nothing
    broke while it was being built.
    """
    import run_trace

    read = run_trace.read(feature_dir)
    unattributed = _unattributed_failures(feature_dir, ctx or {})
    # The marker is read before the early return on purpose. Its whole reason to
    # exist is a run that could not write into the spec directory — which is the
    # same run that has no trace file to read, so deciding "nothing captured yet"
    # first is what made this evidence permanently unreachable.
    lost = _lost_entries(feature_dir)

    if read is None:
        if lost or unattributed:
            findings = [_lost_finding(lost)] if lost else []
            if unattributed:
                findings.append(_unattributed_finding(unattributed))
            return CheckStatus("trace", "ran"), findings
        return CheckStatus(
            "trace", "skipped",
            f"no {run_trace.TRACE_NAME} — this spec ran before run tracing, or nothing has "
            f"been captured since",
        ), []

    findings = []
    all_failures = read.failures()
    declined = [e for e in all_failures if _is_expected_decline(e)]
    failures = [e for e in all_failures if not _is_expected_decline(e)]
    if declined:
        findings.append(Finding(
            "trace", "note",
            f"{plural(len(declined), 'write')} correctly refused by a guard",
            "a guard declined these rather than letting them land — most often a late hook "
            "trying to re-close a spec that was already finished. Recorded so the count is "
            "complete, not because anything went wrong: " + str(declined[0].get("reason"))[:160],
            {"count": len(declined)},
        ))
    if failures:
        by_reason: dict = {}
        for e in failures:
            by_reason.setdefault(e.get("reason") or "no reason recorded", []).append(e)
        for reason, group in sorted(by_reason.items(), key=lambda kv: -len(kv[1])):
            findings.append(Finding(
                "trace", "problem",
                f"{plural(len(group), 'capture call')} failed ({group[0].get('op')})",
                reason,
                {"op": group[0].get("op"), "count": len(group), "reason": reason},
            ))

    churn = {f: n for f, n in read.rewrites().items() if n > 1}
    if churn:
        worst = sorted(churn.items(), key=lambda kv: -kv[1])
        findings.append(Finding(
            "trace", "note",
            "File rewrite counts",
            ", ".join(f"{f} x{n}" for f, n in worst[:6]),
            {"rewrites": churn},
        ))

    qualifier = "" if read.exact else " (at least — earlier entries rolled off)"
    if lost:
        findings.append(_lost_finding(lost))

    findings.append(Finding(
        "trace", "note",
        f"{plural(len(read.events), 'capture call')} recorded{qualifier}",
        f"{read.bytes_written()} bytes written, {read.bytes_read()} bytes of input carried"
        + (" — and at least one call could not be recorded at all, so these are "
           "lower bounds rather than totals" if lost else "")
        + (f"; {read.unparseable} unreadable line(s) skipped" if read.unparseable else ""),
        {"calls": len(read.events), "failures": len(failures),
         "bytes_written": read.bytes_written(), "bytes_read": read.bytes_read(),
         "unparseable": read.unparseable, "truncated": read.truncated},
    ))

    if unattributed:
        findings.append(_unattributed_finding(unattributed))

    return CheckStatus("trace", "ran"), findings


def _unattributed_failures(feature_dir: Path, ctx: dict) -> list:
    """Failed calls that resolved to no spec, inside this spec's run window."""
    import run_trace

    log = Path(feature_dir).parent / run_trace.TRACE_NAME
    if not log.is_file():
        return []
    read = run_trace.read(log.parent)
    if read is None:
        return []
    start, end = run_window(ctx, WINDOW_PAD)
    out, dropped = [], 0
    for e in read.events:
        if e.get("ok"):
            continue
        if not _in_window(parse_time(e.get("at")), start, end):
            dropped += 1
            continue
        out.append(e)
    if dropped:
        # Say what was set aside. Silently discarding a failure because of when it
        # happened is how "0 problems" got reported for a run that lost writes.
        out.append({"op": "outside-window", "ok": False, "spec": None,
                    "reason": f"{plural(dropped, 'further failed capture call')} in the "
                              f"repo-level log fell outside this run's window and were "
                              f"not attributed to it"})
    return out


def _unattributed_finding(events: list) -> Finding:
    reasons = sorted({e.get("reason") or "no reason recorded" for e in events})
    return Finding(
        "trace", "problem",
        f"{plural(len(events), 'capture call')} could not resolve a spec and wrote nothing",
        reasons[0] + (f" (+{len(reasons) - 1} other reason(s))" if len(reasons) > 1 else ""),
        {"count": len(events), "reasons": reasons},
    )
