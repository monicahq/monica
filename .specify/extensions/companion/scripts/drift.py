#!/usr/bin/env python3
"""Detect code that drifted from its Companion living spec.

For each configured capability, report the source files that changed SINCE the
capability's living spec (`capabilities/<name>/<name>.spec.md`) was last committed, and
classify each:

  - `tracked`   — the file appears in a `specs/*/.spec-context.json` recorded
                  SINCE the capability spec's last commit (a change that went
                  THROUGH the pipeline but was never folded back → a missed
                  recent sync). Scoped to context files changed in
                  `commit..HEAD`; degrades to `unspeced` if git can't scope it.
  - `unspeced`  — the file changed entirely outside the pipeline (the living
                  spec never saw it at all). More concerning than `tracked`.

The `✓ … in sync` claim is earned only by capabilities that were CHECKED — a run
that skipped every capability reports `0 checked, N skipped` instead, a partly
skipped run states both counts, and callers read `checked` off the result to tell
"clean" from "did not run". A clone without the history to reach a capability's
baseline is skipped rather than compared against the graft boundary.

With `--working` (opt-in), each capability's changed set additionally covers the
working tree: the diff runs baseline→worktree (uncommitted edits and deletions
included) and untracked files are added. The default mode is unchanged, and the
never-halts / counts contract holds in both modes. `/speckit.companion.living-sync`
consumes this mode's `--json` output as its sync plan.

Deterministic: pure git + the LS·1 resolver (membership) + an exempt-glob filter.
It NEVER halts — always exits 0, including when everything was skipped (a skip
without a committed baseline is correct, not a failure). A surrounding workflow /
CI may treat findings as a gate; the command itself does not. With living specs
disabled (or no config), it reports nothing and exits 0 (the LS·1 inert/opt-in
contract).

All git operations are anchored to `--root` (via `git -C <root>`), never cwd, so
pointing it at a sandbox or sibling repo records THAT repo's history.

Usage:
  drift.py [--root <dir>] [--json] [--working]
"""
from __future__ import annotations

import argparse
import importlib.util
import json
import os
import re
import subprocess
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))


def _load_resolver():
    """Import the hyphenated resolver module by path (mirrors how the resolver
    imports companion_config)."""
    path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "resolve-spec-paths.py")
    spec = importlib.util.spec_from_file_location("resolve_spec_paths", path)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


rsp = _load_resolver()


def _git(root: str, args: list[str]) -> tuple[int, str]:
    try:
        out = subprocess.run(
            ["git", "-C", root, *args],
            capture_output=True, text=True, check=False,
        )
        return out.returncode, out.stdout
    except (OSError, FileNotFoundError):
        return 1, ""


def _is_git_repo(root: str) -> bool:
    """True when <root> is inside a git work tree and git is runnable."""
    code, out = _git(root, ["rev-parse", "--is-inside-work-tree"])
    return code == 0 and out.strip() == "true"


SKIP_UNCOMMITTED = "spec.md not yet committed"
SKIP_UNREADABLE = "spec history unreadable"
SKIP_SHALLOW = "spec history unreachable (shallow clone)"
HINT_SHALLOW = ("👉 Fetch the full history to check these "
                "(e.g. actions/checkout with fetch-depth: 0).")


def _shallow_boundaries(root: str) -> frozenset[str] | None:
    """The commits git grafted at the edge of a shallow clone, or None if unknowable.

    A boundary commit has no parent in the local object store, so git reports it
    as touching every path — `git log -n1 -- <spec>` resolves to it for specs it
    never modified, which would produce a baseline that does not exist.

    The shallow file IS git's definition of shallowness — `--is-shallow-repository`
    just reports whether it holds anything — so this reads it as the only source.
    """
    code, out = _git(root, ["rev-parse", "--git-path", "shallow"])
    if code != 0 or not out.strip():
        return None
    path = out.strip()
    if not os.path.isabs(path):
        path = os.path.join(root, path)
    try:
        with open(path, encoding="utf-8") as fh:
            return frozenset(ln.strip() for ln in fh if ln.strip())
    except FileNotFoundError:
        # No shallow file is git's own answer for "not a shallow clone".
        return frozenset()
    except OSError:
        # Present but unreadable: we cannot tell which baselines are grafted.
        return None


