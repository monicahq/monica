"""Loader + merge contract for `.specify/companion.yml` (the node-hook / recipe config).

The orchestrator is PROSE — at run time the AI reads `companion.yml` and acts on
it. This module is the executable spec of that contract: it parses the same file,
merges hooks in declared order, resolves a recipe's node-list override, validates
`reads:` against the active node set, and applies the failure table. CI unit-tests
it so the prose and the code never drift.

Failure table (mirrors mark-complete's "never fail the host command" tone):
  - absent companion.yml      -> shipped defaults, no warning
  - malformed companion.yml   -> shipped defaults + a warning
  - hook anchor not in recipe -> warn + skip that anchor's hooks
  - type: node, ref: missing  -> error

Stdlib only — includes a minimal YAML reader for the constrained config subset
(block maps, block seqs, inline flow maps/seqs, quoted/bare scalars). Anything
outside that subset raises, which the loader surfaces as "malformed".
"""
from __future__ import annotations

import os
import re
import stat
import tempfile

HOOK_TYPES = {"command", "prompt", "node", "skill"}
WHENS = ("before", "after")

#: A block-scalar header: `|` or `>` with an optional chomping marker and indent digit.
_BLOCK_SCALAR = re.compile(r"[|>][-+0-9]*")
#: A quoted span, removed before scanning so prose inside a string is never read as YAML.
_QUOTED_SPAN = re.compile(r"\"[^\"]*\"|'[^']*'")
#: An anchor definition. Unambiguous: the marker must start a token, so a shell
#: redirect (`2>&1`) is not one, and no glob begins with `&`. The name class covers
#: real-world anchor names (`shared.spec`, `caps/auth`) but deliberately excludes
#: `&`/`>` so shell operators (`a && b`) never read as anchors.
_ANCHOR_DEF = re.compile(r"(?:^|(?<=[\s,\[{]))&([A-Za-z0-9_.+/-]+)(?=[\s,\]}]|$)")
#: A line that opens its own mapping key, so it ends a wrapped plain scalar.
_CONTINUATION_STOP = re.compile(r"[A-Za-z0-9_.\"'-]+:(\s|$)")

#: An alias reference. Lexically identical to a glob (`- *bundle`), so this is only
#: an alias when the file also defines that anchor — otherwise it is a glob, and
#: rejecting the file over it would throw away a config for no reason.
_ALIAS_REF = re.compile(r"(?:^|(?<=[\s,\[{]))\*([A-Za-z0-9_.+/-]+)(?=[\s,\]}]|$)")


class ConfigError(Exception):
    """Raised for a hard failure-table case (e.g. type: node ref missing)."""


# --------------------------------------------------------------------------- #
# Minimal YAML reader (constrained subset)
# --------------------------------------------------------------------------- #
def _split_flow(s: str) -> list:
    """Split a flow body on top-level commas, respecting quotes and nesting."""
    out, buf, depth, quote = [], [], 0, None
    for ch in s:
        if quote:
            buf.append(ch)
            if ch == quote:
                quote = None
            continue
        if ch in "\"'":
            quote = ch
            buf.append(ch)
        elif ch in "[{":
            depth += 1
            buf.append(ch)
        elif ch in "]}":
            depth -= 1
            buf.append(ch)
        elif ch == "," and depth == 0:
            out.append("".join(buf))
            buf = []
        else:
            buf.append(ch)
    if "".join(buf).strip():
        out.append("".join(buf))
    return out


def _scalar(s: str):
    s = s.strip()
    if not s:
        return None
    if s[0] in "\"'" and s[-1] == s[0]:
        return s[1:-1]
    if s.lstrip("-").isdigit():
        return int(s)
    if s in ("true", "false"):
        return s == "true"
    # YAML's null spellings. Read as the string "null" they were truthy, so a
    # `condition: null` hook looked conditional and was reported as one.
    if s in ("null", "Null", "NULL", "~"):
        return None
    return s


def _parse_flow(s: str):
    s = s.strip()
    if s.startswith("[") and s.endswith("]"):
        body = s[1:-1].strip()
        return [_parse_flow(x) for x in _split_flow(body)] if body else []
    if s.startswith("{") and s.endswith("}"):
        body = s[1:-1].strip()
        out = {}
        for piece in _split_flow(body):
            if ":" not in piece:
                raise ValueError(f"flow map entry without ':' -> {piece!r}")
            k, v = piece.split(":", 1)
            out[k.strip()] = _parse_flow(v)
        return out
    return _scalar(s)


