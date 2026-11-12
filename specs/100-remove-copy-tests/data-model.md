# Data Model: Remove Copy Tests

No persisted data. The work operates on three objects of the test suite.

- **Test method**: a `#[Test]` method in `tests/**/*Test.php`. State: kept, stripped (lost copy
  assertions, kept others), deleted (nothing left to assert).
- **Assertion**: one `assert*` or `expectExceptionMessage` call. Class: copy, behaviour, or mixed (a
  list or a structure holding both). Outcome: kept, weakened (R3), deleted, or rewritten by hand (R5).
- **Test file**: deleted when its last method is, together with any fixture class only it used.
