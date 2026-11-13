# Contract: what the MCP server tells a connected assistant

No parameter, response shape or refusal changes. Only the catalogue paging and three texts change.

## `tools/list`

| Request | Before | After |
| --- | --- | --- |
| No cursor | 15 tools and a `nextCursor` | Every tool registered for the key, no `nextCursor` |
| `per_page` above 500 | Capped at 50 | Capped at 500 |

## Server instructions, relationship paragraph (replaces the current one)

> A relationship links a contact to somebody else of the same vault: a partner, a parent, a child, a
> colleague. Find the type with list-relationship-types and the contact with list-contacts or
> search-records, and call list-contact-relationships first to avoid recording the same link twice.
> The other person can be an existing contact, or somebody named only in the call with first_name,
> or somebody whose name is unknown. Somebody named in the call gets no record of their own unless
> creates_record is true: they exist only through the relationship, which is how to add a wife, a
> child or a cousin the person mentions without filling the vault. holds_role_a says which person
> holds role_a of a type that is not symmetric; for parent and child, true makes the contact the parent.

## Server instructions, new possession paragraph (after the possession type paragraph)

> A possession is an object somebody owns, recorded in one vault: a bicycle, a painting, a car.
> Record one with create-possession, giving it a type from list-possession-types; when no type fits
> what the person described, ask them rather than picking one. Write the values of its type's fields
> afterwards with set-possession-values, and how it was acquired with create-possession-transaction.

## Tool descriptions, first sentence only

| Tool | Before | After |
| --- | --- | --- |
| `create-contact-relationship` | Link a contact to somebody else in the same vault with a relationship type, such as partner or parent. | Add a partner, a family member or any other relative to a contact, with or without a record of their own, by linking them with a relationship type. |
| `create-possession` | Record something owned in a vault, of one possession type, optionally at one location, with what it would cost to replace and its tags. | Record an object somebody owns in a vault, of one possession type, optionally at one location, with what it would cost to replace and its tags. |

The rest of each description is kept as it stands.
