## General

- Do not tell me I am right all the time. Be critical. We're equals. Try to be neutral and objective.
- Do not excessively use emojis.
- Remove all mannered prose.
- Add quiet flags to noisy commands

## Which rules win

When two sources disagree, follow this order.

1. What I ask you in the conversation.
2. The code that is already there, checked in the files sitting next to the one you are editing.
3. This file, then `.claude/rules/`, then the project skills in `.claude/skills/`.
4. The `laravel-best-practices` skill and the Laravel Boost guidelines at the bottom of this file.
   They are defaults for a question the project has never answered, and for a real correctness or
   security defect. When you follow one of them against a project rule, say so.
5. Framework defaults.

## Coding instructions

- Write code as simply as possible - do not over-engineer so anyone can understand it.
- Do not extract a private method just because a few lines are repeated twice. If the repeated code is short and not business critical, repeat it inline rather than naming and hiding it behind a helper. Extract only when the logic is non-trivial, reused in several places, or its own concept worth naming.
- Always follow the Laravel best practices and how we structure our codebase.
- If you are unsure about a specific implementation, ask for clarification before proceeding.
- When you write tests, read the testing rules in `rules/testing.md` and follow them.
- When writing code, follow the coding standards in `rules/code-style.md`.
- ALWAYS warn users before making a destructive action in the UI.

## Naming

Every name you choose—for files, scripts, directories, functions, variables,
classes, commits, branches, and anything else—must use precise, professional
vocabulary.

Use terminology that would be appropriate in a formal engineering
specification. Prefer literal, unambiguous names over slang, casual shorthand,
cute or clever names, metaphors, or terminology borrowed from chat culture.

Naming should also follow the principles of ASD-STE100 Simplified Technical
English where applicable:

- Use one word for one meaning.
- Prefer approved, common, concrete terminology.
- Avoid synonyms when an established term already exists.
- Avoid ambiguous words and expressions.
- Avoid unnecessary abbreviations and contractions.
- Use verbs that describe the actual operation being performed.
- Use nouns that identify the actual object or concept.
- Keep terminology consistent throughout the codebase.

This applies universally, not only to the example that follows.

A script that deploys dashboards is `deploy_dashboards.sh`, not
`push_dashboards.sh`. `Deploy` describes the operation precisely; `push` is
informal and can have several meanings.

Treat this example as an illustration of the principle, not as the extent of
the rule.

## Tech Stack & Architecture

- Backend: PHP 8.4+ / latest version of Laravel
- Frontend: Blade / Tailwind CSS / Alpine Ajax / Alpine.js

## Application structure

- `app/Actions`: one class per user action, holding the business logic. Controllers stay thin and delegate here. Most of the app lives in this folder.
- `app/Models`: Eloquent models.
- `app/Http/Controllers`: everything under `App` (the logged in app), grouped by domain (`Vaults`, `Settings`, `Marketplace`, `Admin`, `Auth`), plus the base `Controller.php`.
- `app/Http/Middleware`: route middleware. Six classes, four of them aliased in
  `bootstrap/app.php`: `active`, `administrator`, `magic.link` and `sso`. `SetLocale` is appended to the web
  group and `SetApiLocale` prepended to the api group instead. An account carries no role: a record
  is reached when its account matches, which `resolveRouteBinding()` enforces, and when the vault in
  the URL matches, which the controller enforces with `abort_unless(..., 404)`. The one capacity is
  instance wide, the administrator, held through the `administrators` table and guarding the `admin`
  route group.
- `app/Http/Requests`: form requests. There is one, `SaveFieldValuesRequest`; validation is inline
  everywhere else.
- `app/Contracts`: the interfaces the application defines. `app/Rules`: validation rules.
- `app/Jobs`: queued jobs. `app/Enums`: enums. `app/Helpers`: helpers.
- `app/Notifications`: every email the application sends, one class per email, sent with
  `$user->notify()`. There is no `app/Mail` and no mailable. `app/Notifications/Messages/MailMessage`
  is the message they all build: it adds the panel and picks the mailer. An email that belongs in the
  account's journal says so by implementing `App\Contracts\HasEmailType`, and
  `App\Listeners\RecordEmailSent` writes the row once it has gone out.
- `app/ViewModels`: assemble what one screen shows. They query the database, they hold no business
  logic, they may hold presentation logic, and they end up in Blade and nowhere else. One per screen,
  grouped like the controllers (`Vaults`, `Settings`, `Marketplace`, `Admin`, `Auth`). A rich page
  keeps one main view model and delegates its blocks to small objects next to it
  (`MarketplaceStatistics`).
