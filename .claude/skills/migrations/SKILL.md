---
name: migrations
description: Use when creating or editing database migrations in the project.
---

# Migrations

## Rules

- You MUST generate a migration with `php artisan make:migration <name> --no-interaction` rather than writing the file by hand, then rename the generated file to the next free sequential prefix. The generator stamps the clock; this repository sequences by hand, with a suffix between `000000` and `000008`. Renaming is what keeps the two compatible.
- You MUST create one table per migration. When a table needs a foreign key on a table created later, you MUST name the migration file so that it runs first (e.g. `0000_01_01_000000_create_companies_table.php` before `0001_01_01_000000_create_users_table.php`), or declare the whole column in the later migration with `Schema::table()` and `->after('...')`, the way `published_version_id` is added to `sections` by `create_section_versions_table`.
- You MUST comment a field by using `->comment('...')` to explain what the field is for.
- Until we are in production (we have at least until end of 2026 for this), you MUST alter existing migrations instead of creating new ones when adding or changing fields. This is to avoid having too many migrations and to keep the database structure clean. Once we are in production, you MAY create new migrations for changes. A database that predates the change is rebuilt with `migrate:fresh`, never patched: an edited `Schema::create` never reaches a database that already ran it, so no command and no backfill can repair one. Say so wherever you tell somebody to migrate.
- The exception is a column that must reach a database already in use. Three migrations take it, `add_deleted_with_vault_to_contacts_table`, `add_is_identifying_to_fields_table` and `add_match_key_to_contacts_table`, each adding one column to a table whose create migration is left alone. Where existing rows also need a value, a `backfill_<what>` migration follows it and re-saves the records so the model writes whatever it derives, which is what `backfill_contact_match_keys` does in fifteen lines. Reach for the pair only when rebuilding is not on the table, and say in the pull request why it was not.
- Unless stated otherwise, you MUST include timestamps fields in every migration.

## Foreign keys

- You MUST declare a column that references another table with `$table->foreignIdFor(Model::class)` followed by `->constrained()`. You MUST NOT write `$table->foreign()`, `foreignId()`, `foreignUuid()`, or an `unsignedBigInteger()` column constrained elsewhere. The column and its constraint stand on one line.
- You MUST pass the column name only when it differs from the default of the model (`account_id` for `Account`): `foreignIdFor(User::class, 'created_by_user_id')`.
- `foreignIdFor()` reads the key type of the model: an integer key gives `foreignId()`, a uuid key gives `foreignUuid()` (`Laravel\Passport\Client` for `client_id`). Laravel has no `foreignUuidFor()`.
- You MUST write the column modifiers (`nullable()`, `unique()`, `index()`, `after()`) and `comment()` before `constrained()`, and the delete action after it. `constrained()` returns the constraint, so a column modifier written after it does not reach the column.
- You MUST call `constrained()` with no table and no column, because the model gives both. Pass `indexName:` only when the generated name is 64 characters or longer, as `marketplace_installations` does for its version.
- A column that deliberately carries no foreign key stays `unsignedBigInteger()`, and its comment says why: a morph (`*_id` next to a `*_type`), or a record the row must outlive (`kept_contact_id` and `merged_contact_id` on `contact_merges`).

❌ Don't
```php
$table->unsignedBigInteger('user_id')->nullable()->comment('user who performed the action');
...
$table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
```

Do
```php
$table->foreignIdFor(User::class)->nullable()->comment('user who performed the action')->constrained()->nullOnDelete();
$table->foreignIdFor(User::class, 'created_by_user_id')->comment('user who wrote it')->constrained()->cascadeOnDelete();
```
