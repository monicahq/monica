---
name: routes
description: Conventions for defining routes. Use when the user wants to create or modify routes, including route groups, middleware, and naming conventions.
---

# Routes

- You MUST NOT use `Route::resource()` or `Route::apiResource()`. You MUST define each route individually, and name it explicitly.
- You MUST add names to routes, and follow the naming convention `<domain>.<resource>.<action>` (e.g. `account.users.index`).
- You MUST constrain a route parameter that is not a model, with `where()` and a regex, as the six
  token, id and hash parameters of `routes/web.php`, `routes/administrator.php` and `routes/auth.php` do
  (e.g. `->where('token', '[A-Za-z0-9]{64}')`). A parameter that binds to a model needs nothing: the
  model names its own key with `getRouteKeyName()`, usually `uuid`, and refuses anything else.
- You MUST add a comment to every major route group.
- You SHOULD NOT add comments anywhere else, unless they are necessary.
- You MUST NOT use route prefixes. Write the whole URL on the route, `admin/moderation/{version}`
  and not `prefix('admin')` around `moderation/{version}`, so that grepping the URL a browser shows
  lands on the line that declares it. A group is for middleware, and for nothing else.