def _indent(line: str) -> int:
    return len(line) - len(line.lstrip(" "))


def _strip_comment(line: str) -> str:
    """Drop a trailing `# …` comment. A `#` is a comment only at line start or after
    whitespace and outside quotes — so `run: "echo #x"` and `a#b` keep their hash."""
    quote = None
    for i, ch in enumerate(line):
        if quote:
            if ch == quote:
                quote = None
        elif ch in "\"'":
            quote = ch
        elif ch == "#" and (i == 0 or line[i - 1] in " \t"):
            return line[:i].rstrip()
    return line


def _unquote(text: str) -> str:
    """Drop one matched pair of surrounding quotes.

    A key often has to be quoted — a section heading with a colon or an ampersand
    in it, for instance — and the quotes are YAML syntax, not part of the name.
    Keeping them produced a key nothing could match, so a configuration addressing
    `"User Scenarios & Testing"` silently addressed nothing.
    """
    if len(text) >= 2 and text[0] == text[-1] and text[0] in "\"'":
        return text[1:-1]
    return text


def _split_key(body: str) -> tuple:
    """Split `key: value` at the colon that ends the key; a line with no such colon is all key.

    A quoted key is split at the colon *after* its closing quote, so a colon
    inside the quotes stays part of the name.
    """
    if body[:1] in "\"'":
        quote = body[0]
        close = body.find(quote, 1)
        if close != -1:
            rest = body[close + 1:]
            if rest.startswith(":") and (len(rest) == 1 or rest[1] == " "):
                return _unquote(body[:close + 1]), rest[1:].strip()
    for ci, ch in enumerate(body):
        if ch == ":" and (ci + 1 == len(body) or body[ci + 1] == " "):
            return _unquote(body[:ci].strip()), body[ci + 1:].strip()
    return _unquote(body), ""


def _anchor_names(lines: list) -> set:
    """Every anchor defined in the file, so an alias can be told from a glob."""
    out = set()
    for line in lines:
        out.update(_ANCHOR_DEF.findall(_QUOTED_SPAN.sub(" ", line)))
    return out


def _unsupported(line: str, anchors: set = frozenset()) -> str:
    """Name the YAML feature a line reaches for beyond the supported subset, else ""."""
    if line.lstrip(" ").startswith("\t"):
        return "tabs cannot be used for indentation"
    body = line.strip()
    while body.startswith("- "):
        body = body[2:].strip()
    if body.startswith("---") or body.startswith("..."):
        return "a file may hold only one document"
    key, val = _split_key(body)
    # Scan the whole line with quoted spans removed, not just the first token of
    # each half: an alias inside a flow collection (`nodes: [*shared]`) reached the
    # assembler as a node id that cannot exist — half-applying the config the
    # rest of this guard exists to reject.
    bare = _QUOTED_SPAN.sub(" ", body)
    if _ANCHOR_DEF.search(bare) or (anchors & set(_ALIAS_REF.findall(bare))):
        return "anchors and aliases are not supported"
    if _BLOCK_SCALAR.fullmatch(val):
        return "block scalars are not supported"
    return ""


def _starts_block_map(rest: str) -> bool:
    """True for a seq item that opens a block mapping (`key: val`), not a scalar.
    A colon followed by end-or-space marks the key/value split; `http://x` (colon
    then `/`) and bare scalars (`resolve-dir`) stay scalars."""
    ci = rest.find(":")
    return ci != -1 and (ci + 1 == len(rest) or rest[ci + 1] == " ")


