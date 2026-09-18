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
    reads `draft-spec` is satisfied by `draft-spec-delta` running there. Without
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


# --------------------------------------------------------------------------- #
# Living Specs accessor (opt-in capability registry)
# --------------------------------------------------------------------------- #
DEFAULT_CAPABILITY_ROOT = "capabilities"
#: A spec is named for what it describes, so every living spec ends in this.
SPEC_SUFFIX = ".spec.md"
# `*` does not cross a directory separator, so a bare `*.test.*` only ever matched a
# test file sitting at the repository root — which is to say, nothing. Every project
# that never overrode this list has been carrying its test files as tracked code.
DEFAULT_EXEMPT_GLOBS = ["**/*.config.*", "**/*.test.*", "**/__tests__/**",
                        "**/migrations/**"]

# At the project root, outside `.specify/`, which routine cleanup restores wholesale.
LIVING_SPECS_REL = "living-specs.yml"
LEGACY_CONFIG_REL = os.path.join(".specify", "companion.yml")

# Top-level keys the registry file owns, in emit order. `rules` is owned so a
# rewrite re-emits it: an owned region that stopped at `capabilities` would
# delete a `rules` block that happened to sit above it.
REGISTRY_KEYS = ("enabled", "exempt", "capabilities", "rules")

#: Pipeline steps that may carry authored guidance. An unrecognised key is
#: dropped rather than passed through, so a typo never reaches a step silently.
RULE_STEPS = ("spec", "plan")


def _as_list(value) -> list:
    """Coerce a scalar/None/list into a list of non-empty strings."""
    if value is None:
        return []
    if isinstance(value, list):
        return [str(v) for v in value if v not in (None, "")]
    return [str(value)] if value != "" else []


def load_rules(block, warnings: list | None = None) -> dict:
    """Normalize the registry's `rules:` block into {step: [line]} for every known step.

    Every step key is always present, empty when unset, so no reader needs a
    presence check. Anything unusable is dropped with a warning rather than
    raised — guidance that will not parse must never fail the step it guides.
    """
    warn = warnings if warnings is not None else []
    out = {step: [] for step in RULE_STEPS}
    if block is None:
        return out
    if not isinstance(block, dict):
        warn.append("rules: must be a mapping of step name to a list of lines; ignored")
        return out
    for key, value in block.items():
        step = str(key)
        if step not in RULE_STEPS:
            warn.append(f"rules.{step}: not a pipeline step that takes rules; ignored")
            continue
        if value is not None and not isinstance(value, list):
            warn.append(f"rules.{step}: must be a list of lines; ignored")
            continue
        out[step] = _as_list(value)
    return out


def load_living_specs(config: dict) -> dict:
    """Read the `livingSpecs` block of a companion.yml-shaped mapping."""
    return load_living_specs_block((config or {}).get("livingSpecs"))


def load_living_specs_block(block, rule_warnings: list | None = None) -> dict:
    """Normalize a living-specs mapping into a typed shape.

    Returns {"enabled": bool, "exempt": [glob], "capabilities": [{name, match, exclude, spec}]}.
    `enabled` defaults to False (opt-in). `exempt` is the drift exempt-glob list,
    defaulting to DEFAULT_EXEMPT_GLOBS when unset. Each capability normalizes `match`/`exclude`
    to string lists and defaults `spec` to `capabilities/<name>/<name>.spec.md`. A capability
    whose `spec` is declared but empty keeps "" so the resolver can flag the bad path.

    A mapping carrying a `livingSpecs` key is unwrapped, so the registry file accepts
    both its own flattened shape and a block pasted over from the legacy config.
    """
    block = block or {}
    if isinstance(block, dict) and isinstance(block.get("livingSpecs"), dict):
        block = block["livingSpecs"]
    if not isinstance(block, dict):
        return {
            "enabled": False,
            "layout": "central",
            "exempt": list(DEFAULT_EXEMPT_GLOBS),
            "capabilities": [],
            "rules": load_rules(None),
        }
    enabled = bool(block.get("enabled", False))
    # Where this project keeps its specs, chosen once at set-up. Adoption reads it
    # instead of asking again, which is what made a developer answer twice and get
    # a layout they had not chosen.
    layout = str(block.get("layout") or "central").strip().lower()
    if layout not in ("central", "colocated"):
        layout = "central"
    exempt = _as_list(block.get("exempt")) if "exempt" in block else list(DEFAULT_EXEMPT_GLOBS)
    raw = block.get("capabilities") or []
    capabilities = []
    for entry in raw if isinstance(raw, list) else []:
        if not isinstance(entry, dict):
            continue
        name = entry.get("name")
        if not name:
            continue
        name = str(name)
        declared = "spec" in entry
        if declared:
            spec = "" if entry.get("spec") in (None, "") else str(entry["spec"])
        else:
            # A spec is named for what it describes, so `spec.md` is never a name
            # this writes. `resolve_living_specs` falls back to the legacy name for
            # a registry written before the rename, because it can see the disk.
            spec = f"{DEFAULT_CAPABILITY_ROOT}/{name}/{name}{SPEC_SUFFIX}"
        capabilities.append(
            {
                "name": name,
                "match": _as_list(entry.get("match")),
                "exclude": _as_list(entry.get("exclude")),
                "spec": spec,
                # Only a defaulted path may be reinterpreted against the disk; a
                # declared one is what its author asked for.
                "spec_defaulted": not declared,
                # Emptying a capability's spec is a deliberate act. Absent is
                # false, which is every capability that never says otherwise.
                "retire": entry.get("retire") is True,
            }
        )
    return {
        "enabled": enabled,
        "layout": layout,
        "exempt": exempt,
        "capabilities": capabilities,
        "rules": load_rules(block.get("rules"), rule_warnings),
    }


