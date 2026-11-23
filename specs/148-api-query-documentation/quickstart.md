# Quickstart: validate the QUERY reference entries

> Superseded on 2026-11-23: QUERY requests no longer have pages of their own. Each is described on
> the page of its GET request. [research R12](research.md#r12-one-page-for-get-and-query-2026-11-23) has the design, and the
> [spec](spec.md) the requirements.

## Prerequisites

The worktree has `vendor` installed and `public/build` present. The docs tests need no database
server beyond the test one configured in `phpunit.xml`.

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php
```

Expected: the derivation tests pass, and `it_documents_every_registered_route` and
`it_registers_every_documented_route` pass with QUERY counted.

```bash
php artisan test --compact tests/Unit/Actions/BuildOpenApiDocumentTest.php
```

Expected: every QUERY operation is still described, with the same `operationId`s.

```bash
php artisan test --compact tests/Feature/Controllers/App/Docs
```

Expected: the QUERY entry page renders, and the return field test passes without an arm for any
derived id.

## By hand

1. Open `/docs/api`, expand Contacts in the sidebar: each GET row is followed by a QUERY row with the
   same label.
2. Open the QUERY entry of the contact list: heading ends with "with QUERY", parameters read "Body
   parameters", the cURL sample sends `-X QUERY` with a JSON body. See
   [the contract](contracts/reference-entry.md).
3. Copy that sample, run it against a test account with a token, and compare the answer with the GET
   entry's sample (SC-002).
4. Open the GET entry: its sentence links to the QUERY entry. Use "Next": it lands on the QUERY entry.
5. Open `/docs/api.md` and `/llms.txt`: the QUERY entry appears after the GET entry, by its own title.
