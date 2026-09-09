# Research: Magic link sign in behind one instance setting

## Where the gate lives

**Decision**: a route middleware, `App\Http\Middleware\EnsureMagicLinkIsEnabled`, aliased `magic.link`
in `bootstrap/app.php`, wrapped as a group around the three magic link routes inside the `guest` group
of `routes/auth.php`. It aborts with 404 while `config('monica.magic_link_enabled')` is false.

**Rationale**: the routes stay registered, which three things need. `MagicLinkCreated::toMail()`
builds a signed URL to `auth.magicLink.show` from the queue, and a notification queued before the
setting flips must not throw. `login.blade.php` calls `route('auth.magicLink.new')`, which throws on a
missing route. And a test can flip the setting with `config()->set()` before a request, as
`ScreenshotLinkControllerTest::it_is_refused_while_the_setting_is_off` does, where routes registered
at boot cannot be flipped without rebuilding the application. A route cache is unaffected for the
same reason. Group middleware is merged ahead of route middleware, so `magic.link` runs before
`signed` and an off instance answers 404 whether or not a signature holds. `throttle` runs first by
framework priority, which changes nothing about the answer.

**Alternatives considered**: registering the routes inside `if (config(...))` in `routes/auth.php`,
rejected for the three reasons above. `abort_unless(config(...), 404)` at the top of the three
controller methods, the screenshot link's pattern, rejected because it is three copies of one line
and a magic link route added later escapes it.

## The setting

**Decision**: `'magic_link_enabled' => (bool) env('MONICA_MAGIC_LINK_ENABLED', true)` in
`config/monica.php`, beside `magic_link_duration_minutes`, with the three lines of comment the config
skill asks for, and `MONICA_MAGIC_LINK_ENABLED=true` documented in `.env.example`.

**Rationale**: it follows the flat key already there and the `(bool) env()` shape of
`screenshot_link.enabled`. `env()` turns `true` and `false` into booleans; `0` and an empty value cast
to false, and any other value casts to true, which is what FR-007 asks for a value that reads as
neither. `.env.example` says so in one line.

**Alternatives considered**: nesting both keys under `magic_link` the way `screenshot_link` is,
rejected because it renames a shipped key and variable for no change in behaviour.

## What goes with the shortcut

**Decision**: `composer remove spatie/laravel-login-link`, delete `config/login-link.php`, remove the
`@env` block from `login.blade.php`, remove `exampleAccountEmail()` and `exampleAccountName()` from
`LoginViewModel` with their two tests, and delete `LoginLinkTest.php`. The seeder stays.

**Rationale**: `LoginLinkServiceProvider::bootingPackage()` registers `POST laravel-login-link-login`
whatever the config says, so the route exists as long as the package does. `spatie/laravel-package-tools`
is required by nothing else in `composer.lock` and leaves with it. The worktree has no `vendor/`, so
`composer install` comes first.

**Alternatives considered**: `dont-discover` in `composer.json`, rejected as dead code kept on purpose.
A middleware or a config value that refuses the package's controller, rejected for the same reason.

## Testing the gate

**Decision**: feature tests only, in the files that already cover the routes and the screen.
`MagicLinkControllerTest` gains one test per route answering 404 while off with
`Notification::assertNothingSent()`, and one proving a link created while on is not spent by a
request made while off (`used_at` stays null, the visitor stays a guest). `LoginControllerTest`
gains one test that the offer is absent while off, and one that `Sign in as` is gone and
`POST laravel-login-link-login` answers 404. `LoginViewModelTest` gains one test for
`magicLinkIsEnabled()`. Default on is proven by the existing tests, which set nothing.

**Rationale**: no middleware in this codebase has a unit test; each is tested through its routes, the
way `AdminAccessTest` tests `administrator`.

## Documentation that follows

**Decision**: `.claude/skills/middlewares/SKILL.md` and `.claude/CLAUDE.md:71` both say the
application has three middlewares. Both say four, naming `magic.link`. The same sentence lists
`set.locale` as an alias, but `bootstrap/app.php` appends `SetLocale` to the web group and aliases
only `active` and `administrator`. Corrected in passing, since the sentence is being edited anyway.