# --------------------------------------------------------------------------- #
# Where the capability registry lives (the one answer both writers and readers use)
# --------------------------------------------------------------------------- #
LEGACY_CENTRAL_SPEC = "spec.md"


def _settle_default_specs(living: dict, root: str) -> dict:
    """Point a capability with no declared `spec` at whichever central file exists.

    The central path used to be `capabilities/<name>/<name>.spec.md` and is now
    `capabilities/<name>/<name>.spec.md`. A registry written before the rename
    declares no path at all, so the only way to keep it reading is to look.
    Neither present means nothing to read either way, and the new name stands so
    that whatever writes next writes the current shape.
    """
    for cap in living.get("capabilities", []):
        if not cap.get("spec_defaulted") or not cap.get("spec"):
            continue
        if os.path.exists(os.path.join(root, cap["spec"])):
            continue
        legacy = f"{DEFAULT_CAPABILITY_ROOT}/{cap['name']}/{LEGACY_CENTRAL_SPEC}"
        if os.path.exists(os.path.join(root, legacy)):
            cap["spec"] = legacy
    return living


def resolve_living_specs(root: str):
    """Return (living, meta) for a project root.

    `living` is the normalized block. `meta` is
    {"origin": "registry"|"legacy"|"none", "path": rel|None, "legacy_stale": bool,
     "warnings": [str], "errors": [str]}. `errors` holds the parse failures that left
    the resolver with no answer, so a writer can refuse to overwrite what it couldn't read.

    The registry file wins outright whenever it is present — including when it says
    `enabled: false` — so a stale legacy block can never resurrect a capability the
    registry dropped. A registry that exists but will not parse yields an empty,
    disabled result plus a warning rather than falling back. Never raises.
    """
    registry_path = os.path.join(root, LIVING_SPECS_REL)
    legacy_path = os.path.join(root, LEGACY_CONFIG_REL)
    legacy_cfg, legacy_warnings = load_config(legacy_path)
    legacy_has_block = isinstance(legacy_cfg.get("livingSpecs"), dict)

    if os.path.isfile(registry_path):
        try:
            with open(registry_path, encoding="utf-8") as fh:
                doc = load_yaml(fh.read()) or {}
            if not isinstance(doc, dict):
                raise ValueError("top level must be a mapping")
        except Exception as exc:  # noqa: BLE001 — an unreadable registry must not fall back
            error = f"malformed {LIVING_SPECS_REL} ({exc}); no capabilities loaded"
            return load_living_specs_block({}), {
                "origin": "registry",
                "path": LIVING_SPECS_REL,
                "legacy_stale": legacy_has_block,
                "warnings": [error],
                "errors": [error],
            }
        rule_warnings = []
        living = _settle_default_specs(load_living_specs_block(doc, rule_warnings), root)
        warnings = list(rule_warnings)
        if legacy_has_block:
            warnings.append(
                f"{LEGACY_CONFIG_REL} still has a livingSpecs block; {LIVING_SPECS_REL} "
                "is the registry and the old block is ignored — delete it"
            )
        return living, {
            "origin": "registry",
            "path": LIVING_SPECS_REL,
            "legacy_stale": legacy_has_block,
            "warnings": warnings,
            "errors": [],
        }

    if legacy_has_block:
        return _settle_default_specs(load_living_specs(legacy_cfg), root), {
            "origin": "legacy",
            "path": LEGACY_CONFIG_REL,
            "legacy_stale": False,
            "warnings": [],
            "errors": [],
        }

    return load_living_specs_block({}), {
        "origin": "none",
        "path": None,
        "legacy_stale": False,
        "warnings": list(legacy_warnings),
        "errors": list(legacy_warnings),
    }


