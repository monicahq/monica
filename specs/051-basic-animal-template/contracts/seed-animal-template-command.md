# Contract: `monica:seed-animal-template`

```text
php artisan monica:seed-animal-template [--account=<id>]
```

| Case | Output line | Exit |
| --- | --- | --- |
| before each account | `Account <id>…` | |
| seeded | `  Seeded the Animal type and the Basic animal template.` | |
| already there | `  Already has the Basic animal template.` | |
| no user | `  Skipped: nobody signs in to it.` | |
| slug held by the user | `  Skipped: the slug <slug> is already used by one of its own.` | |
| end | `Seeded X, already done Y, skipped Z.` | 0 |
| `--account` unknown | `No account has the id <id>.` | 1 |

Running it twice prints only "already there" lines the second time and writes nothing new.
