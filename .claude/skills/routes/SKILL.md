---
name: routes
description: Conventions for defining routes. Use when the user wants to create or modify routes, including route groups, middleware, and naming conventions.
---

# Routes

- You MUST NOT use `Route::resource()` or `Route::apiResource()`. You MUST define each route individually, and name it explicitly.
- You MUST add names to routes, and follow the naming convention `<domain>.<resource>.<action>` (e.g. `account.users.index`).
- You MUST add validations to route parameters, using the `where()` method to define regex patterns (e.g. `->where('id', '[0-9]+')`).
- You MUST add a comment to every major route group.
- You SHOULD NOT add comments anywhere else, unless they are necessary.
- You MUST NOT use route prefixes.