def should_drop_legacy(meta: dict) -> bool:
    """True when the legacy block is safe to delete — it was the set just written forward.

    A `legacy_stale` block is NOT safe: the registry answered instead, so its capabilities
    were never carried over and deleting it would lose them. Those are only warned about.
    """
    return meta["origin"] == "legacy"


def is_project_root(path: str) -> bool:
    """True when `path` is its own project — it carries the registry or the legacy config.

    Only a confirmed absence of both answers False; any other error answers True so an
    unreadable candidate still bounds a scan rather than being walked into.
    """
    for rel in (LIVING_SPECS_REL, LEGACY_CONFIG_REL):
        try:
            if os.path.isfile(os.path.join(path, rel)):
                return True
        except OSError:
            return True
    return False


def _yaml_flow_list(items: list) -> str:
    """Render a string list as a YAML flow sequence with double-quoted scalars."""
    return "[" + ", ".join(f'"{i}"' for i in items) + "]"


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


def render_registry(enabled: bool, capabilities: list, exempt=None, rules=None) -> str:
    """Render the registry file's flattened body from a normalized capability list.

    `exempt=None` omits the key (the reader then applies DEFAULT_EXEMPT_GLOBS); an empty
    list is written as `exempt: []`, which the reader honors as "no exemptions".
    `rules` is re-emitted because the owned region covers it — a rewrite that
    dropped the argument would delete the project's authored guidance.
    """
    lines = [f"enabled: {'true' if enabled else 'false'}"]
    if exempt is not None:
        lines.append(f"exempt: {_yaml_flow_list(exempt)}")
    if capabilities:
        lines.append("capabilities:")
        for cap in capabilities:
            lines.extend(render_capability(cap))
    else:
        lines.append("capabilities: []")
    authored = [(step, (rules or {}).get(step) or []) for step in RULE_STEPS]
    authored = [(step, items) for step, items in authored if items]
    if authored:
        lines.append("rules:")
        for step, items in authored:
            lines.append(f"  {step}:")
            lines.extend(f'    - "{item}"' for item in items)
    return "\n".join(lines) + "\n"


def render_capability(cap: dict) -> list:
    """Render one capability as block-seq lines under the registry's `capabilities:` key."""
    pad, body = "  ", "    "

    def scalar(v: str) -> str:
        # Quote anything this module's own reader would refuse or misread bare —
        # a writer that can emit a file its reader rejects (or comment-strips) strands the registry.
        probe = f"k: {v}"
        if _unsupported(probe) or _strip_comment(probe) != probe or v != v.strip() or ":" in v:
            return f'"{v}"'
        return v

    out = [f"{pad}- name: {scalar(cap['name'])}", f"{body}match: {_yaml_flow_list(cap['match'])}"]
    if cap.get("exclude"):
        out.append(f"{body}exclude: {_yaml_flow_list(cap['exclude'])}")
    if cap.get("spec"):
        out.append(f"{body}spec: {scalar(cap['spec'])}")
    return out


def is_top_level_key(line: str) -> bool:
    """True for a column-0 mapping key — the start of a sibling top-level block."""
    return bool(line) and not line[0].isspace() and not line.lstrip().startswith("#")


def block_end(lines: list, last_key: int) -> int:
    """Index one past the block owned by the top-level key at `last_key`.

    Trailing blank lines and column-0 comments are inter-block spacing, not body, so
    they sit outside the span and survive a splice.
    """
    end = len(lines)
    for j in range(last_key + 1, len(lines)):
        if is_top_level_key(lines[j]):
            end = j
            break
    while end > last_key + 1:
        prev = lines[end - 1]
        is_col0_comment = prev.lstrip().startswith("#") and not prev[0].isspace()
        if prev.strip() == "" or is_col0_comment:
            end -= 1
        else:
            break
    return end


def splice_registry(original: str, rendered: str) -> str:
    """Replace the owned region of an existing registry file, preserving the rest.

    The owned region runs from the first top-level key in REGISTRY_KEYS through the last
    such key's indented body, so a header comment above it and anything below it survive.
    """
    lines = original.splitlines(keepends=True)
    owned = [
        i for i, ln in enumerate(lines)
        if is_top_level_key(ln) and ln.split(":", 1)[0].strip() in REGISTRY_KEYS
    ]
    if not owned:
        if not original.strip():
            return rendered
        prefix = original if original.endswith("\n") else original + "\n"
        return prefix + rendered
    start = owned[0]
    end = block_end(lines, owned[-1])
    return "".join(lines[:start]) + rendered + "".join(lines[end:])
