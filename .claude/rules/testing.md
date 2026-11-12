# Testing rules

## Core philosophy

- Test a rule once, in the layer that owns it. A rule tested again one layer up is a second copy to
  keep in step, and it proves nothing the first test did not.

  | Layer | Owns |
  | --- | --- |
  | Action | Business rules, refusals, the account check, and what it writes to the journal |
  | View model | What a screen shows: grouping, ordering, labels, empty states, what is left out |
  | Controller | The HTTP around the action: the route answers, validation written inline in the controller, the response shape, the wrong vault |
  | `tests/Feature/RouteProtectionTest.php` | That every route asks who is signed in, and that every bound model is looked up inside the account |

- Tests should cover the main functionality and important edge cases, not every single line of code.
- A class with nothing of its own to prove gets no test file. Not every model or controller has one.
- Use the popular tv show The Office (US version) when you need data to prove a point.
- We use PHPUnit for testing. There is no Pest test and no browser test in the suite.

## Conventions (all tests)

- Start every test file with `declare(strict_types=1);`.
- Mirror the app namespace under `Tests\Unit\` (e.g. `App\Actions` → `Tests\Unit\Actions`).
- Mark test methods with the `#[Test]` attribute instead of a `test` prefix.
- Name test methods in snake_case describing the behavior, usually starting with `it_`.
- Use named arguments when instantiating classes and calling actions.
- Add `use RefreshDatabase;` only when the test touches the database.
- Build models with `factory()->create()` for persisted records and `factory()->make()` for in-memory ones.
- Build a user for an account that already exists with `assignUserToAccount(user: $user, account: $account)`
  from `TestCase`. An account holds exactly one user, so the account has to be one nobody is in yet.

## Model tests

- Do not test a plain relationship or a cast. Larastan reads the generic on every relationship, and
  every test that loads one exercises it. Test a relationship only when it carries a condition of its
  own (a filter, an ordering, a pivot column).
- Test accessors, scopes, computed attributes and model events, including their fallback/null cases.

## Action tests

- Fake the queue with `Queue::fake()` and assert dispatched jobs with `Queue::assertPushedOn()`.
- Assert the `LogUserAction` an action dispatches inside the test of what the action does, not in a
  test of its own.
- Assert persisted state with `assertDatabaseHas()`, which names the columns, and the returned type
  with `assertInstanceOf()`. Use `assertModelExists()`, `assertModelMissing()` and
  `assertSoftDeleted()` when only the existence of the row is in question.
- Test authorization failures: expect `ModelNotFoundException` when the record belongs to another
  account. An account carries no role and a vault no membership: a vault belongs to an account, and
  reaching a record means matching `account_id`, then matching the vault the URL names. The one
  capacity in the application is instance wide, the administrator, and it guards the admin space
  rather than anything inside an account.
- Cover important business logic edge cases (e.g. auto-incrementing position).

## Job tests

- Dispatch the job, then assert its side effects on the database.
- Use `assertEqualsWithDelta()` for timestamp assertions to avoid flakiness.

## Notification tests

- Every email is a notification. Build it, call `toMail($user)`, then assert the `subject`, the
  `panel`, the `actionUrl` and the `outroLines` of the message it returns, and cast `render()` to a
  string for the copy that has to reach the reader.
- Assert which kind of email it is with `emailType()`, since that is what the account's journal
  records.
- Fake with `Notification::fake()` and assert with `Notification::assertSentTo()`,
  `Notification::assertNothingSent()` and `$notification->queue` for the queue it went on. A test
  that only fakes the queue sees `SendQueuedNotifications` rather than the notification, so a
  `Queue::assertNothingPushed()` needs `Notification::fake()` next to it.

## Helper tests

- Test pure functions across normal input, edge cases, and null/empty handling.

## Controller tests (Feature)

