# Research: MCP tools for reading the account logs

## R1. Every capability already exists in the API

**Decision**: add no route. Each tool mirrors one existing route and runs the same query its
controller runs.

| Tool | Class | Mirrors | Query |
| --- | --- | --- | --- |
| `list-logs` | `ListLogs` | `api.v1.logs` | `account->logs()->with('user')->orderByDesc('id')` |
| `get-log` | `GetLog` | `api.v1.logs.show` | `account->logs()->with('user')->find($id)` |
| `list-emails-sent` | `ListEmailsSent` | `api.v1.emailsSent` | `account->emailsSent()->orderByDesc('sent_at')->orderByDesc('id')` |
| `get-email-sent` | `GetEmailSent` | `api.v1.emailsSent.show` | `account->emailsSent()->find($id)` |

**Rationale**: principle X. No action exists or is needed: the API controllers read the relation
directly, and nothing is written. No class in `app/Actions` shares one of these names, so no import
alias is needed.

**Alternatives considered**: reading through `LogsViewModel`. Rejected: it serves the web screen,
paginates by cursor and five at a time, and the tools mirror the API.

## R2. The list tools are paginated, unlike every existing MCP list tool

**Decision**: `list-logs` and `list-emails-sent` take optional `page` and `per_page` integers and
answer `{data, meta: {page, per_page, total}}`, the API's envelope. `per_page` is clamped exactly as
the controllers clamp it: `max(1, min($perPage ?? config('monica.api_default_items_per_page'),
config('monica.api_maximum_items_per_page')))`. `page` is passed explicitly to `paginate($perPage,
['*'], 'page', max(1, $page ?? 1))`.

**Rationale**: every existing MCP list tool returns the whole set because the API endpoint it mirrors
does. These two endpoints paginate, and the activity of an account grows without bound, so an
unpaginated tool would both differ from the API and hand an assistant thousands of rows. The page is
passed explicitly because `paginate()` otherwise reads it from the HTTP request, which for MCP is the
JSON-RPC envelope and never carries `page` at its root: every call would answer page one.

**Deviation from the API**: the API casts a non-numeric `per_page` to zero and so answers one row. The
tools declare both inputs as integers in their schema and validate `['nullable', 'integer']`, so a
non-numeric value is refused. A client driven by the schema never sends one. Out-of-range integers are
clamped, not refused, as over the API.

**Alternatives considered**: a shared helper for the clamp. Rejected: four lines, repeated in two tools
exactly as the two API controllers repeat them (CLAUDE.md, repeat short code inline).

## R3. The email body reaches the MCP tool

**Decision**: `EmailSentResource` takes whether to carry the body as a constructor argument instead of
asking `$request->routeIs('api.v1.emailsSent.show')`.

```php
public function __construct(EmailSent $resource, private readonly bool $withBody = false)
```

`EmailSentController::show` and `GetEmailSent` pass `withBody: true`. `collection()` builds each row
with one argument, so list rows keep leaving the body out.

**Rationale**: an MCP call is not on the `api.v1.emailsSent.show` route, so reusing the resource as it
stands silently drops the body, which the spec (FR-007) requires. The argument states the intent at the
call site and removes the resource's dependency on the route name. The API's behaviour is unchanged,
and `EmailSentControllerTest` (`it_leaves_the_copy_of_the_email_out_of_the_list`,
`it_carries_the_copy_of_the_email_when_one_is_asked_for`) guards that.

**Alternatives considered**:

- Adding the body in the tool after `resolve()`. Rejected: a second place deciding the resource's
  shape, which drifts.
- Widening the condition to `routeIs('api.v1.emailsSent.show', 'mcp')`. Rejected: couples the resource
  to the MCP route name and still reads the route from inside a resource.

## R4. Web addresses are removed from the body when it is recorded

**Decision**: in `CreateEmailSent::sanitize()`, after the existing Purify pass that forbids `<a>`,
remove every `http://` or `https://` address from the result:

```php
preg_replace('~https?://[^\s<>"]+~i', '', $cleaned)
```

