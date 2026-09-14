# Code Style

## Core Philosophy

- Laravel provides the most value when you write things the way Laravel intended you to write.
- Follow documented Laravel approaches; justify any deviations.

## Where the rest lives

This file is the PHP and framework style. The structure of the application is in the skills:
[actions](../skills/actions/SKILL.md), [controllers](../skills/controllers/SKILL.md),
[models](../skills/models/SKILL.md), [view models](../skills/view-models/SKILL.md),
[views](../skills/views/SKILL.md), [routes](../skills/routes/SKILL.md),
[migrations](../skills/migrations/SKILL.md), [middlewares](../skills/middlewares/SKILL.md).
Policies have no skill; their rules are in [policies.md](policies.md).
Tests have their own rules in [testing.md](testing.md).

When a rule here disagrees with the `laravel-best-practices` skill or with the Laravel Boost
guidelines, this file wins. The precedence order is in `CLAUDE.md`.

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
- Keep the docblock on every relationship, accessor and factory trait: the generic type
  (`@return BelongsTo<Vault, $this>`, `@return Attribute<string, never>`, `@use HasFactory<ContactFactory>`)
  cannot be written in the signature, and static analysis reads it.
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

The default is no comment. A comment earns its place only where something is complex or needs an
explanation the code cannot give, and a class or a method that says what it does through its name and
its signature gets none. Five exceptions, and nothing else.

- **Docblocks that carry a type.** `@param`, `@return`, `@var`, `@template`, `@throws`, `@mixin`,
  `@use`: static analysis reads them and they stay. Write the tags and no prose above them. A
  docblock left with one tag goes on one line.
- **`app/Models`.** Its docblocks are the model's documentation and are written in full.
- **`app/Actions`.** One class docblock of a sentence or two saying what the action does. Its private
  methods get none.
- **`app/Jobs`.** The same: one class docblock of a sentence or two, saying what the job does and,
  where it is not obvious, why the work is on a queue at all or what its cadence has to guarantee.

Blade is the fifth. Every template opens with one `{{-- --}}` comment of a single short line saying
what the view is, on the very first line of the file and under 110 characters including the markers.
Below it, and only below it, comes the `@var` block: one `@var` per prop for a component, the view
model for a screen. Nothing else. A second sentence belongs in neither, and a template with props but
nothing to say about itself is still missing its first line.

```blade
{{-- Where somebody lands after signing in: the compartments their account is divided into. --}}
{{-- @var \App\ViewModels\Vaults\VaultsViewModel $viewModel --}}
```

```blade
{{-- A short word about the state or the kind of something. --}}
{{--
  @var string $tone
  @var string|null $icon
--}}
```

- Minimize comments by writing expressive code. Adding a comment should never be the first tactic to make code readable.
- Comments often become outdated and mislead over time, so be critical about adding them.
- Only explain *why* something non-obvious is done, never *what* the code does.
- Prefer a descriptive variable name over a generic name plus a comment.
- Format single-line comments with a space before the text.
- Multi-line comments use `/*` with a single `*` on the first line.
- Refactor comments into named functions when possible.
- Never add comments to tests; the test names should be descriptive enough.

## Attributes

- You MUST add the `#[Override]` attribute when overriding parent methods.
- Use the `#[Fillable]` attribute to define mass-assignable fields.
- Use the `#[RouteKey]` attribute to define the route key for the model.

## Test Classes

- A test file MUST contain the test class and nothing else.
- Every class a test needs (a stub, a fake, a notification standing in for a real one) MUST live in its own file under `tests/Fixtures`, in a namespace that mirrors the application (e.g. `Tests\Fixtures\Notifications\SomethingHappened`), whether or not it is used by a single test. The folder is empty today.
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
- Reference the controller with the class array notation, `[HomeController::class, 'index']`. Every route in `routes/web.php` does.
- Route names use camelCase.
- Place the HTTP verb first, followed by other options.
- Route parameters use camelCase.
- Don't start routes with `/` except for the root path `/`.
- Unless you have a good reason, do not use query strings.

## API Routing

- Resource names use the plural form in kebab-case.
- Limit deep nesting; use nesting only when it provides necessary context.

## Controllers

- Resource controllers use the singular resource name: `ContactController`, `VaultController`, `TemplateController`.
- Stick to default keywords (`index`, `new`, `create`, `show`, `edit`, `update`, `destroy`).
- Extract separate controllers for additional actions.

## Views

- View files use kebab-case.

## Migrations

- Write only the `up()` method; you SHALL never write a `down()` method.
- Never use `constrained()`. Declare the column, then declare the foreign key at the bottom of the
  migration. The [migrations skill](../skills/migrations/SKILL.md) has the rest, including the file
  naming that orders them and the rule about editing an existing migration.

## Validation

- Use array notation for multiple rules instead of pipe-separated strings.
- Custom validation rules use snake_case.

## Blade Templates

