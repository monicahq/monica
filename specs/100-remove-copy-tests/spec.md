# Feature Specification: Remove Copy Tests

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-10-27 | **Status**: Draft

**Input**: "audit entire codebase. tell me how many tests check actual copy/wording." then "remove all
those tests. same branch."

An audit on 2026-10-26 found 1,405 of 8,189 test methods (17.2%, in 485 files) asserting a multi-word
string from the application's copy catalogue. 397 of them assert nothing but copy. The other 1,008
mix copy with assertions about behaviour. A further 885 methods match only a single-word label, which
cannot be told apart from fixture data by matching alone.

## Clarifications

### Session 2026-10-27
- Q: A method mixing copy and behaviour? → A: Strip the copy assertions, keep the method with what
  remains. A method left with no assertion is deleted.
- Q: Change the testing rules and the constitution? → A: No. They stay as they are.
- Q: Exempt the tests of the translation machinery? → A: No. Their wording assertions go too.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Change a wording without touching a test (Priority: P1)

Pam rewrites "The order is saved." as "Order saved." in one controller. Today a test fails and has to
be edited to match. After this change, she edits the string, runs `monica:localize`, and the suite
stays green.

**Why this priority**: it is the reason for the change. A test that restates the copy proves nothing
the copy itself does not, and it costs an edit on every rewording.

**Independent Test**: change one shipped wording in each layer (a flash message, a view model label,
a validation refusal, a notification subject, a rendered heading) and run the suite.

**Acceptance Scenarios**:

1. **Given** a rewording of any string in the catalogue, **When** the suite runs, **Then** no test
   fails because of the new wording.
2. **Given** the suite after the change, **When** it is searched for an assertion whose expected
   value is a catalogue string or a `__()`/`trans_choice()` call, **Then** none is found outside the
   exemptions in FR-005.

---

### User Story 2 - Keep the behaviour the removed tests also proved (Priority: P1)

Dwight deletes a location that still holds possessions. The refusal used to be proven by matching its
sentence. After this change the refusal is still proven, by what the test can observe without the
sentence: the request is refused, the record stays, the error is on the right field.

**Why this priority**: removing a copy assertion must not remove the only proof that a rule holds.

**Independent Test**: for every edited test file, the behavioural assertions present before the
change are present after it, unless they were themselves copy.

**Acceptance Scenarios**:

1. **Given** a test method that mixes copy and behaviour, **When** it is processed, **Then** its copy
   assertions are deleted and its other assertions stay. A method left asserting nothing is deleted.
2. **Given** a refusal whose only proof was its wording, **When** the wording assertion goes,
   **Then** the test still asserts that the refusal happened (the status, the error key, the record
   unchanged).

---

### Edge Cases

- A literal that is both fixture data and a catalogue word ("Family", "Warranty", "Neighbourhood").
- A matcher false positive: an Office fixture name or a `value="Dr Meyer"` attribute.
- Copy embedded in an accessibility attribute (`aria-label="Delete this note"`).
- A test whose only assertion is copy and whose name promises behaviour ("it_refuses_...").
- A file left with no test method once its methods are removed.
- A fixture class under `tests/Fixtures` used only by removed tests.
- Copy asserted through a constant, a variable or a data provider, which the matcher misses.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every test method whose assertions are all copy MUST be deleted.
- **FR-002**: In a test method that mixes copy and behaviour, the copy assertions MUST be deleted and
  the others kept. Arrangement and variables used only by a deleted assertion MUST go with it.
- **FR-003**: An assertion counts as copy when its expected value is a multi-word string of the copy
  catalogue (matched with placeholders), or a `__()`/`trans_choice()` call, including copy inside an
  accessibility attribute. Single-word matches are copy only when the word is not fixture data in
  that test.
- **FR-004**: Matcher false positives (fixture names, user input, values typed in the test) MUST NOT
  be removed.
- **FR-005**: No layer is exempt, the tests of the translation machinery included. An assertion that
  a locale was served or a plural form picked survives only where it can be proven without a
  wording (the locale code, the number of segments).
- **FR-006**: A test file left with no method MUST be deleted, and a fixture left with no user MUST be
  deleted with it.
- **FR-007**: The coverage guards (`PolicyCoverageTest`, `RouteProtectionTest`) MUST still pass. A
  policy ability whose only test was removed MUST keep a test proving its three answers.
- **FR-008**: `.claude/rules/testing.md` and the constitution MUST NOT change.
- **FR-009**: The full suite, PHPStan and Pint MUST pass after the change.
- **FR-010**: The work MUST land on the current branch, `2026-10-25-location-management`.

## Success Criteria *(mandatory)*

- **SC-001**: Zero copy assertions remain, by a rerun of the audit matcher.
- **SC-002**: Rewording five shipped strings, one per layer, fails zero tests.
- **SC-003**: The suite passes, and its run time does not increase.
- **SC-004**: No behavioural assertion is lost, checked by comparing non-copy assertion counts per
  file before and after.

## Assumptions

- "Those tests" means the 1,405 methods with a multi-word catalogue match, plus the single-word
  matches that turn out on reading to be copy.
- Translation correctness stays guarded by `php artisan monica:localize --check`, which reads every
  locale file and needs no test restating a string.
- The user's instruction overrides `.claude/rules/testing.md` where they disagree (CLAUDE.md
  precedence, item 1). The rule still asks a screen test and a notification test to assert copy, so
  new tests written after this change will add copy assertions again. That is accepted.
