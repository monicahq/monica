# How to Find Test Framework Features

PHPUnit and Laravel provide features for most testing needs. Find an existing feature before implementing the behavior by hand.

- Give `search-docs` the capability you need rather than the name of a method you remember. It returns Laravel testing documentation for the installed version.
- Fetch `https://phpunit.de/documentation.html` for version-specific PHPUnit attributes, assertions, and command-line options.
- If a search returns no results, tell the user that the installed version does not provide the feature. Do not write an API that you have not confirmed.

Search for a feature in this table before you write the code by hand.

| Work that you need | Term to search for |
| --- | --- |
| Run one test method with many input values | data provider, `#[DataProvider]`, `#[TestWith]` |
| Run one test only after another test passes | `#[Depends]` |
| Select or skip a set of tests in one run | `#[Group]`, `--group`, `--exclude-group` |
| Skip a test on a version or on a missing extension | `#[RequiresPhp]`, `#[RequiresPhpExtension]` |
| Find a test that depends on the order of the run | `--order-by=random` |
| Reduce the time of a slow suite | ParaTest, `--cache-result` |
| Stop the run at the first failure while you debug | `--stop-on-failure`, `--filter` |

## Built-in Laravel Assertion Methods

Laravel provides assertions for each part of the framework. Fetch `https://laravel.com/framework/docs/testing` for the complete list, and search for an assertion before building a check by hand. Examples include `assertDatabaseHas()`, `assertModelExists()`, `assertSoftDeleted()`, response assertions such as `assertRedirectToRoute()` and `assertJsonPath()`, and fake assertions such as `Queue::assertPushed()` and `Notification::assertSentTo()`.

A hand-built check fails with `false is not true`, which identifies nothing. A framework assertion names the incorrect table, value, or response, so the failure indicates what to fix.

```php
// The failure says that false is not true. Instead of this...
$this->assertTrue(User::where('email', 'taylor@laravel.com')->exists());

// Use this... the failure names the table and the attributes that it did not find...
$this->assertDatabaseHas('users', ['email' => 'taylor@laravel.com']);
```