def _has_commits(root: str) -> bool:
    """False on an unborn HEAD, where `git log` fails for every path alike."""
    code, _ = _git(root, ["rev-parse", "--verify", "--quiet", "HEAD"])
    return code == 0


def _spec_commit(root: str, spec: str) -> tuple[str, str | None]:
    """`("ok", sha)` for the last commit that modified <spec>, else
    `("uncommitted", None)` or `("unreadable", None)`."""
    code, out = _git(root, ["log", "-n", "1", "--format=%H", "--", spec])
    if code != 0:
        return "unreadable", None
    sha = out.strip()
    return ("ok", sha) if sha else ("uncommitted", None)


def _changed_since(root: str, commit: str, pathspec: str | None = None,
                   working: bool = False) -> list[str]:
    """Files changed after <commit>. Default: committed history only
    (`commit..HEAD`). Working mode diffs baseline→worktree (`git diff <commit>`),
    which folds in commits since, staged, unstaged, and deletions in one pass."""
    args = ["diff", "--name-only", commit if working else f"{commit}..HEAD"]
    if pathspec is not None:
        args += ["--", pathspec]
    code, out = _git(root, args)
    if code != 0:
        return []
    return [ln.strip() for ln in out.splitlines() if ln.strip()]


def _untracked(root: str, pathspec: str | None = None) -> list[str]:
    """Untracked files (gitignore respected) — invisible to `git diff`."""
    args = ["ls-files", "--others", "--exclude-standard"]
    if pathspec is not None:
        args += ["--", pathspec]
    code, out = _git(root, args)
    if code != 0:
        return []
    return [ln.strip() for ln in out.splitlines() if ln.strip()]


def _read_context_files(path: str) -> set[str]:
    """Files recorded in one `.spec-context.json` (canonical + legacy schema)."""
    out: set[str] = set()
    try:
        with open(path, encoding="utf-8") as fh:
            data = json.load(fh)
    except (OSError, ValueError):
        return out
    if not isinstance(data, dict):
        return out
    for f in data.get("files_modified") or []:
        if isinstance(f, str):
            out.add(rsp._posix(f))
    for entry in data.get("history") or []:
        if isinstance(entry, dict):
            for f in entry.get("files") or []:
                if isinstance(f, str):
                    out.add(rsp._posix(f))
    return out


def _tracked_files_since(root: str, commit: str, working: bool = False) -> set[str]:
    """Files recorded in a `specs/*/.spec-context.json` that itself changed in
    `commit..HEAD` — i.e. a pipeline sync recorded SINCE the capability spec's
    last commit. Working mode widens the scan the same way as the drifted set,
    so an uncommitted pipeline record still earns its files the `tracked` label.

    Scoping to context files changed in `commit..HEAD` is the git-based read
    consistent with the rest of drift. It degrades conservatively: if git can't
    scope it (no diff output / non-repo), the set is empty, so an ambiguous file
    is labeled `unspeced` rather than a false `tracked`.
    """
    out: set[str] = set()
    rels = _changed_since(root, commit, "specs/", working=working)
    if working:
        rels += _untracked(root, "specs/")
    for rel in rels:
        rel = rsp._posix(rel)
        if not rel.endswith("/.spec-context.json"):
            continue
        out |= _read_context_files(os.path.join(root, rel))
    return out


def _vouched_files_since(root: str, commit: str, cap_name: str, working: bool = False) -> set[str]:
    """Files changed by a run that accounted for `cap_name` — folded into it, or
    recorded an explicit skip for it — since the spec's last commit. Either is
    the run saying the spec still describes the code, so those files are not
    drift. A file changed by hand, with no run behind it, still is."""
    out: set[str] = set()
    rels = _changed_since(root, commit, "specs/", working=working)
    if working:
        rels += _untracked(root, "specs/")
    for rel in rels:
        rel = rsp._posix(rel)
        if not rel.endswith("/.spec-context.json"):
            continue
        try:
            with open(os.path.join(root, rel), encoding="utf-8") as fh:
                data = json.load(fh)
        except (OSError, ValueError):
            continue
        living = data.get("livingSpecs") if isinstance(data, dict) else None
        if not isinstance(living, dict):
            continue
        names = {n for n in (living.get("synced") or []) if isinstance(n, str)}
        for s_ in living.get("skipped") or []:
            n = s_ if isinstance(s_, str) else (s_.get("name") if isinstance(s_, dict) else None)
            if isinstance(n, str):
                names.add(n)
        if cap_name in names:
            out |= _read_context_files(os.path.join(root, rel))
    return out