**Evidence**: rendering `MagicLinkCreated` and passing it through the current sanitizer (in memory,
nothing saved) leaves exactly one address, the signed sign-in link in the subcopy line "If the “Sign in
to Monica” button does not work, copy and paste the address below into your browser:". The subcopy
is `resources/views/vendor/notifications/email.blade.php:55`, where the link text is the displayable
URL, so forbidding the tag keeps the URL as text. After the replacement the text of the email reads
unchanged apart from that line ending at the colon. The expression stops at `<`, a quote or
whitespace, and the purifier has already escaped `&` to `&amp;`, which the expression consumes, so the
whole query string goes.

**Rationale**: every email with an action button carries this line (`VerifyEmail`, `MagicLinkCreated`,
and any other notification using `->action()`), so fixing it in the one place bodies are recorded
covers them all, and the API as well as MCP. It is one line in the action that already sanitizes.

**Consequence accepted**: the subcopy sentence is left ending at its colon. Removing the whole subcopy
table would need a DOM pass over the HTML to find `table.subcopy`, which the purifier cannot target by
class. The body is a record of what was sent, and a sentence pointing at a removed address says
plainly that one was there.

**Alternatives considered**:

- A placeholder such as "(address removed)". Rejected: the listener runs on the queue, the locale at
  that moment is not reliably the reader's, and the placeholder would be English inside a translated
  email.
- Removing only addresses on the application's own domain. Rejected: a sign-in link on a custom
  domain or behind a proxy would slip through, and the spec says no web address.
- Rewriting rows already stored. Rejected in the spec: every link they hold has expired within an hour.

## R5. Not found

**Decision**: two trait methods on `MirrorsAnApiCapability`, `log()` and `emailSent()`, taking the
identifier as a string. An identifier that is not a positive whole number, or that names no row of the
key's account, throws `doesNotExist()` with a new translated sentence: `That log entry does not exist.`
and `That email does not exist.`

**Rationale**: the API route refuses a non-matching identifier (`[1-9][0-9]*`) as not found before the
controller runs, and a missing row as not found after. Both are the same answer to the reader, so the
tool gives one sentence for both, as every other trait lookup does. The schema declares the identifier
as a string, like every other tool, because the resource gives `id` as a string.

## R6. Nothing is journalled

**Decision**: no tool writes to `logs`. They call no action, so `LogUserAction` is never dispatched.
`ListLogsTest` proves listing leaves the count of `logs` rows unchanged.

## R7. Documentation

**Decision**:

- `McpDocsViewModel`: a tool group `Account logs` with `changesData: false` after `Life event
  categories`; a `LOGS` constant of two paragraphs; a section `['id' => 'logs', 'label' => 'Read the
  logs']` before `troubleshooting`; the paragraphs in `markdown()` under `## Read the logs`.
- `resources/views/app/docs/mcp.blade.php`: the section drawn the way `relationship-types` is.
- `MonicaServer` instructions: one paragraph saying the two list tools answer a page at a time, newest
  first, with the total.
- `resources/docs/api/09-emails-sent.php`: the `body` field description gains that links and web
  addresses are removed from the copy before it is kept. This corrects the API reference; no endpoint
  changes. The spec's assumption "the API reference needs no change" is updated accordingly.
- `docs/FEATURES.md`: an entry, through the `feature-documentator` skill.

The two paragraphs:

1. The activity lists every change made in your account, newest first: who made it, what it was, where
   it came from, such as the web, the API or an assistant, and the address it came from. A change made
   through MCP is marked as coming from an assistant, so you can ask your assistant what it did and
   check the answer against **Settings → Logs**.
2. The emails sent list what Monica sent your account and whether it arrived. Reading one returns what
   it said, with every link and web address removed before it was kept, so a sign-in link can never be
   read back from the logs.

**Rationale**: FR-009 and FR-010. The docs tests already fail when the page and the server disagree
(`McpDocsViewModelTest::it_lists_exactly_the_tools_the_server_registers`) and pin the count and the
changing groups.
