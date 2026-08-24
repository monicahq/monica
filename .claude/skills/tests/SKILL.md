---
name: tests
description: Conventions for defining tests. Use when the user wants to create or modify tests, including test structure, naming conventions, and best practices.
---

# Tests

- You MUST read and follow the testing rules in `.claude/rules/testing.md`.
- You MUST use `use PHPUnit\Framework\Attributes\Test;` and `#[Test]` for test methods, instead of `public function testX()`.
- Test method names MUST be descriptive and use `snake_case`, like `it_can_list_the_api_keys_of_the_current_user`.
- You MUST not test sanitization (i.e. `TextSanitizer`).
- You MUST not test casts (Model casting).
- You MUST test blade components: one file per component under `tests/Feature/Views/Components`,
  rendered with `$this->blade(...)`. See the view tests section of `.claude/rules/testing.md`.