- Place controller tests under `Tests\Feature\Controllers`, mirroring the `App` controller structure and future `Api` and `Marketing` controllers.
- Authenticate web requests with `actingAs($user)` and make calls with `get()`, `post()`, `put()` and `delete()`.
- Authenticate API requests with `Sanctum::actingAs($user)` and make calls with `$this->json(METHOD, url, payload)`.
- Assert web responses with `assertStatus()`, `assertRedirect()` and `assertSessionHas('status', ...)`.
- Assert API responses with `assertOk()`, `assertCreated()`, `assertNoContent()`, `assertNotFound()` and `assertUnprocessable()`.
- Define a shared `$jsonStructure` property and assert it with `assertJsonStructure()`; assert values with `assertJsonPath()` and `assertJsonCount()`.
- Assert validation failures with `assertUnprocessable()` and `assertJsonValidationErrors()`.
- A controller test proves what the controller does and nothing the action or the view model already
  proves. For each route: it answers, validation written inline in the controller refuses what it
  should, and the response has its shape (the redirect and the flash, or the JSON structure). One
  refusal from the action per controller is enough to prove it reaches the reader as an error on the
  form or as a 422. The other refusals belong to the action test.
- A screen is proven to render with one test asserting the copy the reader needs to recognise it. What
  the screen lists, groups, labels or leaves out is the view model's test.
- Do not write a "visitor is sent to sign in" test per controller. `RouteProtectionTest` checks every
  route; a route that is public is added to its list.
- Test the compartments the controller owns: a record reached through the wrong vault of the same
  account returns not found, which the controller refuses with `abort_unless(..., 404)`. A record of
  another account needs no controller test when the route binds it (a typed model parameter) and its
  model uses `ResolvesModelInAccount` or `ResolvesModelInVault`: those traits are tested once, and
  `RouteProtectionTest` checks every bound model uses one. A controller that takes a `string` and runs
  its own query, which most API controllers do, is scoped by that query, and its test proves it.
- A list endpoint still proves that another account's records are left out, since the query is the
  controller's or the view model's own.
- Assert persisted changes via `refresh()` and deletions via `assertModelMissing()`.
- Fake the queue with `Queue::fake()` when the controller dispatches jobs.
- Make a user an administrator with `Administrator::factory()->create(['user_id' => $user->id])`, and
  test the admin space the way `AdminAccessTest` does: a visitor, a regular user and a deactivated
  administrator all get not found, over every URL of the area through a `#[DataProvider]`.

## View tests (Feature)

A view test asserts what a screen does, never how it looks. How it looks is reviewed by looking at
it, and a test that describes the look fails on the next visual change while the screen is still
right.

- You MUST NOT assert a class string, a bare tag, SVG path data, a CSS variable, a breakpoint or any
  other property of the drawing. There is no exception for a class that "carries the meaning": a
  tint, a size, a shadow, a gutter and a stacking rule are all the look.
- A component with nothing to assert but its look gets no test file. Not every component has one.
- What a view test MAY assert: the copy the reader sees, a route or a form `action`, a field `name`
  or `value`, the accessibility contract (`aria-*`, `role`, `for`/`id`), the Alpine hooks that bind
  the markup to a JavaScript component (`x-data="someComponent("`, `x-target`, `x-merge`), whether a
  whole block is present at all, and which element a component resolves to when that changes what it
  does (`<a>` against `<button>`, `<img>` against a link).
- Blade components that have such a contract are tested one file per component, under
  `tests/Feature/Views/Components`, and the shell under `tests/Feature/Views/Layouts`. Render with
  `$this->blade('<x-badge tone="danger">Bounced</x-badge>')`.
- Put a named slot **after** the default content in the string you pass to `$this->blade()`, the way
  `ButtonSplitTest` does. A slot written first compiles wrongly there and leaks
  `@endslot` into the output, which a `.blade.php` file does not do, so the test fails on a component
  that works.
- Prefer comparing two renderings to naming what is inside one. `IconTest` asserts that an unknown
  glyph name renders the same as the default one, and so says what the component does without naming
  a single path.
