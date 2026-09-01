# Phase 0 Research: Response fields in the API reference

**Feature**: [spec.md](spec.md) | **Date**: 2026-09-01

Nothing was left as NEEDS CLARIFICATION. The two scope questions were settled with the author before
the spec was written: the field list is the scope, and correctness is proven by calling the endpoints
rather than by comparing the reference against its own examples. What follows is the set of decisions
this feature has to take, plus three things reading the code disproved.

## 1. What the answers actually look like

**Finding**: every record the API hands out is wrapped, and the spec's first draft got the depth wrong.

The four resources shipped and the fifth added last week all answer in the same envelope:

```
data
data.type                  a constant naming the kind of record
data.id                    the address
data.attributes            everything about the record
data.attributes.<field>    one value
data.links.self            where this record lives
```

A list wraps that again, and adds the paging block:

```
data[]                     the records on this page
data[].attributes.<field>  a value on a listed record
meta.page / per_page / total
```

So a value on a listed record is three levels down, not one. Three endpoints answer outside the
envelope entirely: the health check with three flat strings, and registration and sign in with a token
and an expiry. Three answer with no body at all.

| Endpoint | Answer |
| --- | --- |
| `GET /status` | 3 flat fields |
| `POST /register`, `POST /login` | 2 flat fields |
| `DELETE /logout`, `DELETE /api-keys/{apiKey}`, `DELETE /vaults/{vault}` | nothing |
| `GET /me`, `PUT /me` | one record, 12 attributes |
| `GET /api-keys`, `/api-keys/{apiKey}`, `POST /api-keys` | one record or a page, 5 attributes. Creation carries a `token` beside the envelope |
| `GET /logs`, `/logs/{log}` | one record or a page, 7 attributes |
| `GET /emails-sent`, `/emails-sent/{emailSent}` | 8 attributes listed, 9 when read singly |
| `GET /vaults`, `/vaults/{vault}`, `POST /vaults`, `PUT /vaults/{vault}` | one record or a page, 8 attributes |

Counted out, that is roughly 230 documented rows across the 16 endpoints that answer with a body. Most
of them are the same envelope written again, which is what decision 3 is about.

**Consequence for the spec**: its assumption that one level of nesting was enough has been corrected
rather than left standing.

## 2. A field is named by its path

**Decision**: the name of a documented field is its full path from the top of the answer:
`data.attributes.name`, `data[].links.self`, `meta.total`, `token`. Nesting is still shown visually, by
indentation computed from the path, but the path is what is written and what a reader is read.

**Rationale**: three requirements land on the same answer.

- FR-004 says a nested field's parent must not be carried by indentation alone, for a reader who cannot
  see it or who is being read the page. A path says the parent in the name itself, in every rendering,
  with nothing added.
- The plain text rendering (FR-010) has no rails or indentation to lean on. A path survives it.
- The check in FR-011 compares two sets of names. Paths make that a plain set comparison, at any depth,
  with no tree walking on either side.

It also removes the `depth` number the design carries beside each field. Depth is derivable from the
path, so it cannot disagree with it, and there is one fewer thing to get wrong in 230 rows.

**Alternative rejected**: the design's own shape, a bare leaf name plus a depth number, with a left
rail drawn for anything nested. It reads a little cleaner on the page, and it fails the other three
requirements: it is silent about the parent in plain text, it makes the check walk a tree it has to
rebuild from depth numbers, and a wrong depth number renders a field under the wrong parent with
nothing to catch it.

**Notation**: a dot for a field inside an object, `[]` for a field on a member of a list. `data[]` is
written once as the row describing the records; its members are `data[].type` and so on.

## 3. The envelope is described once, in code, not 16 times by hand

**Decision**: add a small class beside the existing docs view models that builds the envelope rows for a
record and for a page, given the kind of record and its list of attribute rows. Each reference file
calls it, and writes only the attribute rows itself.

**Rationale**: about 160 of the 230 rows are the envelope. Written by hand 16 times they would say
sixteen slightly different things about the same `type`, `id`, `links.self` and paging block, which is
exactly what FR-007 forbids. Written once they cannot. It also cuts the hand written work to the 70
rows that actually differ, which is the part where care is worth spending.

This is an extraction the project's own rule allows, since the logic is reused in five files and is a
concept worth naming. `app/ViewModels/Docs/` already holds `ApiDocsViewModel`, `ApiDocsSection` and
`ApiDocsCodeSamples`, so it is an existing folder and needs no amendment.

**Alternative rejected**: a shared PHP file under `resources/docs/api/` that the group files require.
`ApiDocsViewModel::groups()` builds the sidebar from `glob('*.php')` over that folder, so any file put
there becomes a group in the navigation. A leading underscore would not save it; the glob takes
everything.

**Alternative also rejected**: a local closure repeated in each of the five group files. Cheaper, and it
leaves five copies of the envelope prose to drift apart, which is the thing this feature exists to stop
happening to field documentation.

## 4. Free form values, and where the check has to stop

**Finding**: one documented field holds whatever an action put in it. `Log::$parameters` is cast to
`array` and carries different keys for different actions: `{"name": "Dunder Mifflin"}` for a vault
creation, something else for a template publication.

**Decision**: a field may be marked as not looked inside. The reference documents
`data.attributes.parameters` as an object, says its keys depend on the action, and marks it, and the
check does not descend below it.

