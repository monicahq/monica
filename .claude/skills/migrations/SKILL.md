---
name: migrations
description: Use when creating or editing database migrations in the project.
---

# Migrations

## Rules

- You MUST create one table per migration. When a table needs a foreign key on a table created later, you MUST name the migration file so that it runs first (e.g. `0000_01_01_000000_create_companies_table.php` before `0001_01_01_000000_create_users_table.php`), or add the constraint in the later migration with `Schema::table()`.
- When adding foreign keys, you MUST NOT use the `constrained()` method.
- You MUST comment a field by using `->comment('...')` to explain what the field is for.
- Until we are in production (we have at least until end of 2026 for this), you MUST alter existing migrations instead of creating new ones when adding or changing fields. This is to avoid having too many migrations and to keep the database structure clean. Once we are in production, you MAY create new migrations for changes.
- Unless stated otherwise, you MUST include timestamps fields in every migration.

❌ Don't
```php
$table->foreignId('account_id')->comment('account the user belongs to')->constrained()->cascadeOnDelete();
```

Do
```php
$table->unsignedBigInteger('user_id')->nullable()->comment('user who performed the action');
...
// (at the bottom of the migration)
$table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
```