def load_yaml(text: str):
    """Parse the constrained YAML subset into nested dict/list. Raises on the rest."""
    lines, linenos = [], []
    anchors = _anchor_names([_strip_comment(r) for r in text.split("\n")])
    for lineno, raw in enumerate(text.split("\n"), 1):
        line = _strip_comment(raw)
        if not line.strip():
            continue
        problem = _unsupported(line, anchors)
        if problem:
            raise ValueError(f"line {lineno}: {problem}")
        lines.append(line)
        linenos.append(lineno)
    pos = [0]

    def parse_block(min_indent: int):
        if pos[0] >= len(lines):
            return None
        first = lines[pos[0]]
        ind = _indent(first)
        if ind < min_indent:
            return None
        is_seq = first.lstrip().startswith("- ")
        return _parse_seq(ind) if is_seq else _parse_map(ind)

    def _parse_seq(ind: int):
        items = []
        while pos[0] < len(lines):
            line = lines[pos[0]]
            if _indent(line) != ind or not line.lstrip().startswith("- "):
                break
            rest = line.lstrip()[2:].strip()
            pos[0] += 1
            if rest.startswith("{") or rest.startswith("["):
                items.append(_parse_flow(rest))
            elif _starts_block_map(rest):
                # block-mapping item ("- key: val" + deeper-indented keys): re-anchor
                # the line at the key column and let _parse_map gather the whole entry.
                item_indent = ind + 2
                pos[0] -= 1
                lines[pos[0]] = " " * item_indent + rest
                items.append(_parse_map(item_indent))
            elif rest:
                items.append(_scalar(rest))
            else:
                items.append(parse_block(ind + 1))
        return items

    def _parse_map(ind: int):
        out = {}
        while pos[0] < len(lines):
            line = lines[pos[0]]
            if _indent(line) != ind or line.lstrip().startswith("- "):
                break
            stripped = line.strip()
            if ":" not in stripped:
                raise ValueError(f"map line without ':' -> {stripped!r}")
            # A quoted key is split after its closing quote, so a colon inside the
            # quotes stays part of the name — and the quotes come off, because they
            # are YAML syntax rather than part of the key. Keeping them produced a
            # key nothing could match, which made a configuration addressing
            # `"User Scenarios & Testing"` silently address nothing.
            key, val = _split_key(stripped)
            pos[0] += 1
            if not val:
                # A block sequence may sit at the SAME indent as its key. That is
                # ordinary YAML, and the style spec-kit's own `extensions.yml` is
                # written in — refusing it meant we could not read the file the
                # tool we extend generates.
                nxt = pos[0]
                if (nxt < len(lines) and _indent(lines[nxt]) == ind
                        and lines[nxt].lstrip().startswith("- ")):
                    out[key] = _parse_seq(ind)
                else:
                    out[key] = parse_block(ind + 1)
            elif val.startswith("{") or val.startswith("["):
                out[key] = _parse_flow(val)
            else:
                out[key] = _scalar(_join_wrapped(val, ind))
        return out

    def _join_wrapped(val: str, ind: int) -> str:
        """Absorb a plain scalar that wrapped onto deeper-indented lines.

        Emitters wrap long values — spec-kit's own `extensions.yml` carries
        descriptions folded this way — and YAML joins them with a space. Reading
        only the first line stopped the parse mid-file, so a registry we had to
        read looked malformed.
        """
        parts = [val]
        while pos[0] < len(lines):
            line = lines[pos[0]]
            if _indent(line) <= ind:
                break
            rest = line.strip()
            # A deeper line that opens its own key or list item is structure,
            # not continuation.
            if rest.startswith("- ") or _CONTINUATION_STOP.match(rest):
                break
            parts.append(rest)
            pos[0] += 1
        return " ".join(parts)

    result = parse_block(0)
    if pos[0] < len(lines):
        stopped = lines[pos[0]].lstrip()
        hint = (" (a list at the same indent as its key is not supported — indent the "
                "`- ` items under it)" if stopped.startswith("- ") else "")
        raise ValueError(
            f"line {linenos[pos[0]]}: parsing stopped before the end of the file{hint}")
    return result if result is not None else {}


# --------------------------------------------------------------------------- #
# Loader + contract
# --------------------------------------------------------------------------- #
def load_config(path: str):
    """Return (config_dict, warnings). Absent -> ({}, []). Malformed -> ({}, [warn])."""
    if not os.path.isfile(path):
        return {}, []
    try:
        with open(path, encoding="utf-8") as fh:
            cfg = load_yaml(fh.read())
        if cfg is None:
            cfg = {}
        if not isinstance(cfg, dict):
            raise ValueError("top level must be a mapping")
        return cfg, []
    except Exception as exc:  # noqa: BLE001 — any parse failure degrades to defaults
        return {}, [f"malformed companion.yml ({exc}); using shipped defaults"]


DEBUG_KEY = "debug"
#: The node-hook / recipe config, relative to a project root.
CONFIG_REL = os.path.join(".specify", "companion.yml")


