# Research: 128 MCP descriptions and docs

## R1. One update rule: keep what is left out, null clears

Survey of the 82 `PUT` routes in `routes/api.php` (2026-11-17): 25 replace, 5 mixed, 15 already keep,
37 carry a single value or no body.

- **Decision**: the 30 replacing or mixed routes move to the pattern `GroupController@update` already
  uses: a field is validated `sometimes`, and the controller passes
  `array_key_exists($key, $validated) ? $validated[$key] : $record->value` to the action. A field that
  cannot be empty (a name) is `sometimes` plus `required`, so null is refused rather than clearing it.
  A boolean left out keeps its value instead of becoming false.
- **A set sent whole** (`lifeEventTypes.fields`, `contacts.nameRepresentations`, `contactListColumns`,
  the `values` of a possession, the `cells` of a knowledge item): a set left out keeps the set; a set
  sent replaces it, except `values` and `cells`, which already merge by key and stay so.
- Routes without a tool (`me`, `vaults`, notification channels and preferences, template pages,
  contact list columns, contact sections) change too: FR-010 is about the API, not only what MCP uses.
- **Rejected**: adding `PATCH` beside `PUT` (two verbs, one meaning); a new API version (the change
  breaks only a client clearing a field by omitting it, which the changelog tells to send null).

## R2. Every tool answers through the API

64 routes are mirrored by a tool today and only `UpdateLocation`'s family calls `answerThroughApi()`.

- **Decision**: every tool's `handle()` validates only what addresses the record, looks it up
  through the trait (for the translated "does not exist" and the MCP history), then returns
  `answerThroughApi()`, as `.claude/rules/mcp.md` describes. Its schema lists the endpoint's fields.
- **Parameter names follow the API**, because `answerThroughApi()` sends the tool's input as the
  request body. Where the API names one record two ways (`location_id` on a possession, `location` on
  a move), the API name wins and is not changed: renaming an API field breaks clients. FR-008 and
  FR-005 are narrowed to say so.
- Tool tests shrink to the wiring `mcp.md` lists; what an endpoint does is proven once, in its
  controller test.

## R3. Vocabulary

| Concept | Word | Replaces |
| --- | --- | --- |
| A record's UUID | id | address, identifier |
| Where changes are written | the account log | journal, changelog, activity |
| A kind of relationship or life event grouped | category | group |
| A dated thing on a contact or group | life event | activity |
| `search-records` result bucket | result group | group (the API field keeps its name) |

## R4. The documentation page

- **Decision**: `McpDocsViewModel` builds the catalogue from the tools `MonicaServer` registers, through
  `Tool::toArray()`: name, description, input schema (type, required, description) and the read only
  annotation, which is the "changes data" mark. The 20 families of the design and their order are a
  map from tool name to family in the view model; a test fails when a registered tool has no family.
  The hand written `toolGroups()` is deleted.
- **One URL per page**: `docs/mcp` is Getting started, `docs/mcp/tools/{family}` a family. Links, the
  back button and "On this page" work without script. Search and the client tabs are Alpine; search
  filters links rendered by the server. **Rejected**: the design's single page held in script state.
- The page reuses `x-button`, `x-badge`, `x-icon`, `x-copy-button` and the design tokens already in
  `resources/css/app.css`. Strings stay as the docs pages write them today (English in the markup).
- `docs/mcp.md` is rendered from the same catalogue, so it cannot drift from the page.

## R5. Announcing the change

An entry in `resources/docs/api-changelog/` lists every endpoint whose update rule changed and says to
send null to clear. The API reference (`resources/docs/api/*.php`) states the rule once in its guide
and drops every per field "cleared when left out".
