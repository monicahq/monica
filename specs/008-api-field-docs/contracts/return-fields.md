# Contract: the field list, as a reader meets it

**Feature**: [../spec.md](../spec.md) | **Date**: 2026-09-01

This feature exposes no new endpoint. What it exposes is a block of the reference, in two renderings,
and a rule about what may appear in it. That is the contract.

## On the reference page

The Returns block, between the parameters and the rate limit note, on every endpoint.

```
Returns
A page of vault objects, and how many there are in all.
──────────────────────────────────────────────────────────
data                              array
  The vaults on this page, in the order you arranged them.
──────────────────────────────────────────────────────────
  data[]                          object
    One vault.
──────────────────────────────────────────────────────────
  data[].type                     string
    Always vault. It names the kind of record, so a
    caller holding a mixed collection can tell them apart.
──────────────────────────────────────────────────────────
  data[].id                       string
    The identifier of the vault, a uuid. It is the
    address every vault call takes.
──────────────────────────────────────────────────────────
    data[].attributes.name        string
    Up to 255 characters. What the vault is called.
──────────────────────────────────────────────────────────
  meta.total                      integer
    How many vaults your account holds in all, not how
    many are on this page.
```

Rules the rendering follows:

- The summary sentence stays, above the list. It answers a different question from the rows.
- A row is the field's full path, its type, and a sentence. Nothing else.
- Indentation is computed from the path, so it can never disagree with it. It is decoration: the path is
  what carries the parent, which is what makes the block work when the indentation cannot be seen.
- The type is a word, never a colour. Nothing in the block is distinguished by colour alone.
- The rows keep the portal's own tokens, so the block reads correctly in both themes.
- An endpoint with nothing to return shows the summary and no rows.

## In the plain text rendering

The same fields, at `/docs/api/{section}.md` and in the whole document at `/docs/api.md`, under a
`## Returns` heading after the summary:

```markdown
## Returns

A page of vault objects, and how many there are in all.

- `data` (array): The vaults on this page, in the order you arranged them.
- `data[]` (object): One vault.
- `data[].type` (string): Always vault. It names the kind of record, so a caller holding a mixed collection can tell them apart.
- `data[].id` (string): The identifier of the vault, a uuid. It is the address every vault call takes.
- `meta.total` (integer): How many vaults your account holds in all, not how many are on this page.
```

Flat, one bullet per field, in the order of the answer. The path is the whole of the nesting here, which
is why the path is what is written rather than a leaf name. This rendering is what a program is handed,
and what somebody reading with assistance is likely to read.

## What may appear in the block

| Rule | |
| --- | --- |
| Every field the answer carries has a row | FR-001, and the check proves it |
| No row names a field the answer does not carry | FR-011, the same check, the other direction |
| Types come from a closed vocabulary of six words | FR-007, listed in [../data-model.md](../data-model.md) |
| Nullability is in the description, never in the type | [../data-model.md](../data-model.md) |
| A bounded string says its maximum, in characters | FR-008 |
| A value from a fixed set lists it, or says where to read it | FR-008 |
| A field that can be absent says when, and what absent looks like | FR-008 |
| A time says it is a count of seconds | FR-008 |
| A derived field says what it really reports, and from what | FR-008 |
| A field whose name and type say everything gets a plain sentence | FR-009 |
| Nothing is translated | FR-015 |

## The guarantee behind it

Every documented endpoint is called by the suite, and the field names in the reference are compared
against the field names in the answer. The comparison is an equality, in both directions, per endpoint.

| Change | What happens |
| --- | --- |
| a resource gains a field, reference untouched | the suite fails, naming the endpoint and the field |
| a resource loses or renames a field, reference untouched | the suite fails, naming the endpoint and the field |
| the reference names a field nothing returns | the suite fails |
| an endpoint is added to the API | the suite fails until it has a reference entry, a field list, and an arm in the check |
| a field's description is wrong but its name is right | **the suite passes.** The check compares names, never meanings. Descriptions are guarded by review and by the checklist, and by nothing else. |

That last row is the honest limit of this feature and it belongs in the pull request.

## Not in this contract

- The design's breadcrumb, its previous and next links, its keyboard shortcut, and its restyled chips.
- Any change to what an endpoint returns, its address, or its status. This describes what the API
  already does.
- Request parameters, which were already documented.
- The example responses, which stay exactly as they are.