def debug_enabled(config: dict) -> bool:
    """True only for a literal `debug: true` at the top level of companion.yml.

    Anything else — absent, unreadable, a string, a nested value — is off. There
    is no verbose tier: the flag decides which version of the command bodies gets
    rendered, and a half-on state would be a third shape nobody asked for.
    """
    return config.get(DEBUG_KEY) is True


def debug_from_root(root: str) -> bool:
    """Read the flag from a project root, inheriting the loader's failure table."""
    cfg, _warnings = load_config(os.path.join(root, CONFIG_REL))
    return debug_enabled(cfg)


def resolve_order(config: dict, command: str, default_order: list) -> list:
    """A recipe's `nodes: [...]` replaces the default order; else the default."""
    cmd = (config.get("commands") or {}).get(command) or {}
    nodes = cmd.get("nodes")
    return list(nodes) if isinstance(nodes, list) and nodes else list(default_order)


def node_hook_dirs(nodes_dir) -> list:
    """The directories a `type: node` ref is looked up in, in order.

    Accepts one path or several. A project's own directory is passed first by its
    caller, so a hook can name a node file the project wrote — which is what this
    format has always documented.
    """
    if not nodes_dir:
        return []
    return [nodes_dir] if isinstance(nodes_dir, str) else [d for d in nodes_dir if d]


def find_node_file(ref: str, nodes_dir):
    """The first `<dir>/<ref>.md` that exists, or None."""
    for directory in node_hook_dirs(nodes_dir):
        path = os.path.join(directory, f"{ref}.md")
        if os.path.isfile(path):
            return path
    return None


def resolve_phases(config: dict, command: str) -> list:
    """A project's own phase grouping for a command, or `[]` for the shipped one.

    Phases were the one block a project could see and not touch: the nodes were
    reorderable and replaceable, the hooks attachable, and the group they sat in
    belonged to the extension alone.
    """
    cmd = (config.get("commands") or {}).get(command) or {}
    phases = cmd.get("phases")
    if not isinstance(phases, list) or not phases:
        return []

    out = []
    for i, phase in enumerate(phases):
        if not isinstance(phase, dict):
            raise ConfigError(f"{command}: phases[{i}] is not a name and a node list")
        name = str(phase.get("name") or "").strip()
        if not name:
            raise ConfigError(f"{command}: phases[{i}] has no name")
        nodes = phase.get("nodes")
        # Absent and empty are the same mistake, and deserve the same sentence.
        if not isinstance(nodes, list) or not nodes:
            raise ConfigError(
                f"{command}: phase '{name}' has no nodes — remove the phase, "
                f"or give it one")
        out.append({"name": name, "nodes": [str(n) for n in nodes]})

    names = [p["name"] for p in out]
    duplicate = next((n for n in names if names.count(n) > 1), None)
    if duplicate:
        raise ConfigError(
            f"{command}: two phases are both called '{duplicate}' — a hook anchored "
            f"there could not say which")

    placed = [n for phase in out for n in phase["nodes"]]
    twice = next((n for n in placed if placed.count(n) > 1), None)
    if twice:
        raise ConfigError(f"{command}: node '{twice}' is in more than one phase")
    return out


