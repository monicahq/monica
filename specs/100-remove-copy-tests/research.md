# Research: Remove Copy Tests

## R1. What counts as copy

- **Decision**: an assertion argument is copy when a string literal in it is a multi-word segment of
  the catalogue (`lang/fr_FR.json` keys split on `|` with `{n}`/`[a,b]` prefixes stripped, plus the
  values of `lang/en/*.php`), contains such a segment, matches a segment with its `:placeholders`
  as wildcards, or when the argument calls `__()`, `trans()` or `trans_choice()`. A single word is
  copy only after reading the test: it is copy when the application wrote it (a seeded label, a
  heading), data when the test created it (a factory value, a request payload).
- **Rationale**: the catalogue is the complete list of what the application can say, because
  `monica:localize --check` refuses a string missing from it. Matching against it separates copy from
  fixture data far better than a prose-shape heuristic, which flagged "Beet harvest".
- **Alternatives considered**: a prose heuristic (1,968 methods, many fixture sentences); counting
  single words automatically (2,290 methods, "Neighbourhood" and "Animal" are fixture data).

## R2. Removing an assertion

- **Decision**: a statement that is one assertion (`$this->assertSame('copy', $x);`) is deleted. A
  copy link in a chain (`->assertOk()->assertSee('copy')`) is deleted and the chain kept. A chain
  left with no assertion keeps its call, and the method is judged by R4.
- **Rationale**: the other links of a chain are behaviour (`assertOk`, `assertRedirect`).

## R3. Refusals keep their proof without their sentence

| Before | After |
| --- | --- |
| `assertSessionHas('status', 'copy')` | `assertSessionHas('status')` |
| `assertSessionHasErrors(['field' => 'copy'])` | `assertSessionHasErrors(['field'])` |
| `assertSessionHasErrors('copy')` without key | `assertSessionHasErrors()` |
| `assertJsonValidationErrors(['field' => 'copy'])` | `assertJsonValidationErrors(['field'])` |
| MCP `assertHasErrors(['copy'])` | `assertHasErrors()` |
| `expectExceptionMessage('copy')` | deleted, `expectException(...)` stays |
| `assertJsonPath('message', 'copy')` | deleted |
| policy `assertSame('copy', $response->message())` | deleted, the `denied()`/`allowed()` assertion stays |

- **Rationale**: the key or the fact of the error is behaviour, the sentence is copy. A test named
  `it_refuses_...` still proves the refusal (US2 scenario 2).
- **Known loss**: where two refusals of one action share a key and only the sentence told them apart,
  the test now proves "refused" and not which refusal. Accepted by the decision to remove copy.
- **Alternatives considered**: deleting refusal tests outright, rejected by the clarification.

## R4. A method left with nothing

- **Decision**: a method with no assertion and no `expectException` left is deleted, with its
  `#[Test]` and attributes. A data provider with no user left is deleted. A file with no method left
  is deleted, and a class in `tests/Fixtures` with no reference left is deleted with it.
- **Arrangement**: a local variable that only fed a deleted assertion is deleted when its right side
  has no side effect (a view model, a rendered string, a `fresh()` read). A factory call stays, since
  rows it creates may matter to what remains.

## R5. Hand-read cases

- Lists mixing copy with data (`assertSeeInOrder`, `assertSame([...])`): the copy items are removed
  and the order or set of data items is kept when two or more remain.
- Mixed assertions on a whole structure (`assertSame([['label' => 'Employer', 'value' => ...]])`): the
  copy keys are dropped from the expectation, or the assertion is rewritten on the data part.
- Helpers taking a sentence (`StructureFileValuesTest::assertRefused(string $sentence, ...)`): the
  sentence parameter goes.
- Copy reaching an assertion through a data provider, a constant or a variable: found by searching
  providers and constants for catalogue strings, then handled by the rules above.
- The 885 single-word methods.
- Work split: the script handles R2 to R4. Hand-read cases are split by folder across Sonnet
  subagents with these rules, each checked by a diff review before acceptance.

## R6. Proving nothing behavioural was lost

- **Decision**: count non-copy assertions per file before and after. A file whose count drops by more
  than the weakened refusals and the deleted copy-only methods is reviewed.
