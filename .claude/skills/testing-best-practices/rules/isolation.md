# Fakes, mocks, and determinism

Tests that depend on actual time, randomness, sleeping, or network calls can fail for reasons unrelated to the code under test. Control all four.

## How to isolate a dependency

Fetch `https://laravel.com/framework/docs/mocking` for Laravel's fakes, facade doubles, and fake assertions. Confirm each name before using it.

Identify the dependency, then choose the first option that applies. A framework fake preserves the real code path, while a mock replaces the dependency.

1. Always use framework fakes for facades such as events, queues, mail, notifications, storage, the HTTP client, time, and sleep.
2. Use a developer-defined fake implementation of a service if the application provides one.
3. Use a mock for a container-resolved contract only when the real implementation leaves the process or is nondeterministic.
4. Use the real implementation for everything else, including the database.

## Framework fakes

- Create each fake inside the test method that needs it. Do not create fakes in `setUp()`.
- Pass class names to `Event::fake()` and `Queue::fake()` when you know which classes the code dispatches. A fake without class names can hide an unexpected dispatch.
- Use a fake without class names only when the test asserts the complete result, including a call to `assertNothingPushed()`.
- Write one assertion for each fake. The assertion states that the code dispatches the item, or that the code does not dispatch the item.
- If the data of a job or an event is part of the behavior, assert that data.
- Use `Exceptions::fake()` to assert that the application reports the correct exception. Do not use `withoutExceptionHandling()`, because it changes the response under test.

Create prerequisite factory records before calling `Event::fake()`. Factories use model events, such as a `creating` hook that generates a UUID. A fake without class names suppresses those events, and the factory can then produce an invalid model. Call the fake first only when a factory event is under test, and pass that event's class name.

## Mocking

Use `shouldReceive()` before the action to declare an expectation. Use `shouldHaveReceived()` after the action for a spy. If an equality check cannot state the expected argument, such as a check of one field of a value object, use `Mockery::on()` or `withArgs()`.

Use `$this->mock(Contract::class)` to put a mock in the container. Do not build a PHPUnit mock for a class that Mockery can double, because the project uses Mockery.

## Outbound HTTP testing

Call `Http::preventStrayRequests()`. Any request without a matching fake then fails without reaching the network.

Fake the exact endpoint that each test calls. Do not call `Http::fake()` without an endpoint, because a fake without an endpoint accepts unexpected requests and can hide defects.

## Time and randomness

- Freeze the time or move the time in each test that depends on a date, a period, or a timestamp.
- Use the framework helpers `$this->freezeTime()`, `$this->travelTo()`, `$this->travel()`, and `$this->travelBack()`. Do not call `Carbon::setTestNow()`.
- If the test asserts an identifier or a slug, use `Str::createRandomStringsUsing()` to fix the generated string.
- Use `Sleep::fake()` instead of a real sleep, and assert the sleeps that the code requests.
- If the suite does not restore the time and the randomness for every test, restore them after each test.

## Database

- Run real queries against the real records in the test database. Do not mock the query builder, because the test then asserts the mock.
- If the shape of the serialized model is a contract, assert the exact keys of `toArray()`. The test then fails when the model exposes a new attribute.
- Test application behavior caused by the schema, such as deleting dependent records through a cascade. Do not test the database engine's cascade implementation.
- Use `LazilyRefreshDatabase` instead of `RefreshDatabase`. A test that does not use the database then does not run the migrations.