def _is_any_spec_doc(fp: str, spec_dirs: set) -> bool:
    """True for any registered capability's living-spec documents, not only this one's.

    Colocated capabilities sit beside the code they describe, so one capability's `match`
    routinely claims the directory its *siblings* keep their specs in. Without this, editing
    a neighbour's spec is reported as drifted code here — which is how a directory holding
    six colocated capabilities flags all six every time any one of them is written.
    """
    if not any(fp.endswith(t) for t in rsp.RESERVED_TIERS) and not fp.endswith(".spec.md"):
        return False
    file_dir = fp.rsplit("/", 1)[0] if "/" in fp else ""
    return file_dir in spec_dirs


def _is_own_spec_doc(fp: str, spec_posix: str) -> bool:
    """True for the capability's own living-spec documents — the spec itself or a
    reserved-tier sibling (`.arch.md` / `.coverage.md`) in the spec's directory.

    A colocated capability's `match` globs claim its own area, so without this a
    `src/billing/billing.arch.md` edit would be reported as drifted *code*. The
    spec documents ARE the spec, not drift — mirror the resolver's tier hygiene.
    """
    if fp == spec_posix:
        return True
    spec_dir = spec_posix.rsplit("/", 1)[0] if "/" in spec_posix else ""
    file_dir = fp.rsplit("/", 1)[0] if "/" in fp else ""
    return file_dir == spec_dir and any(fp.endswith(t) for t in rsp.RESERVED_TIERS)


def _exempt(file: str, exempt_globs: list[str]) -> bool:
    """A file is exempt if any glob matches its full path OR its basename.

    The basename pass lets a segment-scoped pattern like `*.test.*` exempt
    `src/a/b.test.ts` — `*` never crosses `/`, so without it the pattern would
    only match top-level files. A `**/...` glob still matches via the path pass.
    """
    base = file.rsplit("/", 1)[-1]
    return any(
        rsp._glob_matches(g, file) or rsp._glob_matches(g, base)
        for g in exempt_globs
    )


def compute_drift(root: str, living: dict, working: bool = False,
                  since: str | None = None) -> dict:
    """The drift result object. Inert (empty) when living specs are disabled.
    `working` widens each changed set to the working tree (uncommitted +
    untracked); the default path issues exactly the same git commands as before.

    Every evaluation records its inputs and its verdict to the run self-trace, so
    a "drift warning on every open" can be compared against what the computation
    actually said last time instead of relying on memory."""
    import time as _time

    _started = _time.monotonic()
    try:
        result = _compute_drift(root, living, working, since)
    except Exception as exc:  # noqa: BLE001
        _trace_drift(root, living, working, None, exc,
                     int((_time.monotonic() - _started) * 1000))
        raise
    _trace_drift(root, living, working, result, None,
                 int((_time.monotonic() - _started) * 1000))
    return result


def _trace_drift(root: str, living: dict, working: bool, result, exc, ms: int) -> None:
    """One trace line per drift evaluation. Never raises."""
    try:
        import sys as _sys
        from pathlib import Path as _Path

        _sys.path.insert(0, str(_Path(__file__).resolve().parent))
        import run_trace

        if result is None:
            reason = f"{type(exc).__name__}: {exc}".strip()
            run_trace.record("drift", "drift-compute", False, ms=ms,
                             feature_dir=None, reason=reason,
                             read=len(living.get("capabilities") or []))
            return
        drifted = [c["name"] for c in result.get("capabilities", []) if not c.get("inSync")]
        run_trace.record(
            "drift", "drift-compute", True, ms=ms, feature_dir=None,
            spec=None,
            files=sorted({d["file"] for c in result.get("capabilities", [])
                          for d in (c.get("drifted") or [])}),
            read=len(living.get("capabilities") or []),
        )
        _record_drift_verdict(root, {
            "working": working,
            "checked": result.get("checked", 0),
            "drifted": drifted,
            "skipped": [s.get("name") for s in result.get("skipped", [])],
        })
    except Exception:  # noqa: BLE001 — tracing never breaks the evaluation
        pass


