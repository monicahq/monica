# Research: MCP documentation page

<a id="r1"></a>
## R1. Language of the page

- **Decision**: English copy in the markup and the view model, as the API reference writes it. The
  portal card, the page title in `partials.meta` and the shared header stay translated.
- **Rationale**: the API reference is the precedent the components rule names ("its own small design
  system down to the English strings written in the markup"); code wins over the constitution's
  default in the precedence order of `CLAUDE.md`.
- **Alternatives**: `__()` on every sentence, rejected: about 60 strings of reference copy in 30
  locales, none read by a native speaker.

<a id="r2"></a>
## R2. Third party clients, checked on 2026-09-25

| Client | Source | Finding |
| --- | --- | --- |
| Claude Code | code.claude.com/docs/en/mcp | `type: http`, `url`, `headers`; `${VAR}` expands in `headers`; project servers need approval; `/mcp` shows status. Confirmed |
| Cursor | cursor.com/docs/context/mcp | Remote entry has no `type`; `${env:NAME}` resolves in `headers`; file `~/.cursor/mcp.json`. Confirmed |
| Codex | learn.chatgpt.com/docs/extend/mcp | `codex mcp add NAME --url URL --bearer-token-env-var VAR`; TOML `url`, `bearer_token_env_var`. Confirmed. `auth` defaults to `oauth`; whether a bearer variable alone skips OAuth is not stated, checked in the quickstart |
| VS Code | code.visualstudio.com/docs/agents/reference/mcp-configuration | "MCP: Open User Configuration"; `inputs` of `promptString` with `password: true`, "securely stored"; servers needing `${input:…}` are not forwarded to Agent Host. Confirmed |
| Claude Desktop | claude.com/docs/connectors/custom/add-unlisted | "choose No sign-in and add the key under Request headers"; the value is sent as entered, so it must read `Bearer …`; "in beta and available to a limited set of organizations". The design's "not confirmed" is wrong |
| ChatGPT | developers.openai.com/api/docs/guides/developer-mode | "OAuth, No Authentication, and Mixed Authentication" only. The design's notice holds |

<a id="r3"></a>
## R3. Open in Claude and Open in ChatGPT

- **Decision**: `https://claude.ai/new?q=` and `https://chatgpt.com/?hints=search&q=`, the prompt
  being "Read {mcp.md URL} so I can ask you questions about it." URL encoded.
- **Rationale**: Claude documents `q` for its desktop link (support.claude.com article 14729294); the
  web form and both ChatGPT forms are what Retype, Chakra UI, PrimeNG and others ship. `hints=search`
  lets ChatGPT fetch the address. Neither web form is officially documented, so a broken link is a
  copy fix, not a code change.
- **Alternatives**: leaving both links out, rejected: the design has them and the risk is small.

<a id="r4"></a>
## R4. Facts of the design confirmed against the code

The `/api/mcp` path (`routes/api.php`, `Mcp::web('mcp', …)` under the `api` prefix); Sanctum Bearer
keys; 13 tools, named by `Primitive::name()` as the kebab case of the class; three that change data;
`ContactResource` carries no field values, notes or life events; the client choice "does not change
what the key reaches" (API keys form); the first vault is named "Personal" (`CreateAccount`); keys are
revoked under Settings → Security and access → API keys; `SetMcpLogSource` records the source as MCP
with the key's name; `throttle:mcp` is 60 a minute per key, none when self hosted.

<a id="r5"></a>
## R5. Holding the table to the server

- **Decision**: the unit test reads `EricaServer`'s `$tools` default through reflection, builds each
  tool, and compares the set of `name()` values with the names in the view model.
- **Alternatives**: building the table from the server at run time, rejected: descriptions are
  written for readers and differ from the tools' own, which are written for assistants.
