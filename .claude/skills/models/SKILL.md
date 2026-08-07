---
name: models
description: Adds a new model to the Laravel application. Use when the user wants to create a new Eloquent model, including its migration, factory, and seeder. Triggers on model creation, migration generation, and related tasks.
---

# Models

## Migration

- If no migration already exists, you MUST create a migration for the given fields.
- If a migration exists, you MUST read it to understand the database schema changes.
- You MUST comment each field of the migration, except `id` and timestamps.

## Default values

- Many models have default values for some fields, and these values are meant to be translated.
- In those cases, you MUST store both `<field_name>` and `<field_name>_translation_key` in the database, and fall back to the translation key via `__()` when the value is null.

## Model

- You MUST create the model in `app/Models/`, extending the Eloquent `Model` class.
- You MUST add a class-level PHPDoc block listing every column as an `@property`, including `id`, `created_at` and `updated_at`.
- You MUST use `HasFactory` with a `/** @use HasFactory<XFactory> */` docblock above the trait.
- You MUST declare every constant at the top of the class, right after the traits and before `protected $table`. Never in the middle of the class.
- You MUST set the table explicitly with `protected $table`.
- You MUST declare mass-assignable fields in `protected $fillable` typed as `list<string>`.
- You MUST define casts in a `protected function casts(): array` method, not a property.
- You MUST cast sensitive string fields to `encrypted`, booleans to `boolean`, integers to `integer`, and dates to `datetime`.
- Most models belong to a company, so you MUST add the `company()` `BelongsTo` relationship in those cases.
- You MUST type-hint every relationship and document it with a generic docblock (e.g. `@return BelongsTo<Vault, $this>`).
- You MUST expose computed values through accessors using `Attribute::make()` with an `@return Attribute<string, never>` docblock.
- For translatable names, you MUST store both `name` and `name_translation_key`, and fall back to the translated key via `__()` when the value is null.
- You MUST add a short docblock to every relationship, accessor and method.
- You MUST group all the relationships together, before any other method of the model.
- You MUST not add any business logic to the model; use Actions for that.

## Factory

- You MUST add a matching factory in `database/factories/`, extending `Factory` with an `@extends Factory<Model>` docblock.
- You MUST set `protected $model` and return the defaults from `definition(): array`.
- You MUST populate fields with fake data and reference related models via their factories (e.g. `Vault::factory()`).

## Usage

- You MUST use `Model::query()` instead of direct static calls.

```
// GOOD
Member::query()->firstWhere('id', 42);

// BAD
Member::firstWhere('id', 42);
```

- You SHOULD avoid mass assignment:

```
// PREFERRED
$member = new Member();
$member->name = $request->input('name');
$member->email = $request->input('email');

// AVOID
$member->forceFill([
    'name' => $request->input('name'),
    'email' => $request->input('email'),
]);

// NEVER DO
$member->forceFill($request->all());
```

- You MUST eager load relationships to avoid N+1 queries (`$users = User::query()->with(['posts'])->get();`).
- You MUST use chunking for large datasets:
```
User::query()->chunk(100, function (Collection $users) {
    foreach ($users as $user) {
        // Process user
    }
});
```
- You MUST index frequently queried columns.
- You SHOULD use query caching for expensive, frequently run queries.