def _record_drift_verdict(root: str, verdict: dict) -> None:
    """Append the verdict itself, so two evaluations can be diffed later."""
    try:
        import sys as _sys
        from pathlib import Path as _Path

        _sys.path.insert(0, str(_Path(__file__).resolve().parent))
        import run_trace
        from spec_context import _now_iso

        target = _Path(root) / run_trace.UNATTRIBUTED_DIR
        if not target.is_dir():
            return
        run_trace._ensure_ignored(target)
        line = json.dumps({"at": _now_iso(), "tool": "drift", "verdict": verdict},
                          ensure_ascii=False, separators=(",", ":")) + "\n"
        with (target / run_trace.TRACE_NAME).open("a", encoding="utf-8") as fh:
            fh.write(line)
    except Exception:  # noqa: BLE001
        pass


def _marker_globs(root: str, living: dict) -> dict[str, list[str]]:
    """Every `touches` glob in each capability's spec, by capability name."""
    out: dict[str, list[str]] = {}
    for cap in living.get("capabilities") or []:
        spec = cap.get("spec")
        if not spec:
            continue
        try:
            with open(os.path.join(root, spec), encoding="utf-8") as fh:
                slices = rsp.requirement_slices(fh.read())
        except (OSError, UnicodeDecodeError):
            continue
        out[cap["name"]] = [g for s in slices for g in (s.get("touches") or [])]
    return out


def _named_elsewhere(fp: str, cap_name: str, markers: dict[str, list[str]]) -> bool:
    """A requirement in another capability names this file, and none here does.

    Sibling capabilities routinely share one broad glob, so a change one of them
    describes would flag every other. The requirement that names the file is the
    claim; a glob with no requirement behind it yields to it.
    """
    if any(rsp._glob_matches(g, fp) for g in markers.get(cap_name, [])):
        return False
    return any(rsp._glob_matches(g, fp)
               for name, globs in markers.items() if name != cap_name for g in globs)


