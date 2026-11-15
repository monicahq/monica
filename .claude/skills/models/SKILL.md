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
- You MUST declare every constant at the top of the class, right after the traits and before the casts. Never in the middle of the class.
- You MUST NOT set `protected $table`. No model does; the convention gives the right name.
- You MUST define casts in a `protected function casts(): array` method, not a property.
- You MUST cast sensitive string fields to `encrypted`, booleans to `boolean`, integers to `integer`, and dates to `datetime`.
- Every model that belongs to the tenant MUST have the `account()` `BelongsTo` relationship, and a
  model that lives inside a vault MUST have `vault()` too.
- You MUST type-hint every relationship and document it with a generic docblock (e.g. `@return BelongsTo<Vault, $this>`).
- You MUST expose computed values through accessors using `Attribute::make()` with an `@return Attribute<string, never>` docblock.
- For translatable names, you MUST store both `name` and `name_translation_key`, and fall back to the translated key via `__()` when the value is null.
- You MUST add a short docblock to every relationship, accessor and method.
- You MUST group all the relationships together, before any other method of the model.
- You MUST not add any business logic to the model; use Actions for that.
- A model reached through a URL MUST name define a `RouteKey` attribute with the `uuid` value, and  MUST use the
  `ResolvesModelInAccount` or `ResolvesModelInVault` trait, so a record of another account is not found rather than forbidden. 

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

- You MUST NOT use `forceFill()` to write fields declared in `#[Fillable]`. Write them with
  `create([...])` or `fill([...])`.
- `forceFill()` is allowed, as an exception, for a field that is not fillable and has to stay so
  (a timestamp kept from an import, a column a package owns). Name that field and its value
  explicitly, and never pass request data to it.

```
// GOOD
$contact = Contact::query()->create([
    'first_name' => $this->firstName,
    'last_name' => $this->lastName,
]);

// GOOD, by exception: created_at is not fillable
$note->forceFill(['created_at' => $createdAt])->saveQuietly();

// NEVER DO
$member->forceFill(['name' => $this->name]); // name is fillable
$member->forceFill($request->all());
```
