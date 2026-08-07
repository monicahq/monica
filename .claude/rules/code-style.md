# Code Style

## Core Philosophy

- Laravel provides the most value when you write things the way Laravel intended you to write.
- Follow documented Laravel approaches; justify any deviations.

## General PHP

- You MUST declare one class, interface, trait or enum per file. This has no exception, and applies to tests exactly as it applies to the rest of the application.
- Follow PSR-1, PSR-2, and PSR-12 standards.
- Use camelCase for non-public-facing string-like elements.
- Avoid using the `final` keyword; assume users write tests for overridden behavior.
- Prefer short nullable notation (`?string`) over union types with null.
- Use the `void` return type when a method returns nothing.
- Class names use PascalCase; methods and variables use camelCase.
- Prefer string interpolation over concatenation.

## Typed Properties & Type Hints

- Always type properties when possible.
- Use actual type declarations instead of docblock `@var` annotations.
- Always specify return types, including `void`.

## Enums

- Enum case names use PascalCase.

## Docblocks

- Omit docblocks for fully type-hinted methods unless a description adds context.
- Descriptions must use complete sentences with periods.
- Import class names in docblocks rather than using fully qualified names.
- Use single-line format when possible.
- For multiple types, list the most common type first.
- If one parameter requires documentation, add docblocks for all parameters and the return value.
- For iterables, specify key and value types using angle brackets.
- For arrays with fixed keys, use `{}` notation.

## Constructor Property Promotion

- Use promotion when all properties can be promoted.
- Place each promoted parameter on a separate line with a trailing comma.

## Traits

- Each trait gets its own `use` statement for cleaner diffs.

## Ternary Operators

- Short expressions can fit on one line.
- Longer expressions require each portion on a separate line.

## If Statements

- Always use curly brackets; never omit them.
- Place the unhappy path first with early returns, leaving the happy path last and unindented.
- Avoid `else`; refactor using early returns or ternary operators.
- Prefer separate if statements over compound conditions for easier debugging.

## Comments

- Minimize comments by writing expressive code. Adding a comment should never be the first tactic to make code readable.
- Comments often become outdated and mislead over time, so be critical about adding them.
- Only explain *why* something non-obvious is done, never *what* the code does.
- Prefer a descriptive variable name over a generic name plus a comment.
- Format single-line comments with a space before the text.
- Multi-line comments use `/*` with a single `*` on the first line.
- Refactor comments into named functions when possible.
- Never add comments to tests; the test names should be descriptive enough.

## Test Classes

- A test file MUST contain the test class and nothing else.
- Every class a test needs (a mailable, a stub, a fake) MUST live in its own file under `tests/Fixtures`, in a namespace that mirrors the application (e.g. `Tests\Fixtures\Mail\NewLoginDetected`), whether or not it is used by a single test.
- Use descriptive test method names and follow the arrange, act, assert pattern.

## Whitespace

- Add blank lines between statements to allow breathing room.
- Single-line equivalent operations may be grouped together.
- Don't add empty lines between `{}` brackets.

## Configuration

- Configuration file names use kebab-case.
- Configuration keys use snake_case.
- Avoid the `env()` helper outside config files; create config values from env variables.
- Add service credentials to `config/services.php` rather than creating separate files.

## Artisan Commands

- Command names use kebab-case.
- Always output a confirmation message on successful completion.
- When processing items, output before processing each item, and provide a summary count at the end.

## Routing

- Public-facing URLs use kebab-case.
- Prefer string notation over class array notation.
- Route names use camelCase.
- Place the HTTP verb first, followed by other options.
- Route parameters use camelCase.
- Don't start routes with `/` except for the root path `/`.
- Unless you have a good reason, do not use query strings.

## API Routing

- Resource names use the plural form in kebab-case.
- Limit deep nesting; use nesting only when it provides necessary context.

## Controllers

- Resource controllers use plural resource names.
- Stick to default keywords (`index`, `new`, `create`, `show`, `edit`, `update`, `destroy`).
- Extract separate controllers for additional actions.

## Views

- View files use kebab-case.

## Migrations

- Write only the `up()` method; never write a `down()` method.

## Validation

- Use array notation for multiple rules instead of pipe-separated strings.
- Custom validation rules use snake_case.

## Blade Templates

- Use two spaces for indentation.
- Don't add spaces after control structure keywords.

## Authorization

- Policies use camelCase.
- Use default CRUD words; replace `show` with `view`.

## Translations

- Use the `__()` function instead of `@lang` in Blade.

## Naming Classes

- Controllers: plural resource name + `Controller` suffix; non-resourceful/invokable controllers use the action name + `Controller`.
- Resources/Transformers: plural form + `Resource` or `Transformer` suffix.
- Jobs: describe the action performed.
- Events: use tense to indicate timing (before vs. after).
- Listeners: action name + `Listener` suffix.
- Commands: add a `Command` suffix to avoid collisions.
- Mailables: add a `Mail` suffix.
- Enums: no special prefix; the name should clearly indicate it's an enum.
