# Research: A lightweight list of contacts

Checked against the code on 2026-11-22.

## R1. A new endpoint, not a parameter on the contact list

Decision: a separate endpoint, `GET /api/v1/vaults/{vault}/contact-summaries`.

Rationale: `ContactController::index()` builds every list column (`ContactListCells`), eager loads five
relations and filters through `inGrouping()`, which always drops archived contacts and never returns
partial and full contacts together. A parameter switching all of that off would leave one method
with two answers of different shapes. The MCP rule is one tool per endpoint, so a separate endpoint
also gives the assistant a tool it can pick by name.

Rejected: `?fields=summary` on `api.v1.contacts` (two shapes in one method); changing what `all`
means in `ContactGroupingEnum` (changes a shipped answer, principle VI).

## R2. Names

| Thing | Name |
| --- | --- |
| Route | `vaults/{vault}/contact-summaries`, name `api.v1.contactSummaries`, `GET` and `QUERY` like its siblings |
| Controller | `App\Http\Controllers\Api\ContactSummaryController::index` |
| MCP tool | `App\Mcp\Tools\ListContactSummaries`, `list-contact-summaries` |

The path sits beside `contact-categories`, so it never meets `contacts/{contact}`, whose parameter
also accepts a slug. Rejected: `contact-names`, too close to the `list-contact-name-representations`
tool.

## R3. Parameters are words, not booleans

Decision: `filter` takes `partial` or `full` (absent: both); `archived` takes `excluded` (default) or
`included`.

Rationale: the API reads these from a query string, where Laravel's `boolean` rule refuses the
string `true` an agent naturally sends. A closed list of words gives the 422 naming the accepted
values that the spec asks for (US2, scenario 3), and reads the same through the API and the tool.

`partial` rather than `names-only`, the slug the contact list's grouping gives the same people:
`partial` is the word of the response field (`is_partial`), of the model and of the screen label
("Partial contacts"), and the grouping has no word for "full" to pair it with.

## R4. Paging

Decision: paged with `page` and `per_page`, 100 by default and 500 at most, read from two keys of
`config/monica.php` beside the general pair (`api_default_contact_summaries_per_page`,
`api_maximum_contact_summaries_per_page`), as the controllers skill asks of every paginated API
`index`, with the `meta` block of the contact list (`page`, `per_page`, `total`).

Rationale: an entry is four fields, so 500 entries stay smaller than one page of 25 full contacts.
SC-001 (500 contacts in at most two requests) holds with room. The general API maximum (100, from
`config('monica.api_maximum_items_per_page')`) is sized for full records and does not apply.

## R5. The query

`Contact::query()->where('vault_id', ...)`, `is_partial` per `filter`, `is_archived = false` unless
`archived=included`, `sortedBy(ContactSortEnum::Name, Ascending)` so the order is the vault list's
default, then `paginate()`. Soft deleted contacts are left out by the model's `SoftDeletes`. The name
is the `name` accessor, which reads the name columns of the row only, so no relation is loaded. The
vault is resolved the way `ContactController` does, so another account's vault answers 404.

No API key scope: `ApiKeyScopeEnum` guards sensitive fields and administration, and a name is
neither (`ContactResource` returns it unguarded).
