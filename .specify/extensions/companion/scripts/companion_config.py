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

HOOK_TYPES = {"command", "prompt", "node"}
WHENS = ("before", "after")


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


def _starts_block_map(rest: str) -> bool:
    """True for a seq item that opens a block mapping (`key: val`), not a scalar.
    A colon followed by end-or-space marks the key/value split; `http://x` (colon
    then `/`) and bare scalars (`resolve-dir`) stay scalars."""
    ci = rest.find(":")
    return ci != -1 and (ci + 1 == len(rest) or rest[ci + 1] == " ")


def load_yaml(text: str):
    """Parse the constrained YAML subset into nested dict/list. Raises on the rest."""
    lines = [stripped for ln in text.split("\n") if (stripped := _strip_comment(ln)).strip()]
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
            key, val = stripped.split(":", 1)
            key, val = key.strip(), val.strip()
            pos[0] += 1
            if not val:
                out[key] = parse_block(ind + 1)
            elif val.startswith("{") or val.startswith("["):
                out[key] = _parse_flow(val)
            else:
                out[key] = _scalar(val)
        return out

    result = parse_block(0)
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


def resolve_order(config: dict, command: str, default_order: list) -> list:
    """A recipe's `nodes: [...]` replaces the default order; else the default."""
    cmd = (config.get("commands") or {}).get(command) or {}
    nodes = cmd.get("nodes")
    return list(nodes) if isinstance(nodes, list) and nodes else list(default_order)


def merge_hooks(config: dict, command: str, active_nodes: list, nodes_dir: str = None):
    """Return (ordered_hooks, warnings).

    ordered_hooks is a flat list of dicts: {when, anchor, index, hook}. Hooks at a
    given (when, anchor) keep their declared order. An anchor not in active_nodes is
    warned + skipped. A `type: node` hook with no `ref` always raises ConfigError;
    when `nodes_dir` is given, a `ref` whose `.md` file is absent also raises.
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
                    ref_path = os.path.join(nodes_dir, f"{ref}.md") if nodes_dir else None
                    if not ref or (ref_path and not os.path.isfile(ref_path)):
                        raise ConfigError(
                            f"hook {command}.{when}.{anchor}[{i}] type:node ref '{ref}' has no node file"
                        )
                ordered.append({"when": when, "anchor": anchor, "index": i, "hook": hook})
    return ordered, warnings


def validate_reads(active_meta: dict):
    """active_meta: {node_id: reads_list}. A kept node reading a dropped node is an error."""
    active = set(active_meta)
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
DEFAULT_EXEMPT_GLOBS = ["*.config.*", "*.test.*", "**/migrations/**"]

# At the project root, outside `.specify/`, which routine cleanup restores wholesale.
LIVING_SPECS_REL = "living-specs.yml"
LEGACY_CONFIG_REL = os.path.join(".specify", "companion.yml")

# Top-level keys the registry file owns, in emit order.
REGISTRY_KEYS = ("enabled", "exempt", "capabilities")


def _as_list(value) -> list:
    """Coerce a scalar/None/list into a list of non-empty strings."""
    if value is None:
        return []
    if isinstance(value, list):
        return [str(v) for v in value if v not in (None, "")]
    return [str(value)] if value != "" else []


def load_living_specs(config: dict) -> dict:
    """Read the `livingSpecs` block of a companion.yml-shaped mapping."""
    return load_living_specs_block((config or {}).get("livingSpecs"))


def load_living_specs_block(block) -> dict:
    """Normalize a living-specs mapping into a typed shape.

    Returns {"enabled": bool, "exempt": [glob], "capabilities": [{name, match, exclude, spec}]}.
    `enabled` defaults to False (opt-in). `exempt` is the drift exempt-glob list,
    defaulting to DEFAULT_EXEMPT_GLOBS when unset. Each capability normalizes `match`/`exclude`
    to string lists and defaults `spec` to `capabilities/<name>/spec.md`. A capability
    whose `spec` is declared but empty keeps "" so the resolver can flag the bad path.

    A mapping carrying a `livingSpecs` key is unwrapped, so the registry file accepts
    both its own flattened shape and a block pasted over from the legacy config.
    """
    block = block or {}
    if isinstance(block, dict) and isinstance(block.get("livingSpecs"), dict):
        block = block["livingSpecs"]
    if not isinstance(block, dict):
        return {"enabled": False, "exempt": list(DEFAULT_EXEMPT_GLOBS), "capabilities": []}
    enabled = bool(block.get("enabled", False))
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
        if "spec" in entry:
            spec = "" if entry.get("spec") in (None, "") else str(entry["spec"])
        else:
            spec = f"{DEFAULT_CAPABILITY_ROOT}/{name}/spec.md"
        capabilities.append(
            {
                "name": name,
                "match": _as_list(entry.get("match")),
                "exclude": _as_list(entry.get("exclude")),
                "spec": spec,
            }
        )
    return {"enabled": enabled, "exempt": exempt, "capabilities": capabilities}


# --------------------------------------------------------------------------- #
# Where the capability registry lives (the one answer both writers and readers use)
# --------------------------------------------------------------------------- #
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
        warnings = []
        if legacy_has_block:
            warnings.append(
                f"{LEGACY_CONFIG_REL} still has a livingSpecs block; {LIVING_SPECS_REL} "
                "is the registry and the old block is ignored — delete it"
            )
        return load_living_specs_block(doc), {
            "origin": "registry",
            "path": LIVING_SPECS_REL,
            "legacy_stale": legacy_has_block,
            "warnings": warnings,
            "errors": [],
        }

    if legacy_has_block:
        return load_living_specs(legacy_cfg), {
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


def render_registry(enabled: bool, capabilities: list, exempt=None) -> str:
    """Render the registry file's flattened body from a normalized capability list.

    `exempt=None` omits the key (the reader then applies DEFAULT_EXEMPT_GLOBS); an empty
    list is written as `exempt: []`, which the reader honors as "no exemptions".
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
    return "\n".join(lines) + "\n"


def render_capability(cap: dict) -> list:
    """Render one capability as block-seq lines under the registry's `capabilities:` key."""
    pad, body = "  ", "    "
    out = [f"{pad}- name: {cap['name']}", f"{body}match: {_yaml_flow_list(cap['match'])}"]
    if cap.get("exclude"):
        out.append(f"{body}exclude: {_yaml_flow_list(cap['exclude'])}")
    if cap.get("spec"):
        out.append(f"{body}spec: {cap['spec']}")
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
