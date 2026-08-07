# Testing rules

## Core philosophy

- Every model, controller, action, helper, job, mail, service and view model should have a corresponding test.
- Tests should cover the main functionality and important edge cases, not every single line of code.
- Use the popular tv show The Office (US version) when you need data to prove a point.
- We use PHPunit for testing.

## Conventions (all tests)

- Start every test file with `declare(strict_types=1);`.
- Mirror the app namespace under `Tests\Unit\` (e.g. `App\Actions` → `Tests\Unit\Actions`).
- Mark test methods with the `#[Test]` attribute instead of a `test` prefix.
- Name test methods in snake_case describing the behavior, usually starting with `it_`.
- Use named arguments when instantiating classes and calling actions.
- Add `use RefreshDatabase;` only when the test touches the database.
- Build models with `factory()->create()` for persisted records and `factory()->make()` for in-memory ones.

## Model tests

- Assert relationships exist with `$model->relation()->exists()` (belongs to, has many).
- Test accessors and computed attributes, including their fallback/null cases.

## Action tests

- Fake the queue with `Queue::fake()` and assert dispatched jobs with `Queue::assertPushedOn()`.
- Assert persisted state with `assertDatabaseHas()` and the returned type with `assertInstanceOf()`.
- Test authorization failures: expect `ModelNotFoundException` when the user is not in the vault or lacks the role.
- Cover important business logic edge cases (e.g. auto-incrementing position).

## Job tests

- Dispatch the job, then assert its side effects on the database.
- Use `assertEqualsWithDelta()` for timestamp assertions to avoid flakiness.

## Mail tests

- Assert the envelope subject and that `render()` contains the expected dynamic content.

## Helper tests

- Test pure functions across normal input, edge cases, and null/empty handling.

## Controller tests (Feature)

- Place controller tests under `Tests\Feature\Controllers`, mirroring the `App`, `Api` and `Marketing` controller structure.
- Authenticate web requests with `actingAs($user)` and make calls with `get()`, `post()`, `put()` and `delete()`.
- Authenticate API requests with `Sanctum::actingAs($user)` and make calls with `$this->json(METHOD, url, payload)`.
- Assert web responses with `assertStatus()`, `assertRedirect()` and `assertSessionHas('status', ...)`.
- Assert API responses with `assertOk()`, `assertCreated()`, `assertNoContent()`, `assertNotFound()` and `assertUnprocessable()`.
- Define a shared `$jsonStructure` property and assert it with `assertJsonStructure()`; assert values with `assertJsonPath()` and `assertJsonCount()`.
- Assert validation failures with `assertUnprocessable()` and `assertJsonValidationErrors()`.
- Test vault scoping: requests for another vault's resource return not found (or an empty list).
- Test role restrictions: a non-owner performing an owner-only action gets `assertNotFound()`.
- Assert persisted changes via `refresh()` and deletions via `assertModelMissing()`.
- Fake the queue with `Queue::fake()` when the controller dispatches jobs.
