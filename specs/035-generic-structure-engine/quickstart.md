# Quickstart: validating the generic structure engine

The database is rebuilt rather than migrated, so start from a clean one.

```bash
php artisan migrate:fresh --seed --quiet
```

## 1. The rename left nothing behind

The only `template` names that may survive are the product vocabulary FR-009 preserves: the
`templates` table, the Template model, its actions, controllers, routes, screens, API paths and log
copy. Everything else must be gone.

```bash
grep -rn "TemplateVersion\|template_version\|templateVersion" app/ database/ resources/ routes/ tests/ spec/ specs/
```

Expect no match. A match under `resources/docs/api` or in `ContactResource` is the one exception,
per D5 in [research.md](research.md): the contact payload keeps its `template_version_id` key.

## 2. Contacts and entities behave as before

```bash
php artisan test --compact tests/Feature/Controllers/App/Vaults tests/Unit/Actions tests/Unit/Models
```

Expect the suites that covered the engine to pass with the new names and no change in meaning.

## 3. A template and an activity type share one engine

```bash
php artisan tinker --execute 'App\Models\Template::query()->first()->structure()->exists();'
php artisan tinker --execute 'App\Models\ActivityType::query()->first()->structure->publishedVersion->pages()->count();'
```

Expect `true`, then `1`: a new activity type has one published version holding one page and no
sections, created by the same actions a template uses.

## 4. Publishing does not move an existing record

```bash
php artisan test --compact --filter=publishing_a_new_version_does_not_move_an_existing_record
```

Covers a contact, an entity and an activity type structure in one place, per SC-002 and SC-005.

## 5. The whole suite

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
```

PHPStan runs at level 5 in CI and `monica:localize --check` enforces the counted-phrase and
typography rules, so tests passing is not the same as the pull request being green.
