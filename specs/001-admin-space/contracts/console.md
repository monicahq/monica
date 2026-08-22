# Console contract: Admin Space

**Feature**: `001-admin-space` | **Date**: 2026-08-21

The one route into the administrator capacity that does not go through the application. It is
how the first administrator of an instance is designated, and how an instance left with none is
recovered (FR-006).

## `monica:make-administrator`

```
php artisan monica:make-administrator {email}
```

Class `App\Console\Commands\MakeAdministratorCommand`.

| Argument | Required | Meaning |
|---|---|---|
| `email` | yes | the address of an existing user of the instance |

Behaviour:

- Grants the administrator capacity to the user with that address, through the same
  `GrantAdministratorAccess` action the admin space uses. The command holds no logic of its own.
- Writes an `AdministratorLog` entry attributed to `Log::SYSTEM`, since nobody signed in took it.
- Prints a confirmation naming the person, and returns `Command::SUCCESS`.

Refusals, each printed as an error with `Command::FAILURE`:

- No user has that address.
- That user already holds the capacity.

Notes:

- The command works on an instance with zero administrators. It is deliberately the only path
  that does, and it requires shell access to the server, which is the authorisation.
- There is no command that withdraws the capacity. Withdrawal happens in the admin space, where
  the last administrator rule and the not yourself rule are enforced together.
