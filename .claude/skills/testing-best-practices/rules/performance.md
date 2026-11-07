# Test suite performance

These settings apply to the project and CI, not to individual tests. Read `rules/isolation.md` for choices within a test.

Fetch the PHPUnit 13.4.6 documentation at `https://phpunit.de/documentation.html` for PHPUnit options that make test runs faster.
Verify each flag in the documentation before adding it to CI.

Measure before changing a setting. Find the slow test first, and apply a project-wide setting only after identifying the costly work.

## Test environment

- Set `BCRYPT_ROUNDS=4` in `.env.testing` or in `phpunit.xml`. The default value is 12, and the hash then takes most of the time of each test that signs a user in.
- Disable Xdebug. Unless the run needs coverage, disable pcov too.
- Disable packages that perform work on every request in the test environment. Examples are Pulse, Telescope, and Nightwatch.
- Use the `WithCachedConfig` and `WithCachedRoutes` traits, so the run does not parse the configuration and the routes for every test.
- Call `withoutVite()`, or `withoutMix()`, so the framework does not resolve a built asset.

## Global fakes

Put these three calls in the `setUp()` of the base `TestCase` of the project:

- `Http::preventStrayRequests()`, because one request that reaches the network can slow the suite. It catches requests that go through Laravel's HTTP client. Check direct Guzzle and cURL usage separately.
- `Sleep::fake(syncWithCarbon: true)`, so a retry and a backoff do not sleep.
- `Exceptions::fake()`, so the suite does not report an exception to an external service.

## How to run the suite in parallel

Run `php artisan test --parallel`, which uses ParaTest, to spread tests across the machine's CPU cores. If the default count does not suit the machine or CI, add `--processes=N`.

A parallel run gives each process a separate database. Each test must meet the conditions below. A test that fails only in parallel breaks one of them.

- The test creates each record that it reads. It does not read a record that another test creates.
- The test does not depend on the order of the run.
- The test does not share a file, a cache key, or a queue with another test. Give each process a separate name for such a resource.

## How to find a slow test

Run `php artisan test --profile` to list the slowest tests. Start with the ten slowest tests, because the same cause often applies to the complete suite.

If the cause of a slow test is unclear, add an event listener or temporary log entry to identify the work the test does.

## Common errors

- The run loads Xdebug for a test that does not need it.
- `BCRYPT_ROUNDS` keeps the default value, because the project has no `.env.testing`.
- The code under test calls the real `sleep()`, and `Sleep::fake()` then does not help.
