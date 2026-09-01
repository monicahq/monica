# Phase 1 Data Model: Response fields in the API reference

**Feature**: [spec.md](spec.md) | **Date**: 2026-09-01

No database change. Nothing here is stored, queried or migrated. The reference is copy held in PHP
files, and what follows is the shape of one row of it.

## A documented field

One entry in an endpoint's field list.

| Key | Type | Required | What it is |
| --- | --- | --- | --- |
| `name` | string | yes | The field's full path from the top of the answer: `data.attributes.name`, `data[].links.self`, `meta.total`, `token`. This is what a reader sees and what the check compares. |
| `type` | string | yes | One word from the vocabulary below. |
| `description` | string | yes | What the field is, and what its name and type do not imply. The checklist is in [research.md](research.md) section 6. |
| `opaque` | boolean | no | Present and true only for a field whose keys are not fixed. The check does not descend below it. Exactly one field in the reference carries it. |

There is no `depth`. Indentation is computed from the path, so the two cannot disagree.

### Path notation

| Written | Means |
| --- | --- |
| `token` | a field at the top of the answer |
| `data.type` | a field inside the `data` object |
| `data.attributes.name` | a field inside `attributes`, inside `data` |
| `data[]` | the records of a list, described once as a row of their own |
| `data[].attributes.name` | that field on each record of the list |
| `meta.total` | a field inside the paging block |

## The type vocabulary

Closed. Two fields of the same kind read the same way in every group, which is FR-007.

| Type | Used for | Note |
| --- | --- | --- |
| `string` | text | |
| `integer` | a whole number that is not a time | |
| `boolean` | true or false | |
| `timestamp` | a moment in time | Always a count of seconds. Every description says so, because the name does not. |
| `array` | a list of records | |
| `object` | a group of fields | |

Whether a field can be absent is **not** in the type. It is in the description, which has to say under
what circumstances and what absent looks like. A type of `string or null` was rejected: it doubles the
vocabulary and still does not say why or when.

## The envelope

Built once by a class beside the existing docs view models, called by each group file, given the kind of
record and the attribute rows. The rows it produces, for one record:

```
data                        object
data.type                   string     the constant naming the kind of record
data.id                     string     the address of this record
data.attributes             object
data.attributes.<field>     ...        supplied by the caller of the builder
data.links                  object
data.links.self             string     where this record is read
```

For a page, the same with `data` as an `array`, `data[]` describing a record, every attribute path
prefixed `data[].`, and four more rows:

```
meta                        object
meta.page                   integer
meta.per_page               integer
meta.total                  integer
```

Three endpoints answer outside this shape and write their rows directly: the health check, registration
and sign in. One answers in it and carries a field beside it: creating a credential returns `token` at
the top of the answer, alongside `data`.

## What the reference already holds, and what changes

`App\ViewModels\Docs\ApiDocsSection` is built from the array in each group file. It already carries
`returns`, a one sentence summary. It gains one key.

| Existing | Change |
| --- | --- |
| `returns` (string, the summary) | unchanged, and it stays above the field list. FR-002. |
| `params` (list of parameter rows) | unchanged. Parameters were already documented; this feature is about the answer. |
| `response` (the example answer) | unchanged. FR-002 and the spec's last assumption: an example shows one thing that did arrive, a field list says what can. |
| (new) | `returnFields`, the list of documented fields described above. |

Nothing else on the section object moves, so no rendering that exists today changes.

## The two sets the check compares

For one endpoint:

- **Documented**: the `name` of every row in `returnFields`.
- **Actual**: every path in the answer, built by walking the decoded JSON. An object contributes its own
  path and then its children. A list of records contributes its own path, then a `[]` row, then the
  paths of its first member. A scalar, or a null, contributes only itself. The walk stops at any path
  marked `opaque`.

The two sets must be equal. Not a subset either way: a field the reference invents is as wrong as a
field it forgets.

Two answers make the comparison degenerate, and both are guarded rather than allowed to pass quietly:

- An answer with no body. Both sets are empty, which is correct for the three endpoints that answer 204
  and wrong for anything else. The reference's summary has to say there is nothing to expect. FR-005.
- A list whose page is empty. `data[]` and everything under it cannot be produced, so the check would
  compare nothing and pass. It fails instead, naming the endpoint and saying a record has to be
  arranged.
