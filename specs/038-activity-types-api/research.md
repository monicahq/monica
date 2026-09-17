# Research: Activity type and category endpoints

Decisions taken before design, each with the alternative that was rejected in one line.

## The custom fields of a type

| Question | Decision | Rejected |
| --- | --- | --- |
| Granularity of the write | One whole layout at `PUT .../fields`, reusing `ReplaceActivityTypeFields` | Template style version, page, section and field endpoints: no action exists for them on a type, so the API would hold rules the product does not have |
| Body shape | Nested: `groups[]` each holding its `fields[]`, plus a top level `fields[]` for the ungrouped | The flat `groups` + `fields` pair the form posts: it makes the caller invent the join reference, which is a form mechanism rather than a contract |
| Read shape | `StructureVersionSectionResource`, already written for templates, over the sections of the type's published version | A new resource: the placement `id` it already answers is exactly the `placement_id` the write takes, so read then write round trips and a group the caller kept stays matched |

The controller turns the nested body into the action's arguments by using each group's index as its
reference. That is six lines of transport and no rule: which field belongs to which group, and which
field is the same field as before, both stay inside the action.

## The rest of the surface

| Question | Decision | Rejected |
| --- | --- | --- |
| Listing the types of one category | `GET /activity-categories/{activityCategory}/types`, mirroring `RelationshipCategoryTypeController` line for line | Nesting the types inside the category answer: it would make every category read carry every type |
| Listing every type of the account | `GET /activity-types`, which needs a `HasMany` on `Account` that does not exist yet | Making the caller walk the categories: eight requests to read a vocabulary |
| `is_active` on create | Not accepted. A type is created offered, and retired at the activation address | Accepting it as the web form does: FR-009 puts that decision at its own address, and `ActivityCategoryController::create` already takes a name and nothing else |
| Whether may be chosen, and where it sits | Each at its own address, `PUT .../activation` and `PUT .../position` | Folding either into the rewording: a caller correcting a name would move the type by omission |
| Refusing to delete a supplied type | `ActivityTypePolicy::delete` already denies it with wording; the controller converts the `AuthorizationException` to a 422 the way `ActivityCategoryController::destroy` does | A bespoke refusal type: `.claude/rules/policies.md` forbids one |

## The reference and the generated document

The OpenAPI document is not written. `BuildOpenApiDocument` assembles it from `resources/docs/api`
on every request, so documenting an endpoint is what puts it in the document. Three tests already
hold the three surfaces together, in both directions, and fail the suite the moment they disagree:

| Test | What it refuses |
| --- | --- |
| `ApiDocsViewModelTest` | A route in `routes/api.php` that no reference file documents |
| `BuildOpenApiDocumentTest::it_describes_every_endpoint_the_api_answers` | An endpoint the API answers and the document omits |
| `BuildOpenApiDocumentTest::it_describes_no_endpoint_the_api_does_not_answer` | A documented path the API does not answer |

So FR-016 needs no code. It needs the reference page to be right.

The page is added as `resources/docs/api/19-activity-types.php`, next to the categories at 18, and
`19-mcp.php` is renamed `20-mcp.php` so the MCP guide stays last in the portal. Both the portal and
the document glob the directory and sort by filename, and every section carries an explicit `id`, so
the rename changes the reading order and no URL.

## What the reference already gets wrong

`18-activity-categories.php` says of `activity_type_count` that activity natures are not built yet,
so it answers 0 on every category. `ActivityCategory::activityTypeCount()` has counted correctly
since the settings screens shipped. The prose is the only thing wrong, and FR-015 is that sentence.