**Rationale**: without a marker the check would compare `data.attributes.parameters.name` against
whatever the arranged log happened to record, so it would pass or fail on the shape of test data rather
than on the reference. With the marker the honesty is explicit and greppable: exactly one field in the
reference carries it today, and adding a second is a decision somebody makes on purpose.

**Alternative rejected**: letting the check stop wherever the reference happens to stop, so that any
field documented without children is treated as opaque. It needs no marker, and it also means anybody
can silence the check for a whole record by documenting `data.attributes` and nothing under it. The
marker keeps the check strict everywhere it is not deliberately turned off.

## 5. Types come from the code, not from the field name

**Finding**: names mislead, and two fields already prove it.

- `Log::$action` looks like an enum and is a plain string. The model casts `parameters`, `changes` and
  `source`, and not `action`.
- Every moment in time in this API is a count of seconds, not a written date. `created_at` is
  `->timestamp`, an integer.

**Decision**: the vocabulary is fixed and small, each type is read off the resource and the model rather
than guessed, and whether a field can be absent is said in the description rather than in the type. The
vocabulary is in [data-model.md](data-model.md).

**Rationale**: FR-007 wants one vocabulary; FR-008 wants the form of a time to be unambiguous. Two
fields already being surprising is the reason to check all 70 rather than trust the names.

## 6. What each description has to carry

**Decision**: FR-008 is turned into a checklist applied to every row, because 230 rows written from a
vague instruction will be inconsistent by the end.

| If the field is | The description says |
| --- | --- |
| bounded text | the maximum, in characters |
| drawn from a fixed set | the values, or exactly where to read them |
| sometimes absent | under what circumstances, and what absent looks like |
| a moment in time | that it is a count of seconds |
| derived rather than stored | what it is really telling the reader, and from what |
| a constant | the value it always holds |
| a counter | what it counts, including what it leaves out |
| none of the above | a plain sentence, and no invented rule |

The last row is FR-009 and it matters as much as the others. A rule invented to make a row look
thorough is a rule a reader will act on.

## 7. How the check calls 19 endpoints

**Decision**: one test class with two tests.

- One asserts that the list of endpoints the check covers is exactly the list of endpoint sections in
  the reference. That is what makes an endpoint added later fail rather than go unchecked.
- One is given each endpoint in turn, arranges the state it needs, calls it, flattens the answer to a
  set of paths, and compares that against the documented paths.

The list of endpoints is written out rather than read from the reference, because a data provider runs
before the application is booted and the reference is read through `resource_path()`. The first test is
what stops that written list from falling behind.

**Rationale**: FR-012 says the check arranges its own state, and FR-013 says no endpoint stands in for
another. A `match` on the section id, one short arm each, does both and stays readable: the arm for
listing vaults makes a vault, the arm for reading one email makes an email that has been delivered.
Nothing is shared between arms, so nothing couples two endpoints.

**Two traps the arms have to avoid.**

- A list whose page is empty proves nothing: there is no record to look inside, so every
  `data[].something` row would go unverified and the check would pass. The arm has to create at least
  one record, and the check has to fail loudly, naming the endpoint, when a documented path contains
  `[]` and the answer's list is empty.
- Registration and sign in create an account each. They are ordinary feature test requests, which the
  suite already makes in `RegistrationControllerTest` and `LoginControllerTest`, so this is a note
  rather than a problem: the database is rolled back like everywhere else.

## 8. Where this touches the design, and where it does not

The design was read in full. The portal already has its title, its method and path row, its parameter
list with types and required flags, its code panel with three languages, its response panel, its copy
actions and its sidebar filter. The one block it adds is the field list under Returns, which is the
scope.

Three things in the design are deliberately not taken:

| The design has | This feature does | Why |
| --- | --- | --- |
| a bare leaf name plus a `depth` number | the full path as the name | Decision 2. Accessibility, plain text, and a checkable comparison. |
| an inline light only palette in hex | the portal's tokens | The portal supports dark mode and the project's rule is that the design system is tokens in Blade. A hex palette pasted in would look right in one theme and wrong in the other. |
| a breadcrumb, previous and next links, a keyboard shortcut, restyled chips | none of them | Out of scope, recorded in the spec's assumptions. Each is a separate change and none is needed for a reader to learn what a field is. |

## 9. Localization

No new translated string. The reference is English copy on a surface that does not go through `__()`,
which is the existing choice for it, and a field name is a JSON key rather than copy. FR-015 says so.
`php artisan monica:localize --check` still runs, to prove nothing went missing.

## 10. Risks

- **The 70 attribute rows are the work, and care does not scale by itself.** The checklist in decision 6
  is the guard. The greatest risk is a plausible sentence that is wrong, and the check cannot catch
  that: it compares names, never meanings. Every bound, every set and every derivation has to be read
  off the code, not remembered.
- **The check can pass while proving nothing.** An empty list, an opaque marker in the wrong place, or
  an arm that arranges a record without the optional field set, each leaves rows unverified. The two
  traps in decision 7 are covered; the opaque marker being used exactly once is worth asserting.
- **The envelope builder is a single point of failure in the good sense.** If it describes `type`
  wrongly, it does so in 16 places at once. Its own test is therefore not optional.
- **Nothing about the API changes.** No route, no resource, no controller, no migration. The only
  application code added is the envelope builder and the field list on the section object, so nothing a
  caller relies on can move.