- `app/View/Components`: the five layout classes (`Shell`, `AccountLayout`, `AdminLayout`, `GuestLayout`, `MarketplaceLayout`). The 86 components under `resources/views/components` are anonymous, with `@props`.
- `resources/views`: `app` for the logged in screens, `components` for shared UI, `layouts` and `partials` for the shell, `shared` for what a private link opens, `vendor` for the views a package publishes, which is where the email templates live.
- `resources/css` and `resources/js`: the Tailwind theme and the Alpine setup.
- `routes`: `web.php` (logged in), `api.php` (the JSON API), `auth.php`, `console.php`.
- `database`: `migrations`, `factories`, `seeders`.
- `lang`: one JSON file per locale.
- `tests`: `Unit` (models, actions, jobs, mails, helpers, view models, enums), `Feature` (controllers and views), `Fixtures` (the classes a test needs). PHPUnit only.

> **TODO (rules, corrected 2026-09-08)**: the API surface those two skills describe exists now. There
> is an `Api` controller namespace, a `routes/api.php`, an `app/Http/Resources` and a docs portal
> under `resources/docs/api`, so `api-writer` and `api-docs-writer` apply as written. What is left is
> their worked example: both use `collections`, and there is no such model, controller or resource
> here, so an agent following one literally still looks for files that are not there. Point them at a
> resource that exists, `contacts` or `entity-types`.

> **TODO (rules, 2026-09-08)**: this section describes the repository tree a second time, which the
> constitution's Governance forbids: it says this file must point at `.specify/memory/constitution.md`
> rather than repeat it, because two descriptions of one tree is how the first one went stale. That is
> follow-up 8 in the constitution's own list. Keep the operational notes below, drop the tree.

## Guidelines for git and Github

- You MUST create a new branch when doing a new task, unless stated otherwise, based off of main branch. Make sure main is always up-to-date.
- Branche names MUST be of the format YYYY-MM-DD-{name}.
- You MUST follow conventional commits for commit messages.
- NEVER mention Claude Code in PR descriptions, PR comments, or issue comments.

## Guidelines for writing a commit message

- You MUST provide a simple sentence when submitting a PR. Do NOT write `feat: allow deleting the last vault of an account` - instead, write `feat: delete last vault in account`.

DO NOT
```
feat: draw the top bar controls so they read against the bar
```

DO
```
feat: better header buttons
```

## Writing something

Never use dashes (— or -) as punctuation in documentation or README files. Rephrase sentences using periods, commas, or parentheses instead.

## Checklist for each feature or change

- You MUST run `php artisan monica:localize` and translate what it reports as missing, in every configured locale. Never edit `lang/*.json` keys by hand.
- You MUST make sure what you wrote is covered by a test

# Laravel Boost Guidelines

NOTE: The following guidelines are auto updated regularly. Do not edit this section manually.
When the instructions mention `.ai/rules`, replace them with `.claude/rules` in the following section.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `bun run build`, `bun run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Test every code change by adding or updating a test.
- Run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `bun run build` or ask the user to run `bun run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

# Laravel best practices

**IMPORTANT: This project overrides parts of the skill `.claude/skills/laravel-best-practices/SKILL.md`.**
Read `.claude/rules/code-style.md`, `.claude/rules/testing.md` and the project skills first.

| laravel-best-practices | The project does |
| --- | --- |
| Actions expose `handle(array $data)` and take services in the constructor | Constructor takes the promoted `readonly` inputs, one public `execute()`. 135 actions, no `handle()` |
| `Route::resource()` and `apiResource()` | One named route per line. Zero `Route::resource` |
| Route names in `snake_case` with dots | camelCase segments: `contacts.shareLinks.create` |
| Form Request classes for validation | Inline `$request->validate()`. One Form Request in the application |
| `constrained()`, reversible `down()`, never touch a shipped migration | No `constrained()`, `up()` only, existing migrations edited until production |
| `LazilyRefreshDatabase`, `assertModelExists()` | `RefreshDatabase` (297 files), `assertDatabaseHas()` for values |
| Blade components over `@include`, view composers, `compact()` | `@include` with every variable named, view models, zero `compact()` |
| `Str`, `Arr` and shorter syntax such as `$request->name` | Fine, except `Model::query()` is always spelled out, and request input is read with `$request->string(...)` |