def merge_hooks(config: dict, command: str, active_nodes: list, nodes_dir=None):
    """Return (ordered_hooks, warnings).

    ordered_hooks is a flat list of dicts: {when, anchor, index, hook}. Hooks at a
    given (when, anchor) keep their declared order. An anchor not in active_nodes is
    warned + skipped. A `type: node` hook with no `ref` always raises ConfigError;
    when `nodes_dir` is given, a `ref` found in none of those directories also
    raises. `nodes_dir` may be one path or several, searched in order.
    """
    warnings = []
    ordered = []
    active = set(active_nodes)
    cmd = (config.get("commands") or {}).get(command) or {}
    hooks = cmd.get("hooks") or {}
    for when in WHENS:
        anchors = hooks.get(when) or {}
        if not isinstance(anchors, dict):
            continue
        for anchor, hook_list in anchors.items():
            if anchor not in active:
                warnings.append(f"hook anchor '{anchor}' for {command}.{when} not in active recipe — skipped")
                continue
            if not isinstance(hook_list, list):
                hook_list = [hook_list]
            for i, hook in enumerate(hook_list):
                if not isinstance(hook, dict) or hook.get("type") not in HOOK_TYPES:
                    warnings.append(f"ignoring malformed hook at {command}.{when}.{anchor}[{i}]")
                    continue
                if hook["type"] == "node":
                    ref = hook.get("ref")
                    if not ref or (nodes_dir and not find_node_file(ref, nodes_dir)):
                        looked = ", ".join(node_hook_dirs(nodes_dir))
                        raise ConfigError(
                            f"hook {command}.{when}.{anchor}[{i}] type:node ref '{ref}' has no "
                            f"node file" + (f" in {looked}" if looked else "")
                        )
                # A skill hook names something the assistant resolves, not a file
                # this build can see — so the name is all there is to check. An
                # unnamed one would render an instruction to invoke nothing.
                if hook["type"] == "skill" and not str(hook.get("ref", "")).strip():
                    raise ConfigError(
                        f"hook {command}.{when}.{anchor}[{i}] type:skill has no ref — "
                        f"name the skill to invoke"
                    )
                ordered.append({"when": when, "anchor": anchor, "index": i, "hook": hook})
    return ordered, warnings


def validate_reads(active_meta: dict, stands_in: dict = None):
    """active_meta: {node_id: reads_list}. A kept node reading a dropped node is an error.

    `stands_in` maps a variant to the node it replaces. A variant occupies the
    same slot — it writes the same thing, in the same place — so a node that
    reads `draft-spec` is satisfied by `draft-spec-bugfix` running there. Without
    this, every variant of a node anything reads would be unusable: the swap is
    exactly the case where the name changes and the dependency does not.
    """
    active = set(active_meta)
    for variant in list(active):
        slot = (stands_in or {}).get(variant)
        if slot:
            active.add(slot)
    for node_id, reads in active_meta.items():
        for dep in reads or []:
            if dep not in active:
                raise ConfigError(
                    f"node '{node_id}' reads dropped node '{dep}' — recipe broke the pipeline"
                )


def atomic_write_text(path: str, text: str) -> None:
    """Write text through a temp file and a rename, leaving no debris on failure.

    Three call sites had grown their own copy of this and every one of them
    dropped the cleanup, so a failed write left a `.tmp` beside a tracked file.

    Not quite a drop-in for `open(path, "w")`: publishing by rename needs write
    and execute on the containing directory rather than write on the file, gives
    the result the current user's ownership, and breaks any hard links to it.
    """
    # Follow a symlink: these files are user-managed, and replacing the link
    # itself with a regular file would quietly detach a shared config.
    path = os.path.realpath(path) if os.path.islink(path) else path
    mode = None
    try:
        # Permission bits only — chmod's behavior on the file-type bits is
        # unspecified by POSIX even though Linux and macOS happen to mask them.
        mode = stat.S_IMODE(os.stat(path).st_mode)
    except OSError:
        pass
    parent = os.path.dirname(path)
    if parent:
        os.makedirs(parent, exist_ok=True)
    # A unique temp name per writer: a fixed `<path>.tmp` let two concurrent runs
    # against the same file truncate each other's temp and publish half of it.
    fd, tmp = tempfile.mkstemp(dir=parent or ".", prefix=os.path.basename(path) + ".")
    try:
        with os.fdopen(fd, "w", encoding="utf-8") as fh:
            fh.write(text)
            # os.replace is atomic against a killed process but not against power
            # loss: the rename can reach disk before the data blocks, leaving a
            # zero-length file. Flushing to disk first is what makes the durability
            # claim true rather than nearly true.
            fh.flush()
            os.fsync(fh.fileno())
        if mode is None:
            # New file: match what a plain open() would have produced, so a
            # restrictive umask still yields a restrictive config. Hardcoding
            # 0644 here made a fresh config world-readable under `umask 077`.
            umask = os.umask(0)
            os.umask(umask)
            mode = 0o666 & ~umask
        # A fresh temp file is 0600 regardless, so carry the intended mode across.
        os.chmod(tmp, mode)
        os.replace(tmp, path)
    except BaseException:
        # BaseException, not OSError: a Ctrl-C during the write would otherwise
        # leave the temp file beside the real one, which is the litter this
        # helper exists to prevent.
        try:
            os.unlink(tmp)
        except OSError:
            pass
        raise