- Use two spaces for indentation.
- Don't add spaces after control structure keywords.

## Authorization

Policies have their own rules in [policies.md](policies.md): when an ability earns one, how to ask it,
what the refusal carries, and what stays out of it. Naming lives there too.

## Translations

- Use the `__()` function instead of `@lang` in Blade.

### Phrases that show a number

Three rules, and `php artisan monica:localize --check` fails on a breach of any of them.

- A phrase containing `:count` MUST be asked for with `trans_choice()`, never `__()`, `trans()` or
  `@lang`. Those cannot vary a wording by number. `trans_choice()` supplies `count` itself, so only
  the other placeholders are passed.

  ```php
  trans_choice(':count person|:count people', $count)
  trans_choice(':count value written in :label|:count values written in :label', $count, ['label' => $label])
  ```

- Every segment MUST interpolate `:count`. Never write the digit. French groups zero with the
  singular, so the singular segment is what a French reader sees at a count of zero, and
  `1 personne|:count personnes` renders "1 personne" there. `:count personne` renders "0 personne".
  English is unaffected, since `:count person` at one renders "1 person" either way.

- A phrase whose wording does not change with its number MUST say so with an explicit `[0,*]`, and
  still go through `trans_choice()`, which strips the prefix. `__()` prints it to the reader. Silence
  is not a declaration: a phrase that says nothing is treated as depending on its number.

  ```php
  trans_choice('[0,*]Show :count more', $count)
  ```

  Declare it only after checking. "It reads fine at one in English" is not the test: `1 lines` and
  `1 published something this week` both read wrongly, and a phrase that never inflects in English
  can still inflect elsewhere.

A segment MAY be pinned to exactly one number with `{1}`, and then it needs no `:count`, because the
number is already known. Use it where the copy deliberately spells the number as a word rather than a
digit, which reads better in several places and is what the tests for those screens ask for.

```php
trans_choice('{1}Opened once|Opened :count times', $count)
```

A pinned wording names one thing, so it cannot stand in for none. `{1}` matches one and nothing else,
so at zero the phrase falls back to the plural rules and lands on the pinned wording, which a French
reader then sees for zero. A phrase with a pinned wording MUST therefore keep zero away from it, in the
method that builds the phrase and not in the screen that shows it. The check enforces this.

```php
if ($this->headcount() === 0) {
    return '';
}

return trans_choice('{1}One person in this vault.|All :count people in this vault.', $this->headcount());
```

A wording carrying a condition MUST go through `trans_choice()`. Nothing else takes the condition off,
so `__('{1}Just one thing')` prints `{1}Just one thing` to the reader.

A phrase carrying more than one wording MUST go through `trans_choice()`, whether or not it names the
count and whether or not it carries a condition. Nothing else picks between wordings, so
`__('Just one thing|Several things')` prints every wording at once with the pipes still in place. The
check reports the call rather than the phrase, since the call is the mistake.

A pinned wording MUST NOT be written in a Blade file. A guard put there belongs to the screen, and the
phrase has to be safe wherever it is shown, so build it in whatever gives the screen its words and
guard it there. The check refuses a pinned wording in a view outright.

The exemption from `:count` applies only to a wording pinned to a single number. A wording covering
more than one still needs the placeholder: `[0,*]1 gadget` reads "1 gadget" at every number.

A wording MUST NOT be chosen by comparing a count to one. That is English grammar imposed on every
language, and no translator can reach it.

```php
// Wrong
return $count === 1 ? __('1 person') : __(':count people', ['count' => $count]);

// Right
return trans_choice(':count person|:count people', $count);
```

Comparing against **zero** is fine and often right. Copy for an empty state is its own sentence, not
a plural form, and it stays.

```php
if ($vault->contact_count === 0) {
    return __('Nobody in it yet');
}

return trans_choice(':count person|:count people', $vault->contact_count);
```

How many segments a locale needs is a property of its language, worked out from the framework rather
than written down. Most shipped locales ask for two. Turkish, Japanese, Korean and Chinese ask for
one, selecting the same wording at every number, and Polish, Lithuanian and Irish ask for three.
Supply what the locale asks for and no more. `tests/Unit/Translation/CountedPhraseTest.php` records the
count for every shipped locale, and a locale missing from that table fails the suite.

## Naming Classes

- Controllers: singular resource name + `Controller` suffix; non-resourceful/invokable controllers use the action name + `Controller`.
- Resources/Transformers: plural form + `Resource` or `Transformer` suffix.
- Jobs: describe the action performed.
- Events: use tense to indicate timing (before vs. after).
- Listeners: action name + `Listener` suffix.
- Commands: add a `Command` suffix to avoid collisions.
- Mailables: add a `Mail` suffix.
- Enums: singular, with the `Enum` suffix: `GenderEnum`, `UserActionEnum`, `FieldTypeEnum`. Every
  enum in `app/Enums` is named that way except the older `EmailType`.