def _compute_drift(root: str, living: dict, working: bool = False,
                   since: str | None = None) -> dict:
    if not living["enabled"]:
        return {"enabled": False, "working": working, "checked": 0,
                "capabilities": [], "skipped": []}

    exempt_globs = living.get("exempt") or []
    # Every directory a registered spec lives in. A colocated capability's globs claim the
    # directory its siblings keep their specs in, and a spec is not code that drifts.
    spec_dirs = set()
    for _c in living.get("capabilities") or []:
        _s = _c.get("spec")
        if _s:
            _sp = rsp._posix(_s)
            spec_dirs.add(_sp.rsplit("/", 1)[0] if "/" in _sp else "")
    git_ok = _is_git_repo(root)
    boundaries = _shallow_boundaries(root) if git_ok else frozenset()
    graft_state_unknown = boundaries is None
    has_commits = _has_commits(root) if git_ok else False
    untracked = _untracked(root) if (working and git_ok) else []
    caps_out: list[dict] = []
    skipped: list[dict] = []
    markers = _marker_globs(root, living)

    for cap in living["capabilities"]:
        spec = cap.get("spec")
        if not spec:
            skipped.append({"name": cap["name"], "reason": "no resolvable spec path"})
            continue
        if not git_ok:
            skipped.append({"name": cap["name"],
                            "reason": "git unavailable or --root is not a git repo"})
            continue
        if graft_state_unknown:
            skipped.append({"name": cap["name"], "reason": SKIP_UNREADABLE})
            continue
        state, commit = _spec_commit(root, spec)
        if since:
            # Branch-scoped: measure from where this work started rather than from each
            # spec's own last commit. Whole-repo drift only ever gets read once it is a
            # backlog; this answers the question while the change is still in hand — which
            # capabilities did I touch, and did I fold any of them.
            merge_base = _git(root, ["merge-base", since, "HEAD"])[1].strip()
            if merge_base:
                state, commit = "ok", merge_base
        if state != "ok":
            unreadable = state == "unreadable" and has_commits
            reason = SKIP_UNREADABLE if unreadable else SKIP_UNCOMMITTED
            skipped.append({"name": cap["name"], "reason": reason})
            continue
        if commit in boundaries:
            skipped.append({"name": cap["name"], "reason": SKIP_SHALLOW})
            continue

        spec_posix = rsp._posix(spec)
        tracked = _tracked_files_since(root, commit, working=working)
        vouched = _vouched_files_since(root, commit, cap["name"], working=working)
        changed = _changed_since(root, commit, working=working)
        if working:
            changed += untracked
        drifted = []
        folded_here = False
        seen: set[str] = set()
        for f in changed:
            fp = rsp._posix(f)
            if fp in seen:
                continue
            seen.add(fp)
            if _is_own_spec_doc(fp, spec_posix) or _is_any_spec_doc(fp, spec_dirs):
                folded_here = folded_here or _is_own_spec_doc(fp, spec_posix)
                continue
            if not rsp.matches(cap, fp):
                continue
            if _exempt(fp, exempt_globs):
                continue
            if fp in vouched:
                continue
            if _named_elsewhere(fp, cap["name"], markers):
                continue
            severity = "tracked" if fp in tracked else "unspeced"
            drifted.append({"file": fp, "severity": severity})
        drifted.sort(key=lambda d: (d["severity"], d["file"]))
        if since and folded_here:
            # The spec was written on this branch, so its code changing is the fold, not drift.
            drifted = []
        caps_out.append({
            "name": cap["name"],
            "spec": spec_posix,
            "commit": commit[:8],
            "drifted": drifted,
            "inSync": len(drifted) == 0,
        })

    caps_out.sort(key=lambda c: c["name"])
    return {"enabled": True, "working": working, "checked": len(caps_out),
            "capabilities": caps_out, "skipped": skipped}


def _counts_line(result: dict) -> str:
    """`0 checked, 9 skipped (spec.md not yet committed)` — the parenthetical
    appears only when every skip shares one reason."""
    skipped = result["skipped"]
    line = f"{result['checked']} checked, {len(skipped)} skipped"
    reasons = {sk["reason"] for sk in skipped}
    if len(reasons) == 1:
        line += f" ({reasons.pop()})"
    return line


def _partial_clean_line(result: dict) -> str:
    """`✓ 2 of 9 capabilities in sync; 7 not checked — <reason>` — the clean-run
    summary when some capabilities were skipped, so the ✓ can't read as a verdict
    on the whole configuration."""
    checked = result["checked"]
    skipped = result["skipped"]
    line = (f"✓ {checked} of {checked + len(skipped)} capabilities in sync; "
            f"{len(skipped)} not checked")
    reasons = {sk["reason"] for sk in skipped}
    if len(reasons) == 1:
        line += f" — {reasons.pop()}"
    return line


def _shallow_hint(result: dict) -> list[str]:
    return ([HINT_SHALLOW]
            if any(sk["reason"] == SKIP_SHALLOW for sk in result["skipped"])
            else [])


def render_human(result: dict) -> str:
    if not result["enabled"]:
        return ""  # opt-in: disabled feature reports nothing in human mode

    lines: list[str] = []
    for sk in result["skipped"]:
        lines.append(f"ℹ {sk['name']}: {sk['reason']}; skipping drift check")

    checked = result["checked"]
    if checked == 0:
        if not result["skipped"]:
            return "No capabilities configured."
        lines.append(_counts_line(result))
        lines.extend(_shallow_hint(result))
        return "\n".join(lines)

    if not any(c["drifted"] for c in result["capabilities"]):
        if result["skipped"]:
            lines.append(_partial_clean_line(result))
            lines.extend(_shallow_hint(result))
        else:
            noun = "capability" if checked == 1 else "capabilities"
            lines.append(f"✓ All {checked} checked {noun} in sync.")
        return "\n".join(lines)

    lines.append("🔍 Spec drift report (working tree included)"
                 if result.get("working") else "🔍 Spec drift report")
    for cap in result["capabilities"]:
        if not cap["drifted"]:
            lines.append(f"✓ {cap['name']} — in sync")
            continue
        n = len(cap["drifted"])
        lines.append("")
        lines.append(f"📁 {cap['spec']}  (last committed {cap['commit']})")
        lines.append(f"   {n} file{'s' if n != 1 else ''} changed since spec was last committed:")
        for d in cap["drifted"]:
            note = ("changed via pipeline, never folded back"
                    if d["severity"] == "tracked"
                    else "changed outside the pipeline")
            lines.append(f"   {d['severity']:<8} {d['file']}  — {note}")
    lines.append("")
    lines.append(_counts_line(result))
    lines.extend(_shallow_hint(result))
    lines.append("👉 Fold these into the living spec (e.g. /speckit.companion.living-adopt) "
                 "or add the path to livingSpecs.exempt.")
    return "\n".join(lines)


REVIEWED_RE = re.compile(r"^<!--\s*reviewed:\s*([0-9a-f]{7,40})\s*-->\s*$", re.M)


def accept(root: str, living: dict, names: list) -> int:
    """Record that a spec was read against the code and found still true.

    The baseline is the spec's last commit, so a spec that is *correct* drifts further every
    week and there is no way to say so. Left alone, every capability ends up flagged and the
    flag stops meaning anything — which is the state that made this worth adding.

    The record is a line in the spec rather than a field somewhere else, because committing
    it is what moves the baseline: a note kept anywhere else would need its own bookkeeping
    to stay true. Reviewing is a claim a person makes, so nothing here writes it on its own.
    """
    from pathlib import Path as _Path

    caps = {c.get("name"): c for c in (living or {}).get("capabilities") or []}
    head = _git(root, ["rev-parse", "--short", "HEAD"])[1].strip() or "unknown"
    touched = 0
    for name in names:
        cap = caps.get(name)
        if not cap or not cap.get("spec"):
            print(f"[companion] No capability named {name!r} in the registry.", file=sys.stderr)
            continue
        path = _Path(root) / cap["spec"]
        try:
            text = path.read_text(encoding="utf-8")
        except OSError as err:
            print(f"[companion] Could not read {cap['spec']}: {err}", file=sys.stderr)
            continue
        line = f"<!-- reviewed: {head} -->"
        if REVIEWED_RE.search(text):
            text = REVIEWED_RE.sub(line, text, count=1)
        else:
            lines = text.splitlines()
            at = 1 if lines and lines[0].startswith("# ") else 0
            lines.insert(at, "" if at else line)
            if at:
                lines.insert(at + 1, line)
            text = "\n".join(lines) + ("\n" if text.endswith("\n") else "")
        path.write_text(text, encoding="utf-8")
        print(f"[companion] {name}: reviewed at {head}. Commit the spec to move its baseline.")
        touched += 1
    return 0 if touched else 1


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="Report Companion living-spec drift.")
    ap.add_argument("--root", default=".", help="repo root (default: cwd)")
    ap.add_argument("--json", action="store_true",
                    help="emit the machine-readable JSON object")
    ap.add_argument("--working", action="store_true",
                    help="also count working-tree changes (uncommitted edits, "
                         "deletions, and untracked files) as drift")
    ap.add_argument("--since", metavar="REF",
                    help="measure from the merge base with REF instead of each spec's own "
                         "last commit, so the report is about this branch's work alone")
    ap.add_argument("--accept", metavar="CAPABILITY", action="append", default=[],
                    help="record that this capability's spec was read against the code and "
                         "found still true; repeatable. Commit the spec to move its baseline")
    args = ap.parse_args(argv)

    living = rsp.load_living(args.root)
    if args.accept:
        return accept(args.root, living, args.accept)
    result = compute_drift(args.root, living, working=args.working, since=args.since)
    if args.json:
        print(json.dumps(result, indent=2))
    else:
        text = render_human(result)
        if text:  # no blank line when disabled / nothing to say
            print(text)
    return 0  # never halts


if __name__ == "__main__":
    raise SystemExit(main())
